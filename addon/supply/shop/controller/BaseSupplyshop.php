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

namespace addon\supply\shop\controller;

use app\Controller;
use app\model\shop\Shop;
use app\model\system\Menu;
use app\model\system\User as UserModel;
use app\model\web\Config as ConfigModel;
use app\model\web\WebSite;

class BaseSupplyshop extends Controller
{
    protected $init_menu = [];
    protected $crumbs = [];
    protected $crumbs_array = [];

    protected $uid;
    protected $user_info;
    protected $url;
    protected $app_module = "shop";
    protected $addon = '';
    protected $site_id;
    protected $website_id;
    protected $shop_info;
    protected $replace = [];    //视图输出字符串内容替换    相当于配置文件中的'view_replace_str'

    public function __construct()
    {
        //执行父类构造函数
        parent::__construct();
        //检测基础登录
        $user_model = new UserModel();
        $this->uid = $user_model->uid($this->app_module);
        $this->url = request()->parseUrl();
        $this->addon = request()->addon() ? request()->addon() : '';
        $this->user_info = $user_model->userInfo($this->app_module);
        $this->assign("user_info", $this->user_info);
        $this->site_id = $this->user_info["site_id"] ?? 0;
        if (!request()->isAjax()) {
            $this->initBaseInfo();
            $this->replace = [
                'SHOP_SUPPLY_LOCAL_CSS' => __ROOT__ . '/addon/supply/shop/view/public/css',
                'SHOP_SUPPLY_LOCAL_JS'  => __ROOT__ . '/addon/supply/shop/view/public/js',
                'SHOP_SUPPLY_LOCAL_IMG' => __ROOT__ . '/addon/supply/shop/view/public/img',
            ];
        }

        if($this->site_id > 0){
            //获取店铺信息
            $shop = new Shop();
            $shop_info = $shop->getShopInfo([ [ 'site_id', '=', $this->site_id ] ], 'website_id, site_name,logo,is_own,level_id,category_id,group_id,seo_keywords,seo_description,expire_time,shop_status');
            $this->website_id = $shop_info['data']['website_id'];
            $this->shop_info = $shop_info['data'];
            $this->assign("shop_info", $shop_info['data']);
        }
        //搜索默认图片
        $upload_config_model = new ConfigModel();
        $upload_config_result = $upload_config_model->getDefaultImg();
        $this->assign("default_img", $upload_config_result['data']['value']);
    }

    /**
     * 加载基础信息
     */
    private function initBaseInfo()
    {
        //获取一级权限菜单
        $menu_model = new Menu();
        $info_result = $menu_model->getMenuInfoByUrl($this->url, $this->app_module, $this->addon);
        $info = [];
        if (!empty($info_result[ "data" ])) {
            $info = $info_result[ "data" ];
        }

        $this->assign("menu_info", $info);
        //加载网站基础信息
        $website = new WebSite();
        $website_info = $website->getWebSite([['site_id', '=', 0]], 'title,logo,desc,keywords,web_status,close_reason,web_qrcode,web_phone');
        $this->assign("website_info", $website_info[ 'data' ]);
        //加载版权信息
        $config_model = new ConfigModel();
        $copyright = $config_model->getCopyright();
        $this->assign('copyright', $copyright[ 'data' ][ 'value' ]);
    }

    /**
     * 验证登录
     */
    protected function checkLogin()
    {
        $config_model = new ConfigModel();
        if (!$this->uid) {
            if (!request()->isAjax()) {
                //验证基础登录

                    $this->redirect(url('shop/login/login'));

            }else{
                return $config_model->error([], 'NOT_LOGIN');
            }
        }else{
            return $config_model->success([]);
        }
    }

}
