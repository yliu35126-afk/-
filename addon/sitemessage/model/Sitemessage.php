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
use app\model\member\MemberSearch;
use app\model\system\Cron;
use think\facade\Db;


/**
 * 站内信
 */
class Sitemessage extends BaseModel
{
    public $status = [
        '0' => '创建中',
        '1' => '待发送',
        '2' => '已发送',
        '-1' => '已拒绝',
    ];

    /**
     * 添加站内信
     * @param $data
     * @return array
     */
    public function addSiteMessageRecords($data)
    {
        $result = model('site_message_records')->add($data);

        return $this->success($result);
    }

    /**
     * 修改站内信
     * @param $data
     * @return array
     */
    public function editSiteMessageRecords($data, $condition)
    {
        $result = model('site_message_records')->update($data, $condition);
        return $this->success($result);
    }

    /**
     * 删除专题活动
     * @param unknown $topic_id
     */
    public function deleteSiteMessageRecords($condition)
    {
        $res = model('site_message_records')->delete($condition);
        return $this->success($res);
    }

    /**
     * 获取站内信信息
     * @param array $condition
     * @param string $field
     */
    public function getSiteMessageRecordsInfo($condition, $field = '*')
    {
        $res = model('site_message_records')->getInfo($condition, $field);
        return $this->success($res);
    }

    /**
     * 获取站内信列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param string $limit
     */
    public function getSiteMessageRecordsList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'create_time desc', $field = '*')
    {
        $list = model('site_message_records')->pageList($condition, $field, $order, $page, $page_size);
        return $this->success($list);
    }

    /**
     * 获取站内信列表
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getSiteMessageRecordsPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'create_time desc', $field = '*')
    {
        $list = model('site_message_records')->pageList($condition, $field, $order, $page, $page_size);
        return $this->success($list);
    }
    /***************************************************************** 站内信记录 end ***********************************************************/


    /***************************************************************** 站内信会员记录 start ***********************************************************/

    /**
     * 获取站内信列表
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getSiteMemberMessagePageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'create_time desc', $field = '*')
    {
        $list = model('site_member_message')->pageList($condition, $field, $order, $page, $page_size);
        return $this->success($list);
    }

    /**
     * 站内信主表统计
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getSiteMemberMessageCount($condition = [], $field = '*')
    {
        $count = model('site_member_message')->getCount($condition, $field);
        return $this->success($count);
    }
    /***************************************************************** 站内信会员 end ***********************************************************/
    /***************************************************************** 站内信会员站内信副表记录 start ***********************************************************/
    /**
     * 获取站内信列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param string $limit
     */
    public function getSiteMessageSubMessageList($condition = [], $field = '*', $order = '', $limit = null)
    {
        $list = model('site_member_sub_message')->getList($condition, $field, $order, '', '', '', $limit);

        return $this->success($list);
    }

    /**
     * 获取站内信列表
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getSiteMemberSubMessagePageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'create_time desc', $field = '*', $alias = '', $join = '')
    {
        $list = model('site_member_sub_message')->pageList($condition, $field, $order, $page, $page_size, $alias, $join);
        return $this->success($list);
    }

    /**
     * 查询第一个
     * @param array $condition
     * @param string $field
     * @param string $order
     * @return array
     */
    public function getSiteMemberSubMessageFirst($condition = [], $field ='', $order = 'sended_time desc')
    {
        $info = model('site_member_sub_message')->getFirstData($condition, $field, $order);
        return $this->success($info);
    }

    /**
     * 站内信附表统计
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getSiteMemberSubMessageCount($condition = [], $field = '*')
    {
        $count = model('site_member_sub_message')->getCount($condition, $field);
        return $this->success($count);
    }
    /***************************************************************** 站内信会员站内信副表记录 end ***********************************************************/

    /**
     * 创建发送的任务
     * @param $params
     */
    public function create($params)
    {
        $site_id = $params['site_id'] ?? 0;
        $type = $params['type'] ?? '';
        $title = $params['title'] ?? '';
        $is_timing = $params['is_timing'] ?? 0;//是否定时
        $timing = $params['timing'] ?? 0;//定时时间
        $content = $params['content'] ?? '';//简略的内容介绍
        $scene = $params['scene'] ?? '';//场景
        $message_json = !empty($params['message_json']) ? $params['message_json'] : [];//['image'=> ['pic_path' => ''], 'text' => ['content' => ''] , 'link' => ['url' => '']]
        $json = $params['json'] ?? [];//扩展信息
        $addon = $params['addon'] ?? '';//插件
        $event_type = $params['event_type'] ?? '';//点击事件
        $link = $params['link'] ?? '';//链接
        $text = $params['text'] ?? '';//独立消息页内容
        $is_search = $params['is_search'] ?? 0;
        $sub_type = $params['sub_type'] ?? '';
        $image = $params['image'] ?? '';//图片
        $data = array(
            'site_id' => $site_id,
            'type' => $type,
            'is_timing' => $is_timing,
            'timing' => $timing,
            'content' => $content,
            'title' => $title,
            'addon' => $addon,
            'scene' => $scene,
            'create_time' => time(),
            'message_json' => json_encode($message_json),
            'json' => json_encode($json),
            'status' => 0,
            'event_type' => $event_type,
            'link' => $link,
            'text' => $text,
            'is_search' => $is_search,
            'image' => $image,
            'sub_type' => $sub_type
        );
        $result = model('site_message_records')->add($data);
        $records_id = $result;
        $params['records_id'] = $records_id;
        //todo  这个应该放到消息队列,
        //消息创建后事件
        $result = $this->createAfter($params);
        $condition = array(
            ['site_id', '=', $site_id],
            ['id', '=', $records_id],
        );
        $info = $this->getSiteMessageRecordsInfo($condition)['data'] ?? [];
        $status = $info['status'] ?? '';//群发记录状态  已拒绝的就不要再发送了
        if ($status == 1) {
            if ($is_timing == 0) {
                //立即发送就是当前的时间
                $data['send_time'] = time();
                $group_send_params = [
                    'records_id' => $records_id,
                    'site_id' => $site_id,
                ];
                $this->groupSend($group_send_params);
            } else {
                //创建自动任务
                $cron_model = new Cron();
                $cron_model->addCron(1, 0, "会员定时群发", "CronGroupSend", $timing, $records_id);
            }
        }
        return $this->success($records_id);
    }

    /**
     * 群发后事件
     * @param $params
     * @return array
     */
    public function createAfter($params)
    {
        $member_ids = $params['member_ids'];
        $site_id = $params['site_id'];
        $records_id = $params['records_id'];
        if (empty($member_ids) || empty($records_id)) {
            return $this->error('参数错误！');
        }
        $records_condition = array(
            ['site_id', '=', $site_id],
            ['id', '=', $records_id]
        );

        $records_info = $this->getSiteMessageRecordsInfo($records_condition)['data'] ?? [];
        if (empty($records_info)) {
            return $this->success();
        }
        try {
            //没有会员的话  就拒绝掉
            if (empty($member_ids)) {
                $update_data = array(
                    'status' => -1,
                    'error_msg' => '没有可发送的目标会员!'
                );
                model('site_message_records')->update($update_data, $records_condition);
                return $this->success();
            }
            $update_data = array(
                'status' => 1,
                'total_num' => count($member_ids),//发送总消息数量
            );
            model('site_message_records')->update($update_data, $records_condition);
            $member_common_data = array(
                'site_id' => $site_id,
                'type' => $records_info['type'],
                'scene' => $records_info['scene'],
                'create_time' => time(),
                'message_json' => $records_info['message_json'],
                'json' => $records_info['json'],
                'records_id' => $records_id,
                'title' => $records_info['title'],
                'content' => $records_info['content'],
                'event_type' => $records_info['event_type'],
                'link' => $records_info['link'],
                'text' => $records_info['text'],
                'image' => $records_info['image'],
                'sub_type' => $records_info['sub_type']
            );
            //会员群发
            $data = [];
            foreach ($member_ids as $k => $v) {
                $item_member_data = $member_common_data;
                $item_member_data['member_id'] = $v;
                $data[] = $item_member_data;
            }
            model('site_member_sub_message')->addList($data);
        }catch (\Exception $e){
            return $this->error($e->getMessage());
        }
        return $this->success();
    }

    /**
     * 会员群发
     * @param $params
     */
    public function memberGroupCreate($params)
    {
//        $params = array(
//            'site_id' => 0,
//            'json' => '{"sex":1,"member_level":"","member_label":"","reg_time_start":0,"reg_time_end":0,"remove_member_label":""}',
//            'is_timing' => '',//是否定时
//            'title' => '',//标题
//            'content' => '',//简略的内容介绍
//            'scene' => '',//场景  h5  pc  wechat等 用,隔开
//            'message_json' => '',//消息json 暂时不需要传
//            'event_type' => '',//点击事件
//            'link' => '',//链接
//            'text' => '',//独立消息页内容  富文本
//        );
        $is_search = $params['is_search'] ?? 0;//是否参与筛选
        $params['type'] = 'group';
        //筛选会员得到会员ids
        $json = [];
        if ($is_search) {
            $json = !empty($params['json']) ? json_decode($params['json'], true) : [];
        }
        $site_id = $params['site_id'] ?? 0;
        $json['site_id'] = $site_id;
        $member_search_model = new MemberSearch();
        $member_ids = $member_search_model->search($json)['data'] ?? [];
        $params['json'] = $json;
        $params['member_ids'] = $member_ids;
        $params['sub_type'] = 'group';
        $create_result = $this->create($params);
//        if($create_result['code'] < 0){
//            return $create_result;
//        }
        return $create_result;
        //重新编辑群发记录,设置将要发送的会员ids

    }

//    public function memberGroupSend($params){
//        $site_id = $params['site_id'];
//        $member_ids = $params['member_ids'] ?? [];
//        $id = $params['id'];
//        $records_condition = array(
//            ['site_id', '=', $site_id],
//            ['id', '=', $id]
//        );
//        $records_info = $this->getSiteMessageRecordsInfo($records_condition)['data'] ?? [];
//        if(empty($records_info)){
//            return $this->success();
//        }
//    }
    /**
     * 群发
     * @param $params
     */
    public function groupSend($params)
    {
        $site_id = isset($params['site_id'])?$params['site_id']:0;
        $records_id = $params['records_id'] ?? 0;
        $records_condition = array(
            ['id', '=', $records_id]
        );
        if ($site_id > 0) {
            $records_condition[] = ['site_id', '=', $site_id];
        }
        $records_info = $this->getSiteMessageRecordsInfo($records_condition)['data'] ?? [];
        $status = $records_info['status'];
        $site_id = $records_info['site_id'];
        if ($status == 1) {//处于待发送状态才可以发送
            $condition = array(
                ['site_id', '=', $site_id],
                ['records_id', '=', $records_id],
            );
            $list = $this->getSiteMessageSubMessageList($condition)['data'] ?? [];
            foreach ($list as $k => $v) {
                $item_params = [
                    'site_id' => $site_id,
                    'records_id' => $records_id,
                    'member_id' => $v['member_id'],
                ];
                $this->send($item_params);
                if($k==(count($list)-1)){
                    model('site_message_records')->update(['status'=>2],$records_condition);
                }
            }
            model('site_message_records')->update(['send_num'=>count($list),'send_time'=>time()],['id'=>$records_id]);
        }
        return $this->success();
    }

    /**
     * 给会员发送站内信
     * @param $params
     */
    public function send($params)
    {
        $member_id = $params['member_id'];
        $site_id = $params['site_id'];//
        $records_id = $params['records_id'] ?? 0;//站内信记录id
        $time = time();//送达时间
        $records_condition = array(
            ['site_id', '=', $site_id],
            ['id', '=', $records_id]
        );
        $records_info = $this->getSiteMessageRecordsInfo($records_condition)['data'] ?? [];
        if (empty($records_info)) {
            return $this->error();
        }
        $data = array(
            'sended_time' => $time,
            'sended_status' => 1,
            'status' => 2
        );
        $condition = array(
            ['site_id', '=', $site_id],
            ['member_id', '=', $member_id],
            ['records_id', '=', $records_id],
        );
        $info = model('site_member_sub_message')->getInfo($condition, '*');
        if (empty($info))
            return $this->error();
        model('site_member_sub_message')->update($data, $condition);
        //将消息主信息展示最新的一套业务消息
        $reset_param = array(
            'site_id' => $site_id,
            'id' => $info['id']
        );
        $this->resetMemberMessage($reset_param);
        return $this->success();
    }

    /**
     * 获取消息接收统计
     * @param $param
     * @return array
     */
    public function getSiteMessageCount($param)
    {
        $data = [
            'zcount' => model('site_member_sub_message')->getCount([['records_id', '=', $param['id']]]),
            'ycount' => model('site_member_sub_message')->getCount([['records_id', '=', $param['id']], ['sended_status', '=', '1']]),
            'rcount' => model('site_member_sub_message')->getCount([['records_id', '=', $param['id']], ['is_seed', '=', '1']])
        ];
        return $data;
    }

    /**
     * 消息主表点击已接收（分类接收）
     * @param $params
     */
    public function recordsSeed($params)
    {
        $member_id = $params['member_id'];
        $id = $params['id'];
        $site_id = $params['site_id'];
        $condition = array(
            ['site_id', '=', $site_id],
            ['id', '=', $id],
            ['member_id', '=', $member_id],
        );
        $data = array(
            'is_seed' => 1,//已接收
            'remind_num' => 0,//未读消息设置为0
            'seed_time' => time()
        );
        $info = model('site_member_message')->getInfo($condition);
        model('site_member_message')->update($data, $condition);
        $condition[] = ['sub_type', '=', $info['sub_type']];
        $member_sub_list = model('site_member_sub_message')->getList($condition);
        foreach ($member_sub_list as $v) {
            $res = $this->seed(['id' => $v['id'], 'site_id' => $v['site_id'], 'member_id' => $v['member_id']]);
        }
        return $this->success();
    }

    /**
     * 消息点击接受（单个接收）
     * @param $params
     */
    public function seed($params)
    {
        $member_id = $params['member_id'];
        $id = $params['id'];
        $site_id = $params['site_id'];
        $sub_type = $params['sub_type'] ?? '';
        $condition = array(
            ['site_id', '=', $site_id],
            ['id', '=', $id],
            ['member_id', '=', $member_id],
        );
        if (!empty($sub_type)) {
            $condition[] = ['sub_type', '=', $sub_type];
        }
        $info = model('site_member_sub_message')->getInfo($condition);
        if (empty($info)) {
            return $this->error();
        }
        if($info['is_seed']){
            return $this->success();
        }
        try {
            //更新附表信息
            $data = array(
                'is_seed' => 1,//已接收
                'seed_time' => time()
            );
            model('site_member_sub_message')->update($data, $condition);
            //更新主表信息
            $site_message_sub_message_condition = [
                ['site_id','=',$info['site_id']],
                ['member_id','=',$info['member_id']],
                ['type','=',$info['type']],
                ['sub_type','=',$info['sub_type']]
            ];
            model('site_member_message')->setInc($site_message_sub_message_condition,'seed_num' );
            //更新站内信
            $site_message_records_condition = [
                ['id', '=', $info['records_id']]
            ];
            model('site_message_records')->setInc($site_message_records_condition,'seed_num');
        }catch (\Exception $e){
            return $this->error($e->getMessage());
        }
        return $this->success();
    }

    /**
     * 最新的消息重置消息( todo 有两种方案,一种是批量全部修改,一种是单个修改)
     * @param $params
     */
    public function resetMemberMessage($params)
    {
        $site_id = $params['site_id'] ?? 0;
        $id = $params['id'];
        $condition = array(
            ['site_id', '=', $site_id],
            ['id', '=', $id],
        );
        $info = model('site_member_sub_message')->getInfo($condition, '*');
        if (empty($info))
            return $this->error();

        $member_id = $info['member_id'];
        $type = $info['type'] ?? '';
        $sub_type = $info['sub_type'] ?? '';
        $title = $info['title'] ?? '';
        $content = $info['content'] ?? '';
        $master_condition = array(
            ['member_id', '=', $member_id],
//            ['type', '=', $type],
            ['sub_type', '=', $sub_type],
        );
        if($sub_type=='servicer'){
            $master_condition[] = ['site_id', '=', $site_id];
        }
        $data = array(
            'title' => $title,
            'content' => $content,
            'type' => $type,
            'member_id' => $member_id,
            'sub_type' => $sub_type,
            //没有的话创建默认数据,存在的话就重置当前的数据
            'seed_time' => 0,
            'is_seed' => 1,
            'site_id' => $site_id,
            'send_time' => time(),
            'is_delete' => 0
        );
        $info = model('site_member_message')->getInfo($master_condition);
        if (!empty($info)) {//编辑
            model('site_member_message')->update($data, $master_condition);
            model('site_member_message')->setInc($master_condition,'remind_num');
        } else {//添加
            $data['create_time'] = time();
            $data['remind_num'] = '1';
            model('site_member_message')->add($data);
        }

        return $this->success();
    }


    /**
     * 删除会员主消息表
     * @param $params
     * @return array
     */
    public function deleteMemberMessage($params)
    {
        $site_id = $params['site_id'] ?? 0;
        $id = $params['id'];
        $member_id = $params['member_id'] ?? 0;
        $condition = array(
            ['site_id', '=', $site_id],
            ['id', '=', $id],
            ['member_id', '=', $member_id]
        );
        $info = model('site_member_message')->getInfo($condition, '*');
        if (empty($info))
            return $this->error();

        //将会员的这条记录设置为已删除
        $data = array(
            'is_delete' => 1,
            'delete_time' => time()
        );
        model('site_member_message')->update($data, $condition);
        $member_sub_condition = [
            ['id', '=', $info['id']],
            ['site_id', '=', $info['site_id']],
            ['member_id', '=',$info['member_id']]
        ];

        $member_sub_list = model('site_member_sub_message')->getList($member_sub_condition);
        foreach ($member_sub_list as $v) {
            $res = $this->deleteMemberSubMessage(['id' => $v['id'], 'site_id' => $v['site_id'], 'member_id' => $v['member_id']]);
        }
        return $this->success();
    }

    /**
     * 删除单条消息
     * @param $params
     * @return array
     */
    public function deleteMemberSubMessage($params)
    {

        $id = $params['id'] ?? 0;
        $site_id = $params['site_id'] ?? 0;
        $member_id = $params['member_id'] ?? 0;
        $condition = array(
            'id' => $id,
            'site_id' => $site_id,
            'member_id' => $member_id
        );
        $info = model('site_member_sub_message')->getInfo($condition, '*');
        if (empty($info)){
            return $this->error();
        }
        //更新附表信息
        $data = array(
            'is_delete' => 1,
            'delete_time' => time()
        );
        model('site_member_sub_message')->update($data, $condition);
        //更新主表信息
        $master_condition = array(
            ['site_id', '=', $site_id],
            ['member_id', '=', $member_id],
            ['type', '=', $info['type']],
            ['sub_type', '=', $info['sub_type']],
        );
        $master_data = array('delete_num'=>'delete_num + 1');
        //递增总消息数量(一般情况下没有意义)
        model('site_member_message')->update($master_data, $master_condition);
        //更新消息表
        $message_records_data = ['delete_num'=>'delete_num + 1'];
        $site_message_records_condition = [['id', '=', $info['records_id']]];
        //更新站内信删除总数
        model('site_message_records')->update($message_records_data, $site_message_records_condition);
        return $this->success();
    }

    /**
     * 将会员消息递增
     * @param $params
     * @return array
     */
    public function incMessageNum($params)
    {
        $site_id = $params['site_id'];
        $member_id = $params['member_id'];
        $type = $params['type'];
        $sub_type = $params['sub_type'];
        $condition = array(
            ['site_id', '=', $site_id],
            ['member_id', '=', $member_id],
            ['type', '=', $type],
            ['sub_type', '=', $sub_type],
        );
        //递增总消息数量(一般情况下没有意义)
        model('site_member_message')->setInc($condition, 'num', 1);
        //递增未接收消息数量,
        model('site_member_message')->setInc($condition, 'num', 1);
        return $this->success();
    }

    public function decMessageNum()
    {

    }

    /**
     * 全部已读（全部）
     * @param $member_id
     * @param $sub_type
     * @return array
     */
    public function allSeed($member_id, $sub_type)
    {
        $sub_type = explode(',',$sub_type);
        $sub_condition = [
            ['member_id', '=', $member_id],
            ['sub_type', 'IN', $sub_type]
        ];
        $sub_data = ['is_seed' => 1, 'seed_time' => time(), 'status' => 2];
        $data = ['is_seed' => 1, 'seed_time' => time(),'remind_num'=>0];
        try {
            model('site_member_sub_message')->update($sub_data, $sub_condition);
            model('site_member_message')->update($data, $sub_condition);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
        return $this->success();
    }

    /**
     * 获取site_member_message 某个字段的总和 SUM
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getMessageSum($condition = [], $field = '*')
    {
        $sum = model('site_member_message')->getSum($condition, $field);
        return $this->success($sum);
    }

}