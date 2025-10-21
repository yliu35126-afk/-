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

namespace addon\servicer\servicer\controller;

use addon\servicer\model\Servicer;
use app\model\system\User as UserModel;
use app\Controller;
use app\model\web\WebSite as WebsiteModel;
use app\model\web\Config as ConfigModel;
use Exception;
use GatewayClient\Gateway;
use think\captcha\facade\Captcha as ThinkCaptcha;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Cache;
use think\facade\Config as FacadeConfig;
use think\facade\Session;

/**
 * 登录
 * Class Login
 * @package addon\servicer\servicer\controller
 */
class Login extends Base
{
    public function __construct()
    {
        parent::__construct();

        // 加载配置文件
        FacadeConfig::load(__DIR__ . '/../../config/gateway_client.php');

        // 注册GateWayClient 到 GatewayWorker
        Gateway::$registerAddress = @config()['register_address'];
    }

    /**
     * 登录
     */
    public function login()
    {
        $config_model = new ConfigModel();
        $config       = $config_model->getCaptchaConfig()['data']['value'];

        if (request()->isAjax()) {
            $username = input('username', '');
            $password = input('password', '');

            // 检测验证码
            if ($config['shop_login'] == 1) {
                $res = $this->checkCaptcha();
                if ($res['code'] < 0) {
                    return $res;
                }
            }

            // 验证登录
            $user_model = new UserModel();
            $res        = $user_model->login($username, $password, $this->app_module);
            if ($res['code'] >= 0) {
                $userInfo = Session::get($this->app_module . '.user_info');
            } else {
                if ($res['error_code'] === 'USER_IS_LOCKED') {
                    return $user_model->error('', '该客服账号未启用，请联系管理员');
                }
                return $res;
            }

            try {
                // 强制下线异地登陆，检测是否在线，在线则发送下线通知
                if (@Gateway::isUidOnline('ns_servicer_' . $userInfo['uid'])) {
                    Gateway::sendToUid(
                        'ns_servicer_' . $userInfo['uid'],
                        json_encode(['type' => 'force_offline', 'data' => ['servicer_id' => $userInfo['uid']]])
                    );
                }
            } catch (Exception $e) {
                $user_model->clearLogin($this->app_module);
                return $user_model->error('', '客服未启用，请联系管理员');
            }

            return $res;
        } else {
            $this->assign('shop_login', $config['shop_login']);
            $this->assign("menu_info", ['title' => "登录"]);
            $this->assign("shop_info", ['site_name' => "客服端"]);
            //平台配置信息
            $website_model = new WebsiteModel();
            $website_info  = $website_model->getWebSite(
                [['site_id', '=', 0]],
                'web_phone,web_email,web_qrcode,web_qq,web_weixin,logo'
            );
            $this->assign('website_info', $website_info['data']);
            $this->assign('domain', $_SERVER['SERVER_NAME']);
            $captcha = $this->captcha();
            $captcha = $captcha['data'];
            $this->assign("captcha", $captcha);
            //加载版权信息
            $copyright = $config_model->getCopyright();
            $this->assign('copyright', $copyright['data']['value']);

            return $this->fetch('login/login');
        }
    }

    /**
     * 退出操作
     */
    public function logout()
    {
        $user_model = new UserModel();
        $uid        = $user_model->uid($this->app_module);
        if ($uid > 0) {
            //清除登录信息session
            $user_model->clearLogin($this->app_module);
            $this->redirect(addon_url("servicer://servicer/login/login"));
        } else {
            $this->redirect(addon_url("servicer://servicer/login/login"));
        }
    }

    /**
     * 验证码
     */
    public function captcha()
    {
        $captcha_data = ThinkCaptcha::create(null, true);
        $captcha_id   = md5(uniqid(null, true));
        // 验证码10分钟有效
        Cache::set($captcha_id, $captcha_data['code'], 600);
        return success(0, '', ['id' => $captcha_id, 'img' => $captcha_data['img']]);
    }

    /**
     * 验证码验证
     */
    public function checkCaptcha()
    {
        $captcha = input('captcha', '');

        if (empty($captcha)) {
            return error(-1, '请输入验证码');
        }
        if (!captcha_check($captcha)) {
            return error(-1, '验证码错误');
        }

        return success();
    }

    /**
     * 修改密码
     * @return array|void
     */
    public function modifyPassword()
    {
        if (request()->isAjax()) {
            $user_model = new UserModel();
            $uid        = $user_model->uid($this->app_module);

            $old_pass = input('old_pass', '');
            $new_pass = input('new_pass', '123456');

            $res = $user_model->modifyAdminUserPassword($uid, $old_pass, $new_pass);

            return $res;
        }
    }
}
