<?php
// +---------------------------------------------------------------------+
// | NiuCloud | [ WE CAN DO IT JUST NiuCloud ]                |
// +---------------------------------------------------------------------+
// | Copy right 2019-2029 www.niucloud.com                          |
// +---------------------------------------------------------------------+
// | Author | NiuCloud <niucloud@outlook.com>                       |
// +---------------------------------------------------------------------+
// | Repository | https://github.com/niucloud/framework.git          |
// +---------------------------------------------------------------------+

namespace addon\sitemessage\model;

use app\model\BaseModel;
use think\facade\Cache;

/**
 * 微信小程序订阅消息
 */
class Message extends BaseModel
{
    /**
     * 消息分页列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return \multitype
     */
    public function getMessagePageList($condition = [], $site_id = 0, $page = 1, $page_size = PAGE_LIST_ROWS, $order = '')
    {
        $list = model('message')->pageList($condition, 'id,keywords,title,message_type,sitemessage_is_open,sitemessage_json', $order, $page, $page_size);
        if (!empty($list[ 'list' ])) {
            foreach ($list[ 'list' ] as $k => $v) {
                $list[ 'list' ][ $k ]['message_info'] = json_decode($v['sitemessage_json'], true);
                $list['list'][$k]['sitemessage_is_open'] = $v['sitemessage_is_open'] == null ? 0 : $v['sitemessage_is_open'];
                $list[ 'list' ][ $k ][ 'weapp_template_id' ] = $list[ 'list' ][ $k ]['message_info'] == null ? 0 : $list[ 'list' ][ $k ]['message_info'][ 'template_id' ];
            }
        }
        return $this->success($list);
    }

    /**
     * 发送站内信
     * @param array $param
     */
    public function sendMessage(array $data)
    {
        $support_type = $data[ "support_type" ] ?? [];
        //验证是否支持站内信发送
        if (!empty($support_type) && !in_array("sitemessage", $support_type)){
            return $this->success();
        }
        $message_info = $data[ "message_info" ];
        //站内信是否开启
        if (!isset($message_info[ "sitemessage_is_open" ]) || $message_info[ "sitemessage_is_open" ] == 0){
            return $this->error();
        }
        $title = $message_info[ "title" ];  //站内信标题
        $content = $message_info[ "sitemessage_json_array" ]['content'];//站内信发送内容
        $action_type = $data['action_type'];
        $var_parse = $data[ "var_parse" ];
        if(isset($var_parse['username']) && !empty($var_parse['username'])){
            $message_info[ "message_json_array" ]['username'] = '会员名称';
        }
        //循环替换变量解析
        foreach ($message_info[ "message_json_array" ] as $k => $v) {
            if (!empty($var_parse[ $k ])) {
                $content = str_replace("{" . $k . "}", $var_parse[ $k ], $content);
                $title = str_replace("{" . $k . "}", $var_parse[ $k ], $title);
            }
        }
        try {
            $member_message_model = new MemberMessage();
            switch ($data['sub_type']){
                case 'order':
                    $member_message_model->orderMessageCreate($data['order_id'], $title, $content, $action_type);
                    break;
                case 'delivery':

                    break;
                case 'group':

                    break;
                case 'promotion':
                    die('直接调用MemberMessage发送');
                    break;
                case 'account':
                    break;
                case 'servicer':

                    break;
            }


        } catch (\Exception $e) {
            return $this->error('', "消息发送失败");
        }
    }


}