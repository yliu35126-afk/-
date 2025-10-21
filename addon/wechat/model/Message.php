<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\wechat\model;

use app\model\BaseModel;
use addon\weapp\model\Config as WeappConfig;
use app\model\web\WebSite as WebsiteModel;
/**
 * 微信消息模板
 */
class Message extends BaseModel
{
    /**
     * 发送模板消息
     * @param array $param
     */
	public function sendMessage(array $param) {
	    $support_type = $data["support_type"] ?? [];
	    //验证是否支持邮箱发送
	    if(!empty($support_type) && !in_array("wechat", $support_type)) return $this->success();
	    
	    if (empty($param['openid'])) return $this->success('缺少必需参数openid');
	    
	    $message_info = $param['message_info'];
	    if (!isset($message_info['wechat_is_open']) || $message_info['wechat_is_open'] == 0) return $this->error('未启用模板消息');
	    
	    $wechat_info = json_decode($message_info['wechat_json'], true);
	    if (!isset($wechat_info['template_id']) || empty($wechat_info['template_id'])) return $this->error('未配置模板消息');
        

	    $template_data = [
            'first' => [
                'value' => $wechat_info['headtext'],
                'color' => !empty($wechat_info['headtextcolor']) ? $wechat_info['headtextcolor'] : '#f00'
            ],
	        'remark' => [
	            'value' => $wechat_info['bottomtext'],
	            'color' => !empty($wechat_info['bottomtextcolor']) ? $wechat_info['bottomtextcolor']: '#333'
	        ]
	    ];
	    if (!empty($param['template_data'])) $template_data = array_merge($template_data, $param['template_data']);
	    
	    
	    $data = [
	        'openid' => $param['openid'],
	        'template_id' => $wechat_info['template_id'],
	        'data' => $template_data,
	        'miniprogram' => [],
	        'url' => ''
	    ];
	    
	    if (!empty($param['page'])) {
	        // 小程序配置
	        $weapp_config = new WeappConfig();
	        $weapp_config_result = $weapp_config->getWeAppConfig();
	        $weapp_config = $weapp_config_result['data']["value"];
	        
    	    if (!empty($weapp_config['appid'])) {
    	        $data['miniprogram'] = [
    	            'appid' => $weapp_config['appid'],
    	            'pagepath' => $param['page']
    	        ];
    	    }

            $h5_domain = getH5Domain();
            $data['url'] = $h5_domain .'/'. $param['page'];
	    }
	    $wechat = new Wechat();
	    $res = $wechat->sendTemplateMessage($data);
	    return $res;
	}
}