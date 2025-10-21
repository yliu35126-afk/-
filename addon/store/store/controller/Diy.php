<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\store\store\controller;

use app\model\web\DiyView as DiyViewModel;

/**
 * 网站装修控制器
 */
class Diy extends BaseStore
{
    /**
     * 网站主页
     */
    public function index()
    {
        if (request()->isAjax()) {
            $res = 0;
            $data = array ();
            $id = input("id", 0);
            $name = input("name", "");
            $title = input("title", "");
            $value = input("value", "");
            if (!empty($name) && !empty($title) && !empty($value)) {
                $diy_view = new DiyViewModel();
                $data[ 'site_id' ] = $this->site_id;
                $data[ 'name' ] = $name;
                $data[ 'title' ] = $title;
                $data[ 'type' ] = 'store';
                $data[ 'value' ] = $value;
                if ($id == 0) {
                    $data[ 'create_time' ] = time();
                    $res = $diy_view->addSiteDiyView($data);
                } else {
                    $data[ 'update_time' ] = time();
                    $res = $diy_view->editSiteDiyView($data, [
                        [ 'id', '=', $id ]
                    ]);
                }
            }

            return $res;
        } else {
            // 查询公共组件和支持的页面
            $condition = [
                [ 'support_diy_view', 'like', [ 'DIY_STORE', '%' . 'DIY_STORE' . ',%', '%' . 'DIY_STORE', '%,' . 'DIY_STORE' . ',%', '' ], 'or' ]
            ];
            $data = [
                'app_module' => 'store',
                'site_id' => $this->site_id,
                'name' => 'DIY_STORE_' . $this->store_id,
                'condition' => $condition,
                'store_id' => $this->store_id
            ];
            $edit_view = event('DiyViewEdit', $data, true);

            return $edit_view;
        }
    }

    /**
     * 链接选择
     */
    public function link()
    {
        $data = [
            'app_module' => $this->app_module,
            'site_id' => $this->site_id,
        ];
        $diy_link = event('DiyLink', $data, true);
        return $diy_link;
    }

    public function childLink()
    {
        $diy_child_link = event('DiyChildLink', [ 'site_id' => $this->site_id, 'app_module' => $this->app_module ], true);
        return $diy_child_link;
    }

}