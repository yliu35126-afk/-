<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\topic\shop\controller;

use addon\topic\model\Topic as TopicModel;
use addon\topic\model\TopicGoods as TopicGoodsModel;
use app\shop\controller\BaseShop;

/**
 * 专题活动
 * @author Administrator
 *
 */
class Topic extends BaseShop
{
    /**
     * 专题活动列表
     */
    public function lists()
    {
        if (request()->isAjax()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $topic_name = input('topic_name', '');

            $condition = [];
            $condition[] = [ 'topic_name', 'like', '%' . $topic_name . '%' ];
            $order = 'modify_time desc,create_time desc';
            $field = '*';

            $topic_model = new TopicModel();
            $res = $topic_model->getTopicPageList($condition, $page, $page_size, $order, $field);
            return $res;
        } else {

            return $this->fetch("topic/lists");
        }
    }

    /**
     * 专题活动商品列表
     */
    public function goodslist()
    {
        $topic_id = input('topic_id', '');
        if (request()->isAjax()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $goods_name = input('goods_name', '');

            $condition = [
                [ 'nptg.site_id', '=', $this->site_id ],
                [ 'nptg.topic_id', '=', $topic_id ],
                [ 'nptg.default', '=', 1 ],
                [ 'g.goods_state', '=', 1 ],
                [ 'g.is_delete', '=', 0 ]
            ];
            if ($goods_name) {
                $condition[] = [ 'g.goods_name', 'like', '%' . $goods_name . '%' ];
            }

            $topic_goods_model = new TopicGoodsModel();
            $res = $topic_goods_model->getTopicGoodsPageList($condition, $page, $page_size);
            return $res;
        } else {

            //活动信息
            $topic_model = new TopicModel();
            $topic_info = $topic_model->getTopicInfo([ [ 'topic_id', '=', $topic_id ] ]);
            $this->assign('info', $topic_info[ 'data' ]);

            $this->assign('topic_id', $topic_id);
            return $this->fetch("topic/goodslist");
        }
    }


    /**
     * 添加专题活动商品
     */
    public function add()
    {
        $topic_goods_model = new TopicGoodsModel();
        if (request()->isAjax()) {

            $topic_id = input("topic_id", 0);
            $goods = input("goods", '{}');


            $res = $topic_goods_model->addTopicGoods($topic_id, $this->site_id, json_decode($goods, true));
            return $res;
        } else {
            $topic_id = input("topic_id", 0);
            //活动信息
            $topic_model = new TopicModel();
            $topic_info = $topic_model->getTopicInfo([ [ 'topic_id', '=', $topic_id ] ]);
            $this->assign('info', $topic_info[ 'data' ]);

            //获取参与中的商品id
            $goods_ids = $topic_goods_model->getTopicGoodsColumn([ [ 'topic_id', '=', $topic_id ] ]);
            $this->assign('goods_ids', $goods_ids[ 'data' ]);

            return $this->fetch('topic/add');
        }

    }

    /**
     * 添加专题活动商品
     */
    public function edit()
    {
        $topic_goods_model = new TopicGoodsModel();
        if (request()->isAjax()) {

            $topic_id = input("topic_id", 0);
            $goods = input("goods", '{}');

            $res = $topic_goods_model->editTopicGoods($topic_id, $this->site_id, json_decode($goods, true));
            return $res;
        } else {

            $topic_id = input("topic_id", 0);
            $goods_id = input("goods_id", 0);

            //活动信息
            $topic_model = new TopicModel();
            $topic_info = $topic_model->getTopicInfo([ [ 'topic_id', '=', $topic_id ] ]);
            $topic_info = $topic_info[ 'data' ];

            //获取商品列表
            $sku_list = $topic_goods_model->getTopicGoodsSku($topic_id, $goods_id, $this->site_id);
            $topic_info[ 'sku_list' ] = $sku_list[ 'data' ];
            $this->assign('info', $topic_info);
            return $this->fetch('topic/edit');
        }

    }

    /**
     * 编辑专题活动商品
     */
    public function editTopicGoods()
    {
        if (request()->isAjax()) {
            $topic_id = input("topic_id", 0);
            $sku_id = input("sku_id", 0);
            $price = input("price", 0);
            $topic_goods_model = new TopicGoodsModel();
            $res = $topic_goods_model->editTopicGoods($topic_id, $this->site_id, $sku_id, $price);
            return $res;
        }
    }

    /**
     * 删除专题活动商品
     */
    public function delete()
    {
        if (request()->isAjax()) {
            $topic_id = input("topic_id", 0);
            $goods_id = input("goods_id", 0);
            $topic_goods_model = new TopicGoodsModel();
            $res = $topic_goods_model->deleteTopicGoods($topic_id, $goods_id);
            return $res;
        }
    }

    public function getSkuList()
    {
        if (request()->isAjax()) {

            $topic_id = input("topic_id", 0);
            $goods_id = input("goods_id", 0);
            $topic_goods_model = new TopicGoodsModel();

            $condition = [
                [ 'ptg.site_id', '=', $this->site_id ],
                [ 'ptg.topic_id', '=', $topic_id ],
                [ 'ptg.goods_id', '=', $goods_id ],
                [ 'g.goods_state', '=', 1 ],
                [ 'g.is_delete', '=', 0 ]
            ];
            $list = $topic_goods_model->getTopicGoodsSkuList($condition);
            return $list;
        }
    }
}