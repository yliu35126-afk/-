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

namespace addon\sitemessage\api\controller;

use addon\sitemessage\model\MemberMessage;
use addon\sitemessage\model\Sitemessage as SitemessageModel;
use app\api\controller\BaseApi;
use think\facade\Db;

/**
 * 站内信
 */
class Sitemessage extends BaseApi
{
    /**
     * 个人中心获取我的未读消息数量
     * @return false|string
     */
    public function getPerMessageCount()
    {
        $token = $this->checkToken();
        if ($token['code'] < 0) {
            return $this->response($token);
        }
        $site_message_model = new SitemessageModel();
        $condition = [
            ['member_id', '=', $this->member_id],
            ['is_delete', '=', 0]
        ];
        $count = $site_message_model->getMessageSum($condition, 'remind_num');
        $res = ['count' => $count['data']];
        return $this->response($this->success($res));
    }

    /**
     * 站内信记录
     */
    public function lists()
    {
        $token = $this->checkToken();
        if ($token['code'] < 0) {
            return $this->response($token);
        }
        $page = $this->params['page'] ?? 1;
        $page_size = $this->params['page_size'] ?? PAGE_LIST_ROWS;
        $app_type = $this->params['app_type'] ?? '';
        $site_message_model = new SitemessageModel();
        $condition = [
            ['member_id', '=', $this->member_id],
            ['is_delete', '=', 0],//未删除的
        ];
        $list = $site_message_model->getSiteMemberMessagePageList($condition, $page, $page_size);
        $common_condition = [
            ['member_id', '=', $this->member_id],
            ['sended_status', '=', 1],
            ['is_delete', '=', 0]
        ];
        if (!empty($app_type)) {
            $common_condition[] = ["", 'exp', Db::raw(" scene like '%{$app_type}%' or  scene = '' ")];
        }
        foreach($list['data']['list'] as $k => $v){
            $item_condition = $common_condition;
            $item_condition[] = ['sub_type', '=', $v['sub_type']];
            $first_info = $site_message_model->getSiteMemberSubMessageFirst($item_condition)['data'] ?? [];
            $list['data']['list'][$k]['content'] = $first_info['content'] ?? '';
        }
        return $this->response($list);
    }

    /**
     * 获取客服消息
     * @return false|string
     */
    public function getSiteMemberSubMessageServicerList()
    {
        $token = $this->checkToken();
        if ($token['code'] < 0) {
            return $this->response($token);
        }
        $page = $this->params['page'] ?? 1;
        $page_size = $this->params['page_size'] ?? PAGE_LIST_ROWS;
        $site_id = $this->params['site_id'] ?? 0;
        $status = $this->params['status'] ?? '';
        $app_type = $this->params['app_type'] ?? '';
        $site_message_model = new SitemessageModel();
        $condition = [
            ['member_id', '=', $this->member_id],
            ['sub_type', '=', 'servicer'],
            ['sended_status', '=', 1],
            ['is_delete', '=', 0]
        ];
        if ($site_id > 0) {
            $condition[] = ['site_id', '=', $site_id];
        }
        if (!empty($status) || $status === 0) {
            $condition[] = ['status', '=', $status];
        }
        if (!empty($app_type)) {
            $condition[] = ["", 'exp', Db::raw(" scene like '%{$app_type}%' or  scene = '' ")];
        }
        $list = $site_message_model->getSiteMemberSubMessagePageList($condition, $page, $page_size,'sended_time desc');
        return $this->response($list);
    }

    /**
     * 获取订单消息
     * @return false|string
     */
    public function getSiteMemberSubMessageOrderList()
    {
        $token = $this->checkToken();
        if ($token['code'] < 0) {
            return $this->response($token);
        }
        $page = $this->params['page'] ?? 1;
        $page_size = $this->params['page_size'] ?? PAGE_LIST_ROWS;
        $status = $this->params['status'] ?? '';
        $app_type = $this->params['app_type'] ?? '';
        $site_message_model = new SitemessageModel();
        $condition = [
            ['member_id', '=', $this->member_id],
            ['sub_type', '=', 'order'],
            ['sended_status', '=', 1],
            ['is_delete', '=', 0]
        ];
        if (!empty($status) || $status === 0) {
            $condition[] = ['status', '=', $status];
        }
        if (!empty($app_type)) {
            $condition[] = ["", 'exp', Db::raw(" scene like '%{$app_type}%' or  scene = '' ")];
        }
        $list = $site_message_model->getSiteMemberSubMessagePageList($condition, $page, $page_size,'sended_time desc');
        return $this->response($list);
    }

    /**
     * 物流消息
     * @return false|string
     */
    public function getSiteMemberSubMessageDeliveryList()
    {
        $token = $this->checkToken();
        if ($token['code'] < 0) {
            return $this->response($token);
        }
        $page = $this->params['page'] ?? 1;
        $page_size = $this->params['page_size'] ?? PAGE_LIST_ROWS;
        $status = $this->params['status'] ?? '';
        $app_type = $this->params['app_type'] ?? '';
        $site_message_model = new SitemessageModel();
        $condition = [
            ['member_id', '=', $this->member_id],
            ['sub_type', '=', 'delivery'],
            ['sended_status', '=', 1],
            ['is_delete', '=', 0]
        ];
        if (!empty($status) || $status === 0) {
            $condition[] = ['status', '=', $status];
        }
        if (!empty($app_type)) {
            $condition[] = ["", 'exp', Db::raw(" scene like '%{$app_type}%' or  scene = '' ")];
        }
        $list = $site_message_model->getSiteMemberSubMessagePageList($condition, $page, $page_size,'sended_time desc');
        return $this->response($list);
    }

    /**
     * 活动消息
     * @return false|string
     */
    public function getSiteMemberSubMessagePromotionList()
    {
        $token = $this->checkToken();
        if ($token['code'] < 0) {
            return $this->response($token);
        }
        $page = $this->params['page'] ?? 1;
        $page_size = $this->params['page_size'] ?? PAGE_LIST_ROWS;
        $status = $this->params['status'] ?? '';
        $app_type = $this->params['app_type'] ?? '';
        $site_message_model = new SitemessageModel();
        $condition = [
            ['member_id', '=', $this->member_id],
            ['sub_type', '=', 'promotion'],
            ['sended_status', '=', 1],
            ['is_delete', '=', 0]
        ];

        if (!empty($app_type)) {
            $condition[] = ["", 'exp', Db::raw(" scene like '%{$app_type}%' or  scene = '' ")];
        }
        $list = $site_message_model->getSiteMemberSubMessagePageList($condition, $page, $page_size, 'sended_time desc');
        return $this->response($list);
    }

    /**
     * 账户变动消息
     * @return false|string
     */
    public function getSiteMemberSubMessageAccountList()
    {
        $token = $this->checkToken();
        if ($token['code'] < 0) {
            return $this->response($token);
        }
        $page = $this->params['page'] ?? 1;
        $page_size = $this->params['page_size'] ?? PAGE_LIST_ROWS;
        $site_message_model = new SitemessageModel();
        $condition = [
            ['member_id', '=', $this->member_id],
            ['sub_type', '=', 'account'],
            ['sended_status', '=', 1],
            ['is_delete', '=', 0]
        ];
        $list = $site_message_model->getSiteMemberSubMessagePageList($condition, $page, $page_size, 'sended_time desc');
        return $this->response($list);
    }


    /**
     * 会员群发消息
     * @return false|string
     */
    public function getSiteMemberSubMessageGroupList()
    {
        $token = $this->checkToken();
        if ($token['code'] < 0) {
            return $this->response($token);
        }
        $page = $this->params['page'] ?? 1;
        $page_size = $this->params['page_size'] ?? PAGE_LIST_ROWS;
        $app_type = $this->params['app_type'] ?? '';
        $site_message_model = new SitemessageModel();
        $condition = [
            ['member_id', '=', $this->member_id],
            ['sub_type', '=', 'group'],
            ['sended_status', '=', 1],
            ['is_delete', '=', 0]
        ];
        if (!empty($app_type)) {
            $condition[] = ["", 'exp', Db::raw(" scene like '%{$app_type}%' or  scene = '' ")];
        }
        $list = $site_message_model->getSiteMemberSubMessagePageList($condition, $page, $page_size, 'sended_time desc');
        return $this->response($list);
    }

    /**
     * 消息主表点击已接收（分类）
     * @return false|string
     */
    public function recordsSeed()
    {
        $token = $this->checkToken();
        if ($token['code'] < 0) {
            return $this->response($token);
        }
        if (!isset($this->params['id']) || !isset($this->params['site_id'])) {
            return $this->response($this->error('参数错误'));
        }
        $id = $this->params['id'];
        $site_id = $this->params['site_id'];
        $site_message_model = new SitemessageModel();
        $data = [
            'member_id' => $this->member_id,
            'id' => $id,
            'site_id' => $site_id
        ];
        $res = $site_message_model->recordsSeed($data);
        return $this->response($res);
    }

    /**
     * 消息点击接受（个体）
     * @return false|string
     */
    public function seed()
    {
        $token = $this->checkToken();
        if ($token['code'] < 0) {
            return $this->response($token);
        }
        if (!isset($this->params['id']) || !isset($this->params['site_id'])) {
            return $this->response($this->error('参数错误'));
        }
        $id = $this->params['id'];
        $site_id = $this->params['site_id'];
        $site_message_model = new SitemessageModel();
        $data = [
            'member_id' => $this->member_id,
            'id' => $id,
            'site_id' => $site_id
        ];
        $res = $site_message_model->seed($data);
        return $this->response($res);
    }

    /**
     * 全部已读（全部）
     * @return false|string
     */
    public function editAllSeed()
    {
        $token = $this->checkToken();
        $site_message_model = new SitemessageModel();
        if ($token['code'] < 0) {
            return $this->response($token);
        }
        if (!isset($this->params['sub_type'])) {
            return $this->response($this->error('参数错误'));
        }
        $res = $site_message_model->allSeed($this->member_id, $this->params['sub_type']);
        return $this->response($res);
    }

    /**
     * 查询会员未接受消息数量
     * @return false|string
     */
    public function getSiteMemberSubMessageCount()
    {
        $token = $this->checkToken();
        if ($token['code'] < 0) {
            return $this->response($token);
        }
        $site_message_model = new SitemessageModel();
        $site_id = $this->params['site_id'] ?? 0;
        $is_seed = $this->params['is_seed'] ?? 0;
        $app_type = $this->params['app_type'] ?? '';
        $sub_type = $this->params['sub_type'] ?? '';
        $sended_status = $this->params['sended_status'] ?? '';
        $condition = [
            ['member_id', '=', $this->member_id],
            ['site_id', '=', $site_id]
        ];

        if (!empty($is_seed)) {
            $condition[] = ['is_seed', '=', $is_seed];
        }
        if (!empty($sub_type)) {
            $condition[] = ['sub_type', '=', $sub_type];
        }
        if (!empty($app_type)) {
            $condition[] = ["", 'exp', Db::raw(" scene like '%{$app_type}%' or  scene = '' ")];
        }
        if (!empty($sended_status) || $sended_status === 0) {
            $condition[] = ['sended_status', '=', $sended_status];
        }
        $count = $site_message_model->getSiteMemberSubMessageCount($condition, 'id');
        return $this->response($count);
    }

    /**
     * 静态资源数据
     * @return false|string
     */
    public function staticData()
    {
        $message_model = new MemberMessage();
        $data = array(
            "sub_type" => $message_model->sub_type
        );
        return $this->response($this->success($data));
    }

    /**
     * 删除单条消息
     * @return false|string
     */
    public function deleteMemberSubMessage()
    {
        $token = $this->checkToken();
        if ($token['code'] < 0) {
            return $this->response($token);
        }
        $site_id = $this->params['site_id'] ?? 0;
        $id = $this->params['id'] ?? 0;
        $site_message_model = new SitemessageModel();
        $params = [
            'id'=>$id,
            'member_id'=>$this->member_id
        ];
        if ($site_id > 0) {
            $params['site_id'] = $site_id;
        }

        $res = $site_message_model->deleteMemberSubMessage($params);
        return $this->response($res);
    }

    public function getSiteMessageRecordsInfo(){
        $token = $this->checkToken();
        if ($token['code'] < 0) {
            return $this->response($token);
        }
        $id = $this->params['id'];
        if(empty($id)){
            return $this->response(error('参数错误'));
        }
        $res = model('site_member_sub_message')->getInfo(['id'=>$id]);
        $formatData_01 = htmlspecialchars_decode($res['text']);//把一些预定义的 HTML 实体转换为字符
//        $formatData_02 = strip_tags($formatData_01);//函数剥去字符串中的 HTML、XML 以及 PHP 的标签,获取纯文本内容
        $res['text'] = $formatData_01;
        return $this->response($this->success($res));
    }

    /**
     * 删除会员主消息表
     * @return false|string
     */
    public function deleteMemberMessage()
    {

        $token = $this->checkToken();
        if ($token['code'] < 0) {
            return $this->response($token);
        }
        $site_id = $this->params['site_id'] ?? 0;
        $id = $this->params['id'];
        $site_message_model = new SitemessageModel();
        $params = [
            'id'=>$id,
            'member_id'=>$this->member_id
        ];
        if ($site_id > 0) {
            $params['site_id'] = $site_id;
        }
        $res = $site_message_model->deleteMemberMessage($params);
        return $this->response($res);
    }
}