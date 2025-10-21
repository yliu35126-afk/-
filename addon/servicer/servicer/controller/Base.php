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

namespace addon\servicer\servicer\controller;

use app\Controller;

/**
 * 客服端基础控制器
 * Class Base
 * @package addon\servicer\servicer\controller
 */
class Base extends Controller
{
    /**
     * 模块
     * @var string
     */
    protected $app_module = 'servicer';

    /**
     * 当前解析的路径
     * @var
     */
    protected $url;

    /**
     * 当前访问的插件名
     * @var string
     */
    protected $addon = '';

    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct();

        $this->url   = request()->parseUrl();
        $this->addon = request()->addon() ?: '';
    }


    /**
     * 加载模板输出
     * @param string $template 模板文件名
     * @param array $vars 模板输出变量
     * @param array $config 模板参数
     * @return mixed
     */
    public function fetch($template = '', $vars = [], $config = [])
    {
        $config = array_merge([
            'SERVICER_CSS' => __ROOT__ . '/'. ADDON_PATH . $this->addon .'/' . $this->app_module . '/view/public/css',
            'SERVICER_JS'  => __ROOT__ . '/'. ADDON_PATH . $this->addon .'/' . $this->app_module . '/view/public/js',
            'SERVICER_IMG' => __ROOT__ . '/'. ADDON_PATH . $this->addon .'/' . $this->app_module . '/view/public/img',
        ], $config);
        return parent::fetch($template, $vars, $config);
    }
}