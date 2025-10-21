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

namespace addon\servicer\servicer\controller;

use app\model\shop\Shop;
use app\model\system\User as UserModel;
use app\model\web\Config as ConfigModel;
use app\model\web\WebSite;
use addon\servicer\model\Servicer as ServicerModel;

/**
 * 客服
 */
class BaseServicer extends Base
{
    /**
     * 面包屑
     * @var array
     */
    protected $crumbs = [];

    /**
     * 当前登录的用户ID
     * @var mixed
     */
    protected $uid;

    /**
     * 当前登录的用户信息
     * @var mixed
     */
    protected $user_info;

    /**
     * 站点ID
     * @var mixed
     */
    protected $site_id;

    /**
     * 商家信息
     * @var
     */
    protected $shop_info;

    protected $servicer_info = [];


    public function __construct()
    {
        parent::__construct();

        $user_model = new UserModel();
        $this->uid  = $user_model->uid($this->app_module);

        // 检测登录
        if (empty($this->uid)) {
            $this->redirect(addon_url("servicer://servicer/login/login"));
            exit();
        }

        $this->user_info = $user_model->userInfo($this->app_module);
        $this->site_id   = $this->user_info['site_id'];

        $this->assign('user_info', $this->user_info);
        if (!request()->isAjax()) {
            $this->initBaseInfo();
        }

        // 默认图配置
        $config_model = new ConfigModel();
        $default_img  = $config_model->getDefaultImg()['data']['value'];
        $this->assign("default_img", $default_img);

        // 客服信息
        $servicer_model = new ServicerModel();
        $this->servicer_info = $servicer_model->getInfo([['user_id', '=', $this->uid]])['data'];
        $this->assign("servicer_info", $this->servicer_info);
    }

    protected function result($data, $code = 0, $msg = '', $type = '', array $header = [])
    {
        return ['code' => $code, 'data' => $data, 'msg' => $msg];
    }

    /**
     * 加载基础信息
     */
    private function initBaseInfo()
    {

        $shop_model      = new Shop();
        $shop_info       = $shop_model->getShopInfo(
            [['site_id', '=', $this->site_id]],
            'site_name,logo,is_own,level_id,category_id,group_id,seo_keywords,seo_description,expire_time'
        );
        $this->shop_info = $shop_info['data'];
        $this->assign("shop_info", $this->shop_info);
        $this->assign("url", $this->url);
        $this->assign("crumbs", $this->crumbs);

        //加载网站基础信息
        $website      = new WebSite();
        $website_info = $website->getWebSite(
            [['site_id', '=', 0]],
            'title,logo,desc,keywords,web_status,close_reason,web_qrcode,web_phone'
        );
        $this->assign("website_info", $website_info['data']);
        //加载版权信息
        $config_model = new ConfigModel();
        $copyright    = $config_model->getCopyright();
        $this->assign('copyright', $copyright['data']['value']);
    }
}
