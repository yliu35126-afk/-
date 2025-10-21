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

namespace app\admin\controller;

use app\model\shop\ShopGroup as ShopGroupModel;
use app\model\system\Promotion as PromotionModel;

/**
 * 开店套餐管理 控制器
 */
class Shopgroup extends BaseAdmin
{
    /**
     * 分组列表
     */
    public function lists()
    {
        if (request()->isAjax()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $search_text = input('search_text', '');

            $condition = [];
            if(!empty($search_text))
            {
                $condition[] = [ 'group_name', 'like', '%' . $search_text . '%' ];
            }

            $order = 'is_own desc,fee asc';
            $field = '*';
            $shop_group_model = new ShopGroupModel();

            //group_name 分组名称 fee 年费 is_own 是否自营 remark 备注 create_time
            return $shop_group_model->getGroupPageList($condition, $page, $page_size, $order, $field);

        } else {
            return $this->fetch('shopgroup/lists');
        }
    }

    /**
     * 分组添加
     */
    public function addGroup()
    {
        $promotion_model = new PromotionModel();
        $promotions = $promotion_model->getPromotions();
        if (request()->isAjax()) {
            $data = [
                'is_own' => input('is_own', 0),//是否自营
                'group_name' => input('group_name', ''),//分组名称
                'fee' => input('fee', 0.00),//年费
                'addon_array' => input('addon_array', ''),//营销插件权限组
                'remark' => input('remark', ''),//备注
                'create_time' => time(),
                'menu_array' => ''
            ];
            $shop_group_model = new ShopGroupModel();
            $this->addLog("添加开店套餐:" . $data[ 'group_name' ] . ",金额:" . $data[ "fee" ]);
            return $shop_group_model->addGroup($data);
        } else {
            foreach ($promotions[ 'shop' ] as $key => $promotion) {
                if (!empty($promotion[ 'is_developing' ])) {
                    unset($promotions[ 'shop' ][ $key ]);
                    continue;
                }
            }
            $this->assign("promotions", $promotions[ 'shop' ]);
            return $this->fetch('shopgroup/add_group');
        }

    }

    /**
     * 分组编辑
     */
    public function editGroup()
    {
        $shop_group_model = new ShopGroupModel();
        $promotion_model = new PromotionModel();
        $promotions = $promotion_model->getPromotions();
        $promotions = $promotions[ 'shop' ];
        if (request()->isAjax()) {
            $data = [
                'is_own' => input('is_own', 0),
                'group_name' => input('group_name', ''),
                'fee' => input('fee', 0.00),
                'addon_array' => input('addon_array', ''),
                'remark' => input('remark', ''),
                'modify_time' => time(),
                'group_id' => input('group_id', 0),
                'menu_array' => ''
            ];
            $this->addLog("编辑开店套餐:" . $data[ 'group_name' ] . ",金额:" . $data[ "fee" ]);
            return $shop_group_model->editGroup($data);
        } else {
            $group_id = input('group_id', 0);
            $group_info = $shop_group_model->getGroupInfo([ [ 'group_id', '=', $group_id ] ]);
            $addon_array = !empty($group_info[ 'data' ][ 'addon_array' ]) ? explode(',', $group_info[ 'data' ][ 'addon_array' ]) : [];
            foreach ($promotions as $key => &$promotion) {
                if (!empty($promotion[ 'is_developing' ])) {
                    unset($promotions[ $key ]);
                    continue;
                }
                $promotion[ 'is_checked' ] = 0;
                if (in_array($promotion[ 'name' ], $addon_array)) {
                    $promotion[ 'is_checked' ] = 1;
                }
            }

            $this->assign('group_info', $group_info);
            $this->assign("promotions", $promotions);
            return $this->fetch('shopgroup/edit_group');
        }

    }

    /**
     * 分组删除
     */
    public function deleteGroup()
    {
        $group_ids = input('group_ids', '');
        $shop_group_model = new ShopGroupModel();
        $this->addLog("删除开店套餐id:" . $group_ids);
        return $shop_group_model->deleteGroup([ [ 'group_id', 'in', $group_ids ] ]);
    }
}