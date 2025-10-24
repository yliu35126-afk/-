<?php

namespace app\event;

use app\model\system\Upgrade;
use think\app\Service;
use think\facade\Route;
use app\model\system\Addon;
use think\facade\Cache;

class InitRoute extends Service
{
    public function handle()
    {
        if (defined('BIND_MODULE') && BIND_MODULE === 'install') return;
        
        define("NIUSHOP_AUTH_CODE", '');
        
        $pathinfo = request()->pathinfo();
        // 兼容 Nginx rewrite 使用 s=/module/controller/action.html 的方式
        if (!$pathinfo && isset($_GET['s']) && is_string($_GET['s'])) {
            $pathinfo = trim($_GET['s'], '/');
        }
        $raw_pathinfo = $pathinfo;
        $pathinfo_array = explode('/', $pathinfo);
        
        // 去掉伪静态后缀
        $pathinfo = str_replace('.html', '', $pathinfo);
        
        // 插件路由解析（两种风格）
        if (!empty($pathinfo_array) && $pathinfo_array[0] === 'addons' && isset($pathinfo_array[1]) && addon_is_exit($pathinfo_array[1]) == 1) {
            $addon = $pathinfo_array[1];
            $module = isset($pathinfo_array[2]) ? $pathinfo_array[2] : 'admin';
            if ($module === 'api' && isset($pathinfo_array[3]) && (!isset($pathinfo_array[4]) || $pathinfo_array[4] === '')) {
                $controller = strtolower($addon);
                $method = $pathinfo_array[3];
            } else {
                $controller = isset($pathinfo_array[3]) ? $pathinfo_array[3] : 'index';
                $method = isset($pathinfo_array[4]) ? $pathinfo_array[4] : 'index';
            }
            $controller = str_replace('.html', '', $controller);
            $method = str_replace('.html', '', $method);
            $method = preg_replace_callback('/-([a-z])/', function ($m) { return strtoupper($m[1]); }, $method);
            /** @var \app\Request $req */
            $req = request();
            $req->addon($addon);
            $this->app->setNamespace("addon\\" . $addon . '\\' . $module);
            $this->app->setAppPath($this->app->getRootPath() . 'addon' . DIRECTORY_SEPARATOR . $addon . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR);
            $req->module($module);
            Route::rule($pathinfo, $module . '/' . $controller . '/' . $method);
            // 调试日志
            @file_put_contents($this->app->getRuntimePath() . 'initroute.log', date('c') . " addons => $addon module => $module controller => $controller action => $method pathinfo_raw => $raw_pathinfo\n", FILE_APPEND);
            return;
        }
        
        // 常规应用解析
        $check_model = $pathinfo_array[0] ?? '';
        $addon_is_exit = $check_model ? addon_is_exit($check_model) : 0;
        $addon = $addon_is_exit == 1 ? $check_model : '';
        if (!empty($addon)) {
            $module = isset($pathinfo_array[1]) ? $pathinfo_array[1] : 'admin';
            $controller = isset($pathinfo_array[2]) ? $pathinfo_array[2] : 'index';
            $method = isset($pathinfo_array[3]) ? $pathinfo_array[3] : 'index';
            /** @var \app\Request $req */
            $req = request();
            $req->addon($addon);
            $this->app->setNamespace("addon\\" . $addon . '\\' . $module);
            $this->app->setAppPath($this->app->getRootPath() . 'addon' . DIRECTORY_SEPARATOR . $addon . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR);
        } else {
            $module = isset($pathinfo_array[0]) ? $pathinfo_array[0] : 'admin';
            $controller = isset($pathinfo_array[1]) ? $pathinfo_array[1] : 'index';
            $method = isset($pathinfo_array[2]) ? $pathinfo_array[2] : 'index';
        }
        $controller = str_replace('.html', '', $controller);
        $method = str_replace('.html', '', $method);
        /** @var \app\Request $req */
        $req = request();
        $req->module($module);
        Route::rule($pathinfo, $module . '/' . $controller . '/' . $method);
        // 调试日志
        @file_put_contents($this->app->getRuntimePath() . 'initroute.log', date('c') . " module => $module controller => $controller action => $method pathinfo => $pathinfo pathinfo_raw => $raw_pathinfo\n", FILE_APPEND);
    }

    private function addonsAuth()
    {
        $cache = Cache::get('auth_addon');
        if (!empty($cache)) return $cache;
        $upgrade = new Upgrade();
        $auth_addons = $upgrade->getAuthAddons();
        $addons = [];
        if ($auth_addons['code'] == 0) {
            $addons = array_column($auth_addons['data'], 'code');
        }
        Cache::set('auth_addon', $addons);
        return $addons;
    }

    private function authControl()
    {
        return ['fenxiao', 'pintuan'];
    }
}