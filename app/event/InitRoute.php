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
        $pathinfo_array = explode('/', $pathinfo);
        $check_model = $pathinfo_array[0];
        $addon_is_exit = addon_is_exit($check_model);
        $addon = $addon_is_exit == 1 ? $check_model : '';
        if (!empty($addon)) {
            $module = isset($pathinfo_array[1]) ? $pathinfo_array[1] : 'admin';
            $controller = isset($pathinfo_array[2]) ? $pathinfo_array[2] : 'index';
            $method = isset($pathinfo_array[3]) ? $pathinfo_array[3] : 'index';
            request()->addon($addon);
            $this->app->setNamespace("addon\\" . $addon . '\\' . $module);
            $this->app->setAppPath($this->app->getRootPath() . 'addon' . DIRECTORY_SEPARATOR . $addon . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR);
        } else {
            $module = isset($pathinfo_array[0]) ? $pathinfo_array[0] : 'admin';
            $controller = isset($pathinfo_array[1]) ? $pathinfo_array[1] : 'index';
            $method = isset($pathinfo_array[2]) ? $pathinfo_array[2] : 'index';
        }
        $pathinfo = str_replace(".html", '', $pathinfo);
        $controller = str_replace(".html", '', $controller);
        $method = str_replace(".html", '', $method);
        request()->module($module);
        Route::rule($pathinfo, $module . '/' . $controller . '/' . $method);
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