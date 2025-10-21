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

namespace addon\supply\admin\controller;

use app\admin\controller\BaseAdmin;
use think\facade\Config;

class BaseSupply extends BaseAdmin
{

    protected $replace = [];    //视图输出字符串内容替换    相当于配置文件中的'view_replace_str'

    public function __construct()
    {
        parent::__construct();
        $this->replace = [
            'ADMIN_SUPPLY_CSS' => __ROOT__ . '/addon/supply/admin/view/public/css',
            'ADMIN_SUPPLY_JS' => __ROOT__ . '/addon/supply/admin/view/public/js',
            'ADMIN_SUPPLY_IMG' => __ROOT__ . '/addon/supply/admin/view/public/img',
        ];

        $tpl_replace_string = array_merge(config('view.tpl_replace_string'), $this->replace);
        $view[ 'tpl_replace_string' ] = $tpl_replace_string;
        Config::set($view, 'view');
    }


}