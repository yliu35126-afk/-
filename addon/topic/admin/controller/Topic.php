<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\topic\admin\controller;

use app\admin\controller\BaseAdmin;
use addon\topic\model\Topic as TopicModel;
use addon\topic\model\TopicGoods as TopicGoodsModel;

/**
 * 专题控制器
 */
class Topic extends BaseAdmin
{
	
	/**
	 * 专题列表
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
			$this->forthMenu();
			return $this->fetch("topic/lists");
		}
	}
	
	/**
	 * 添加专题活动
	 */
	public function add()
	{
		if (request()->isAjax()) {
			$topic_name = input("topic_name", '');
			$start_time = input("start_time", 0);
			$end_time = input("end_time", 0);
			$remark = input("remark", '');
			$topic_adv = input("topic_adv", '');
			$bg_color = input("bg_color", '#ffffff');
			$topic_model = new TopicModel();
			$data = array(
				"topic_name" => $topic_name,
				"start_time" => $start_time,
				"end_time" => $end_time,
				"remark" => $remark,
				"topic_adv" => $topic_adv,
				'bg_color' => $bg_color
			);
			$res = $topic_model->addTopic($data);
			return $res;
		} else {
			return $this->fetch("topic/add");
		}
	}
	
	/**
	 * 编辑专题活动
	 */
	public function edit()
	{
		$topic_id = input("topic_id", '');
		$topic_model = new TopicModel();
		if (request()->isAjax()) {
			$topic_name = input("topic_name", '');
			$start_time = input("start_time", 0);
			$end_time = input("end_time", 0);
			$remark = input("remark", '');
			$topic_adv = input("topic_adv", '');
			$bg_color = input("bg_color", '#ffffff');
			
			$data = array(
				"topic_name" => $topic_name,
				"start_time" => $start_time,
				"end_time" => $end_time,
				"remark" => $remark,
				"topic_adv" => $topic_adv,
				"topic_id" => $topic_id,
				'bg_color' => $bg_color
			);
			$res = $topic_model->editTopic($data);
			return $res;
		} else {
			$condition = array(
				[ "topic_id", "=", $topic_id ]
			);
			$topic_info_result = $topic_model->getTopicInfo($condition);
			$this->assign("info", $topic_info_result["data"]);
			return $this->fetch("topic/edit");
		}
	}
	
	/**
	 * 删除专题活动
	 */
	public function delete()
	{
        if (request()->isAjax()) {
            $topic_id = input("topic_id", 0);
            $topic_goods_model = new TopicModel();
            $res = $topic_goods_model->deleteTopic($topic_id);
            return $res;
        }
	}
	
	/**
	 * 专题活动商品列表
	 */
	public function goods()
	{
		$topic_id = input("topic_id", 0);
		if (request()->isAjax()) {
			$page = input('page', 1);
			$page_size = input('page_size', PAGE_LIST_ROWS);
			$goods_name = input('goods_name', '');
            $site_name = input('site_name', '');
			
			$condition = [[ 'nptg.topic_id', '=', $topic_id ]];
			if(empty($goods_name)){
                $condition[] = [ 'g.goods_name', 'like', '%' . $goods_name . '%' ];
            }
            if(empty($site_name)){
                $condition[] = [ 'g.site_name', 'like', '%' . $site_name . '%' ];
            }
			$order = 'g.sort desc';
			$topic_goods_model = new TopicGoodsModel();

			$res = $topic_goods_model->getTopicGoodsPageList($condition, $page, $page_size, $order);
			return $res;
		} else {
            //活动信息
            $topic_model = new TopicModel();
            $topic_info = $topic_model->getTopicInfo([['topic_id','=',$topic_id]]);
            $this->assign('info',$topic_info['data']);

            $this->assign("topic_id", $topic_id);
			return $this->fetch("topic/goods");
		}
	}
	
	/**
	 * 专题活动商品列表
	 */
	public function goodslist()
	{
        $topic_id = input('topic_id','');
        if (request()->isAjax()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $goods_name = input('goods_name', '');

            $condition = [
                ['nptg.topic_id','=',$topic_id],
                ['nptg.default','=',1],
                ['g.goods_state','=',1],
                ['g.is_delete', '=', 0]
            ];
            if($goods_name){
                $condition[] = [ 'g.goods_name', 'like', '%' . $goods_name . '%' ];
            }

            $topic_goods_model = new TopicGoodsModel();
            $res = $topic_goods_model->getTopicGoodsPageList($condition, $page, $page_size);
            return $res;
        } else {
            $this->assign('topic_id',$topic_id);
            return $this->fetch("topic/goodslist");
        }
	}
	
	/**
	 * 删除专题活动商品
	 */
	public function deleteTopicGoods()
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
        if(request()->isAjax()){

            $topic_id = input("topic_id", 0);
            $goods_id = input("goods_id", 0);
            $topic_goods_model = new TopicGoodsModel();

            $condition = [
                ['ptg.topic_id','=',$topic_id],
                ['ptg.goods_id','=',$goods_id],
                ['g.goods_state','=',1],
                ['g.is_delete', '=', 0]
            ];
            $list = $topic_goods_model->getTopicGoodsSkuList($condition);
            return $list;
        }
    }
}