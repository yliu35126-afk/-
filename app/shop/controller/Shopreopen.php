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

namespace app\shop\controller;

use app\model\shop\Config as ShopConfigModel;
use app\model\shop\Shop;
use app\model\shop\ShopGroup as ShopGroupModel;
use app\model\shop\ShopReopen as ShopReopenModel;
use app\model\web\WebSite as WebsiteModel;

/**
 * 店铺
 * Class Shop
 * @package app\shop\controller
 */
class Shopreopen extends BaseShop
{

    protected $app_module = "shop";

    public function __construct()
    {
        //执行父类构造函数
        parent::__construct();
    }

    /*
     * 续签首页
     */
    public function index()
    {
        $site_id = $this->site_id;//店铺ID

        //获取店铺信息
        $condition[] = [ 'site_id', '=', $site_id ];
        $apply_model = new Shop();
        $apply_info = $apply_model->getShopInfo($condition, '*');
        $apply_data = $apply_info[ 'data' ];

        //店铺的到期时间（0为永久授权）
        if ($apply_data != null) {

            if ($apply_data[ 'expire_time' ] == 0) {
                $apply_data[ 'is_reopen' ] = 1;//永久有效
            } elseif ($apply_data[ 'expire_time' ] > time()) {
                $cha = $apply_data[ 'expire_time' ] - time();
                $date = ceil(( $cha / 86400 ));
                if ($date < 30) {
                    $apply_data[ 'is_reopen' ] = 2;//离到期一月内才可以申请续签
                }

            } else {
                $apply_data[ 'is_reopen' ] = 3;
            }

            $apply_data[ 'expire_time' ] = $apply_data[ 'expire_time' ] == 0 ? '永久有效' : date("Y-m-d H:i:s", $apply_data[ 'expire_time' ]);
        }
        $this->assign('apply_data', $apply_data);

        $shop_reopen = new ShopReopenModel();
        //获取续签信息
        if (request()->isAjax()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $list = $shop_reopen->getReopenPageList([ [ 'site_id', '=', $site_id ] ], $page, $page_size);
            return $list;
        } else {
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $shop_reopen = $shop_reopen->getReopenPageList([ [ 'site_id', '=', $site_id ] ], 1, $page_size);
            $this->assign('shop_reopen', $shop_reopen);
        }
        //店铺等级
        $shop_group_model = new ShopGroupModel();
        $shop_group_list = $shop_group_model->getGroupList([ [ 'is_own', '=', 0 ] ], 'group_id,is_own,group_name,fee,remark', 'is_own asc,fee asc');
        $this->assign('shop_group_list', $shop_group_list[ 'data' ]);

        //平台配置信息
        $website_model = new WebsiteModel();
        $website_info = $website_model->getWebSite([ [ 'site_id', '=', 0 ] ], 'web_qrcode,web_phone');
        $this->assign('website_info', $website_info[ 'data' ]);

        //收款信息
        $shop_config_model = new ShopConfigModel();
        $receivable_config = $shop_config_model->getSystemBankAccount();
        $this->assign('receivable_config', $receivable_config[ 'data' ]);

        return $this->fetch("shopreopen/index");
    }

    /**
     * 添加续签
     * @return unknown|mixed
     */
    public function addReopen()
    {
        if (request()->isAjax()) {

            $site_id = $this->site_id;
            $reopen_data = [
                'site_id' => $site_id,//店铺ID
                'apply_year' => input('apply_year', ''),//入驻年长
                'shop_group_name' => input('shop_group_name', ''),//开店套餐名称
                'shop_group_id' => input('shop_group_id', ''),//开店套餐id
                'paying_money_certificate' => input('paying_money_certificate', ''),//支付凭证
                'paying_money_certificate_explain' => input('paying_money_certificate_explain', '')//付款凭证说明
            ];

            $shop_model = new Shop();
            $condition[] = [ 'site_id', '=', $reopen_data[ 'site_id' ] ];
            //获取该店分类ID
            $shop_info = $shop_model->getShopInfo($condition, 'category_id');

            $reopen_model = new ShopReopenModel();
            //计算入驻金额
            $apply_money = $reopen_model->getReopenMoney($reopen_data[ 'apply_year' ], $reopen_data[ 'shop_group_id' ]);
            $reopen_data[ 'paying_amount' ] = $apply_money[ 'data' ][ 'money' ];

            $result = $reopen_model->addReopen($reopen_data);

            return $result;
        } else {
            return $this->fetch("shopreopen/add_reopen");
        }
    }

    /**
     * 编辑续签
     * @return unknown|mixed
     */
    public function editReopen()
    {
        $model = new ShopReopenModel();
        if (request()->isPost()) {

            $reopen_data = [
                'id' => input('id', ''),
                'paying_money_certificate' => input('paying_money_certificate', ''),//支付凭证
                'paying_money_certificate_explain' => input('paying_money_certificate_explain', '')//付款凭证说明
            ];

            $model = new ShopReopenModel();
            $result = $model->editReopen($reopen_data);

            return $result;
        } else {
            $id = input('id', '');
            //获取续签信息
            $result = $model->getReopenInfo([ [ 'id', '=', $id ] ], '*');
            return $result;

        }
    }

    /*
     *  删除续签
     */
    public function deleteReopen()
    {
        $id = input('id', '');
        $model = new ShopReopenModel();

        return $model->deleteReopen($id);
    }

    /**
     * 获取续签金额
     */
    public function getReopenMoney()
    {
        $apply_year = input("apply_year", '');//入驻年长
        $group_id = input("group_id", '');//店铺等级ID

        $model = new ShopReopenModel();
        $result = $model->getReopenMoney($apply_year, $group_id);
        return $result;
    }

}