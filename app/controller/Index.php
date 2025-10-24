<?php
namespace app\controller;

use app\Controller;
use think\Response;

class Index extends Controller
{
    public function index()
    {
        // 将根路径重定向到移动端入口页
        return Response::create('/mshop/index.html', 'redirect');
    }
}