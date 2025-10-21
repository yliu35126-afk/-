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
namespace addon\sitemessage\admin\controller;

use app\admin\controller\BaseAdmin;
use addon\sitemessage\model\Message as MessageModel;
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
        ];
    }



    /**
     * 站内信模板消息状态设置
     */
    public function setMessageStatus()
    {
        $message_model = new MessageModel();
        if (request()->isAjax()) {
            $keywords = input("keywords", "");
            $weapp_is_open = input('sitemessage_is_open', 0);
            $res = $message_model->getWeappTemplateNo([['keywords', '=', $keywords]], $weapp_is_open);
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
        $keywords = input("keywords", "");
        $info_result = $message_model->getMessageInfo([['keywords', '=', $keywords]]);
        $info = $info_result["data"];
        $sitemessage_json_array = $info["sitemessage_json_array"];
        if (request()->isAjax()) {
            if (empty($info))
                return error("", "不存在的模板信息!");

            $sitemessage_is_open = input('sitemessage_is_open', 0);
            $res = $message_model->editMessage(['sitemessage_is_open' => $sitemessage_is_open, 'keywords' => $keywords], [
                ["keywords", "=", $keywords],
            ]);
            return $res;
        } else {
            if (empty($info))
                $this->error("不存在的模板信息!");

            $this->assign("keywords", $keywords);
            $this->assign("info", $sitemessage_json_array);

            $this->assign('sitemessage_is_open', $info['sitemessage_is_open']);
            return $this->fetch('message/edit');
        }
    }
}