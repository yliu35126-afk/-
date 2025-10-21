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

namespace addon\servicer\api\controller;

use addon\servicer\model\Dialogue;
use addon\servicer\model\Keyword as KeywordModel;
use addon\servicer\model\Member;
use addon\servicer\model\Servicer;
use app\api\controller\BaseApi;
use app\model\goods\Goods;
use app\model\order\Order;
use app\model\shop\Shop;
use app\model\upload\Upload;
use app\model\web\WebSite as WebsiteModel;
use Exception;
use GatewayClient\Gateway;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Config;
use think\facade\Db;

/**
 * 客户端客服相关API
 */
class Chat extends BaseApi
{
    /**
     * 构造函数
     */
    public function __construct()
    {
        Config::load(__DIR__ . "/../../config/gateway_client.php");

        // 注册GateWayClient 到 GatewayWorker
        Gateway::$registerAddress = @config()['register_address'];

        parent::__construct();
    }

    /**
     * 查找当前是否有客服在线
     * @return false|string
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function hasServicers()
    {
        $token = $this->checkToken();
        if ($token['code'] < 0) return $this->response($token);

        $site_id = $this->params['site_id'] ?? 0;
        if (empty($site_id)) return $this->response($this->error('没有指定店铺'));

        // 在线客服
        $servicer_list = (new Servicer)->getOnlineList([['shop_id', '=', $site_id]])['data'];
        // 统计客服在线数量
        $onlineCount = 0;

        foreach ($servicer_list as $val) {
            // 判断客服是否连接服务
            $online = @Gateway::isUidOnline('ns_servicer_' . $val['user_id']) ?? 0;
            if ($online) $onlineCount += 1;
        }
        return $this->response($this->success(['online_count' => $onlineCount]));
    }

    /**
     * 绑定WebSocket client_id 和 member_id / user_id
     * @return false|string
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function bind()
    {
        $token = $this->checkToken();
        if ($token['code'] < 0) return $this->response($token);

        $site_id = $this->params['site_id'] ?? 0;
        if (empty($site_id) && $site_id != 0) {
            return $this->response($this->error('', '没有指定商家'));
        }
        $client_id = $this->params['client_id'] ?? '';
        if (empty($client_id)) {
            return $this->response($this->error('', '缺少必要的参数'));
        }

        $member_id = $this->member_id;

        // 检测当前用户是否仍然在线
        $isOnline = Gateway::isOnline($client_id);

        Gateway::bindUid($client_id, $member_id);

        // 获取匹配的客服
        $servicerModel = new Servicer();
        $servicerList  = @$servicerModel->assigning([['shop_id', '=', $site_id]]);
        if (empty($servicerList)) {
            return $this->response($this->error('', '客服不在线'));
        }
        foreach ($servicerList as $item) {
            // ws是否在线
            $online = @Gateway::isUidOnline('ns_servicer_' . $item['user_id']) ?? 0;
            if (!$online) continue;
            $servicer = $item;
            if (!empty($servicer)) break;
        }
        if (empty($servicer)) {
            return $this->response($this->error('', '客服不在线'));
        }
        // 绑定客服
        $memberModel = new Member();
        $id          = $memberModel->createMember($member_id, $servicer['user_id'], $isOnline, $client_id, $site_id);
        if (!$id) {
            return $this->response($this->error('', '客服连接异常'));
        }

        // 向客服通知，有会员咨询
        $member = (new Member)->getMember($member_id, $servicer['user_id']);
        Gateway::sendToUid(
            'ns_servicer_' . $servicer['user_id'],
            json_encode(['type' => 'connect', 'data' => $member])
        );

        // 客服信息
        $servicer_info                = $servicerModel->handleServicerInfo([['user_id', '=', $servicer['user_id']]], $site_id);
        $servicer_info['servicer_id'] = $servicer['user_id'];

        return $this->response($this->success($servicer_info));
    }

    /**
     * 获取店铺信息
     * @return false|string
     */
    public function siteInfo()
    {
        $token = $this->checkToken();
        if ($token['code'] < 0) {
            return $this->response($token);
        }

        $site_id = $this->params['site_id'] ?? 0;
        if (empty($site_id) && $site_id != 0) {
            return $this->response($this->error('没有指定站点'));
        }

        if ($site_id == 0) {
            $website_model               = new WebsiteModel();
            $website_info                = $website_model->getWebSite([['site_id', '=', 0]], '*');
            $result['data']['logo']      = $website_info['data']['logo'];
            $result['data']['site_name'] = '平台客服';
        } else {
            $result = (new Shop)->getShopInfo(['site_id' => $site_id, ['site_name', 'logo']]);
        }

        return $this->response($this->success($result));
    }

    /**
     * 订单详情
     * @return array|false|string
     */
    public function orderDetail()
    {
        $token = $this->checkToken();
        if ($token['code'] < 0) {
            return $this->response($token);
        }

        $orderId     = $this->params['order_id'] ?? 0;
        $condition   = array(
            ['order_id', '=', $orderId]
        );
        $orderDetail = (new Order)->getOrderDetail($condition);
        return $orderDetail;
    }

    /**
     * 商品详情
     * @return array|false|string
     */
    public function goodSkuDetial()
    {
        $token = $this->checkToken();
        if ($token['code'] < 0) {
            return $this->response($token);
        }

        $skuId          = $this->params['sku_id'] ?? 0;
        $goodsSkuDetail = (new Goods)->getGoodsSkuInfo(['sku_id' => $skuId]);

        return $goodsSkuDetail;
    }

    /**
     * 发送聊天内容
     * @return false|string
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function say()
    {
        $token = $this->checkToken();
        if ($token['code'] < 0) return $this->response($token);

        $servicer_id = $this->params['servicer_id'] ?? 0;
        $contentType = $this->params['content_type'] ?? '';
        $message     = $this->params['message'] ?? '';
        $goodsId     = $this->params['goods_id'] ?? 0;
        $orderId     = $this->params['order_id'] ?? 0;
        $siteId      = $this->params['site_id'] ?? 0;
        $relate_data = $this->params['relate_data'] ?? '';

        if (empty($siteId) && $siteId != 0) {
            return $this->response($this->error('没有指定商家'));
        }

        if (empty($message) && empty($goodsId) && empty($orderId)) {
            return $this->response($this->error('不能发送空消息哦！'));
        }

        try {
            $isServicerOnline = @Gateway::isUidOnline('ns_servicer_' . $servicer_id);
        } catch (Exception $e) {
            $isServicerOnline = false;
        }

//        $read = $isServicerOnline ? 1 : 0;
        $read = 0;

        // 消息持久化逻辑
        $dialogueModel = new Dialogue();
        $servicerModel = new Servicer();

        // 客服不在线时，不推送
        if (!$isServicerOnline) {
            // 重新匹配的客服
            $servicerList  = @$servicerModel->assigning([['shop_id', '=', $siteId]]);
            $servicer      = [];
            foreach ($servicerList as $item) {
                // ws是否在线
                $online = @Gateway::isUidOnline('ns_servicer_' . $item['user_id']) ?? 0;
                if (!$online) {
                    continue;
                }
                $servicer = $item;
                if (!empty($servicer)) {
                    break;
                }
            }
            if (empty($servicer)) {
                $dialogueModel->createDialogue(0, $this->member_id, 0, $contentType, 0, $siteId, 0, $message, '', $goodsId, $orderId, $relate_data);

                // 关键词回复
                if ($contentType == Dialogue::CONTENTTYPE_STRING) {
                    $keyword_model = new KeywordModel();
                    $keyword_reply = $keyword_model->getContentByKeyword($siteId, strip_tags($message));
                    if (!empty($keyword_reply)) {
                        $keyword_reply = '<p>' . $keyword_reply . '</p>';
                        $dialogueModel = new Dialogue();
                        $dialogueId    = $dialogueModel->createDialogue(1, $this->member_id, 0, $contentType, 0, $siteId, 0, '', $keyword_reply, 0, 0);

                        // 客服信息
                        $servicer_info                = $servicerModel->handleServicerInfo([], $siteId);
                        $servicer_info['servicer_id'] = 0;

                        Gateway::sendToUid(
                            $this->member_id,
                            json_encode(['type' => 'connect', 'data' => $servicer_info])
                        );
                        $keyword_reply_dialogue = array_merge($dialogueModel->getDialogue($dialogueId), $servicer_info);
                        // 转发消息至会员
                        Gateway::sendToUid($this->member_id, json_encode(['type' => Dialogue::contentType($contentType)['type'], 'data' => $keyword_reply_dialogue]));
                    }
                }
                return $this->response($this->success(['read' => $read]));
            }
            // 绑定客服
            $servicer_id = $servicer['user_id'];
            $client_id   = Gateway::getClientIdByUid($this->member_id);
            $memberModel = new Member();
            $id          = $memberModel->createMember($this->member_id, $servicer['user_id'], 1, $client_id, $siteId);
            if (!$id) {
                return $this->response($this->success(['read' => $read]));
            }
            // 向客服通知，有会员咨询
            $member = (new Member)->getMember($this->member_id, $servicer['user_id']);
            Gateway::sendToUid(
                'ns_servicer_' . $servicer['user_id'],
                json_encode(['type' => 'connect', 'data' => $member])
            );

            // 客服信息
            $servicer_info                = $servicerModel->handleServicerInfo([['user_id', '=', $servicer['user_id']]], $siteId);
            $servicer_info['servicer_id'] = $servicer['user_id'];

            Gateway::sendToUid(
                $this->member_id,
                json_encode(['type' => 'connect', 'data' => $servicer_info])
            );
        } else {
            // 客服信息
            $servicer_info                = $servicerModel->handleServicerInfo([['user_id', '=', $servicer_id]], $siteId);
            $servicer_info['servicer_id'] = $servicer_id;
        }

        // 消息持久化逻辑
        $dialogueId = $dialogueModel->createDialogue(0, $this->member_id, $servicer_id, $contentType, $read, $siteId, 0, $message, '', $goodsId, $orderId, $relate_data);
        // 获取消息数据
        $dialogue = $dialogueModel->getDialogue($dialogueId);

        $type = Dialogue::contentType($contentType)['type'];

        // 关键词回复
        if ($contentType == Dialogue::CONTENTTYPE_STRING) {
            $keyword_model = new KeywordModel();
            $keyword_reply = $keyword_model->getContentByKeyword($siteId, strip_tags($message));
            if (!empty($keyword_reply)) {
                $keyword_reply          = '<p>' . $keyword_reply . '</p>';
                $dialogueModel          = new Dialogue();
                $dialogue_id            = $dialogueModel->createDialogue(1, $this->member_id, $servicer_id, $contentType, 0, $siteId, $servicer['user_id'] ?? 0, '', $keyword_reply, 0, 0);

                $keyword_reply_dialogue = array_merge($dialogueModel->getDialogue($dialogue_id), $servicer_info);
                // 转发消息至会员
                Gateway::sendToUid($this->member_id, json_encode(['type' => $type, 'data' => $keyword_reply_dialogue]));
            }
        }

        // 发送给所有连接的客服
        $memberModel = new Member();
        $memberList  = $memberModel->getList([['member_id', '=', $this->member_id], ['online', '=', 1], ['shop_id', '=', $siteId]]);
        if (!empty($memberList)) {
            foreach ($memberList as $item) {
                $isServicerOnline = @Gateway::isUidOnline('ns_servicer_' . $item['servicer_id']);
                $isServicerOnline = $isServicerOnline ? 1 : 0;
                if ($isServicerOnline) {
                    // 转发消息至客服·
                    Gateway::sendToUid('ns_servicer_' . $item['servicer_id'], json_encode(['type' => $type, 'data' => $dialogue]));

                    // 关键词自动回复的消息转发到客服端
                    if (isset($keyword_reply_dialogue) && !empty($keyword_reply_dialogue)) {
                        Gateway::sendToUid('ns_servicer_' . $item['servicer_id'], json_encode(['type' => 'keyword_reply', 'data' => $keyword_reply_dialogue]));
                    }
                }
            }
        } else {
            if (!empty($servicer_id)) {
                // 向客服通知，有会员咨询
                $member = (new Member)->getMember($this->member_id, $servicer_id);
                Gateway::sendToUid(
                    'ns_servicer_' . $servicer_id,
                    json_encode(['type' => 'connect', 'data' => $member])
                );

                // 客服信息
                $servicer_info                = $servicerModel->handleServicerInfo([['user_id', '=', $servicer_id]], $siteId);
                $servicer_info['servicer_id'] = $servicer_id;

                Gateway::sendToUid(
                    $this->member_id,
                    json_encode(['type' => 'connect', 'data' => $servicer_info])
                );
                Gateway::sendToUid('ns_servicer_' . $servicer_id, json_encode(['type' => $type, 'data' => $dialogue]));

                // 关键词自动回复的消息转发到客服端
                if (isset($keyword_reply_dialogue) && !empty($keyword_reply_dialogue)) {
                    Gateway::sendToUid('ns_servicer_' . $servicer_id, json_encode(['type' => 'keyword_reply', 'data' => $keyword_reply_dialogue]));
                }
            }
        }
        return $this->response($this->success(['read' => $read]));
    }

    /**
     * 获取聊天记录表
     * @return false|string
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function dialogs()
    {
        $token = $this->checkToken();
        if ($token['code'] < 0) {
            return $this->response($token);
        }

        $page   = $this->params['page'] ?? 1;
        $limit  = $this->params['limit'] ?? 5;
        $siteId = $this->params['site_id'] ?? 0;

        if (empty($siteId) && $siteId != 0) {
            return $this->response($this->error('没有指定商家'));
        }

        $pagelist = (new Dialogue())->getDialogueList($this->member_id, $page, $limit, $siteId);
        if (!empty($pagelist) && !empty($pagelist['list']) && count($pagelist['list']) > 0) {
            $pagelist['list'] = array_reverse($pagelist['list']);
        }

        return $this->response($this->success($pagelist));
    }

    /**
     * 客户端主动结束会话
     * @return false|string
     * @throws DbException
     */
    public function bye()
    {
        $token = $this->checkToken();
        if ($token['code'] < 0) return $this->response($token);

        $servicer_id = $this->params['servicer_id'] ?? 0;
        if (empty($servicer_id)) return $this->response($this->error('没有指定客服'));

        $member_id = $this->member_id;
        $client_id = Gateway::getClientIdByUid($member_id);

        // 查询连接本会员的所有客服（数据库）
        $memberModel = new Member();
        $dataList    = $memberModel->getList([['member_id', '=', $this->member_id], ['online', '=', 1]]);

        // 给本会员的所有客服，发送离线信号
        if (!empty($dataList)) {
            foreach ($dataList as $item) {
                // 判断当前客服是否在线（workerman服务）
                $isServicerOnline = @Gateway::isUidOnline('ns_servicer_' . $item['servicer_id']);
                $isServicerOnline = $isServicerOnline ? 1 : 0;

                if ($isServicerOnline) {
                    // 发送离线信息至客服
                    Gateway::sendToUid(
                        'ns_servicer_' . $item['servicer_id'],
                        json_encode(['type' => 'disconnect', 'data' => ['member_id' => $member_id]])
                    );
                }
            }
        }

        // 更新会员为离线状态
        $memberModel->setMemberOnline($member_id, false);

        // 关闭WS客户端（会员）
        if (!empty($client_id)) {
            Gateway::closeClient($client_id[0], json_encode(['type' => 'close', 'data' => ['status' => true]]));
        }
        return $this->response($this->success('', '会话已结束！'));
    }

    /**
     * 在线信息
     * @return false|string
     */
    public function checkOnline()
    {
        $uidList    = Gateway::getAllUidList();
        $clientList = Gateway::getAllClientIdList();
        return json_encode(['code' => 1, 'msg' => 'success', 'data' => compact('uidList', 'clientList')]);
    }

    /**
     * 在线信息
     * @return false|string
     */
    public function checkClient()
    {
        $client_id = $this->params['client_id'] ?? 0;
        $uid       = $this->params['uid'] ?? 0;

        $online = 0;
        if (!empty($client_id)) {
            $session = Gateway::getSession($client_id);
            $uid     = Gateway::getUidByClientId($client_id);
            $online  = @Gateway::isOnline($client_id) ?? 0;
        }
        if (empty($client_id) && !empty($uid)) {
            $uid     = 'ns_servicer_' . $uid;
            $clients = Gateway::getClientIdByUid($uid);
            if (empty($clients)) {
                return json_encode(['code' => 0, 'msg' => '用户不在线']);
            }
            $client_id = $clients[0];
            $session   = Gateway::getSession($client_id);
            $uid       = Gateway::getUidByClientId($client_id);
            $online    = @Gateway::isUidOnline($uid) ?? 0;
        }
        if (!$online) {
            return json_encode(['code' => 0, 'msg' => '客服不在线', 'data' => compact('session', 'uid', 'client_id')]);
        }
        return json_encode(['code' => 1, 'msg' => '客服在线', 'data' => compact('session', 'uid', 'client_id')]);
    }

    /**
     * 在线信息
     * @return false|string
     */
    public function sendMsg()
    {
        $client_id = request()->param('client_id', '');
        $uid       = Gateway::getUidByClientId($client_id);
        Gateway::sendToUid(
            $uid,
            json_encode(['type' => 'connect', 'data' => ['servicer_id' => 111]])
        );
        return json_encode(['code' => 1, 'msg' => '发送成功']);
    }

    /**
     * 客服列表
     * @return false|string
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function servicerList()
    {
        $token = $this->checkToken();
        if ($token['code'] < 0) {
            return $this->response($token);
        }
        $page = $this->params['page'] ?? 1;
        $size = $this->params['size'] ?? PAGE_LIST_ROWS;

        $map   = [['sd.member_id', '=', $this->member_id]];
        $field = 'sd.shop_id,sh.logo,sh.site_name';
        $join  = [
            ['user u', 'u.uid=sd.member_id', 'left'],
            ['shop sh', 'sh.site_id=sd.shop_id', 'left'],
        ];
        $model = new Dialogue();
        $list  = $model->getPageList($map, $field, 'sd.create_time desc', $page, $size, 'sd', $join, 'sd.shop_id');

        $shop = array_column($list['list'], 'shop_id');
        if (in_array(0, $shop)) {
            // 查询店铺信息
            $website_model                   = new WebsiteModel();
            $website_info                    = $website_model->getWebSite([['site_id', '=', 0]], 'logo,title');
            $key                             = array_search(0, $shop);
            $list['list'][$key]['logo']      = $website_info['data']['logo'];
            $list['list'][$key]['site_name'] = '平台客服';
        }
        return $this->response($this->success($list));
    }

    /**
     * 客服聊天列表
     * @return false|string
     */
    public function chatList()
    {
        $token = $this->checkToken();
        if ($token['code'] < 0) return $this->response($token);

        $prefix = config('database.connections.mysql.prefix');
        $member_id = $this->member_id;
        // 获取有过聊天记录的客服列表，获取未读消息数量，头像、昵称、在线状态，获取最后一次聊天内容、类型
        $sql = "SELECT * FROM (SELECT sd.id,sd.shop_id,sd.create_time,sd.servicer_say,sd.consumer_say,sd.type,sd.content,sd.content_type,sd.add_time,"
            . "IFNULL(s.site_name, '') as shop_name,IFNULL(s.logo,'') as logo,IFNULL(s.avatar,'') as avatar,IFNULL(sr.`online`, 0) as online,"
            . "(SELECT count(sd1.member_id) FROM {$prefix}servicer_dialogue sd1 where sd1.type=1 AND sd1.`read`=0 AND  sd1.shop_id = sd.shop_id AND sd1.member_id={$member_id}) AS unread "
            . "FROM {$prefix}servicer_dialogue sd "
            . "LEFT JOIN {$prefix}shop s on s.site_id=sd.shop_id "
            . "LEFT JOIN {$prefix}servicer sr on sr.shop_id=sd.shop_id AND sr.online=1 "
            . "WHERE sd.member_id={$member_id} "
            . "ORDER BY `online` desc,sd.id desc) as part "
            . "GROUP BY shop_id ORDER BY `online` desc,id desc";
        $chat_list = Db::query($sql);

        $shop = array_column($chat_list, 'shop_id');
        if (in_array(0, $shop)) {
            // 查询店铺信息
            $website_model                   = new WebsiteModel();
            $website_info                    = $website_model->getWebSite([['site_id', '=', 0]], 'logo,title');
            $key                             = array_search(0, $shop);
            $chat_list[$key]['logo']      = $website_info['data']['logo'];
            $chat_list[$key]['shop_name'] = '平台客服';
        }
        return $this->response($this->success($chat_list));
    }
    /**
     * 聊天图片上传
     */
    public function chatimg()
    {
        $upload_model = new Upload();
        $param        = array(
            "thumb_type" => "",
            "name"       => "file"
        );
        $result       = $upload_model->setPath("chat_img/" . date("Ymd") . '/')->image($param);
        return $this->response($result);
    }


    /**
     * @return false|string
     */
    public function checkServicer()
    {
        $servicer_id = $this->params['servicer_id'] ?? 0;

        $servicer = (new Servicer)->getDetail([['user_id', '=', $servicer_id]])['data'];

        $online = 0;
        if (!empty($servicer)) {
            $uid = 'ns_servicer_'.$servicer['user_id'];
            $clients   = Gateway::getClientIdByUid($uid);
            if (empty($clients)) return $this->response($this->error('', '客服不在线'));
            $client_id = $clients[0];
            $session   = Gateway::getSession($client_id);
            $uid       = Gateway::getUidByClientId($client_id);
            $online    = @Gateway::isUidOnline($uid) ?? 0;
        }
        if (!$online) {
            return $this->response($this->error(compact('servicer', 'session'), '客服不在线'));
        }
        return $this->response($this->success(compact('servicer', 'session'), '客服在线'));
    }

    /**
     * 消息设为已读
     * @return false|string
     */
    public function setRead()
    {
        $token = $this->checkToken();
        if ($token['code'] < 0) return $this->response($token);

        $site_id = $this->params['site_id'] ?? 0;

        if (empty($site_id)) return $this->response($this->error('没有指定店铺'));

        // 查询后设为已读
        $condition = [
            ['shop_id', '=', $site_id],
            ['member_id', '=', $this->member_id],
            ['type', '=', 1],
        ];
        (new Dialogue())->setDialoguesRead($condition, true);
        return $this->response($this->success());
    }
}
