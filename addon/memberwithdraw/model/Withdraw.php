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

namespace addon\memberwithdraw\model;

use app\model\BaseModel;
use app\model\member\Withdraw as MemberWithdraw;

/**
 * 会员提现
 */
class Withdraw extends BaseModel
{
	

	
	/**
	 * 转账
	 * @param $condition
	 */
	public function transfer($id)
	{
            $withdraw_model = new MemberWithdraw();
            $info_result = $withdraw_model->getMemberWithdrawInfo([ [ "id", "=", $id ] ], "withdraw_no,account_number,realname,money,memo,transfer_type");
            if (empty($info_result["data"]))
                return $this->error();

            $info = $info_result["data"];
		if(!in_array($info["transfer_type"], ["wechatpay","alipay"]))
		    return $this->error('', "当前提现方式不支持在线转账");


		$pay_data = array(
		    "id" => $id,
			"out_trade_no" => $info["withdraw_no"],
			"real_name" => $info["realname"],
			"amount" => $info["money"],
			"desc" => "会员提现".$info["memo"],
			"transfer_type" => $info["transfer_type"],
			"account_number" => $info["account_number"],
            "type" => "member"
		);
		//调用在线转账借口
		$pay_result = event("PayTransfer", $pay_data, true);
		if (empty($pay_result)) {
			$pay_result = $this->error();
		}
		if ($pay_result["code"] < 0) {
			return $pay_result;
		}
		//调用完成转账
		$result = $withdraw_model->transferFinish([ [ "id", "=", $id ] ]);
		return $result;
		
	}
	

}