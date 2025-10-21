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

namespace addon\present\admin\controller;

use app\admin\controller\BaseAdmin;
use addon\present\model\Present as PresentModel;

/**
 * 赠品控制器
 */
class Present extends BaseAdmin
{

    /*
     *  赠品活动列表
     */
    public function lists()
    {
        if (request()->isAjax()) {

            $present_model = new PresentModel();
            $condition = [
                [ 'g.goods_state', '=', 1 ],
                [ 'g.is_delete', '=', 0 ]
            ];

            $status = input('status', '');//拼团状态
            if ($status) {
                $condition[] = [ 'p.status', '=', $status ];
            }

            $goods_name = input('goods_name', '');
            if ($goods_name) {
                $condition[] = [ 'g.goods_name', 'like', '%' . $goods_name . '%' ];
            }

            $start_time = input('start_time', '');
            $end_time = input('end_time', '');
            if ($start_time && !$end_time) {
                $condition[] = [ 'p.start_time', '>=', date_to_time($start_time) ];
            } elseif (!$start_time && $end_time) {
                $condition[] = [ 'p.end_time', '<=', date_to_time($end_time) ];
            } elseif ($start_time && $end_time) {
                $condition[] = [ 'p.start_time', '>=', date_to_time($start_time) ];
                $condition[] = [ 'p.end_time', '<=', date_to_time($end_time) ];
            }

            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);

            $list = $present_model->getPresentPageList($condition, $page, $page_size, 'g.sort desc,p.present_id desc');
            return $list;
        } else {
            return $this->fetch("present/lists");
        }
    }

    /**
     * 赠品详情
     * @return mixed
     */
    public function detail()
    {
        $present_model = new PresentModel();

        $present_id = input('present_id', '');
        //获取赠品信息
        $present_info = $present_model->getPresentJoinGoodsList($present_id);
        $this->assign('present_info', $present_info);
        return $this->fetch("present/detail");
    }

    /*
     *  删除赠品活动
     */
    public function delete()
    {
        $present_id = input('present_id', '');
        $site_id = $this->site_id;

        $present_model = new PresentModel();
        $condition = array (
            [ 'site_id', '=', $site_id ],
            [ 'present_id', '=', $present_id ]
        );
        return $present_model->deletePresent($condition);
    }

    /*
     *  结束赠品活动
     */
    public function finish()
    {
        $present_id = input('present_id', '');
        $site_id = $this->site_id;

        $present_model = new PresentModel();
        $condition = array (
            [ 'site_id', '=', $site_id ],
            [ 'present_id', '=', $present_id ]
        );
        return $present_model->finishPresent($condition);
    }

    /**
     * 获取商品列表
     * @return array
     */
    public function getSkuList()
    {
        if (request()->isAjax()) {
            $present_model = new PresentModel();
            $present_id = input('present_id', '');
            $pintuan_info = $present_model->getPresentGoodsList($present_id);
            return $pintuan_info;
        }
    }

}