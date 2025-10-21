<?php

namespace app\model\map;

use app\model\BaseModel;
use app\model\web\Config as ConfigModel;
use extend\api\HttpClient;

class QqMap extends BaseModel
{
    protected $key = '';
    public function __construct($param = [])
    {
        if(!empty($param['key'])){
            $this->key = $param['key'];
        }else{
            $config_model = new ConfigModel();
            $mp_config = $config_model->getMapConfig();
            $this->key = $mp_config['data']['value']['tencent_map_key'];
        }
    }
    
    protected function httpGet($url, $query_data)
    {
        $httpClient = new HttpClient();
        $res_json = $httpClient->get($url, $query_data);
        return json_decode($res_json, true);
    }

    protected function httpPost($url, $post_data)
    {
        $httpClient = new HttpClient();
        $res_json = $httpClient->post($url, $post_data);
        return json_decode($res_json, true);
    }

    /**
     * 通过地址字符串获取详情
     * @param $address
     * @return bool|string
     */
    public function addressToDetail($param)
    {
        $url = 'https://apis.map.qq.com/ws/geocoder/v1/';
        $query_data = [
            'key' => $this->key,
            'address' => $param['address'],
        ];
        return $this->httpGet($url, $query_data);
    }

    /**
     * 通过ip获取详情
     * @param $ip
     * @return bool|string
     */
    public function ipToDetail($param)
    {
        $url = 'https://apis.map.qq.com/ws/location/v1/ip';
        $query_data = [
            'key' => $this->key,
        ];
        if($param['ip']){
            $query_data['ip'] = $param['ip'];
        }
        return $this->httpGet($url, $query_data);
    }

    /**
     * 通过经纬度获取详情
     * @param $param
     * @return bool|string
     */
    public function locationToDetail($param)
    {
        $url = 'https://apis.map.qq.com/ws/geocoder/v1/';
        $query_data = [
            'key' => $this->key,
            'location' => $param['location'],//$latitude.','.$longitude
            'get_poi' => 0,//是否返回周边POI列表：1.返回；0不返回(默认)
        ];
        return $this->httpGet($url, $query_data);
    }
}