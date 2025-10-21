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

namespace addon\fenxiao\model;

use app\model\BaseModel;
use app\model\goods\Goods as GoodsModel;

/**
 * 分销商品
 */
class FenxiaoGoods extends BaseModel
{

    /**
     * @return array
     */
    public function editGoodsFenxiao($data, $condition)
    {
        $re = model('goods')->update($data, $condition);
        return $this->success($re);
    }

    /**
     * 修改分销状态
     * @param $goods_ids
     * @param $is_fenxiao
     * @param $site_id
     * @return array
     */
    public function modifyGoodsFenxiaoStatus($goods_id, $is_fenxiao, $site_id)
    {
        $fenxiao_goods_skus = model('fenxiao_goods_sku')->getList([ [ 'goods_id', '=', $goods_id ] ]);
        model('goods')->startTrans();
        try {
            if (empty($fenxiao_goods_skus)) {
                $level_list = model('fenxiao_level')->getList();
                $goods_model = new GoodsModel();
                $goods_info = $goods_model->getGoodsDetail($goods_id);
                $fenxiao_goods_sku_data = [];
                foreach ($level_list as $level) {
                    foreach ($goods_info[ 'data' ][ 'sku_data' ] as $sku) {
                        $fenxiao_sku = [
                            'goods_id' => $goods_id,
                            'level_id' => $level[ 'level_id' ],
                            'sku_id' => $sku[ 'sku_id' ],
                            'one_rate' => $level[ 'one_rate' ],
                            'one_money' => 0,
                            'two_rate' => $level[ 'two_rate' ],
                            'two_money' => 0,
                            'three_rate' => $level[ 'three_rate' ],
                            'three_money' => 0,
                        ];
                        $fenxiao_goods_sku_data[] = $fenxiao_sku;
                    }
                }
                model('fenxiao_goods_sku')->addList($fenxiao_goods_sku_data);
            }

            model('goods')->update([ 'is_fenxiao' => $is_fenxiao ], [ [ 'goods_id', '=', $goods_id ], [ 'site_id', '=', $site_id ] ]);
            model('goods')->commit();
            return $this->success(1);
        } catch (\Exception $e) {
            model('goods')->rollback();
            return $this->error($e->getMessage());
        }
    }

    /**
     * 取消参与分销
     * @param $goods_ids
     * @param $site_id
     * @return array
     */
    public function modifyGoodsIsFenxiao($goods_ids, $is_fenxiao, $site_id)
    {
        $res = model('goods')->update([ 'is_fenxiao' => $is_fenxiao ], [ [ 'goods_id', 'in', $goods_ids ], [ 'site_id', '=', $site_id ] ]);
        return $this->success($res);
    }
}