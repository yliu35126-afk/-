<?php
// +----------------------------------------------------------------------
// | 应用设置
// +----------------------------------------------------------------------

use think\facade\Env;

return [
    // 应用地址
    'app_host'         => Env::get('app.host', ''),
    // 应用的命名空间
    'app_namespace'    => '',
    // 是否启用路由
    'with_route'       => true,

    'app_debug'     =>  false,
    // 是否启用事件
    'with_event'       => true,
    // 默认应用（单应用模式下无需指定，置空以使用自定义路由分发）
    'default_app'      => '',
    // 自动多应用模式：关闭，改用 AppInit 路由服务统一解析 /admin、/api、/addons 等
    'auto_multi_app'   => false,

    // 默认时区
    'default_timezone' => 'Asia/Shanghai',

    // 应用映射（自动多应用模式有效）
    // 明确声明可用应用，确保 /admin、/shop、/api 被解析为对应应用
    'app_map'          => [
        'admin' => 'admin',
        'shop'  => 'shop',
        'api'   => 'api',
    ],
    // 域名绑定（自动多应用模式有效）
    'domain_bind'      => [],
    // 禁止URL访问的应用列表（自动多应用模式有效）
    'deny_app_list'    => [],

    // 异常页面的模板文件
    'exception_tmpl'   => app()->getThinkPath() . 'tpl/think_exception.tpl',

    // 错误显示信息,非调试模式有效
    'error_message'    => '页面错误！请稍后再试～',
    // 显示错误信息
    'show_error_msg'   => true,

    // WebSocket 设置（默认从环境变量读取，未设置则走本地开发值）
    // 供 API 暴露给前端使用；后台可通过新增配置覆盖
    'ws_host'   => Env::get('ws.host', '127.0.0.1'),
    'ws_port'   => (int)Env::get('ws.port', 3001),
    'ws_scheme' => Env::get('ws.scheme', 'ws'), // 可选：ws 或 wss
    'ws_path'   => Env::get('ws.path', '/socket.io/'),
];
