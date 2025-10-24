<?php
namespace addon\wechat\event;

use addon\wechat\model\Wechat;

class WechatMsg
{
    public function handle($param = [])
    {
        $wechat_config = new Wechat();
        /** @var \app\Request $request */
        $request = request();
        $res = $wechat_config->sendTemplateMsg($request->siteid(), $param);
        return $res;
    }
}