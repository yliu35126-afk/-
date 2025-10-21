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

namespace addon\sitemessage\model;

use app\model\BaseModel;
use app\model\shop\Shop;
use app\model\web\WebSite;

/**
 * 站内信
 */
class MemberMessage extends BaseModel
{

    public $sub_type = ['order' => '订单通知', 'delivery' => '物流通知', 'group' => '会员群发', 'promotion' => '活动通知', 'account' => '账户变动', 'servicer' => '官方客服'];
    public $order_type = ['create' => '创建', 'delivery' => '发货', 'receive' => '收货', 'close' => '关闭','refundrefuse'=>'订单退款拒绝提醒'];
    public $delivery_type = ['delivery' => '发货', 'receive' => '收货'];

    /**
     * 订单消息
     * @param int $order_id
     * @param string $type
     * @return array
     */
    public function orderMessageCreate($order_id = '', $title='',$content='',$action_type='')
    {
        $site_message_model = new Sitemessage();
        $join = [
            [
                'order_goods og',
                'og.order_id = o.order_id',
                'left'
            ]
        ];
        $order_info = model('order')->getInfo([['o.order_id', '=', $order_id]], 'o.*,og.sku_image', 'o', $join);
        $message_json = [
            'type' => 'system',
            'order_id' => $order_info['order_id'],
            'order_no' => $order_info['order_no'],
            'order_type' => $order_info['order_type'],
            'order_name' => $order_info['order_name'],
            'order_money' => $order_info['order_money'],
            'create_time' => $order_info['create_time']
        ];
        if(!empty($action_type)){
            $message_json['action_type'] = $action_type;
        }
        $image = $order_info['sku_image'];
        $data = [
            'site_id' => $order_info['site_id'],
            'type' => 'order',
            'title' => $title,
            'content' => $content,
            'image' => $image,
            'message_json' => $message_json,
            'member_ids' => explode(',', $order_info['member_id']),
            'sub_type'=>'order'
        ];
        $res = $site_message_model->create($data);
        return $res;
    }

    /**
     * 物流发信息
     * @param int $order_goods_id
     * @param string $delivery_type
     * @return array
     */
    public function deliveryMessageCreate($order_goods_id = 11, $delivery_type = 'delivery')
    {
        if (!isset($this->delivery_type[$delivery_type])) {
            return $this->error('delivery_type');
        }
        $site_message_model = new Sitemessage();
        $join = [
            [
                'order o',
                'og.order_id = o.order_id',
                'left'
            ]
        ];
        $field = 'o.order_id,o.order_name,o.order_money,o.create_time,o.order_no,o.site_id,o.member_id,o.name,o.mobile,o.full_address,og.order_goods_id,og.sku_image,og.sku_name';
        $order_info = model('order_goods')->getInfo([['og.order_goods_id', '=', $order_goods_id]], $field, 'og', $join);
        $message_json = [
            'type' => '',
            'order_id' => $order_info['order_id'],
            'order_name' => $order_info['order_name'],
            'order_money' => $order_info['order_money'],
            'order_goods_id' => $order_info['order_goods_id'],
            'sku_name' => $order_info['sku_name'],
            'name' => $order_info['name'],
            'mobile' => $order_info['mobile'],
            'full_address' => $order_info['full_address'],
            'create_time' => $order_info['create_time']
        ];
        $title = '物流更新';
        $content = "您的订单:" . $order_info['order_name'] . ",订单号为:" . $order_info['order_no'] . "已" . $this->delivery_type[$delivery_type];
        $image = $order_info['sku_image'];
        $data = [
            'site_id' => $order_info['site_id'],
            'type' => $delivery_type,
            'title' => $title,
            'content' => $content,
            'image' => $image,
            'message_json' => $message_json,
            'member_ids' => explode(',', $order_info['member_id']),
            'sub_type'=>'delivery'
        ];
        $res = $site_message_model->create($data);
        return $res;
    }

    /**
     * 账户变更消息
     * @param int $account_id
     * @return array
     */
    public function accountMessageCreate($account_id = 0)
    {
        $site_message_model = new Sitemessage();
        $join = [
            [
                'member m',
                'ma.member_id = m.member_id',
                'left'
            ]
        ];
        $order_info = model('member_account')->getInfo([['id', '=', $account_id]], '', 'ma', $join);
        switch ($order_info['account_type']) {
            case 'point':
                $account_type_str = '积分';
                break;
            case 'balance':
                $account_type_str = '余额';
                break;
            case 'growth':
                $account_type_str = '成长值';
                break;
            case 'balance_money':
                $account_type_str = '余额';
                break;
        }
        $account_type_str .= $order_info['account_data'] > 0 ? '增加' : '减少';
        $message_json = [
            'account_type' => $order_info['account_type'],
            'account_data' => $order_info['account_data'],
            'remark' => $order_info['remark'],
            'from_type' => $order_info['from_type'],
            'type_name' => $order_info['type_name'],
        ];
        $title = $account_type_str;
        $content = "您的账户:" . $order_info['nickname'] . ',' . $order_info['remark'];
        $data = [
            'site_id' => '',
            'type' => 'account',
            'title' => $title,
            'content' => $content,
            'image' => '',
            'message_json' => $message_json,
            'member_ids' => explode(',', $order_info['member_id']),
            'sub_type' => 'account'
        ];
        $res = $site_message_model->create($data);
        return $res;
    }

    /**
     * 会员充值成功站内信
     * @param $order_id
     * @param string $action_type
     * @return array
     */
    public function accountMessageCreateForMemberRechargeOrder($order_id)
    {
        $site_message_model = new Sitemessage();
        $order_info = model("member_recharge_order")->getInfo([ [ "order_id", "=", $order_id ] ], "member_id,site_id,nickname,order_no,pay_time,face_value,price");
        $title = '账户充值';
        $content = "您的账户:" . $order_info['nickname'] . ',充值金额：' . $order_info['price'].'元';
        $message_json = [
            'account_type' => 'balance',
            'account_data' => $order_info['price'],
            'remark' => '充值'.$order_info['price'],
            'from_type' => 'memberrecharge',
            'type_name' => '账户充值',
        ];
        $data = [
            'site_id' => $order_info['site_id'],
            'type' => 'account',
            'title' => $title,
            'content' => $content,
            'image' => '',
            'message_json' => $message_json,
            'member_ids' => explode(',', $order_info['member_id']),
            'sub_type' => 'account'
        ];
        $res = $site_message_model->create($data);
        return $res;
    }

    /**
     * 提现成功站内信
     * @param $id
     * @return array
     */
    public function accountMessageCreateForMemberWithdrawal($id)
    {
        $site_message_model = new Sitemessage();
        $info = model("member_withdraw")->getInfo([ [ "id", "=", $id ] ]);
        $title = '账户提现';
        $content = "您的账户:" . $info['member_name'] . '，申请提现金额：' . $info['apply_money'].'元';
        $message_json = [
            'account_type' => 'balance',
            'account_data' => $info['apply_money'],
            'remark' => '提现'.$info['apply_money'],
            'from_type' => 'withdraw',
            'type_name' => '账户提现',
        ];
        $data = [
            'site_id' => '',
            'type' => 'account',
            'title' => $title,
            'content' => $content,
            'image' => '',
            'message_json' => $message_json,
            'member_ids' => explode(',', $info['member_id']),
            'sub_type' => 'account'
        ];
        $res = $site_message_model->create($data);
        return $res;
    }
    /**
     * 活动消息
     * @param int $promotion_id
     * @param string $promotion_type
     * @return array
     */
    public function promotionMessageCreate($promotion_id = 1, $promotion_type = 'freeshipping')
    {
        return $this->success();
        //执行效率存在问题，暂时不能添加
        $site_message_model = new Sitemessage();
        $promotion_type_arr = ['freeshipping','bargain','bundling','coupon','discount','groupbuy','manjian','pintuan','presale','present','wholesale','seckill','topic'];
        if(!in_array($promotion_type,$promotion_type_arr)){
            return $this->error('未添加该活动类型');
        }

        $star_time = '';
        $end_time = '';
        $image = '';
        $goods_id = '';
        switch ($promotion_type) {
            case 'freeshipping':
                //满减包邮
                $info = model('promotion_freeshipping')->getInfo([['freeshipping_id','=',$promotion_id]]);
                $title = "满减包邮活动已开启";
                $content = "满减包邮-已开启";
                break;
            case 'bargain':
                //砍价
                $info = model('promotion_bargain')->getInfo([['bargain_id','=',$promotion_id]]);
                $image = model('goods')->getColumn(['goods_id'=>$info['goods_id']],'goods_image');
                $title = $info['bargain_name']."已开启";
                $content = $info['bargain_name']."活动-已开启";
                $image = empty($image)?'':$image['0'];
                $star_time = $info['start_time'];
                $end_time = $info['end_time'];
                $goods_id = $info['goods_id'];
                break;
            case 'bundling':
                //组合套餐
                $info = model('promotion_bundling')->getInfo([['bl_id','=',$promotion_id]]);
                $image = model('promotion_bundling_goods')->getColumn(['bl_id'=>$promotion_id],'sku_image,sku_id');
                $title = $info['bl_name']."已开启";
                $content = $info['bl_name']."活动-已开启";
                $goods_id = $image['0']['sku_id'];
                $image = empty($image)?'':$image['0']['sku_image'];
                break;
            case 'coupon':
                //优惠卷
                $info = model('promotion_coupon')->getInfo([['coupon_id','=',$promotion_id]]);
                $title = $info['coupon_name']."已开启";
                $content = $info['coupon_name']."活动-已开启";
                $star_time = $info['start_time'];
                $end_time = $info['end_time'];
                break;
            case 'discount':
                //限时折扣
                $info = model('promotion_discount')->getInfo([['discount_id','=',$promotion_id]]);
                $image = model('promotion_discount_goods')->getColumn(['discount_id'=>$promotion_id],'sku_image,sku_id');
                $title = $info['discount_name']."活动已开启";
                $content = $info['discount_name']."活动-已开启";
                $star_time = $info['start_time'];
                $end_time = $info['end_time'];
                $goods_id = $image['0']['sku_id'];
                $image = empty($image)?'':$image['0']['sku_image'];
                break;
            case 'groupbuy':
                //团购
                $info = model('promotion_groupbuy')->getInfo([['groupbuy_id','=',$promotion_id]]);
                $title = $info['goods_name']."团购活动已开启";
                $content = $info['goods_name']."团购活动-已开启";
                $star_time = $info['start_time'];
                $end_time = $info['end_time'];
                $image = $info['goods_image'];
                $goods_id = $info['goods_id'];
                break;
            case 'manjian':
                // 满减活动
                $info = model('promotion_manjian')->getInfo([['manjian_id','=',$promotion_id]]);
                $title = $info['manjian_name']."活动已开启";
                $content = empty($info['remark'])?$info['pintuan_name']."活动-已开启":$info['remark'];
                $star_time = $info['start_time'];
                $end_time = $info['end_time'];
                break;
            case 'pintuan':
                //拼团
                $info = model('promotion_pintuan')->getInfo([['pintuan_id','=',$promotion_id]]);
                $image = model('goods')->getColumn(['goods_id'=>$info['goods_id']],'goods_image');
                $title = $info['pintuan_name']."活动已开启";
                $content = empty($info['remark'])?$info['pintuan_name']."活动-已开启":$info['remark'];
                $image = empty($image)?'':$image['0'];
                $star_time = $info['start_time'];
                $end_time = $info['end_time'];
                $goods_id = $info['goods_id'];
                break;
            case 'presale':
                //预售列表
                $info = model('promotion_presale')->getInfo([['presale_id','=',$promotion_id]]);
                $image = model('goods')->getColumn(['goods_id'=>$info['goods_id']],'goods_image');
                $title = $info['presale_name']."活动已开启";
                $content = $info['remark'];
                $image = empty($image)?'':$image['0'];
                $star_time = $info['start_time'];
                $end_time = $info['end_time'];
                break;
            case 'present':
                // 赠品
                $info = model('promotion_present')->getInfo([['present_id','=',$promotion_id]]);
                $image = model('goods')->getColumn(['goods_id'=>$info['goods_id']],'goods_image');
                $title = "赠品活动已开启";
                $content = "赠品活动-已开启";
                $image = empty($image)?'':$image['0'];
                $star_time = $info['start_time'];
                $end_time = $info['end_time'];
                break;
            case 'wholesale':
                // 批发管理
                $info = model('wholesale_goods')->getInfo([['wholesale_goods_id','=',$promotion_id]]);
                $title = $info['goods_name']."批发活动已开启";
                $content = $info['goods_name']."批发活动-已开启";
                $image = $info['goods_image'];
                break;
            case 'seckill':
                //限时秒杀
                $info = model('promotion_seckill')->getInfo([['id','=',$promotion_id]]);
                $title = $info['seckill_name']."活动已开启";
                $content = $info['seckill_name']."活动-已开启";
                $star_time = $info['start_time'];
                $end_time = $info['end_time'];
                $image = $info['goods_image'];
                $goods_id = $info['goods_id'];
                break;
            case 'topic':
                //专题活动
                $info = model('promotion_topic')->getInfo([['topic_id','=',$promotion_id]]);
                $title = $info['topic_name']."活动已开启";
                $content = $info['topic_name']."活动-已开启";
                $image = $info['topic_adv'];//专题广告图片
                $star_time = $info['start_time'];
                $end_time = $info['end_time'];
                break;
        }

        //获取member_ids
        $member_arr_condition = [['site_id','=',$info['site_id']],['is_subscribe','=',1]];
        $member_arr = model('shop_member')->getColumn($member_arr_condition,'member_id');
        $message_json = [
            'promotion_type' => $promotion_type,
            'promotion_id' => $promotion_id,
            'goods_id' => $goods_id,
            'start_time' => $star_time,
            'end_time' => $end_time,
        ];
        $data = [
            'site_id' => $info['site_id'],
            'type' => 'promotion',
            'title' => $title,
            'content' => $content,
            'image' => $image,
            'message_json' => $message_json,
            'member_ids' => $member_arr,
            'sub_type'=>'promotion'
        ];
        $res = $site_message_model->create($data);
        return $res;
    }

    /**
     * 发送客服消息
     * @param int $promotion_id
     * @param string $promotion_type
     */
    public function servicerMessageCreate($params){
        $member_ids = $params['member_ids'];//站点id
        $site_id = $params['site_id'];//站点id
//        $temp1 = htmlspecialchars_decode($params['content']);//把一些预定义的 HTML 实体转换为字符
//        $temp2 = strip_tags($temp1);//函数剥去字符串中的 HTML、XML 以及 PHP 的标签,获取纯文本内容
//        $content = $temp2;
        $content = $params['content'];
        if(empty($content)){
            return $this->success();
        }

        $title = '';
        if($site_id > 0){
            $shop_model = new Shop();
            $shop_info = $shop_model->getShopInfo([['site_id', '=', $site_id]])['data'] ?? [];
            $title = $shop_info['site_name'] ?? '';
        }else{
            //查询网站信息
            $website_model = new WebSite();
            $website_info = $website_model->getWebSite([ [ "site_id", "=", 0 ] ], "title")['data'] ?? [];
            $title = $website_info[ "title" ] ?? '';
        }

        $data = [
            'site_id' => $site_id,
            'type' => 'servicer',
            'title' => $title,
            'content' => $content,
            'image' => '',
            'message_json' => [],
            'member_ids' => $member_ids,
            'sub_type'=>'servicer'
        ];
        $site_message_model = new Sitemessage();
        $res = $site_message_model->create($data);
        return $res;
    }

    public function groupMessageCreate($params){
        $site_messageModel = new Sitemessage();
        $res = $site_messageModel->create($params);
        return $res;
    }
    /**
     * 共有的站内信调用发送
     * @param $params
     */
    public function send($params){
        $sub_type = $params['sub_type'];
        switch ($sub_type){
            case 'servicer'://客服
                $this->servicerMessageCreate($params);
                break;
            case 'promotion'://活动
                $this->promotionMessageCreate($params);
                break;
            case 'order'://订单
                $this->orderMessageCreate($params);
                break;
            case 'group'://群发
                $this->groupMessageCreate($params);
                break;
            case 'account'://账户
                $this->accountMessageCreate($params);
                break;
        }
        return $this->success();
    }


}