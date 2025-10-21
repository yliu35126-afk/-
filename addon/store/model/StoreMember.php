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

namespace addon\store\model;

use app\model\BaseModel;
use app\model\system\Address;
use think\facade\Db;

/**
 * 店铺会员表
 */
class StoreMember extends BaseModel
{

    /**
     * 添加店铺关注会员
     * @param $site_id
     * @param $member_id
     * @param int $is_subscribe
     * @return array
     */
    public function addStoreMember($store_id, $member_id)
    {
        $shop_member_info = model("store_member")->getInfo([ [ 'store_id', '=', $store_id ], [ 'member_id', '=', $member_id ] ], 'id');
        if (!empty($shop_member_info)) {
            return $this->success();
        } else {
            $data = [
                'store_id' => $store_id,
                'member_id' => $member_id,
                'create_time' => time(),
            ];
            $res = model("store_member")->add($data);
        }

        return $this->success($res);
    }


    /**
     * 获取店铺会员分页列表
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getStoreMemberPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '')
    {
        $field = 'nm.member_id, nm.source_member, nm.username, nm.nickname, nm.mobile, nm.email, nm.headimg, nm.status, nsm.store_id, nsm.create_time';
        $alias = 'nsm';
        $join = [
            [
                'member nm',
                'nsm.member_id = nm.member_id',
                'inner'
            ],
        ];
        $list = model("store_member")->pageList($condition, $field, $order, $page, $page_size, $alias, $join);
        return $this->success($list);
    }

    /**
     * 获取会员店铺分页列表
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getMemberShopPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'nsm.subscribe_time desc', $field = 'ns.site_id, ns.site_name, ns.is_own, ns.logo, ns.telephone,ns.sub_num, nsm.subscribe_time,ns.seo_description,ns.shop_desccredit,ns.shop_servicecredit,ns.shop_deliverycredit,ns.shop_sales,ns.shop_sales,ns.is_own')
    {
        $alias = 'nsm';
        $join = [
            [
                'shop ns',
                'nsm.site_id = ns.site_id',
                'inner'
            ],
        ];
        $list = model("shop_member")->pageList($condition, $field, $order, $page, $page_size, $alias, $join);
        return $this->success($list);
    }

    /**
     * 获取门店会员数量
     * @param $condition
     * @param string $alias
     * @param array $join
     * @param null $group
     * @return array
     */
    public function getMemberCount($condition, $alias = 'a', $join = [], $group = null)
    {
        $db = Db::name('store_member')->where($condition);
        if (!empty($join)) {
            $db = $this->parseJoin($db->alias($alias), $join);
        }
        if (!empty($group)) {
            $db = $db->group($group);
        }
        $count = $db->count();
        return $this->success($count);
    }

    /**
     * 获取门店已购会员数
     */
    public function getPurchasedMemberCount($store_id)
    {
        $prefix = config("database")[ "connections" ][ "mysql" ][ "prefix" ];
        $res = model("store_member")->query("SELECT GROUP_CONCAT(member_id) as member_id FROM {$prefix}store_member WHERE store_id = {$store_id}");
        if (isset($res[ 0 ]) && isset($res[ 0 ][ 'member_id' ]) && !empty($res[ 0 ][ 'member_id' ])) {
            $condition = [
                [ 'delivery_store_id', '=', $store_id ],
                [ 'member_id', 'in', $res[ 0 ][ 'member_id' ] ]
            ];
            $res = model("order")->getList($condition, 'order_id', '', 'a', [], 'member_id');
            return $this->success(count($res));
        }
        return $this->success(0);
    }

    /**
     * 按地域分布查询会员数量
     * @param unknown $site_id
     * @param string $handle
     */
    public function getMemberCountByArea($site_id, $handle = false)
    {
        $total_count = $this->getMemberCount([ [ 'site_id', '=', $site_id ] ]);

        $address = new Address();
        $list = $address->getAreaList([ [ 'pid', '=', 0 ] ], 'id,shortname', 'sort asc');

        $data = [];

        if ($total_count[ 'data' ]) {
            foreach ($list[ 'data' ] as $item) {
                $count = $this->getMemberCount([ [ 'nsm.site_id', '=', $site_id ], [ 'nma.is_default', '=', 1 ], [ 'nma.province_id', '=', $item[ 'id' ] ] ], 'nsm', [ [ 'member_address nma', 'nsm.member_id = nma.member_id', 'left' ] ], 'nma.member_id');
                if ($handle) {
                    if ($count[ 'data' ] > 0) {
                        array_push($data, [
                            'name' => $item[ 'shortname' ],
                            'value' => $count[ 'data' ],
                            'ratio' => $count[ 'data' ] > 0 ? sprintf("%.2f", $count[ 'data' ] / $total_count[ 'data' ] * 100) : 0
                        ]);
                    }
                } else {
                    array_push($data, [
                        'name' => $item[ 'shortname' ],
                        'value' => $count[ 'data' ],
                        'ratio' => $count[ 'data' ] > 0 ? sprintf("%.2f", $count[ 'data' ] / $total_count[ 'data' ] * 100) : 0
                    ]);
                }
            }
        }

        if ($handle) {
            array_multisort(array_column($data, 'value'), SORT_DESC, $data);
        }

        return $this->success([
            'page_count' => 1,
            'count' => $total_count[ 'data' ],
            'list' => $data
        ]);
    }

    /**
     * 处理表连接
     * @param unknown $db_obj
     * @param unknown $join
     */
    protected function parseJoin($db_obj, $join)
    {
        foreach ($join as $item) {
            list($table, $on, $type) = $item;
            $type = strtolower($type);
            switch ( $type ) {
                case "left":
                    $db_obj = $db_obj->leftJoin($table, $on);
                    break;
                case "inner":
                    $db_obj = $db_obj->join($table, $on);
                    break;
                case "right":
                    $db_obj = $db_obj->rightjoin($table, $on);
                    break;
                case "full":
                    $db_obj = $db_obj->fulljoin($table, $on);
                    break;
                default:
                    break;
            }
        }
        return $db_obj;
    }

    /**
     * 查询会员信息
     * @param unknown $where
     * @param string $field
     * @param string $alias
     * @param string $join
     * @param string $data
     */
    public function getMemberInfo($where = [], $field = true, $alias = 'a', $join = null, $data = null)
    {
        $info = model("store_member")->getInfo($where, $field, $alias, $join, $data);
        if (empty($info)) return $this->error('', 'MEMBER_NOT_EXIST');
        else return $this->success($info);
    }

}