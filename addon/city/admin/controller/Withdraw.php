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

namespace addon\city\admin\controller;

use addon\city\model\CityWithdraw;
use app\admin\controller\BaseAdmin;
use app\model\web\WebSite as WebsiteModel;

/**
 * 跳转页
 */
class Withdraw extends BaseAdmin
{

    /**
     * 转账记录
     */
    public function lists()
    {

        if (request()->isAjax()) {

            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);

            $condition = [];

            $website_name = input('website_name', '');
            if ($website_name) {
                $condition[] = [ 'website_name', 'like', '%' . $website_name . '%' ];
            }

            $start_time = input('start_time', '');
            $end_time = input('end_time', '');
            if (!empty($start_time) && empty($end_time)) {
                $condition[] = [ 'apply_time', '>=', $start_time ];
            } elseif (empty($start_time) && !empty($end_time)) {
                $condition[] = [ 'apply_time', '<=', $end_time ];
            } elseif (!empty($start_time) && !empty($end_time)) {
                $condition[] = [ 'apply_time', 'between', [ $start_time, $end_time ] ];
            }

            $order = "id desc";

            $model = new CityWithdraw();
            $list = $model->getCityWithdrawPageList($condition, $page, $page_size, $order);
            return $list;
        } else {
            return $this->fetch('withdraw/lists');
        }
    }

    /**
     * 转账记录
     */
    public function adminLists()
    {

        if (request()->isAjax()) {

            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);

            $condition = [];

            $website_name = input('website_name', '');
            if ($website_name) {
                $condition[] = [ 'website_name', 'like', '%' . $website_name . '%' ];
            }

            $start_time = input('start_time', '');
            $end_time = input('end_time', '');
            if (!empty($start_time) && empty($end_time)) {
                $condition[] = [ 'apply_time', '>=', $start_time ];
            } elseif (empty($start_time) && !empty($end_time)) {
                $condition[] = [ 'apply_time', '<=', $end_time ];
            } elseif (!empty($start_time) && !empty($end_time)) {
                $condition[] = [ 'apply_time', 'between', [ $start_time, $end_time ] ];
            }

            $order = "id desc";

            $model = new CityWithdraw();
            $list = $model->getCityWithdrawPageList($condition, $page, $page_size, $order);
            return $list;
        } else {

            $website_model = new WebSiteModel();
            $website_info = $website_model->getWebSiteSum([ [ 'site_id', '>', 0 ] ]);
            $this->assign('website_info', $website_info[ 'data' ]);
            return $this->fetch('withdraw/admin_lists');
        }
    }


    /**
     * 转账
     */
    public function withdraw()
    {
        if (request()->isAjax()) {

            $data = [
                'website_id' => input('website_id', ''),
                'money' => input('money', ''),
                'paying_money_certificate' => input('paying_money_certificate', ''),
                'paying_money_certificate_explain' => input('paying_money_certificate_explain', ''),
            ];

            $model = new CityWithdraw();
            $res = $model->withdraw($data);
            return $res;
        }
    }
}