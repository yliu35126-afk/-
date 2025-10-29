<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\shopapi\controller;

use app\model\system\User as UserModel;
use app\model\web\Config as ConfigModel;

class Login extends BaseApi
{

    /**
     * 登录方法
     */
    public function login()
    {
        if (empty($this->params[ "username" ])) return $this->response($this->error([], "商家账号不能为空!"));
        if (empty($this->params[ "password" ])) return $this->response($this->error([], "密码不可为空!"));

        $config_model = new ConfigModel();
        $config_info = $config_model->getCaptchaConfig();
        $config = $config_info[ 'data' ][ 'value' ];
        $shop_login = $config[ "shop_login" ] ?? 0;
        $weapp_login = $config[ 'weapp_login' ] ?? 0;
        $client = strtolower((string)($this->params['client'] ?? ''));
        $is_miniapp = in_array($client, ['weapp','miniapp','wxapp','mp']);

        if ($shop_login == 1 && !($is_miniapp && intval($weapp_login) === 0)) {
            // 校验验证码
            $captcha = new Captcha();
            $check_res = $captcha->checkCaptcha();
            if ($check_res[ 'code' ] < 0) return $this->response($check_res);
        }

        // 登录
        $login = new UserModel();
        $res = $login->uniAppLogin($this->params[ 'username' ], $this->params[ "password" ], $this->app_module);

        //生成access_token
        if ($res[ 'code' ] >= 0) {
            $token = $this->createToken($res[ 'data' ]);
            return $this->response($this->success([ 'token' => $token, 'site_id' => $res[ 'data' ][ 'site_id' ] ]));
        }
        return $this->response($res);
    }

    /**
     * 修改密码
     * */
    public function modifyPassword()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $user_model = new UserModel();

        $old_pass = isset($this->params[ 'old_pass' ]) ? $this->params[ 'old_pass' ] : '';
        $new_pass = isset($this->params[ 'new_pass' ]) ? $this->params[ 'new_pass' ] : '123456';

        $res = $user_model->modifyAdminUserPassword($this->uid, $old_pass, $new_pass);

        return $this->response($res);
    }
}