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

use addon\sitemessage\model\MemberMessage;
use addon\weapp\model\Message as WeappMessage;
use addon\wechat\model\Message as WechatMessage;
use app\model\BaseModel;
use app\model\message\Email;
use app\model\message\Message;
use app\model\message\Message as MessageModel;
use app\model\message\Sms;
use app\model\shop\ShopAcceptMessage;
use app\model\system\Config as ConfigModel;
use app\model\system\Pay;
use Exception;
use think\facade\Cache;
use addon\memberwithdraw\model\Withdraw as MemberWithdraw;

/**
 * 会员提现
 */
class Withdraw extends BaseModel
{

    /**************************************************************************** 会员提现设置 *************************************************************/
    /**
     * 会员提现设置
     * @param $data
     * @param $is_use
     * @return array
     */
    public function setConfig($data, $is_use)
    {
        $config = new ConfigModel();
        $res = $config->setConfig($data, '会员提现设置', $is_use, [ [ 'site_id', '=', 0 ], [ 'app_module', '=', 'admin' ], [ 'config_key', '=', 'MEMBER_WITHDRAW_CONFIG' ] ]);
        return $res;
    }

    /**
     * 会员提现设置
     */
    public function getConfig()
    {
        $config = new ConfigModel();
        $res = $config->getConfig([ [ 'site_id', '=', 0 ], [ 'app_module', '=', 'admin' ], [ 'config_key', '=', 'MEMBER_WITHDRAW_CONFIG' ] ]);
        return $res;
    }

    /**
     * 申请提现
     * @param $data
     * @return array
     */
    public function apply($data)
    {

        $config_result = $this->getConfig();
        $config = $config_result[ "data" ][ 'value' ];
        if ($config_result[ "data" ][ "is_use" ] == 0)
            return $this->error([], "提现未开启");

        $withdraw_no = $this->createWithdrawNo();
        $apply_money = round($data[ "apply_money" ], 2);
        if ($apply_money < $config[ "min" ])
            return $this->error([], "申请提现金额不能小于最低提现额度" . $config[ "min" ]);

        $member_id = $data[ "member_id" ];
        $member_model = new Member();
        $member_info_result = $member_model->getMemberInfo([ [ "member_id", "=", $member_id ] ], "balance_money,headimg,wx_openid,username,weapp_openid");
        $member_info = $member_info_result[ "data" ];
        if (empty($member_info))
            return $this->error([], "MEMBER_NOT_EXIST");

        $balance_money = $member_info[ "balance_money" ];
        if ($apply_money > $balance_money)
            return $this->error([], "申请提现金额不能大于会员可提现金额");

        $transfer_type = $data[ "transfer_type" ];
        $transfer_type_list = $this->getTransferType();
        $transfer_type_name = $transfer_type_list[ $transfer_type ];
        if (empty($transfer_type_name))
            return $this->error([], "不支持的提现方式");

        model('member_withdraw')->startTrans();
        try {
            $rate = $config[ "rate" ];
            $bank_name = "";
            $account_number = "";
            switch ( $transfer_type ) {
                case "bank":
                    $bank_name = $data[ "bank_name" ];
                    $account_number = $data[ "account_number" ];

                    break;
                case "alipay":
                    $bank_name = '';
                    $account_number = $data[ "account_number" ];
                    break;
                case "wechatpay":
                    $bank_name = '';
                    if ($data[ "app_type" ] == "wechat") {
                        $account_number = $member_info[ "wx_openid" ];
                    } else if ($data[ "app_type" ] == "weapp") {
                        $account_number = $member_info[ "weapp_openid" ];
                    }
                    if (empty($account_number)) {
                        return $this->error("");
                    }
                    break;

            }

            $service_money = round($apply_money * $rate / 100, 2);//手续费
            $money = $apply_money - $service_money;
            $data = array (
                "withdraw_no" => $withdraw_no,
                "member_name" => $member_info[ "username" ],
                "member_id" => $data[ "member_id" ],
                "transfer_type" => $data[ "transfer_type" ],
                "transfer_type_name" => $transfer_type_name,
                "apply_money" => $apply_money,
                "service_money" => $service_money,
                "rate" => $rate,
                "money" => $money,
                "apply_time" => time(),
                "status" => 0,
                "status_name" => "待审核",
                "member_headimg" => $member_info[ "headimg" ],
                "realname" => $data[ "realname" ],
                "bank_name" => $bank_name,
                "account_number" => $account_number,
                "mobile" => $data[ "mobile" ]
            );
            $result = model("member_withdraw")->add($data);

            //减少可提现余额
            model("member")->setDec([ [ "member_id", "=", $member_id ] ], "balance_money", $apply_money);
            //增加提现中余额
            model("member")->setInc([ [ "member_id", "=", $member_id ] ], "balance_withdraw_apply", $apply_money);

            //是否启用自动通过审核(必须是微信)
            if ($config[ "is_auto_audit" ] == 1) {
                $audit_result = $this->agree([ [ "id", "=", $result ] ]);
//				if ($audit_result["code"] < 0) {
//					model('order')->rollback();
//					return $audit_result;
//				}
            }

            model('member_withdraw')->commit();

            //申请提现发送消息
            $data[ 'keywords' ] = 'USER_WITHDRAWAL_APPLY';
            $message_model = new Message();
            $message_model->sendMessage($data);

            return $this->success();
        } catch (Exception $e) {
            model('member_withdraw')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 同意提现申请
     * @param $condition
     * @return array
     */
    public function agree($condition)
    {
        $info = model("member_withdraw")->getInfo($condition);
        if (empty($info))
            return $this->error();

        $config_result = $this->getConfig();
        $config = $config_result[ "data" ];

        model('member_withdraw')->startTrans();
        try {
            $data = array (
                "status" => 1,
                "status_name" => "待转账",
                "audit_time" => time(),
            );
            model("member_withdraw")->update($data, $condition);

            //是否启用自动转账(必须是微信或支付宝)
            if ($config[ "value" ][ "is_auto_transfer" ] == 1) {
                $member_withdraw_model = new MemberWithdraw();
                $member_withdraw_model->transfer($info[ "id" ]);
            }
            model('member_withdraw')->commit();

//            if ($config[ "value" ][ "is_auto_transfer" ] == 1) {
//                //提现成功发送消息
//                $info[ 'keywords' ] = 'USER_WITHDRAWAL_SUCCESS';
//                $message_model = new Message();
//                $message_model->sendMessage($info);
//            }
            return $this->success();
        } catch (Exception $e) {
            model('member_withdraw')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 拒绝提现申请
     * @param $condition
     * @param $param
     * @return array
     */
    public function refuse($condition, $param)
    {
        $info = model("member_withdraw")->getInfo($condition, "transfer_type,member_id,apply_money");

        if (empty($info))
            return $this->error();

        model('member_withdraw')->startTrans();
        try {
            $data = array (
                "status" => -1,
                "status_name" => "已拒绝",
                "refuse_reason" => $param[ "refuse_reason" ],
                "audit_time" => time(),
            );
            model("member_withdraw")->update($data, $condition);

            //减少可提现余额
            model("member")->setInc([ [ "member_id", "=", $info[ "member_id" ] ] ], "balance_money", $info[ "apply_money" ]);
            //增加提现中余额
            model("member")->setDec([ [ "member_id", "=", $info[ "member_id" ] ] ], "balance_withdraw_apply", $info[ "apply_money" ]);

            model('member_withdraw')->commit();
            return $this->success();
        } catch (Exception $e) {
            model('member_withdraw')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 提现转账完成
     * @param $condition
     * @param array $data
     * @return array
     */
    public function transferFinish($condition, $data = [])
    {
        $info = model("member_withdraw")->getInfo($condition);

        if (empty($info)) return $this->error();

        if ($info[ 'status' ] != 1) {
            return $this->error([], '只有待转账的提现申请可以转账!');
        }
        model('member_withdraw')->startTrans();
        try {

            $data[ "status" ] = 2;
            $data[ "status_name" ] = "已转账";
            $data[ "payment_time" ] = time();
            model("member_withdraw")->update($data, $condition);

            //退还提现的余额，再次扣除并产生记录
            model("member")->setInc([ [ "member_id", "=", $info[ "member_id" ] ] ], "balance_money", $info[ "apply_money" ]);
            $member_account = new MemberAccount();
            $result = $member_account->addMemberAccount($info[ "member_id" ], "balance_money", -$info[ "apply_money" ],
                "withdraw", "余额提现", "余额提现,扣除余额:" . $info[ "apply_money" ]);
            if ($result[ 'code' ] < 0) {
                model('member_withdraw')->rollback();
                return $result;
            }

            //增加已提现余额
            model("member")->setInc([ [ "member_id", "=", $info[ "member_id" ] ] ], "balance_withdraw", $info[ "apply_money" ]);
            //减少提现中余额
            model("member")->setDec([ [ "member_id", "=", $info[ "member_id" ] ] ], "balance_withdraw_apply", $info[ "apply_money" ]);

            model('member_withdraw')->commit();

            //提现成功发送消息
            $info[ 'keywords' ] = 'USER_WITHDRAWAL_SUCCESS';
            $message_model = new Message();
            $message_model->sendMessage($info);

            return $this->success();
        } catch (Exception $e) {
            model('member_withdraw')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * @param $condition
     * @param string $field
     * @return array
     */
    public function getMemberWithdrawInfo($condition, $field = "*")
    {
        $info = model('member_withdraw')->getInfo($condition, $field);
        return $this->success($info);
    }

    /**
     * 提现详情
     * @param $condition
     * @return array
     */
    public function getMemberWithdrawDetail($condition)
    {
        $info = model('member_withdraw')->getInfo($condition, "*");
        return $this->success($info);
    }

    /**
     * 提现单数
     * @param $condition
     * @return array
     */
    public function getMemberWithdrawCount($condition)
    {
        $count = model('member_withdraw')->getCount($condition, "id");
        return $this->success($count);
    }

    /**
     * 获取会员提现分页列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getMemberWithdrawPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*')
    {
        $list = model('member_withdraw')->pageList($condition, $field, $order, $page, $page_size, '', '', '');
        return $this->success($list);
    }

    /**
     * 获取会员提现列表
     * @param array $where
     * @param bool $field
     * @param string $order
     * @param string $alias
     * @param array $join
     * @param string $group
     * @param null $limit
     * @return array
     */
    public function getMemberWithdrawList($where = [], $field = true, $order = '', $alias = 'a', $join = [], $group = '', $limit = null)
    {
        $res = model('member_withdraw')->getList($where, $field, $order, $alias, $join, $group, $limit);
        return $this->success($res);
    }

    /**
     * 提现流水号
     */
    private function createWithdrawNo()
    {
        $cache = Cache::get("member_withdraw_no" . time());
        if (empty($cache)) {
            Cache::set("niutk" . time(), 1000);
            $cache = Cache::get("member_withdraw_no" . time());
        } else {
            $cache = $cache + 1;
            Cache::set("member_withdraw_no" . time(), $cache);
        }
        $no = date('Ymdhis', time()) . rand(1000, 9999) . $cache;
        return $no;
    }

    /**
     * 转账方式
     */
    public function getTransferType()
    {
        $pay_model = new Pay();
        $transfer_type_list = $pay_model->getTransferType();
        $config_result = $this->getConfig();
        $config = $config_result[ "data" ][ 'value' ];
        $support_type = explode(",", $config[ "transfer_type" ]);
        $data = [];
        foreach ($transfer_type_list as $k => $v) {
            if (in_array($k, $support_type)) {
                $data[ $k ] = $v;
            }
        }
        return $data;
    }

    /**
     * 会员提现成功通知
     * @param $data
     */
    public function messageUserWithdrawalSuccess($data)
    {
        $var_parse = array (
            'username' => $data[ "member_name" ],//会员名
            'money' => $data[ 'apply_money' ]
        );
        $data[ "var_parse" ] = $var_parse;

        $member_model = new Member();
        $member_info_result = $member_model->getMemberInfo([ [ "member_id", "=", $data[ "member_id" ] ] ]);
        $member_info = $member_info_result[ "data" ];

        if (!empty($member_info)) {

            //发送短信
            if (!empty($member_info[ "mobile" ])) {
                $sms_model = new Sms();
                $data[ "sms_account" ] = $member_info[ "mobile" ];//手机号
                $sms_model->sendMessage($data);
            }
            //发送邮箱
            if (!empty($member_info[ "email" ])) {
                $email_model = new Email();
                $data[ "email_account" ] = $member_info[ "email" ];//邮箱号
                $email_model->sendMessage($data);
            }
            //发送订阅消息
            if (!empty($member_info["weapp_openid"])) {
                $weapp_model = new WeappMessage();
                $data["openid"] = $member_info["weapp_openid"];
                $data["template_data"] = [
                    'amount6' => [
                        'value' => $data['apply_money']
                    ],
                    'date3' => [
                        'value' => time_to_date(time())
                    ]
                ];
                $data["page"] = "";
                $weapp_model->sendMessage($data);
            }
            //发送站内信
            if(addon_is_exit('sitemessage')) {
                $message_model = new MessageModel();
                $message_info_result = $message_model->getMessageInfo([['keywords', '=', $data["keywords"]]]);
                $message_info = $message_info_result["data"];
                if ($message_info['sitemessage_is_open']) {
                    $MemberMessageModel = new MemberMessage();
                    $MemberMessageModel->accountMessageCreateForMemberWithdrawal($data["id"]);
                }
            }
        }
    }

    /**
     * 会员申请提现通知 卖家通知
     * @param $data
     */
    public function messageUserWithdrawalApply($data)
    {
        $var_parse = array (
            "username" => replaceSpecialChar($data[ "member_name" ]),//会员名
            "money" => $data[ "apply_money" ],//退款申请金额
        );

        $shop_accept_message_model = new ShopAcceptMessage();
        $result = $shop_accept_message_model->getShopAcceptMessageList([ [ 'sam.site_id', '=', $data[ 'site_id' ] ] ]);
        $list = $result[ 'data' ];
        if (!empty($list)) {
            $sms_model = new Sms();
            $email_model = new Email();
            $wechat_model = new WechatMessage();
            foreach ($list as $v) {

                $message_data = $data;
                $message_data[ "var_parse" ] = $var_parse;
                if (!empty($v[ 'mobile' ])) {
                    //发送短信
                    $message_data[ "sms_account" ] = $v[ "mobile" ];//手机号
                    $sms_model->sendMessage($message_data);
                }
                //有邮箱才发送
                if (!empty($v[ 'email' ])) {
                    $message_data[ "email_account" ] = $v[ 'email' ];//邮箱号
                    $email_model->sendMessage($message_data);
                }
                if ($v[ 'wx_openid' ] != "") {
                    $data[ "openid" ] = $v[ 'wx_openid' ];
                    $data[ "template_data" ] = [
                        'keyword1' => replaceSpecialChar($data[ "member_name" ]),
                        'keyword2' => time_to_date($data[ 'apply_time' ]),
                        'keyword3' => $data[ "apply_money" ]
                    ];
                    $data[ "page" ] = "";
                    $wechat_model->sendMessage($data);
                }

            }
        }
    }

}