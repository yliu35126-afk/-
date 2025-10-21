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

namespace addon\seckill\api\controller;

use addon\seckill\model\Seckill as SeckillModel;
use app\api\controller\BaseApi;

/**
 * 秒杀
 */
class Seckill extends BaseApi
{
    /**
     * 列表信息
     */
    public function lists()
    {
        $today_time = strtotime(date("Y-m-d"), time());
        $time = time() - $today_time;//当日时间戳

        $site_id = isset($this->params['site_id']) ? $this->params['site_id'] : 0;

        $condition = [
            ['seckill_end_time', '>=', $time]
        ];
        $order = 'seckill_start_time asc';
        $field = 'id,name,seckill_start_time,seckill_end_time';

        $seckill_model = new SeckillModel();
        $list = $seckill_model->getGoodsSeckillTimeList($condition, $field, $order);
        $list = $list['data'];
        foreach ($list as $key => $val) {
            $val = $seckill_model->transformSeckillTime($val);
            $list[$key]['seckill_start_time_show'] = "{$val['start_hour']}:{$val['start_minute']}:{$val['start_second']}";
            $list[$key]['seckill_end_time_show'] = "{$val['end_hour']}:{$val['end_minute']}:{$val['end_second']}";

            //判断该时段下是否有商品
            $seckill_model = new SeckillModel();
            $goods_condition = [
                [ 'ps.status', '=', 1 ],
                [ 'g.goods_state', '=', 1 ],
                [ 'g.verify_state', '=', 1 ],
                [ 'g.is_delete', '=', 0 ],
                [ 'ps.seckill_time_id', 'like', '%,'.$val['id'].',%' ],
                [ 's.shop_status', '=', 1]
            ];
            if($site_id > 0){
                $goods_condition[] = [ 'g.site_id', '=', $site_id];
            }
            $goods_sku_detail = $seckill_model->getSeckillGoodsByTimeInfo($goods_condition);

            if(empty($goods_sku_detail['data'])){
                unset($list[$key]);
            }
        }
        $res = [
            'list' => array_values($list)
        ];
        return $this->response($this->success($res));
    }
}