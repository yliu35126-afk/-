<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\shopcomponent\admin\controller;

use addon\shopcomponent\model\Goods as GoodsModel;
use app\admin\controller\BaseAdmin;
use addon\shopcomponent\model\Video as VideoModel;
use app\model\shop\Shop as ShopModel;

/**
 *
 */
class Video extends BaseAdmin
{
    /**
     * @return array|mixed
     * 申请视频号 微信 列表页
     */
    public function lists()
    {
        if (request()->isAjax()) {
            $video_model = new VideoModel();

            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $status = input('status', '');
            $wx_username = input('wx_username', '');
            $site_id = input('site_id', '');
            $name = input('name', '');

            $condition = [];
            if ($site_id) {
                $condition[] = [ 'v.site_id', '=', $site_id ];
            }
            if ($status) {
                $condition[] = [ 'v.status', '=', $status ];
            }
            if ($name) {
                $condition[] = [ 'v.name', 'like', '%' . $name . '%' ];
            }
            if ($wx_username) {
                $condition[] = [ 'v.wx_username', 'like', '%' . $wx_username . '%' ];
            }
            $field = 'v.*,s.site_name';
            $alias = 'v';
            $join = [
                ['shop s', 's.site_id = v.site_id', 'inner'],
            ];

            $data = $video_model->getVideoPageList($condition, $field, 'id desc', $page, $page_size,$alias,$join);

            return $data;
        } else {
            //商家主营行业
            $shop_model = new ShopModel();
            $shop_list = $shop_model->getShopList([], 'site_id, site_name', 'sort asc');
            $this->assign('shop_list', $shop_list[ 'data' ]);
            
            return $this->fetch("video/index");
        }
    }

    /**
     * @return array
     * 审核成功
     */
    public function pass()
    {
        $id = input('id', 0);
        $video_model = new VideoModel();
        return $video_model->videoPass($id);
    }

    /**
     * @return mixed
     * 审核失败
     */
    public function refuse()
    {
        $id = input('id', 0);
        $reason = input('reason', '');
        $video_model = new VideoModel();
        return $video_model->videoReject($id, $reason);
    }
}