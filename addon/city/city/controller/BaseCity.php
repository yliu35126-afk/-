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

namespace addon\city\city\controller;

use app\Controller;
use app\model\system\Group;
use app\model\system\Menu;
use app\model\system\User;
use app\model\system\User as UserModel;
use app\model\web\Config as ConfigModel;
use app\model\web\WebSite;

class BaseCity extends Controller
{

    protected $init_menu = [];
    protected $crumbs = [];
    protected $crumbs_array = [];

    protected $uid;
    protected $user_info;
    protected $url;
    protected $group_info;
    protected $menus;
    protected $site_id = 0;
    protected $app_module = "city";
    protected $addon = '';
    protected $replace;

    public function __construct()
    {
        $this->replace = [
            'CITY_CSS' => __ROOT__ . '/addon/city/city/view/public/css',
            'CITY_JS' => __ROOT__ . '/addon/city/city/view/public/js',
            'CITY_IMG' => __ROOT__ . '/addon/city/city/view/public/img',
        ];

        //执行父类构造函数
        parent::__construct();

        $user = new UserModel();
        //检测基础登录
        $this->uid = $user->uid($this->app_module);

        /** @var \app\Request $req */
        $req = request();
        $this->url = $req->parseUrl();
        $this->addon = $req->addon() ? $req->addon() : '';
        $this->user_info = $user->userInfo($this->app_module);
        $this->site_id = $this->user_info[ "site_id" ]??'';
        $this->assign("user_info", $this->user_info);
        $this->assign("site_if",  $this->site_id);
        $this->checkLogin();

        $website_model = new WebSite();
        $city_info = $website_model->getWebSite([ [ 'site_id', '=', $this->site_id ] ]);
        $this->assign('city_info', $city_info[ 'data' ]);

        //检测用户组
        $this->getGroupInfo();

        if (!$this->checkAuth()) {
            if (!request()->isAjax()) {
                $this->error('权限不足');
            } else {
                echo json_encode(error('-1', '权限不足！'));
                exit;
            }
        }

        if (!request()->isAjax()) {
            //获取菜单
            $this->menus = $this->getMenuList();
            $this->initBaseInfo();
        }
        //默认图配置
        $config_model = new ConfigModel();
        $default_img_config_result = $config_model->getDefaultImg();
        $this->assign("default_img", $default_img_config_result[ 'data' ][ 'value' ]);
    }

    /**
     * 加载基础信息
     */
    private function initBaseInfo()
    {
        //获取一级权限菜单
        $this->getTopMenu();
        $menu_model = new Menu();
        $info = $menu_model->getMenuInfoByUrl($this->url, $this->app_module, $this->addon);
        if (!empty($info[ 'data' ])) {
            $this->getParentMenuList($info[ 'data' ][ 'name' ]);
            $this->assign("menu_info", $info[ 'data' ]);
        }

        //加载网站基础信息
        $website = new WebSite();
        $website_info = $website->getWebSite([ [ 'site_id', '=', 0 ] ], 'title,logo,desc,keywords,web_status,close_reason');
        $this->assign("website", $website_info[ 'data' ]);
        //加载菜单树
        $init_menu = $this->initMenu($this->menus, '');

        //加载版权信息
        $config_model = new ConfigModel();
        $copyright = $config_model->getCopyright();
        $this->assign('copyright', $copyright[ 'data' ][ 'value' ]);
        $this->assign("url", $this->url);
        $this->assign("menu", $init_menu);
        $this->assign("crumbs", $this->crumbs);
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
     * 获取菜单
     */
    private function getMenuList()
    {
        $menu_model = new Menu();
        if (empty($this->group_info) || !isset($this->group_info['is_system'])) {
            return [];
        }
        if ($this->group_info['is_system'] == 1) {
            $menus = $menu_model->getMenuList([
                [ 'app_module', '=', $this->app_module ],
                [ 'is_show', '=', 1 ]
            ], '*', 'sort asc');
        } else {
            $menu_array = isset($this->group_info['menu_array']) ? $this->group_info['menu_array'] : [];
            if (empty($menu_array)) {
                return [];
            }
            $menus = $menu_model->getMenuList([
                [ 'name', 'in', $menu_array ],
                [ 'is_show', '=', 1 ],
                [ 'app_module', '=', $this->app_module ]
            ], '*', 'sort asc');
        }
        return $menus['data'] ?? [];
    }

    /**
     * 获取顶级菜单
     */
    protected function getTopMenu()
    {
        $list = array_filter($this->menus, function($v) {
            return $v['parent'] == '0';
        });
        return $list;
    }

    /**
     * 获取当前用户的用户组
     */
    private function getGroupInfo()
    {
        $group_model = new Group();
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
            $this->redirect(addon_url('city://city/login/login'));
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
}