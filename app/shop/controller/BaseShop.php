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

use app\Controller;
use app\model\shop\Shop;
use app\model\shop\ShopGroup;
use app\model\system\Addon;
use app\model\system\Group as GroupModel;
use app\model\system\Menu;
use app\model\system\Promotion as PromotionModel;
use app\model\system\User as UserModel;
use app\model\web\Config as ConfigModel;
use app\model\web\WebSite;

class BaseShop extends Controller
{
    protected $init_menu = [];
    protected $crumbs = [];
    protected $crumbs_array = [];

    protected $uid;
    protected $user_info;
    protected $url;
    protected $group_info;
    protected $menus;
    protected $site_id;
    protected $website_id;
    protected $shop_info;
    protected $app_module = "shop";
    protected $addon = '';

    public function __construct()
    {
        //执行父类构造函数
        parent::__construct();
        //检测基础登录
        $user_model = new UserModel();
        $this->uid = $user_model->uid($this->app_module);
        /** @var \app\Request $req */
        $req = request();
        $this->url = $req->parseUrl();
        $this->addon = $req->addon() ? $req->addon() : '';
        $this->user_info = $user_model->userInfo($this->app_module);
        $this->assign("user_info", $this->user_info);
        $this->site_id = $this->user_info[ "site_id" ] ?? 0;
        if (empty($this->uid)) {
            /** @var \app\Request $req */
            $req = request();
            $currentModule = strtolower($req->module() ?? '');
            $currentController = strtolower($req->controller() ?? '');
            // 避免在登录页自身发生重定向循环
            if (!($currentModule === 'shop' && $currentController === 'login')) {
                // 记录一次简要调试日志，便于定位为何发生跳转
                try {
                    $user_model->addUserLog(0, 'guest', 0, 'Shop未登录跳转', [
                        'url' => method_exists($req, 'url') ? $req->url(true) : '',
                        'host' => method_exists($req, 'host') ? $req->host() : '',
                        'domain' => method_exists($req, 'domain') ? $req->domain() : '',
                        'root_url' => defined('ROOT_URL') ? ROOT_URL : '',
                        'sid' => session_id(),
                    ]);
                } catch (\Throwable $e) {
                    // 忽略日志异常
                }
                $this->redirect(url("shop/login/login"));
                exit();
            }
        }
        if ($this->site_id == 0) {
            $this->redirect(url("shop/apply/index"));
            exit();
        }
        $this->checkLogin();
        //检测用户组
        $this->getGroupInfo();
        if (!$this->checkAuth()) {
            if (!request()->isAjax()) {
                $menu_info = $user_model->getRedirectUrl($this->url, $this->app_module, $this->group_info, $this->addon);
                if (empty($menu_info)) {
                    $this->error('权限不足');
                } else {
                    $this->redirect(addon_url($menu_info[ 'url' ]));
                }
            } else {
                echo json_encode(error(-1, '权限不足'));
                exit;
            }

        }

        //获取店铺信息
        $shop = new Shop();
        $shop_info = $shop->getShopInfo([ [ 'site_id', '=', $this->site_id ] ], 'website_id, site_name,logo,is_own,level_id,category_id,group_id,seo_keywords,seo_description,expire_time,shop_status');
        $this->website_id = $shop_info[ 'data' ][ 'website_id' ];
        $this->shop_info = $shop_info[ 'data' ];
        $this->assign('site_id', $this->site_id);
        $this->assign("shop_info", $shop_info[ 'data' ]);
        if (!request()->isAjax()) {

            //获取菜单
            $this->menus = $this->getMenuList();
            $this->initBaseInfo();
            //检测开店套餐权限

            $this->checkShopGroup();
        }

        //搜索图片
        $upload_config_model = new ConfigModel();
        $upload_config_result = $upload_config_model->getDefaultImg();
        $this->assign("default_img", $upload_config_result[ 'data' ][ 'value' ]);

    }

    /**
     * 加载基础信息
     */
    private function initBaseInfo()
    {
        //获取一级权限菜单
        $this->getTopMenu();
        $menu_model = new Menu();
        $info_result = $menu_model->getMenuInfoByUrl($this->url, $this->app_module, $this->addon);
        $info = [];
        if (!empty($info_result[ "data" ])) {
            $info = $info_result[ "data" ];
            $this->getParentMenuList($info[ 'name' ]);
        }
        $this->assign("menu_info", $info);
        //加载菜单树
        $init_menu = $this->initMenu($this->menus, '');

        // 应用下的菜单特殊处理
        if (!empty($this->crumbs) && $this->crumbs[ 0 ][ 'name' ] == 'PROMOTION_ROOT') {

            //如果当前选择了【应用管理】，则只保留【应用管理】菜单
            if ($this->crumbs[ 1 ][ 'name' ] == 'PROMOTION_TOOL') {
                foreach ($init_menu as $k => $v) {
                    if ($v[ 'selected' ]) {
                        $init_menu[ $k ][ 'child_list' ] = [ $v[ 'child_list' ][ 'PROMOTION_CENTER' ], $v[ 'child_list' ][ 'PROMOTION_MEMBER' ], $v[ 'child_list' ][ 'PROMOTION_TOOL' ] ];
                        break;
                    }
                }
            } else {
                //选择了应用下的某个插件，则移除【应用管理】菜单，显示该插件下的菜单，并且标题名称改为插件名称
                $addon_model = new Addon();
                $addon_info = $addon_model->getAddonInfo([ [ 'name', '=', $this->addon ] ], 'name,title');
                $addon_info = $addon_info[ 'data' ] ?? [ 'name' => '', 'title' => '' ];
                $promotion_menu_arr = [ 'PROMOTION_CENTER', 'PROMOTION_MEMBER', 'PROMOTION_TOOL' ];
                foreach ($init_menu as $k => $v) {
                    if ($v[ 'selected' ]) {
                        $this->crumbs[ 0 ][ 'title' ] = $addon_info[ 'title' ];
//                        unset($init_menu[ $k ][ 'child_list' ][ 'PROMOTION_TOOL' ]);
                        foreach ($init_menu[ $k ][ 'child_list' ] as $ck => $cv) {
                            if ($cv[ 'addon' ] != $addon_info[ 'name' ]) {
                                if (isset($this->crumbs[ 2 ]) && ( $this->crumbs[ 2 ][ 'parent' ] == 'PROMOTION_CENTER' || $this->crumbs[ 2 ][ 'parent' ] == 'PROMOTION_MEMBER' || $this->crumbs[ 2 ][ 'parent' ] == 'PROMOTION_TOOL' )) {
                                    if (!in_array($ck, $promotion_menu_arr)) {
                                        unset($init_menu[ $k ][ 'child_list' ][ $ck ]);
                                    }
                                } else {
                                    unset($init_menu[ $k ][ 'child_list' ][ $ck ]);
                                }
                            }
                        }
                        break;
                    }
                }
            }
        }

        $this->assign("url", $this->url);
        $this->assign("menu", $init_menu);
        $this->assign("crumbs", $this->crumbs);

        //加载网站基础信息
        $website = new WebSite();
        $website_info = $website->getWebSite([ [ 'site_id', '=', 0 ] ], 'title,logo,desc,keywords,web_status,close_reason,web_qrcode,web_phone');
        $this->assign("website_info", $website_info[ 'data' ]);
        //加载版权信息
        $config_model = new ConfigModel();
        $copyright = $config_model->getCopyright();
        $this->assign('copyright', $copyright[ 'data' ][ 'value' ]);
    }

    /**
     * layui化处理菜单数据
     */
    public function initMenu($menus_list, $parent = "")
    {
        $temp_list = [];
        if (!empty($menus_list)) {
            foreach ($menus_list as $menu_k => $menu_v) {

                if (in_array($menu_v[ 'name' ], $this->crumbs_array)) {
                    $selected = true;
                } else {
                    $selected = false;
                }

                if ($menu_v[ "parent" ] == $parent && $menu_v[ "is_show" ] == 1) {
                    $temp_item = array (
                        'addon' => $menu_v[ 'addon' ],
                        'selected' => $selected,
                        'url' => addon_url($menu_v[ 'url' ]),
                        'title' => $menu_v[ 'title' ],
                        'icon' => $menu_v[ 'picture' ],
                        'icon_selected' => $menu_v[ 'picture_select' ],
                        'target' => ''
                    );

                    $child = $this->initMenu($menus_list, $menu_v[ "name" ]);//获取下级的菜单
                    $temp_item[ "child_list" ] = $child;
                    $temp_list[ $menu_v[ "name" ] ] = $temp_item;
                }
            }
        }
        return $temp_list;
    }

    /**
     * 获取上级菜单列表
     * @param number $menu_id
     */
    private function getParentMenuList($name = '')
    {
        if (!empty($name)) {
            $menu_model = new Menu();
            $menu_info_result = $menu_model->getMenuInfo([ [ 'name', "=", $name ], [ 'app_module', '=', $this->app_module ] ]);
            $menu_info = $menu_info_result[ "data" ];
            if (!empty($menu_info)) {
                $this->getParentMenuList($menu_info[ 'parent' ]);
                $menu_info[ "url" ] = addon_url($menu_info[ "url" ]);
                $this->crumbs[] = $menu_info;
                $this->crumbs_array[] = $menu_info[ 'name' ];
            }
        }

    }

    /**
     * 获取当前用户的用户组
     */
    private function getGroupInfo()
    {
        $group_model = new GroupModel();

        $group_info_result = $group_model->getGroupInfo([ [ "group_id", "=", $this->user_info[ "group_id" ] ], [ "site_id", "=", $this->site_id ], [ "app_module", "=", $this->app_module ] ]);

        $this->group_info = $group_info_result[ "data" ];

    }

    /**
     * 验证登录
     */
    private function checkLogin()
    {
        //验证基础登录
        if (!$this->uid) {
            // 如果门店端已登录，自动复用门店会话到店铺端，避免误跳登录
            try {
                $user_model = new UserModel();
                $store_uid = $user_model->uid('store');
                if (!empty($store_uid)) {
                    $store_info = $user_model->userInfo('store');
                    \think\facade\Session::set('shop.uid', $store_uid);
                    \think\facade\Session::set('shop.user_info', $store_info);
                    $this->uid = $store_uid;
                    $this->user_info = $store_info;
                    $this->site_id = $this->user_info["site_id"] ?? 0;
                    // 记录一次自动复用日志，便于后续排查
                    try {
                        $user_model->addUserLog($store_info['uid'] ?? 0, $store_info['username'] ?? 'store_user', $store_info['site_id'] ?? 0, 'store->shop会话复用', [
                            'from' => 'store',
                            'to' => 'shop',
                            'sid' => session_id(),
                            'url' => request()->url(true)
                        ]);
                    } catch (\Throwable $e) {}
                    return; // 复用成功后不再跳登录
                }
            } catch (\Throwable $e) {
                // 忽略复用异常，走默认登录流程
            }
            // 使用框架 URL 生成，避免根目录/子目录切换导致路径偏差
            $this->redirect(url('shop/login/login'));
        }
    }

    /**
     * 检测权限
     */
    private function checkAuth()
    {
        $user_model = new UserModel();
        $res = $user_model->checkAuth($this->url, $this->app_module, $this->group_info);
        return $res;
    }

    /**
     * 获取菜单
     */
    private function getMenuList()
    {
        $menu_model = new Menu();
        if ($this->group_info[ 'is_system' ] == 1) {
            $shop_group_model = new ShopGroup();
            $shop_group_info_result = $shop_group_model->getGroupInfo([ [ 'group_id', '=', $this->user_info[ 'app_group' ] ] ], 'addon_array');
            $shop_group_info = $shop_group_info_result[ 'data' ];
            if (empty($shop_group_info)) {
                $this->error('用户组缺失');
            }
            //获取除营销插件以外的shop菜单
            $promotion_model = new PromotionModel();
            $promotions = $promotion_model->getPromotions();
            $all_promotion_name = array_column($promotions[ 'shop' ], 'name');
            $menus = $menu_model->getMenuList([ [ 'app_module', '=', $this->app_module ], [ 'addon', 'NOT IN', $all_promotion_name ], [ 'is_show', '=', 1 ] ], '*', 'sort ASC');

            $addon_menus = $menu_model->getMenuList([ [ 'addon', 'in', $shop_group_info[ 'addon_array' ] ], [ 'app_module', '=', $this->app_module ], [ 'is_show', '=', 1 ] ], '*', 'sort ASC');
            if (!empty($addon_menus)) {
                $menus[ 'data' ] = array_merge($menus[ 'data' ], $addon_menus[ 'data' ]);
            }
        } else {
            $addon_array = $this->group_info[ 'menu_array' ];
            $addon_menus = $menu_model->getMenuList([ [ 'addon', 'in', $addon_array ], [ 'app_module', '=', $this->app_module ], [ 'is_show', '=', 1 ] ], '*', 'sort asc');
            $addon_menus = $addon_menus[ 'data' ];
            $menus = $menu_model->getMenuList([ [ 'name', 'in', $this->group_info[ 'menu_array' ] ], [ 'is_show', '=', 1 ], [ 'app_module', '=', $this->app_module ] ], '*', 'sort ASC');
            if (!empty($addon_menus)) {
                $menus[ 'data' ] = array_merge($menus[ 'data' ], $addon_menus);
            }
        }
        return $menus[ 'data' ];
    }

    /**
     * 获取顶级菜单
     */
    protected function getTopMenu()
    {
        $list = array_filter($this->menus, function($v) {
            return $v[ 'parent' ] == '0';
        });
        return $list;

    }

    protected function forthMenu($params = [])
    {
        $url = strtolower($this->url);
        $menu_model = new Menu();
        $menu_info = $menu_model->getMenuInfo([ [ 'url', "=", $url ], [ 'level', '=', 4 ] ], 'parent');
        if (!empty($menu_info[ 'data' ])) {
            $menus = $menu_model->getMenuList([ [ 'app_module', "=", $this->app_module ], [ 'is_show', "=", 1 ], [ 'parent', '=', $menu_info[ 'data' ][ 'parent' ] ] ], '*', 'sort asc');
            foreach ($menus[ 'data' ] as $k => $v) {
                $menus[ 'data' ][ $k ][ 'parse_url' ] = addon_url($menus[ 'data' ][ $k ][ 'url' ], $params);
                if ($menus[ 'data' ][ $k ][ 'url' ] == $url) {
                    $menus[ 'data' ][ $k ][ 'selected' ] = 1;
                } else {
                    $menus[ 'data' ][ $k ][ 'selected' ] = 0;
                }
            }
            $this->assign('forth_menu', $menus[ 'data' ]);
        }
    }

    /**
     * 添加日志
     * @param unknown $action_name
     * @param unknown $data
     */
    protected function addLog($action_name, $data = [])
    {
        $user = new UserModel();
        $user->addUserLog($this->uid, $this->user_info[ 'username' ], $this->site_id, $action_name, $data);
    }

    /**
     * 验证套餐权限
     */
    private function checkShopGroup()
    {
        $url = request()->url();
        $count = substr_count($url, '/');
        // 是否插件请求
        $addon_name = substr($url, 1, strpos(substr($url, 1), '/'));
        //排除不需要控制权限的插件
        $except = [ 'store', 'supply', 'memberprice', 'goodsgrab' ];
        if (( $count > 3 && !in_array($addon_name, $except) ) && addon_is_exit($addon_name)) {
            $shop_group_model = new ShopGroup();
            $addon_array = $shop_group_model->getGroupInfo([ 'group_id' => $this->shop_info[ 'group_id' ] ], 'addon_array');
            if (strpos($addon_array[ 'data' ][ 'addon_array' ], $addon_name) !== false) {
                // 存在插件
            } else {
                // 不存在插件
                if (!request()->isAjax()) {
                    $this->error('权限不足');
                } else {
                    echo json_encode(error('', '权限不足'));
                    exit;
                }
            }
        }
    }
}
