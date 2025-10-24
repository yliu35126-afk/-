<?php
// 全局中间件定义文件
return [
    // 多应用解析（必须置于最前，确保 /admin、/shop 等正确解析）
    \think\app\MultiApp::class,
    // 全局请求缓存
    //\think\middleware\CheckRequestCache::class,
    // 多语言加载
    \think\middleware\LoadLangPack::class,
    // Session初始化
    \think\middleware\SessionInit::class,
];
