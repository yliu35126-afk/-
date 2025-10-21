<?php

namespace addon\servicer\model;

use app\model\BaseModel;
use app\model\shop\Shop as ShopModel;
use app\model\web\WebSite as WebsiteModel;
use app\model\system\User as UserModel;

/**
 * 客服
 */
class Servicer extends BaseModel
{
    /**
     * 表名称
     */
    const TABLE_NAME = 'servicer';

    /**
     * 添加客服
     * @param array $data
     * @param array $params
     * @return array
     */
    public function add(array $data, array $params)
    {
        if (empty($data) || empty($params)) {
            return $this->error('', 'PARAMETER_ERROR');
        }

        model(self::TABLE_NAME)->startTrans();
        try {
            $user_model = new UserModel();
            $res        = $user_model->addUser($data, 'add');
            if ($res['code'] < 0) {
                model(self::TABLE_NAME)->rollback();
                return $res;
            }
            $user_info = $res['data'];

            $res = model(self::TABLE_NAME)->add(array_merge($params, [
                'is_platform' => ($user_info['site_id'] === 0 ? 1 : 0),
                'shop_id'     => $user_info['site_id'],
                'user_id'     => $user_info['uid'],
                'create_time' => time(),
            ]));

            model(self::TABLE_NAME)->commit();
            return $this->success($res);
        } catch (\Exception $e) {
            model(self::TABLE_NAME)->rollback();
            return $this->error('', '操作异常：' . $e->getMessage());
        }
    }

    /**
     * 编辑客服
     * @param array $data
     * @param array $condition
     * @param array $params
     * @return array
     */
    public function edit(array $data, array $condition, array $params = [])
    {
        if (empty($data)) {
            return $this->error('', 'PARAMETER_ERROR');
        }

        $user_model = new UserModel();
        $user_info  = $user_model->getUserInfo($condition)['data'];
        if (empty($user_info)) {
            return $this->error('', '客服不存在');
        }

        model(self::TABLE_NAME)->startTrans();
        try {
            $res = $user_model->editUser($data, $condition);
            if ($res['code'] < 0) {
                model(self::TABLE_NAME)->rollback();
                return $res;
            }

            if (!empty($params)) {
                model(self::TABLE_NAME)->update($params, [['shop_id', '=', $user_info['site_id']], ['user_id', '=', $user_info['uid']]]);
            }

            model(self::TABLE_NAME)->commit();
            return $this->success($res);
        } catch (\Exception $e) {
            model(self::TABLE_NAME)->rollback();
            return $this->error('', '操作异常：' . $e->getMessage());
        }
    }

    /**
     * 删除客服
     * @param $site_id
     * @param $uid
     * @return array
     */
    public function delete($site_id, $uid)
    {
        $condition = [
            ['site_id', '=', $site_id],
            ['uid', '=', $uid],
            ['app_module', '=', 'servicer']
        ];

        $user_model = new UserModel();
        $user_info  = $user_model->getUserInfo($condition)['data'];
        if (empty($user_info)) {
            return $this->error('', '客服不存在');
        }

        model(self::TABLE_NAME)->startTrans();
        try {
            $res = $user_model->deleteUser($condition);
            if ($res['code'] < 0) {
                model(self::TABLE_NAME)->rollback();
                return $res;
            }

            $res = model(self::TABLE_NAME)->delete([['shop_id', '=', $site_id], ['user_id', '=', $uid]]);

            model(self::TABLE_NAME)->commit();
            return $this->success($res);
        } catch (\Exception $e) {
            model(self::TABLE_NAME)->rollback();
            return $this->error('', '操作异常：' . $e->getMessage());
        }
    }

    /**
     * 获取信息
     * @param array $condition
     * @param bool $field
     * @return array
     */
    public function getInfo($condition = [], $field = true)
    {
        $info = model(self::TABLE_NAME)->getInfo($condition, $field);
        return $this->success($info);
    }

    /**
     * 获取客服详情
     * @param array $condition
     * @param bool $field
     * @return array
     */
    public function getDetail($condition = [], $field = true)
    {
        $info = $this->getInfo($condition, $field)['data'];
        if (!empty($info)) {
            $user_model        = new UserModel();
            $info['user_info'] = $user_model->getUserInfo([['uid', '=', $info['user_id']]])['data'];
        }
        return $this->success($info);
    }

    /**
     * 获取客服列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param bool $field
     * @param string $alias
     * @param array $join
     * @return array
     */
    public function getPageList(array $condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'id desc', $field = true, $alias = '', $join = [])
    {
        $res = model(self::TABLE_NAME)->pageList($condition, $field, $order, $page, $page_size, $alias, $join);
        return $this->success($res);
    }

    /**
     * 获取列表
     * @param array $condition
     * @param bool $field
     * @param string $order
     * @param string $alias
     * @param array $join
     * @param string $group
     * @param null $limit
     * @return array
     */
    public function getList($condition = [], $field = true, $order = 'id desc', $alias = '', $join = [], $group = '', $limit = null)
    {
        $res = model(self::TABLE_NAME)->getList($condition, $field, $order, $alias, $join, $group, $limit);
        return $this->success($res);
    }

    /**
     * 获取在线客服列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @return array
     */
    public function getOnlineList(array $condition, $field = 'user_id', $order = 'id desc')
    {
        $res = $this->getList(array_merge($condition, [['online', '=', 1]]), $field, $order);
        return $res;
    }

    /**
     * 分配客服
     * @param array $condition
     * @return array
     */
    public function assigning(array $condition)
    {
        $servicer_list = $this->getOnlineList($condition)['data'];
        $data          = [];

        foreach ($servicer_list as $val) {
            $data[] = [
                'user_id'    => $val['user_id'],
                'chat_count' => model('servicer_member')->getCount([
                    ['servicer_id', '=', $val['user_id']],
                    ['online', '=', 1],
                ])
            ];
        }
        if (!empty($data)) {
            // 按照服务的会员人数最少的优先分配
            $sort_data = array_column($data, 'chat_count');
            array_multisort($sort_data, SORT_ASC, $data);
        }
        return $data;
    }

    /**
     * 设置客服在线状态
     * @param $servicerId
     * @param $clientId
     * @param bool $online
     * @return int
     */
    public function setServicerOnlineStatus($servicerId, $clientId, $online = true)
    {
        if (!$online) {
            model('servicer_member')->update(
                ['client_id' => '', 'online' => $online],
                [['servicer_id', '=', $servicerId]]
            );
        }
        return model('servicer')->update(
            ['client_id' => $online ? $clientId : '', 'online' => $online, 'last_online_time' => time()],
            [['user_id', '=', $servicerId]]
        );
    }

    /**
     * 处理客服信息
     * @param array $condition
     * @param $site_id
     * @return array
     */
    public function handleServicerInfo(array $condition, $site_id)
    {
        $res = [
            'nickname' => '',
            'avatar'   => '',
        ];

        if (empty($condition)) {
            $info = [];
        } else {
            $info = $this->getInfo($condition, 'nickname, avatar')['data'];
        }

        if (empty($info)) {
            if ($site_id == 0) {
                $website_model = new WebsiteModel();
                $website_info  = $website_model->getWebSite([['site_id', '=', 0]], 'title, logo')['data'];
                if (!empty($website_info)) {
                    $res = [
                        'nickname' => $website_info['title'],
                        'avatar'   => $website_info['logo'],
                    ];
                }
            } else {
                $shop_model = new ShopModel();
                $shop_info = $shop_model->getShopInfo([['site_id', '=', $site_id]], 'site_name, logo')['data'];
                if (!empty($website_info)) {
                    $res = [
                        'nickname' => $shop_info['site_name'],
                        'avatar'   => $shop_info['logo'],
                    ];
                }
            }
        } else {
            $res = $info;
        }

        return $res;
    }
}
