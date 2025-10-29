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

    /**
     * 批量创建并设置指定平台用户组（含菜单权限）
     * 访问示例：/admin/dev/roles.html
     */
    public function roles()
    {
        try {
            $app_module = 'admin';
            $site_id = 0;

            $menu_model = new \app\model\system\Menu();
            $group_model = new \app\model\system\Group();

            // 拉取平台端全部菜单，用于基于 parent 关系递归收集
            $menu_list_res = $menu_model->getMenuList([[ 'app_module', '=', $app_module ]], 'name,parent,level,is_show,url,addon');
            $menu_rows = (array)($menu_list_res['data'] ?? []);

            // 建立 parent -> children 索引
            $children = [];
            foreach ($menu_rows as $row) {
                $p = $row['parent'] ?? '';
                $n = $row['name'] ?? '';
                if ($n === '') continue;
                if (!isset($children[$p])) $children[$p] = [];
                $children[$p][] = $n;
            }

            // 递归收集从若干根节点出发的所有后代（包含根）
            $collect_desc = function(array $roots) use ($children) {
                $seen = [];
                $queue = [];
                foreach ($roots as $r) {
                    if ($r === '' || isset($seen[$r])) continue;
                    $seen[$r] = true;
                    $queue[] = $r;
                }
                for ($i = 0; $i < count($queue); $i++) {
                    $cur = $queue[$i];
                    if (!empty($children[$cur])) {
                        foreach ($children[$cur] as $child) {
                            if (!isset($seen[$child])) {
                                $seen[$child] = true;
                                $queue[] = $child;
                            }
                        }
                    }
                }
                return array_values(array_keys($seen));
            };

            // 角色与其菜单根节点映射（根据已安装的功能做收敛）
            $definitions = [
                // 设备主：设备抽奖全链路权限
                [ 'group_name' => '设备主', 'roots' => ['PROMOTION_ADMIN_TURNTABLE'] , 'desc' => '设备抽奖全权限' ],
                // 铺设者：设备投放与绑定、看板
                [ 'group_name' => '铺设者', 'roots' => ['PROMOTION_ADMIN_TURNTABLE_DEVICE','PROMOTION_ADMIN_TURNTABLE_DEVICE_PRICE_BIND','PROMOTION_ADMIN_TURNTABLE_STATS'] , 'desc' => '设备铺设与价档绑定' ],
                // 商家：平台店铺管理
                [ 'group_name' => '商家', 'roots' => ['SHOP_ROOT'] , 'desc' => '店铺管理相关权限' ],
                // 供应商：供应商插件全链路
                [ 'group_name' => '供应商', 'roots' => ['ADDON_SUPPLY_ROOT'] , 'desc' => '供应商管理相关权限' ],
                // 推广人：分销中心
                [ 'group_name' => '推广人', 'roots' => ['PROMOTION_FENXIAO_ROOT'] , 'desc' => '分销（推广）管理权限' ],
                // 代理：与推广人相同，可按需后续细分
                [ 'group_name' => '代理', 'roots' => ['PROMOTION_FENXIAO_ROOT'] , 'desc' => '代理推广相关权限' ],
                // 省市区：城市分站
                [ 'group_name' => '省市区', 'roots' => ['ADDON_CITY_ROOT'] , 'desc' => '城市分站相关权限' ],
            ];

            $result = [];
            foreach ($definitions as $def) {
                $roots = (array)($def['roots'] ?? []);
                // 根据当前已安装的功能判断是否能找到相应根节点
                $has_any = false;
                foreach ($roots as $r) {
                    if (isset($children['']) && in_array($r, $children[''], true)) { $has_any = true; break; }
                    // 也可能不是一级菜单，若任何父下有此节点也算存在
                    if (!$has_any) {
                        foreach ($children as $p => $arr) {
                            if ($p === '') continue;
                            if (in_array($r, $arr, true)) { $has_any = true; break 2; }
                        }
                    }
                }

                $menu_names = $has_any ? $collect_desc($roots) : [];
                $menu_array = implode(',', $menu_names);

                // 已存在则更新，不存在则创建
                $exists = $group_model->getGroupInfo([[ 'group_name', '=', $def['group_name'] ], [ 'site_id', '=', $site_id ], [ 'app_module', '=', $app_module ]], 'group_id');
                if (!empty($exists) && isset($exists['group_id'])) {
                    $save = $group_model->editGroup([
                        'menu_array' => $menu_array,
                        'group_status' => 1,
                        'desc' => $def['desc'] ?? ''
                    ], [[ 'group_id', '=', (int)$exists['group_id'] ]]);
                    $ok = ($save['code'] ?? -1) >= 0;
                    $result[] = [ 'group' => $def['group_name'], 'action' => 'updated', 'menus' => count($menu_names), 'ok' => $ok ];
                } else {
                    $add = $group_model->addGroup([
                        'group_name'   => $def['group_name'],
                        'site_id'      => $site_id,
                        'app_module'   => $app_module,
                        'group_status' => 1,
                        'menu_array'   => $menu_array,
                        'desc'         => $def['desc'] ?? '',
                        'is_system'    => 0,
                        'create_time'  => time(),
                    ]);
                    $ok = ($add['code'] ?? -1) >= 0;
                    $result[] = [ 'group' => $def['group_name'], 'action' => 'created', 'menus' => count($menu_names), 'ok' => $ok ];
                }
            }

            return json([ 'code' => 0, 'message' => 'ok', 'data' => $result ]);
        } catch (\Throwable $e) {
            return json([ 'code' => -1, 'message' => $e->getMessage() ]);
        }
    }

    /**
     * 本机工具：清理平台端不应出现的外部角色用户组，并保留平台岗位
     * 访问示例（仅本机有效）：/index.php/admin/dev/roles_cleanup
     */
    public function roles_cleanup()
    {
        // 仅允许本机调试使用
        $host = request()->host();
        $isLocal = preg_match('#^(localhost|127\\.0\\.0\\.1|\[::1\])(?::\\d+)?$#i', (string)$host);
        if (!$isLocal) {
            return json([ 'code' => -1, 'message' => '仅允许在本机访问此接口' ]);
        }

        try {
            $app_module = 'admin';
            $site_id = 0;

            $group_model = new \app\model\system\Group();

            // 需要从平台管理员组中移除的外部角色（应归属前台或各自模块）
            $remove_names = [
                '设备主','铺设者','商家','供应商','推广人','代理','省市区','省','市','区'
            ];

            $removed = [];
            foreach ($remove_names as $name) {
                // 找出并删除匹配的组（平台端、site_id=0、admin模块）
                $info = $group_model->getGroupInfo([[ 'group_name', '=', $name ], [ 'site_id', '=', $site_id ], [ 'app_module', '=', $app_module ]], 'group_id');
                if (!empty($info) && isset($info['group_id'])) {
                    $res = $group_model->deleteGroup([[ 'group_id', '=', (int)$info['group_id'] ]]);
                    $removed[] = [ 'group' => $name, 'ok' => (($res['code'] ?? -1) >= 0) ];
                }
            }

            // 可选：确保保留的平台岗位存在（不创建菜单，留待人工分配权限）
            $keep_names = ['系统管理员','设备运营','财务','风控'];
            $kept = [];
            foreach ($keep_names as $name) {
                $exists = $group_model->getGroupInfo([[ 'group_name', '=', $name ], [ 'site_id', '=', $site_id ], [ 'app_module', '=', $app_module ]], 'group_id');
                if (empty($exists) || !isset($exists['group_id'])) {
                    $add = $group_model->addGroup([
                        'group_name'   => $name,
                        'site_id'      => $site_id,
                        'app_module'   => $app_module,
                        'group_status' => 1,
                        'menu_array'   => '',
                        'desc'         => '平台岗位（权限待分配）',
                        'is_system'    => 0,
                        'create_time'  => time(),
                    ]);
                    $kept[] = [ 'group' => $name, 'action' => 'created', 'ok' => (($add['code'] ?? -1) >= 0) ];
                } else {
                    $kept[] = [ 'group' => $name, 'action' => 'exists', 'ok' => true ];
                }
            }

            return json([ 'code' => 0, 'message' => 'ok', 'data' => [ 'removed' => $removed, 'kept' => $kept ] ]);
        } catch (\Throwable $e) {
            return json([ 'code' => -1, 'message' => $e->getMessage() ]);
        }
    }

    /**
     * 本机工具：重置平台与商户账号密码
     * 访问示例（仅本机有效）：/index.php/admin/dev/reset?u1=admin&p1=123456&u2=shop&p2=123456
     */
    public function reset()
    {
        // 仅允许本机调试使用
        $host = request()->host();
        $isLocal = preg_match('#^(localhost|127\\.0\\.0\\.1|\[::1\])(?::\\d+)?$#i', (string)$host);
        if (!$isLocal) {
            return json([ 'code' => -1, 'message' => '仅允许在本机访问此接口' ]);
        }

        $u1 = input('u1', 'admin');
        $p1 = input('p1', '123456');
        $u2 = input('u2', 'shop');
        $p2 = input('p2', '123456');
        $cleanup = intval(input('cleanup', 0));

        $user_model = new UserModel();

        // 确保 admin 存在；不存在则创建（site_id=0，admin端）
        $adminInfo = model('user')->getInfo([[ 'username', '=', $u1 ], [ 'app_module', '=', 'admin' ]]);
        if (empty($adminInfo)) {
            $user_model->addUser([
                'username'   => $u1,
                'password'   => $p1,
                'site_id'    => 0,
                'app_module' => 'admin',
                'is_admin'   => 1,
                'status'     => 1,
            ], 'add');
        } else {
            // 重置密码并确保具有管理员权限且启用
            $user_model->modifyUserPassword($p1, [[ 'username', '=', $u1 ], [ 'app_module', '=', 'admin' ]]);
            model('user')->update([
                'is_admin' => 1,
                'status' => 1,
            ], [[ 'username', '=', $u1 ], [ 'app_module', '=', 'admin' ]]);
        }

        // 商户端：若账号不存在，则为第一个店铺自动创建“管理员组”和默认账号
        $shopInfo = model('user')->getInfo([[ 'username', '=', $u2 ], [ 'app_module', '=', 'shop' ]]);
        $shop_created = false;
        $group_created = false;
        if (empty($shopInfo)) {
            // 选择一个可用店铺site_id
            $first_shop = model('shop')->getInfo([], 'site_id,site_name,group_id');
            if (!empty($first_shop) && isset($first_shop['site_id'])) {
                $site_id = (int)$first_shop['site_id'];

                // 确保存在一个is_system=1的管理员组
                $group = model('group')->getInfo([[ 'site_id', '=', $site_id ], [ 'app_module', '=', 'shop' ], [ 'is_system', '=', 1 ]], 'group_id,group_name');
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
                    $group_created = ($group_id_res['code'] ?? -1) >= 0;
                    $group_id = $group_created ? (int)$group_id_res['data'] : 0;
                } else {
                    $group_id = (int)$group['group_id'];
                }

                // 创建shop管理员账号（带管理员组与启用状态）
                $add_res = $user_model->addUser([
                    'username'   => $u2,
                    'password'   => $p2,
                    'site_id'    => $site_id,
                    'app_module' => 'shop',
                    'is_admin'   => 1,
                    'status'     => 1,
                    'group_id'   => $group_id,
                    'group_name' => '管理员组',
                ], 'add');
                $shop_created = ($add_res['code'] ?? -1) >= 0;
            }
        } else {
            // 已存在则仅重置密码并确保启用
            $user_model->modifyUserPassword($p2, [[ 'username', '=', $u2 ], [ 'app_module', '=', 'shop' ]]);
            model('user')->update([ 'status' => 1 ], [[ 'username', '=', $u2 ], [ 'app_module', '=', 'shop' ]]);
        }

        // 可选：顺带清理平台端不应出现的外部角色（仅本机）
        $cleanup_result = [];
        if ($cleanup === 1) {
            try {
                $group_model = new \app\model\system\Group();
                $remove_names = ['设备主','铺设者','商家','供应商','推广人','代理','省市区','省','市','区'];
                foreach ($remove_names as $name) {
                    $info = $group_model->getGroupInfo([[ 'group_name', '=', $name ], [ 'site_id', '=', 0 ], [ 'app_module', '=', 'admin' ]], 'group_id');
                    if (!empty($info) && isset($info['group_id'])) {
                        $res = $group_model->deleteGroup([[ 'group_id', '=', (int)$info['group_id'] ]]);
                        $cleanup_result[] = [ 'group' => $name, 'ok' => (($res['code'] ?? -1) >= 0) ];
                    }
                }
            } catch (\Throwable $e) {
                $cleanup_result[] = [ 'error' => $e->getMessage() ];
            }
        }

        return json([
            'code' => 0,
            'message' => 'ok',
            'data' => [
                'admin_reset' => true,
                'shop_reset'  => !empty($shopInfo) || $shop_created,
                'shop_created' => $shop_created,
                'group_created' => $group_created,
                'cleanup' => $cleanup === 1 ? $cleanup_result : null,
            ]
        ]);
    }
}