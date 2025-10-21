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

use app\model\system\Config as ConfigModel;
use app\model\web\DiyView as DiyViewModel;
use app\model\web\WebSite;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 自定义模板
 * @package app\api\controller
 */
class Diyview extends BaseApi
{

    /**
     * 基础信息
     * @return false|string
     */
    public function info()
    {

        $id = isset($this->params[ 'id' ]) ? $this->params[ 'id' ] : 0;
        $name = isset($this->params[ 'name' ]) ? $this->params[ 'name' ] : '';
        $site_id = isset($this->params[ 'site_id' ]) ? $this->params[ 'site_id' ] : 0;
        $website_id= isset($this->params[ 'website_id' ]) ? $this->params[ 'website_id' ] : "0";

        if (empty($id) && empty($name)) {
            return $this->response($this->error('', 'REQUEST_DIY_ID_NAME'));
        }
        $diy_view = new DiyViewModel();

        $condition = [];
        if (!empty($id)) {
            $condition[] = [ 'sdv.id', '=', $id ];
            $diy_view->modifyClick([ [ 'id', '=', $id ] ], $site_id);
        }
        if (!empty($name)) {
            $condition[] = [ 'sdv.name', '=', $name ];
            $diy_view->modifyClick([ [ 'name', '=', $name ] ], $site_id);
        }
        if (!empty($site_id)) {
            $condition[] = [ 'sdv.site_id', '=', $site_id ];
        }

        // 查询是否存在城市分站
        $page = $diy_view->getPage();
        if ($name == $page[ 'city' ][ 'index' ][ 'name' ] && addon_is_exit('city') && ( !empty($website_id))) {
            $website_model = new WebSite();
            $website_condition = [
                [ 'status', '=', 1 ],
//                ['site_id', '=', $website_id]
            ];
            if (!empty($site_id)) {
                $website_condition[] = [ 'site_id', '=', $site_id ];
            } elseif (!empty($web_city)) {
                $website_condition[] = [ 'site_id', '=', $website_id ];
            }
//
            $is_empty = true;
            $website_info = $website_model->getWebSite($website_condition, 'site_id,site_area_id,site_area_name');
            $website_info = $website_info[ 'data' ];
            if (!empty($website_info)) {
                $condition[] = [ 'sdv.site_id', '=', $website_id ];
                $info = $diy_view->getSiteDiyViewDetail($condition);
                if (!empty($info[ 'data' ])) {
                    $is_empty = false;
                    $website_data = [
                        'site_area_id' => $website_info[ 'site_area_id' ],
                        'site_area_name' => $website_info[ 'site_area_name' ]
                    ];
                    $info[ 'data' ] = array_merge($info[ 'data' ], $website_data);
                }
            }
            if ($is_empty) {
                // 如果分站没有设置自定义首页数据，则显示全国的
                foreach ($condition as $k => $v) {
                    if ($v[ 0 ] == 'sdv.site_id') {
                        unset($condition[ $k ]);
                        break;
                    }
                }
                $condition = array_values($condition);
                $info = $diy_view->getSiteDiyViewDetail($condition);

            }

        } else {
            $info = $diy_view->getSiteDiyViewDetail($condition);
        }

        if (!empty($info[ 'data' ][ 'value' ])) {
            $json_data = json_decode($info[ 'data' ][ 'value' ], true);
            foreach ($json_data[ 'value' ] as $k => $v) {
                if (!empty($v[ 'addon_name' ])) {
                    $is_exit = addon_is_exit($v[ 'addon_name' ], $site_id);
                    // 检查插件是否存在
                    if ($is_exit == 0) {
                        unset($json_data[ 'value' ][ $k ]);
                    }
                }
            }
            $json_data[ 'value' ] = array_values($json_data[ 'value' ]);
            $info[ 'data' ][ 'value' ] = json_encode($json_data);
        }
        return $this->response($info);
    }

    /**
     * 平台端底部导航
     * @return string
     */
    public function bottomNav()
    {
        $diy_view = new DiyViewModel();
        $info = $diy_view->getBottomNavConfig(0);
        return $this->response($info);
    }

    /**
     * 店铺端底部导航
     * @return string
     */
    public function shopBottomNav()
    {
        $site_id = isset($this->params[ 'site_id' ]) ? $this->params[ 'site_id' ] : 0;
        if (empty($site_id)) {
            return $this->response($this->error('', 'REQUEST_SITE_ID'));
        }
        $diy_view = new DiyViewModel();
        $info = $diy_view->getShopBottomNavConfig($site_id);
        return $this->response($info);
    }

    /**
     * 风格
     */
    public function style()
    {
        $config_model = new ConfigModel();
        $res = $config_model->getConfig([ [ 'site_id', '=', 0 ], [ 'app_module', '=', 'admin' ], [ 'config_key', '=', 'SHOP_STYLE_CONFIG' ] ]);
        $style_theme = empty($res[ 'data' ][ 'value' ]) ? [ 'style_theme' => 'default' ] : $res[ 'data' ][ 'value' ];
        return $this->response($this->success($style_theme));
    }

    /**
     * 自定义会员中心配置
     * @return string
     */
    public function memberIndex()
    {
        $site_id = 0;
        $diy_view = new DiyViewModel();
        $info = $diy_view->getMemberIndexDiyConfig($site_id);
        $info = $info[ 'data' ][ 'value' ];
        foreach ($info[ 'menuList' ] as $k => $v) {
            if ($v[ 'isShow' ] == 0) {
                unset($info[ 'menuList' ][ $k ]);
                continue;
            }

            if (isset($v[ 'tag' ]) && $v[ 'tag' ] != 'verifier') {

                $is_exit = addon_is_exit($v[ 'tag' ], $site_id);// 检查插件是否安装
                if ($is_exit == 0) {
                    unset($info[ 'menuList' ][ $k ]);
                    continue;
                }
            }

            $info[ 'menuList' ][ $k ][ 'url' ] = isset($info[ 'menuList' ][ $k ][ 'link' ][ 'wap_url' ]) ? $info[ 'menuList' ][ $k ][ 'link' ][ 'wap_url' ] : '';
            unset($info[ 'menuList' ][ $k ][ 'isShow' ]);
            unset($info[ 'menuList' ][ $k ][ 'isSystem' ]);
            unset($info[ 'menuList' ][ $k ][ 'link' ]);
            unset($info[ 'menuList' ][ $k ][ 'remark' ]);
        }
        $info[ 'menuList' ] = array_values($info[ 'menuList' ]);
        return $this->response($this->success($info));
    }
}
