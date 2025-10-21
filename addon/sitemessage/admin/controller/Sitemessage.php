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

namespace addon\sitemessage\admin\controller;

use app\admin\controller\BaseAdmin;
use addon\sitemessage\model\Sitemessage as SitemessageModel;
use app\model\member\MemberLabel;
use app\model\member\MemberLevel;

/**
 * 站内信
 */
class Sitemessage extends BaseAdmin
{

    /**
     * 站内信记录
     */
    public function lists()
    {
        if (request()->isAjax()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $sub_type = input('sub_type', '');
            $condition[] = ['site_id', '=', $this->site_id];
            if (!empty($sub_type)) {
                $condition[] = ['sub_type', '=', $sub_type];
            }
            $sitemessage_model = new SitemessageModel();
            $list = $sitemessage_model->getSiteMessageRecordsPageList($condition, $page, $page_size, 'create_time desc');
            foreach($list['data']['list'] as $k => $v){
                $temp = $sitemessage_model->getSiteMessageCount($v);
                $list['data']['list'][$k]['seed_num'] = $temp['rcount'] ?? 0;
            }
            return $list;
        } else {
            return $this->fetch("sitemessage/lists");
        }
    }

    public function detail()
    {
        $sitemessage_model = new SitemessageModel();
        if (request()->isAjax()) {
            $id = input('id', '');
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $condition = [
                ['records_id', '=', $id]
            ];
            $join = [
                ['member m', 'sm.member_id = m.member_id', 'inner']
            ];
            $alias = 'sm';
            $order = 'sm.id desc';
            $field = 'm.headimg,m.username,m.nickname,sm.id,sm.site_id,sm.member_id,sm.type,sm.is_seed,sm.title,sm.content,sm.message_json,sm.event_type,sm.link,sm.create_time,sm.update_time,sm.records_id,sm.seed_time,sm.scene,sm.sended_status,sm.sended_time';
            $list = $sitemessage_model->getSiteMemberSubMessagePageList($condition, $page, $page_size, $order, $field, $alias, $join);
            return $list;
        } else {
            $member_label = new MemberLabel();
            $member_level = new MemberLevel();

            $id = input('id', '');
            $info = model('site_message_records')->getInfo([['id', '=', $id]]);
//            $formatData_01 = htmlspecialchars_decode($info['text']);//把一些预定义的 HTML 实体转换为字符
//            $formatData_02 = strip_tags($formatData_01);//函数剥去字符串中的 HTML、XML 以及 PHP 的标签,获取纯文本内容
            //获取消息总人数
            $where = [
                ['records_id', '=', $id]
            ];
            $count = $list = model('site_member_sub_message')->getCount($where, 'id');
            $info['member_count'] = $count;
            $info['json'] = json_decode($info['json'], true);
            //json 解析
            if (isset($info['json']['member_level']) && !empty($info['json']['member_level'])) {
                $memberLevelList = $member_level->getMemberLevelList([['level_id', 'IN', $info['json']['member_level']]], 'level_name');
                $level_arr = [];
                foreach ($memberLevelList['data'] as $k => $v) {
                    $level_arr[] = $v['level_name'];
                }
                $info['json']['member_level'] = $level_arr;
            }
            if (isset($info['json']['member_label']) && !empty($info['json']['member_label'])) {
                $memberLabelList = $member_label->getMemberLabelList([['label_id', 'IN', $info['json']['member_label']]], 'label_name');
                $label_arr = [];
                foreach ($memberLabelList['data'] as $k => $v) {
                    $label_arr[] = $v['label_name'];
                }
                $info['json']['member_label'] = $label_arr;
            }
            if (isset($info['json']['remove_member_label']) && !empty($info['json']['remove_member_label'])) {
                $memberLabelList = $member_label->getMemberLabelList([['label_id', 'IN', $info['json']['remove_member_label']]], 'label_name');
                $label_arr = [];
                foreach ($memberLabelList['data'] as $k => $v) {
                    $label_arr[] = $v['label_name'];
                }
                $info['json']['remove_member_label'] = $label_arr;
            }
            //获取三个用户昵称
            $condition = [
                ['records_id', '=', $id]
            ];
            $join = [
                ['member m', 'sm.member_id = m.member_id', 'inner']
            ];
            $alias = 'sm';
            $order = 'sm.id desc';
            $field = 'm.headimg,m.username';
            $list = $sitemessage_model->getSiteMemberSubMessagePageList($condition, 1, 3, $order, $field, $alias, $join);
            $info['member_list'] = $list['data']['list'];
            $mem_count = $sitemessage_model->getSiteMessageCount(['id' => $id]);
            $info['status_name'] = $sitemessage_model->status[$info['status']] ?? '';
//            $app_type = config('app_type');
            $app_type = [
                'wechat' => ['name' => '微信公众号', 'logo' => 'public/static/img/wx_public_number.png'],
                'weapp' => ['name' => '微信小程序', 'logo' => 'public/static/img/wx_small_procedures.png'],
                'h5' => ['name' => 'H5', 'logo' => 'public/static/img/baidu_small_procedures.png'],
            ];
            $temp = [];
            if (!empty($info['scene'])) {
                $scene = explode(',', $info['scene']);
                foreach ($scene as $scene_k => $scene_v) {
                    $temp[] = $app_type[$scene_v]['name'] ?? [];
                }
            } else {
                foreach ($app_type as $type_k => $type_v) {
                    $temp[] = $type_v['name'] ?? [];
                }
            }
            $info['scene'] = implode('、', $temp);

            $this->assign('info', $info);
            $this->assign('count', $mem_count);
            return $this->fetch("sitemessage/detail");
        }
    }


    public function add()
    {
        if (request()->isAjax()) {
            $sitemessage_model = new  SitemessageModel();
            $is_search = input('is_search', '');
            $sex = input('sex', '');
            $member_level = input('level_id', []);
            $member_label = input('label_id', []);
            $reg_time_start = input('reg_time_start', '');
            $reg_time_end = input('reg_time_end', '');
            $remove_member_label = input('remove_member_label', []);
            $event_type = input('event_type', '');
            $content = input('content', '');
            $text = input('text', '');
            $link = input('link', '');
            $scene = input('scene', []);
            $image = input('image', '');
            $is_timing = input('is_timing', 0);
            $timing = input('timing', '');
            if ($is_timing && !empty($timing)) {
                $timing = strtotime($timing);
            }
            if (in_array('all', $scene)) {
                foreach ($scene as $k => $v) {
                    if ($v == 'all') {
                        unset($scene[$k]);
                    }
                }
            }
            $title = input('title', '');
            $json_arr = [];
            if ($is_search == 1) {
                $json_arr = [
                    'sex' => $sex,
                    'member_level' => $member_level,
                    'member_label' => $member_label,
                    'reg_time_start' => $reg_time_start,
                    'reg_time_end' => $reg_time_end,
                    'remove_member_label' => $remove_member_label
                ];
            }
            $json = json_encode($json_arr, true);
            $params = array(
                'site_id' => $this->site_id,
                'json' => $json,
                'is_search' => $is_search,
                'title' => $title,//标题
                'content' => $content,//简略的内容介绍
                'scene' => implode(',', $scene),//场景  h5  pc  wechat等 用,隔开
                'message_json' => '',//消息json 暂时不需要传
                'event_type' => $event_type,//点击事件
                'link' => $event_type == 'link' ? $link : '',//链接
                'text' => $event_type == 'text' ? $text : '',//独立消息页内容  富文本
                'sub_type' => 'group',
                'image' => $image,
                'is_timing' => $is_timing,
                'timing' => $timing
            );
            $res = $sitemessage_model->memberGroupCreate($params);
            return $res;
        } else {
            $member_label = new MemberLabel();
            $member_level = new MemberLevel();
            $member_label_list = $member_label->getMemberLabelList();
            $member_level_list = $member_level->getMemberLevelList();
            $this->assign('member_level_list', $member_level_list['data']);
            $this->assign('member_label_list', $member_label_list['data']);
//            $app_type = config('app_type');
            $app_type = [
                'wechat' => ['name' => '微信公众号', 'logo' => 'public/static/img/wx_public_number.png'],
                'weapp' => ['name' => '微信小程序', 'logo' => 'public/static/img/wx_small_procedures.png'],
                'h5' => ['name' => 'H5', 'logo' => 'public/static/img/baidu_small_procedures.png'],
            ];
            $this->assign('scene', $app_type);
            return $this->fetch("sitemessage/add");
        }
    }


}