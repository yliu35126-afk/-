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

namespace addon\present\shop\controller;

use addon\present\model\Present as PresentModel;
use app\shop\controller\BaseShop;

/**
 * 赠品控制器
 */
class Present extends BaseShop
{

    /*
     *  赠品活动列表
     */
    public function lists()
    {
        if (request()->isAjax()) {

            $present_model = new PresentModel();
            $condition = [
                [ 'p.site_id', '=', $this->site_id ],
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

            $list = $present_model->getPresentPageList($condition, $page, $page_size, 'p.present_id desc');
            return $list;
        } else {
            return $this->fetch("present/lists");
        }
    }

    /**
     * 添加活动
     */
    public function add()
    {
        if (request()->isAjax()) {

            //获取商品信息
            $present_data = [
                'site_id' => $this->site_id,
                'site_name' => $this->shop_info[ 'site_name' ],
                'start_time' => date_to_time(input('start_time', '')),
                'end_time' => date_to_time(input('end_time', '')),
                'goods_ids' => input('goods_ids', ''),
                'sku_ids' => input('sku_ids', ''),
                'sku_list' => input('sku_list', ''),
            ];

            $present_model = new PresentModel();
            return $present_model->addPresent($present_data);
        } else {
            return $this->fetch("present/add");
        }
    }

    /**
     * 编辑
     * @return array|mixed
     */
    public function edit()
    {
        $present_model = new PresentModel();

        $present_id = input('present_id', '');
        if (request()->isAjax()) {

            //获取商品信息
            $present_data = [
                'present_id' => $present_id,
                'site_id' => $this->site_id,
                'site_name' => $this->shop_info[ 'site_name' ],
                'start_time' => date_to_time(input('start_time', '')),
                'end_time' => date_to_time(input('end_time', '')),
                'goods_ids' => input('goods_ids', ''),
                'sku_ids' => input('sku_ids', ''),
                'sku_list' => input('sku_list', ''),
            ];
            return $present_model->editPresent($present_data);
        } else {
            $present_info = $present_model->getPresentDetail($present_id);
            $this->assign('present_info', $present_info[ 'data' ]);
            return $this->fetch("present/edit");
        }
    }

    /*
     *  赠品详情
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

    /**
     * 赠品商品列表
     */
    public function goodslists()
    {
        $present_model = new PresentModel();
        //获取续签信息
        if (request()->isAjax()) {
            $condition = [
                [ 'sku.goods_state', '=', 1 ],
                [ 'sku.is_delete', '=', 0 ]
            ];
            $condition[] = [ 'pg.site_id', '=', $this->site_id ];
            $status = input('status', '');//赠品状态
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);

            if ($status) {
                $condition[] = [ 'pp.status', '=', $status ];
            }
            $goods_name = input('goods_name', '');
            if (!empty($goods_name)) {
                $condition[] = [ 'sku.sku_name', 'like', '%' . $goods_name . '%' ];
            }

            $field = 'pg.present_id,pp.status,pp.start_time,pp.end_time,pg.sale_num,sku.sku_id,sku.site_id,sku.sku_name,sku.price,sku.sku_spec_format,sku.promotion_type,sku.stock,sku.click_num,sku.sale_num,sku.collect_num,sku.sku_image,sku.sku_images,sku.goods_id,sku.site_id,sku.goods_content,sku.goods_state,sku.verify_state,sku.is_virtual,sku.is_free_shipping,sku.goods_spec_format,sku.goods_attr_format,sku.introduction,sku.unit,sku.video_url,sku.evaluate,sku.goods_id';
            $alias = 'pg';
            $join = [
                [ 'goods_sku sku', 'pg.sku_id = sku.sku_id', 'inner' ],
                [ 'promotion_present pp', 'pp.present_id = pg.present_id', 'inner' ],
            ];
            $list = $present_model->getPresentGoodsPageList($condition, $page, $page_size, 'pp.present_id desc', $field, $alias, $join);
            return $list;
        }
    }
}