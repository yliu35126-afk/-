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


namespace app\event;

use addon\pc\model\Pc;

/**
 * PC端导航
 * @author Administrator
 *
 */
class InitPcNav
{
    public function handle($data)
    {
        $link = [
            [
                'title' => '首页',
                'url' => '/index',
                'sort' => 1,
            ],
            [
                'title' => '品牌专区',
                'url' => '/brand',
                'sort' => 2,
            ],
            [
                'title' => '店铺街',
                'url' => '/street',
                'sort' => 3,
            ]
        ];
        if(addon_is_exit('pc')){
            $pc_model = new Pc();
            foreach ($link as $k => $v) {
                $pc_model->deleteNav([ [ 'nav_title', '=', $v[ 'title' ] ] ]);
                $sort = $v[ 'sort' ];
                unset($v[ 'sort' ]);
                $add_data = [
                    'nav_title' => $v[ 'title' ],
                    'nav_url' => json_encode($v, 320),
                    'sort' => $sort,
                    'is_blank' => 0,
                    'create_time' => time(),
                    'is_show' => 1
                ];
                $pc_model->addNav($add_data);
            }
        }


        return 1;
    }
}
