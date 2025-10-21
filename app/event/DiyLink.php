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
use app\model\goods\GoodsCategory;
use app\model\web\DiyViewLink;

/**
 * 自定义链接
 */
class DiyLink extends Controller
{
    // 行为扩展的执行入口必须是run
    public function handle($data)
    {
        $link = input("link", '');
        if (!empty($link) && $link != '[]') {
            $link = json_decode($link, true);
        } else {
            $link = [];
        }

        $support_diy_view = input("support_diy_view", '');//支持的自定义页面（为空表示都支持）
        $link_model = new DiyViewLink();
        $condition = [
            [ 'parent', '=', '' ]
        ];
        $list_result = $link_model->getLinkList($condition, '*', 'sort ASC');
        $list = $list_result[ 'data' ];
        foreach ($list as $k => $v) {

            $child_condition = [
                [ 'parent', '=', $v[ 'name' ] ]
            ];
            $child_list_result = $link_model->getLinkList($child_condition, '*', 'sort ASC');
            $child_list = $child_list_result[ 'data' ];
            $list[ $k ][ 'child_list' ] = $child_list;
        }
        $this->assign('list', $list);
        $this->assign("link", $link);
        $this->assign("support_diy_view", $support_diy_view);
        $this->assign("app_module", $data[ 'app_module' ]);

        $replace = [];

        $template = dirname(realpath(__DIR__)) . '/admin/view/commonutil/link.html';

        $goods_category_model = new GoodsCategory();
        $category_condition = [];
        $category_list = $goods_category_model->getCategoryTree($category_condition);
        $category_list = $category_list[ 'data' ];
        $this->assign("category_list", $category_list);
        return $this->fetch($template, [], $replace);
    }

}