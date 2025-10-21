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

namespace app\model\shop;


use app\model\BaseModel;
use app\model\system\Stat;
use think\facade\Db;
use app\model\system\Address;

/**
 * 店铺会员表
 */
class ShopMember extends BaseModel
{

    /**
     * 添加店铺关注会员
     * @param $site_id
     * @param $member_id
     * @param int $is_subscribe
     * @return array
     */
    public function addShopMember($site_id, $member_id, $is_subscribe = 1)
    {

        $shop_member_info = model("shop_member")->getInfo([ [ 'site_id', '=', $site_id ], [ 'member_id', '=', $member_id ] ], 'id,is_subscribe');
        $sub_num = 0;
        if (!empty($shop_member_info)) {
            if($is_subscribe == 1){
                $res = model("shop_member")->update([ 'is_subscribe' => $is_subscribe, 'create_time' => time(), 'cancel_time' => 0 ], [ [ 'site_id', '=', $site_id ], [ 'member_id', '=', $member_id ] ]);

                if ($shop_member_info[ 'is_subscribe' ] == 0 && $is_subscribe == 1) {
                    $sub_num = 1;
                }
            }

        } else {
            $data = [
                'site_id' => $site_id,
                'member_id' => $member_id,
                'subscribe_time' => time(),
                'is_subscribe' => $is_subscribe,
                'create_time' => time(),
            ];
            $res = model("shop_member")->add($data);
            if ($is_subscribe == 1) {
                $sub_num = 1;
            }
        }

        if ($sub_num != 0) {
            //添加统计
            $stat = new Stat();
            if ($sub_num > 0) {
                model("shop")->setInc([ [ 'site_id', '=', $site_id ] ], 'sub_num', $sub_num);
                $stat->addShopStat([ 'collect_shop' => $sub_num, 'site_id' => $site_id ]);
            } else {
                model("shop")->setDec([ [ 'site_id', '=', $site_id ] ], 'sub_num', abs($sub_num));
                $stat->addShopStat([ 'collect_shop' => -$sub_num, 'site_id' => $site_id ]);
            }
        }
        return $this->success();
    }

    /**
     * 取消店铺关注会员
     * @param array $data
     */
    public function deleteShopMember($site_id, $member_id)
    {
        $is_sub = $this->isSubscribe($site_id, $member_id);
        if ($is_sub[ 'data' ] == 1) {
            $res = model("shop_member")->update([ 'is_subscribe' => 0, 'cancel_time' => time(), 'create_time' => 0 ], [ [ 'site_id', '=', $site_id ], [ 'member_id', '=', $member_id ] ]);
            model("shop")->setDec([ [ 'site_id', '=', $site_id ] ], 'sub_num');
            //添加统计
            $stat = new Stat();
            $stat->addShopStat([ 'collect_shop' => -1, 'site_id' => $site_id ]);
            return $this->success($res);
        } else {
            return $this->error();
        }
    }

    /**
     * 检测是否关注
     * @param int $site_id
     * @param int $member_id
     * @return number
     */
    public function isSubscribe($site_id, $member_id)
    {
        $info = model("shop_member")->getInfo([ [ 'site_id', '=', $site_id ], [ 'member_id', '=', $member_id ], [ 'is_subscribe', '=', 1 ] ], 'id');
        if (empty($info)) {
            return $this->success(0);
        } else {
            return $this->success(1);
        }
    }

    /**
     * 获取店铺会员分页列表
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getShopMemberPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '')
    {
        $condition[] = [ 'nm.is_delete', '=', 0 ];
        $field = 'nm.member_id, nm.source_member, nm.username, nm.nickname, nm.mobile, nm.email, nm.headimg, nm.status, nsm.subscribe_time, nsm.site_id, nsm.is_subscribe';
        $alias = 'nsm';
        $join = [
            [
                'member nm',
                'nsm.member_id = nm.member_id',
                'inner'
            ],
        ];
        $list = model("shop_member")->pageList($condition, $field, $order, $page, $page_size, $alias, $join);
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
        $condition[] = [ 'nm.is_delete', '=', 0 ];
        $alias = 'nsm';
        $join = [
            [
                'shop ns',
                'nsm.site_id = ns.site_id',
                'inner'
            ],
            [
                'member nm',
                'nsm.member_id = nm.member_id',
                'inner'
            ],
        ];
        $list = model("shop_member")->pageList($condition, $field, $order, $page, $page_size, $alias, $join);
        return $this->success($list);
    }

    /**
     * 获取店铺会员数量
     * @param unknown $condition
     * @param string $alias
     * @param unknown $join
     */
    public function getMemberCount($condition = [], $join = [])
    {
        $alias = 'nsm';
        $condition[] = [ 'nm.is_delete', '=', 0 ];

        $join[] = [ 'member nm', 'nsm.member_id = nm.member_id', 'left' ];

        $db = Db::name('shop_member')->where($condition);
        $db = $this->parseJoin($db->alias($alias), $join);
        $count = $db->count();

        return $this->success($count);
    }

    /**
     * 获取店铺已购会员数
     */
    public function getPurchasedMemberCount($site_id)
    {
        $prefix = config("database")[ "connections" ][ "mysql" ][ "prefix" ];

        $sql = "SELECT GROUP_CONCAT(nsm.member_id) as member_id FROM " . $prefix . "shop_member nsm ";
        $sql .= " LEFT JOIN " . $prefix . "member nm ON nsm.member_id = nm.member_id";
        $sql .= " WHERE nsm.site_id = " . $site_id . " and nm.is_delete = 0 ";

        $res = model("shop_member")->query($sql);
        if (isset($res[ 0 ]) && isset($res[ 0 ][ 'member_id' ]) && !empty($res[ 0 ][ 'member_id' ])) {
            $condition = [
                [ 'site_id', '=', $site_id ],
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
        $total_count = $this->getMemberCount([ [ 'nsm.site_id', '=', $site_id ] ]);

        $address = new Address();
        $list = $address->getAreaList([ [ 'pid', '=', 0 ] ], 'id,shortname', 'sort asc');

        $data = [];

        if ($total_count[ 'data' ]) {
            foreach ($list[ 'data' ] as $item) {
                $count = $this->getMemberCount(
                    [
                        [ 'nsm.site_id', '=', $site_id ],
                        [ 'nma.is_default', '=', 1 ],
                        [ 'nma.province_id', '=', $item[ 'id' ] ] ]

                    , [ [ 'member_address nma', 'nsm.member_id = nma.member_id', 'left' ] ]
                );
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
        $info = model("shop_member")->getInfo($where, $field, $alias, $join, $data);
        if (empty($info)) return $this->error('', 'MEMBER_NOT_EXIST');
        else return $this->success($info);
    }

}