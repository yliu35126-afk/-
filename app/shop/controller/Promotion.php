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

use app\model\shop\ShopGroup as ShopGroupModel;
use app\model\system\Promotion as PromotionModel;

/**
 * 营销
 * Class Promotion
 * @package app\shop\controller
 */
class Promotion extends BaseShop
{


    public function __construct()
    {
        //执行父类构造函数
        parent::__construct();

    }

    /**
     * 营销中心
     * @return mixed
     */
    public function index()
    {
        $promotion_model = new PromotionModel();
        $promotions = $promotion_model->getPromotions();
        $shop_group_model = new ShopGroupModel();
        $addon_array = $shop_group_model->getGroupInfo([ 'group_id' => $this->shop_info[ 'group_id' ] ], 'addon_array');
        $addon_array = explode(',', $addon_array[ 'data' ][ 'addon_array' ]);
        foreach ($promotions[ 'shop' ] as $key => $promotion) {
            if (!in_array($promotion[ 'name' ], $addon_array) && empty($promotion[ 'is_developing' ])) {
                unset($promotions[ 'shop' ][ $key ]);
            }
        }
        $this->assign("promotion", $promotions[ 'shop' ]);
        return $this->fetch("promotion/index");
    }

    /**
     * 平台营销
     * @return mixed
     */
    public function platform()
    {
        $promotion_model = new PromotionModel();
        $promotions = $promotion_model->getPromotions();
        $shop_group_model = new ShopGroupModel();
        $addon_array = $shop_group_model->getGroupInfo([ 'group_id' => $this->shop_info[ 'group_id' ] ], 'addon_array');
        $addon_array = explode(',', $addon_array[ 'data' ][ 'addon_array' ]);
        foreach ($promotions[ 'shop' ] as $key => $promotion) {
            if (!in_array($promotion[ 'name' ], $addon_array) && empty($promotion[ 'is_developing' ])) {
                unset($promotions[ 'shop' ][ $key ]);
            }
        }
        $this->assign("promotion", $promotions[ 'shop' ]);
        return $this->fetch("promotion/platform");
    }

    /**
     * 会员营销
     * @return mixed
     */
    public function member()
    {
        $promotion_model = new PromotionModel();
        $promotions = $promotion_model->getPromotions();
        $shop_group_model = new ShopGroupModel();
        $addon_array = $shop_group_model->getGroupInfo([ 'group_id' => $this->shop_info[ 'group_id' ] ], 'addon_array');
        $addon_array = explode(',', $addon_array[ 'data' ][ 'addon_array' ]);
        foreach ($promotions[ 'shop' ] as $key => $promotion) {
            if (!in_array($promotion[ 'name' ], $addon_array) && empty($promotion[ 'is_developing' ])) {
                unset($promotions[ 'shop' ][ $key ]);
            }
        }
        $this->assign("promotion", $promotions[ 'shop' ]);
        return $this->fetch("promotion/member");
    }

    /**
     * 营销工具
     * @return mixed
     */
    public function tool()
    {
        $promotion_model = new PromotionModel();
        $promotions = $promotion_model->getPromotions();
        $shop_group_model = new ShopGroupModel();
        $addon_array = $shop_group_model->getGroupInfo([ 'group_id' => $this->shop_info[ 'group_id' ] ], 'addon_array');
        $addon_array = explode(',', $addon_array[ 'data' ][ 'addon_array' ]);
        foreach ($promotions[ 'shop' ] as $key => $promotion) {
            if (!in_array($promotion[ 'name' ], $addon_array) && empty($promotion[ 'is_developing' ])) {
                unset($promotions[ 'shop' ][ $key ]);
            }
        }
        $this->assign("promotion", $promotions[ 'shop' ]);
        return $this->fetch("promotion/tool");
    }
}