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

namespace addon\supply\supply\controller;

use addon\supply\model\Purchase as PurchaseModel;

/**
 * 求购
 */
class Purchase extends BaseSupply
{

    /**
     * 求购列表
     */
    public function lists()
    {
        if(request()->isAjax()){
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);

            $condition = [];

            //关键词
            $search_name = input('search_name','');
            if($search_name){
                $condition[] = [ 'title', 'like', '%' . $search_name . '%' ];
            }

            $start_time = input('start_time','');
            $end_time = input('end_time','');
            if(!empty($start_time) && !empty($end_time)){
                $condition[] = ['end_time','between',[date_to_time($start_time), date_to_time($end_time)]];
            }elseif(!empty($start_time) && empty($end_time)){
                $condition[] = ['end_time','>=',date_to_time($start_time)];
            }elseif(empty($start_time) && !empty($end_time)){
                $condition[] = ['end_time','<=',date_to_time($end_time)];
            }

            $purchase_model = new PurchaseModel();
            $list = $purchase_model->getPurchasePageList($condition, $page, $page_size,'create_time desc');
            return $list;
        }else{

            return $this->fetch('purchase/lists');
        }
    }


    /**
     * 求购详情
     */
    public function detail()
    {
        $purchase_id = input('purchase_id', 0);
        $purchase_model = new PurchaseModel();
        $info_result = $purchase_model->getPurchaseInfo([['purchase_id','=',$purchase_id]]);
        $info = $info_result['data'] ?? [];
        if(!empty($info)){
            $end_time = $info['end_time'];
            $nowtime = time();
            $day = 0;
            if($nowtime <= $end_time){
                $time = $end_time - $nowtime;
                $day = ceil($time/86400);
            }
            $info['day'] = $day;
        }
        $this->assign("info", $info);
        return $this->fetch('purchase/detail');
    }

}