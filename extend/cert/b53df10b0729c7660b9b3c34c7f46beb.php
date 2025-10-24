<?php
namespace app\event;
use app\model\system\Upgrade;
use think\app\Service;
use think\facade\Route;
use app\model\system\Addon;
use think\facade\Cache;
class InitRoute extends Service {
    public function handle() {
        if (defined('BIND_MODULE') && BIND_MODULE === 'install') return;
        $ip = request()->ip();
        define("SHOP_AUTH_CODE", '');
        define("SHOP_AUTH_VERSION", '');

        $pathinfo = request()->pathinfo();
        $pathinfo_array = explode('/', $pathinfo);

        // 支持 /addons/{addon}/{module}/{action} 以及 /addons/{addon}/api/{action}
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

            $pathinfo = str_replace('.html', '', $pathinfo);
            $controller = str_replace('.html', '', $controller);
            $method = str_replace('.html', '', $method);
            $method = preg_replace_callback('/-([a-z])/', function ($m) { return strtoupper($m[1]); }, $method);

            /** @var \app\Request $req */
            $req = request();
            $req->addon($addon);
            $this->app->setNamespace("addon\\".$addon.'\\'.$module);
            $this->app->setAppPath($this->app->getRootPath() . 'addon' . DIRECTORY_SEPARATOR. $addon.DIRECTORY_SEPARATOR.$module.DIRECTORY_SEPARATOR);
            $req->module($module);
            // 调试日志
            $this->routeDebugLog('addons', compact('pathinfo','addon','module','controller','method'));
            Route::rule($pathinfo, $module.'/'.$controller. '/'. $method);
            return;
        }

        $check_model = $pathinfo_array[0];
        $addon_is_exit = addon_is_exit($check_model);
        $addon = $addon_is_exit == 1 ? $check_model : '';
        if(!empty($addon)) {
            $module = isset($pathinfo_array[1]) ? $pathinfo_array[1] : 'admin';
            $controller = isset($pathinfo_array[2]) ? $pathinfo_array[2] : 'index';
            $method = isset($pathinfo_array[3]) ? $pathinfo_array[3] : 'index';
            /** @var \app\Request $req */
            $req = request();
            $req->addon($addon);
            $this->app->setNamespace("addon\\".$addon.'\\'.$module);
            $this->app->setAppPath($this->app->getRootPath() . 'addon' . DIRECTORY_SEPARATOR. $addon.DIRECTORY_SEPARATOR.$module.DIRECTORY_SEPARATOR);
        } else {
            $module = isset($pathinfo_array[0]) ? $pathinfo_array[0] : 'admin';
            $controller = isset($pathinfo_array[1]) ? $pathinfo_array[1] : 'index';
            $method = isset($pathinfo_array[2]) ? $pathinfo_array[2] : 'index';
        }
        $pathinfo = str_replace('.html', '', $pathinfo);
        $controller = str_replace('.html', '', $controller);
        $method = str_replace('.html', '', $method);
        /** @var \app\Request $req */
        $req = request();
        $req->module($module);
        // 调试日志
        $this->routeDebugLog('core', compact('pathinfo','addon','module','controller','method'));
        Route::rule($pathinfo, $module.'/'.$controller. '/'. $method);
    }
    private function decrypt($data) {
        $format_data = substr($data, 32);
        $time = substr($data, -10);
        $decrypt_data = strstr($format_data, $time);
        $key = str_replace($decrypt_data, '', $format_data);
        $data = str_replace($time, '', $decrypt_data);
        $json_data = decrypt($data, $key);
        $array = json_decode($json_data, true);
        return $array;
    }
    private function addonsAuth() {
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
    private function authControl() {
        return [ 'fenxiao', 'pintuan' ];
    }
    private function routeDebugLog($scene, $info) {
        try {
            $runtime = app()->getRuntimePath();
            $file = $runtime . 'route_debug.log';
            $time = date('Y-m-d H:i:s');
            $ip = request()->ip();
            $line = '['.$time.'] ['.$scene.'] ['.$ip.'] ' . json_encode($info, JSON_UNESCAPED_UNICODE) . "\n";
            @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $e) {
            // 忽略日志异常
        }
    }
}