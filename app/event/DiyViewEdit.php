<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\event;

use app\Controller;
use app\model\shop\Shop;
use app\model\shop\ShopGroup as ShopGroupModel;
use app\model\web\DiyView as DiyViewModel;

/**
 * 自定义页面编辑
 */
class DiyViewEdit extends Controller
{
    /**
     * 行为扩展的执行入口必须是run
     * @param $data
     * @return mixed
     */
    public function handle($data)
    {
        $diy_view = new DiyViewModel();

        $shop = new Shop();
        $shop_info_res = $shop->getShopInfo([[ 'site_id', '=', $data['site_id'] ]], 'group_id');
        $shop_info = is_array($shop_info_res) ? ($shop_info_res['data'] ?? []) : [];
        $group_id = isset($shop_info['group_id']) ? $shop_info['group_id'] : 0;

        $addon_array = [];
        if ($data['app_module'] == 'shop') {
            // 查询店铺套餐
            $shop_group_model = new ShopGroupModel();
            $group_info_res = $shop_group_model->getGroupInfo([ 'group_id' => $group_id ], 'addon_array');
            $group_info = is_array($group_info_res) ? ($group_info_res['data'] ?? []) : [];
            $group_addon_array = $group_info['addon_array'] ?? '';

            if (!empty($group_addon_array)) {
                $addon_array = explode(',', $group_addon_array);
            }
        }

        $addon_array[] = '';
        if (!empty($data['addon_name'])) {
            $addon_array[] = $data['addon_name'];
        }

        if (!empty($data['site_id'])) {
            // 自定义模板组件集合
            $data['condition'][] = [
                [ 'addon_name', 'in', $addon_array, 'or' ]
            ];
        }

        $utils = $diy_view->getDiyViewUtilList($data['condition']);

        $diy_view_info = [];
        // 推广码
        $qrcode_info = [];
        if (!empty($data['id'])) {
            $diy_view_info = $diy_view->getSiteDiyViewDetail([
                [ 'sdv.site_id', '=', $data[ 'site_id' ] ],
                [ 'sdv.id', '=', $data[ 'id' ] ]
            ]);
            $qrcode_info = $diy_view->qrcode([
                [ 'site_id', '=', $data[ 'site_id' ] ],
                [ 'id', '=', $data[ 'id' ] ]
            ]);
        } elseif (!empty($data[ 'name' ])) {
            $condition = [
                [ 'sdv.site_id', '=', $data[ 'site_id' ] ],
                [ 'sdv.name', '=', $data[ 'name' ] ]
            ];
            $qrcode_info = $diy_view->qrcode([
                [ 'site_id', '=', $data[ 'site_id' ] ],
                [ 'name', '=', $data[ 'name' ] ]
            ]);
            $diy_view_info = $diy_view->getSiteDiyViewDetail($condition);
        }
        if (!empty($diy_view_info) && !empty($diy_view_info['data'])) {
            $diy_view_info = $diy_view_info['data'];
        }

        if (!empty($qrcode_info)) {
            $qrcode_info = $qrcode_info['data'] ?? [];
            // 目前只支持H5
            $h5_status = 0;
            if (isset($qrcode_info['path']) && isset($qrcode_info['path']['h5']) && isset($qrcode_info['path']['h5']['status'])) {
                $h5_status = $qrcode_info['path']['h5']['status'];
            }
            if ($h5_status != 1) {
                $qrcode_info = [];
            }
        }

        $diy_view_utils = [];
        if (!empty($utils['data'])) {

            // 先遍历，组件分类
            foreach ($utils['data'] as $k => $v) {
                $value = [];
                $value['type'] = $v['type'] ?? '';
                $value['type_name'] = $diy_view->getTypeName($v['type'] ?? '');
                $value['list'] = [];
                if (!in_array($value, $diy_view_utils)) {
                    array_push($diy_view_utils, $value);
                }
            }

            // 遍历每一个组件，将其添加到对应的分类中
            foreach ($utils['data'] as $k => $v) {
                foreach ($diy_view_utils as $diy_k => $diy_v) {
                    if (($diy_v['type'] ?? '') == ($v['type'] ?? '')) {
                        array_push($diy_view_utils[$diy_k]['list'], $v);
                    }
                }
            }
        }

        // 平台/城市分站端，组件的icon特殊处理
        if ($data['app_module'] == 'admin' || $data['app_module'] == 'city') {
            foreach ($diy_view_utils as $k => $v) {
                foreach ($v[ 'list' ] as $ck => $cv) {
                    if (!empty($cv[ 'icon' ])) {
                        $diy_view_utils[ $k ][ 'list' ][ $ck ][ 'icon' ] = str_replace('.png', '_admin.png', $cv[ 'icon' ]);
                    }
                    if (!empty($cv[ 'icon_selected' ])) {
                        $diy_view_utils[ $k ][ 'list' ][ $ck ][ 'icon_selected' ] = str_replace('.png', '_admin.png', $cv[ 'icon_selected' ]);
                    }
                }
            }
        }

        // 已知插件：【秒杀、团购、拼团、砍价、优惠券、分销、直播、门店、店铺笔记、城市分站】
        if (!empty($diy_view_info) && $data[ 'app_module' ] != 'city') {
            if (!empty($diy_view_info[ 'value' ])) {
                $json_data = json_decode($diy_view_info[ 'value' ], true);
                foreach ($json_data[ 'value' ] as $k => $v) {

                    // 检测自定义组件是否存在
                    $count = 0;
                    foreach ($utils[ 'data' ] as $ck => $cv) {
                        if ($cv[ 'name' ] == $v[ 'type' ]) {
                            $count++;
                        }
                    }
                    if ($count == 0) {
                        unset($json_data[ 'value' ][ $k ]);
                        continue;
                    }

                    // 检查插件是否存在
                    if (!empty($v[ 'addon_name' ])) {
                        $is_exit = addon_is_exit($v[ 'addon_name' ], $data[ 'site_id' ]);
                        if (!empty($data[ 'site_id' ])) {
                            if ($is_exit == 0 || !in_array($v[ 'addon_name' ], $addon_array)) {
                                unset($json_data[ 'value' ][ $k ]);
                                continue;
                            }
                        }
                    }
                }
                $json_data[ 'value' ] = array_values($json_data[ 'value' ]);
                $diy_view_info[ 'value' ] = json_encode($json_data);
            }
        }

        $this->assign("time", time());
        $this->assign("name", isset($data[ 'name' ]) ? $data[ 'name' ] : '');
        $this->assign("store_id", isset($data[ 'store_id' ]) ? $data[ 'store_id' ] : 0);

        // 禁止编辑页面设置（商品分类单页用）
        $this->assign("disabled_page_set", isset($data[ 'disabled_page_set' ]) ? $data[ 'disabled_page_set' ] : '');
        $this->assign("qrcode_info", $qrcode_info);
        $this->assign('diy_view_utils', $diy_view_utils);
        $this->assign("diy_view_info", $diy_view_info);

        $request_url = $data[ 'app_module' ] . '/diy/edit';

        $replace = [];
        if ($data[ 'app_module' ] == 'city') {
            $replace = [
                'CITY_CSS' => __ROOT__ . '/addon/city/city/view/public/css',
                'CITY_JS' => __ROOT__ . '/addon/city/city/view/public/js',
                'CITY_IMG' => __ROOT__ . '/addon/city/city/view/public/img',
            ];

            $request_url = $data[ 'app_module' ] . '://' . $data[ 'app_module' ] . '/diy/index';

            $this->assign("extend_base", 'addon/' . $data[ 'app_module' ] . '/' . $data[ 'app_module' ] . '/view/base.html');
        } else {
            $this->assign("extend_base", 'app/' . $data[ 'app_module' ] . '/view/base.html');
        }

        $this->assign("app_module", $data[ 'app_module' ]);
        $this->assign("request_url", $request_url);

        $template = dirname(realpath(__DIR__)) . '/admin/view/diy/edit.html';
        return $this->fetch($template, [], $replace);
    }

}