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
use app\model\web\DiyView as DiyViewModel;
use app\model\web\DiyViewLink;

/**
 * 自定义子级链接
 */
class DiyChildLink extends Controller
{
    // 行为扩展的执行入口必须是run
    public function handle($data)
    {

        $link = input("link", []);
        $support_diy_view = isset($data[ 'support_diy_view' ]) ? $data[ 'support_diy_view' ] : '';//支持的自定义页面（为空表示都支持）
        $name = input('name', '');
        $is_array = true;//记录是否是数组，后续判断受该变量影响
        if (!empty($link)) {
            $link = json_decode($link, true);
            $is_array = is_array($link);
        }
        $condition = [
            [ 'parent', '=', $name ],
            [ 'support_diy_view', 'like', [ $support_diy_view, '' ], 'or' ]
        ];
        $link_model = new DiyViewLink();
        $list_result = $link_model->getLinkList($condition, '*', 'sort ASC');
        $list = $list_result[ 'data' ];

        $temp_link = [];

        foreach ($list as $k => $v) {
            $child_condition = [
                [ 'parent', '=', $v[ 'name' ] ],
                [ 'support_diy_view', 'like', [ $support_diy_view, '' ], 'or' ]
            ];
            $child_list_result = $link_model->getLinkList($child_condition, '*', 'sort ASC');
            $child_list = $child_list_result[ 'data' ];

            foreach ($child_list as $item => $value) {
                if ($value[ 'addon_name' ] == '') {

                    if (!empty($link) && $is_array && $link[ 'name' ] == $value[ 'name' ]) {
                        //对象方式匹配
                        $child_list[ $item ][ 'selected' ] = true;
                    } elseif (!empty($link) && !$is_array && strtolower($link) == strtolower($value[ 'wap_url' ])) {
                        //字符串方式匹配
                        $child_list[ $item ][ 'selected' ] = true;
                        $temp_link = $value;
                    } else {
                        $child_list[ $item ][ 'selected' ] = false;
                    }
                }
            }
            $list[ $k ][ 'child_list' ] = $child_list;
        }
        if (!$is_array) {
            $link = $temp_link;
        }

        if ($name == 'MIC_PAGE') {
            // 遍历微页面
            $diy_view_model = new DiyViewModel();
            $page = $diy_view_model->getPage();
            $condition = [
                [ 'sdv.site_id', '=', $data[ 'site_id' ] ],
                [ 'sdv.type', '=', $page[ $data[ 'app_module' ] ][ 'port' ] ],
                [ 'sdv.name', 'like', '%DIY_VIEW_RANDOM_%' ]
            ];
            $site_diy_view_list = $diy_view_model->getSiteDiyViewPageList($condition, 1, 0, "sdv.sort desc,sdv.create_time desc");
            $site_diy_view_list = $site_diy_view_list[ 'data' ][ 'list' ];
            $link_mic = [
                'id' => 0,
                'addon_name' => '',
                'name' => 'MIC_PAGE',
                'title' => '微页面',
                'parent' => 'MALL_LINK',
                'sort' => 1,
                'level' => 3,
                'web_url' => '',
                'wap_url' => '',
                'icon' => '',
                'support_diy_view' => '',
                'child_list' => []
            ];
            foreach ($site_diy_view_list as $page_k => $page_v) {
                $title = $page_v[ 'title' ];
                $item = [
                    'id' => $page_v[ 'id' ],
                    'name' => $page_v[ 'name' ],
                    'title' => $title,
                    'addon_icon' => "",
                    'addon_name' => isset($page_v[ 'addon_name' ]) ? $page_v[ 'addon_name' ] : '',
                    'addon_title' => '',
                    'web_url' => '',
                    'wap_url' => '/otherpages/diy/diy/diy?name=' . $page_v[ 'name' ],
                    'icon' => '',
                    'type' => 0,
                    'diy' => 1
                ];

                if (!empty($link) && $is_array && $link[ 'name' ] == $page_v[ 'name' ]) {
                    //对象方式匹配
                    $item[ 'selected' ] = true;
                } elseif (!empty($link) && !$is_array && strtolower($link) == strtolower($page_v[ 'wap_url' ])) {
                    //字符串方式匹配
                    $item[ 'selected' ] = true;
                } else {
                    $item[ 'selected' ] = false;
                }
                array_push($link_mic[ 'child_list' ], $item);
            }
            $list[] = $link_mic;
        }

        return [
            'list' => $list,
            'link' => $link
        ];
    }

}