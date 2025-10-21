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

use app\model\web\DiyView as DiyViewModel;

/**
 * 网站装修控制器
 */
class Diy extends BaseShop
{
    /**
     * 网站主页
     */
    public function index()
    {
        $diy_view = new DiyViewModel();
        $page = $diy_view->getPage();
        // 查询公共组件和支持的页面
        $condition = [
            [ 'support_diy_view', 'like', [ $page[ $this->app_module ][ 'index' ][ 'name' ], '%' . $page[ $this->app_module ][ 'index' ][ 'name' ] . ',%', '%' . $page[ $this->app_module ][ 'index' ][ 'name' ], '%,' . $page[ $this->app_module ][ 'index' ][ 'name' ] . ',%', '' ], 'or' ]
        ];

        $data = [
            'app_module' => $this->app_module,
            'site_id' => $this->site_id,
            'name' => $page[ $this->app_module ][ 'index' ][ 'name' ],
            'condition' => $condition
        ];
        $edit_view = event('DiyViewEdit', $data, true);
        return $edit_view;
    }

    /**
     * 商品分类页面
     */
    public function goodsCategory()
    {
        $diy_view = new DiyViewModel();
        $page = $diy_view->getPage();

        // 查询公共组件和支持的页面
        $condition = [
            [ 'name', '=', 'GOODS_CATEGORY' ]
        ];

        $data = [
            'app_module' => $this->app_module,
            'site_id' => $this->site_id,
            'name' => $page[ $this->app_module ][ 'goods_category' ][ 'name' ],
            'condition' => $condition,
            'disabled_page_set' => 1
        ];
        $edit_view = event('DiyViewEdit', $data, true);
        return $edit_view;
    }

    /**
     * 编辑
     */
    public function edit()
    {
        $diy_view = new DiyViewModel();
        $page = $diy_view->getPage();
        if (request()->isAjax()) {
            $res = 0;
            $data = array ();
            $id = input("id", 0);
            $name = input("name", "");
            $title = input("title", "");
            $value = input("value", "");
            if (!empty($name) && !empty($title) && !empty($value)) {
                $data[ 'site_id' ] = $this->site_id;
                $data[ 'name' ] = $name;
                $data[ 'title' ] = $title;
                $data[ 'type' ] = $page[ $this->app_module ][ 'port' ];
                $data[ 'value' ] = $value;
                if ($id == 0 && $name != 'DIY_VIEW_SHOP_INDEX') {
                    $data[ 'create_time' ] = time();
                    $res = $diy_view->addSiteDiyView($data);
                } else {
                    $data[ 'update_time' ] = time();
                    $res = $diy_view->editSiteDiyView($data, [ [ 'id', '=', $id ] ]);
                }
            }

            return $res;
        } else {

            $id = input("id", 0);
            //查询公共系统组件
            $condition = [
                [ 'support_diy_view', 'like', [ $page[ $this->app_module ][ 'index' ][ 'name' ], '%' . $page[ $this->app_module ][ 'index' ][ 'name' ] . ',%', '%' . $page[ $this->app_module ][ 'index' ][ 'name' ], '%,' . $page[ $this->app_module ][ 'index' ][ 'name' ] . ',%', '' ], 'or' ]
            ];
            $data = [
                'app_module' => $this->app_module,
                'site_id' => $this->site_id,
                'id' => $id,
                'condition' => $condition
            ];
            $edit_view = event('DiyViewEdit', $data, true);
            return $edit_view;
        }
    }

    /**
     * 微页面
     */
    public function lists()
    {
        if (request()->isAjax()) {
            $page_index = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $diy_view = new DiyViewModel();
            $page = $diy_view->getPage();
            $condition = [
                [ 'sdv.site_id', '=', $this->site_id ],
                [ 'sdv.type', '=', $page[ $this->app_module ][ 'port' ] ],
                [ 'sdv.name', 'like', '%DIY_VIEW_RANDOM_%' ]
            ];
            $list = $diy_view->getSiteDiyViewPageList($condition, $page_index, $page_size, "INSTR('DIY_VIEW_SHOP_INDEX', sdv.name) desc, sdv.create_time desc");
            return $list;
        } else {
            return $this->fetch('diy/lists');
        }
    }

    /**
     * 删除自定义模板页面
     */
    public function deleteSiteDiyView()
    {
        if (request()->isAjax()) {
            $diy_view = new DiyViewModel();
            $id_array = input("id", 0);
            $condition = [
                [ 'id', 'in', $id_array ]
            ];
            $res = $diy_view->deleteSiteDiyView($condition);
            return $res;
        }
    }

    /**
     * 底部导航
     */
    public function bottomNavDesign()
    {
        $diy_view = new DiyViewModel();
        if (request()->isAjax()) {
            $value = input("value", "");
            $res = $diy_view->setShopBottomNavConfig($value, $this->site_id);
            return $res;
        } else {
            $bottom_nav_info = $diy_view->getShopBottomNavConfig($this->site_id);
            $this->assign("bottom_nav_info", $bottom_nav_info[ 'data' ][ 'value' ]);
            return $this->fetch('diy/bottom_nav_design');
        }
    }

    /**
     * 推广链接
     */
    public function promote()
    {
        if (request()->isAjax()) {
            $id = input("id", 0);
            $diy_view = new DiyViewModel();
            $res = $diy_view->qrcode([
                [ 'site_id', '=', $this->site_id ],
                [ 'id', '=', $id ]
            ]);
            return $res;
        }
    }

    /**
     * 修改排序
     */
    public function modifySort()
    {
        if (request()->isAjax()) {
            $sort = input('sort', 0);
            $id = input('id', 0);
            $diy_view = new DiyViewModel();
            return $diy_view->modifyDiyViewSort($sort, $id);
        }
    }

}