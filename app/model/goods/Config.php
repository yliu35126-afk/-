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

namespace app\model\goods;

use app\model\system\Config as ConfigModel;
use app\model\BaseModel;
use app\model\system\Document as DocumentModel;

/**
 * 商品设置
 */
class Config extends BaseModel
{

	/**
	 * 商品审核设置
	 * array $data
	 */
	public function setVerifyConfig($data)
	{
		$config = new ConfigModel();
		$res = $config->setConfig($data, '商品审核设置', 1, [ [ 'site_id', '=', 0 ], [ 'app_module', '=', 'shop' ], [ 'config_key', '=', 'GOODS_VERIFY_CONFIG' ] ]);
		return $res;
	}
	
	/**
	 * 查询商品审核设置
	 */
	public function getVerifyConfig()
	{
		$config = new ConfigModel();
		$res = $config->getConfig([ [ 'site_id', '=', 0 ], [ 'app_module', '=', 'shop' ], [ 'config_key', '=', 'GOODS_VERIFY_CONFIG' ] ]);
		if(empty($res['data']['value'])){
            $res['data']['value'] = array(
                'is_open' =>0
            );
        }
		return $res;
	}
	
//	/**
//	 * 获取售后保障设置
//	 */
//	public function getAfterSaleConfig()
//	{
//        $document = new DocumentModel();
//		$info = $document->getDocument([ [ 'site_id', '=', 0 ], [ 'app_module', '=', 'admin' ], [ 'document_key', '=', "GOODS_AFTER_SALE" ] ]);
//		return $info;
//	}
//    /**
//     * 设置售后保障
//     * @param unknown $content
//     */
//    public function setAfterSaleConfig($title,$content,$site_id, $is_display = 0)
//    {
//        $document = new DocumentModel();
//        $res = $document->setDocument($title, $content, [ [ 'site_id', '=', 0 ], [ 'app_module', '=', 'admin' ], [ 'document_key', '=', "GOODS_AFTER_SALE" ] ]);
//        return $res;
//    }

    /**
     * 获取售后保障设置
     */
    public function getAfterSaleConfig($site_id = 0)
    {
        $document = new DocumentModel();
        $info     = $document->getDocument([['site_id', '=', $site_id], ['app_module', '=', 'shop'], ['document_key', '=', "GOODS_AFTER_SALE"]]);
        $config = (new ConfigModel())->getConfig([['site_id', '=', $site_id], ['app_module', '=', 'shop'], ['config_key', '=', 'GOODS_AFTER_SALE']]);
        $info['data']['is_display'] = empty($config['data']['value']) ? 0 : $config['data']['value']['is_display'];
        return $info;
    }

    /**
     * 设置售后保障
     * @param $title
     * @param $content
     * @param $site_id
     * @return \app\model\system\multitype
     */
    public function setAfterSaleConfig($title, $content, $site_id, $is_display = 0)
    {
        $document = new DocumentModel();
        $res      = $document->setDocument($title, $content, [['site_id', '=', $site_id], ['app_module', '=', 'shop'], ['document_key', '=', "GOODS_AFTER_SALE"]]);
        (new ConfigModel())->setConfig(['is_display' => $is_display], '售后保障是否显示', 1, [['site_id', '=', $site_id], ['app_module', '=', 'shop'], ['config_key', '=', 'GOODS_AFTER_SALE']]);
        return $res;
    }
	
}