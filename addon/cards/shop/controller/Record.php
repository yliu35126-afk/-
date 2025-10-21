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

namespace addon\cards\shop\controller;

use app\model\games\Games;
use app\model\games\Record as RecordModel;
use app\shop\controller\BaseShop;

/**
 * 抽奖记录
 */
class Record extends BaseShop
{
    /*
     *  抽奖记录
     */
    public function lists()
    {
        $game_id = input('game_id', '');
        if (request()->isAjax()) {
            $condition = [
                [ 'site_id', '=', $this->site_id ],
                [ 'game_id', '=', $game_id ]
            ];

            //中奖状态
            $is_winning = input('status', '');
            if ($is_winning !== '') {
                $condition[] = [ 'is_winning', '=', $is_winning ];
            }
            //会员昵称
            $member_nick_name = input('member_nick_name', '');
            if ($member_nick_name) {
                $condition[] = [ 'member_nick_name', 'like', '%' . $member_nick_name . '%' ];
            }
            //参与时间
            $start_time = input('start_time', '');
            $end_time = input('end_time', '');
            if ($start_time && $end_time) {
                $condition[] = [ 'create_time', 'between', [ date_to_time($start_time), date_to_time($end_time) ] ];
            } elseif (!$start_time && $end_time) {
                $condition[] = [ 'create_time', '<=', date_to_time($end_time) ];
            } elseif ($start_time && !$end_time) {
                $condition[] = [ 'create_time', '>=', date_to_time($start_time) ];
            }

            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);

            $model = new RecordModel();
            $list = $model->getGamesDrawRecordPageList($condition, $page, $page_size, 'record_id desc');
            return $list;

        } else {
            $this->assign('game_id', $game_id);
            //游戏活动信息
            $game_model = new Games();
            $game_info = $game_model->getGamesInfo([ [ 'site_id', '=', $this->site_id ], [ 'game_id', '=', $game_id ] ]);
            $this->assign('game_info', $game_info[ 'data' ]);
            return $this->fetch("record/lists");
        }
    }

}