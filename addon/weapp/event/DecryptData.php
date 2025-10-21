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
 * 开放数据解密
 */
class DecryptData
{
    /**
     * 执行安装
     * @param array $param
     * @return array
     */
    public function handle($param = [])
    {
        if ($param['app_type'] == 'weapp') {
            $weapp = new Weapp();
            return $weapp->decryptData($param);
        }
    }
}