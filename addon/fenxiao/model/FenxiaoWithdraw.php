<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\fenxiao\model;

use addon\weapp\model\Message as WeappMessage;
use app\model\BaseModel;
use app\model\member\Member;
use app\model\member\MemberAccount;
use app\model\message\Email;
use app\model\message\Message;
use app\model\message\Sms;
use app\model\shop\ShopAcceptMessage;
use addon\wechat\model\Message as WechatMessage;

/**
 * 分销商提现
 */
class FenxiaoWithdraw extends BaseModel
{
    //提现类型
    public $withdraw_type = [
        'balance' => '余额',
        'weixin' => '微信',
        'alipay' => '支付宝',
        'bank' => '银行卡',
    ];

    /**
     * 分销商申请提现
     * @param $data
     * @return array
     */
    public function addFenxiaoWithdraw($data)
    {
        //获取分销商信息
        $fenxiao_model = new Fenxiao();
        $fenxiao_info = $fenxiao_model->getFenxiaoInfo([ [ 'member_id', '=', $data[ 'member_id' ] ] ], 'fenxiao_id,fenxiao_name,account');
        if ($fenxiao_info[ 'data' ][ 'account' ] < $data[ 'money' ]) {
            return $this->error('', '提现金额大于可提现金额');
        }
        //获取提现配置信息
        $config_model = new Config();
        $config = $config_model->getFenxiaoWithdrawConfig();
        $config_info = $config[ 'data' ][ 'value' ];
        if ($config_info[ 'withdraw' ] > $data[ 'money' ]) {
            return $this->error('', '提现金额小于最低提现金额');
        }
        if ($data[ 'money' ] >= $config_info[ 'min_no_fee' ] && $data[ 'money' ] <= $config_info[ 'max_no_fee' ]) {
            $data[ 'withdraw_rate' ] = 0;
            $data[ 'withdraw_rate_money' ] = 0;
            $data[ 'real_money' ] = $data[ 'money' ];
        } else {
            $data[ 'withdraw_rate' ] = $config_info[ 'withdraw_rate' ];
            if ($config_info[ 'withdraw_rate' ] == 0) {
                $data[ 'withdraw_rate' ] = 0;
                $data[ 'withdraw_rate_money' ] = 0;
                $data[ 'real_money' ] = $data[ 'money' ];
            } else {
                $data[ 'withdraw_rate' ] = $config_info[ 'withdraw_rate' ];
                $data[ 'withdraw_rate_money' ] = round($data[ 'money' ] * $config_info[ 'withdraw_rate' ] / 100, 2);
                $data[ 'real_money' ] = $data[ 'money' ] - $data[ 'withdraw_rate_money' ];
            }
        }

        $data[ 'withdraw_no' ] = date('YmdHis') . rand(1000, 9999);
        $data[ 'create_time' ] = time();

        model('fenxiao_withdraw')->startTrans();
        try {

            $data[ 'fenxiao_id' ] = $fenxiao_info[ 'data' ][ 'fenxiao_id' ];
            $data[ 'fenxiao_name' ] = $fenxiao_info[ 'data' ][ 'fenxiao_name' ];

            $res = model('fenxiao_withdraw')->add($data);

            //判断是否需要审核
            if ($config_info[ 'withdraw_status' ] == 2) {//不需要

                $result = $this->withdrawPass($res);
                if ($result[ 'code' ] < 0) {
                    model('fenxiao_withdraw')->rollback();
                    return $result;
                }
            }

            //修改分销商提现中金额
            model('fenxiao')->setInc([ [ 'member_id', '=', $data[ 'member_id' ] ] ], 'account_withdraw_apply', $data[ 'money' ]);
            //修改分销商可提现金额
            model('fenxiao')->setDec([ [ 'member_id', '=', $data[ 'member_id' ] ] ], 'account', $data[ 'money' ]);

            model('fenxiao_withdraw')->commit();

            //申请提现发送消息
            $data[ 'keywords' ] = 'FENXIAO_WITHDRAWAL_APPLY';
            $message_model = new Message();
            $message_model->sendMessage($data);

            return $this->success($res);
        } catch (\Exception $e) {
            model('fenxiao_withdraw')->rollback();
            return $this->error('', $e->getMessage());
        }

    }

    /**
     * 提现审核通过
     * @param $ids
     * @return array
     */
    public function withdrawPass($ids)
    {
        model('fenxiao_withdraw')->startTrans();
        try {

            $withdraw_list = $this->getFenxiaoWithdrawList([ [ 'id', 'in', $ids ] ], 'id,fenxiao_id,fenxiao_name,member_id,money,real_money');
            foreach ($withdraw_list[ 'data' ] as $k => $v) {

                //修改分销商提现中金额
                model('fenxiao')->setDec([ [ 'fenxiao_id', '=', $v[ 'fenxiao_id' ] ] ], 'account_withdraw_apply', $v[ 'money' ]);
                //修改分销商已提现金额
                model('fenxiao')->setInc([ [ 'fenxiao_id', '=', $v[ 'fenxiao_id' ] ] ], 'account_withdraw', $v[ 'money' ]);

                //添加会员账户流水
                $member_account = new MemberAccount();
                $member_result = $member_account->addMemberAccount($v[ 'member_id' ], 'balance_money', $v[ 'real_money' ], 'fenxiao', '佣金提现', '分销佣金提现');
                if ($member_result[ 'code' ] != 0) {
                    model('fenxiao_withdraw')->rollback();
                    return $member_result;
                }

                $account_model = new FenxiaoAccount();
                $account_result = $account_model->addAccountLog($v[ 'fenxiao_id' ], $v[ 'fenxiao_name' ], 'withdraw', '-' . $v[ 'money' ], $v[ 'id' ]);
                if ($account_result[ 'code' ] != 0) {
                    model('fenxiao_withdraw')->rollback();
                    return $account_result;
                }
            }

            //修改提现状态
            $data = [
                'status' => 2,
                'payment_time' => time(),
                'modify_time' => time(),
            ];
            model('fenxiao_withdraw')->update($data, [ [ 'id', 'in', $ids ] ]);

            model('fenxiao_withdraw')->commit();

            //提现成功发送消息
            foreach ($withdraw_list[ 'data' ] as $k => $v) {

                $message_model = new Message();
                $v[ 'keywords' ] = 'FENXIAO_WITHDRAWAL_SUCCESS';
                $message_model->sendMessage($v);
            }

            return $this->success();
        } catch (\Exception $e) {
            model('fenxiao_withdraw')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 提现审核拒绝
     * @param $id
     * @return array
     */
    public function withdrawRefuse($id)
    {
        $data = [
            'status' => -1,
            'payment_time' => time()
        ];

        $info = model('fenxiao_withdraw')->getInfo([ [ 'id', '=', $id ] ], 'fenxiao_id,money,member_id');
        model('fenxiao_withdraw')->startTrans();
        try {
            model('fenxiao_withdraw')->update($data, [ [ 'id', '=', $id ] ]);

            //修改分销商提现中金额
            model('fenxiao')->setDec([ [ 'fenxiao_id', '=', $info[ 'fenxiao_id' ] ] ], 'account_withdraw_apply', $info[ 'money' ]);
            //修改分销商可提现金额
            model('fenxiao')->setInc([ [ 'fenxiao_id', '=', $info['fenxiao_id'] ] ], 'account', $info['money']);

            //提现失败发送消息
            $message_model = new Message();
            $info['keywords'] = 'FENXIAO_WITHDRAWAL_ERROR';

            $message_model->sendMessage($info);
            
            model('fenxiao_withdraw')->commit();
            return $this->success();
        } catch (\Exception $e) {
            model('fenxiao_withdraw')->rollback();
            return $this->error('', $e->getMessage());
        }

    }

    /**
     * 获取提现详情
     * @param array $condition
     * @return array
     */
    public function getFenxiaoWithdrawInfo($condition = [], $field = '*')
    {
        $res = model('fenxiao_withdraw')->getInfo($condition, $field);
        return $this->success($res);
    }

    /**
     * 获取分销列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param string $limit
     */
    public function getFenxiaoWithdrawList($condition = [], $field = '*', $order = '', $limit = null)
    {
        $list = model('fenxiao_withdraw')->getList($condition, $field, $order, '', '', '', $limit);
        return $this->success($list);
    }

    /**
     * 获取分销提现分页列表
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getFenxiaoWithdrawPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*')
    {
        $list = model('fenxiao_withdraw')->pageList($condition, $field, $order, $page, $page_size);
        return $this->success($list);
    }

    /**
     * 分销提现成功通知
     * @param $data
     */
    public function messageFenxiaoWithdrawalSuccess($data)
    {
        $var_parse = array (
            'fenxiaoname' => $data[ "fenxiao_name" ],//会员名
            'money' => $data[ 'money' ]
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
                    'amount1' => [
                        'value' => $data['money']
                    ],
                    'time2' => [
                        'value' => time_to_date(time())
                    ],
                    'thing3' => [
                        'value' => '提现成功'
                    ]
                ];
                $data["page"] = "";
                $weapp_model->sendMessage($data);
            }
        }
    }

    /**
     * 分销提现失败通知
     * @param $data
     */
    public function messageFenxiaoWithdrawalError($data)
    {
        //发送短信
        $sms_model = new Sms();

        $member_model = new Member();

        $member_info_result = $member_model->getMemberInfo([["member_id", "=", $data["member_id"]]]);

        $member_info = $member_info_result["data"];

        $var_parse = array(
            'fenxiaoname' => str_replace(' ','',isset($data["fenxiao_name"])?$data["fenxiao_name"]:0),//会员名
            'money' => $data['money']
        );

        if (!empty($member_info)) {

            //发送短信
            if (!empty($member_info[ "mobile" ])) {
                $data["sms_account"] = $member_info["mobile"];//手机号
                $data["var_parse"] = $var_parse;
                $sms_model->sendMessage($data);
            }
            //发送邮箱
            if (!empty($member_info[ "email" ])) {
                $email_model = new Email();
                $data[ "email_account" ] = $member_info[ "email" ];//邮箱号
                $email_model->sendMessage($data);
            }

            //绑定微信公众号才发送
            if (!empty($member_info) && !empty($member_info["wx_openid"])) {
                $wechat_model = new WechatMessage();
                $data["openid"] = $member_info["wx_openid"];
                $data["template_data"] = [
                    'keyword1' => time_to_date($data['create_time']),
                    'keyword2' => '审核失败',
                    'keyword3' => '会员申请提现',
                    'keyword4' => $data['money'],
                ];
                $data["page"] = "";
                $wechat_model->sendMessage($data);
            }

            //发送订阅消息
            if (!empty($member_info["weapp_openid"])) {
                $weapp_model = new WeappMessage();
                $data["openid"] = $member_info["weapp_openid"];
                $data["template_data"] = [
                    'amount2' => [
                        'value' => $data['money']
                    ],
                    'thing4' => [
                        'value' => '提现审核失败'
                    ]
                ];
                $data["page"] = "";
                $weapp_model->sendMessage($data);
            }

        }
    }

    /**
     * 分销申请提现通知，卖家通知
     * @param $data
     */
    public function messageFenxiaoWithdrawalApply($data)
    {
        $var_parse = array (
            "fenxiaoname" => replaceSpecialChar($data[ "fenxiao_name" ]),//会员名
            "money" => $data[ "money" ],//退款申请金额
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
                if ($v[ 'wx_openid' ] != '') {
                    $data[ "openid" ] = $v[ 'wx_openid' ];
                    $data[ "template_data" ] = [
                        'keyword1' => replaceSpecialChar($data[ "fenxiao_name" ]),
                        'keyword2' => time_to_date($data[ 'create_time' ]),
                        'keyword3' => $data[ "money" ]
                    ];
                    $data[ "page" ] = "";
                    $wechat_model->sendMessage($data);
                }
            }
        }

    }

    /**
     * 获取提现数量
     * @param array $condition
     * @return array
     */
    public function getFenxiaoWithdrawCount($condition = [], $field = '*')
    {
        $res = model('fenxiao_withdraw')->getCount($condition, $field);
        return $this->success($res);
    }
}