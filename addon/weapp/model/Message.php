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

namespace addon\weapp\model;

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
        $list = model('message')->pageList($condition, 'id,keywords,title,message_type,weapp_json,weapp_is_open', $order, $page, $page_size);

        if (!empty($list[ 'list' ])) {
            foreach ($list[ 'list' ] as $k => $v) {
                $list[ 'list' ][ $k ]['message_info'] = json_decode($v['weapp_json'], true);
            }
        }

        return $this->success($list);
    }


    /**
     * 获取微信模板消息id
     * @param string $keywords
     * todo 批量获取模板消息
     */
    public function getWeappTemplateNo(string $keywords, $weapp_is_open = 0)
    {
        $keyword = explode(',', $keywords);
        $weapp_model = new Weapp();
        if ($weapp_is_open == 1) {
            // 启用
            foreach ($keyword as $item) {
                $shop_message = model('message')->getInfo([ [ 'keywords', '=', $item ],[ 'weapp_json', '<>', '' ]], 'weapp_json');
                $data = [
                    'weapp_is_open' => $weapp_is_open,
                    'keywords' => $item,
                ];
                // 开启时没有模板则进行添加
                $template = json_decode($shop_message[ 'weapp_json' ], true);
                $res = $weapp_model->getTemplateId($template);
                if (isset($res[ 'errcode' ]) && $res[ 'errcode' ] == 0) {
                    $template['weapp_template_id'] = $res[ 'priTmplId' ];
                    $data[ 'weapp_json' ] = json_encode($template);
                } else {
                    return $this->error($res, $res[ 'errmsg' ]);
                }

                model('message')->update($data, [ [ 'keywords', '=', $item ] ]);

            }
        } else if ($weapp_is_open == 0) {
            // 关闭
            foreach ($keyword as $item) {
//                $shop_message = model('message')->getInfo([ [ 'keywords', '=', $item ]], 'weapp_template_id');
                model('message')->update([ 'weapp_is_open' => $weapp_is_open ], [ [ 'keywords', '=', $item ]]);
            }
        } else {
            // 获取
            $list = model('message')->getList([ [ 'keywords', 'in', $keyword ], [ 'weapp_json', '<>', '' ] ], 'keywords,weapp_json');
            if (!empty($list)) {
                foreach ($list as $item) {
                    $template = json_decode($item[ 'weapp_json' ], true);
                    $res = $weapp_model->getTemplateId($template);
                    if (isset($res[ 'errcode' ]) && $res[ 'errcode' ] != 0) return $this->error($res, $res[ 'errmsg' ]);
                    $template['weapp_template_id'] = $res[ 'priTmplId' ];
                    $item_data = [ 'weapp_json' => json_encode($template)];
                    model('message')->update($item_data, [ [ 'keywords', '=', $item[ 'keywords' ] ]]);

                }
            }
        }
        return $this->success();
    }

    /**
     * 发送订阅消息
     * @param array $param
     */
    public function sendMessage(array $param)
    {
        try {
            $support_type = $param['message_info']["support_type"] ?? [];
            if (empty($support_type) || strpos($support_type, "weapp") === false) return $this->success();

            if (empty($param['openid'])) return $this->success('缺少必需参数openid');
            $message_info = $param['message_info'];
            if ($message_info['weapp_is_open'] == 0) return $this->error('未启用模板消息');
            if (!isset($message_info['weapp_json_array']['weapp_template_id']) || empty($message_info['weapp_json_array']['weapp_template_id'])) return $this->error('未配置模板消息');
            $data = [
                'openid' => $param['openid'],
                'template_id' => $message_info['weapp_json_array']['weapp_template_id'],
                'data' => $param['template_data'],
                'page' => $param['page'] ?? ''
            ];

            $weapp = new Weapp();
            $res = $weapp->sendTemplateMessage($data);

            return $res;
        } catch (\Exception $e) {
            return $this->error('', "消息发送失败");
        }
    }

    /**
     * 获取订阅消息模板id集合
     * @param $site_id
     * @param $keywords
     */
    public function getMessageTmplIds($keywords){
        $data = model('message')->getColumn([ ['weapp_is_open', '=', 1], ['weapp_json', '<>', ''],['keywords', 'in', explode(',', $keywords) ] ], 'weapp_json');
        $list = [];
        if(!empty($data)){
            foreach($data as $k => $v){
                $item = json_decode($v, true);
                if(!empty($item['weapp_template_id'])){
                    $list[] = $item['weapp_template_id'];
                }
            }
        }
 
        return $this->success($list);
    }
}