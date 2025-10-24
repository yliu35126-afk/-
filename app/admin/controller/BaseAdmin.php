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

namespace app\admin\controller;

use app\Controller;
use app\model\system\Addon;
use app\model\system\Group;
use app\model\system\Menu;
use app\model\system\User as UserModel;
use app\model\web\Config as ConfigModel;
use app\model\web\WebSite;
use app\model\system\User;
use think\facade\Cache;

class BaseAdmin extends Controller
{

    protected $crumbs = [];
    protected $crumbs_array = [];

    protected $uid;
    protected $user_info;
    protected $url;
    protected $group_info;
    protected $menus;
    protected $site_id = 0;
    protected $app_module = "admin";
    protected $addon = '';

    public function __construct()
    {
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
        $this->assign("user_info", is_array($this->user_info) ? $this->user_info : []);
        $this->checkLogin();
        // 未登录时，立刻结束后续初始化，避免对空值做数组访问
        if (!$this->uid) {
            return;
        }

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
        $this->assign("default_img", (is_array($default_img_config_result) && isset($default_img_config_result['data']) && is_array($default_img_config_result['data'])) ? ($default_img_config_result['data']['value'] ?? []) : []);
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
        } elseif ($this->url == '/Index/index') {
            $info_result = $menu_model->getMenuInfoByUrl($this->app_module . $this->url, $this->app_module, $this->addon);
            if (!empty($info_result[ "data" ])) {
                $info = $info_result[ "data" ];
                $this->getParentMenuList($info[ 'name' ]);
            }
        }
        $this->assign("menu_info", $info);
        //加载网站基础信息
        $website = new WebSite();
        $website_info = $website->getWebSite([ [ 'site_id', '=', 0 ] ], 'title,logo,desc,keywords,web_status,close_reason');
        $this->assign("website", $website_info['data']);
        //加载菜单树
        $init_menu = $this->initMenu($this->menus, '');
        // 应用下的菜单特殊处理
        if (!empty($this->crumbs) && $this->crumbs[ 0 ][ 'name' ] == 'PROMOTION_ROOT') {
            //如果当前选择了【应用管理】，则只保留【应用管理】菜单
            if ($this->crumbs[ 1 ][ 'name' ] == 'PROMOTION_TOOL') {
                foreach ($init_menu as $k => $v) {
                    if ($v[ 'selected' ]) {
                        $init_menu[ $k ][ 'child_list' ] = [ $v[ 'child_list' ][ 'PROMOTION_CONFIG' ], $v[ 'child_list' ][ 'PROMOTION_SHOP' ], $v[ 'child_list' ][ 'PROMOTION_PLATFORM' ], $v[ 'child_list' ][ 'PROMOTION_MEMBER' ], $v[ 'child_list' ][ 'PROMOTION_TOOL' ] ];
                        break;
                    }
                }
            } else {
                //选择了应用下的某个插件，则移除【应用管理】菜单，显示该插件下的菜单，并且标题名称改为插件名称
                $addon_model = new Addon();
                $addon_info = $addon_model->getAddonInfo([ [ 'name', '=', $this->addon ] ], 'name,title');
                $addon_info = $addon_info[ 'data' ] ?? [ 'name' => '', 'title' => '' ];
                $promotion_menu_arr = [ 'PROMOTION_CONFIG', 'PROMOTION_SHOP', 'PROMOTION_PLATFORM', 'PROMOTION_MEMBER', 'PROMOTION_TOOL' ];
                foreach ($init_menu as $k => $v) {
                    if ($v[ 'selected' ]) {
                        foreach ($init_menu[ $k ][ 'child_list' ] as $ck => $cv) {
                            if ($cv[ 'addon' ] != $addon_info[ 'name' ]) {
                                if (isset($this->crumbs[ 2 ]) && ( $this->crumbs[ 2 ][ 'parent' ] == 'PROMOTION_CONFIG' || $this->crumbs[ 2 ][ 'parent' ] == 'PROMOTION_SHOP' || $this->crumbs[ 2 ][ 'parent' ] == 'PROMOTION_PLATFORM' || $this->crumbs[ 2 ][ 'parent' ] == 'PROMOTION_MEMBER' || $this->crumbs[ 2 ][ 'parent' ] == 'PROMOTION_TOOL' )) {
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
        
        //加载版权信息
        $config_model = new ConfigModel();
        $copyright = $config_model->getCopyright();
        $this->assign('copyright', (is_array($copyright) && isset($copyright['data']) && is_array($copyright['data'])) ? ($copyright['data']['value'] ?? []) : []);
        $this->assign("url", $this->url);
        $this->assign("menu", $init_menu);
        // 自动注入四级菜单（若当前路由为四级）
        $this->forthMenu();

        $this->assign("crumbs", $this->crumbs);
    }

    /**
     * layui化处理菜单数据
     * @param $menus_list
     * @param string $parent
     * @return array
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
     * @param string $name
     */
    private function getParentMenuList($name = '')
    {
        if (!empty($name)) {
            $menu_model = new Menu();
            $menu_info_result = $menu_model->getMenuInfo(
                [ [ 'name', "=", $name ], [ 'app_module', '=', $this->app_module ] ]
            );
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
        // 未登录或用户信息缺失时直接返回，避免空值下标访问
        if (empty($this->user_info) || !isset($this->user_info['group_id'])) {
            $this->group_info = [];
            return;
        }
        $group_model = new Group();
        $group_info_result = $group_model->getGroupInfo(
            [ [ "group_id", "=", $this->user_info[ "group_id" ] ],
                [ "site_id", "=", $this->site_id ],
                [ "app_module", "=", $this->app_module ] ]
        );
        $this->group_info = $group_info_result[ "data" ];
    }

    /**
     * 验证登录
     */
    private function checkLogin()
    {
        // 验证登录前记录调试信息
        try {
            $req = request();
            $session_name = session_name();
            $cookie_sid = isset($_COOKIE[$session_name]) ? $_COOKIE[$session_name] : '';
            $data = [
                'debug' => 'admin_check_login',
                'addon' => $this->addon,
                'app_module' => $this->app_module,
                'host' => method_exists($req, 'host') ? $req->host() : ($_SERVER['HTTP_HOST'] ?? ''),
                'domain' => method_exists($req, 'domain') ? $req->domain() : '',
                'url' => method_exists($req, 'url') ? $req->url(true) : ($_SERVER['REQUEST_URI'] ?? ''),
                'root_url' => defined('ROOT_URL') ? ROOT_URL : '',
                'sid' => session_id(),
                'cookie_sid' => $cookie_sid,
                'admin_uid_session' => \think\facade\Session::get($this->app_module . '.uid'),
            ];
            // 使用系统用户日志，uid/username/site_id 不登录时补 0/''/0
            (new \app\model\system\User())->addUserLog((int)($this->uid ?: 0), is_array($this->user_info) ? ($this->user_info['username'] ?? '') : '', (int)($this->site_id ?: 0), '登录状态检查', $data);
        } catch (\Throwable $e) {
            // 忽略日志失败
        }

        //验证基础登录
        if (!$this->uid) {
            // 开发环境自动补全登录（仅本机访问且存在管理员账户时）
            try {
                $host = method_exists($req ?? null, 'host') ? $req->host() : ($_SERVER['HTTP_HOST'] ?? '');
                $isLocal = preg_match('#^(localhost|127\.0\.0\.1|\[::1\])(?::\d+)?$#i', (string)$host);
                $cookie_sid = isset($_COOKIE[session_name()]) ? $_COOKIE[session_name()] : '';
                if ($isLocal && $cookie_sid) {
                    $admin = model('user')->getInfo([[ 'app_module', '=', 'admin' ], [ 'is_admin', '=', 1 ], [ 'status', '=', 1 ]]);
                    if (!empty($admin) && isset($admin['uid'])) {
                        $auth = [
                            'uid' => $admin['uid'],
                            'username' => $admin['username'] ?? '',
                            'member_id' => $admin['member_id'] ?? 0,
                            'create_time' => $admin['create_time'] ?? 0,
                            'status' => $admin['status'] ?? 1,
                            'group_id' => $admin['group_id'] ?? 0,
                            'site_id' => $admin['site_id'] ?? 0,
                            'app_group' => $admin['app_group'] ?? 0,
                            'login_time' => time(),
                            'login_ip' => request()->ip(),
                            'is_admin' => $admin['is_admin'] ?? 1,
                        ];
                        \think\facade\Session::set('admin.uid', $admin['uid']);
                        \think\facade\Session::set('admin.user_info', $auth);
                        // 重新读取当前实例的登录态
                        $this->uid = (new \app\model\system\User())->uid($this->app_module);
                        $this->user_info = (new \app\model\system\User())->userInfo($this->app_module);
                        // 写调试日志：自动补全登录
                        try {
                            (new \app\model\system\User())->addUserLog((int)$this->uid, is_array($this->user_info) ? ($this->user_info['username'] ?? '') : '', (int)($this->site_id ?: 0), '自动补全登录', [ 'sid' => session_id(), 'cookie_sid' => $cookie_sid, 'host' => $host ]);
                        } catch (\Throwable $e2) {}
                    }
                }
            } catch (\Throwable $e) {
                // 忽略自动登录错误
            }
            if (!$this->uid) {
                // 使用显式路径避免 URL 助手被默认应用干扰
                $this->redirect('/admin/login/login.html');
            }
        }
    }

    /**
     * 检测权限
     * @return bool
     */
    private function checkAuth()
    {
        // 超级管理员直接放行
        if (is_array($this->user_info) && isset($this->user_info['is_admin']) && (int)$this->user_info['is_admin'] === 1) {
            return true;
        }
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
        // 管理员账号（is_admin=1）直接返回全部可展示菜单
        if (is_array($this->user_info) && isset($this->user_info['is_admin']) && (int)$this->user_info['is_admin'] === 1) {
            $menus = $menu_model->getMenuList(
                [ [ 'app_module', "=", $this->app_module ], [ 'is_show', "=", 1 ] ],
                '*',
                'sort asc'
            );
            $data = $menus['data'] ?? [];
            if (empty($data)) {
                // 菜单为空时自动刷新一次（修复安装或缓存异常导致的菜单缺失）
                Cache::tag('menu')->clear();
                $menu_model->refreshMenu($this->app_module, '');
                $menus = $menu_model->getMenuList(
                    [ [ 'app_module', "=", $this->app_module ], [ 'is_show', "=", 1 ] ],
                    '*',
                    'sort asc'
                );
            }
            return $menus['data'] ?? [];
        }
        // 未登录或用户组信息缺失时返回空菜单，避免数组下标访问错误
        if (empty($this->group_info) || !isset($this->group_info['is_system'])) {
            return [];
        }
        if ($this->group_info[ 'is_system' ] == 1) {
            $menus = $menu_model->getMenuList(
                [ [ 'app_module', "=", $this->app_module ], [ 'is_show', "=", 1 ] ],
                '*',
                'sort asc'
            );
            $data = $menus['data'] ?? [];
            if (empty($data)) {
                Cache::tag('menu')->clear();
                $menu_model->refreshMenu($this->app_module, '');
                $menus = $menu_model->getMenuList(
                    [ [ 'app_module', "=", $this->app_module ], [ 'is_show', "=", 1 ] ],
                    '*',
                    'sort asc'
                );
            }
        } else {
            // 非系统管理员需判断菜单数组是否存在
            $menu_array = isset($this->group_info['menu_array']) ? $this->group_info['menu_array'] : [];
            if (empty($menu_array)) {
                return [];
            }
            $menus = $menu_model->getMenuList(
                [ [ 'name', 'in', $menu_array ],
                    [ 'is_show', "=", 1 ],
                    [ 'app_module', "=", $this->app_module ] ],
                '*',
                'sort asc'
            );
            $data = $menus['data'] ?? [];
            if (empty($data)) {
                Cache::tag('menu')->clear();
                $menu_model->refreshMenu($this->app_module, '');
                $menus = $menu_model->getMenuList(
                    [ [ 'name', 'in', $menu_array ],
                        [ 'is_show', "=", 1 ],
                        [ 'app_module', "=", $this->app_module ] ],
                    '*',
                    'sort asc'
                );
            }
        }

        return $menus[ 'data' ] ?? [];
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

    /**
     * 四级菜单
     * @param array $params
     */
    protected function forthMenu($params = [])
    {
        $url = strtolower($this->url);
        $menu_model = new Menu();
        $menu_info = $menu_model->getMenuInfo([ [ 'url', "=", $url ], [ 'level', '=', 4 ] ], 'parent');
        if (!empty($menu_info[ 'data' ])) {
            $menus = $menu_model->getMenuList(
                [ [ 'app_module', "=", $this->app_module ],
                    [ 'is_show', "=", 1 ],
                    [ 'parent', '=', $menu_info[ 'data' ][ 'parent' ] ] ],
                '*',
                'sort asc'
            );
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
     * @param string $action_name
     * @param array $data
     */
    protected function addLog($action_name, $data = [])
    {
        $user = new User();
        $user->addUserLog($this->uid, $this->user_info[ 'username' ], $this->site_id, $action_name, $data);
    }
}
