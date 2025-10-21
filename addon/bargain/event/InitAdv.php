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

namespace addon\bargain\event;

use app\model\web\Adv as AdvModel;
use app\model\web\AdvPosition;

/**
 * 广告
 * @author Administrator
 *
 */
class InitAdv
{
    public function handle($data)
    {
        $adv_position_model = new AdvPosition();
        $res = $adv_position_model->addAdvPosition([
            'ap_name' => '砍价专区',
            'keyword' => 'NS_BARGAIN',
            'ap_intro' => '',
            'ap_height' => '400',
            'ap_width' => '1920',
            'default_content' => '',
            'ap_background_color' => '#FFFFFF',
            'type' => 2,
        ]);

        $adv_model = new AdvModel();
        $adv_model->addAdv([
            'ap_id' => $res[ 'data' ],
            'adv_title' => '砍价专区',
            'adv_url' => '',
            'adv_image' => 'upload/default/diy_view/index_bargain_gg.png',
            'slide_sort' => 0,
            'price' => 0,
            'background' => '#FFFFFF'
        ]);

    }
}
