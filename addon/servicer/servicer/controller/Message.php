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

namespace addon\servicer\servicer\controller;

use addon\servicer\model\Dialogue;
use app\model\order\OrderCommon as OrderCommonModel;

/**
 * 消息相关
 * Class Message
 * @package addon\servicer\servicer\controller
 */
class Message extends BaseServicer
{
    /**
     * 消息记录
     */
    public function lists()
    {
        if (request()->isAjax()) {
            $page         = input('page', 1);
            $page_size    = input('page_size', PAGE_LIST_ROWS);
            $content_type = input('content_type', '');
            $search_text  = input('search_text', '');
            $start_date   = input('start_date', '');
            $end_date     = input('end_date', '');
            $member_id    = input('member_id', 0);

            $alias = 'sd';
            $join  = [
                ['member m', 'm.member_id = sd.member_id', 'left'],
                ['servicer s', 's.user_id = sd.servicer_id', 'left'],
                ['goods_sku gs', 'gs.sku_id = sd.goods_sku_id', 'left'],
                ['order o', 'o.order_id = sd.order_id', 'left'],
            ];

            $condition = [['sd.shop_id', '=', $this->site_id], ['sd.member_id', '=', $member_id]];
            if (!empty($start_date) && !empty($end_date)) {
                $condition[] = ['sd.add_time', 'between', [date_to_time($start_date), date_to_time($end_date)]];
            } elseif (!empty($start_date) && empty($end_date)) {
                $condition[] = ['sd.add_time', '>=', date_to_time($start_date)];
            } elseif (empty($start_date) && !empty($end_date)) {
                $condition[] = ['sd.add_time', '<=', date_to_time($end_date)];
            }
            if ($content_type !== '') {
                $condition[] = ['sd.content_type', '=', $content_type];
            }

            if (!empty($search_text)) {
                if ($content_type === '') {
                    $condition[] = ['sd.message|gs.sku_name|gs.keywords|o.order_no|o.order_name', 'like', '%' . $search_text . '%'];
                    $condition[] = ['sd.content_type', '<>',  Dialogue::CONTENTTYPE_IMAGE];
                } else {
                    switch ($content_type) {
                        case Dialogue::CONTENTTYPE_STRING:
                            $condition[] = ['sd.message', 'like', '%' . $search_text . '%'];
                            break;
                        case Dialogue::CONTENTTYPE_GOODSKU:
                            $condition[] = ['gs.sku_name|gs.keywords', 'like', '%' . $search_text . '%'];
                            break;
                        case Dialogue::CONTENTTYPE_ORDER:
                            $condition[] = ['o.order_no|o.order_name', 'like', '%' . $search_text . '%'];
                            break;
                    }
                }
            }

            $order_by = 'sd.id desc';
            $field    = [
                'sd.id, sd.member_id, sd.servicer_id, sd.add_time, sd.content_type, sd.consumer_say, sd.servicer_say',
                'goods_sku_id, sd.type',
                'm.nickname, m.headimg',
                's.nickname as servicer_nickname, s.avatar',
                'gs.goods_id, gs.sku_name, gs.sku_image, gs.price, gs.goods_state, gs.stock, gs.sale_num',
                'o.order_id, o.order_no, o.goods_money, o.delivery_money, o.order_money, o.order_status_name',
            ];

            $dialogue_model     = new Dialogue();
            $order_common_model = new OrderCommonModel();

            $res = $dialogue_model->getPageList($condition, $field, $order_by, $page, $page_size, $alias, $join);
            foreach ($res['list'] as $key => $val) {

                $order_goods_list = [];
                if ($val['content_type'] == Dialogue::CONTENTTYPE_ORDER && !empty($val['order_id'])) {
                    $order_goods_list = $order_common_model->getOrderGoodsList([
                        ['order_id', '=', $val['order_id']]
                    ], 'sku_id, goods_id, sku_name, sku_image, price, num')['data'];
                }
                $res['list'][$key]['order_goods_list'] = $order_goods_list;
            }
            return $dialogue_model->success($res);
        }
    }

    /**
     * 设置已读
     */
    public function setRead()
    {
        if (request()->isAjax()) {
            $dialogue_model = new Dialogue();
            return $dialogue_model->setDialoguesRead([
                ['member_id', '=', input('member_id', 0)],
                ['shop_id', '=', $this->site_id],
                ['type', '=', 0],
            ]);
        }
    }
}