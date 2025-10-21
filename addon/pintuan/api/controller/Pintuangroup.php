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

namespace addon\pintuan\api\controller;

use addon\pintuan\model\PintuanGroup as PintuanGroupModel;
use app\api\controller\BaseApi;

/**
 * 拼团组
 */
class Pintuangroup extends BaseApi
{

    /**
     * 列表信息
     */
    public function lists()
    {
        $goods_id = isset($this->params['goods_id']) ? $this->params['goods_id'] : 0;
        if (empty($goods_id)) {
            return $this->response($this->error('', 'REQUEST_GOODS_ID'));
        }

        $pintuan_group_model = new PintuanGroupModel();
        $condition = [
            ['ppg.goods_id', '=', $goods_id],
            ['ppg.status', '=', 2],// 当前状态:0未支付 1拼团失败 2.组团中3.拼团成功
        ];
        $list = $pintuan_group_model->getPintuanGoodsGroupList($condition);
        return $this->response($list);
    }

}