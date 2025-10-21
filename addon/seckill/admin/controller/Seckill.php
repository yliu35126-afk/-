<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\seckill\admin\controller;

use app\admin\controller\BaseAdmin;
use addon\seckill\model\Seckill as SeckillModel;

/**
 * 秒杀控制器
 */
class Seckill extends BaseAdmin
{

    /**
     * 秒杀时间段列表
     */
    public function lists()
    {
        if (request()->isAjax()) {
            $condition = [];
            $order       = 'seckill_start_time asc';
            $field       = '*';

            $seckill_model = new SeckillModel();
            $res           = $seckill_model->getSeckillTimeList($condition, $field, $order, null);
            foreach ($res['data'] as $key => $val) {
                $val                                          = $seckill_model->transformSeckillTime($val);
                $res['data'][$key]['seckill_start_time_show'] = "{$val['start_hour']}:{$val['start_minute']}:{$val['start_second']}";
                $res['data'][$key]['seckill_end_time_show']   = "{$val['end_hour']}:{$val['end_minute']}:{$val['end_second']}";
            }
            return $res;
        } else {
            $this->forthMenu();
            return $this->fetch("seckill/lists");
        }
    }

    /**
     * 添加秒杀时间段
     */
    public function add()
    {
        if (request()->isAjax()) {
            $start_hour   = input('start_hour', 0);
            $start_minute = input('start_minute', 0);
            $start_second = input('start_second', 0);

            $end_hour   = input('end_hour', 0);
            $end_minute = input('end_minute', 0);
            $end_second = input('end_second', 0);

            $data          = [
                'name'               => input('name', ''),
                'seckill_start_time' => $start_hour * 3600 + $start_minute * 60 + $start_second,
                'seckill_end_time'   => $end_hour * 3600 + $end_minute * 60 + $end_second,
                'create_time'        => time(),
            ];
            $seckill_model = new SeckillModel();
            return $seckill_model->addSeckillTime($data);
        } else {
            return $this->fetch("seckill/add");
        }
    }

    /**
     * 编辑秒杀时间段
     */
    public function edit()
    {
        $seckill_model = new SeckillModel();
        if (request()->isAjax()) {
            $start_hour   = input('start_hour', 0);
            $start_minute = input('start_minute', 0);
            $start_second = input('start_second', 0);

            $end_hour   = input('end_hour', 0);
            $end_minute = input('end_minute', 0);
            $end_second = input('end_second', 0);

            $data = [
                'name'               => input('name', ''),
                'seckill_start_time' => $start_hour * 3600 + $start_minute * 60 + $start_second,
                'seckill_end_time'   => $end_hour * 3600 + $end_minute * 60 + $end_second,
                'create_time'        => time(),
                'id'                 => input('seckill_id', 0),
            ];
            return $seckill_model->editSeckillTime($data);
        } else {
            $id = input('seckill_id', 0);
            $this->assign('seckill_id', $id);

            //秒杀详情
            $time_info = $seckill_model->getSeckillTimeInfo([['id', '=', $id]]);
            if (!empty($time_info['data'])) {
                $time_info['data'] = $seckill_model->transformSeckillTime($time_info['data']);
            }
            $this->assign('seckill_info', $time_info['data']);

            return $this->fetch("seckill/edit");
        }
    }

    /**
     * 删除秒杀时间段
     */
    public function delete()
    {
        if (request()->isAjax()) {
            $seckill_time_id    = input('id', 0);
            $seckill_model = new SeckillModel();
            return $seckill_model->deleteSeckillTime($seckill_time_id);
        }
    }
	
	/**
	 * 秒杀商品
	 */
	public function goods()
	{
		if (request()->isAjax()) {
            $page      = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $goods_name  = input('goods_name', '');
            $site_name  = input('site_name', '');
            $status  = input('status', '');

            $condition   = [];
            $condition[] = ['goods_name', 'like', '%' . $goods_name . '%'];
            $condition[] = ['site_name', 'like', '%' . $site_name . '%'];

            if($status !== '') $condition[] = ['status', '=', $status];

            $seckill_model = new SeckillModel();
            $seckill_list = $seckill_model->getSeckillPageList($condition, $page, $page_size, 'id desc');

            $time_list = $seckill_model->getSeckillTimeList([]);

            foreach ($seckill_list['data']['list'] as $k => $v){
                $seckill_list['data']['list'][$k]['time_list'] = [];
                foreach ($time_list['data'] as $index => $item){
                    if(strpos(','.$v['seckill_time_id'].',', ','.$item['id'].',') !== false ){
                        $seckill_list['data']['list'][$k]['time_list'][] = $item;
                    }
                }
            }
            return $seckill_list;

		} else {
			$seckill_id = input('seckill_id', 0);
			$this->assign('seckill_id', $seckill_id);

			return $this->fetch("seckill/goods");
		}
	}
	
	/**
	 * 秒杀商品
	 */
	public function goodslist()
	{
		if (request()->isAjax()) {
			$page = input('page', 1);
			$page_size = input('page_size', PAGE_LIST_ROWS);
            $goods_name = input('goods_name', '');
            $site_name = input('site_name', '');

            $condition = [
                ['g.goods_state','=',1],
                ['g.is_delete','=',0]
            ];
			if(!empty($goods_name)){
                $condition[] = [ 'g.goods_name', 'like', '%' . $goods_name . '%' ];
            }
            if(!empty($site_name)){
                $condition[] = [ 'ps.site_name', 'like', '%' . $site_name . '%' ];
            }

			$join = [
			    ['goods g','g.goods_id = ps.goods_id','inner']
            ];
			$seckill_model = new SeckillModel();
			$res = $seckill_model->getSeckillGoodsPageList($condition, $page, $page_size,'g.sort desc','ps.*','ps',$join);
			
			foreach ($res['data']['list'] as $key => $val) {
				if ($val['price'] != 0) {
					$discount_rate = floor($val['seckill_price'] / $val['price'] * 100);
				} else {
					$discount_rate = 100;
				}
				$res['data']['list'][ $key ]['discount_rate'] = $discount_rate;
				$val = $seckill_model->transformSeckillTime($val);
				$res['data']['list'][ $key ]['seckill_start_time_show'] = "{$val['start_hour']}:{$val['start_minute']}:{$val['start_second']}";
				$res['data']['list'][ $key ]['seckill_end_time_show'] = "{$val['end_hour']}:{$val['end_minute']}:{$val['end_second']}";
			}
			return $res;
			
		} else {
			$this->forthMenu();
			return $this->fetch("seckill/goodslist");
		}
	}
	
	/**
	 * 删除商品
	 */
	public function deleteGoods()
	{
        if (request()->isAjax()) {
            $seckill_id = input('id', 0);

            $seckill_model = new SeckillModel();
            return $seckill_model->deleteSeckillGoods($seckill_id);
        }
	}

    /**
     * 手动关闭秒杀
     * @return array
     */
    public function close()
    {
        if(request()->isAjax()){
            $seckill_model = new SeckillModel();

            $seckill_id = input('seckill_id', '');

            $goods_list = $seckill_model->closeSeckill($seckill_id);
            return $goods_list;
        }
    }

    /**
     * 获取商品列表
     * @return array
     */
    public function getSkuList()
    {
        if(request()->isAjax()){
            $seckill_model = new SeckillModel();

            $seckill_id = input('seckill_id', '');

            $goods_list = $seckill_model->getSeckillGoodsList($seckill_id);
            return $goods_list;
        }
    }
}