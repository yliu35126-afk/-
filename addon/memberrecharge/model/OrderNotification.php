<?php


namespace addon\memberrecharge\model;


use addon\sitemessage\model\MemberMessage;
use addon\sitemessage\model\Message as SiteMessage;
use addon\wechat\model\Message as WechatMessage;
use app\model\member\Member;
use app\model\message\Email;
use app\model\message\Message as MessageModel;
use app\model\message\Sms;

class OrderNotification
{

    /**
     * @param $param
     */
    public function messageOrderNotification($params)
    {
        $order_id = $params["order_id"];
        $order_info = model("member_recharge_order")->getInfo([ [ "order_id", "=", $order_id ] ], "member_id,nickname,order_no,pay_time,face_value,price");
        $var_parse = array(
            "username" => $order_info["nickname"],//会员名称
            "money" => $order_info["face_value"],//充值金额
        );
        $params["var_parse"] = $var_parse;
        $member_model = new Member();
        $member_info = $member_model->getMemberInfo([["member_id", "=", $order_info["member_id"]]])['data'];
        
        if(!empty($member_info)){
            //有手机号才发送
            if(!empty($member_info["mobile"])){
                // 发送短信
                $sms_model = new Sms();
                $params["sms_account"] = $member_info["mobile"];//邮箱号
                $sms_result = $sms_model->sendMessage($params);
            }
            //有邮箱才发送
            if(!empty($member_info["email"])){
                //邮箱发送
                $email_model = new Email();
                $params["email_account"] = $member_info["email"];//邮箱号
                $email_model->sendMessage($params);
            }
        }
        //发送站内信
        if(addon_is_exit('sitemessage')) {
            $message_model = new MessageModel();
            $message_info_result = $message_model->getMessageInfo([['keywords', '=', 'RECHARGE_SUCCESS']]);
            $message_info = $message_info_result["data"];
            if ($message_info['sitemessage_is_open']) {
                $MemberMessageModel = new MemberMessage();
                $MemberMessageModel->accountMessageCreateForMemberRechargeOrder($order_id);
            }
        }

//
        //发送模板消息
//        $data = $params;
//        $wechat_model = new WechatMessage();
//        $data["openid"] = $member_info["wx_openid"];
//        $data["template_data"] = [
//            'keyword1' => $order_info['full_address'].$order_info['address'],
//            'keyword2' => $order_info["name"],
//            'keyword3' => $order_info['order_no'],
//            'keyword4' => $order_info['order_name'],
//            'keyword5' => time_to_date($order_info['sign_time']),
//        ];
////        $data["page"] = $this->handleUrl($order_info['order_type'], $order_id);
//        $wechat_model->sendMessage($data);
    }
}