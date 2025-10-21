<?php
// 事件定义文件
return [
    'bind' => [
    ],

    'listen' => [
        //定时群发
        'CronGroupSend' => [
            'addon\sitemessage\event\CronGroupSend',
        ],

    ],

    'subscribe' => [
    ],
];
