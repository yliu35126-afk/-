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

namespace addon\fenxiao\admin\controller;

use app\admin\controller\BaseAdmin;

/**
 * 分销市场
 */
class Market extends BaseAdmin
{
    /**
     * 分销市场
     */
    public function index()
    {
        // 查询公共组件和支持的页面
        $condition = [
            [ 'support_diy_view', 'like', [ 'DIY_FENXIAO_MARKET', '%' . 'DIY_FENXIAO_MARKET' . ',%', '%' . 'DIY_FENXIAO_MARKET', '%,' . 'DIY_FENXIAO_MARKET' . ',%', '' ], 'or' ],
            [ 'addon_name', 'not in', [ 'seckill', 'pintuan', 'groupbuy', 'live', 'bargain', 'wholesale' ], 'or' ],
        ];
        $data = [
            'app_module' => 'admin',
            'site_id' => $this->site_id,
            'name' => 'DIY_FENXIAO_MARKET',
            'condition' => $condition
        ];
        $edit_view = event('DiyViewEdit', $data, true);
        return $edit_view;
    }

}