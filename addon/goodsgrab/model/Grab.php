<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\goodsgrab\model;

use app\model\BaseModel;
use app\model\goods\Goods;
use app\model\goods\VirtualGoods;
use app\model\upload\Album;
use app\model\upload\Upload;

/**
 * 商品采集
 */
class Grab extends BaseModel
{
    //接口地址
    private $request_url = [
        'taobao'   => 'https://api03.6bqb.com/taobao/detail',
        'tmall'    => 'https://api03.6bqb.com/tmall/detail',
        'jd'       => 'https://api03.6bqb.com/jd/detail',
        '1688'     => 'https://api03.6bqb.com/alibaba/pro/detail'
    ];

    //采集平台
    private $url_type = [
        'detail.tmall.com'  =>    ['type' => 'tmall',   'type_name' => '天猫'],
        'item.taobao.com'   =>    ['type' => 'taobao',  'type_name' => '淘宝'],
        'detail.1688.com'   =>    ['type' => '1688',    'type_name' => '1688'],
        'item.jd.com'       =>    ['type' => 'jd',      'type_name' => '京东']
    ];

    /**
     * 生成采集记录
     * @param $data
     * @return array
     */
    public function addGoodsGrab($data)
    {
        $data['create_time'] = time();
        $res = model('goods_grab')->add($data);
        return $this->success($res);
    }

    /**
     * 添加采集明细记录
     * @param $grab_id
     * @param $data
     * @return array
     */
    public function addGoodsGrabDetail($data)
    {
        model('goods_grab_detail')->startTrans();
        try {
            model('goods_grab_detail')->add($data);
            $goods_grab_info = model('goods_grab')->getInfo(['grab_id' => $data['grab_id']]);

            $total_num = $goods_grab_info['total_num'] + 1;
            $success_num = $goods_grab_info['success_num'];
            $error_num = $goods_grab_info['error_num'];

            if ($data['status'] == 1) {
                $success_num += 1;
            } else {
                $error_num += 1;
            }

            model('goods_grab')->update(
                ['total_num' => $total_num, 'success_num' => $success_num, 'error_num' => $error_num],
                ['grab_id' => $data['grab_id']]
            );

            model('goods_grab_detail')->commit();
            return $this->success();
        } catch (\Exception $e) {

            model('goods_grab_detail')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 商品采集
     * @param $grab_id
     * @param $url
     * @return array
     */
    public function goodsGrab($data, $url)
    {
        set_time_limit(0);

        $site_id = $data['site_id'];
        $grab_id = $data['grab_id'];

        if ($grab_id == 0) {
            return $this->error('', '缺少参数grab_id');
        }
        if ($url == '') {
            return $this->error('', '商品链接不能为空');
        }

        model('goods_grab')->startTrans();
        try {
            //判断链接合法性
            if (!is_url($url)) {
                $this->addGoodsGrabDetail(['site_id' => $site_id, 'grab_id' => $grab_id, 'url' => $url, 'reason' => 'url地址不正确', 'status' => 2]);
                model('goods_grab')->commit();
                return $this->error('', 'url地址不正确');
            }
            //判断url类型  及 获取id
            $url_data = parse_url($url);
            if (!array_key_exists($url_data['host'], $this->url_type)) {
                $this->addGoodsGrabDetail(['site_id' => $site_id, 'grab_id' => $grab_id, 'url' => $url, 'reason' => '不支持的商品链接', 'status' => 2]);
                model('goods_grab')->commit();
                return $this->error('', '不支持的商品链接');
            }

            $url_type = $this->url_type[$url_data['host']];
            if ($url_type['type'] == 'jd' || $url_type['type'] == '1688') {
                $id = preg_replace('/[^0-9]/', '', $url_data['path']);
            }else {
                $params = $this->convertUrlQuery($url_data['query']);
                $id = $params['id'] ?? '';
            }

            if ($id == '') {
                $this->addGoodsGrabDetail(['site_id' => $site_id, 'grab_id' => $grab_id, 'url' => $url, 'reason' => 'url地址不正确', 'status' => 2]);
                model('goods_grab')->commit();
                return $this->error('', 'url地址不正确');
            }

            //99api key
            $config_model = new GoodsGrab();
            $config = $config_model->getGoodsGrabConfig($site_id);
            $key = $config['data']['value']['key'];

            $request_url = $this->request_url[$url_type['type']];
            $request_url = $request_url . '?apikey=' . urlencode($key) . '&itemid=' . urlencode($id);

            //获取商品数据
            $goods_data = $this->httpGet($request_url);

            if ($goods_data['code'] < 0) {

                $this->addGoodsGrabDetail(array_merge($url_type, ['site_id' => $site_id, 'grab_id' => $grab_id, 'url' => $url, 'reason' => $goods_data['message'], 'status' => 2]));
                model('goods_grab')->commit();
                return $goods_data;
            }

            //处理数据
            switch ($url_type['type']) {
                //淘宝
                case 'taobao':

                    $goods_data = $this->taobaoHandleData($goods_data['data']['data']['item'],$site_id);
                    break;
                //天猫
                case 'tmall':

                    $goods_data = $this->tmallHandleData($goods_data['data']['data']['item'],$site_id);
                    break;
                //京东
                case 'jd':

                    $goods_data = $this->jdHandleData($goods_data['data']['data']['item'],$site_id);
                    break;
                //1688
                case '1688':

                    $goods_data = $this->alibabaHandleData($goods_data['data']['data'],$site_id);
                    break;
            }
            if($goods_data['code'] < 0){
                $this->addGoodsGrabDetail(array_merge($url_type, ['site_id' => $site_id, 'grab_id' => $grab_id, 'url' => $url, 'reason' => $goods_data['message'], 'status' => 2]));
                model('goods_grab')->commit();
                return $goods_data;
            }
            $goods_data = $goods_data['data'];
            $goods_data['category_id'] = $data['category_id'];
            $goods_data['category_array'] = json_decode($data['category_json']);
            $goods_data['category_json'] = $data['category_json'];
            $goods_data['is_virtual'] = $data['is_virtual'];
            if($data['is_virtual']==1){
                $virtual_goods_model = new VirtualGoods();
                $res = $virtual_goods_model->addGoods($goods_data);
            }else{
                $goods_model = new Goods();
                $res = $goods_model->addGoods($goods_data);
            }
            if($res['code'] < 0){
                $this->addGoodsGrabDetail(array_merge($url_type, ['site_id' => $site_id, 'grab_id' => $grab_id, 'reason' => $res['message'], 'url' => $url, 'status' => 2]));
            }else{
                $this->addGoodsGrabDetail(array_merge($url_type, ['site_id' => $site_id, 'grab_id' => $grab_id, 'url' => $url, 'status' => 1]));
            }

            model('goods_grab')->commit();
            return $res;
        } catch (\Exception $e) {

            model('goods_grab')->rollback();
            return $this->error('', $e->getMessage());
        }
    }


    /**
     * 获取url中的参数
     * @param $query
     * @return array
     */
    function convertUrlQuery($query)
    {
        $queryParts = explode('&', $query);
        $params = array();
        foreach ($queryParts as $param) {
            $item = explode('=', $param);
            $params[$item[0]] = $item[1];
        }
        return $params;
    }


    /************************************************ 商品数据处理 start ********************************************************/

    /**
     * 淘宝数据处理
     * @param $key
     * @param $id
     */
    public function taobaoHandleData($params,$site_id)
    {
        try{

            $goods_spec_format = [];

            if(isset($params['props']) && !empty($params['props'])){

                foreach ($params['props'] as $k => $v) {
                    $goods_spec_format[$k] = [
                        'spec_id' => $v['pid'],
                        'spec_name' => $v['name']
                    ];
                    foreach ($v['values'] as $key => $val) {
                        $goods_spec_format[$k]['value'][$key] = [
                            'spec_id' => $v['pid'],
                            'spec_name' => $v['name'],
                            'spec_value_id' => $val['vid'],
                            'spec_value_name' => $val['name'],
                            'image' => ''
                        ];
                    }
                }
            }

            //商品sku
            $goods_sku_data = [];
            if(empty($goods_spec_format)){//单规格
                $goods_spec_format = '';
                $goods_sku_data[] = [
                    'spec_name' => '',
                    'sku_no' => "",
                    'sku_spec_format' => '',
                    'price' => '',
                    'market_price' => "",
                    'cost_price' => "",
                    'stock' => 0,
                    'stock_alarm' => 0,
                    'weight' => "",
                    'volume' => "",
                    'sku_image' => "",
                    'sku_images' => "",
                    'sku_images_arr' => [],
                    'is_default' => 0,
                    'propPath' => '',
                ];
            }else {//多规格

                foreach ($goods_spec_format as $k => $v) {

                    $item_prop_arr = [];
                    if(empty($goods_sku_data)){

                        foreach ($v['value'] as $key => $val) {
                            $item_prop_arr[] = [
                                'spec_name' => $val['spec_value_name'],
                                'sku_no' => "",
                                'sku_spec_format' => [$val],
                                'price' => "",
                                'market_price' => "",
                                'cost_price' => "",
                                'stock' => 0,
                                'stock_alarm' => 0,
                                'weight' => "",
                                'volume' => "",
                                'sku_image' => "",
                                'sku_images' => "",
                                'sku_images_arr' => [],
                                'is_default' => 0,
                                'propPath' => $val['spec_id'].':'.$val['spec_value_id'].';',
                            ];
                        }
                    }else{
                        foreach($goods_sku_data as $key1 => $val1){

                            foreach($v['value'] as $key2 => $val2){


                                $sku_spec_format = $val1['sku_spec_format'];
                                $sku_spec_format[] = $val2;

                                $item_prop_arr[] = [

                                    'spec_name' => $val1['spec_name'].' '.$val2['spec_value_name'],
                                    'sku_no' => "",
                                    'sku_spec_format' => $sku_spec_format,
                                    'price' => "",
                                    'market_price' => "",
                                    'cost_price' => "",
                                    'stock' => 0,
                                    'stock_alarm' => "",
                                    'weight' => "",
                                    'volume' => "",
                                    'sku_image' => "",
                                    'sku_images' => "",
                                    'sku_images_arr' => [],
                                    'is_default' => 0,
                                    'propPath' => $val1['propPath'].$val2['spec_id'].':'.$val2['spec_value_id'].';',
                                ];
                            }
                        }
                    }
                    $goods_sku_data = count($item_prop_arr) > 0 ? $item_prop_arr : $goods_sku_data;
                }
            }


            //获取商品价格
            foreach($goods_sku_data as $k=>$v){
                if(empty($goods_spec_format)){
                    $goods_sku_data[$k]['price'] = $params['sku'][0]['price'];
                }else{

                    $propPath = substr($v['propPath'],0,-1);
                    $sku_info = array_filter($params['sku'], function($t) use ($propPath) { return $t['propPath'] == $propPath; });

                    foreach($sku_info as $sku){
                        $goods_sku_data[$k]['price'] = $sku['price'];
                        $goods_sku_data[$k]['sku_image'] = $sku['image'] ?? '';
                    }
                }
                unset($goods_sku_data[$k]['propPath']);
            }

            //商品属性
            $groupProps = $params['groupProps'][0]['基本信息'] ?? [];
            $goods_attr_format = [];
            if(!empty($groupProps)){
                $num = 1;
                foreach($groupProps as $v){
                    foreach($v as $item_k=>$item_v){
                        $goods_attr_format[] = [
                            'attr_class_id' => '-'.($num + 1),
                            'attr_id' => '-'.($num + 2),
                            'attr_name' => $item_k,
                            'attr_value_id' => '-'.($num + 3),
                            'attr_value_name' => $item_v,
                            'sort' => 0
                        ];
                        $num += 1;
                    }
                }
            }
            $video_url = isset($params['videos']) ? $params['videos'][0]['url'] : '';

            $goods_data = [
                'goods_name' => $params['title'],// 商品名称,
                'goods_attr_class' => '',// 商品类型id,
                'goods_attr_name' => '',// 商品类型名称,
                'site_id' => $site_id,
                'category_id' => '',
                'category_json' => '',
                'goods_image' => $params['images'],// 商品主图路径
                'goods_content' => '',// 商品详情
                'goods_state' => 0,// 商品状态（1.正常0下架）
                'price' => $goods_sku_data[0]['price'],// 商品价格（取第一个sku）
                'market_price' => '',// 市场价格（取第一个sku）
                'cost_price' => '',// 成本价（取第一个sku）
                'sku_no' => '',// 商品sku编码
                'weight' => '',// 重量
                'volume' => '',// 体积
                'goods_stock' => 0,// 商品库存（总和）
                'goods_stock_alarm' => '',// 库存预警
                'is_free_shipping' => 1,// 是否免邮
                'shipping_template' => '',// 指定运费模板
                'goods_spec_format' => $goods_spec_format,// 商品规格格式
                'goods_attr_format' => $goods_attr_format,// 商品属性格式
                'introduction' => '',// 促销语
                'keywords' => '',// 关键词
                'unit' => '',// 单位
                'sort' => '',// 排序,
                'video_url' => $video_url,// 视频
                'goods_sku_data' => $goods_sku_data,// SKU商品数据
                'goods_service_ids' => '',// 商品服务id集合
                'label_id' => '',// 商品分组id
                'virtual_sale' => '',// 虚拟销量
                'max_buy' => '',// 限购
                'min_buy' => '',// 起售
                'recommend_way' => '', // 推荐方式，1：新品，2：精品，3；推荐
                'timer_on' => '',//定时上架
                'timer_off' => '',//定时下架
                'is_consume_discount' => ''//是否参与会员折扣
            ];

            //处理商品详情
            $desc = '';
            if (isset($params['desc'])) {
                $temp_array = explode('src="',$params['desc']);
                foreach($temp_array as $k => $v){
                    if($k > 0){
                        $position = strpos($v,'http');
                        if($position === false || $position !== 0){
                            $v = 'https:'.$v;
                        }
                        $desc .= 'src="'.$v;
                    }else{
                        $desc .= $v;
                    }
                }
            }

            $goods_data['goods_content'] = $desc;

            $goods_data = $this->downloadResources($goods_data,$site_id);
            if($goods_data['code'] < 0){
                return $goods_data;
            }
            $goods_data = $goods_data['data'];

            $goods_data['goods_spec_format'] = empty($goods_data['goods_spec_format']) ? '' : json_encode($goods_data['goods_spec_format']);
            $goods_data['goods_attr_format'] = json_encode($goods_data['goods_attr_format']);
            $goods_data['goods_sku_data'] = json_encode($goods_data['goods_sku_data']);

            return $this->success($goods_data);
        }catch (\Exception $e){

            return $this->error('',$e->getMessage());
        }
    }

    /**
     * 天猫数据处理
     * @param $key
     * @param $id
     */
    public function tmallHandleData($params,$site_id)
    {
        try{

            $goods_spec_format = [];

            foreach ($params['props'] as $k => $v) {
                $goods_spec_format[$k] = [
                    'spec_id' => $v['pid'],
                    'spec_name' => $v['name']
                ];
                foreach ($v['values'] as $key => $val) {
                    $goods_spec_format[$k]['value'][$key] = [
                        'spec_id' => $v['pid'],
                        'spec_name' => $v['name'],
                        'spec_value_id' => $val['vid'],
                        'spec_value_name' => $val['name'],
                        'image' => ''
                    ];
                }
            }

            //商品sku
            $goods_sku_data = [];
            foreach ($goods_spec_format as $k => $v) {

                $item_prop_arr = [];
                if(empty($goods_sku_data)){

                    foreach ($v['value'] as $key => $val) {
                        $item_prop_arr[] = [
                            'spec_name' => $val['spec_value_name'],
                            'sku_no' => "",
                            'sku_spec_format' => [$val],
                            'price' => "",
                            'market_price' => "",
                            'cost_price' => "",
                            'stock' => 0,
                            'stock_alarm' => 0,
                            'weight' => "",
                            'volume' => "",
                            'sku_image' => "",
                            'sku_images' => "",
                            'sku_images_arr' => [],
                            'is_default' => 0,
                            'propPath' => $val['spec_id'].':'.$val['spec_value_id'].';',
                        ];
                    }
                }else{
                    foreach($goods_sku_data as $key1 => $val1){

                        foreach($v['value'] as $key2 => $val2){

                            $sku_spec_format = $val1['sku_spec_format'];
                            $sku_spec_format[] = $val2;

                            $item_prop_arr[] = [
                                'spec_name' => $val1['spec_name'].' '.$val2['spec_value_name'],
                                'sku_no' => "",
                                'sku_spec_format' => $sku_spec_format,
                                'price' => "",
                                'market_price' => "",
                                'cost_price' => "",
                                'stock' => 0,
                                'stock_alarm' => "",
                                'weight' => "",
                                'volume' => "",
                                'sku_image' => "",
                                'sku_images' => "",
                                'sku_images_arr' => [],
                                'is_default' => 0,
                                'propPath' => $val1['propPath'].$val2['spec_id'].':'.$val2['spec_value_id'].';',
                            ];
                        }
                    }
                }
                $goods_sku_data = count($item_prop_arr) > 0 ? $item_prop_arr : $goods_sku_data;
            }

            //获取商品价格
            foreach($goods_sku_data as $k=>$v){
                $propPath = substr($v['propPath'],0,-1);

                $sku_info = array_filter($params['sku'], function($t) use ($propPath) { return $t['propPath'] == $propPath; });
                foreach($sku_info as $sku){
                    $goods_sku_data[$k]['price'] = $sku['price'];
                    $goods_sku_data[$k]['sku_image'] = $sku['image'] ?? '';
                }
                unset($goods_sku_data[$k]['propPath']);
            }

            //商品属性
            $groupProps = $params['groupProps'][0]['基本信息'] ?? [];
            $goods_attr_format = [];
            if(!empty($groupProps)){
                foreach($groupProps as $v){
                    foreach($v as $item_k=>$item_v){
                        $goods_attr_format[] = [
                            'attr_class_id' => '-'.mt_rand(0,9).mt_rand(1,999),
                            'attr_id' => '-'.mt_rand(0,9).mt_rand(1,999),
                            'attr_name' => $item_k,
                            'attr_value_id' => '-'.mt_rand(0,9).mt_rand(1,999),
                            'attr_value_name' => $item_v,
                            'sort' => 0
                        ];
                    }
                }
            }
            $video_url = isset($params['videos']) ? $params['videos'][0]['url'] : '';

            $goods_data = [
                'goods_name' => $params['title'],// 商品名称,
                'goods_attr_class' => '',// 商品类型id,
                'goods_attr_name' => '',// 商品类型名称,
                'site_id' => $site_id,
                'category_id' => '',
                'category_json' => '',
                'goods_image' => $params['images'],// 商品主图路径
                'goods_content' => '',// 商品详情
                'goods_state' => 0,// 商品状态（1.正常0下架）
                'price' => $goods_sku_data[0]['price'],// 商品价格（取第一个sku）
                'market_price' => '',// 市场价格（取第一个sku）
                'cost_price' => '',// 成本价（取第一个sku）
                'sku_no' => '',// 商品sku编码
                'weight' => '',// 重量
                'volume' => '',// 体积
                'goods_stock' => 0,// 商品库存（总和）
                'goods_stock_alarm' => '',// 库存预警
                'is_free_shipping' => 1,// 是否免邮
                'shipping_template' => '',// 指定运费模板
                'goods_spec_format' => $goods_spec_format,// 商品规格格式
                'goods_attr_format' => $goods_attr_format,// 商品属性格式
                'introduction' => '',// 促销语
                'keywords' => '',// 关键词
                'unit' => '',// 单位
                'sort' => '',// 排序,
                'video_url' => $video_url,// 视频
                'goods_sku_data' => $goods_sku_data,// SKU商品数据
                'goods_service_ids' => '',// 商品服务id集合
                'label_id' => '',// 商品分组id
                'virtual_sale' => '',// 虚拟销量
                'max_buy' => '',// 限购
                'min_buy' => '',// 起售
                'recommend_way' => '', // 推荐方式，1：新品，2：精品，3；推荐
                'timer_on' => '',//定时上架
                'timer_off' => '',//定时下架
                'is_consume_discount' => ''//是否参与会员折扣
            ];

            //处理商品详情
            $desc = '';

            $temp_array = explode('src="',$params['desc']);
            foreach($temp_array as $k => $v){
                if($k > 0){
                    $position = strpos($v,'http');
                    if($position === false || $position !== 0){
                        $v = 'https:'.$v;
                    }
                    $desc .= 'src="'.$v;
                }else{
                    $desc .= $v;
                }
            }
            $goods_data['goods_content'] = $desc;

            $goods_data = $this->downloadResources($goods_data,$site_id);
            if($goods_data['code'] < 0){
                return $goods_data;
            }
            $goods_data = $goods_data['data'];

            $goods_data['goods_spec_format'] = json_encode($goods_data['goods_spec_format']);
            $goods_data['goods_attr_format'] = json_encode($goods_data['goods_attr_format']);
            $goods_data['goods_sku_data'] = json_encode($goods_data['goods_sku_data']);

            return $this->success($goods_data);
        }catch (\Exception $e){

            return $this->error('',$e->getMessage());
        }
    }

    /**
     * 京东数据处理
     * @param $key
     * @param $id
     */
    public function jdHandleData($params,$site_id)
    {
        try{

            $goods_spec_format = [];
            $num = 0;
            foreach ($params['saleProp'] as $k=>$v) {

                $sku_props = $params['skuProps'][$k];
                if(!empty($sku_props)){
                    $spec_id = $k.mt_rand(1,99).mt_rand(1,999);
                    $goods_spec_format[$num] = [
                        'spec_id' => $spec_id,
                        'spec_name' => $v,
                        'value' => []
                    ];

                    foreach($sku_props as $key => $val){
                        if(!empty($val)){
                            $goods_spec_format[$num]['value'][$key] = [
                                'spec_id' => $spec_id,
                                'spec_name' => $v,
                                'spec_value_id' => $key.mt_rand(1,999).mt_rand(1,99),
                                'spec_value_name' => $val,
                                'image' => ''
                            ];
                        }
                    }
                }
                if(empty($goods_spec_format[$num]['value'])){
                    unset($goods_spec_format[$num]);
                }
                $num ++;
            }

            //商品sku
            $goods_sku_data = [];
            if(empty($goods_spec_format)){//单规格

                $goods_spec_format = '';
                $goods_sku_data[] = [
                    'spec_name' => '',
                    'sku_no' => "",
                    'sku_spec_format' => '',
                    'price' => '',
                    'market_price' => "",
                    'cost_price' => "",
                    'stock' => 0,
                    'stock_alarm' => 0,
                    'weight' => "",
                    'volume' => "",
                    'sku_image' => "",
                    'sku_images' => "",
                    'sku_images_arr' => [],
                    'is_default' => 0,
                    'propPath' => '',
                ];
            }else{//多规格

                foreach ($goods_spec_format as $k => $v) {

                    $item_prop_arr = [];
                    if(empty($goods_sku_data)){

                        foreach ($v['value'] as $key => $val) {
                            $item_prop_arr[] = [
                                'spec_name' => $val['spec_value_name'],
                                'sku_no' => "",
                                'sku_spec_format' => [$val],
                                'price' => "",
                                'market_price' => "",
                                'cost_price' => "",
                                'stock' => 0,
                                'stock_alarm' => 0,
                                'weight' => "",
                                'volume' => "",
                                'sku_image' => "",
                                'sku_images' => "",
                                'sku_images_arr' => [],
                                'is_default' => 0,
                                'propPath' => $val['spec_value_name'].':',
                            ];
                        }
                    }else{
                        foreach($goods_sku_data as $key1 => $val1){
                            foreach($v['value'] as $key2 => $val2){

                                $sku_spec_format = $val1['sku_spec_format'];
                                $sku_spec_format[] = $val2;
                                $item_prop_arr[] = [

                                    'spec_name' => $val1['spec_name'].' '.$val2['spec_value_name'],
                                    'sku_no' => "",
                                    'sku_spec_format' => $sku_spec_format,
                                    'price' => "",
                                    'market_price' => "",
                                    'cost_price' => "",
                                    'stock' => 0,
                                    'stock_alarm' => "",
                                    'weight' => "",
                                    'volume' => "",
                                    'sku_image' => "",
                                    'sku_images' => "",
                                    'sku_images_arr' => [],
                                    'is_default' => 0,
                                    'propPath' => $val1['propPath'].$val2['spec_value_name'].':',
                                ];
                            }
                        }
                    }
                    $goods_sku_data = count($item_prop_arr) > 0 ? $item_prop_arr : $goods_sku_data;
                }
            }

            //获取商品价格
            foreach($goods_sku_data as $k=>$v){

                if($v['propPath'] == ''){
                    $prop_path_arr = [
                        '1' => '',
                        '2' => '',
                        '3' => ''
                    ];
                }else{
                    $propPath = substr($v['propPath'],0,-1);
                    $prop_path_arr = explode(':',$propPath);
                    $prop_path_arr = [
                        '1' => $prop_path_arr[0] ?? '',
                        '2' => $prop_path_arr[1] ?? '',
                        '3' => $prop_path_arr[2] ?? '',
                    ];
                }

                $is_exists = 0;
                foreach($params['sku'] as $item_k => $item_v){

                    //判断该sku是否存在
                    if($item_v[1] != ""){
                        if($prop_path_arr[1] == $item_v[1] && $prop_path_arr[2] == $item_v[2] && $prop_path_arr[3] == $item_v[3]){
                            $is_exists = 1;
                            unset($goods_sku_data[$k]['propPath']);
                            $goods_sku_data[$k]['price'] = $item_v['price'];
                            break;
                        }
                    }

                    if($item_v[1] == "" && $item_v[2] != ""){
                        if($prop_path_arr[1] == $item_v[2] && $prop_path_arr[3] == $item_v[3]){
                            $is_exists = 1;
                            unset($goods_sku_data[$k]['propPath']);
                            $goods_sku_data[$k]['price'] = $item_v['price'];
                        }
                    }

                    if($item_v[1] == "" && $item_v[2] == ""){
                        if($prop_path_arr[1] == $item_v[3]){
                            $is_exists = 1;
                            unset($goods_sku_data[$k]['propPath']);
                            $goods_sku_data[$k]['price'] = $item_v['price'];
                        }
                    }
                }
                if($is_exists == 0){
                    unset($goods_sku_data[$k]);
                }

            }
            if(empty($goods_sku_data)){
                return $this->error();
            }
            $goods_sku_data = array_values($goods_sku_data);

            //商品属性
            $goods_attr_format = [];
            if(!empty($params['pcAttributes'])){
                foreach($params['pcAttributes'] as $k=>$v){

                    $val = explode('：',$v);
                    $goods_attr_format[] = [
                        'attr_class_id' => '-'. $k . mt_rand(1,99) . mt_rand(1,99),
                        'attr_id' => '-'. $k . mt_rand(1,99) . mt_rand(1,99),
                        'attr_name' => $val[0],
                        'attr_value_id' => '-'. $k . mt_rand(1,99) . mt_rand(1,99),
                        'attr_value_name' => $val[1],
                        'sort' => 0
                    ];
                }
            }

            $video_url = "";
            if(isset($params['videoInfo'])){
                if(isset($params['videoInfo']['videoUrl'])){
                    $video_url = $params['videoInfo']['videoInfo'] ? $params['videoInfo']['videoUrl'] : '';
                    $video_url = is_array($video_url) ? $video_url['android'] : $video_url;
                }
                if(isset($params['videoInfo']['data'])){
                    $video_url = is_array($params['videoInfo']['data']) ? $params['videoInfo']['data'][2]['main_url'] : '';
                }
            }
            $goods_data = [
                'goods_name' => $params['name'],// 商品名称,
                'goods_attr_class' => '',// 商品类型id,
                'goods_attr_name' => '',// 商品类型名称,
                'site_id' => $site_id,
                'category_id' => '',
                'category_json' => '',
                'goods_image' => $params['images'],// 商品主图路径
                'goods_content' => '',// 商品详情
                'goods_state' => 0,// 商品状态（1.正常0下架）
                'price' => $goods_sku_data[0]['price'],// 商品价格（取第一个sku）
                'market_price' => '',// 市场价格（取第一个sku）
                'cost_price' => '',// 成本价（取第一个sku）
                'sku_no' => '',// 商品sku编码
                'weight' => '',// 重量
                'volume' => '',// 体积
                'goods_stock' => 0,// 商品库存（总和）
                'goods_stock_alarm' => '',// 库存预警
                'is_free_shipping' => 1,// 是否免邮
                'shipping_template' => '',// 指定运费模板
                'goods_spec_format' => $goods_spec_format,// 商品规格格式
                'goods_attr_format' => $goods_attr_format,// 商品属性格式
                'introduction' => '',// 促销语
                'keywords' => '',// 关键词
                'unit' => '',// 单位
                'sort' => '',// 排序,
                'video_url' => $video_url,// 视频
                'goods_sku_data' => $goods_sku_data,// SKU商品数据
                'goods_service_ids' => '',// 商品服务id集合
                'label_id' => '',// 商品分组id
                'virtual_sale' => '',// 虚拟销量
                'max_buy' => '',// 限购
                'min_buy' => '',// 起售
                'recommend_way' => '', // 推荐方式，1：新品，2：精品，3；推荐
                'timer_on' => '',//定时上架
                'timer_off' => '',//定时下架
                'is_consume_discount' => ''//是否参与会员折扣
            ];

            //处理商品详情
            if(!empty($params['desc'])){
                $old_desc = preg_replace('/(<img.+?data-lazyload=")(.*?)/','<img src="$2', $params['desc']);

                $temp_array = explode('src="',$old_desc);
                $desc = '';
                foreach($temp_array as $k => $v){
                    if($k > 0){
                        $position = strpos($v,'http');
                        if($position === false || $position !== 0){
                            $v = 'https:'.$v;
                        }
                        $desc .= 'src="'.$v;
                    }else{
                        $desc .= $v;
                    }
                }
                $goods_data['goods_content'] = $desc;
            }

            $goods_data = $this->downloadResources($goods_data,$site_id);
            if($goods_data['code'] < 0){
                return $goods_data;
            }
            $goods_data = $goods_data['data'];
            if($goods_data['goods_spec_format']){
                sort($goods_data['goods_spec_format']);//排序
            }
            $goods_data['goods_spec_format'] = empty($goods_data['goods_spec_format']) ? '' : json_encode($goods_data['goods_spec_format']);
            $goods_data['goods_attr_format'] = json_encode($goods_data['goods_attr_format']);
            $goods_data['goods_sku_data'] = json_encode($goods_data['goods_sku_data']);

            return $this->success($goods_data);
        }catch (\Exception $e){

            return $this->error('',$e->getMessage());
        }
    }

    /**
     * 1688数据处理
     * @param $key
     * @param $id
     */
    public function alibabaHandleData($params,$site_id)
    {
        try{
            $goods_spec_format = [];
            if(isset($params['skuProps'])){

                foreach ($params['skuProps'] as $k => $v) {
                    $spec_id = $k.mt_rand(1,99).mt_rand(1,999);
                    $goods_spec_format[$k] = [
                        'spec_id' => $spec_id,
                        'spec_name' => $v['prop']
                    ];
                    foreach ($v['value'] as $key => $val) {
                        $goods_spec_format[$k]['value'][$key] = [
                            'spec_id' => $spec_id,
                            'spec_name' => $v['prop'],
                            'spec_value_id' => $key.mt_rand(1,999).mt_rand(1,99),
                            'spec_value_name' => $val['name'],
                            'image' => ''
                        ];
                    }
                }
            }
            //商品sku
            $goods_sku_data = [];
            if(empty($goods_spec_format)){//单规格
                $goods_spec_format = '';
                $price = explode('-',$params['displayPrice']);
                $goods_sku_data[] = [
                    'spec_name' => '',
                    'sku_no' => "",
                    'sku_spec_format' => '',
                    'price' => str_replace('￥','',$price[0]),
                    'market_price' => "",
                    'cost_price' => "",
                    'stock' => 0,
                    'stock_alarm' => 0,
                    'weight' => "",
                    'volume' => "",
                    'sku_image' => "",
                    'sku_images' => "",
                    'sku_images_arr' => [],
                    'is_default' => 0,
                    'propPath' => '',
                ];
            }else{//多规格

                foreach ($goods_spec_format as $k => $v) {

                    $item_prop_arr = [];
                    if(empty($goods_sku_data)){

                        foreach ($v['value'] as $key => $val) {
                            $item_prop_arr[] = [
                                'spec_name' => $val['spec_value_name'],
                                'sku_no' => "",
                                'sku_spec_format' => [$val],
                                'price' => "",
                                'market_price' => "",
                                'cost_price' => "",
                                'stock' => 0,
                                'stock_alarm' => 0,
                                'weight' => "",
                                'volume' => "",
                                'sku_image' => "",
                                'sku_images' => "",
                                'sku_images_arr' => [],
                                'is_default' => 0,
                                'propPath' => $val['spec_value_name'].'>',
                            ];
                        }
                    }else{
                        foreach($goods_sku_data as $key1 => $val1){

                            foreach($v['value'] as $key2 => $val2){


                                $sku_spec_format = $val1['sku_spec_format'];
                                $sku_spec_format[] = $val2;

                                $item_prop_arr[] = [

                                    'spec_name' => $val1['spec_name'].' '.$val2['spec_value_name'],
                                    'sku_no' => "",
                                    'sku_spec_format' => $sku_spec_format,
                                    'price' => "",
                                    'market_price' => "",
                                    'cost_price' => "",
                                    'stock' => 0,
                                    'stock_alarm' => "",
                                    'weight' => "",
                                    'volume' => "",
                                    'sku_image' => "",
                                    'sku_images' => "",
                                    'sku_images_arr' => [],
                                    'is_default' => 0,
                                    'propPath' => $val1['spec_name'].'>'.$val2['spec_value_name'].'>',
                                ];
                            }
                        }
                    }
                    $goods_sku_data = count($item_prop_arr) > 0 ? $item_prop_arr : $goods_sku_data;
                }
            }
            //获取商品价格
            foreach($goods_sku_data as $k=>$v){
                if(isset($params['skuMap'])){
                    if($params['skuMap']){
                        $propPath = substr($v['propPath'],0,-1);
                        $sku_info = $params['skuMap'][htmlspecialchars($propPath)];
                        if(isset($sku_info['discountPrice'])){
                            $price = $sku_info['discountPrice'];
                        }else{
                            $price = explode('-',$params['displayPrice']);
                            $price = str_replace('￥','',$price[0]);
                        }
                    }else{
                        $price = explode('-',$params['displayPrice']);
                        $price = str_replace('￥','',$price[0]);
                    }

                }else{
                    $price = explode('-',$params['displayPrice']);
                    $price = str_replace('￥','',$price[0]);
                }
                $goods_sku_data[$k]['price'] = $price;
                //$goods_sku_data[$k]['sku_image'] = $sku['image'] ?? '';
                unset($goods_sku_data[$k]['propPath']);
            }
            //商品属性
            $goods_attr_format = [];
            if(!empty($params['attributes'])){
                foreach($params['attributes'] as $k=>$v){

                    $val = explode(':',$v);
                    $goods_attr_format[] = [
                        'attr_class_id' => '-'. $k . mt_rand(1,99) . mt_rand(1,99),
                        'attr_id' => '-'. $k . mt_rand(1,99) . mt_rand(1,99),
                        'attr_name' => $val[0],
                        'attr_value_id' => '-'. $k . mt_rand(1,99) . mt_rand(1,99),
                        'attr_value_name' => $val[1],
                        'sort' => 0
                    ];
                }
            }
            $video_url = '';
            if(isset($params['videoInfo']) && isset($params['videoInfo']['videoUrl'])){
                $video_url = $params['videoInfo']['videoUrl'];
                $video_url = is_array($video_url) ? $video_url['android'] : $video_url;
            }
            $goods_data = [
                'goods_name' => $params['title'],// 商品名称,
                'goods_attr_class' => '',// 商品类型id,
                'goods_attr_name' => '',// 商品类型名称,
                'site_id' => $site_id,
                'category_id' => '',
                'category_json' => '',
                'goods_image' => $params['images'],// 商品主图路径
                'goods_content' => '',// 商品详情
                'goods_state' => 0,// 商品状态（1.正常0下架）
                'price' => $goods_sku_data[0]['price'],// 商品价格（取第一个sku）
                'market_price' => '',// 市场价格（取第一个sku）
                'cost_price' => '',// 成本价（取第一个sku）
                'sku_no' => '',// 商品sku编码
                'weight' => '',// 重量
                'volume' => '',// 体积
                'goods_stock' => 0,// 商品库存（总和）
                'goods_stock_alarm' => '',// 库存预警
                'is_free_shipping' => 1,// 是否免邮
                'shipping_template' => '',// 指定运费模板
                'goods_spec_format' => $goods_spec_format,// 商品规格格式
                'goods_attr_format' => $goods_attr_format,// 商品属性格式
                'introduction' => '',// 促销语
                'keywords' => '',// 关键词
                'unit' => $params['unit'] ?? '',// 单位
                'sort' => '',// 排序,
                'video_url' => $video_url,// 视频
                'goods_sku_data' => $goods_sku_data,// SKU商品数据
                'goods_service_ids' => '',// 商品服务id集合
                'label_id' => '',// 商品分组id
                'virtual_sale' => '',// 虚拟销量
                'max_buy' => '',// 限购
                'min_buy' => '',// 起售
                'recommend_way' => '', // 推荐方式，1：新品，2：精品，3；推荐
                'timer_on' => '',//定时上架
                'timer_off' => '',//定时下架
                'is_consume_discount' => ''//是否参与会员折扣
            ];

            //处理商品详情
            $desc = '';
            $temp_array = explode('src="',$params['desc']);
            foreach($temp_array as $k => $v){
                if($k > 0){
                    $position = strpos($v,'http');
                    if($position === false || $position !== 0){
                        $v = 'https:'.$v;
                    }
                    $desc .= 'src="'.$v;
                }else{
                    $desc .= $v;
                }
            }
            $goods_data['goods_content'] = $desc;

            $goods_data = $this->downloadResources($goods_data,$site_id);
            if($goods_data['code'] < 0){
                return $goods_data;
            }
            $goods_data = $goods_data['data'];

            $goods_data['goods_spec_format'] = empty($goods_data['goods_spec_format']) ? '' : json_encode($goods_data['goods_spec_format']);
            $goods_data['goods_attr_format'] = json_encode($goods_data['goods_attr_format']);
            $goods_data['goods_sku_data'] = json_encode($goods_data['goods_sku_data']);

            return $this->success($goods_data);
        }catch (\Exception $e){

            return $this->error('',$e->getMessage());
        }
    }


    /**
     * 下载资源（商品图片 + 商品视屏）
     * @param $goods_data
     */
    public function downloadResources($goods_data,$site_id)
    {
        try{

            //查询相册默认值
            $album_model = new Album();
            $album_info = $album_model->getAlbumInfo([['site_id','=',$site_id],['is_default','=',1]]);
            $album_info = $album_info['data'];

            $upload_model = new Upload($site_id);
            $path = "common/goods_grab/images/" . date("Ymd") . '/';

            //下载商品图片
            $goods_image = [];
            if(!empty($goods_data['goods_image'])){
                foreach($goods_data['goods_image'] as $v){
                    if(strpos($v,'http') !== false){
                        $goods_image_url = $v;
                    }else{
                        $goods_image_url = 'http:'.$v;
                    }

                    $goods_image_result = @$upload_model->setPath($path)->remoteGoodsPullToLocal(['img' => $goods_image_url, "thumb_type" => [ "big", "mid", "small" ], 'album_id' => $album_info['album_id']]);
                    if($goods_image_result['code'] > 0){
                        $goods_image[] = $goods_image_result['data']['pic_path'];
                    }
                }
                $goods_data['goods_image'] = implode(',',$goods_image);
            }

            //下载sku图片
            $goods_sku_data = $goods_data['goods_sku_data'];

            foreach($goods_sku_data as $k=>$v){

                if($v['sku_image'] != ''){
                    if(strpos($v['sku_image'],'http') !== false){
                        $sku_image = $v['sku_image'];
                    }else{
                        $sku_image = 'http:'.$v['sku_image'];
                    }

                    $sku_image_result = @$upload_model->setPath($path)->remoteGoodsPullToLocal(['img' => $sku_image, "thumb_type" => [ "big", "mid", "small" ], 'album_id' => $album_info['album_id']]);
                    if($sku_image_result['code'] > 0){
                        $goods_sku_data[$k]['sku_image'] = $sku_image_result['data']['pic_path'];
                        $goods_sku_data[$k]['sku_images'] = $sku_image_result['data']['pic_path'];
                    }
                }
            }
            $goods_data['goods_sku_data'] = $goods_sku_data;

            return $this->success($goods_data);
        }catch (\Exception $e){
            return $this->error('',$e->getMessage());
        }
    }


    /**
     * 商品数据采集
     * @param $url
     * @return array
     */
    public function httpGet($url)
    {
        // 模拟提交数据函数
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 对认证证书来源的检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器

        curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // 获取的信息以文件流的形式返回
        curl_setopt($curl, CURLOPT_HEADER, false); //开启header
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',
        )); //类型为json
        //类型为json
        $result = curl_exec($curl); // 执行操作
        if (curl_errno($curl)) {
            return $this->error('', '系统错误，请联系平台进行处理');
        }
        curl_close($curl); // 关键CURL会话

        $result = json_decode($result, true);
        if ($result['retcode'] != '0000') {
            return $this->error('', $result['message']);
        }
        return $this->success($result); // 返回数据
    }

    /************************************************ 商品数据处理 end ********************************************************/
}