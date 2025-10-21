<?php

/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\api\controller;

use addon\weapp\model\Config as ConfigModel;
use app\model\web\WebSite as WebsiteModel;

/**
 * 网站设置
 * @author Administrator
 *
 */
class Website extends BaseApi
{
	/**
	 * 基础信息
	 */
	public function info()
	{
		$site_id = isset($this->params['site_id']) ? $this->params['site_id'] : 0;
		$filed = 'title,logo,desc,web_qrcode,web_status,close_reason,wap_status,keywords,web_email,web_phone';
		$website_model = new WebsiteModel();
		$info = $website_model->getWebSite([['site_id', '=', $site_id]], $filed);
		if(addon_is_exit('weapp')){
            $weapp_model = new ConfigModel();
            $weapp_config_result = $weapp_model->getWeAppConfig($site_id);
            $web_qrcode = $weapp_config_result['data']["value"]['qrcode'] ?? '';
            if(!empty($web_qrcode)){
                $info['data']['web_qrcode'] = $web_qrcode;
            }
        }
		return $this->response($info);
	}

	public function wapQrcode()
	{
		$site_id = isset($this->params['site_id']) ? $this->params['site_id'] : 0;
		$website_model = new WebsiteModel();
		$info = $website_model->qrcode($site_id);
		return $this->response($info);
	}

    /**
     * 通过地址获取城市分站id
     * @return false|string
     */
	public function getWebsiteidByAddress(){
        $web_city = isset($this->params[ 'web_city' ]) ? $this->params[ 'web_city' ] : "";
        $website_model = new WebsiteModel();
        $info = $website_model->getWebSite([['site_area_id', '=', $web_city]], '*');
        return $this->response($info);
    }

    /**
     * 获取城市分站信息
     * @return false|string
     */
    public function getWebsiteInfo(){
        $site_id = isset($this->params['site_id']) ? $this->params['site_id'] : 0;
        $filed = 'title,logo,desc,web_qrcode,web_status,close_reason,wap_status,keywords,web_email,web_phone,site_area_id,status';
        $website_model = new WebsiteModel();
        $info = $website_model->getWebSite([['site_id', '=', $site_id]], $filed);
        return $this->response($info);
    }
}
