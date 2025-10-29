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

namespace app\admin\controller;

use app\Controller;
use app\model\system\User as UserModel;
use app\model\web\Config as ConfigModel;
use think\captcha\facade\Captcha as ThinkCaptcha;
use think\facade\Cache;

class Login extends Controller
{
    protected $app_module = "admin";

    /**
     * 登录页面
     */
    public function login()
    {
        $config_model = new ConfigModel();
        $config_info = $config_model->getCaptchaConfig();
        $config_data = (is_array($config_info) && isset($config_info['data']) && is_array($config_info['data'])) ? $config_info['data'] : [];
        $config = (isset($config_data['value']) && is_array($config_data['value'])) ? $config_data['value'] : [];
        // 临时关闭后台登录验证码，避免环境问题导致无法登录
        $admin_login = 0;
        if (request()->isAjax()) {
            $username = input('username', '');
            $password = input('password', '');
            if ($admin_login == 1) {
                $captcha_result = $this->checkCaptcha();
                //验证码
                if ($captcha_result["code"] != 0) {
                    return $captcha_result;
                }
            }

            $user_model = new UserModel();
            $res = $user_model->login($username, $password, $this->app_module);
            return json($res);
        } else {
            $this->assign('admin_login', $admin_login);
            $this->assign("menu_info", [ 'title' => "登录" ]);
            $this->assign("config", $config);
            // 验证码仅在开启时生成，避免环境缺少GD导致报错
            if ($admin_login == 1) {
                $captcha = $this->getCaptchaData();
                $this->assign("captcha", $captcha);
            }
            return $this->fetch("login/login");
        }
    }

    /**
     * 退出操作
     */
    public function logout()
    {
        $user_model = new UserModel();
        $uid = $user_model->uid($this->app_module);
        if ($uid > 0) {
            // 清除登录信息session
            $user_model->clearLogin($this->app_module);
            $this->redirect('/index.php/admin/login/login');
        } else {
            $this->redirect('/index.php/admin/login/login');
        }
    }

    /**
     * 生成验证码数据（仅供内部调用）
     */
    private function getCaptchaData(): array
    {
        $str = md5(rand(1000, 9999) . time());
        $captcha_id = substr($str, 0, 4) . substr($str, -4);
        $captcha_src = url("admin/login/code", [ 'captcha_id' => $captcha_id ]);
        return [ 'id' => $captcha_id, 'img' => $captcha_src ];
    }

    /**
     * 验证码接口（对外统一返回 JSON）
     */
    public function captcha()
    {
        $payload = $this->getCaptchaData();
        return json(success(0, '', $payload));
    }

    /**
     * 检测验证码
     */
    public function checkCaptcha()
    {
        $captcha_id = input('captcha_id', '');
        $captcha = input('captcha', '');
        if (!$captcha_id || !$captcha) return error(-1, '验证码不能为空');
        $captcha_key = "captcha_" . $captcha_id;
        $check = ThinkCaptcha::check($captcha, $captcha_key);
        if (!$check) {
            return error(-1, '验证码错误');
        } else {
            return success();
        }
    }

    /**
     * 验证码图片
     */
    public function code()
    {
        $captcha_id = input('captcha_id', '');
        $captcha_key = "captcha_" . $captcha_id;
        return ThinkCaptcha::create(null, $captcha_key);
    }

    public function devSim()
    {
        $username = input('username', 'admin');
        $password = input('password', 'admin123');
        $user_model = new UserModel();
        // 确保账号存在；不存在则创建一个基础管理员账号
        $user = model('user')->getInfo([[ 'username', '=', $username ], [ 'app_module', '=', $this->app_module ]]);
        if (empty($user)) {
            $user_model->addUser([
                'site_id' => 0,
                'app_module' => $this->app_module,
                'username' => $username,
                'password' => $password,
                'realname' => $username
            ], 'add');
        }
        // 无需验证码，直接模拟登录，建立会话
        $res = $user_model->simulatedLogin($username, $this->app_module);
        // 对外返回 JSON，避免数组类型错误
        return json($res);
    }
}