<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\groupbuy\admin\controller;

use app\admin\controller\BaseAdmin;
use addon\groupbuy\model\Groupbuy as GroupbuyModel;

/**
 * 团购控制器
 */
class Groupbuy extends BaseAdmin
{

    /*
     *  团购活动列表
     */
    public function lists()
    {
        $model = new GroupbuyModel();
        //获取续签信息
        if (request()->isAjax()) {

            $condition = [
                [ 'g.goods_state', '=', 1 ],
                [ 'g.is_delete', '=', 0 ]
            ];

            $goods_name = input('goods_name', '');
            $status = input('status', '');//团购状态
            if ($status) {
                $condition[] = [ 'pg.status', '=', $status ];
            }
            if (!empty($goods_name)) {
                $condition[] = [ 'g.goods_name', 'like', '%' . $goods_name . '%' ];
            }

            $start_time = input('start_time', '');
            $end_time = input('end_time', '');
            if ($start_time && !$end_time) {
                $condition[] = [ 'pg.start_time', '>=', date_to_time($start_time) ];
            } elseif (!$start_time && $end_time) {
                $condition[] = [ 'pg.end_time', '<=', date_to_time($end_time) ];
            } elseif ($start_time && $end_time) {
                $condition[] = [ 'pg.start_time', '>=', date_to_time($start_time) ];
                $condition[] = [ 'pg.end_time', '<=', date_to_time($end_time) ];
            }

            //排序
            $order = input('order', '');
            $sort = input('sort', '');
            if ($order == '') {
                $order_by = 'g.sort desc,pg.create_time desc';
            } else {
                $order_by = 'pg.' . $order . ' ' . $sort . ',pg.create_time desc';
            }

            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);

            $list = $model->getGroupbuyPageList($condition, $page, $page_size, $order_by);
            return $list;
        } else {
            return $this->fetch("groupbuy/lists");
        }
    }

    /*
     *  团购详情
     */
    public function detail()
    {
        $groupbuy_model = new GroupbuyModel();

        $groupbuy_id = input('groupbuy_id', '');
        //获取团购信息
        $condition = [
            [ 'pg.groupbuy_id', '=', $groupbuy_id ],
            [ 'g.goods_state', '=', 1 ], [ 'g.is_delete', '=', 0 ]
        ];
        $groupbuy_info = $groupbuy_model->getGroupbuyInfo($condition);
        $this->assign('groupbuy_info', $groupbuy_info);
        return $this->fetch("groupbuy/detail");
    }

    /*
     *  删除团购活动
     */
    public function delete()
    {
        $groupbuy_id = input('groupbuy_id', '');

        $groupbuy_model = new GroupbuyModel();
        return $groupbuy_model->deleteGroupbuy($groupbuy_id);
    }

    /*
     *  结束团购活动
     */
    public function finish()
    {
        $groupbuy_id = input('groupbuy_id', '');

        $groupbuy_model = new GroupbuyModel();
        return $groupbuy_model->finishGroupbuy($groupbuy_id);
    }

}