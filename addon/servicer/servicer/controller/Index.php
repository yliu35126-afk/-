<?php

/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用。
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * =========================================================
 */

namespace addon\servicer\servicer\controller;

use addon\servicer\model\Dialogue;
use addon\servicer\model\Member;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Db;

/**
 * 门店首页
 * @author Administrator
 *
 */
class Index extends BaseServicer
{
    /**
     * 首页
     * @return array|mixed|void
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        $page = input('page', 1);

        $memberModel = new Member();
        $condition = [
            ['servicer_id', '=', $this->uid],
            ['online', '=', 1]
        ];
        $onlineMembers = $memberModel->getList($condition);

        $condition = [
            ['servicer_id', '=', $this->uid],
//            ['online', '=', 0]
        ];
        $offlineMembers = $memberModel->getPageList($condition, true, 'last_online_time desc', $page);

        if (request()->isAjax()) {
            return $this->result(['onlineMembers' => $onlineMembers, 'offlineMembers' => $offlineMembers]);
        }

        $this->assign('servicer', $this->user_info);
        $this->assign('online_members', $onlineMembers);
        $this->assign('online_members_count', @count($onlineMembers) ?? 0);
        $this->assign('offline_members', @$offlineMembers['list'] ?? []);
        $this->assign('offline_members_count', @$offlineMembers['count'] ?? 0);

        $this->assign("menu_info", ['title' => "聊天室"]);
        return $this->fetch('index/index');
    }

    /**
     * 获取聊天记录表
     * @return array|void
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function dialogs()
    {
        $member_id = input('member_id', 0);
        if (empty($member_id)) {
            return $this->result('', -1, '没有指定会员');
        }

        $page = input('page', 1);
        $limit = input('limit', 15);

        $pagelist = (new Dialogue())->getDialogueList($member_id, $page, $limit, $this->site_id);

        return $this->result($pagelist);
    }

    /**
     * 获取会员详情
     * @return array|void
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getMember()
    {
        $member_id = input('member_id', 0);
        if (empty($member_id)) {
            return $this->result('', -1, '没有指定会员');
        }

        $member = (new Member)->getMember($member_id, $this->uid);
        return $this->result($member);
    }

    /**
     * 历史聊天会员
     * @return array|void
     */
    public function historyMembers()
    {
        $page = input('page', 1);
        $size = input('size', 10);

        $prefix = config('database.connections.mysql.prefix');
        $sql = "select sm.member_id,sm.servicer_id,m.nickname as member_name,m.headimg,(select count(sd.read) from {$prefix}servicer_dialogue sd where sd.member_id=sm.member_id and sd.shop_id=s.shop_id and sd.type=0 and sd.read=0) as unread"
            . " from {$prefix}servicer_member sm"
            . " left join {$prefix}member m on m.member_id=sm.member_id"
            . " left join {$prefix}servicer s on s.user_id=sm.servicer_id"
            . " where sm.servicer_id=:id limit :page,:size";
        $offlineMembers = Db::query($sql, ['id' => $this->uid, 'page' => ($page - 1) * $size, 'size' => $size]);

        $total = Db::query("select count(*) as num from " . config('database.connections.mysql.prefix') . "servicer_member sm where sm.servicer_id=:id", ['id' => $this->uid]);
        $total = $total[0]['num'];
        $page_count = is_float($total / $size) ? ceil($total / $size) : $total / $size;

        return $this->result(['offlineMembers' => ['count' => count($offlineMembers), 'list' => $offlineMembers, 'page_count' => $page_count]]);
    }

    public function history()
    {
        $this->assign("menu_info", ['title' => "聊天记录"]);
        return $this->fetch('index/history');
    }

    /**
     * 未读消息联系人
     * @return array|void
     */
    public function unreadMembers()
    {
        $prefix = config('database.connections.mysql.prefix');
        $sql = "select sd.member_id,m.nickname as member_name,m.headimg,count(sd.member_id) as unread from {$prefix}servicer_dialogue sd"
            . " left join {$prefix}member m on m.member_id=sd.member_id "
            . " where sd.type=0 and sd.read=0 and sd.shop_id=:id GROUP BY sd.member_id";
        $unreadMembers = Db::query($sql, ['id' => $this->site_id]);
        return $this->result(['unread_member' => ['count' => count($unreadMembers), 'list' => $unreadMembers]]);
    }

    /**
     * 聊天联系人
     * @return array|void
     */
    public function memberList()
    {
        $member_name = input('member_name', 0);
        $prefix = config('database.connections.mysql.prefix');
        $shop_id = $this->site_id;
        // 获取有过聊天记录的联系人列表，获取未读消息数量，头像、昵称、在线状态，获取最后一次聊天内容、类型
        $sql = "SELECT * FROM (SELECT sd.id,sd.member_id,m.nickname AS member_name,m.headimg,m.login_type,m.login_type_name,IFNULL(sm.`online`, 0) as online,"
            . "sd.create_time,sd.add_time,sd.content_type,sd.content,sd.consumer_say,sd.servicer_say,sd.type,"
            . "(SELECT count(sd1.member_id) FROM {$prefix}servicer_dialogue sd1 where sd1.type=0 AND sd1.shop_id={$shop_id} AND sd1.`read`=0 AND  sd1.member_id = sd.member_id) AS unread "
            . "FROM {$prefix}servicer_dialogue sd "
            . "INNER JOIN {$prefix}member m on m.member_id=sd.member_id "
            . "LEFT JOIN {$prefix}servicer_member sm on sm.member_id=sd.member_id AND sm.online=1 "
            . "WHERE sd.shop_id={$shop_id} "
            . (!empty($member_name) ? "AND m.nickname LIKE '%{$member_name}%' " : '')
            . "ORDER BY `online` desc,sd.id desc) as part "
            . "GROUP BY member_id ORDER BY `online` desc,id desc";
        $members = Db::query($sql);
        return $this->result(['member_list' => ['count' => count($members), 'list' => $members]]);
    }
}
