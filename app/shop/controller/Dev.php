<?php
namespace app\shop\controller;

use app\model\system\User as UserModel;

/**
 * 开发调试：商家端模拟登录
 * 访问示例：/index.php/shop/dev/sim.html?u=shop_admin&p=123456
 */
class Dev
{
    public function sim()
    {
        $username = input('u', 'shop_admin');
        $password = input('p', '123456');

        $user_model = new UserModel();

        // 查询是否已有商家账号
        $info = model('user')->getInfo([
            ['username', '=', $username],
            ['app_module', '=', 'shop']
        ]);

        if (empty($info)) {
            // 选择一个可用店铺 site_id
            $first_shop = model('shop')->getInfo([], 'site_id,site_name,group_id');
            if (empty($first_shop) || !isset($first_shop['site_id'])) {
                return json([ 'code' => -1, 'message' => 'NO_SHOP_AVAILABLE' ]);
            }

            $site_id = (int)$first_shop['site_id'];

            // 确保存在一个 is_system=1 的管理员组
            $group = model('group')->getInfo([
                ['site_id', '=', $site_id],
                ['app_module', '=', 'shop'],
                ['is_system', '=', 1]
            ], 'group_id,group_name');

            if (empty($group)) {
                $group_model = new \app\model\system\Group();
                $group_id_res = $group_model->addGroup([
                    'site_id' => $site_id,
                    'app_module' => 'shop',
                    'group_name' => '管理员组',
                    'is_system' => 1,
                    'menu_array' => '',
                    'create_time' => time(),
                ]);
                if (($group_id_res['code'] ?? -1) < 0) {
                    return json($group_id_res);
                }
                $group_id = (int)$group_id_res['data'];
            } else {
                $group_id = (int)$group['group_id'];
            }

            // 创建商家账号并归属到该店铺与管理员组
            $create = $user_model->addUser([
                'username'   => $username,
                'password'   => $password,
                'site_id'    => $site_id,
                'app_module' => 'shop',
                'group_id'   => $group_id,
                'is_admin'   => 1,
            ], 'add');
            if (($create['code'] ?? -1) < 0) {
                return json($create);
            }

            // 为该店铺的开店套餐增加设备抽奖与幸运抽奖权限，避免插件权限拦截
            try {
                $shop_group_id = (int)($first_shop['group_id'] ?? 0);
                if ($shop_group_id > 0) {
                    $shop_group_info = model('shop_group')->getInfo([[ 'group_id', '=', $shop_group_id ]], 'group_id,group_name,addon_array');
                    $group_name = $shop_group_info['group_name'] ?? '';
                    $addon_array = $shop_group_info['addon_array'] ?? '';
                    $addons = array_filter(array_unique(explode(',', $addon_array ?: '')));
                    foreach (['turntable','device_turntable'] as $need) {
                        if (!in_array($need, $addons)) $addons[] = $need;
                    }
                    $new_addon_array = join(',', $addons);
                    $shop_group_model = new \app\model\shop\ShopGroup();
                    $shop_group_model->editGroup([
                        'group_id' => $shop_group_id,
                        'group_name' => $group_name ?: '管理员套餐',
                        'addon_array' => $new_addon_array,
                        'menu_array' => ''
                    ]);
                }
            } catch (\Throwable $e) {
                // 忽略套餐权限更新失败，模拟登录仍可进行
            }
        } else {
            // 校验状态
            if (($info['status'] ?? 1) != 1) {
                return json([ 'code' => -1, 'message' => 'USER_IS_LOCKED' ]);
            }
        }

        // 模拟登录，建立会话
        $res = $user_model->simulatedLogin($username, 'shop', 'pc');
        if (($res['code'] ?? -1) < 0) {
            return json($res);
        }

        // 跳转到商家首页
        return json([
            'code' => 0,
            'message' => 'ok',
            'data' => [
                'next' => url('shop/index/index')
            ]
        ]);
    }
}