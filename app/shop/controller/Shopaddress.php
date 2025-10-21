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

namespace app\shop\controller;

use app\model\shop\ShopAddress as ShopAddressModel;
use app\model\system\Address as AddressModel;


/**
 * 店铺地址库
 * Class Shop
 * @package app\shop\controller
 */
class Shopaddress extends BaseShop
{
    /**
     * 店铺地址列表
     */
    public function lists()
    {
        $address = new  ShopAddressModel();
        if (request()->isAjax()) {
            $page_index = input('page', 1);
            $page_size = input('limit', PAGE_LIST_ROWS);
            $condition = [
                [ 'site_id', '=', $this->site_id ]
            ];

            $list = $address->getAddressPageList($condition, $page_index, $page_size);
            return $list;
        } else {
            return $this->fetch("shopaddress/lists");
        }
    }

    /**
     * 添加地址
     * @return mixed
     */
    public function addAddress()
    {
        if (request()->isAjax()) {
            $contact_name = input("contact_name", '');
            $mobile = input("mobile", '');
            $postcode = input("postcode", '');
            $province_id = input("province_id", 0);
            $city_id = input("city_id", 0);
            $district_id = input("district_id", 0);
            $community_id = input("community_id", 0);
            $address = input("address", '');
            $is_return = input("is_return", '');
            $is_delivery = input("is_delivery", '');
            $data = array (
                "contact_name" => $contact_name,
                "mobile" => $mobile,
                "postcode" => $postcode,
                "province_id" => $province_id,
                "city_id" => $city_id,
                "district_id" => $district_id,
                "community_id" => $community_id,
                "address" => $address,
                "is_return" => $is_return,
                "is_delivery" => $is_delivery,
                "site_id" => $this->site_id
            );

            $address = new  ShopAddressModel();
            $result = $address->addAddress($data);
            return $result;
        } else {
            //查询省级数据列表
            $address_model = new AddressModel();
            $list = $address_model->getAreaList([ [ "pid", "=", 0 ], [ "level", "=", 1 ] ]);
            $this->assign("province_list", $list[ "data" ]);

            return $this->fetch("shopaddress/add_address");
        }
    }

    /**
     * 编辑地址
     * @return mixed
     */
    public function editAddress()
    {
        $address_id = input("address_id", 0);
        $condition = [
            [ 'id', '=', $address_id ]
        ];
        $address = new  ShopAddressModel();
        if (request()->isAjax()) {
            $contact_name = input("contact_name", '');
            $mobile = input("mobile", '');
            $postcode = input("postcode", '');
            $province_id = input("province_id", 0);
            $city_id = input("city_id", 0);
            $district_id = input("district_id", 0);
            $community_id = input("community_id", 0);
            $address = input("address", '');
            $is_return = input("is_return", '');
            $is_delivery = input("is_delivery", '');
            $data = array (
                "contact_name" => $contact_name,
                "mobile" => $mobile,
                "postcode" => $postcode,
                "province_id" => $province_id,
                "city_id" => $city_id,
                "district_id" => $district_id,
                "community_id" => $community_id,
                "address" => $address,
                "is_return" => $is_return,
                "is_delivery" => $is_delivery,
                "site_id" => $this->site_id
            );
            $result = $address->editAddress($data, $condition);
            return $result;
        } else {
            $address_result = $address->getAddressInfo($condition);//地址信息
            $info = $address_result[ "data" ];
            $this->assign("info", $info);

            //查询省级数据列表
            $address_model = new AddressModel();
            $list = $address_model->getAreaList([ [ "pid", "=", 0 ], [ "level", "=", 1 ] ]);
            $this->assign("province_list", $list[ "data" ]);

            return $this->fetch("shopaddress/edit_address");
        }
    }

    /**
     * 删除地址
     */
    public function deleteAddress()
    {
        if (request()->isAjax()) {
            $address_id = input("address_id", 0);
            $address = new  ShopAddressModel();
            $condition = [
                [ "id", '=', $address_id ]
            ];
            $res = $address->deleteAddress($condition);
            return $res;
        }
    }

}