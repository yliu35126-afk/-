<?php
// +----------------------------------------------------------------------
// | 模板设置
// +----------------------------------------------------------------------

return [
    // 模板引擎类型使用Think
    'type'          => 'Think',
    // 默认模板渲染规则 1 解析为小写+下划线 2 全部转换小写 3 保持操作方法
    'auto_rule'     => 1,
    // 模板目录名
    'view_dir_name' => 'view',
    // 模板后缀
    'view_suffix'   => 'html',
    // 模板文件名分隔符
    'view_depr'     => DIRECTORY_SEPARATOR,
    // 模板引擎普通标签开始标记
    'tpl_begin'     => '{',
    // 模板引擎普通标签结束标记
    'tpl_end'       => '}',
    // 标签库标签开始标记
    'taglib_begin'  => '{',
    // 标签库标签结束标记
    'taglib_end'    => '}',
    // 允许跨模块模板路径解析，例如 app/admin/view/base.html
    // 设置模板基础路径为项目根目录
    'view_base'     => dirname(__DIR__) . DIRECTORY_SEPARATOR,

    // 视图输出字符串内容替换（供模板中使用的静态资源占位符）
    // 解决 Controller::fetch 合并配置时未定义 'tpl_replace_string' 的报错
    'tpl_replace_string' => [
        // 公共静态资源
        '__STATIC__' => '/public/static',
        'STATIC_EXT' => '/public/static/ext',

        // 后台静态资源
        'ADMIN_CSS'  => '/app/admin/view/public/css',
        'ADMIN_JS'   => '/app/admin/view/public/js',
        'ADMIN_IMG'  => '/app/admin/view/public/img',

        // 商家端静态资源
        'SHOP_CSS'   => '/app/shop/view/public/css',
        'SHOP_JS'    => '/app/shop/view/public/js',
        'SHOP_IMG'   => '/app/shop/view/public/img',
    ],
];
