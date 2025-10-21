<?php
namespace addon\wechatpay\event;

use addon\wechatpay\model\Config;
use addon\wechatpay\model\V3;
use app\model\shop\ShopAccount;

class ShopTransferResult
{
    public function handle(array $params)
    {
        $withdraw_info = (new ShopAccount())->getShopWithdrawInfo([ ['id','=', $params['relate_id']] ], 'id,site_id,withdraw_no')['data'];
        if (!empty($withdraw_info)) {
            $withdraw_info['type'] = 'shop';
            $pay_config = (new Config())->getPayConfig()['data']['value'];
            if (!empty($pay_config)) {
                (new V3($pay_config))->getTransferResult($withdraw_info);
            }
        }
    }
}