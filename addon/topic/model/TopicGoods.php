<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\topic\model;

use app\model\BaseModel;
use app\model\goods\Goods;

/**
 * 专题活动
 */
class TopicGoods extends BaseModel
{

    /**
     * 添加专题商品
     * @param $topic_id
     * @param $site_id
     * @param $sku_ids
     * @return array
     */
    public function addTopicGoods($topic_id, $site_id, $goods_list)
    {
        if (empty($goods_list)) return $this->error('', '请选择活动商品');

        //获取活动信息
        $topic_model = new Topic();
        $topic_info = $topic_model->getTopicInfo([['topic_id', '=', $topic_id]]);
        if (empty($topic_info['data'])) {
            return $this->error('', '该专题活动不存在');
        }

        if ($topic_info['data']['status'] == 3) {
            return $this->error('', '该专题活动已结束');
        }

        model('promotion_topic_goods')->startTrans();
        try {

            $sku_list = [];
            foreach ($goods_list as $good_item) {

                if (count($good_item) > 1) array_multisort(array_column($good_item, 'topic_price'), SORT_ASC, $good_item);

                foreach ($good_item as $k => $sku_item) {
                    array_push($sku_list, [
                        'topic_id' => $topic_id,
                        'start_time' => $topic_info['data']['start_time'],
                        'end_time' => $topic_info['data']['end_time'],
                        'site_id' => $site_id,
                        'sku_id' => $sku_item['sku_id'],
                        'topic_price' => $sku_item['topic_price'],
                        'goods_id' => $sku_item['goods_id'],
                        'default' => $k == 0 ? 1 : 0
                    ]);
                }
            }
            model('promotion_topic_goods')->addList($sku_list);

            model('promotion_topic_goods')->commit();
            return $this->success();
        } catch (\Exception $e) {

            model('promotion_topic_goods')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 修改专题商品
     * @param $topic_id
     * @param $site_id
     * @param $goods_list
     * @return array
     */
    public function editTopicGoods($topic_id, $site_id, $goods_list)
    {
        if (empty($goods_list)) return $this->error('', '请选择活动商品');

        //获取活动信息
        $topic_model = new Topic();
        $topic_info = $topic_model->getTopicInfo([['topic_id', '=', $topic_id]]);
        if (empty($topic_info['data'])) {
            return $this->error('', '该专题活动不存在');
        }

        if ($topic_info['data']['status'] == 3) {
            return $this->error('', '该专题活动已结束');
        }

        model('promotion_topic_goods')->startTrans();
        try {

            $sku_list = [];
            foreach ($goods_list as $item => $good_item) {

                $goods_id = explode('_', $item);
                model('promotion_topic_goods')->delete([['topic_id', '=', $topic_id], ['goods_id', '=', $goods_id[1]]]);

                if (count($good_item) > 1) array_multisort(array_column($good_item, 'topic_price'), SORT_ASC, $good_item);

                foreach ($good_item as $k => $sku_item) {
                    array_push($sku_list, [
                        'topic_id' => $topic_id,
                        'start_time' => $topic_info['data']['start_time'],
                        'end_time' => $topic_info['data']['end_time'],
                        'site_id' => $site_id,
                        'sku_id' => $sku_item['sku_id'],
                        'topic_price' => $sku_item['topic_price'],
                        'goods_id' => $sku_item['goods_id'],
                        'default' => $k == 0 ? 1 : 0
                    ]);
                }
            }
            model('promotion_topic_goods')->addList($sku_list);

            model('promotion_topic_goods')->commit();
            return $this->success();
        } catch (\Exception $e) {

            model('promotion_topic_goods')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 删除专题商品
     * @param $topic_id
     * @param $site_id
     * @param $sku_id
     * @return array
     */
    public function deleteTopicGoods($topic_id, $goods_id)
    {
        $goods = new Goods();
        $goods->modifyPromotionAddon($goods_id, ['topic' => $topic_id], true);
        model("promotion_topic_goods")->delete([['topic_id', '=', $topic_id], ['goods_id', '=', $goods_id]]);
        return $this->success();
    }


    /**
     * 获取专题商品详情
     * @param $condition
     * @param string $field
     * @return array
     */
    public function getTopicGoodsDetail($condition, $field = 'sku.goods_id,sku.sku_id,sku.sku_name,sku.sku_spec_format,sku.price,sku.promotion_type,sku.stock,sku.click_num,g.sale_num,sku.collect_num,sku.sku_image,sku.sku_images,sku.site_id,sku.goods_content,sku.goods_state,sku.is_virtual,sku.is_free_shipping,sku.goods_spec_format,sku.goods_attr_format,sku.introduction,sku.unit,sku.video_url,sku.evaluate,ptg.id,ptg.topic_id,ptg.start_time,ptg.end_time,ptg.topic_price,pt.topic_name,g.goods_image,g.goods_stock,g.goods_name,g.verify_state')
    {
        $alias = 'ptg';
        $join = [
            ['goods_sku sku', 'ptg.sku_id = sku.sku_id', 'inner'],
            ['goods g', 'g.goods_id = sku.goods_id', 'inner'],
            ['promotion_topic pt', 'pt.topic_id = ptg.topic_id', 'inner'],
        ];

        $list = model('promotion_topic_goods')->getInfo($condition, $field, $alias, $join);
        return $this->success($list);
    }


    /**
     * 获取商品参与的sku列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @return array
     */
    public function getTopicGoodsSkuList($condition = [], $field = 'ptg.*,sku.sku_name,sku.sku_image,sku.price,sku.stock,sku.sale_num', $order = 'ptg.topic_price asc')
    {
        $alias = 'ptg';
        $join = [
            ['goods g', 'g.goods_id = ptg.goods_id', 'inner'],
            ['goods_sku sku', 'sku.sku_id = ptg.sku_id', 'inner'],
        ];

        $list = model('promotion_topic_goods')->getList($condition, $field, $order, $alias, $join);
        return $this->success($list);
    }


    /**
     * @param $topic_id
     * @param $goods_id
     * @param $site_id
     */
    public function getTopicGoodsSku($topic_id, $goods_id, $site_id)
    {
        $goods_list = model('goods_sku')->getList(
            [['goods_id', '=', $goods_id]],
            'goods_id,sku_id,sku_name,price,sku_image,stock'
        );

        foreach ($goods_list as $k => $v) {
            $topic_goods = model('promotion_topic_goods')->getInfo(
                [['topic_id', '=', $topic_id], ['sku_id', '=', $v['sku_id']], ['goods_id', '=', $goods_id], ['site_id', '=', $site_id]],
                'topic_price'
            );
            if (empty($topic_goods)) {
                $topic_goods = [
                    'topic_price' => 0,
                ];
            }
            $goods_list[$k] = array_merge($v, $topic_goods);
        }
        return $this->success($goods_list);
    }

    /**
     * 获取专题商品sku列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getTopicGoodsPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '')
    {
        if (empty($field)) {
            $field = 'ngs.sku_id, ngs.sku_name, ngs.sku_no, ngs.sku_spec_format, ngs.price, ngs.market_price,
	        ngs.cost_price, ngs.discount_price, ngs.promotion_type, ngs.stock,
	        ngs.weight, ngs.volume, ngs.click_num, ngs.sale_num, ngs.collect_num, ngs.sku_image,
	        ngs.sku_images, ngs.goods_id, ngs.goods_class, ngs.goods_class_name, ngs.goods_attr_class,
	        ngs.goods_attr_name, ngs.goods_name, ngs.site_id, ngs.site_name,
	        ngs.goods_content, ngs.goods_state,ngs.goods_stock_alarm,
	        ngs.is_virtual, ngs.virtual_indate, ngs.is_free_shipping, ngs.shipping_template, ngs.goods_spec_format,
	        ngs.goods_attr_format, ngs.is_delete, ngs.introduction, ngs.keywords, ngs.unit, ngs.sort,npt.topic_name,
	        npt.topic_adv, npt.status, nptg.start_time, nptg.end_time, nptg.topic_price, npt.topic_id,g.goods_image';
        }

        $alias = 'nptg';
        $join = [
            ['goods g', 'nptg.goods_id = g.goods_id', 'inner'],
            ['goods_sku ngs', 'nptg.sku_id = ngs.sku_id', 'inner'],
            ['promotion_topic npt', 'nptg.topic_id = npt.topic_id', 'inner'],
            ['shop s', 's.site_id = ngs.site_id', 'inner']
        ];
        $list = model('promotion_topic_goods')->pageList($condition, $field, $order, $page, $page_size, $alias, $join);
        return $this->success($list);
    }


    /**
     *
     * @param $condition
     * @param string $field
     * @return array
     */
    public function getTopicGoodsColumn($condition, $field = 'goods_id')
    {
        $info = model('promotion_topic_goods')->getColumn($condition, $field);
        return $this->success($info);
    }

    /**
     * 判断规格值是否禁用
     * @param $topic_id
     * @param $site_id
     * @param $goods
     * @return mixed
     */
    public function getGoodsSpecFormat($topic_id, $goods_spec_format = '', $sku_id = 0)
    {
        //获取活动参与的商品sku_ids
        $sku_ids = model('promotion_topic_goods')->getColumn([['topic_id', '=', $topic_id]], 'sku_id');
        $goods_model = new Goods();
        if ($sku_id == 0) {
            $res = $goods_model->getGoodsSpecFormat($sku_ids, $goods_spec_format);
        } else {
            $res = $goods_model->getEmptyGoodsSpecFormat($sku_ids, $sku_id);
        }
        return $res;
    }

}