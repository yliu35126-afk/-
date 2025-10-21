<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace addon\pintuan\shop\controller;

use addon\pintuan\model\PintuanOrder;
use app\shop\controller\BaseShop;
use addon\pintuan\model\Pintuan as PintuanModel;
use addon\pintuan\model\PintuanGroup as PintuanGroupModel;

/**
 * 拼团控制器
 */
class Pintuan extends BaseShop
{

    /*
     *  拼团活动列表
     */
    public function lists()
    {
        if (request()->isAjax()) {

            $model       = new PintuanModel();
            $condition = [
                ['p.site_id', '=', $this->site_id],
                ['g.goods_state', '=', 1],
                ['g.is_delete', '=', 0]
            ];

            $status = input('status', '');//拼团状态
            if ($status) {
                if ($status == 6) {
                    $condition[] = ['p.status', '=', 0];
                } else {
                    $condition[] = ['p.status', '=', $status];
                }
            }
            $goods_name = input('goods_name', '');
            if ($goods_name) {
                $condition[] = ['g.goods_name', 'like', '%' . $goods_name . '%'];
            }
            $start_time = input('start_time', '');
            $end_time   = input('end_time', '');
            if ($start_time && !$end_time) {
                $condition[] = ['p.start_time', '>=', date_to_time($start_time)];
            } elseif (!$start_time && $end_time) {
                $condition[] = ['p.end_time', '<=', date_to_time($end_time)];
            } elseif ($start_time && $end_time) {
                $condition[] = ['p.start_time', '>=', date_to_time($start_time)];
                $condition[] = ['p.end_time', '<=', date_to_time($end_time)];
            }

            //排序
            $order = input('order', 'create_time');
            $sort = input('sort', 'desc');
            if($order == 'create_time'){
                $order_by = 'p.'.$order . ' ' . $sort;
            }else{
                $order_by = 'p.'.$order . ' ' . $sort.',p.create_time desc';
            }

            $page      = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);

            $list      = $model->getPintuanPageList($condition, $page, $page_size, $order_by);
            return $list;

        } else {

            $this->forthMenu();
            return $this->fetch("pintuan/lists");
        }
    }

    /**
     * 添加活动
     */
    public function add()
    {
        if (request()->isAjax()) {
            $pintuan_data = [
                'site_id'          => $this->site_id,
                'site_name'        => $this->shop_info['site_name'],
                'pintuan_name'     => input('pintuan_name', ''),//活动名称
                'is_virtual_goods' => input('is_virtual_goods', ''),//是否是虚拟商品
                'pintuan_num'      => input('pintuan_num', ''),//参团人数
                'pintuan_time'     => input('pintuan_time', ''),//拼团有效期
                'remark'           => input('remark', ''),//备注
                'is_recommend'     => input('is_recommend', ''),//是否推荐
                'start_time'       => date_to_time(input('start_time', '')),//开始时间
                'end_time'         => date_to_time(input('end_time', '')),//结束时间
                'buy_num'          => input('buy_num', ''),//拼团限制购买
                'is_single_buy'    => input('is_single_buy', ''),//是否单独购买
                'is_virtual_buy'   => input('is_virtual_buy', ''),//是否虚拟成团
                'is_promotion'     => input('is_promotion', ''),//是否团长优惠
            ];
            $goods = [
                'goods_ids' => input('goods_ids', ''),
                'sku_ids' => input('sku_ids', ''),
            ];
            $sku_list      = input('sku_list', '');
            $pintuan_model = new PintuanModel();
            return $pintuan_model->addPintuan($pintuan_data, $goods, $sku_list);
        } else {

            $pintuan_name = '拼团 '.date('Y-m-d');
            $this->assign('pintuan_name',$pintuan_name);
            return $this->fetch("pintuan/add");
        }
    }

    /**
     * 编辑活动
     */
    public function edit()
    {
        $pintuan_model = new PintuanModel();
        if (request()->isAjax()) {

            $pintuan_data = [
                'pintuan_id'       => input('pintuan_id', ''),
                'site_id'          => $this->site_id,
                'pintuan_name'     => input('pintuan_name', ''),//活动名称
                'goods_id'         => input('goods_id', ''),//商品ID
                'is_virtual_goods' => input('is_virtual_goods', ''),//是否是虚拟商品
                'pintuan_num'      => input('pintuan_num', ''),//参团人数
                'pintuan_time'     => input('pintuan_time', ''),//拼团有效期
                'remark'           => input('remark', ''),//备注
                'is_recommend'     => input('is_recommend', ''),//是否推荐
                'start_time'       => date_to_time(input('start_time', '')),//开始时间
                'end_time'         => date_to_time(input('end_time', '')),//结束时间
                'buy_num'          => input('buy_num', ''),//拼团限制购买
                'is_single_buy'    => input('is_single_buy', ''),//是否单独购买
                'is_virtual_buy'   => input('is_virtual_buy', ''),//是否虚拟成团
                'is_promotion'     => input('is_promotion', ''),//是否团长优惠
            ];

            $sku_list   = input('sku_list', '');
            $goods = [
                'goods_id' => input('goods_id', ''),
                'sku_ids' => input('sku_ids', ''),
            ];
            return $pintuan_model->editPintuan($pintuan_data, $goods, $sku_list);

        } else {
            $pintuan_id = input('pintuan_id', '');
            //获取拼团信息
            $pintuan_info = $pintuan_model->getPintuanDetail($pintuan_id);
            $this->assign('pintuan_info', $pintuan_info);
            return $this->fetch("pintuan/edit");
        }
    }

    /*
     *  拼团详情
     */
    public function detail()
    {
        $pintuan_model = new PintuanModel();

        $pintuan_id = input('pintuan_id', '');
        //获取拼团信息
        $pintuan_info = $pintuan_model->getPintuanJoinGoodsList($pintuan_id);
        $this->assign('pintuan_info', $pintuan_info);
        return $this->fetch("pintuan/detail");
    }

    /*
     *  删除拼团活动
     */
    public function delete()
    {
        $pintuan_id = input('pintuan_id', '');

        $pintuan_model = new PintuanModel();
        return $pintuan_model->deletePintuan($pintuan_id);
    }

    /*
     *  拼团活动失效
     */
    public function invalid()
    {
        $pintuan_id = input('pintuan_id', '');

        $pintuan_model = new PintuanModel();
        return $pintuan_model->invalidPintuan($pintuan_id);
    }

    /**********************************  开团团队    ******************************************************/

    /*
     *  开团团队列表
     */
    public function group()
    {
        $model = new PintuanGroupModel();

        $condition[] = ['pg.site_id', '=', $this->site_id];
        $pintuan_id  = input('pintuan_id', '');
        if ($pintuan_id) {
            $condition[] = ['pg.pintuan_id', '=', $pintuan_id];
        }
        //获取续签信息
        if (request()->isAjax()) {
            $goods_name = input('goods_name', '');
            $nickname = input('nickname', '');
            $start_time = input('start_time', '');
            $end_time   = input('end_time', '');
            $status = input('status', '');//拼团状态
            $page      = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);

            if ($goods_name) {
                $condition[] = ['g.goods_name', 'like', '%' . $goods_name . '%'];
            }

            if ($nickname) {
                $condition[] = ['m.nickname', 'like', '%' . $nickname . '%'];
            }

            if ($start_time && !$end_time) {
                $condition[] = ['pg.create_time', '>=', date_to_time($start_time)];
            } elseif (!$start_time && $end_time) {
                $condition[] = ['pg.create_time', '<=', date_to_time($end_time)];
            } elseif ($start_time && $end_time) {
                $condition[] = ['pg.create_time', 'between', [date_to_time($start_time), date_to_time($end_time) ]];
            }

            if ($status) {
                if ($status == 6) {
                    $condition[] = ['pg.status', '=', 0];
                } else {
                    $condition[] = ['pg.status', '=', $status];
                }
            }

            $list      = $model->getPintuanGroupPageList($condition, $page, $page_size, 'pg.group_id desc');
            return $list;

        } else {

            $this->assign('pintuan_id',$pintuan_id);
            $this->forthMenu();
            return $this->fetch("pintuan/group");
        }

    }

    /*
     *  拼团组成员订单列表
     */
    public function groupOrder()
    {
        $model = new PintuanOrder();

        $condition   = [];
        $condition[] = ['ppo.pintuan_status', 'in', '2,3'];
        $group_id    = input('group_id', '');
        if ($group_id) {
            $condition[] = ['ppo.group_id', '=', $group_id];
        }
        //获取续签信息
        if (request()->isAjax()) {

            $page      = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $list      = $model->getPintuanOrderPageList($condition, $page, $page_size, 'ppo.id desc');
            return $list;

        } else {
            $this->assign('group_id', $group_id);

            //获取团长信息
            $pintuan_group_model = new PintuanGroupModel();
            $info = $pintuan_group_model->getPintuanGroupDetail([['pg.group_id','=',$group_id],['pg.site_id','=',$this->site_id]]);
            $this->assign('info',$info['data']);
            return $this->fetch("pintuan/group_order");
        }
    }

    /**
     * 获取商品列表
     * @return array
     */
    public function getSkuList()
    {
        if(request()->isAjax()){
            $pintuan_model = new PintuanModel();

            $pintuan_id = input('pintuan_id', '');

            $pintuan_info = $pintuan_model->getPintuanGoodsList($pintuan_id);
            return $pintuan_info;
        }
    }
}