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

namespace addon\wechat\api\controller;

use addon\wechat\model\Material as MaterialModel;
use addon\wechat\model\Wechat as WechatModel;
use app\Controller;
use EasyWeChat\Kernel\Exceptions\InvalidConfigException;
use think\facade\Cache;

class Auth extends Controller
{

    public $wechat;
    public $config;

    public function __construct()
    {
        $this->wechat = new WechatModel();
    }

    /**
     * ************************************************************************微信公众号消息相关方法 开始******************************************************
     */

    /**
     * 关联公众号微信unserialize
     */
    public function relateWeixin()
    {

        $this->wechat->relateWeixin();

    }

    /**
     * ************************************************************************微信公众号消息相关方法 结束******************************************************
     */

    /**
     * 关联公众号微信unserialize
     */
    public function wechatArticle()
    {
        $id             = input('id', '');
        $index          = input('i', 0);
        $material_model = new MaterialModel();
        $info           = $material_model->getMaterialInfo(['id' => $id]);
        if (!empty($info['data']['value']) && json_decode($info['data']['value'], true)) {
            $info['data']['value'] = json_decode($info['data']['value'], true);
        }
        $this->assign('info', $info['data']);
        $this->assign('index', $index);
        $replace = [
            'WECHAT_CSS' => __ROOT__ . '/addon/wechat/admin/view/public/css',
            'WECHAT_JS'  => __ROOT__ . '/addon/wechat/admin/view/public/js',
            'WECHAT_IMG' => __ROOT__ . '/addon/wechat/admin/view/public/img',
        ];
        return $this->fetch('wechat/article', [], $replace);
    }

    /**
     * 绑定店铺openid
     * @return false|string
     * @throws InvalidConfigException
     */
    public function shopBindOpenid()
    {
        $key         = input("key");
        $weapp_model = new WechatModel();
        $res         = $weapp_model->authCodeToOpenid(input());
        if ($res["code"] >= 0) {
            Cache::set($key, $res["data"]);
            $this->assign('result', '授权成功！');
            return $this->fetch('auth/result');
        } else {
            $this->assign('result', $res['message']);
            return $this->fetch('auth/result');
        }
    }

    /**
     * 绑定店铺openid
     * @throws InvalidConfigException
     */
    public function supplyBindOpenid()
    {
        $key         = input("key");
        $weapp_model = new WechatModel();
        $res         = $weapp_model->authCodeToOpenid(input());
        if ($res["code"] >= 0) {
            Cache::set($key, $res["data"]);
            $this->assign('result', '恭喜您，授权成功！');
            return $this->fetch('auth/result');
        } else {
            $this->assign('result', $res['message']);
            return $this->fetch('auth/result');
        }
    }
}
