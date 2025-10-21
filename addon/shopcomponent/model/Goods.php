<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace addon\shopcomponent\model;

use app\model\BaseModel;
use think\facade\Log;

class Goods extends BaseModel
{
    /**
     * 商品状态
     * @var string[]
     */
    private $status = [
        0 => '未上架',
        5 => '上架中',
        11 => '已下架',
        13 => '违规下架'
    ];

    /**
     * 审核状态
     * @var string[]
     */
    private $editStatus = [
        0 => '审核中',
        1 => '编辑中',
        2 => '审核中',
        3 => '审核失败',
        4 => '审核成功',
    ];

    /**
     * 获取商品列表
     * @param array $condition
     * @param bool $field
     * @param string $order
     * @param int $page
     * @param int $list_rows
     * @param string $alias
     * @param array $join
     * @return array
     */
    public function getGoodsPageList($condition = [], $field = true, $order = '', $page = 1, $list_rows = PAGE_LIST_ROWS, $alias = 'a', $join = [])
    {
        $field = 'sg.*,
        g.goods_name,g.goods_image,g.price,g.goods_stock,g.recommend_way';
        $alias = 'sg';
        $join = [
            ['goods g', 'g.goods_id = sg.out_product_id', 'inner'],
        ];
        $data = model('shopcompoent_goods')->pageList($condition, $field, $order, $page, $list_rows, $alias, $join);

        if (!empty($data['list'])) {
            foreach ($data['list'] as $k => $item) {
                $data['list'][$k]['status_name'] = $this->status[$item['status']] ?? '';
                $data['list'][$k]['edit_status_name'] = $this->editStatus[$item['edit_status']] ?? '';
                $arr_img = explode(',',$item['goods_image']);
                $data['list'][$k]['cover_img'] = $arr_img[0] ?? '';
                $data['list'][$k]['create_time'] = $item['create_time']>0 ? time_to_date($item['create_time']) : '--';
                $data['list'][$k]['audit_time'] = $item['audit_time']>0 ? time_to_date($item['audit_time']) : '--';
                $data['list'][$k]['reject_reason'] = str_replace('https://developers.weixin.qq.com/miniprogram/dev/framework/ministore/minishopopencomponent/API/spu/add_spu.html','',$item['reject_reason']);
            }
        }
        return $this->success($data);
    }

    /**
     * 同步商品库商品
     */
    public function syncGoods($start, $limit, $site_id, $status = 5)
    {
        $weapp = new Weapp();
        $sync_res = $weapp->getSpuPage(['page' => $start, 'page_size' => $limit, 'status' => $status]);
        if ($sync_res['code'] < 0) return $sync_res;

        if (!empty($sync_res['data']['list'])) {
            foreach ($sync_res['data']['list'] as $goods_item) {
                $count = model('shopcompoent_goods')->getCount([ ['out_product_id', '=', $goods_item['out_product_id'] ], ['site_id', '=', $site_id] ]);
                if ($count) {
                    model('shopcompoent_goods')->update([
                        'status' => $goods_item['status'],
                        'edit_status' => $goods_item['edit_status'],
                        'audit_time' => empty($goods_item['audit_info']) ? 0 : strtotime($goods_item['audit_info']['audit_time']),
                        'reject_reason' => $goods_item['edit_status'] == 3 ? $goods_item['audit_info']['reject_reason'] : ''
                    ], [
                        ['out_product_id', '=', $goods_item['out_product_id'] ]
                    ]);
                } else {
                    $category = (new Category())->getCategoryInfo($goods_item['third_cat_id'], $site_id);
                    model('shopcompoent_goods')->add([
                        'site_id' => $site_id,
                        'product_id' => $goods_item['product_id'],
                        'out_product_id' => $goods_item['out_product_id'],
                        'third_cat_id' => $goods_item['third_cat_id'],
                        'brand_id' => $goods_item['brand_id'],
                        'info_version' => $goods_item['info_version'],
                        'status' => $goods_item['status'],
                        'edit_status' => $goods_item['edit_status'],
                        'create_time' => strtotime($goods_item['create_time']),
                        'update_time' => strtotime($goods_item['update_time']),
                        'audit_time' => empty($goods_item['audit_info']) ? 0 : strtotime($goods_item['audit_info']['audit_time']),
                        'reject_reason' => $goods_item['edit_status'] == 3 ? $goods_item['audit_info']['reject_reason'] : '',
                        'cat_name' => "{$category['first_cat_name']}>{$category['second_cat_name']}>{$category['third_cat_name']}"
                    ]);
                }
            }
            $total_page = ceil($sync_res['data']['total'] / $limit);
            return $this->success(['page' => $start, 'total_page' => $total_page]);
        } else {
            return $this->success(['page' => $start, 'total_page' => 1]);
        }
    }

    /**
     * 添加商品
     * @param $param
     */
    public function addGoods($param)
    {
        $goods_list = model('goods')->getList([ ['goods_id', 'in', explode(',', $param['goods_ids'] )], ['site_id', '=', $param['site_id'] ] ], 'goods_id,goods_name,goods_image,sku_id,goods_content');
        if (!empty($goods_list)) {
            $category = (new Category())->getCategoryInfo($param['third_cat_id'], $param['site_id']);
            if (empty($category) || $category['status'] == 0) return $this->error('', '该类目不存在或未审核通过');

            $weapp = new Weapp();
            foreach ($goods_list as $goods_item) {
                // 需加到库中的商品数据
                $goods_data = [
                    'out_product_id' => $goods_item['goods_id'],
                    'third_cat_id' => $param['third_cat_id'],
                    'brand_id' => $param['brand_id'] ?? 2100000000,
                    'info_version' => '0.0.1',
                ];

                //处理图片
                $goods_image_arr = $this->handleImg($goods_item['goods_image']);
                $goods_image = [];
                foreach ($goods_image_arr as $img_k => $img_y){
                    $res = $weapp->getImg($img_y);
                    if($res['code'] == 0){
                        $goods_image[] = $res['data']['img_info']['temp_img_url'];
                    }
                }

                // 同步商品所需数据
                $spu_data = [
                    'title' => $goods_item['goods_name'],
                    'path' => 'pages/goods/detail/detail?sku_id=' . $goods_item['sku_id'],
                    'head_img' => $goods_image,
                    'qualification_pics' => $category['product_qualification_type'] == 0 ? '' : explode(',', $category['qualification_pics']),
                    'desc_info' => [
                        'desc' => $goods_item['goods_content']
                    ],
                    'skus' => []
                ];

                $sku_list = model('goods_sku')->getList([['goods_id', '=', $goods_item['goods_id']]], 'sku_id,sku_no,sku_image,discount_price,market_price,stock,sku_spec_format');
                foreach ($sku_list as $sku_item) {
                    //图片处理
                    $sku_res = $weapp->getImg($this->handleImg($sku_item['sku_image'])[0]);
                    $sku_image = "";
                    if($sku_res['code'] == 0){
                        $sku_image = $sku_res['data']['img_info']['temp_img_url'];
                    }

                    $sku_data = [
                        'out_product_id' => $goods_item['goods_id'],
                        'out_sku_id' => $sku_item['sku_id'],
                        'thumb_img' => $sku_image,
                        'sale_price' => $sku_item['discount_price'] * 100,
                        'market_price' => $sku_item['market_price'] * 100,
                        'stock_num' => $sku_item['stock'],
                        'sku_code' => $sku_item['sku_no'],
                        'sku_attrs' => []
                    ];
                    if (!empty($sku_item['sku_spec_format'])) {
                        foreach (json_decode($sku_item['sku_spec_format'], true) as $spec_item) {
                            array_push($sku_data['sku_attrs'], [
                                'attr_key' => $spec_item['spec_name'],
                                'attr_value' => $spec_item['spec_value_name']
                            ]);
                        }
                    } else {
                        $sku_data['sku_attrs'] = [
                            [
                                'attr_key' => '',
                                'attr_value' => ''
                            ]
                        ];
                    }
                    array_push($spu_data['skus'], $sku_data);
                }

                // 添加商品到小程序
                $add_res = $weapp->addSpu(array_merge($goods_data, $spu_data));
                if ($add_res['code'] != 0) return $add_res;

                $goods_data['product_id'] = $add_res['data']['product_id'];
                $goods_data['create_time'] = strtotime($add_res['data']['create_time']);
                $goods_data['site_id'] = $param['site_id'];
                $goods_data['cat_name'] = "{$category['first_cat_name']}>{$category['second_cat_name']}>{$category['third_cat_name']}";

                model('shopcompoent_goods')->add($goods_data);
            }
            return $this->success();
        } else {
            return $this->error('', '未获取到要添加的商品');
        }
    }

    /**
     * 更新商品
     * @param $param
     * @return array
     */
    public function updateGoods($param){
        $shopcompoent_goods_info = model('shopcompoent_goods')->getInfo([ ['out_product_id', '=', $param['goods_id'] ], ['site_id', '=', $param['site_id'] ] ], '*');
        $goods_info = model('goods')->getInfo([ ['goods_id', '=', $param['goods_id'] ], ['site_id', '=', $param['site_id'] ] ], 'goods_id,goods_name,goods_image,sku_id,goods_content');
        if (!empty($shopcompoent_goods_info) && !empty($goods_info)) {

            $third_cat_id = $param['third_cat_id'] ?? $shopcompoent_goods_info['third_cat_id'];
            $category = (new Category())->getCategoryInfo($third_cat_id, $param['site_id']);
            if (empty($category) || $category['status'] == 0) return $this->error('', '该类目不存在或未审核通过');

            $weapp = new Weapp();
            //处理图片
            $goods_image_arr = $this->handleImg($goods_info['goods_image']);
            $goods_image = [];
            foreach ($goods_image_arr as $img_k => $img_y){
                $res = $weapp->getImg($img_y);
                if($res['code'] == 0){
                    $goods_image[] = $res['data']['img_info']['temp_img_url'];
                }
            }


            // 同步商品所需数据
            $spu_data = [
                'out_product_id' => $goods_info['goods_id'],
                'third_cat_id' => $third_cat_id,
                'brand_id' => $param['brand_id'] ?? $shopcompoent_goods_info['brand_id'],
                'info_version' => '0.0.1',
                'title' => $goods_info['goods_name'],
                'path' => 'pages/goods/detail/detail?sku_id=' . $goods_info['sku_id'],
                'head_img' => $goods_image,
                'qualification_pics' => $category['product_qualification_type'] == 0 ? '' : explode(',', $category['qualification_pics']),
                'desc_info' => [
                    'desc' => $goods_info['goods_content']
                ],
                'skus' => []
            ];

            $sku_list = model('goods_sku')->getList([['goods_id', '=', $goods_info['goods_id']]], 'sku_id,sku_no,sku_image,discount_price,market_price,stock,sku_spec_format');
            foreach ($sku_list as $sku_item) {
                //图片处理
                $sku_res = $weapp->getImg($this->handleImg($sku_item['sku_image'])[0]);
                $sku_image = "";
                if($sku_res['code'] == 0){
                    $sku_image = $sku_res['data']['img_info']['temp_img_url'];
                }
                $sku_data = [
                    'out_product_id' => $goods_info['goods_id'],
                    'out_sku_id' => $sku_item['sku_id'],
                    'thumb_img' => $sku_image,
                    'sale_price' => $sku_item['discount_price'] * 100,
                    'market_price' => $sku_item['market_price'] * 100,
                    'stock_num' => $sku_item['stock'],
                    'sku_code' => $sku_item['sku_no'],
                    'sku_attrs' => []
                ];
                if (!empty($sku_item['sku_spec_format'])) {
                    foreach (json_decode($sku_item['sku_spec_format'], true) as $spec_item) {
                        array_push($sku_data['sku_attrs'], [
                            'attr_key' => $spec_item['spec_name'],
                            'attr_value' => $spec_item['spec_value_name']
                        ]);
                    }
                } else {
                    $sku_data['sku_attrs'] = [
                        [
                            'attr_key' => '',
                            'attr_value' => ''
                        ]
                    ];
                }
                array_push($spu_data['skus'], $sku_data);
            }

            // 更新商品
            $update_res = $weapp->updateSpu($spu_data);
            if ($update_res['code'] != 0) return $update_res;

            $goods_data = [
                'edit_status' => 0,
                'update_time' => time(),
                'third_cat_id' => $third_cat_id,
                'cat_name' => "{$category['first_cat_name']}>{$category['second_cat_name']}>{$category['third_cat_name']}"
            ];

            model('shopcompoent_goods')->update($goods_data, [ ['out_product_id', '=', $param['goods_id'] ], ['site_id', '=', $param['site_id'] ] ]);

            return $this->success();
        } else {
            return $this->error('', '未获取到要更新的商品');
        }
    }

    /**
     * 处理图片
     * @param $images
     * @return false|string[]
     */
    private function handleImg($images){
        $img_arr = explode(',', $images);
        foreach ($img_arr as $k => $v) {
            $img_arr[$k] = img($v);
        }
        return $img_arr;
    }

    /**
     * 删除商品
     * @param $id
     * @param $site_id
     */
    public function deleteGoods($goods_ids, $site_id)
    {
        if(!empty($goods_ids)){
            $array_goodsIds = explode(',',$goods_ids);
        }

        foreach($array_goodsIds as $k=>$goods_id){
            $res[$k] = (new Weapp())->delSpu(['out_product_id' => $goods_id]);
            if ($res[$k]['code'] != 0) return $res[$k];
        }

        model('shopcompoent_goods')->delete([ ['site_id', '=', $site_id], ['out_product_id','in', $goods_ids] ]);

        return $this->success();
    }

    /**
     * 商品上架
     * @param $goods_id
     * @param $site_id
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function goodsListing($goods_ids, $site_id){
        if(!empty($goods_ids)){
            $array_goodsIds = explode(',',$goods_ids);
        }

        foreach($array_goodsIds as $k=>$goods_id) {
            $res[$k] = (new Weapp())->listing(['out_product_id' => $goods_id]);
            if ($res[$k]['code'] != 0) return $res[$k];
        }
        model('shopcompoent_goods')->update(['status' => 5], [ ['site_id', '=', $site_id], ['out_product_id','in', $goods_ids] ]);
        return $this->success();
    }

    /**
     * 商品下架
     * @param $goods_id
     * @param $site_id
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function goodsDelisting($goods_ids, $site_id){
        if(!empty($goods_ids)){
            $array_goodsIds = explode(',',$goods_ids);
        }
    
        foreach($array_goodsIds as $k=>$goods_id) {
            $res[$k] = (new Weapp())->delisting(['out_product_id' => $goods_id]);
            if ($res[$k]['code'] != 0) return $res[$k];
        }
        model('shopcompoent_goods')->update(['status' => 11], [ ['site_id', '=', $site_id], ['out_product_id','in', $goods_ids] ]);
        return $this->success();
    }

    /**
     * @param $site_id
     */
    public function getOrderPayInfo($site_id){
        $join = [
            ['goods g', 'g.goods_id = sg.out_product_id', 'left']
        ];
        $condition = [
            ['sg.status', '=', 5],
            ['sg.edit_status', '=', 4],
            ['g.is_delete', '=', 0],
            ['g.goods_state', '=', 1]
        ];
        $goods_info = model('shopcompoent_goods')->getInfo($condition, 'g.goods_name,g.price,g.goods_id,g.sku_id', 'sg', $join);
        if (empty($goods_info)) return $this->error('', '请先将商品同步到微信侧，等待商品审核通过并已上架');

        $qrcode_res = event('Qrcode', [
            'site_id' => $site_id,
            'app_type' => 'weapp',
            'type' => 'create',
            'data' => [
                'sku_id' => $goods_info['sku_id'],
                'is_test' => 1
            ],
            'page' => '/pages/goods/detail/detail',
            'qrcode_path' => 'upload/qrcode/goods',
            'qrcode_name' => "goods_qrcode_" . $goods_info['sku_id']
        ], true);
        if ($qrcode_res['code'] != 0) return $qrcode_res;

        $goods_info['qrcode_path'] = $qrcode_res['data']['path'];

        return $this->success($goods_info);
    }
}