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

namespace app\model\goods;

use addon\discount\model\Discount;
use app\model\BaseModel;
use app\model\order\OrderCommon;
use app\model\order\OrderRefund;
use app\model\system\Config as ConfigModel;
use app\model\system\Cron;
use app\model\web\WebSite as WebsiteModel;
use app\model\system\Stat;
use app\model\web\Config as WebConfig;

/**
 * 商品
 */
class Goods extends BaseModel
{

    private $goods_class = array ( 'id' => 1, 'name' => '实物商品' );

    private $goods_state = array (
        1 => '销售中',
        0 => '仓库中'
    );

    private $verify_state = array (
        1 => '已审核',
        0 => '待审核',
        -2 => '审核失败',
        10 => '违规下架'
    );

    public function getGoodsState()
    {
        return $this->goods_state;
    }

    public function getVerifyState()
    {
        return $this->verify_state;
    }

    /**
     * 商品添加
     * @param $data
     */
    public function addGoods($data)
    {

        model('goods')->startTrans();
        try {
            //店铺信息
            $shop_info = model('shop')->getInfo([ [ 'site_id', '=', $data[ 'site_id' ] ] ], 'site_name, website_id, is_own,cert_id,shop_status');
            $goods_config = new Config();
            $goods_verify_config = $goods_config->getVerifyConfig();
            $goods_verify_config = $goods_verify_config[ 'data' ][ 'value' ];
            $verify_state = 1;
            if (!empty($goods_verify_config[ 'is_open' ])) {
                $verify_state = 0;//开启商品审核后，审核状态为：待审核
            }
            // 店铺未认证、审核中的状态下，商品需要审核
            if (empty($shop_info[ 'cert_id' ]) || $shop_info[ 'shop_status' ] == 0 || $shop_info[ 'shop_status' ] == 2) {
                $verify_state = 0;//开启商品审核后，审核状态为：待审核
            }


            //SKU商品数据(图片数据)
            $goods_image = $data[ 'goods_image' ];
            $first_image = explode(",", $goods_image)[ 0 ];
            if (!empty($data[ 'goods_sku_data' ])) {
                $data[ 'goods_sku_data' ] = json_decode($data[ 'goods_sku_data' ], true);
                if (empty($goods_image)) {
                    $goods_image = $data[ 'goods_sku_data' ][ 0 ][ 'sku_image' ];
                }
            }

            //规格排序
            if (!empty($data[ 'goods_attr_format' ])) {
                $goods_attr_format = json_decode($data[ 'goods_attr_format' ], true);
                $keys = array_column($goods_attr_format, 'sort');
                if (!empty($keys)) {
                    array_multisort($keys, SORT_ASC, SORT_NUMERIC, $goods_attr_format);
                    $data[ 'goods_attr_format' ] = json_encode($goods_attr_format);
                }
            }

            //todo  商品的分佣比率是通过选择的分类来计算的
            $goods_category_model = new GoodsCategory();
            $category_array = $data[ 'category_array' ][ 0 ] ?? [];
            $category_array = explode(',', $category_array);
            $last_category_id = end($category_array);
            $goods_category_info = $goods_category_model->getCategoryInfo([ [ 'category_id', '=', $last_category_id ] ], 'commission_rate, category_full_name')[ 'data' ] ?? [];
            if (empty($goods_category_info)) {
                model('goods')->rollback();
                return $this->error([], '您选择的分类不存在');
            }
            $commission_rate = $goods_category_info[ 'commission_rate' ] ?? 0;
            $category_name = $goods_category_info[ 'category_full_name' ] ?? '';

            $goods_data = array (
                'goods_image' => $goods_image,
                'goods_stock' => $data[ 'goods_stock' ],
                'price' => $data[ 'goods_sku_data' ][ 0 ][ 'price' ],
                'market_price' => $data[ 'goods_sku_data' ][ 0 ][ 'market_price' ],
                'cost_price' => $data[ 'goods_sku_data' ][ 0 ][ 'cost_price' ],
                'goods_spec_format' => $data[ 'goods_spec_format' ],
                'category_id' => $data[ 'category_id' ],//分类id
                'category_json' => $data[ 'category_json' ],//分类json
                'timer_on' => $data[ 'timer_on' ] ?? 0,//定时上线
                'timer_off' => $data[ 'timer_off' ] ?? 0,//定时下线
                'commission_rate' => $commission_rate,
                'is_consume_discount' => $data['is_consume_discount'] ?? 0,
                // Turntable新增字段（仅goods表持有）
                'supply_price' => $data['supply_price'] ?? 0,
                'lottery_participation' => $data['lottery_participation'] ?? 0,
                'lottery_tier_ids' => $data['lottery_tier_ids'] ?? null,
                'source_type' => $data['source_type'] ?? 'merchant',
            );

            $goods_data[ 'category_name' ] = $category_name;

            $common_data = array (
                'goods_name' => $data[ 'goods_name' ],
                'goods_class' => $this->goods_class[ 'id' ],
                'goods_class_name' => $this->goods_class[ 'name' ],
                'goods_attr_class' => $data[ 'goods_attr_class' ],
                'goods_attr_name' => $data[ 'goods_attr_name' ],
                'site_id' => $data[ 'site_id' ],
                'site_name' => $shop_info[ 'site_name' ],
                'website_id' => $shop_info[ 'website_id' ],
                'brand_id' => $data[ 'brand_id' ] ?? 0,
                'brand_name' => $data[ 'brand_name' ] ?? '',
                'goods_content' => $data[ 'goods_content' ],
                'is_own' => $shop_info[ 'is_own' ],
                'goods_state' => $data[ 'goods_state' ],
                'verify_state' => $verify_state,
                'goods_stock_alarm' => $data[ 'goods_stock_alarm' ],
                'is_free_shipping' => $data[ 'is_free_shipping' ],
                'shipping_template' => $data[ 'shipping_template' ],
                'goods_attr_format' => $data[ 'goods_attr_format' ],
                'introduction' => $data[ 'introduction' ],
                'keywords' => $data[ 'keywords' ],
                'unit' => $data[ 'unit' ],
                'video_url' => $data[ 'video_url' ],
//              'sort' => $data['sort'],
                'max_buy' => $data[ 'max_buy' ],
                'min_buy' => $data[ 'min_buy' ],
                'create_time' => time(),


                'goods_shop_category_ids' => $data[ 'goods_shop_category_ids' ] ?? '',
                'supplier_id' => $data[ 'supplier_id' ] ?? 0,

            );

            $goods_id = model('goods')->add(array_merge($goods_data, $common_data));

            $goods_stock = 0;
            $sku_arr = array ();
            //添加sku商品
            foreach ($data[ 'goods_sku_data' ] as $item) {
                $goods_stock += $item[ 'stock' ];
                $sku_data = array (
                    'sku_name' => $data[ 'goods_name' ] . ' ' . $item[ 'spec_name' ],
                    'spec_name' => $item[ 'spec_name' ],
                    'sku_no' => $item[ 'sku_no' ],
                    'sku_spec_format' => !empty($item[ 'sku_spec_format' ]) ? json_encode($item[ 'sku_spec_format' ]) : "",
                    'price' => $item[ 'price' ],
                    'market_price' => $item[ 'market_price' ],
                    'cost_price' => $item[ 'cost_price' ],
                    'discount_price' => $item[ 'price' ],//sku折扣价（默认等于单价）
                    'stock' => $item[ 'stock' ],
                    'weight' => $item[ 'weight' ],
                    'volume' => $item[ 'volume' ],
                    'sku_image' => !empty($item[ 'sku_image' ]) ? $item[ 'sku_image' ] : $first_image,
                    'sku_images' => $item[ 'sku_images' ],
                    'goods_id' => $goods_id,
                    'is_default' => $item[ 'is_default' ] ?? 0,
                    'stock_alarm' => $item[ 'stock_alarm' ],
                    'is_consume_discount' => $data['is_consume_discount'] ?? 0,
                );

                $sku_arr[] = array_merge($sku_data, $common_data);
            }
            model('goods_sku')->addList($sku_arr);

            // 赋值第一个商品sku_id
            $first_info = model('goods_sku')->getFirstData([ 'goods_id' => $goods_id ], 'sku_id', 'is_default desc,sku_id asc');
            model('goods')->update([ 'sku_id' => $first_info[ 'sku_id' ], 'goods_stock' => $goods_stock ], [ [ 'goods_id', '=', $goods_id ] ]);

            //添加商品属性关联关系
            $this->refreshGoodsAttribute($goods_id, $data[ 'goods_attr_format' ]);

            if (!empty($data[ 'goods_spec_format' ])) {
                //刷新SKU商品规格项/规格值JSON字符串
                $this->dealGoodsSkuSpecFormat($goods_id, $data[ 'goods_spec_format' ]);
            }

            $cron = new Cron();
            //定时上下架
            if ($goods_data[ 'timer_on' ] > 0) {
                $cron->addCron(1, 0, "商品定时上架", "CronGoodsTimerOn", $goods_data[ 'timer_on' ], $goods_id);
            }
            if ($goods_data[ 'timer_off' ] > 0) {
                $cron->addCron(1, 0, "商品定时下架", "CronGoodsTimerOff", $goods_data[ 'timer_off' ], $goods_id);
            }

            //添加店铺添加统计
            //添加统计
            $stat = new Stat();
            $stat->addShopStat([ 'add_goods_count' => 1, 'site_id' => $data[ 'site_id' ] ]);

            // 处理抽奖价池绑定（lottery_goods_tier）
            if (function_exists('addon_is_exit') && addon_is_exit('turntable')) {
                try {
                    if (!empty($data['lottery_tier_ids'])) {
                        $tier_ids = $data['lottery_tier_ids'];
                        if (is_string($tier_ids)) {
                            $tier_ids = json_decode($tier_ids, true) ?: [];
                        }
                        if (!empty($tier_ids)) {
                            \think\facade\Db::name('lottery_goods_tier')->where([
                                ['site_id', '=', $data['site_id']],
                                ['goods_id', '=', $goods_id]
                            ])->delete();
                            \think\facade\Db::name('lottery_goods_tier')->insert([
                                'site_id' => $data['site_id'],
                                'goods_id' => $goods_id,
                                'tier_ids' => json_encode(array_values($tier_ids)),
                                'create_time' => time(),
                            ]);
                        }
                    }
                } catch (\Throwable $e) {
                    // 忽略绑定异常
                }
            }
            model('goods')->commit();

            return $this->success($goods_id);
        } catch (\Exception $e) {
            model('goods')->rollback();
            return $this->error($e->getMessage() . "，File：" . $e->getFile() . "，Line：" . $e->getLine());
        }
    }

    /**
     * 商品编辑
     * @param $data
     */
    public function editGoods($data)
    {

        model('goods')->startTrans();
        try {
            //店铺信息
            $shop_info = model('shop')->getInfo([ [ 'site_id', '=', $data[ 'site_id' ] ] ], 'site_name, website_id, is_own,cert_id,shop_status');

            $goods_config = new Config();
            $goods_verify_config = $goods_config->getVerifyConfig();
            $goods_verify_config = $goods_verify_config[ 'data' ][ 'value' ];
            $verify_state = 1;
            if (!empty($goods_verify_config[ 'is_open' ])) {
                $verify_state = 0;//开启商品审核后，审核状态为：待审核
            }

            // 店铺未认证、审核中的状态下，商品需要审核
            if (empty($shop_info[ 'cert_id' ]) || $shop_info[ 'shop_status' ] == 0 || $shop_info[ 'shop_status' ] == 2) {
                $verify_state = 0;//开启商品审核后，审核状态为：待审核
            }

            if (!empty($data[ 'goods_attr_format' ])) {

                $goods_attr_format = json_decode($data[ 'goods_attr_format' ], true);
                $keys = array_column($goods_attr_format, 'sort');
                if (!empty($keys)) {
                    array_multisort($keys, SORT_ASC, SORT_NUMERIC, $goods_attr_format);
                    $data[ 'goods_attr_format' ] = json_encode($goods_attr_format);
                }
            }
            $goods_id = $data[ 'goods_id' ];
            $goods_image = $data[ 'goods_image' ];
            $first_image = explode(",", $goods_image)[ 0 ];

            //SKU商品数据
            if (!empty($data[ 'goods_sku_data' ])) {
                $data[ 'goods_sku_data' ] = json_decode($data[ 'goods_sku_data' ], true);
//                if (empty($goods_image)) {
//                    $goods_image = $data[ 'goods_sku_data' ][ 0 ][ 'sku_image' ];
//                }
            }

            //todo  商品的分佣比率是通过选择的分类来计算的
            $goods_category_model = new GoodsCategory();
            $category_array = $data[ 'category_array' ];
            $last_category_id = end($category_array);

            $goods_category_info = $goods_category_model->getCategoryInfo([ [ 'category_id', '=', $last_category_id ] ], 'commission_rate, category_full_name')[ 'data' ] ?? [];
            if (empty($goods_category_info)) {
                model('goods')->rollback();
                return $this->error([], '您选择的分类不存在');
            }
            $commission_rate = $goods_category_info[ 'commission_rate' ] ?? 0;
            $category_name = $goods_category_info[ 'category_full_name' ] ?? '';

            $goods_data = array (
                'goods_image' => $goods_image,
                'goods_stock' => $data[ 'goods_stock' ],
                'price' => $data[ 'goods_sku_data' ][ 0 ][ 'price' ],
                'market_price' => $data[ 'goods_sku_data' ][ 0 ][ 'market_price' ],
                'cost_price' => $data[ 'goods_sku_data' ][ 0 ][ 'cost_price' ],
                'goods_spec_format' => $data[ 'goods_spec_format' ],
                'category_id' => $data[ 'category_id' ],
                'category_json' => $data[ 'category_json' ],
                'timer_on' => $data[ 'timer_on' ],
                'timer_off' => $data[ 'timer_off' ],
                'commission_rate' => $commission_rate,//佣金比率
                'is_consume_discount' => $data['is_consume_discount'] ?? 0,
                // Turntable新增字段（仅goods表持有）
                'supply_price' => $data['supply_price'] ?? 0,
                'lottery_participation' => $data['lottery_participation'] ?? 0,
                'lottery_tier_ids' => $data['lottery_tier_ids'] ?? null,
                'source_type' => $data['source_type'] ?? 'merchant',
            );

            $goods_data[ 'category_name' ] = $category_name;
            $common_data = array (
                'goods_name' => $data[ 'goods_name' ],
                'goods_class' => $this->goods_class[ 'id' ],
                'goods_class_name' => $this->goods_class[ 'name' ],
                'goods_attr_class' => $data[ 'goods_attr_class' ],
                'goods_attr_name' => $data[ 'goods_attr_name' ],
                'site_id' => $data[ 'site_id' ],
                'goods_content' => $data[ 'goods_content' ],
                'goods_state' => $data[ 'goods_state' ],
                'goods_stock_alarm' => $data[ 'goods_stock_alarm' ],
                'is_free_shipping' => $data[ 'is_free_shipping' ],
                'shipping_template' => $data[ 'shipping_template' ],
                'goods_attr_format' => $data[ 'goods_attr_format' ],
                'introduction' => $data[ 'introduction' ],
                'keywords' => $data[ 'keywords' ],
                'unit' => $data[ 'unit' ],
                'video_url' => $data[ 'video_url' ],
//                'sort' => $data['sort'],
                'modify_time' => time(),
                'max_buy' => $data[ 'max_buy' ],
                'min_buy' => $data[ 'min_buy' ],
                'is_own' => $shop_info[ 'is_own' ],
                'brand_id' => $data[ 'brand_id' ] ?? 0,
                'brand_name' => $data[ 'brand_name' ] ?? '',
                'verify_state' => $verify_state,
                'goods_shop_category_ids' => $data[ 'goods_shop_category_ids' ] ?? '',
                'supplier_id' => $data[ 'supplier_id' ] ?? 0,
                'site_name' => $shop_info[ 'site_name' ],
                'website_id' => $shop_info[ 'website_id' ],
                'is_consume_discount' => $data['is_consume_discount'] ?? 0,
            );

            model('goods')->update(array_merge($goods_data, $common_data), [ [ 'goods_id', '=', $goods_id ], [ 'goods_class', '=', $this->goods_class[ 'id' ] ] ]);

            $goods_stock = 0;

            // 如果只编辑价格库存就是修改，如果添加规格项/值就需要重新生成
            if (!empty($data[ 'goods_sku_data' ][ 0 ][ 'sku_id' ])) {
                if ($data[ 'spec_type_status' ] == 1) {
                    model('goods_sku')->delete([ [ 'goods_id', '=', $goods_id ] ]);

                    $sku_arr = array ();
                    //添加sku商品
                    foreach ($data[ 'goods_sku_data' ] as $item) {

                        $goods_stock += $item[ 'stock' ];

                        $sku_data = array (
                            'sku_name' => $data[ 'goods_name' ] . ' ' . $item[ 'spec_name' ],
                            'spec_name' => $item[ 'spec_name' ],
                            'sku_no' => $item[ 'sku_no' ],
                            'sku_spec_format' => !empty($item[ 'sku_spec_format' ]) ? json_encode($item[ 'sku_spec_format' ]) : "",
                            'price' => $item[ 'price' ],
                            'market_price' => $item[ 'market_price' ],
                            'cost_price' => $item[ 'cost_price' ],
                            'discount_price' => $item[ 'price' ],//sku折扣价（默认等于单价）
                            'stock' => $item[ 'stock' ],
                            'stock_alarm' => $item[ 'stock_alarm' ],
                            'weight' => $item[ 'weight' ],
                            'volume' => $item[ 'volume' ],
                            'sku_image' => !empty($item[ 'sku_image' ]) ? $item[ 'sku_image' ] : $first_image,
                            'sku_images' => $item[ 'sku_images' ],
                            'goods_id' => $goods_id,
                            'is_default' => $item[ 'is_default' ] ?? 0,
                            'is_consume_discount' => $data['is_consume_discount'] ?? 0,
                        );
                        $sku_arr[] = array_merge($sku_data, $common_data);
                    }
                    model('goods_sku')->addList($sku_arr);
                } else {
                    $sku_id_arr = [];
                    $discount_model = new Discount();
                    foreach ($data[ 'goods_sku_data' ] as $item) {
                        $discount_info = [];

                        if (!empty($item[ 'sku_id' ])) {
                            $discount_info_result = $discount_model->getDiscountGoodsInfo([ [ 'pdg.sku_id', '=', $item[ 'sku_id' ] ], [ 'pd.status', '=', 1 ] ], 'id');
                            $discount_info = $discount_info_result[ 'data' ];

                        }

                        $goods_stock += $item[ 'stock' ];

                        $sku_data = array (
                            'sku_name' => $data[ 'goods_name' ] . ' ' . $item[ 'spec_name' ],
                            'spec_name' => $item[ 'spec_name' ],
                            'sku_no' => $item[ 'sku_no' ],
                            'sku_spec_format' => !empty($item[ 'sku_spec_format' ]) ? json_encode($item[ 'sku_spec_format' ]) : "",
                            'price' => $item[ 'price' ],
                            'market_price' => $item[ 'market_price' ],
                            'cost_price' => $item[ 'cost_price' ],
                            'stock' => $item[ 'stock' ],
                            'stock_alarm' => $item[ 'stock_alarm' ],
                            'weight' => $item[ 'weight' ],
                            'volume' => $item[ 'volume' ],
                            'sku_image' => !empty($item[ 'sku_image' ]) ? $item[ 'sku_image' ] : $first_image,
                            'sku_images' => $item[ 'sku_images' ],
                            'goods_id' => $goods_id,
                            'is_default' => $item[ 'is_default' ] ?? 0,
                            'is_consume_discount' => $data['is_consume_discount'] ?? 0,
                        );
                        if (empty($discount_info)) {
                            $sku_data[ 'discount_price' ] = $item[ 'price' ];
                        }

                        //移除已经不存在的商品sku todo  存在部分sku是原来的,部分sku是新增的情况
                        if (!empty($item[ 'sku_id' ])) {
                            $sku_id_arr[] = $item[ 'sku_id' ];
                            model('goods_sku')->update(array_merge($sku_data, $common_data), [ [ 'sku_id', '=', $item[ 'sku_id' ] ], [ 'goods_class', '=', $this->goods_class[ 'id' ] ] ]);
                        } else {
                            $sku_id = model('goods_sku')->add(array_merge($sku_data, $common_data));
                            $sku_id_arr[] = $sku_id;
                        }
                    }

                    // 移除不存在的商品SKU
                    $sku_id_list = model('goods_sku')->getList([ [ 'goods_id', '=', $goods_id ] ], 'sku_id');
                    $sku_id_list = array_column($sku_id_list, 'sku_id');
                    foreach ($sku_id_list as $k => $v) {
                        foreach ($sku_id_arr as $ck => $cv) {
                            if ($v == $cv) {
                                unset($sku_id_list[ $k ]);
                            }
                        }
                    }
                    $sku_id_list = array_values($sku_id_list);
                    if (!empty($sku_id_list)) {
                        model('goods_sku')->delete([ [ 'sku_id', 'in', implode(",", $sku_id_list) ] ]);
                    }
                }

            } else {

                model('goods_sku')->delete([ [ 'goods_id', '=', $goods_id ] ]);

                $sku_arr = array ();
                //添加sku商品
                foreach ($data[ 'goods_sku_data' ] as $item) {

                    $goods_stock += $item[ 'stock' ];

                    $sku_data = array (
                        'sku_name' => $data[ 'goods_name' ] . ' ' . $item[ 'spec_name' ],
                        'spec_name' => $item[ 'spec_name' ],
                        'sku_no' => $item[ 'sku_no' ],
                        'sku_spec_format' => !empty($item[ 'sku_spec_format' ]) ? json_encode($item[ 'sku_spec_format' ]) : "",
                        'price' => $item[ 'price' ],
                        'market_price' => $item[ 'market_price' ],
                        'cost_price' => $item[ 'cost_price' ],
                        'discount_price' => $item[ 'price' ],//sku折扣价（默认等于单价）
                        'stock' => $item[ 'stock' ],
                        'stock_alarm' => $item[ 'stock_alarm' ],
                        'weight' => $item[ 'weight' ],
                        'volume' => $item[ 'volume' ],
                        'sku_image' => !empty($item[ 'sku_image' ]) ? $item[ 'sku_image' ] : $first_image,
                        'sku_images' => $item[ 'sku_images' ],
                        'goods_id' => $goods_id,
                        'is_default' => $item[ 'is_default' ] ?? 0,
                        'is_consume_discount' => $data['is_consume_discount'] ?? 0,
                    );

                    $sku_arr[] = array_merge($sku_data, $common_data);
                }

                model('goods_sku')->addList($sku_arr);
            }

            // 赋值第一个商品sku_id
            $first_info = model('goods_sku')->getFirstData([ 'goods_id' => $goods_id ], 'sku_id', 'is_default desc,sku_id asc');
            model('goods')->update([ 'sku_id' => $first_info[ 'sku_id' ], 'goods_stock' => $goods_stock ], [ [ 'goods_id', '=', $goods_id ] ]);

            //添加商品属性关联关系
            $this->refreshGoodsAttribute($goods_id, $data[ 'goods_attr_format' ]);
            if (!empty($data[ 'goods_spec_format' ])) {
                //刷新SKU商品规格项/规格值JSON字符串
                $this->dealGoodsSkuSpecFormat($goods_id, $data[ 'goods_spec_format' ]);
            }


            $cron = new Cron();
            //定时上下架
            if ($goods_data[ 'timer_on' ] > 0) {
                $cron->deleteCron([ [ 'event', '=', 'CronGoodsTimerOn' ], [ 'relate_id', '=', $goods_id ] ]);
                $cron->addCron(1, 0, "商品定时上架", "CronGoodsTimerOn", $goods_data[ 'timer_on' ], $goods_id);
            }
            if ($goods_data[ 'timer_off' ] > 0) {
                $cron->deleteCron([ [ 'event', '=', 'CronGoodsTimerOff' ], [ 'relate_id', '=', $goods_id ] ]);
                $cron->addCron(1, 0, "商品定时下架", "CronGoodsTimerOff", $goods_data[ 'timer_off' ], $goods_id);
            }

            event('GoodsEdit', [ 'goods_id' => $goods_id, 'site_id' => $data[ 'site_id' ] ]);
            // 同步抽奖价池绑定（lottery_goods_tier）
            if (function_exists('addon_is_exit') && addon_is_exit('turntable')) {
                try {
                    // 清除后重建（避免残留）
                    \think\facade\Db::name('lottery_goods_tier')->where([
                        ['site_id', '=', $data['site_id']],
                        ['goods_id', '=', $goods_id]
                    ])->delete();
                    if (!empty($data['lottery_tier_ids'])) {
                        $tier_ids = $data['lottery_tier_ids'];
                        if (is_string($tier_ids)) {
                            $tier_ids = json_decode($tier_ids, true) ?: [];
                        }
                        if (!empty($tier_ids)) {
                            \think\facade\Db::name('lottery_goods_tier')->insert([
                                'site_id' => $data['site_id'],
                                'goods_id' => $goods_id,
                                'tier_ids' => json_encode(array_values($tier_ids)),
                                'create_time' => time(),
                            ]);
                        }
                    }
                } catch (\Throwable $e) { }
            }
            model('goods')->commit();
            return $this->success($goods_id);
        } catch (\Exception $e) {
            model('goods')->rollback();
            return $this->error($e->getMessage() . "，File：" . $e->getFile() . "，Line：" . $e->getLine());
        }
    }


    /**
     * 获取商品信息
     * @param array $condition
     * @param string $field
     */
    public function editGetGoodsInfo($condition, $field = '*')
    {
        $info = model('goods')->getInfo($condition, $field);
        $category_json = json_decode($info[ 'category_json' ]);

        $goods_category = [];
        foreach ($category_json as $k => $v) {
            if (!empty($v)) {
                $category_name = model('goods_category')->getColumn([ [ 'category_id', 'in', (string) $v ] ], 'category_name');
                $category_name = implode('/', $category_name);
                $goods_category[ $k ] = [
                    'id' => $v,
                    'category_name' => $category_name
                ];
            }
        }
        $info[ 'goods_category' ] = $goods_category;
        return $this->success($info);
    }

    /**
     * 平台修改商品权重
     * @param $sort
     * @param $goods_id
     * @return array
     */
    public function modifyGoodsSort($sort, $goods_id)
    {
        model('goods')->update([ 'sort' => $sort ], [ [ 'goods_id', '=', $goods_id ] ]);
        model('goods_sku')->update([ 'sort' => $sort ], [ [ 'goods_id', '=', $goods_id ] ]);
        return $this->success(1);
    }

    /**
     * 修改商品状态
     * @param $goods_ids
     * @param $goods_state
     * @param $site_id
     * @return \multitype
     */
    public function modifyGoodsState($goods_ids, $goods_state, $site_id)
    {
        model('goods')->update([ 'goods_state' => $goods_state ], [ [ 'goods_id', 'in', (string) $goods_ids ], [ 'site_id', '=', $site_id ] ]);
        model('goods_sku')->update([ 'goods_state' => $goods_state ], [ [ 'goods_id', 'in', (string) $goods_ids ], [ 'site_id', '=', $site_id ] ]);
        return $this->success(1);
    }

    /**
     * 修改审核状态
     * @param $goods_ids
     * @param $verify_state
     * @param $verify_state_remark
     * @return \multitype
     */
    public function modifyVerifyState($goods_ids, $verify_state, $verify_state_remark, $website_id = 0)
    {
        $condition = [ [ 'goods_id', 'in', $goods_ids ] ];
        if ($website_id > 0) {
            $condition[] = [ 'website_id', '=', $website_id ];
        }
        model('goods')->update([ 'verify_state' => $verify_state, 'verify_state_remark' => $verify_state_remark ], $condition);
        model('goods_sku')->update([ 'verify_state' => $verify_state, 'verify_state_remark' => $verify_state_remark ], $condition);
        return $this->success(1);
    }

    /**
     * 修改删除状态
     * @param $goods_ids
     * @param $is_delete
     * @param $site_id
     */
    public function modifyIsDelete($goods_ids, $is_delete, $site_id)
    {
        model('goods')->update([ 'is_delete' => $is_delete ], [ [ 'goods_id', 'in', $goods_ids ], [ 'site_id', '=', $site_id ] ]);
        model('goods_sku')->update([ 'is_delete' => $is_delete ], [ [ 'goods_id', 'in', $goods_ids ], [ 'site_id', '=', $site_id ] ]);
        //删除商品
        if ($is_delete == 1) {
            event('DeleteGoods', [ 'goods_id' => $goods_ids, 'site_id' => $site_id ]);
        }
        return $this->success(1);
    }

    /**
     * 违规下架商品
     * @param $condition
     * @param $verify_state_remark
     * @return array
     */
    public function lockup($condition, $verify_state_remark)
    {
        model('goods')->update([ 'verify_state_remark' => $verify_state_remark, 'verify_state' => 10, 'goods_state' => 0 ], $condition);
        model('goods_sku')->update([ 'verify_state_remark' => $verify_state_remark, 'verify_state' => 10, 'goods_state' => 0 ], $condition);
        return $this->success(1);
    }

    /**
     * 修改商品点击量
     * @param $sku_id
     * @param $site_id
     */
    public function modifyClick($sku_id)
    {
        model("goods_sku")->setInc([ [ 'sku_id', '=', $sku_id ] ], 'click_num', 1);
        return $this->success(1);
    }

    /**
     * 编辑商品分类
     * @param $category_id
     * @param $condition
     */
    public function modifyCategory($category_id, $condition)
    {
        $category_info = model('goods_category')->getInfo([ [ 'category_id', '=', $category_id ] ], 'category_id,category_id_1, category_id_2, category_id_3, category_full_name');
        $category_array = [];
        if ($category_info[ 'category_id_1' ] > 0) {
            $category_array[] = $category_info[ 'category_id_1' ];
        }
        if ($category_info[ 'category_id_2' ] > 0) {
            $category_array[] = $category_info[ 'category_id_2' ];
        }
        if ($category_info[ 'category_id_3' ] > 0) {
            $category_array[] = $category_info[ 'category_id_3' ];
        }
        $category_json = json_encode($category_array);
        $category_id_string = ',' . implode(',', $category_array) . ',';
        $data = array (
            'category_id' => $category_id_string,
            'category_json' => $category_json,
        );
        model('goods')->update($data, $condition);
//        model('goods_sku')->update($data, $condition);
        return $this->success();
    }

    /**
     * 编辑商品店内分类
     * @param $goods_shop_category_ids
     * @param $condition
     * @return array
     */
    public function modifyShopCategory($goods_shop_category_ids, $condition)
    {
        $data = array (
            'goods_shop_category_ids' => $goods_shop_category_ids
        );
        model('goods')->update($data, $condition);
        model('goods_sku')->update($data, $condition);
        return $this->success();
    }

    /**
     * 修改商品起售限制或限购限制
     */
    public function modifyGoodsLimit($max_buy, $min_buy, $condition)
    {
        $data = array (
            'max_buy' => $max_buy,
            'min_buy' => $min_buy,
        );
        model('goods')->update($data, $condition);
        model('goods_sku')->update($data, $condition);
        return $this->success();
    }

    /**
     * 设置商品是否包邮
     * @param $is_free_shipping
     * @param $shipping_template
     * @param $condition
     * @return array
     */
    public function modifyGoodsShipping($is_free_shipping, $shipping_template, $condition)
    {
        $condition[] = [ 'goods_class', '=', 1 ];//只有实物商品才可以编辑物流信息
        model('goods')->update([ 'is_free_shipping' => $is_free_shipping, 'shipping_template' => $shipping_template ], $condition);
        model('goods_sku')->update([ 'is_free_shipping' => $is_free_shipping, 'shipping_template' => $shipping_template ], $condition);
        return $this->success();
    }

    /**
     * 删除回收站商品
     * @param $goods_ids
     * @param $site_id
     */
    public function deleteRecycleGoods($goods_ids, $site_id)
    {
        model('goods')->delete([ [ 'goods_id', 'in', $goods_ids ], [ 'site_id', '=', $site_id ] ]);
        model('goods_sku')->delete([ [ 'goods_id', 'in', $goods_ids ], [ 'site_id', '=', $site_id ] ]);
        return $this->success(1);
    }

    /**
     * 获取商品信息
     * @param array $condition
     * @param string $field
     */
    public function getGoodsInfo($condition, $field = 'goods_id,goods_name,goods_class,goods_class_name,goods_attr_class,goods_attr_name,category_id,category_json,brand_id,brand_name,goods_image,goods_content,goods_state,verify_state,price,market_price,cost_price,goods_stock,goods_stock_alarm,is_free_shipping,shipping_template,goods_spec_format,goods_attr_format,introduction,keywords,unit,sort,commission_rate,video_url,goods_shop_category_ids,supplier_id,sale_num,evaluate,max_buy,min_buy')
    {
        $info = model('goods')->getInfo($condition, $field);
        return $this->success($info);
    }

    /**
     * 获取商品详情
     * @param $goods_id
     * @return \multitype
     */
    public function getGoodsDetail($goods_id)
    {
        $info = model('goods')->getInfo([ [ 'goods_id', '=', $goods_id ] ], "*");
        $field = 'sku_id, sku_name, sku_no, sku_spec_format, price, market_price, cost_price, discount_price, stock, weight,
         volume, sku_image, sku_images, sort,max_buy,min_buy, member_price,fenxiao_price';
       // $info[ 'sku_data' ] = model('goods_sku')->getList([ [ 'goods_id', '=', $goods_id ] ], $field);
        $sku_data = model('goods_sku')->getList([ [ 'goods_id', '=', $goods_id ] ], $field);

        if (!empty($sku_data)) {
            foreach ($sku_data as $k => $v) {
                $sku_data[ $k ][ 'member_price' ] = $v[ 'member_price' ] == '' ? '' : json_decode($v[ 'member_price' ], true);
            }
        }
        $info[ 'sku_data' ] = $sku_data;


        return $this->success($info);
    }


    /**
     * 商品sku 基础信息
     * @param $condition
     * @param string $field
     * @return array
     */
    public function getGoodsSkuInfo($condition, $field = "sku_id,sku_name,sku_spec_format,price,market_price,discount_price,promotion_type,start_time,end_time,stock,click_num,sale_num,collect_num,sku_image,sku_images,goods_id,site_id,goods_content,goods_state,verify_state,is_virtual,is_free_shipping,goods_spec_format,goods_attr_format,introduction,unit,video_url,max_buy,min_buy")
    {
        $info = model('goods_sku')->getInfo($condition, $field);
        return $this->success($info);
    }

    /**
     * 商品SKU 详情
     * @param $sku_id
     * @param string $field
     * @return array
     */
    public function getGoodsSkuDetail($sku_id, $field = '')
    {
        if (empty($field)) {
            $field = 'gs.goods_id,gs.sku_id,gs.goods_name,gs.sku_name,gs.sku_spec_format,gs.price,gs.market_price,gs.discount_price,gs.promotion_type,gs.start_time,gs.end_time,gs.stock,gs.click_num,g.sale_num,gs.collect_num,gs.sku_image,gs.sku_images,gs.goods_id,gs.site_id,gs.goods_content,gs.goods_state,gs.is_free_shipping,gs.goods_spec_format,gs.goods_attr_format,gs.introduction,gs.unit,gs.video_url,gs.evaluate,gs.is_virtual,gs.max_buy,gs.min_buy,g.goods_image,g.verify_state';
        }
        $join = [
            [ 'goods g', 'g.goods_id = gs.goods_id', 'inner' ],
        ];
        $info = model('goods_sku')->getInfo([ [ 'gs.sku_id', '=', $sku_id ], [ 'gs.is_delete', '=', 0 ] ], $field, 'gs', $join);
        return $this->success($info);
    }

    /**
     * 获取商品列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param string $limit
     */
    public function getGoodsList($condition = [], $field = 'goods_id,goods_class,goods_class_name,goods_attr_name,goods_name,site_id,site_name,website_id,sort,category_name,brand_name,goods_image,goods_content,is_own,goods_state,verify_state,price,market_price,cost_price,goods_stock,goods_stock_alarm,is_virtual,is_free_shipping,shipping_template,goods_spec_format,goods_attr_format,create_time,max_buy,min_buy', $order = 'create_time desc', $limit = null)
    {
        $list = model('goods')->getList($condition, $field, $order, '', '', '', $limit);
        return $this->success($list);
    }

    /**
     * 获取商品分页列表
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getGoodsPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'create_time desc', $field = 'goods_id,goods_name,site_id,site_name,goods_image,is_own,goods_state,verify_state,price,goods_stock,goods_stock_alarm,create_time,sale_num,is_virtual,goods_class,is_fenxiao,fenxiao_type,sku_id,is_consume_discount,discount_config,discount_method,max_buy,min_buy,promotion_addon,sort', $alias = '', $join = '', $group = null)
    {
        $list = model('goods')->pageList($condition, $field, $order, $page, $page_size, $alias, $join, $group);
        return $this->success($list);
    }

    /**
     * 编辑商品库存价格等信息
     * @param $goods_sku_array
     * @return array|\multitype
     */
    public function editGoodsStockPrice($goods_sku_array)
    {
        $goods_sku_model = new GoodsStock();
        model('goods')->startTrans();
        try {
            foreach ($goods_sku_array as $k => $v) {
                $sku_info = model("goods_sku")->getInfo([ [ 'sku_id', '=', $v[ 'sku_id' ] ] ], "goods_id,stock");

                if ($k == 0) {//修改商品中的价格等信息
                    $goods_data = [
                        'price' => $v[ 'price' ],
                        'market_price' => $v[ 'market_price' ],
                        'cost_price' => $v[ 'cost_price' ]
                    ];
                    model('goods')->update($goods_data, [ [ 'goods_id', '=', $sku_info[ 'goods_id' ] ] ]);
                }
                if ($v[ 'stock' ] > $sku_info[ 'stock' ]) {
                    $sku_stock_data = [
                        'sku_id' => $v[ 'sku_id' ],
                        'num' => $v[ 'stock' ] - $sku_info[ 'stock' ]
                    ];
                    $goods_sku_model->incStock($sku_stock_data);
                }
                if ($v[ 'stock' ] < $sku_info[ 'stock' ]) {
                    $sku_stock_data = [
                        'sku_id' => $v[ 'sku_id' ],
                        'num' => $sku_info[ 'stock' ] - $v[ 'stock' ]
                    ];
                    $goods_sku_model->decStock($sku_stock_data);
                }
                unset($v[ 'stock' ]);
                model('goods_sku')->update($v, [ [ 'sku_id', '=', $v[ 'sku_id' ] ] ]);

            }
            model('goods')->commit();
            return $this->success();
        } catch (\Exception $e) {
            model('goods')->rollback();
            return $this->error($e->getMessage());
        }
    }

    /**
     * 编辑商品库存等信息
     * @param $goods_sku_array
     * @return array|\multitype
     */
    public function editGoodsStock($goods_sku_array)
    {
        $goods_sku_model = new GoodsStock();
        model('goods')->startTrans();
        try {
            foreach ($goods_sku_array as $k => $v) {
                $sku_info = model("goods_sku")->getInfo([ [ 'sku_id', '=', $v[ 'sku_id' ] ] ], "goods_id,stock");

                if ($v[ 'stock' ] > $sku_info[ 'stock' ]) {
                    $sku_stock_data = [
                        'sku_id' => $v[ 'sku_id' ],
                        'num' => $v[ 'stock' ] - $sku_info[ 'stock' ]
                    ];
                    $goods_sku_model->incStock($sku_stock_data);
                }
                if ($v[ 'stock' ] < $sku_info[ 'stock' ]) {
                    $sku_stock_data = [
                        'sku_id' => $v[ 'sku_id' ],
                        'num' => $sku_info[ 'stock' ] - $v[ 'stock' ]
                    ];
                    $goods_sku_model->decStock($sku_stock_data);
                }
                unset($v[ 'stock' ]);
                model('goods_sku')->update($v, [ [ 'sku_id', '=', $v[ 'sku_id' ] ] ]);

            }
            model('goods')->commit();
            return $this->success();
        } catch (\Exception $e) {
            model('goods')->rollback();
            return $this->error($e->getMessage());
        }
    }

    /**
     * 获取商品sku列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param string $limit
     */
    public function getGoodsSkuList($condition = [], $field = 'sku_id,sku_name,price,stock,sale_num,sku_image,goods_id,goods_name,site_id,site_name,spec_name,max_buy,min_buy', $order = 'create_time desc', $limit = null, $alias = '', $join = '', $group = null)
    {
        $list = model('goods_sku')->getList($condition, $field, $order, $alias, $join, '', $limit);
        return $this->success($list);
    }
    /**
     * 获取sku商品会员价格
     */
    public function getSkuMemberPrice($sku_list){
        $member_level_list = model('member_level')->getList([ ], 'level_name,level_id,consume_discount', 'level_type asc,growth asc');
        foreach ($sku_list as $k => $sku_item) {
            $member_level = [];
            $is_consume_discount = $sku_item['is_consume_discount'] ?? 0;
            if ($is_consume_discount) {
                foreach ($member_level_list as $level_item) {
                    // 自定义优惠
                    if ($sku_item[ 'discount_config' ] == 1) {
                        $member_price = json_decode($sku_item[ 'member_price' ], true);
                        $value = isset($member_price[ $sku_item[ 'discount_method' ] ][ $level_item['level_id'] ]) ? $member_price[ $sku_item[ 'discount_method' ] ][ $level_item['level_id'] ] : 0;
                        switch ( $sku_item[ 'discount_method' ] ) {
                            case "discount":
                                // 打折
                                if ($value == 0) {
                                    $level_item[ 'member_price' ] = $sku_item[ 'price' ];
                                } else
                                    $level_item[ 'member_price' ] = round($sku_item[ 'price' ] * $value / 10, 2);
                                break;
                            case "manjian":
                                if ($value == 0) {
                                    $level_item[ 'member_price' ] = $sku_item[ 'price' ];
                                } else
                                    // 满减
                                    $level_item[ 'member_price' ] = round($sku_item[ 'price' ] - $value, 2);
                                break;
                            case "fixed_price":
                                if ($value == 0) {
                                    $level_item[ 'member_price' ] = $sku_item[ 'price' ];
                                } else
                                    // 指定价格
                                    $level_item[ 'member_price' ] = round($value, 2);
                                break;
                        }
                    } else {
                        $level_item['member_price'] = round($sku_item[ 'price' ] * $level_item[ 'consume_discount' ] / 100, 2);
                    }
                    array_push($member_level, $level_item);
                }
            }
            $sku_list[$k]['member_price_list'] = $member_level;
        }
        return $sku_list;
    }
    /**
     * 获取商品sku分页列表
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     * @param string $alias
     * @param string $join
     */
    public function getGoodsSkuPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = 'sku_id,sku_name,price,sku_image,create_time,stock,goods_state,goods_class,goods_class_name,max_buy,min_buy', $alias = '', $join = '', $group = null)
    {
        $list = model('goods_sku')->pageList($condition, $field, $order, $page, $page_size, $alias, $join, $group);
        return $this->success($list);
    }

    /**
     * 刷新商品关联属性关系
     * @param $goods_id
     * @param $goods_attr_format
     */
    public function refreshGoodsAttribute($goods_id, $goods_attr_format)
    {
        model('goods_attr_index')->delete([ [ 'goods_id', '=', $goods_id ], [ 'app_module', '=', 'shop' ] ]);
        if (!empty($goods_attr_format)) {

            $list = model('goods_sku')->getList([ [ 'goods_id', '=', $goods_id ] ], 'sku_id');
            $goods_attr_format = json_decode($goods_attr_format, true);
            $attr_data = [];
            foreach ($goods_attr_format as $k => $v) {
                foreach ($list as $ck => $cv) {
                    $item = [
                        'goods_id' => $goods_id,
                        'sku_id' => $cv[ 'sku_id' ],
                        'attr_id' => $v[ 'attr_id' ],
                        'attr_value_id' => $v[ 'attr_value_id' ],
                        'attr_class_id' => $v[ 'attr_class_id' ],
                        'app_module' => 'shop'
                    ];
                    $attr_data[] = $item;
                }

            }

            model('goods_attr_index')->addList($attr_data);
        }
    }

    /**
     * 刷新SKU商品规格项/规格值JSON字符串
     * @param int $goods_id 商品id
     * @param string $goods_spec_format 商品完整规格项/规格值json
     */
    public function dealGoodsSkuSpecFormat($goods_id, $goods_spec_format)
    {
        if (empty($goods_spec_format)) return;

        $goods_spec_format = json_decode($goods_spec_format, true);

        //根据goods_id查询sku商品列表，查询：sku_id、sku_spec_format 列
        $sku_list = model('goods_sku')->getList([ [ 'goods_id', '=', $goods_id ], [ 'sku_spec_format', '<>', '' ] ], 'sku_id,sku_spec_format', 'sku_id asc');
        if (!empty($sku_list)) {

//			$temp = 0;//测试性能，勿删

            //循环SKU商品列表
            foreach ($sku_list as $k => $v) {
//				$temp++;

                $sku_format = $goods_spec_format;//最终要存储的值
                $current_format = json_decode($v[ 'sku_spec_format' ], true);//当前SKU商品规格值json

                $selected_data = [];//已选规格/规格值json

                //1、找出已选规格/规格值json

                //循环完整商品规格json
                foreach ($sku_format as $sku_k => $sku_v) {
//					$temp++;

                    //循环当前SKU商品规格json
                    foreach ($current_format as $current_k => $current_v) {
//						$temp++;

                        //匹配规格项
                        if ($current_v[ 'spec_id' ] == $sku_v[ 'spec_id' ]) {

                            //循环规格值
                            foreach ($sku_v[ 'value' ] as $sku_value_k => $sku_value_v) {
//								$temp++;

                                //匹配规格值id
                                if ($current_v[ 'spec_value_id' ] == $sku_value_v[ 'spec_value_id' ]) {
                                    $sku_format[ $sku_k ][ 'value' ][ $sku_value_k ][ 'selected' ] = true;
                                    $sku_format[ $sku_k ][ 'value' ][ $sku_value_k ][ 'sku_id' ] = $v[ 'sku_id' ];
                                    $selected_data[] = $sku_format[ $sku_k ][ 'value' ][ $sku_value_k ];
                                    break;
                                }
                            }

                        }

                    }
                }

                //2、找出未选中的规格/规格值json
                foreach ($sku_format as $sku_k => $sku_v) {
//					$temp++;

                    foreach ($sku_v[ 'value' ] as $sku_value_k => $sku_value_v) {
//						$temp++;

                        if (!isset($sku_value_v[ 'selected' ])) {

                            $refer_data = [];//参考已选中的规格/规格值json
                            $refer_data[] = $sku_value_v;

//							根据已选中的规格值进行参考
                            foreach ($selected_data as $selected_k => $selected_v) {
//								$temp++;
//								排除自身，然后进行参考
                                if ($selected_v[ 'spec_id' ] != $sku_value_v[ 'spec_id' ]) {
                                    $refer_data[] = $selected_v;
                                }
                            }

                            foreach ($sku_list as $again_k => $again_v) {
//								$temp++;

                                //排除当前SKU商品
                                if ($again_v[ 'sku_id' ] != $v[ 'sku_id' ]) {

                                    $current_format_again = json_decode($again_v[ 'sku_spec_format' ], true);
                                    $count = count($current_format_again);//规格总数量
                                    $curr_count = 0;//当前匹配规格数量

                                    //循环当前SKU商品规格json
                                    foreach ($current_format_again as $current_again_k => $current_again_v) {
//										$temp++;

                                        foreach ($refer_data as $fan_k => $fan_v) {
//											$temp++;

                                            if ($current_again_v[ 'spec_value_id' ] == $fan_v[ 'spec_value_id' ] && $current_again_v[ 'spec_id' ] == $fan_v[ 'spec_id' ]) {
                                                $curr_count++;
                                            }
                                        }

                                    }
//									匹配数量跟规格总数一致表示匹配成功
                                    if ($curr_count == $count) {
                                        $sku_format[ $sku_k ][ 'value' ][ $sku_value_k ][ 'selected' ] = false;
                                        $sku_format[ $sku_k ][ 'value' ][ $sku_value_k ][ 'sku_id' ] = $again_v[ 'sku_id' ];
                                        break;
                                    }
                                }

                            }
                            //没有匹配到规格值，则禁用
                            if (!isset($sku_format[ $sku_k ][ 'value' ][ $sku_value_k ][ 'selected' ])) {
                                $sku_format[ $sku_k ][ 'value' ][ $sku_value_k ][ 'disabled' ] = false;
                            }
                        }
                    }
                }
                //修改goods_sku表表中的goods_spec_format字段，将$sku_format值传入
                model('goods_sku')->update([ 'goods_spec_format' => json_encode($sku_format) ], [ [ 'sku_id', '=', $v[ 'sku_id' ] ] ]);
            }
        }
    }

    /**
     * 商品推广二维码
     * @param $sku_id
     * @param $goods_name
     * @param string $type
     * @return array
     */
    public function qrcode($sku_id, $goods_name, $type = "create")
    {
        $data = [
            'app_type' => "all", // all为全部
            'type' => $type, // 类型 create创建 get获取
            'data' => [
                "sku_id" => $sku_id
            ],
            'page' => '/pages/goods/detail/detail',
            'qrcode_path' => 'upload/qrcode/goods',
            'qrcode_name' => "goods_qrcode_" . $sku_id,
        ];

        event('Qrcode', $data, true);
        $app_type_list = config('app_type');

        $path = [];

        //获取站点信息
        $website_model = new WebConfig();
        $website_info = $website_model->geth5DomainName();

        foreach ($app_type_list as $k => $v) {
            switch ( $k ) {
                case 'h5':
                        $path[ $k ][ 'status' ] = 1;
                        $path[ $k ][ 'url' ] = $website_info[ 'data' ]['value'][ 'domain_name_h5' ] . $data[ 'page' ] . '?sku_id=' . $sku_id;
                        $path[ $k ][ 'img' ] = "upload/qrcode/goods/goods_qrcode_" . $sku_id . "_" . $k . ".png";
                    break;
                case 'weapp' :
                    $config = new ConfigModel();
                    $res = $config->getConfig([ [ 'site_id', '=', 0 ], [ 'app_module', '=', 'admin' ], [ 'config_key', '=', 'WEAPP_CONFIG' ] ]);
                    if (!empty($res[ 'data' ])) {
                        if (empty($res[ 'data' ][ 'value' ][ 'qrcode' ])) {
                            $path[ $k ][ 'status' ] = 2;
                            $path[ $k ][ 'message' ] = '未配置微信小程序';
                        } else {
                            $path[ $k ][ 'status' ] = 1;
                            $path[ $k ][ 'img' ] = $res[ 'data' ][ 'value' ][ 'qrcode' ];
                        }

                    } else {
                        $path[ $k ][ 'status' ] = 2;
                        $path[ $k ][ 'message' ] = '未配置微信小程序';
                    }
                    break;

                case 'wechat' :
                    $config = new ConfigModel();
                    $res = $config->getConfig([ [ 'site_id', '=', 0 ], [ 'app_module', '=', 'admin' ], [ 'config_key', '=', 'WECHAT_CONFIG' ] ]);
                    if (!empty($res[ 'data' ])) {
                        if (empty($res[ 'data' ][ 'value' ][ 'qrcode' ])) {
                            $path[ $k ][ 'status' ] = 2;
                            $path[ $k ][ 'message' ] = '未配置微信公众号';
                        } else {
                            $path[ $k ][ 'status' ] = 1;
                            $path[ $k ][ 'img' ] = $res[ 'data' ][ 'value' ][ 'qrcode' ];
                        }
                    } else {
                        $path[ $k ][ 'status' ] = 2;
                        $path[ $k ][ 'message' ] = '未配置微信公众号';
                    }
                    break;
            }

        }

        $return = [
            'path' => $path,
            'goods_name' => $goods_name,
        ];

        return $this->success($return);
    }

    /**
     * 增加商品销量
     * @param $sku_id
     * @param $num
     */
    public function incGoodsSaleNum($sku_id, $num)
    {
        $condition = array (
            [ "sku_id", "=", $sku_id ]
        );
        //增加sku销量
        $res = model("goods_sku")->setInc($condition, "sale_num", $num);
        if ($res !== false) {
            $sku_info = model("goods_sku")->getInfo($condition, "goods_id");
            $res = model("goods")->setInc([ [ "goods_id", "=", $sku_info[ "goods_id" ] ] ], "sale_num", $num);
            return $this->success($res);
        }

        return $this->error($res);
    }

    /**
     * 减少商品销量
     * @param $sku_id
     * @param $num
     */
    public function decGoodsSaleNum($sku_id, $num)
    {
        $condition = array (
            [ "sku_id", "=", $sku_id ]
        );
        //增加sku销量
        $res = model("goods_sku")->setDec($condition, "sale_num", $num);
        if ($res !== false) {
            $sku_info = model("goods_sku")->getInfo($condition, "goods_id");
            $res = model("goods")->setDec([ [ "goods_id", "=", $sku_info[ "goods_id" ] ] ], "sale_num", $num);
            return $this->success($res);
        }
        return $this->error($res);
    }

    /**
     * 获取商品总数
     * @param array $condition
     * @return array
     */
    public function getGoodsTotalCount($condition = [])
    {
        $res = model('goods')->getCount($condition);
        return $this->success($res);
    }
    /**
     * 获取商品会员价
     * @param $sku_id
     * @param $member_id
     * @return array
     */
    public function getGoodsPrice($sku_id, $member_id)
    {
        $res = [
            'discount_price' => 0, // 折扣价（默认等于单价）
            'member_price' => 0, // 会员价
            'price' => 0 // 最低价格
        ];
        $goods_sku_info = model("goods_sku")->getInfo([ [ 'sku_id', '=', $sku_id ] ], 'is_consume_discount,discount_config,discount_method,price,member_price,discount_price');

        if (empty($goods_sku_info)) return $this->success($res);

        $res[ 'discount_price' ] = $goods_sku_info[ 'discount_price' ];
        $res[ 'price' ] = $goods_sku_info[ 'discount_price' ];

        if (!addon_is_exit("memberprice")) return $this->success($res);

        if ($goods_sku_info[ 'is_consume_discount' ]) {
            $alias = 'm';
            $join = [
                [ 'member_level ml', 'ml.level_id = m.member_level', 'inner' ],
            ];
            $member_info = model("member")->getInfo([ [ 'member_id', '=', $member_id ] ], 'm.member_level,ml.consume_discount', $alias, $join);
            if (!empty($member_info)) {
                if ($goods_sku_info[ 'discount_config' ] == 1) {
                    // 自定义优惠
                    $goods_sku_info[ 'member_price' ] = json_decode($goods_sku_info[ 'member_price' ], true);
                    $value = isset($goods_sku_info[ 'member_price' ][ $goods_sku_info[ 'discount_method' ] ][ $member_info[ 'member_level' ] ]) ? $goods_sku_info[ 'member_price' ][ $goods_sku_info[ 'discount_method' ] ][ $member_info[ 'member_level' ] ] : 0;
                    switch ( $goods_sku_info[ 'discount_method' ] ) {
                        case "discount":
                            // 打折
                            if ($value == 0) {
                                $res[ 'member_price' ] = $goods_sku_info[ 'price' ];
                            } else
                                $res[ 'member_price' ] = round($goods_sku_info[ 'price' ] * $value / 10, 2);
                            break;
                        case "manjian":
                            if ($value == 0) {
                                $res[ 'member_price' ] = $goods_sku_info[ 'price' ];
                            } else
                                // 满减
                                $res[ 'member_price' ] = round($goods_sku_info[ 'price' ] - $value, 2);
                            break;
                        case "fixed_price":
                            if ($value == 0) {
                                $res[ 'member_price' ] = $goods_sku_info[ 'price' ];
                            } else
                                // 指定价格
                                $res[ 'member_price' ] = round($value, 2);
                            break;
                    }
                } else {
                    // 默认按会员享受折扣计算
                    $res[ 'member_price' ] = round($goods_sku_info[ 'price' ] * $member_info[ 'consume_discount' ] / 100, 2);
                }
                if ($res[ 'member_price' ] < $res[ 'price' ]) {
                    $res[ 'price' ] = $res[ 'member_price' ];
                }
            }

        }
        return $this->success($res);
    }
    /**
     * 获取商品会员价（列表）
     * @param array $goods_list
     * @param $member_id
     * @param $site_id
     */
    public function getGoodsListMemberPirce(array $goods_list, $member_id){
        if (!addon_is_exit("memberprice")) return $goods_list;

        $alias = 'm';
        $join = [
            [ 'member_level ml', 'ml.level_id = m.member_level', 'inner' ],
        ];
        $member_info = model("member")->getInfo([ [ 'member_id', '=', $member_id ] ], 'm.member_level,ml.consume_discount', $alias, $join);
        if (empty($member_info)) return $goods_list;

        foreach ($goods_list as $key => $goods_item) {
            if ($goods_item[ 'is_consume_discount' ]){
                // 自定义优惠
                if ($goods_item['discount_config'] == 1) {
                    $member_price_config = json_decode($goods_item[ 'member_price' ], true);
                    $value = $member_price_config[ $goods_item[ 'discount_method' ] ][ $member_info[ 'member_level' ] ] ?? 0;
                    switch ( $goods_item[ 'discount_method' ] ) {
                        case "discount":
                            // 打折
                            if ($value == 0) {
                                $goods_list[$key][ 'member_price' ] = $goods_item[ 'price' ];
                            } else
                                $goods_list[$key][ 'member_price' ] = round($goods_item[ 'price' ] * $value / 10, 2);
                            break;
                        case "manjian":
                            if ($value == 0) {
                                $goods_list[$key][ 'member_price' ] = $goods_item[ 'price' ];
                            } else
                                // 满减
                                $goods_list[$key][ 'member_price' ] = round($goods_item[ 'price' ] - $value, 2);
                            break;
                        case "fixed_price":
                            if ($value == 0) {
                                $goods_list[$key][ 'member_price' ] = $goods_item[ 'price' ];
                            } else
                                // 指定价格
                                $goods_list[$key][ 'member_price' ] = round($value, 2);
                            break;
                    }
                } else {
                    $goods_list[$key][ 'member_price' ] = round($goods_item[ 'price' ] * $member_info[ 'consume_discount' ] / 100, 2);
                }
            } else {
                unset($goods_list[$key][ 'member_price' ]);
            }
        }
        return $goods_list;
    }
    /**
     * 获取商品规格项总数
     * @param array $condition
     * @return array
     */
    public function getGoodsSkuCount($condition = [])
    {
        $res = model('goods_sku')->getCount($condition);
        return $this->success($res);
    }

    /**
     * 复制商品(创建商品的副本)
     * @param $goods_id
     */
    public function copyGoods($condition)
    {
        //查询商品原来的数据用于复制
        $goods_info = model('goods')->getInfo($condition, '*');
        if (empty($goods_info))
            return $this->error('', '商品不存在，无法复制！');

        $sku_list = model('goods_sku')->getList($condition, '*');
        if (empty($sku_list))
            return $this->error('', '商品不存在，无法复制！');

        model('goods')->startTrans();

        try {

            //店铺信息
            $shop_info = model('shop')->getInfo([ [ 'site_id', '=', $goods_info[ 'site_id' ] ] ], 'site_name, website_id, is_own,cert_id,shop_status');

            $goods_config = new Config();
            $goods_verify_config = $goods_config->getVerifyConfig();
            $goods_verify_config = $goods_verify_config[ 'data' ][ 'value' ];
            $verify_state = 1;
            if (!empty($goods_verify_config[ 'is_open' ])) {
                $verify_state = 0;//开启商品审核后，审核状态为：待审核
            }

            // 店铺未认证、审核中的状态下，商品需要审核
            if (empty($shop_info[ 'cert_id' ]) || $shop_info[ 'shop_status' ] == 0 || $shop_info[ 'shop_status' ] == 2) {
                $verify_state = 0;//开启商品审核后，审核状态为：待审核
            }

            //先创建新的商品数据(商品默认没有库存,模拟创建操作)
            $goods_data = $goods_info;

            unset($goods_data[ 'goods_id' ]);//删除原有的自增长键

            $goods_data[ 'goods_name' ] .= '_副本';

            //重置部分字段, 库存都为空, 默认不上架
            $goods_data[ 'sku_id' ] = 0;
            $goods_data[ 'create_time' ] = time();
            $goods_data[ 'modify_time' ] = time();

            $goods_data[ 'promotion_addon' ] = '';
            $goods_data[ 'sale_num' ] = 0;

            $goods_data[ 'evaluate' ] = 0;
            $goods_data[ 'evaluate_shaitu' ] = 0;
            $goods_data[ 'evaluate_shipin' ] = 0;
            $goods_data[ 'evaluate_zhuiping' ] = 0;
            $goods_data[ 'evaluate_haoping' ] = 0;
            $goods_data[ 'evaluate_zhongping' ] = 0;
            $goods_data[ 'evaluate_chaping' ] = 0;
            $goods_data[ 'is_fenxiao' ] = 0;
            $goods_data[ 'fenxiao_type' ] = 1;
            $goods_data[ 'is_consume_discount' ] = 0;
            $goods_data[ 'goods_state' ] = 0;//仓库中
            $goods_data[ 'verify_state' ] = $verify_state;
            $goods_data[ 'verify_state_remark' ] = '';
            $goods_data[ 'goods_stock' ] = 0;//todo  复制商品的同时复制商品

            $goods_data[ 'supply_goods_id' ] = 0;//关联供应商的id
            $goods_id = model('goods')->add($goods_data);
            $sku_arr = [];
            foreach ($sku_list as $k => $v) {
                $sku_data = $v;

                unset($sku_data[ 'sku_id' ]);

                $sku_data[ 'sku_name' ] .= '_副本';
                $sku_data[ 'goods_name' ] .= '_副本';
                $sku_data[ 'goods_id' ] = $goods_id;
                $sku_data[ 'stock' ] = 0;
                $sku_data[ 'goods_state' ] = 0;
                $sku_data[ 'verify_state' ] = $verify_state;

                $sku_data[ 'evaluate' ] = 0;
                $sku_data[ 'evaluate_shaitu' ] = 0;
                $sku_data[ 'evaluate_shipin' ] = 0;
                $sku_data[ 'evaluate_zhuiping' ] = 0;
                $sku_data[ 'evaluate_haoping' ] = 0;
                $sku_data[ 'evaluate_zhongping' ] = 0;
                $sku_data[ 'evaluate_chaping' ] = 0;

                $sku_data[ 'click_num' ] = 0;
                $sku_data[ 'sale_num' ] = 0;
                $sku_data[ 'collect_num' ] = 0;


                $sku_data[ 'sale_num' ] = 0;

//                $sku_data[ 'goods_state' ] = 0;
//                $sku_data[ 'verify_state' ] = 0;
                $sku_data[ 'verify_state_remark' ] = '';
                $sku_data[ 'is_consume_discount' ] = 0;
                $sku_data[ 'start_time' ] = 0;
                $sku_data[ 'end_time' ] = 0;
                $sku_data[ 'promotion_type' ] = 0;
                $sku_data[ 'discount_price' ] = $v[ 'price' ];
                $sku_data[ 'start_time' ] = 0;
                $sku_data[ 'fenxiao_price' ] = 0;
                $sku_arr[] = $sku_data;
            }


            model('goods_sku')->addList($sku_arr);

            // 赋值第一个商品sku_id
            $first_info = model('goods_sku')->getFirstData([ 'goods_id' => $goods_id ], 'sku_id', 'is_default desc,sku_id asc');
            model('goods')->update([ 'sku_id' => $first_info[ 'sku_id' ] ], [ [ 'goods_id', '=', $goods_id ] ]);

            //添加商品属性关联关系
            $this->refreshGoodsAttribute($goods_id, $goods_info[ 'goods_attr_format' ]);

            if (!empty($goods_info[ 'goods_spec_format' ])) {
                //刷新SKU商品规格项/规格值JSON字符串
                $this->dealGoodsSkuSpecFormat($goods_id, $goods_info[ 'goods_spec_format' ]);
            }

            //添加店铺添加统计
            //添加统计
            $stat = new Stat();
            $stat->addShopStat([ 'add_goods_count' => 1, 'site_id' => $goods_info[ 'site_id' ] ]);
            model('goods')->commit();

            return $this->success($goods_id);
        } catch (\Exception $e) {
            model('goods')->rollback();
            return $this->error($e->getMessage());
        }
    }

    /**
     * 获取会员已购该商品数
     * @param $goods_id
     * @param $member_id
     */
    public function getGoodsPurchasedNum($goods_id, $member_id)
    {
        $join = [
            [ 'order o', 'o.order_id = og.order_id', 'left' ]
        ];
        $num = model("order_goods")->getSum([
            [ 'og.member_id', '=', $member_id ],
            [ 'og.goods_id', '=', $goods_id ],
            [ 'o.order_status', '<>', OrderCommon::ORDER_CLOSE ],
            [ 'og.refund_status', '<>', OrderRefund::REFUND_COMPLETE ]
        ], 'og.num', 'og', $join);
        return $num;
    }

    /**
     * 修改当前商品参与的营销活动标识，逗号分隔（限时折扣、团购、拼团、秒杀、专题活动）
     * @param $goods_id
     * @param array $promotion 营销活动标识，【promotion:value】
     * @param bool $is_delete 是否删除
     * @return array
     */
    public function modifyPromotionAddon($goods_id, $promotion = [], $is_delete = false)
    {
        $goods_info = model("goods")->getInfo([ [ 'goods_id', '=', $goods_id ] ], 'promotion_addon');
        $promotion_addon = [];
        if (!empty($goods_info[ 'promotion_addon' ])) {
            $promotion_addon = json_decode($goods_info[ 'promotion_addon' ], true);
        }
        $promotion_addon = array_merge($promotion_addon, $promotion);
        if ($is_delete) {
            foreach ($promotion as $k => $v) {
                unset($promotion_addon[ $k ]);
            }
        }
        if (!empty($promotion_addon)) {
            $promotion_addon = json_encode($promotion_addon);
        } else {
            $promotion_addon = '';
        }
        $res = model("goods")->update([ 'promotion_addon' => $promotion_addon ], [ [ 'goods_id', '=', $goods_id ] ]);
        return $this->success($res);
    }

    /**
     * 商品上架
     */
    public function goodsOn($condition)
    {
        model('goods')->update([ 'goods_state' => 1 ], $condition);
        model('goods_sku')->update([ 'goods_state' => 1 ], $condition);
        return $this->success(1);
    }

    /**
     * 修改商品推荐方式
     * @param $recom_way
     * @param $site_id
     * @param $goods_ids
     * @return array
     */
    public function modifyGoodsRecommendWay($recom_way, $goods_ids)
    {

        model('goods')->update([ 'recommend_way' => $recom_way ], [ [ 'goods_id', 'in', $goods_ids ] ]);
        model('goods_sku')->update([ 'recommend_way' => $recom_way ], [ [ 'goods_id', 'in', $goods_ids ] ]);
        return $this->success();
    }

    /**
     * 商品下架
     */
    public function goodsOff($condition)
    {
        model('goods')->update([ 'goods_state' => 0 ], $condition);
        model('goods_sku')->update([ 'goods_state' => 0 ], $condition);
        return $this->success(1);
    }


    /**
     * 判断规格值是否禁用
     * @param $bargain_id
     * @param $site_id
     * @param $goods
     * @return false|string
     */
    public function getGoodsSpecFormat($sku_ids, $goods_spec_format)
    {
        if (!empty($goods_spec_format) && !empty($sku_ids)) {

            $sku_spec_format = model('goods_sku')->getColumn([ [ 'sku_id', 'in', $sku_ids ] ], 'sku_spec_format');
            $sku_spec_format_arr = [];
            foreach ($sku_spec_format as $sku_spec) {
                if (!empty($sku_spec)) {
                    $format = json_decode($sku_spec, true);
                    foreach ($format as $format_v) {
                        if (empty($sku_spec_format_arr[ $format_v[ 'spec_id' ] ])) {
                            $sku_spec_format_arr[ $format_v[ 'spec_id' ] ] = [];
                        }
                        $sku_spec_format_arr[ $format_v[ 'spec_id' ] ][] = $format_v[ 'spec_value_id' ];
                    }
                }
            }

            $goods_spec_format = json_decode($goods_spec_format, true);
            $count = count($goods_spec_format);
            foreach ($goods_spec_format as $k => $v) {
                foreach ($v[ 'value' ] as $key => $item) {
                    if (!in_array($item[ 'spec_value_id' ], $sku_spec_format_arr[ $item[ 'spec_id' ] ])) {
                        $v[ 'value' ][ $key ][ 'disabled' ] = true;
                    }
                }
                if ($k > 0 || $count == 1) {
                    foreach ($v[ 'value' ] as $key => $item) {
                        if (!in_array($item[ 'sku_id' ], $sku_ids)) {
                            $v[ 'value' ][ $key ][ 'disabled' ] = true;
                        }
                    }
                }
                $goods_spec_format[ $k ][ 'value' ] = $v[ 'value' ];
            }
            return $goods_spec_format;
        }
    }

    /**
     * 根据商品和营销活动支持的sku比对,选择合适的sku返回
     * @param $sku_ids
     * @param $sku_id
     * @return int|mixed
     */
    public function getEmptyGoodsSpecFormat($sku_ids, $sku_id)
    {
        if (!empty($sku_id) && !empty($sku_ids)) {

            $sku_spec_format = model('goods_sku')->getValue([ [ 'sku_id', '=', $sku_id ] ], 'sku_spec_format');
            $sku_spec_format = json_decode($sku_spec_format, true);
            $sku_spec_format = array_column($sku_spec_format, null, 'spec_id');

            $sku_array = [];
            $spec_dict = [];
            foreach ($sku_spec_format as $k => $v) {
                $spec_dict[] = $k;
            }
            $sku_spec_list = model('goods_sku')->getList([ [ 'sku_id', 'in', $sku_ids ] ], 'sku_spec_format,sku_id');
            $sku_spec_list = array_column($sku_spec_list, 'sku_spec_format', 'sku_id');
            foreach ($sku_spec_list as $sku_key => $sku_spec) {
                $format = json_decode($sku_spec, true);
                $format_column = array_column($format, null, 'spec_id');
                $key = 0;
                $num = $this->verifySkuSpec($format_column, $sku_spec_format, $key, $spec_dict);
                if (empty($sku_array)) {
                    $sku_array = array (
                        'sku_id' => $sku_key,
                        'num' => $num
                    );
                } else {
                    if ($num > $sku_array[ 'num' ]) {
                        $sku_array = array (
                            'sku_id' => $sku_key,
                            'num' => $num
                        );
                    }
                }
            }
            $temp_sku_id = $sku_array[ 'sku_id' ] ?? 0;
            return $temp_sku_id;
        }
        return 0;
    }

    public function verifySkuSpec($spec1, $spec2, $key, $spec_dict)
    {
        $real_key = $spec_dict[ $key ];
        $spec1_value = $spec1[ $real_key ];
        $spec2_value = $spec2[ $real_key ];
        $num = 0;
        if ($spec1_value[ 'spec_value_id' ] == $spec2_value[ 'spec_value_id' ]) {
            $num++;
            $key++;
            $num += $this->verifySkuSpec($spec1, $spec2, $key, $spec_dict);
        }
        return $num;

    }

    /**
     * 库存预警数量
     * @param $site_id
     * @return array
     */
    public function getGoodsStockAlarm($site_id)
    {
        $prefix = config('database.connections.mysql.prefix');
        $sql = 'select count(goods_id) as num from ' . $prefix . 'goods where goods_stock_alarm >= goods_stock and goods_stock_alarm > 0 and is_delete = 0 and goods_state = 1 and site_id = ' . $site_id;
        $count = model('goods')->query($sql);
        return $this->success($count[ 0 ][ 'num' ]);
    }

    /**
     * @param string $site_id
     * @param $website_id
     * @return array
     */
    public function editWebsiteId($site_id, $website_id){
        model('goods')->update(['website_id'=>  $website_id],[['site_id', '=', $site_id]]);
        model('goods_sku')->update(['website_id'=>  $website_id],[['site_id', '=', $site_id]]);
        return $this->success();
    }

}