<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\api\controller;

use app\model\goods\Cart as CartModel;
use app\model\shop\Shop as ShopModel;
use app\model\system\Config as ConfigSystemModel;
use app\model\system\Servicer;
use app\model\web\Config as ConfigModel;
use app\model\web\DiyView as DiyViewModel;

class Config extends BaseApi
{

    /**
     * 详情信息
     */
    public function defaultimg()
    {
        $upload_config_model = new ConfigModel();
        $res = $upload_config_model->getDefaultImg();
        if (!empty($res[ 'data' ][ 'value' ])) {
            return $this->response($this->success($res[ 'data' ][ 'value' ]));
        } else {
            return $this->response($this->error());
        }
    }

    /**
     * 版权信息
     */
    public function copyright()
    {
        $config_model = new ConfigModel();
        $res = $config_model->getCopyright();
        return $this->response($this->success($res[ 'data' ][ 'value' ]));
    }

    /**
     * 商城设置
     * @return false|string
     */
    public function basic()
    {
        $config_model = new ConfigModel();
        $res = $config_model->getBasicConfig();
        return $this->response($this->success($res[ 'data' ][ 'value' ] ?? []));
    }

    /**
     * 客服配置
     */
    public function servicer()
    {
        $servicer_model = new Servicer();
        $result = $servicer_model->getServicerConfig()[ 'data' ] ?? [];
        return $this->response($this->success($result[ 'value' ] ?? []));
    }

    /**
     * 获取当前时间戳
     * @return false|string
     */
    public function time()
    {
        $time = time();
        return $this->response($this->success($time));
    }

    /**
     * 获取验证码配置
     */
    public function getCaptchaConfig()
    {
        $config_model = new ConfigModel();
        $info = $config_model->getCaptchaConfig();
        return $this->response($this->success($info[ 'data' ] ?? []));
    }



    /**
     * 初始化加载数据
     * @return false|string
     */
    public function init()
    {
        $cart_count = 0;
        $wholesale_cart_count = 0;
        $token = $this->checkToken();
        if ($token[ 'code' ] >= 0) {
            $cart = new CartModel();
            $condition = [
                [ 'gc.member_id', '=', $token[ 'data' ][ 'member_id' ] ],
                [ 'gs.goods_state', '=', 1 ],
                [ 'gs.is_delete', '=', 0 ]
            ];
            $list = $cart->getCartList($condition, 'gc.num');
            $list = $list[ 'data' ];
            foreach ($list as $k => $v) {
                $cart_count += $v[ 'num' ];
            }

            if (addon_is_exit('wholesale') == 1) {
                // 进货单数量
                $wholesale_cart_model = new \addon\wholesale\model\Cart();
                $wholesale_cart_res = $wholesale_cart_model->getCartCount($token[ 'data' ][ 'member_id' ]);
                $wholesale_cart_count = $wholesale_cart_res[ 'data' ];
            }
        }
        // 商城风格
        $config_model = new ConfigSystemModel();
        $res = $config_model->getConfig([ [ 'site_id', '=', 0 ], [ 'app_module', '=', 'admin' ], [ 'config_key', '=', 'SHOP_STYLE_CONFIG' ] ]);
        $diy_style = empty($res[ 'data' ][ 'value' ]) ? [ 'style_theme' => 'default' ] : $res[ 'data' ][ 'value' ];
        $diy_view = new DiyViewModel();
        $diy_bottom_nav = $diy_view->getBottomNavConfig(0);
        $diy_bottom_nav = $diy_bottom_nav[ 'data' ];
        $addon_api = new Addon();
        $addon_is_exit = json_decode($addon_api->addonisexit(), true);
        $upload_config_model = new ConfigModel();
        $default_img = $upload_config_model->getDefaultImg();
        $default_img = $default_img[ 'data' ][ 'value' ];
        $shop = new ShopModel();
        $development = $shop->getDevelopment();// 0 隐藏，1 显示

        $res = [
            'cart_count' => $cart_count,
            'style_theme' => $diy_style['style_theme'],
            'diy_bottom_nav' => $diy_bottom_nav[ 'value' ] ? json_decode($diy_bottom_nav[ 'value' ], true) : [],
            'addon_is_exit' => $addon_is_exit[ 'data' ],
            'wholesale_cart_count' => $wholesale_cart_count,
            'development' => $development,
            'default_img' => $default_img
        ];

        return $this->response($this->success($res));
    }
}