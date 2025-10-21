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

namespace addon\store\store\controller;

use app\model\store\Store as StoreModel;
use app\model\system\Address as AddressModel;
use app\model\web\Config as ConfigModel;
/**
 * 门店首页
 * @author Administrator
 *
 */
class Store extends BaseStore
{

    /**
     * 门店信息
     */
    public function config()
    {
        $condition = array(
            ["store_id", "=", $this->store_id],
            ["site_id", '=', $this->site_id]
        );

        $store_model = new StoreModel();
        if(request()->isAjax()){
            $store_name = input("store_name", '');
            $telphone = input("telphone", '');
            $store_image = input("store_image", '');
            $status = input("status", 0);
            $province_id = input("province_id", 0);
            $city_id = input("city_id", 0);
            $district_id = input("district_id", 0);
            $community_id = input("community_id", 0);
            $address = input("address", '');
            $full_address = input("full_address", '');
            $longitude = input("longitude", 0);
            $latitude = input("latitude", 0);
            $is_pickup = input("is_pickup", 0);
            $is_o2o = input("is_o2o", 0);
            $open_date = input("open_date", '');
            $data = array(
                "store_name" => $store_name,
                "telphone" => $telphone,
                "store_image" => $store_image,
                "status" => $status,
                "province_id" => $province_id,
                "city_id" => $city_id,
                "district_id" => $district_id,
                "community_id" => $community_id,
                "address" => $address,
                "full_address" => $full_address,
                "longitude" => $longitude,
                "latitude" => $latitude,
                "is_pickup" => $is_pickup,
                "is_o2o" => $is_o2o,
                "open_date" => $open_date,
            );
            $result = $store_model->editStore($data, $condition);
            return $result;
        }else {
            //查询省级数据列表
            $address_model = new AddressModel();
            $list = $address_model->getAreaList([["pid", "=", 0], ["level", "=", 1]]);
            $this->assign("province_list", $list["data"]);
            //门店信息
            $info_result = $store_model->getStoreInfo($condition);
            $this->assign("info", $info_result['data']);

            //获取地址key配置
            $config_model = new ConfigModel();
            $config = $config_model->getMapConfig();
            $this->assign('map_key', $config[ 'data' ][ 'value' ]);

            return $this->fetch("store/config",[],$this->replace);
        }
    }
}