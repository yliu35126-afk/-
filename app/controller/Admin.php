<?php

namespace app\controller;

use app\Controller;

class Admin extends Controller
{
    // 兼容访问 /index.php/Admin 的情况，重定向到后台首页
    public function index()
    {
        return $this->redirect('admin/index/index');
    }
}