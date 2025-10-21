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
namespace addon\weapp\admin\controller;

use addon\weapp\model\Config as ConfigModel;
use app\admin\controller\BaseAdmin;
use addon\weapp\model\Message as MessageModel;
use app\model\message\Message as MessageSyetem;

/**
 * 微信小程序订阅消息
 */
class Message extends BaseAdmin
{
    protected $replace = [];    //视图输出字符串内容替换    相当于配置文件中的'view_replace_str'
    public function __construct()
    {
        parent::__construct();
        $this->replace = [
            'WEAPP_CSS' => __ROOT__ . '/addon/weapp/admin/view/public/css',
            'WEAPP_JS' => __ROOT__ . '/addon/weapp/admin/view/public/js',
            'WEAPP_IMG' => __ROOT__ . '/addon/weapp/admin/view/public/img',
            'WEAPP_SVG' => __ROOT__ . '/addon/weapp/admin/view/public/svg',
        ];
    }
    /**
     * 模板消息设置
     * @return array|mixed|\multitype
     */
    public function config()
    {
        $message_model = new MessageModel();
        if (request()->isAjax()) {
            $page      = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $condition = array(
                ["support_type", "like", '%weapp%'],
            );
            $list      = $message_model->getMessagePageList($condition, $this->site_id, $page, $page_size);
            return $list;
        } else {
            return $this->fetch('message/config', [], $this->replace);
        }
    }

    /**
     * 微信模板消息状态设置
     */
    public function setWeappStatus()
    {
        $message_model = new MessageModel();
        if (request()->isAjax()) {
            $keywords       = input("keywords", "");
            $weapp_is_open = input('weapp_is_open', 0);
            $res = $message_model->getWeappTemplateNo($keywords,$weapp_is_open);
            return $res;
        }
    }

    /**
     * 获取模板编号
     */
    public function getWeappTemplateNo()
    {
        if (request()->isAjax()) {
            $keywords      = input("keywords", "");
            $message_model = new MessageModel();
            $res           = $message_model->getWeappTemplateNo($keywords,  -1);
            return $res;
        }
    }

    /**
     * 编辑模板消息
     * @return array|mixed|string
     */
    public function edit()
    {
        $message_model = new MessageSyetem();
        $keywords      = input("keywords", "");
        $info_result   = $message_model->getMessageInfo([['keywords', '=', $keywords]]);
        $info          = $info_result["data"];
        $weapp_json_array = $info["weapp_json_array"];
        if (request()->isAjax()) {
            if (empty($info))
                return error("", "不存在的模板信息!");
            $weapp_is_open = input('weapp_is_open', 0);

            $res = $message_model->editMessage(['weapp_is_open' => $weapp_is_open,  'keywords' => $keywords], [
                ["keywords", "=", $keywords],
            ]);
            return $res;
        } else {
            if (empty($info))
                $this->error("不存在的模板信息!");

            $this->assign("keywords", $keywords);
            $this->assign("info", $weapp_json_array);
            $this->assign('weapp_is_open', $info['weapp_is_open']);
            return $this->fetch('message/edit',[],$this->replace);
        }
    }
}