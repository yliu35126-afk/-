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

namespace addon\city\city\controller;

use app\model\web\WebSite as WebsiteModel;

/**
 * 跳转页
 */
class Website extends BaseCity
{

    /**
     * 编辑城市分站
     */
    public function config()
    {
        $website_model = new WebsiteModel();
        if (request()->isAjax()) {

            $data = [
                'title' => input('title', ''),
                'logo' => input('logo', ''),
                'desc' => input('desc', ''),
                'keywords' => input('keywords', ''),
                'web_address' => input('web_address', ''),
                'web_qrcode' => input('web_qrcode', ''),
                'web_email' => input('web_email', ''),
                'web_phone' => input('web_phone', ''),
                'web_qq' => input('web_qq', ''),
                'web_weixin' => input('web_weixin', ''),
                'wap_domain' => input('wap_domain', ''),
            ];

            $condition[] = [ 'site_id', '=', $this->site_id ];
            $res = $website_model->setWebSite($data, $condition);
            return $res;
        } else {
            return $this->fetch('website/config', [], $this->replace);
        }
    }

}