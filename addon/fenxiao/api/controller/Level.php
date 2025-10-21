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

namespace addon\fenxiao\api\controller;

use addon\fenxiao\model\FenxiaoLevel;
use app\api\controller\BaseApi;


/**
 * 分销等级
 */
class Level extends BaseApi
{

    /**
     * 分销商等级列表
     */
    public function lists()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $condition = [
//			[ 'status', '=', 1 ]
        ];
        $model = new FenxiaoLevel();
        $info = $model->getLevelList($condition, 'level_id,level_num,level_name,one_rate,two_rate,three_rate,upgrade_type,fenxiao_order_num,fenxiao_order_meney,one_fenxiao_order_num,one_fenxiao_order_money,order_num,order_money,child_num,child_fenxiao_num,one_child_num,one_child_fenxiao_num', 'one_rate asc');

        return $this->response($info);
    }

}