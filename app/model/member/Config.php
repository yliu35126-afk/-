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

namespace app\model\member;

use app\model\system\Document;
use app\model\system\Config as ConfigModel;
use app\model\BaseModel;

/**
 * 会员设置
 */
class Config extends BaseModel
{
	/**
	 * 注册协议
	 * @param unknown $site_id
	 * @param unknown $name
	 * @param unknown $value
	 */
	public function setRegisterDocument($title, $content)
	{
		$document = new Document();
		$res = $document->setDocument($title, $content, [ [ 'site_id', '=', 0 ], [ 'app_module', '=', 'admin' ], [ 'document_key', '=', 'REGISTER_AGREEMENT' ] ]);
		return $res;
	}

	/**
	 * 查询注册协议
	 * @param unknown $where
	 * @param unknown $field
	 * @param unknown $value
	 */
	public function getRegisterDocument()
	{
		$document = new Document();
		$info = $document->getDocument([ [ 'site_id', '=', 0 ], [ 'app_module', '=', 'admin' ], [ 'document_key', '=', 'REGISTER_AGREEMENT' ] ]);
		return $info;
	}

	/**
	 * 注册规则
	 * array $data
	 */
	public function setRegisterConfig($data)
	{
		$config = new ConfigModel();
		$res = $config->setConfig($data, '注册规则', 1, [ [ 'site_id', '=', 0 ], [ 'app_module', '=', 'admin' ], [ 'config_key', '=', 'REGISTER_CONFIG' ] ]);
		return $res;
	}

	/**
	 * 查询注册规则
	 */
	public function getRegisterConfig()
	{
		$config = new ConfigModel();
		$res = $config->getConfig([ [ 'site_id', '=', 0 ], [ 'app_module', '=', 'admin' ], [ 'config_key', '=', 'REGISTER_CONFIG' ] ]);
        if (empty($res['data']['value'])) {
            //默认值设置
            $res['data']['value'] = [
                'login' => 'username,mobile',
                'register' => 'username,mobile',
                'third_party' => 0,
                'bind_mobile' => 0,
                'pwd_len' => 6,
                'pwd_complexity' => ''
            ];
        } else {
            $value = $res['data']['value'];
            $value['login'] = $value['login'] ?? 'username,mobile';
            $value['register'] = $value['register'] ?? 'username,mobile';
            $value['third_party'] = $value['third_party'] ?? 0;
            $value['bind_mobile'] = $value['bind_mobile'] ?? 0;
            $res['data']['value'] = $value;
        }
		return $res;
	}


    /**
     * 注销协议
     * @param unknown $site_id
     * @param unknown $name
     * @param unknown $value
     */
    public function setCancelDocument($title, $content)
    {
        $document = new Document();
        $res = $document->setDocument($title, $content, [['site_id', '=', 0], ['app_module', '=', 'admin'], ['document_key', '=', 'CANCEL_AGREEMENT']]);
        return $res;
    }

    /**
     * 查询注销协议
     * @param unknown $where
     * @param unknown $field
     * @param unknown $value
     */
    public function getCancelDocument()
    {
        $document = new Document();
        $info = $document->getDocument([['site_id', '=', 0], ['app_module', '=', 'admin'], ['document_key', '=', 'CANCEL_AGREEMENT']]);
        return $info;
    }


    /**
     * 注销规则
     * array $data
     */
    public function setCancelConfig($data)
    {
        $config = new ConfigModel();
        $res = $config->setConfig($data, '注销规则', 1, [['site_id', '=', 0], ['app_module', '=', 'admin'], ['config_key', '=', 'CANCEL_CONFIG']]);
        return $res;
    }

    /**
     * 查询注销规则
     */
    public function getCancelConfig()
    {
        $config = new ConfigModel();
        $res = $config->getConfig([['site_id', '=', 0], ['app_module', '=', 'admin'], ['config_key', '=', 'CANCEL_CONFIG']]);
        if (empty($res['data']['value'])) {
            //默认值设置
            $res['data']['value'] = [
                'is_enable' => 0,  //注销开关
                'is_audit' => 1, //审核开关
            ];
        }
        return $res;
    }
}