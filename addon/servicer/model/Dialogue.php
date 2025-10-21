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

namespace addon\servicer\model;

use addon\sitemessage\model\MemberMessage;
use addon\sitemessage\model\Sitemessage;
use app\model\BaseModel;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Model;

class Dialogue extends BaseModel
{
    /**
     * 消息类型，字符串
     */
    const CONTENTTYPE_STRING = 0;
    /**
     * 消息类型，商品SKU
     */
    const CONTENTTYPE_GOODSKU = 1;
    /**
     * 消息类型，订单
     */
    const CONTENTTYPE_ORDER = 2;
    /**
     * 消息类型，图片
     */
    const CONTENTTYPE_IMAGE = 3;

    /**
     * 状态
     * @param mixed $content_type
     * @param string $type
     * @return array
     */
    public static function contentType($content_type = null, $type = 'content_type')
    {
        $data   = array(
            array(
                'content_type' => self::CONTENTTYPE_STRING,
                'name'         => '字符串',
                'type'         => 'string',
                'const'        => 'CONTENTTYPE_STRING',
            ),
            array(
                'content_type' => self::CONTENTTYPE_GOODSKU,
                'name'         => '商品',
                'type'         => 'goodssku',
                'const'        => 'CONTENTTYPE_GOODSKU',
            ),
            array(
                'content_type' => self::CONTENTTYPE_ORDER,
                'name'         => '订单',
                'type'         => 'order',
                'const'        => 'CONTENTTYPE_ORDER',
            ),
            array(
                'content_type' => self::CONTENTTYPE_IMAGE,
                'name'         => '图片',
                'type'         => 'image',
                'const'        => 'CONTENTTYPE_IMAGE',
            ),
        );
        $result = [];
        if (is_null($content_type)) {
            $result = $data;
        } else {
            if (is_array($content_type)) {

                foreach ($data as $val) {

                    if (in_array($val[$type], $content_type)) {
                        $result[] = $val;
                    }
                }
            } else {
                foreach ($data as $val) {
                    if ($content_type == $val[$type]) {
                        $result = $val;
                        break;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * 插入对话数据
     * @param integer $type 会话方向，0 客户，1 客服
     * @param integer $memberId 会员ID
     * @param integer $serviceId 客服ID
     * @param integer $contentType 内容类型
     * @param integer $read 是否已读
     * @param integer $shopId 商户ID
     * @param integer $userId 用户ID
     * @param string $consumerSay 客户咨询内容
     * @param string $servicerSay 客服回答内容
     * @param integer $goodSkuId 商品ID
     * @param integer $orderId 订单ID
     * @param string $relate_data 相关数据
     * @return integer
     */
    public function createDialogue($type, $memberId, $serviceId, $contentType, $read, $shopId, $userId, $consumerSay = '', $servicerSay = '', $goodSkuId = 0, $orderId = 0, $relate_data = '')
    {
        $content = empty($servicerSay)?$consumerSay:$servicerSay;
        $result = model('servicer_dialogue')->add([
            'member_id'    => $memberId,
            'servicer_id'  => $serviceId,
            'create_day'   => date('Y-m-d'),
            'create_time'  => date('H:i:s'),
            'add_time'     => time(),
            'content_type' => $contentType,
            'read'         => $read,
            'shop_id'      => $shopId,
            'goods_sku_id' => $goodSkuId,
            'order_id'     => $orderId,
            'consumer_say' => $consumerSay,
            'servicer_say' => $servicerSay,
            'type'         => $type,
            'relate_data'  => $relate_data,
            'content' => $content,
            'message' => $this->handleMessage($consumerSay, $servicerSay, $contentType),
        ]);
        if(addon_is_exit('sitemessage')){
            $temp_content = '';
            switch ($contentType){
                case '0';//文本
                    $temp_content = $content;
                    break;
                case '1';//商品
                    $temp_content = '您收到了一条商品消息';
                    break;
                case '2';//订单
                    $temp_content = '您收到了一条订单消息';
                    break;
                case '3';//图片
                    $temp_content = '您收到了一条图片消息';
                    break;
            }
            $message_model = new MemberMessage();
            $message_data = array(
                'site_id' => $shopId,
                'member_ids' => [$memberId],
                'content' => $temp_content,
                'sub_type' => 'servicer'
            );
            $message_model->send($message_data);
        }
        return $result;
    }

    /**
     * 处理纯文本消息
     * @param string $consumerSay
     * @param string $servicerSay
     * @param $contentType
     * @return mixed
     */
    private function handleMessage(string $consumerSay, string $servicerSay, $contentType = 0)
    {
        $message = '';
        if ($contentType == self::CONTENTTYPE_STRING) {
            if (!empty($servicerSay)) {
                $message = $servicerSay;
            }
            if (!empty($consumerSay)) {
                $message = $consumerSay;
            }
            $message = strip_tags($message);
            $message = str_replace(['&nbsp;', '\r\n', '\r', '\n'], '', $message);
        }
        return $message;
    }

    /**
     * 设置消息已读状态
     * @param $id
     * @param bool $read
     * @return int
     * @throws DbException
     */
    public function setDialogueRead($id, $read = true)
    {
        $is_read = $read ? 1 : 0;
        $result = model('servicer_dialogue')->setFieldValue(['id' => $id], 'read', $read ? 1 : 0);

        if(addon_is_exit('sitemessage')){
            if($is_read){
                $info = $this->getInfo([['id', '=', $id]])['data'] ?? [];
                $member_id = $info['member_id'];
                $site_message_model = new Sitemessage();
                $site_message_model->allSeed($member_id, 'servicer');
            }
        }
        return $result;
    }

    /**
     * 获取会话详情
     * @param $id
     * @return array|Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getDialogue($id)
    {
        return model('servicer_dialogue')->getInfo(['id' => $id]);
    }

    /**
     * 查询聊天记录
     * @param $memberId
     * @param int $page
     * @param int $limit
     * @param int $siteId
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getDialogueList($memberId, $page = 1, $limit = 20, $siteId = 0)
    {
        $alias = 'sd';
        $field = ['sd.id', 'sd.create_day', 'sd.content_type', 'sd.type', 'sd.create_time', 'sd.content_type', 'sd.read', 'sd.goods_sku_id', 'sd.order_id', 'sd.consumer_say', 'sd.servicer_say'];
        $order = ['sd.id' => 'desc'];

        $join = [
            ['servicer s', 's.user_id = sd.servicer_id', 'LEFT'], // 关联客服
            ['shop sp', 'sp.site_id = sd.shop_id', 'LEFT'], // 关联客服
            ['goods_sku g', 'g.sku_id = sd.goods_sku_id', 'LEFT'], // 关联商品，聊天记录为商品时
            ['order o', 'o.order_id = sd.order_id', 'LEFT'],
            ['order_goods og', 'og.order_id = o.order_id', 'LEFT']
        ];

        $field = array_merge($field, ['s.is_platform', 's.avatar', 's.nickname']);
        $field = array_merge($field, ['g.goods_name', 'g.sku_image', 'g.price']);
        $field = array_merge($field, ['o.order_no', 'o.goods_money', 'o.order_money', 'o.order_status', 'o.order_status_name']);
        $field = array_merge($field, ['og.sku_name', 'og.sku_image']);
        $field = array_merge($field, ['sp.site_name as shop_name', 'sp.logo']);

        $condition[] = ['sd.member_id', '=', $memberId];
        $condition[] = ['sd.shop_id', '=', $siteId];

        return model('servicer_dialogue')->pageList($condition, $field, $order, $page, $limit, $alias, $join, null, $limit);
    }

    /**
     * 获取聊天列表相关信息-分页查询
     * @param array $map
     * @param bool $field
     * @param string $order
     * @param int $page
     * @param int $list_rows
     * @param string $alias
     * @param array $join
     * @param null $group
     * @param null $limit
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getPageList($map = [], $field = true, $order = '', $page = 1, $list_rows = PAGE_LIST_ROWS, $alias = '', $join = [], $group = null, $limit = null)
    {
        return model('servicer_dialogue')->pageList($map, $field, $order, $page, $list_rows, $alias, $join, $group, $limit);
    }

    /**
     * 设置消息读取状态
     * @param $condition
     * @param bool $read
     * @return int
     */
    public function setDialoguesRead($condition, $read = true)
    {
        $res =  model('servicer_dialogue')->setFieldValue($condition, 'read', $read ? 1 : 0);
        return $res;
    }

    /**
     * 获取信息
     * @param $condition
     * @param bool $field
     * @return array
     */
    public function getInfo($condition, $field = true)
    {
        $res = model('servicer_dialogue')->getInfo($condition, $field);
        $res = $this->handleData($res);
        return $this->success($res);
    }

    /**
     * 处理数据
     * @param $info
     * @return mixed
     */
    private function handleData($info)
    {
        if (!empty($info)) {
            if (isset($info['relate_data'])) {
                $info['relate_data_parse'] = !empty($info['relate_data']) ? json_decode($info['relate_data'], true) : [];
            }
        }
        return $info;
    }
}
