<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\seckill\shop\controller;

use app\shop\controller\BaseShop;
use addon\seckill\model\Seckill as SeckillModel;

/**
 * 秒杀控制器
 */
class Seckill extends BaseShop
{

    /**
     * 秒杀商品
     */
    public function lists()
    {
        if (request()->isAjax()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $goods_name = input('goods_name', '');
            $status = input('status', '');

            $alias = 'ps';
            $join = [
                [ 'goods g', 'g.goods_id = ps.goods_id', 'inner' ],
            ];
            $field = 'ps.*';

            $condition = [];
            $condition[] = [ 'ps.site_id', '=', $this->site_id ];
            $condition[] = [ 'ps.goods_name', 'like', '%' . $goods_name . '%' ];
            $condition[] = [ 'g.is_delete', '=', 0 ];
            if ($status !== '') $condition[] = [ 'ps.status', '=', $status ];

            $seckill_model = new SeckillModel();
            $seckill_list = $seckill_model->getSeckillPageList($condition, $page, $page_size, 'ps.id desc', $field, $alias, $join);

            $time_list = $seckill_model->getSeckillTimeList([]);

            foreach ($seckill_list[ 'data' ][ 'list' ] as $k => $v) {
                $seckill_list[ 'data' ][ 'list' ][ $k ][ 'time_list' ] = [];
                foreach ($time_list[ 'data' ] as $index => $item) {
                    if (strpos(',' . $v[ 'seckill_time_id' ] . ',', ',' . $item[ 'id' ] . ',') !== false) {
                        $seckill_list[ 'data' ][ 'list' ][ $k ][ 'time_list' ][] = $item;
                    }
                }
            }
            return $seckill_list;
        } else {
            return $this->fetch("seckill/lists");
        }
    }

    /**
     * 添加秒杀商品
     */
    public function add()
    {
        $seckill_model = new SeckillModel();
        if (request()->isAjax()) {
            $data = [
                'site_id' => $this->site_id,
                'site_name' => $this->shop_info[ 'site_name' ],
                'seckill_name' => input('seckill_name', ''),
                'remark' => input('remark', ''),
                'seckill_time_id' => input('seckill_time_id', ''),
                'start_time' => strtotime(input('start_time', '')),
                'end_time' => strtotime(input('end_time', '')),
                'goods_data' => input('goods_data', ''),
                'goods_ids' => input('goods_ids', '')
            ];
            $res = $seckill_model->addSeckillGoods($data);
            return $res;
        } else {
            $seckill_time_list = $seckill_model->getSeckillTimeList([]);
            foreach ($seckill_time_list[ 'data' ] as $key => $val) {
                $val = $seckill_model->transformSeckillTime($val);
                $seckill_time_list[ 'data' ][ $key ][ 'seckill_start_time_show' ] = "{$val['start_hour']}:{$val['start_minute']}:{$val['start_second']}";
                $seckill_time_list[ 'data' ][ $key ][ 'seckill_end_time_show' ] = "{$val['end_hour']}:{$val['end_minute']}:{$val['end_second']}";
            }
            $this->assign('seckill_time_list', $seckill_time_list);

            return $this->fetch("seckill/add");
        }
    }


    /**
     * 更新商品（秒杀价格）
     */
    public function edit()
    {
        $seckill_model = new SeckillModel();
        if (request()->isAjax()) {
            $data = [
                'seckill_name' => input('seckill_name', ''),
                'remark' => input('remark', ''),
                'seckill_time_id' => input('seckill_time_id', ''),
                'start_time' => strtotime(input('start_time', '')),
                'end_time' => strtotime(input('end_time', '')),
                'site_id' => $this->site_id,
                'sku_list' => input('sku_list', ''),
                'goods_ids' => input('goods_ids', ''),
                'id' => input('id', '')
            ];
            $res = $seckill_model->editSeckillGoods($data);
            return $res;
        } else {
            $seckill_id = input('id', '');
            $seckill_info = $seckill_model->getSeckillDetail([ [ 'id', '=', $seckill_id ] ]);

            $seckill_time_id = trim($seckill_info[ 'data' ][ 'seckill_time_id' ],',');
            $time_list = $seckill_model->getSeckillTimeList([ [ 'id', 'in', $seckill_time_id ] ]);

            $this->assign('seckill_info', $seckill_info[ 'data' ]);
            $this->assign('time_list', $time_list[ 'data' ]);
            return $this->fetch("seckill/edit");
        }
    }


    /**
     * 秒杀时间段列表
     */
    public function seckillTimeList()
    {
        if (request()->isAjax()) {
            $condition = [];
            $order = 'seckill_start_time asc';
            $field = '*';

            $seckill_model = new SeckillModel();
            $res = $seckill_model->getSeckillTimeList($condition, $field, $order, null);
            foreach ($res[ 'data' ] as $key => $val) {
                $val = $seckill_model->transformSeckillTime($val);
                $res[ 'data' ][ $key ][ 'seckill_start_time_show' ] = "{$val['start_hour']}:{$val['start_minute']}:{$val['start_second']}";
                $res[ 'data' ][ $key ][ 'seckill_end_time_show' ] = "{$val['end_hour']}:{$val['end_minute']}:{$val['end_second']}";
            }
            return $res;
        } else {
            $this->forthMenu();
            return $this->fetch("seckill/seckill_time_list");
        }
    }


    /**
     * 删除商品
     */
    public function delete()
    {
        if (request()->isAjax()) {
            $seckill_id = input('id', 0);

            $seckill_model = new SeckillModel();
            return $seckill_model->deleteSeckillGoods($seckill_id);
        }
    }


    /**
     * 秒杀时段
     */
    public function seckilltimeselect()
    {

        if (request()->isAjax()) {
            $condition[] = [ 'site_id', '=', $this->site_id ];
            $order = 'seckill_start_time asc';
            $field = '*';

            $seckill_model = new SeckillModel();
            $res = $seckill_model->getSeckillTimeList($condition, $field, $order, null);
            foreach ($res[ 'data' ] as $key => $val) {
                $val = $seckill_model->transformSeckillTime($val);
                $res[ 'data' ][ $key ][ 'seckill_start_time_show' ] = "{$val['start_hour']}:{$val['start_minute']}:{$val['start_second']}";
                $res[ 'data' ][ $key ][ 'seckill_end_time_show' ] = "{$val['end_hour']}:{$val['end_minute']}:{$val['end_second']}";
            }
            return $res;
        } else {
            $this->forthMenu();
            return $this->fetch("seckill/seckilltimeselect");
        }

    }


    /**
     * 获取商品列表
     * @return array
     */
    public function getSkuList()
    {
        if (request()->isAjax()) {
            $seckill_model = new SeckillModel();

            $seckill_id = input('seckill_id', '');

            $goods_list = $seckill_model->getSeckillGoodsList($seckill_id);
            return $goods_list;
        }
    }

    /**
     * 手动关闭秒杀
     * @return array
     */
    public function close()
    {
        if (request()->isAjax()) {
            $seckill_model = new SeckillModel();

            $seckill_id = input('seckill_id', '');

            $goods_list = $seckill_model->closeSeckill($seckill_id);
            return $goods_list;
        }
    }


}