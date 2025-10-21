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

namespace addon\shopwithdraw\model;

use app\model\BaseModel;
use app\model\shop\ShopAccount;

/**
 * 店铺提现
 */
class Withdraw extends BaseModel
{


    /**
     * 转账
     * @param $condition
     */
    public function transfer($id)
    {
        $shop_account_model = new ShopAccount();

        $info_result = $shop_account_model->getShopWithdrawInfo([ [ "id", "=", $id ] ]);
        if (empty($info_result[ "data" ]))
            return $this->error();

        $info = $info_result[ "data" ];
        $transfer_type = $this->getBankType()[ $info[ "bank_type" ] ];
        $pay_data = array (
            "id" => $id,
            "out_trade_no" => $info[ "withdraw_no" ],
            "real_name" => $info[ "settlement_bank_account_name" ],
            "amount" => $info[ "money" ],
            "desc" => "店铺提现" . $info[ "memo" ],
            "transfer_type" => $transfer_type,
            "account_number" => $info[ "settlement_bank_account_number" ],
            "type" => "shop"
        );

        //调用在线转账借口
        $pay_result = event("PayTransfer", $pay_data, true);
        if (empty($pay_result)) {
            $pay_result = $this->error();
        }
        if ($pay_result[ "code" ] < 0) {
            return $pay_result;
        }
        //调用完成转账
        $result = $shop_account_model->shopWithdrawPass($id, []);
        return $result;

    }

    /**
     * 转账方式
     */
    public function getTransferType()
    {
        $data = array (
            "bank" => "银行卡"
        );
        $temp_array = event("TransferType", []);

        if (!empty($temp_array)) {
            foreach ($temp_array as $k => $v) {
                $data[ $v[ "type" ] ] = $v[ "type_name" ];
            }
        }
        return $data;
    }


    /**
     * 无意义函数
     */
    public function getBankType()
    {
        $bank_type = array (
            1 => "bank",
            2 => "alipay",
            3 => "wechatpay",
        );
        return $bank_type;
    }
}