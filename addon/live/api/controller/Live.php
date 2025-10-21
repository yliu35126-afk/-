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

namespace addon\live\api\controller;

use addon\live\model\Room;
use app\api\controller\BaseApi;

/**
 * 直播
 */
class Live extends BaseApi
{

    /**
     * 获取直播间信息
     */
    public function info()
    {
        $room = new Room();
        $temp_condition = [ [ 'audit_status', '=', 1 ] ];
        $site_id = isset($this->params[ 'site_id' ]) ? $this->params[ 'site_id' ] : 0;
        if ($site_id > 0) {
            $temp_condition[] = [ 'site_id', '=', $site_id ];
        }

        $condition = array_merge($temp_condition, [ [ 'live_status', '=', '101' ] ]);

        // 优先查询进行中的
        $room_info = $room->getRoomInfo($condition);
        if (empty($room_info[ 'data' ])) {
            $condition = array_merge($temp_condition, [ [ 'live_status', '=', '102' ] ]);
            // 没有进行中的查询未开始的
            $room_info = $room->getRoomInfo($condition);
        }
        return $this->response($room_info);
    }

    /**
     * 获取直播间列表
     * @return false|string
     */
    public function page()
    {
        $page = isset($this->params[ 'page' ]) ? $this->params[ 'page' ] : 1;
        $page_size = isset($this->params[ 'page_size' ]) ? $this->params[ 'page_size' ] : PAGE_LIST_ROWS;

        $room = new Room();
        $condition = [
            [ 'audit_status', '=', 1 ]
        ];
        $site_id = isset($this->params[ 'site_id' ]) ? $this->params[ 'site_id' ] : 0;
        if ($site_id > 0) {
            $condition[] = [ 'site_id', '=', $site_id ];
        }
        $data = $room->getRoomPageList($condition, '*', 'live_status asc', $page, $page_size);
        return $this->response($data);
    }

    /**
     * 修改直播间状态
     */
    public function modifyLiveStatus()
    {
        $room_id = $this->params[ 'room_id' ] ?? 0;
        $status = $this->params[ 'status' ] ?? '';
        if (empty($status)) return $this->response($this->error());

        $room = new Room();
        $res = $room->updateRoomInfo([ 'live_status' => $status ], [ [ 'roomid', '=', $room_id ] ]);
        return $this->response($res);
    }
}