<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */


namespace addon\weapp\event;

use addon\weapp\model\Weapp;
/**
 * 二维码
 */
class Qrcode
{
	/**
	 * 二维码生成获取
	 */
	public function handle($param)
	{
	    if ($param["app_type"] == 'weapp' || $param["app_type"] == 'all') {
	        if ($param["app_type"] == 'all') $param["app_type"] = 'weapp';
            $weapp = new Weapp();
            if ($param["type"] == 'create') {
                $res = $weapp->createQrcode($param);
            } else {
                $filename = $param['qrcode_path'] . '/' . $param['qrcode_name'] . '_' . $param['app_type'] . '.png';
                if (file_exists($filename)) {
                    $res = success(0, '', ['path' => $filename]);
                } else {
                    $res = $weapp->createQrcode($param);
                }
            }             
    	    return $res;
	    }
	}
}