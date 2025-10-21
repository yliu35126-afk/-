<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用。
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * =========================================================
 */

namespace addon\supply\model;

use app\model\BaseModel;
use app\model\system\Group;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Cache;

/**
 * 供应商表
 */
class Supplier extends BaseModel
{

    /**
     * 添加供应商
     * @param $data
     * @param $cert_data
     * @param $user_info
     * @return array
     */
    public function addSupplier($data, $cert_data, $user_info)
    {
        $count = model('supplier')->getCount([ [ 'title', '=', $data[ 'title' ] ] ]);
        if ($count > 0) {
            return $this->error('', '该供应商已经存在！');
        }
        model('supplier')->startTrans();
        try {
            //添加系统站
            $site_id = model("site")->add([ 'site_type' => 'supply' ]);

            $cert_data[ 'site_id' ] = $site_id;
            //添加认证信息
            $cert_id = model("supply_cert")->add($cert_data);

            //添加供应商
            $data[ 'cert_id' ] = $cert_id;
            $data[ 'supplier_site_id' ] = $site_id;
            $data[ 'username' ] = $user_info[ 'username' ];
            $data[ 'expire_time' ] = time() + 365 * 24 * 3600 * $data[ 'year' ];

            $data[ 'status' ] = 1;
            $data[ 'create_time' ] = time();
            unset($data[ 'year' ]);
            model('supplier')->add($data);

            //添加系统用户组
            $group = new Group();
            $group_data = [
                'site_id' => $site_id,
                'app_module' => 'supply',
                'group_name' => '管理员组',
                'is_system' => 1,
                'create_time' => time()
            ];
            $group_id = $group->addGroup($group_data)[ 'data' ];
            // 添加供应商相册默认分组
            model("album")->add([
                'site_id' => $site_id,
                'album_name' => "默认分组",
                'update_time' => time(),
                'is_default' => 1,
                'app_module' => 'supply'
            ]);

            //用户检测
            if (empty($user_info[ 'username' ])) {
                return $this->error('', 'USER_NOT_EXIST');
            }
            $user_count = model("user")->getCount(
                [ [ 'username', '=', $user_info[ 'username' ] ], [ 'app_module', '=', 'supply' ] ]
            );
            if ($user_count > 0) {
                return $this->error('', 'USERNAME_EXISTED');
            }

            //添加用户
            $data_user = [
                'app_module' => 'supply',
                'app_group' => 0,
                'is_admin' => 1,
                'group_id' => $group_id,
                'group_name' => '管理员组',
                'site_id' => $site_id
            ];
            $user_info = array_merge($data_user, $user_info);
            model("user")->add($user_info);
            Cache::tag("supply")->clear();
            model('supplier')->commit();
            return $this->success();
        } catch (\Exception $e) {

            model('supplier')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 修改供应商
     * @param $condition
     * @param $data
     * @return array|mixed|string
     */
    public function editSupplier($condition, $data)
    {
        $temp_data =$data;
        //查询过期时间有无更改
        $supplier = $this->getSupplierInfo($condition);
        $expire_time = $supplier['data']['expire_time'];
        //过期关闭供应商
        if (!empty($data[ "expire_time" ])) {
            if($data[ "expire_time" ] != $expire_time){
            //如果过期
                 if ($data[ "expire_time" ] - time() < 0) {
                    $data[ "status" ] = 0;
                }
            }
        }
        if(!empty($temp_data['status'])){
            if($supplier['data']['status'] != $temp_data['status']){
                $data[ "status" ] = $temp_data['status'];
            }
        }
        $res = model('supplier')->update($data, $condition);
        Cache::tag("supply")->clear();

        //订单关闭
        if (isset($data[ 'status' ]) && $data[ "status" ] == 0) {
            $check_condition = array_column($condition, 2, 0);
            $close_result = event("SupplyClose", [ "site_id" => $check_condition[ "supplier_site_id" ] ], true);

            if ($close_result[ "code" ] < 0) {
                return $close_result;
            }
        }
        return $this->success($res);
    }

    /**
     * 删除供货商
     * @param $supplier_id
     * @return array
     */
    public function deleteSupplier($supplier_id)
    {
        $supply_info = model('supplier')->getInfo([ [ 'supplier_id', '=', $supplier_id ] ], 'supplier_site_id,username');
        //todo 删除供应商
        $goods_count = model('supply_goods')->getCount([ [ 'site_id', '=', $supply_info[ 'supplier_site_id' ] ] ]);
        if ($goods_count > 0) {
            return $this->error('', '供应商下有商品，不可删除');
        }

        model('supplier')->startTrans();
        try {

            $res = model('supplier')->delete([ [ 'supplier_id', '=', $supplier_id ] ]);

            //获取用户信息
            $user_info = model('user')->getInfo(
                [ [ 'username', '=', $supply_info[ 'username' ] ], [ 'app_module', '=', 'supply' ] ],
                'uid,group_id'
            );

            if (!empty($user_info)) {
                //删除用户
                model('user')->delete([ [ 'uid', '=', $user_info[ 'uid' ] ] ]);
                //删除用户组
                model('group')->delete([ [ 'group_id', '=', $user_info[ 'group_id' ] ] ]);
                //删除登录记录
                model('user_log')->delete([ [ 'uid', '=', $user_info[ 'uid' ] ] ]);
            }
            Cache::tag("supply")->clear();
            model('supplier')->commit();
            return $this->success($res);

        } catch (\ Exception $e) {
            model('supplier')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 获取供应商分页列表
     * @param array $where
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getSupplierPageList($where = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*')
    {
        $data = json_encode([ $where, $field, $order, $page, $page_size ]);
        $cache = Cache::get("supplier_getSupplierPageList_" . $data);
        if (!empty($cache)) {
            return $this->success($cache);
        }
        $list = model("supplier")->pageList($where, $field, $order, $page, $page_size);
        Cache::tag("supply")->set("supplier_getSupplierPageList_" . $data, $list);
        return $this->success($list);
    }

    /**
     * 获取供应商信息
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getSupplierInfo($condition = [], $field = '*')
    {
//        $data = json_encode([$condition, $field]);
//        $cache = Cache::get("supplier_getSupplierInfo_" . $data);
//        if (!empty($cache)) {
//            return $this->success($cache);
//        }
        $info = model("supplier")->getInfo($condition, $field);
//        Cache::tag("supply")->set("supplier_getSupplierInfo_" . $data, $info);
        return $this->success($info);
    }

    /**
     * 获取供应商认证信息(包含结算账户)
     * @param $condition
     * @param string $field
     * @return array
     */
    public function getSupplierCert($condition, $field = '*')
    {
        $res = model('supply_cert')->getInfo($condition, $field);
        return $this->success($res);
    }

    /**
     * 编辑店铺认证信息，type为1表示更改认证信息
     * @param $data
     * @param $condition
     * @param int $type
     * @return array
     */
    public function editSupplierCert($data, $condition, $type = 0)
    {
        $check_condition = array_column($condition, 2, 0);
        $site_id = isset($check_condition[ 'site_id' ]) ? $check_condition[ 'site_id' ] : '';
        if ($site_id === '') {
            return $this->error('', 'REQUEST_SITE_ID');
        }

        $info = model('supply_cert')->getInfo($condition);
        model()->startTrans();
        try {
            if (!empty($info)) {
                $cert_id = $info[ 'cert_id' ];
                $res = model('supply_cert')->update($data, $condition);
            } else {
                $data[ 'site_id' ] = $site_id;
                $cert_id = model('supply_cert')->add($data);
                $res = $cert_id;
            }

            if ($type == 1) {
                $this->editSupplier([ [ 'supplier_site_id', '=', $site_id ] ], [ 'cert_id' => $cert_id ]);
            }

            model("supply_cert")->commit();
            return $this->success($res);
        } catch (\Exception $e) {
            model("supply_cert")->rollback();
            return $this->error("", $e->getMessage());
        }
    }

    /**
     * 供应商关闭
     * @param $site_id
     * @return array|mixed|string
     */
    public function supplyClose($site_id)
    {
        $res = model("supplier")->update([ "status" => 0 ], [ [ "supplier_site_id", "=", $site_id ] ]);
        if ($res === false) {
            return $this->error();
        }

        $result = event("SupplyClose", [ "site_id" => $site_id ], true);
        if ($result[ "code" ] < 0) {
            return $result;
        }
        return $this->success();
    }

    /**
     * 获取列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param null $limit
     * @return array
     */
    public function getSupplyList($condition = [], $field = '*', $order = '', $limit = null)
    {

        $list = model('supplier')->getList($condition, $field, $order, '', '', '', $limit);
        return $this->success($list);
    }

}
