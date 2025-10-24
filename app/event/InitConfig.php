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


namespace app\event;

use think\facade\Config;
use think\facade\App;

/**
 * 初始化配置信息
 * @author Administrator
 *
 */
class InitConfig
{
    public function handle()
    {
        // 初始化常量
        $this->initConst();
        //初始化配置信息
        $this->initConfig();


    }

    /**
     * 初始化常量
     */
    private function initConst()
    {
        //加载版本信息
        if (file_exists('app/version.php')) {
            $info = include 'app/version.php';
            if (is_array($info)) {
                Config::set($info, 'info');
            }
        } else {
            // 兼容：如果缺失则从全局配置读取，防止致命错误
            $globalInfoPath = 'config/info.php';
            if (file_exists($globalInfoPath)) {
                $info = include $globalInfoPath;
                if (is_array($info)) {
                    Config::set($info, 'info');
                }
            }
        }

        //加载基础化配置信息
        defined('__ROOT__') or define('__ROOT__', str_replace([ '/index.php', '/install.php' ], '', request()->root(true)));

        // public目录绝对路径
        defined('PUBLIC_PATH') or define('PUBLIC_PATH', dirname(dirname(dirname(__FILE__))) . '/public/');

        // 依据实际站点根是否为 public 决定资源前缀
        $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']) : '';
        $publicPath = str_replace('\\', '/', PUBLIC_PATH);
        $isPublicDocRoot = rtrim($docRoot, '/') === rtrim($publicPath, '/');

        defined('__PUBLIC__') or define('__PUBLIC__', $isPublicDocRoot ? __ROOT__ : (__ROOT__ . '/public'));
        defined('__UPLOAD__') or define('__UPLOAD__', 'upload');

        //插件目录名称
        defined('ADDON_DIR_NAME') or define('ADDON_DIR_NAME', 'addon');
        //插件目录路径
        defined('ADDON_PATH') or define('ADDON_PATH', 'addon/');
        //分页每页数量
        defined('PAGE_LIST_ROWS') or define('PAGE_LIST_ROWS', 10);
        //伪静态模式是否开启
        defined('REWRITE_MODULE') or define('REWRITE_MODULE', true);

        //兼容模式访问
        if (!REWRITE_MODULE) {
            defined('ROOT_URL') or define('ROOT_URL', request()->root(true) . '/?s=');
        } else {
            defined('ROOT_URL') or define('ROOT_URL', request()->root(true));
        }
        //检测网址访问
        $url = request()->url(true);
        $url = strtolower($url);
        if (strstr($url, 'call_user_func_array') || strstr($url, 'invokefunction') || strstr($url, 'think\view')) {
            die("非法请求");
        }

    }

    /**
     * 初始化配置信息
     */
    private function initConfig()
    {
        $view_array = [
            // 模板引擎类型使用Think
            'type' => 'Think',
            // 默认模板渲染规则 1 解析为小写+下划线 2 全部转换小写 3 保持操作方法
            'auto_rule' => 1,
            // 模板目录名
            'view_dir_name' => 'view',
            // 模板后缀
            'view_suffix' => 'html',
            // 模板文件名分隔符
            'view_depr' => DIRECTORY_SEPARATOR,
            // 模板引擎普通标签开始标记
            'tpl_begin' => '{',
            // 模板引擎普通标签结束标记
            'tpl_end' => '}',
            // 标签库标签开始标记
            'taglib_begin' => '{',
            // 标签库标签结束标记
            'taglib_end' => '}',
            'tpl_cache' => false,           //模板缓存，部署模式后改为true
            // 允许通过 {extend name="app/admin/view/base.html"} 这类跨模块路径引用模板
            // 将模板基础路径设置为项目根目录，避免相对路径在 public 作为工作目录时解析失败
            'view_base' => App::getRootPath(),
            'tpl_replace_string' => [
                '__ROOT__' => __ROOT__,
                'ROOT_URL' => ROOT_URL,
                '__STATIC__' => __PUBLIC__ . '/static',
                'STATIC_EXT' => __PUBLIC__ . '/static/ext',
                'STATIC_CSS' => __PUBLIC__ . '/static/css',
                'STATIC_JS' => __PUBLIC__ . '/static/js',
                'STATIC_IMG' => __PUBLIC__ . '/static/img',
                'ADMIN_IMG' => __ROOT__ . '/app/admin/view/public/img',
                'ADMIN_CSS' => __ROOT__ . '/app/admin/view/public/css',
                'ADMIN_JS' => __ROOT__ . '/app/admin/view/public/js',
                'SHOP_IMG' => __ROOT__ . '/app/shop/view/public/img',
                'SHOP_CSS' => __ROOT__ . '/app/shop/view/public/css',
                'SHOP_JS' => __ROOT__ . '/app/shop/view/public/js',
                '__UPLOAD__' => __UPLOAD__,
            ]
        ];
        // 与现有 view 配置合并，保留框架或其他代码设置的键，避免覆盖导致路径解析异常
        $current_view = (array)Config::get('view');
        Config::set(array_merge($current_view, $view_array), 'view');

    }

}
