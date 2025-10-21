<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\shopcomponent\model;

use app\model\BaseModel;


class Video extends BaseModel
{
    /**
     * 审核状态
     * @var string[]
     */
    private $status = [
        0 => '待处理',
        1 => '已通过',
        -1 => '已拒绝',
    ];

    /**
     * 获取视频号店铺微信号申请列表
     * @param array $condition
     * @param bool $field
     * @param string $order
     * @param int $page
     * @param int $list_rows
     * @param string $alias
     * @param array $join
     * @return array
     */
    public function getVideoPageList($condition = [], $field = true, $order = '', $page = 1, $list_rows = PAGE_LIST_ROWS, $alias = 'a', $join = [])
    {
        $data = model('shopcomponent_account')->pageList($condition, $field, $order, $page, $list_rows, $alias, $join);
        return $this->success($data);
    }


    /**
     * 添加视频号申请
     * @param $param
     */
    public function operationVideo($param)
    {
        $param ['create_time'] = time();
        $param ['update_time'] = time();
        
        if (isset($param['id'])) {

            $param ['status'] = 0;
            $param ['refuse'] = '';

            $res = model('shopcomponent_account')->update($param, [['id', '=', $param['id']]]);
        } else {
            $res = model('shopcomponent_account')->add($param);
        }

        if ($res) {
            return $this->success();
        } else {
            return $this->error();
        }
    }

    /**
     * 获取当前用户的视频号申请信息
     * @param array $condition
     * @param string $field
     */
    public function getVideoInfo($condition, $field = '*')
    {
        $info = model('shopcomponent_account')->getInfo($condition, $field);
        if (!empty($info)) {
            if ($info && $info['status'] == 1) {
                $info['shopcompoent_goods_num'] = model('shopcompoent_goods')->getCount($condition);
            } else if ($info) {
                $info['shopcompoent_goods_num'] = 0;
            }
        }

        return $this->success($info);
    }

    /**
     *  审核通过
     * @param $id
     * @return array
     */
    public function videoPass($id)
    {
        $res = model('shopcomponent_account')->update(
            ['status' => 1, 'update_time' => time(), 'refuse' => ''],
            [['id', '=', $id]]
        );
        return $this->success($res);
    }

    /**
     * 审核拒绝
     * @param $id
     * @param $reason
     * @return array
     */
    public function videoReject($id, $reason)
    {
        $res = model('shopcomponent_account')->update(
            ['status' => -1, 'update_time' => time(), 'refuse' => $reason],
            [['id', '=', $id]]
        );
        return $this->success($res);
    }
}