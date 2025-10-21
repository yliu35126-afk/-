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

namespace app\model\order;

use addon\weapp\model\Message as WeappMessage;
use addon\sitemessage\model\Message as SiteMessage;
use app\model\member\Member;
use app\model\message\Message;
use app\model\message\Email;
use app\model\message\Sms;
use app\model\BaseModel;
use addon\wechat\model\Message as WechatMessage;
use app\model\shop\ShopAcceptMessage;

/**
 * 订单消息操作
 *
 * @author Administrator
 *
 */
class OrderMessage extends BaseModel
{
    /**
     * 订单生成提醒
     * @param $data
     */
    public function messageOrderCreate($data)
    {

        $order_id = $data["order_id"];
        $join = [
            [
                'member m',
                'o.member_id = m.member_id',
                'inner'
            ]
        ];
        $field = "o.full_address,o.address,o.order_no,o.mobile,o.member_id,o.order_type,o.create_time,o.order_name,o.order_money,m.username";
        $order_info = model("order")->getInfo([["order_id", "=", $order_id]], $field,'o',$join);
        $var_parse = array(
            "username" => $order_info["username"],
            "orderno" => $order_info["order_no"],//商品名称
        );
        $data["var_parse"] = $var_parse;
        //发送邮箱
        $email_model = new Email();
        $member_model = new Member();
        $member_info_result = $member_model->getMemberInfo([["member_id", "=", $order_info["member_id"]]]);
        $member_info = $member_info_result["data"];
        //有邮箱才发送
        if (!empty($member_info)) {
            if (!empty($member_info["mobile"])) {
                //发送短信
                $sms_model = new Sms();
                $data["sms_account"] = $member_info["mobile"];//手机号
                $sms_model->sendMessage($data);
            }
            if (!empty($member_info["email"])) {
                $data["email_account"] = $member_info["email"];//邮箱号
                $email_model->sendMessage($data);
            }
        }
        //绑定微信公众号才发送
        if (!empty($member_info) && !empty($member_info["wx_openid"])) {
            $wechat_model = new WechatMessage();
            $data["openid"] = $member_info["wx_openid"];
            $data["template_data"] = [
                'keyword1' => $order_info['order_no'],
                'keyword2' => time_to_date($order_info['create_time']),
                'keyword3' => str_sub($order_info['order_name']),
                'keyword4' => $order_info['order_money'],
                'keyword5' => $order_info['full_address'] . $order_info['address'] . " " . $order_info['mobile']
            ];
            $data["page"] = $this->handleUrl($order_info['order_type'], $order_id);
            $wechat_model->sendMessage($data);
        }

        //发送订阅消息
        if (!empty($member_info) && !empty($member_info["weapp_openid"])) {
            $weapp_model = new WeappMessage();
            $data["openid"] = $member_info["weapp_openid"];
            $data["template_data"] = [
                'character_string13' => [
                    'value' => $order_info['order_no']
                ],
                'time1' => [
                    'value' => time_to_date($order_info['create_time'], 'Y-m-d H:i')
                ],
                'amount15' => [
                    'value' => $order_info['order_money']
                ],
                'thing14' => [
                    'value' => str_sub($order_info['order_name'])
                ],
            ];

            $data["page"] = $this->handleUrl($order_info['order_type'], $order_id);
            $weapp_model->sendMessage($data);
        }
        if(addon_is_exit('sitemessage')){
            $site_model = new SiteMessage();
            $data['sub_type'] = 'order';
            $data['order_id'] = $order_id;
            $data['action_type'] = 'ORDER_CREATE';
            $site_model->sendMessage($data);
        }
    }

    /**
     * 消息发送——支付成功
     * @param $params
     * @return array|mixed|void
     */
    public function messagePaySuccess($params)
    {
        $join = [
            [
                'member m',
                'o.member_id = m.member_id',
                'inner'
            ]
        ];
        $field = "o.full_address,o.address,o.order_no,o.mobile,o.member_id,o.order_type,o.create_time,o.order_name,o.order_money,m.username";
        $order_info = model("order")->getInfo([["order_no", "=", $params['order_no']]], $field,'o',$join);
        $var_parse = [
            "orderno" => $params['order_no'],
            "username" => $order_info["username"],
            "ordermoney" => $params["order_money"],
        ];
        $params["var_parse"] = $var_parse;
        $member_model = new Member();
        $member_info_result = $member_model->getMemberInfo([["member_id", "=", $params["member_id"]]]);

        $member_info = $member_info_result["data"];
        if (!empty($member_info)) {
            //有手机号才发送
            if (!empty($member_info["mobile"])) {
                // 发送短信
                $sms_model = new Sms();
                $params["sms_account"] = $member_info["mobile"];//邮箱号
                $sms_result = $sms_model->sendMessage($params);
            }
            //有邮箱才发送
            if (!empty($member_info["email"])) {
                //邮箱发送
                $email_model = new Email();
                $params["email_account"] = $member_info["email"];//邮箱号
                $email_model->sendMessage($params);
            }
            //发送订阅消息
            if (!empty($member_info["weapp_openid"])) {
                $data = $params;
                $weapp_model = new WeappMessage();
                $data["openid"] = $member_info["weapp_openid"];
                $data["template_data"] = [
                    'character_string1' => [
                        'value' => $params['order_no']
                    ],
                    'time7' => [
                        'value' => time_to_date($params['create_time'])
                    ],
                    'thing4' => [
                        'value' => str_sub($params['order_name'])
                    ],
                    'amount3' => [
                        'value' => $params['order_money']
                    ],
                ];

                $data["page"] = $this->handleUrl($params['order_type'], $params["order_id"]);

                $weapp_model->sendMessage($data);
            }
        }
        $data = $params;
        //绑定微信公众号才发送
        if (!empty($member_info) && !empty($member_info["wx_openid"])) {
            $wechat_model = new WechatMessage();
            $data["openid"] = $member_info["wx_openid"];
            $data["template_data"] = [
                'keyword1' => time_to_date($params['create_time']),
                'keyword2' => $params['order_no'],
                'keyword3' => str_sub($params['order_name']),
                'keyword4' => $params['order_money'],
            ];
            $data["page"] = $this->handleUrl($params['order_type'], $params["order_id"]);
            $wechat_model->sendMessage($data);
        }
        //发送站内信
        if(addon_is_exit('sitemessage')) {
            $site_model = new SiteMessage();
            $data['sub_type'] = 'order';
            $data['order_id'] = $params['order_id'];
            $data['action_type'] = 'ORDER_PAY';
            $site_model->sendMessage($data);
        }
    }


    /**
     * 订单关闭提醒
     * @param $data
     */
    public function messageOrderClose($params)
    {
        $order_id = $params["order_id"];
        $join = [
            [
                'member m',
                'o.member_id = m.member_id',
                'inner'
            ]
        ];
        $field = "o.order_type,o.order_no,o.mobile,o.member_id,o.order_name,o.create_time,o.order_money,o.close_time,m.username";
        $order_info = model("order")->getInfo([["order_id", "=", $order_id]], $field,'o',$join);
        $var_parse = array(
            "username" => $order_info["username"],
            "orderno" => $order_info["order_no"],//商品名称
        );
        $params["var_parse"] = $var_parse;
        $member_model = new Member();
        $member_info = $member_model->getMemberInfo([["member_id", "=", $order_info["member_id"]]])['data'];
        if (!empty($member_info)) {
            //有手机号才发送
            if (!empty($member_info["mobile"])) {
                // 发送短信
                $sms_model = new Sms();
                $params["sms_account"] = $member_info["mobile"];//手机号
                $sms_result = $sms_model->sendMessage($params);
            }
            //有邮箱才发送
            if (!empty($member_info["email"])) {
                //邮箱发送
                $email_model = new Email();
                $params["email_account"] = $member_info["email"];//邮箱号
                $email_model->sendMessage($params);
            }
        }
        if (!empty($member_info) && !empty($member_info["wx_openid"])) {
            $wechat_model = new WechatMessage();
            $data = $params;
            $data["openid"] = $member_info["wx_openid"];
            $data["template_data"] = [
                'keyword1' => str_sub($order_info['order_name']),
                'keyword2' => $order_info['order_no'],
                'keyword3' => time_to_date($order_info['create_time']),
                'keyword4' => $order_info['order_money'],
                'keyword5' => time_to_date($order_info['close_time'])
            ];
            $data["page"] = $this->handleUrl($order_info['order_type'], $order_id);
            $wechat_model->sendMessage($data);
        }
        //发送站内信
        if(addon_is_exit('sitemessage')) {
            $site_model = new SiteMessage();
            $data = $params;
            $data['sub_type'] = 'order';
            $data['order_id'] = $order_id;
            $data['action_type'] = 'ORDER_CLOSE';
            $site_model->sendMessage($data);
        }
    }

    /**
     * 订单完成提醒
     * @param $data
     */
    public function messageOrderComplete($data)
    {
        $order_id = $data["order_id"];
        $join = [
            [
                'member m',
                'o.member_id = m.member_id',
                'inner'
            ]
        ];
        $field = "o.order_type,o.order_no,o.mobile,o.member_id,o.order_name,o.create_time,o.order_money,o.close_time,m.username";
        $order_info = model("order")->getInfo([["order_id", "=", $order_id]], $field,'o',$join);
        $var_parse = array(
            "username" => $order_info["username"],
            "orderno" => $order_info["order_no"],//订单号
        );
        $data["var_parse"] = $var_parse;
        $member_model = new Member();
        $member_info = $member_model->getMemberInfo([["member_id", "=", $order_info["member_id"]]])['data'];
        if (!empty($member_info)) {
            //有手机号才发送
            if (!empty($member_info["mobile"])) {
                // 发送短信
                $sms_model = new Sms();
                $data["sms_account"] = $member_info["mobile"];//邮箱号
                $sms_result = $sms_model->sendMessage($data);
            }
            //有邮箱才发送
            if (!empty($member_info["email"])) {
                //邮箱发送
                $email_model = new Email();
                $data["email_account"] = $member_info["email"];//邮箱号
                $email_model->sendMessage($data);
            }
        }
        //发送模板消息
        $wechat_model = new WechatMessage();
        $data["openid"] = $member_info["wx_openid"];
        $data["template_data"] = [
            'keyword1' => $order_info['order_no'],
            'keyword2' => str_sub($order_info['order_name']),
            'keyword3' => time_to_date($order_info['create_time']),
        ];
        $data["page"] = $this->handleUrl($order_info['order_type'], $order_id);
        $wechat_model->sendMessage($data);

        //发送站内信
        if(addon_is_exit('sitemessage')) {
            $site_model = new SiteMessage();
            $data['sub_type'] = 'order';
            $data['order_id'] = $order_id;
            $data['action_type'] = 'ORDER_COMPLETE';
            $site_model->sendMessage($data);
        }
    }

    /**
     * 订单发货提醒
     * @param $data
     */
    public function messageOrderDelivery($params)
    {
        //发送短信
        $order_id = $params["order_id"];
        $join = [
            [
                'member m',
                'o.member_id = m.member_id',
                'inner'
            ]
        ];
        $field = "o.order_type,o.order_no,o.mobile,o.member_id,o.order_name,o.goods_num,o.order_money,o.delivery_time,m.username";
        $order_info = model("order")->getInfo([["order_id", "=", $order_id]], $field,'o',$join);
        $var_parse = array(
            "username" => $order_info["username"],
            "orderno" => $order_info["order_no"],//商品名称
        );
        $params["var_parse"] = $var_parse;
        $member_model = new Member();
        $member_info = $member_model->getMemberInfo([["member_id", "=", $order_info["member_id"]]])['data'];
        $data = $params;
        if (!empty($member_info)) {
            //有手机号才发送
            if (!empty($member_info["mobile"])) {
                // 发送短信
                $sms_model = new Sms();
                $params["sms_account"] = $member_info["mobile"];//邮箱号
                $sms_result = $sms_model->sendMessage($params);
            }
            //有邮箱才发送
            if (!empty($member_info["email"])) {
                //邮箱发送
                $email_model = new Email();
                $params["email_account"] = $member_info["email"];//邮箱号
                $email_model->sendMessage($params);
            }
            //发送订阅消息
            if (!empty($member_info["weapp_openid"])) {
                $weapp_model = new WeappMessage();
                $data["openid"] = $member_info["weapp_openid"];
                $data["template_data"] = [
                    'character_string2' => [
                        'value' => $order_info['order_no']
                    ],
                    'thing1' => [
                        'value' => str_sub($order_info['order_name'])
                    ],
                    'amount7' => [
                        'value' => $order_info['order_money']
                    ],
                    'date3' => [
                        'value' => time_to_date($order_info['delivery_time'])
                    ]
                ];
                $data["page"] = $this->handleUrl($order_info['order_type'], $order_id);
                $weapp_model->sendMessage($data);
            }
        }
        //发送模板消息
        $wechat_model = new WechatMessage();
        $data["openid"] = $member_info["wx_openid"];
        $data["template_data"] = [
            'keyword1' => $order_info['order_no'],
            'keyword2' => str_sub($order_info['order_name']),
            'keyword3' => $order_info['goods_num'],
            'keyword4' => $order_info['order_money'],
            'keyword5' => time_to_date($order_info['delivery_time']),
        ];
        $data["page"] = $this->handleUrl($order_info['order_type'], $order_id);
        $wechat_model->sendMessage($data);

        //发送站内信
        if(addon_is_exit('sitemessage')) {
            $data = $params;
            $site_model = new SiteMessage();
            $data['sub_type'] = 'order';
            $data['order_id'] = $order_id;
            $data['action_type'] = 'ORDER_DELIVERY';
            $site_model->sendMessage($data);
        }
    }

    /**
     * 订单收货提醒
     * @param $data
     */
    public function messageOrderTakeDelivery($params)
    {
        $order_id = $params["order_id"];
        $join = [
            [
                'member m',
                'o.member_id = m.member_id',
                'inner'
            ]
        ];
        $field = "o.order_type,o.order_no,o.mobile,o.member_id,o.full_address,o.address,o.name,o.order_name,o.sign_time,m.username";
        $order_info = model("order")->getInfo([["order_id", "=", $order_id]], $field,'o',$join);
        $var_parse = array(
            "username" => $order_info["username"],
            "orderno" => $order_info["order_no"],//商品名称
        );
        $params["var_parse"] = $var_parse;
        $member_model = new Member();
        $member_info = $member_model->getMemberInfo([["member_id", "=", $order_info["member_id"]]])['data'];
        if (!empty($member_info)) {
            //有手机号才发送
            if (!empty($member_info["mobile"])) {
                // 发送短信
                $sms_model = new Sms();
                $params["sms_account"] = $member_info["mobile"];//邮箱号
                $sms_result = $sms_model->sendMessage($params);
            }
            //有邮箱才发送
            if (!empty($member_info["email"])) {
                //邮箱发送
                $email_model = new Email();
                $params["email_account"] = $member_info["email"];//邮箱号
                $email_model->sendMessage($params);
            }
            //发送订阅消息
            if (!empty($member_info["weapp_openid"])) {
                $data = $params;
                $weapp_model = new WeappMessage();
                $data["openid"] = $member_info["weapp_openid"];
                $data["template_data"] = [
                    'character_string1' => [
                        'value' => $order_info['order_no']
                    ],
                    'thing2' => [
                        'value' => str_sub($order_info['order_name'])
                    ],
                    'time7' => [
                        'value' => time_to_date($order_info['sign_time'])
                    ],
                    'thing9' => [
                        'value' => str_sub($order_info['name'])
                    ]
                ];
                $data["page"] = $this->handleUrl($order_info['order_type'], $order_id);
                $weapp_model->sendMessage($data);
            }
        }
        //发送模板消息
        $data = $params;
        $wechat_model = new WechatMessage();
        $data["openid"] = $member_info["wx_openid"];
        $data["template_data"] = [
            'keyword1' => $order_info['full_address'] . $order_info['address'],
            'keyword2' => $order_info["name"],
            'keyword3' => $order_info['order_no'],
            'keyword4' => $order_info['order_name'],
            'keyword5' => time_to_date($order_info['sign_time']),
        ];
        $data["page"] = $this->handleUrl($order_info['order_type'], $order_id);
        $wechat_model->sendMessage($data);

        //发送站内信
        if(addon_is_exit('sitemessage')) {
            $data = $params;
            $site_model = new SiteMessage();
            $data['sub_type'] = 'order';
            $data['order_id'] = $order_id;
            $data['action_type'] = 'ORDER_TAKE_DELIVERY';
            $site_model->sendMessage($data);
        }
    }

    /**
     * 订单退款同意提醒
     * @param $data
     */
    public function messageOrderRefundAgree($data)
    {

        $order_id = $data["order_id"];
        $join = [
            [
                'member m',
                'o.member_id = m.member_id',
                'inner'
            ]
        ];
        $field = "o.order_type,o.order_no,o.mobile,o.member_id,m.username";
        $order_info = model("order")->getInfo([["order_id", "=", $order_id]], $field,'o',$join);
        $order_goods_info = model("order_goods")->getInfo([["order_goods_id", "=", $data["order_goods_id"]]], "refund_apply_money,refund_time,refund_action_time");
        $var_parse = array(
            "username" => $order_info["username"],
            "orderno" => $order_info["order_no"],//商品名称
        );
        $data["var_parse"] = $var_parse;
        $member_model = new Member();
        $member_info = $member_model->getMemberInfo([["member_id", "=", $order_info["member_id"]]])['data'];
        if (!empty($member_info)) {
            //有手机号才发送
            if (!empty($member_info["mobile"])) {
                // 发送短信
                $sms_model = new Sms();
                $params["sms_account"] = $member_info["mobile"];//邮箱号
                $sms_result = $sms_model->sendMessage($params);
            }
            //有邮箱才发送
            if (!empty($member_info["email"])) {
                //邮箱发送
                $email_model = new Email();
                $params["email_account"] = $member_info["email"];//邮箱号
                $email_model->sendMessage($params);
            }
            //发送订阅消息
            if (!empty($member_info["weapp_openid"])) {
                $weapp_model = new WeappMessage();
                $data["openid"] = $member_info["weapp_openid"];
                $data["template_data"] = [
                    'character_string3' => [
                        'value' => $order_info['order_no']
                    ],
                    'amount1' => [
                        'value' => $order_goods_info["refund_apply_money"]
                    ],
                    'phrase7' => [
                        'value' => '成功'
                    ]
                ];
                $data["page"] = $this->handleUrl($order_info['order_type'], $order_id);
                $weapp_model->sendMessage($data);
            }
        }
        //发送模板消息
        $wechat_model = new WechatMessage();
        $data["openid"] = $member_info["wx_openid"];
        $data["template_data"] = [
            'keyword1' => $order_info['order_no'],
            'keyword2' => $order_goods_info["refund_apply_money"],
            'keyword3' => time_to_date($order_goods_info['refund_time']),
        ];
        $data["page"] = $this->handleUrl($order_info['order_type'], $order_id);
        $wechat_model->sendMessage($data);
        //发送站内信
        if(addon_is_exit('sitemessage')) {
            $site_model = new SiteMessage();
            $data['sub_type'] = 'order';
            $data['order_id'] = $order_id;
            $data['action_type'] = 'ORDER_REFUND_AGREE';
            $site_model->sendMessage($data);
        }
    }

    /**
     * 订单退款拒绝提醒
     * @param $data
     */
    public function messageOrderRefundRefuse($data)
    {
        $order_id = $data["order_id"];
        $join = [
            [
                'member m',
                'o.member_id = m.member_id',
                'inner'
            ]
        ];
        $field = "o.order_type,o.order_no,o.mobile,o.member_id,m.username";
        $order_info = model("order")->getInfo([["order_id", "=", $order_id]], $field,'o',$join);
        $order_goods_info = model("order_goods")->getInfo([["order_goods_id", "=", $data["order_goods_id"]]], "refund_apply_money,refund_time,refund_action_time");
        $var_parse = array(
            "username" => $order_info["username"],
            "orderno" => $order_info["order_no"],//商品名称
        );
        $data["var_parse"] = $var_parse;
        $member_model = new Member();
        $member_info = $member_model->getMemberInfo([["member_id", "=", $order_info["member_id"]]])['data'];
        if (!empty($member_info)) {
            //有手机号才发送
            if (!empty($member_info["mobile"])) {
                // 发送短信
                $sms_model = new Sms();
                $params["sms_account"] = $member_info["mobile"];//邮箱号
                $sms_result = $sms_model->sendMessage($params);
            }
            //有邮箱才发送
            if (!empty($member_info["email"])) {
                //邮箱发送
                $email_model = new Email();
                $params["email_account"] = $member_info["email"];//邮箱号
                $email_model->sendMessage($params);
            }
            //发送订阅消息
            if (!empty($member_info["weapp_openid"])) {
                $weapp_model = new WeappMessage();
                $data["openid"] = $member_info["weapp_openid"];
                $data["template_data"] = [
                    'character_string4' => [
                        'value' => $order_info['order_no']
                    ],
                    'amount3' => [
                        'value' => $order_goods_info["refund_apply_money"]
                    ]
                ];
                $data["page"] = $this->handleUrl($order_info['order_type'], $order_id);
                $weapp_model->sendMessage($data);
            }
        }
        //发送模板消息
        $wechat_model = new WechatMessage();
        $data["openid"] = $member_info["wx_openid"];
        $data["template_data"] = [
            'keyword1' => $order_info['order_no'],
            'keyword2' => $order_goods_info["refund_apply_money"],
            'keyword3' => time_to_date($order_goods_info['refund_action_time']),
        ];
        $data["page"] = $this->handleUrl($order_info['order_type'], $order_id);
        $wechat_model->sendMessage($data);

        //发送站内信
        if(addon_is_exit('sitemessage')) {
            $site_model = new SiteMessage();
            $data['sub_type'] = 'order';
            $data['order_id'] = $order_id;
            $data['action_type'] = 'ORDER_REFUND_REFUSE';
            $site_model->sendMessage($data);
        }
    }

    /**
     * 订单核销通知
     * @param $data
     */
    public function messageOrderVerify($data)
    {
        $order_id = $data["order_id"];
        $join = [
            [
                'member m',
                'o.member_id = m.member_id',
                'inner'
            ]
        ];
        $field = "o.order_type,o.order_no,o.mobile,o.member_id,o.order_name,o.goods_num,o.sign_time,m.username";
        $order_info = model("order")->getInfo([["order_id", "=", $order_id]], $field,'o',$join);
        $var_parse = array(
            "username" => $order_info["username"],
            "orderno" => $order_info["order_no"],//商品名称
        );
        $data["var_parse"] = $var_parse;
        $member_model = new Member();
        $member_info = $member_model->getMemberInfo([["member_id", "=", $order_info["member_id"]]])['data'];
        if (!empty($member_info)) {
            //有手机号才发送
            if (!empty($member_info["mobile"])) {
                // 发送短信
                $sms_model = new Sms();
                $params["sms_account"] = $member_info["mobile"];//邮箱号
                $sms_result = $sms_model->sendMessage($params);
            }
            //有邮箱才发送
            if (!empty($member_info["email"])) {
                //邮箱发送
                $email_model = new Email();
                $params["email_account"] = $member_info["email"];//邮箱号
                $email_model->sendMessage($params);
            }
        }
    }


    /******************************************************************************* 卖家通知 START *****************************************************************/
    /**
     * 买家发起退款，卖家通知
     * @param $data
     */
    public function messageOrderRefundApply($data)
    {
        $order_goods_id = $data["order_goods_id"];
        $order_goods_info = model('order_goods')->getInfo(['order_goods_id' => $order_goods_id], 'order_id,refund_no,refund_reason,refund_apply_money,sku_name,refund_action_time');
        $join = [
            [
                'member m',
                'o.member_id = m.member_id',
                'inner'
            ]
        ];
        $field = "o.order_no,o.mobile,o.member_id,o.site_id,o.name,m.username";
        $order_info = model("order")->getInfo([["order_id", "=", $order_goods_info['order_id']]], $field,'o',$join);
        $var_parse = array(
            "username" => $order_info["username"],//会员名
            "goodsname" => $order_goods_info["sku_name"],//商品名称
            "orderno" => $order_info["order_no"],//商品名称
            "refundmoney" => $order_goods_info["refund_apply_money"],//退款申请金额
            "refundreason" => $order_goods_info["refund_reason"],//退款原因
            "refundno" => $order_goods_info["refund_no"],//退款原因
        );
        $shop_accept_message_model = new ShopAcceptMessage();
        $result = $shop_accept_message_model->getShopAcceptMessageList([['sam.site_id', '=', $order_info['site_id']]]);
        $list = $result['data'];
        if (!empty($list)) {
            $sms_model = new Sms();
            $email_model = new Email();
            $wechat_model = new WechatMessage();
            foreach ($list as $v) {
                $message_data = $data;
                $message_data["var_parse"] = $var_parse;
                if (!empty($v['mobile'])) {
                    //发送短信
                    $message_data["sms_account"] = $v["mobile"];//手机号
                    $sms_model->sendMessage($message_data);
                }
                //有邮箱才发送
                if (!empty($v['email'])) {
                    $message_data["email_account"] = $v['email'];//邮箱号
                    $email_model->sendMessage($message_data);
                }
                //微信模板消息
                if ($v['wx_openid'] != '') {
                    $data["openid"] = $v['wx_openid'];
                    $data["template_data"] = [
                        'keyword1' => $order_info["order_no"],
                        'keyword2' => time_to_date($order_goods_info['refund_action_time']),
                        'keyword3' => $order_goods_info['refund_apply_money'],
                    ];
                    $data["page"] = $this->handleUrl($order_info['order_type'], $order_goods_info['order_id']);
                    $wechat_model->sendMessage($data);
                }
            }
        }
    }

    /**
     * 买家已退款，卖家通知
     * @param $data
     */
    public function messageOrderRefundDelivery($data)
    {
        $order_id = $data["order_id"];
        $join = [
            [
                'member m',
                'o.member_id = m.member_id',
                'inner'
            ]
        ];
        $field = "o.order_no,o.mobile,o.member_id,o.site_id,m.username";
        $order_info = model("order")->getInfo([["order_id", "=", $order_id]], $field,'o',$join);
        $var_parse = array(
            "username" => $order_info["username"],
            "orderno" => $order_info["order_no"],//商品名称
        );
        $shop_accept_message_model = new ShopAcceptMessage();
        $result = $shop_accept_message_model->getShopAcceptMessageList([['sam.site_id', '=', $order_info['site_id']]]);
        $list = $result['data'];
        if (!empty($list)) {
            $sms_model = new Sms();
            $email_model = new Email();
            $wechat_model = new WechatMessage();
            foreach ($list as $v) {
                $message_data = $data;
                $message_data["var_parse"] = $var_parse;
                if (!empty($v['mobile'])) {
                    //发送短信
                    $message_data["sms_account"] = $v["mobile"];//手机号
                    $sms_model->sendMessage($message_data);
                }
                //有邮箱才发送
                if (!empty($v['email'])) {
                    $message_data["email_account"] = $v['email'];//邮箱号
                    $email_model->sendMessage($message_data);
                }
                //微信模板消息
                if ($v['wx_openid'] != '') {
                    $data["openid"] = $v['wx_openid'];
                    $data["template_data"] = [
                        'keyword1' => $data['order_goods_info']['order_no'],
                        'keyword2' => mb_substr($data['order_goods_info']['sku_name'], 0, 7, 'utf-8'),
                        'keyword3' => $data['order_goods_info']['num'],
                        'keyword4' => $data['order_goods_info']['refund_real_money'],
                    ];
                    $data["page"] = $this->handleUrl($order_info['order_type'], $data['order_goods_info']['order_id']);
                    $wechat_model->sendMessage($data);
                }
            }
        }
    }


    /**
     * 买家支付成功，卖家通知
     * @param $data
     */
    public function messageBuyerPaySuccess($data)
    {
        $order_id = $data["order_id"];
        $join = [
            [
                'member m',
                'o.member_id = m.member_id',
                'inner'
            ]
        ];
        $field = "o.order_no,o.site_id,o.order_name,o.order_money,o.order_type,o.pay_time,m.username";
        $order_info = model("order")->getInfo([["order_id", "=", $order_id]], $field,'o',$join);
        $var_parse = array(
            "username" => $order_info["username"],
            "orderno" => $order_info["order_no"],//订单编号
            "ordermoney" => $order_info["order_money"] //订单金额
        );
        $shop_accept_message_model = new ShopAcceptMessage();
        $result = $shop_accept_message_model->getShopAcceptMessageList([['sam.site_id', '=', $order_info['site_id']]]);
        $list = $result['data'];
        if (!empty($list)) {
            $sms_model = new Sms();
            $email_model = new Email();
            $wechat_model = new WechatMessage();
            foreach ($list as $v) {
                $message_data = $data;
                $message_data["var_parse"] = $var_parse;
                if (!empty($v['mobile'])) {
                    //发送短信
                    $message_data["sms_account"] = $v["mobile"];//手机号
                    $sms_model->sendMessage($message_data);
                }
                //有邮箱才发送
                if (!empty($v['email'])) {
                    $message_data["email_account"] = $v['email'];//邮箱号
                    $email_model->sendMessage($message_data);
                }
                if ($v['wx_openid'] != '') {
                    $data["openid"] = $v['wx_openid'];
                    $data["template_data"] = [
                        'keyword1' => time_to_date($order_info['pay_time']),
                        'keyword2' => $order_info['order_no'],
                        'keyword3' => str_sub($order_info['order_name']),
                        'keyword4' => $order_info['order_money'],
                    ];
                    $data["page"] = $this->handleUrl($order_info['order_type'], $order_id);
                    $wechat_model->sendMessage($data);
                }
            }
        }
    }


    /**
     * 买家收货成功，卖家通知
     * @param $data
     */
    public function messageBuyerReceive($data)
    {
        $order_id = $data["order_id"];
        $join = [
            [
                'member m',
                'o.member_id = m.member_id',
                'inner'
            ]
        ];
        $field = "o.order_no,o.order_name,o.site_id,o.order_type,o.name,o.full_address,o.sign_time,m.username";
        $order_info = model("order")->getInfo([["order_id", "=", $order_id]], $field,'o',$join);
        $var_parse = array(
            "username" => $order_info["username"],
            "orderno" => $order_info["order_no"]//订单编号
        );
        $shop_accept_message_model = new ShopAcceptMessage();
        $result = $shop_accept_message_model->getShopAcceptMessageList([['sam.site_id', '=', $order_info['site_id']]]);
        $list = $result['data'];
        if (!empty($list)) {
            $sms_model = new Sms();
            $email_model = new Email();
            $wechat_model = new WechatMessage();
            foreach ($list as $v) {
                $message_data = $data;
                $message_data["var_parse"] = $var_parse;
                if (!empty($v['mobile'])) {
                    //发送短信
                    $message_data["sms_account"] = $v["mobile"];//手机号
                    $sms_model->sendMessage($message_data);
                }
                //有邮箱才发送
                if (!empty($v['email'])) {
                    $message_data["email_account"] = $v['email'];//邮箱号
                    $email_model->sendMessage($message_data);
                }
                if ($v['wx_openid'] != '') {
                    $data["openid"] = $v['wx_openid'];
                    $data["template_data"] = [
                        'keyword1' => $order_info['full_address'],
                        'keyword2' => str_sub($order_info['name']),
                        'keyword3' => $order_info['order_no'],
                        'keyword4' => $order_info['order_name'],
                        'keyword5' => time_to_date($order_info['sign_time']),
                    ];
                    $data["page"] = $this->handleUrl($order_info['order_type'], $order_id);
                    $wechat_model->sendMessage($data);
                }
            }
        }
    }


    /******************************************************************************* 卖家通知 END *****************************************************************/
    /**
     * 处理订单链接
     * @param unknown $order_type
     * @param unknown $order_id
     * @return string
     */
    public function handleUrl($order_type, $order_id)
    {
        switch ($order_type) {
            case 2:
                return 'pages/order/detail_pickup/detail_pickup?order_id=' . $order_id;
                break;
            case 3:
                return 'pages/order/detail_local_delivery/detail_local_delivery?order_id=' . $order_id;
                break;
            case 4:
                return 'pages/order/detail_virtual/detail_virtual?order_id=' . $order_id;
                break;
            default:
                return 'pages/order/detail/detail?order_id=' . $order_id;
                break;
        }
    }


}