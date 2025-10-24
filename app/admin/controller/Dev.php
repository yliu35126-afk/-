<?php
namespace app\admin\controller;

use app\model\system\User as UserModel;
use think\facade\Session;

class Dev
{
    /**
     * 调试接口：模拟管理员登录，必要时自动创建账号
     * 访问示例：/admin/dev/sim.html?u=admin&p=123456
     */
    public function sim()
    {
        $username = input('u', 'admin');
        $password = input('p', '123456');

        $user_model = new UserModel();

        // 查询是否存在该管理员账号
        $info = model('user')->getInfo([
            ['username', '=', $username],
            ['app_module', '=', 'admin']
        ]);

        if (empty($info)) {
            // 不存在则创建，平台管理员 site_id 设为 0
            $create = $user_model->addUser([
                'username'   => $username,
                'password'   => $password,
                'site_id'    => 0,
                'app_module' => 'admin'
            ], 'add');
            if (($create['code'] ?? -1) < 0) {
                return json($create);
            }
            $info = $create['data'] ?? null;
        }

        // 校验状态
        if (!empty($info) && ($info['status'] ?? 1) != 1) {
            return json([ 'code' => -1, 'message' => 'USER_IS_LOCKED' ]);
        }

        // 使用系统提供的模拟登录，建立会话
        $res = $user_model->simulatedLogin($username, 'admin', 'pc');
        if (($res['code'] ?? -1) < 0) {
            return json($res);
        }

        // 返回会话信息与跳转提示
        return json([
            'code' => 0,
            'message' => 'ok',
            'data' => [
                'uid' => Session::get('admin.uid'),
                'user_info' => Session::get('admin.user_info'),
                'next' => url('admin/index/index')
            ]
        ]);
    }
}