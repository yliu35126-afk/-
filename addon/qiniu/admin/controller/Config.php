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

namespace addon\qiniu\admin\controller;

use app\admin\controller\BaseAdmin;
use addon\qiniu\model\Config as ConfigModel;

/**
 * 七牛云上传管理
 */
class Config extends BaseAdmin
{
	
	/**
	 * 云上传配置
	 * @return mixed
	 */
	public function config()
	{
		$config_model = new ConfigModel();
		if (request()->isAjax()) {
			$bucket = input("bucket", "");
			$access_key = input("access_key", "");
			$secret_key = input("secret_key", "");
			$domain = input("domain", "");
			$status = input("status", 0);
			$data = array(
				"bucket" => $bucket,
				"access_key" => $access_key,
				"secret_key" => $secret_key,
				"domain" => $domain,
			);
			
			$result = $config_model->setQiniuConfig($data, $status);
			return $result;
		} else {
			$info_result = $config_model->getQiniuConfig();
			$info = $info_result["data"];
			$this->assign("info", $info);
			return $this->fetch("config/config");
		}
	}
}