<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */
namespace addon\weapp\admin\controller;

use addon\weapp\model\Config as ConfigModel;
use app\admin\controller\BaseAdmin;
use addon\weapp\model\Service;
use app\model\system\Upgrade;

/**
 * 微信小程序功能设置
 */
class Weapp extends BaseAdmin
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
     * 功能设置
     */
    public function setting()
    {
        $weapp_menu =  event('WeappMenu', []);
        $this->assign('weapp_menu', $weapp_menu);
        return $this->fetch('weapp/setting', [], $this->replace);
    }

    /**
     * 公众号配置
     */
    public function config()
    {
        $weapp_model = new ConfigModel();
        if (request()->isAjax()) {
            $weapp_name = input('weapp_name', '');
            $weapp_original = input('weapp_original', '');
            $appid = input('appid', '');
            $appsecret = input('appsecret', '');
            $token = input('token', 'TOKEN');
            $encodingaeskey = input('encodingaeskey', '');
            $is_use = input('is_use', 0);
            $qrcode = input('qrcode','');
            $data = array(
                "appid" => $appid,
                "appsecret" => $appsecret,
                "token" => $token,
                "weapp_name" => $weapp_name,
                "weapp_original" => $weapp_original,
                "encodingaeskey" => $encodingaeskey,
                'qrcode' => $qrcode
            );
            $res = $weapp_model->setWeAppConfig($data, $is_use);
            return $res;
        } else {
            $weapp_config_result = $weapp_model->getWeAppConfig();
            $config_info = $weapp_config_result['data']["value"];
            $this->assign("config_info", $config_info);
            // 获取当前域名
            $url = __ROOT__;
            // 去除链接的http://头部
            $url_top = str_replace("https://", "", $url);
            $url_top = str_replace("http://", "", $url_top);
            // 去除链接的尾部/?s=
            $url_top = str_replace('/?s=', '', $url_top);
//            $call_back_url = $url . '/wechat/wap/config/relateWeixin';
            $call_back_url = addon_url('weapp://api/auth/relateWeixin');
            $this->assign("url", $url_top);
            $this->assign("call_back_url", $call_back_url);
            return $this->fetch('weapp/config', [], $this->replace);
        }
    }
    
    /**
     * 源码发布
     */
    public function release(){
        $upgrade_model = new Upgrade();
        $auth_info = $upgrade_model->authInfo();
        $this->assign('auth_info', $auth_info);
        return $this->fetch('weapp/release', [], $this->replace);
    }
    
    /**
     * 小程序包管理
     */
    public function package(){
        if (request()->isAjax()) {
            $mark = input('mark', '');
            $service = new Service();
            $list = $service->getAppletVersionList($mark);
            return $list;
        }
        $mark = input('mark', '');
        $this->assign('mark', $mark);
        return $this->fetch('weapp/package', [], $this->replace);
    }
    
    /**
     * 小程序包下载
     */
    public function download(){
        if (strstr(ROOT_URL, 'niuteam.cn') === false) {
            $app_info = config('info');
            $upgrade_model = new Upgrade();
            $res = $upgrade_model->downloadUniapp($app_info['version_no']);
            if ($res['code'] == 0) {
                $filename = "upload/{$app_info['version_no']}_uniapp.zip";
                $res = file_put_contents($filename, base64_decode($res['data']));

                header("Content-Type: application/zip");
                header("Content-Transfer-Encoding: Binary");
                header("Content-Length: " . filesize($filename));
                header("Content-Disposition: attachment; filename=\"" . basename($filename) . "\"");
                readfile($filename);
                @unlink($filename);
            } else {
                return $this->error($res['message']);
            }
        }
    }
    
    /**
     * 下载
     */
    public function toDownload(){
        $token = input('token', '');
        $service = new Service();
        $redirect_url = $service->download($token);
        $this->redirect($redirect_url);
    }
}