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

namespace app\model\member;

use addon\wechat\model\Message as WechatMessage;
use app\model\BaseModel;
use app\model\message\Email;
use app\model\message\Sms;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 登录
 *
 * @author Administrator
 *
 */
class Login extends BaseModel
{

    /**
     * 用户登录
     * @param $data
     * @return array
     * @throws DbException
     */
    public function login($data)
    {
        //必然传输usern
        $info = model("member")->getInfo(
            [
                [ 'username|mobile|email', '=', $data[ 'username' ] ],
                [ 'password', '=', data_md5($data[ 'password' ]) ],
                [ 'is_delete', '=', 0 ]
            ], 'member_id,
            username, nickname, mobile, email, status,last_login_time'
        );

        if (empty($info)) {
            return $this->error('', 'USERNAME_OR_PASSWORD_ERROR');
        } elseif ($info[ 'status' ] == 0) {
            return $this->error('', 'MEMBER_IS_LOCKED');
        } else {
            //更新登录时间
            model("member")->update([
                'login_time' => time(),
                'login_type' => empty($data['app_type'])?'':$data['app_type'],
                'login_type_name' => empty($data['app_type_name'])?'':$data['app_type_name'],
                'last_login_time' => time(),
                'login_ip' => request()->ip(),
            ], [ [ 'member_id', '=', $info[ 'member_id' ] ] ]);
            //执行登录奖励
            event("MemberLogin", [ 'member_id' => $info[ 'member_id' ] ], true);
            //用户第三方信息刷新
            $this->refreshAuth($info[ 'member_id' ], $data);
            return $this->success($info);
        }
    }

    /**
     * 第三方登录
     * @param $data
     * @return array
     * @throws DbException
     */
    public function authLogin($data)
    {
        $info = [];
        $auth_tag = '';
        foreach ($data as $key => $value) {
            if (in_array($key, [ 'wx_unionid', 'wx_openid', 'weapp_openid', 'qq_openid', 'ali_openid', 'baidu_openid', 'toutiao_openid' ])) {
                $auth_tag = $key;
                if (empty($value)) return $this->error('', 'PARAMETER_ERROR');
                $info = model("member")->getInfo(
                    [
                        [ $key, '=', $value ],
                        [ 'is_delete', '=', 0 ]
                    ], 'member_id,username, nickname, mobile, email, status, last_login_time'
                );
                if (!empty($info)) break;
            }
        }
        if (empty($auth_tag)) return $this->error('', 'PARAMETER_ERROR');

        if (empty($info)) {
            // 会员不存在 第三方自动注册开启 未开启绑定手机 则进行自动注册
            $config = new Config();
            $config_info = $config->getRegisterConfig();
            if ($config_info[ 'data' ][ 'value' ][ 'third_party' ] && !$config_info[ 'data' ][ 'value' ][ 'bind_mobile' ]) {
                $register = new Register();
                $register_res = $register->authRegister($data);
                if ($register_res[ 'code' ] == 0) {
                    $info = model("member")->getInfo([ [ 'member_id', '=', $register_res[ 'data' ] ] ], 'member_id,username, nickname, mobile, email, status, last_login_time');
                }
            }
        }

        if (empty($info)) {
            return $this->error('', 'MEMBER_NOT_EXIST');
        } elseif ($info[ 'status' ] == 0) {
            return $this->error('', 'MEMBER_IS_LOCKED');
        } else {
            //更新登录时间
            model("member")->update([
                'login_time' => time(),
                'login_type' => empty($data['app_type'])?'':$data['app_type'],
                'login_type_name' => empty($data['app_type_name'])?'':$data['app_type_name'],
                'last_login_time' => time(),
                'login_ip' => request()->ip(),
            ], [ [ 'member_id', '=', $info[ 'member_id' ] ] ]);

            //执行登录奖励
            event("MemberLogin", [ 'member_id' => $info[ 'member_id' ] ], true);

            //用户第三方信息刷新
            $this->refreshAuth($info[ 'member_id' ], $data);
            return $this->success($info);
        }
    }

    /**
     * 授权登录仅登录
     * @param $data
     * @return array
     * @throws DbException
     */
    public function authOnlyLogin($data){
        $info = [];
        $auth_tag = '';
        foreach ($data as $key => $value) {
            if (in_array($key, [ 'wx_unionid', 'wx_openid', 'weapp_openid', 'qq_openid', 'ali_openid', 'baidu_openid', 'toutiao_openid' ])) {
                $auth_tag = $key;
                if (empty($value)) return $this->error('', 'PARAMETER_ERROR');
                $info = model("member")->getInfo(
                    [
                        [ $key, '=', $value ],
                        [ 'is_delete', '=', 0 ]
                    ], 'member_id,username, nickname, mobile, email, status, last_login_time'
                );
                if (!empty($info)) break;
            }
        }
        if (empty($auth_tag)) return $this->error('', 'PARAMETER_ERROR');

        if (empty($info)) {
            return $this->error('', 'MEMBER_NOT_EXIST');
        } elseif ($info[ 'status' ] == 0) {
            return $this->error('', 'MEMBER_IS_LOCKED');
        } else {
            //更新登录时间
            model("member")->update([
                'login_time' => time(),
                'login_type' => empty($data['app_type'])?'':$data['app_type'],
                'login_type_name' => empty($data['app_type_name'])?'':$data['app_type_name'],
                'last_login_time' => time(),
                'login_ip' => request()->ip(),
            ], [ [ 'member_id', '=', $info[ 'member_id' ] ] ]);

            //执行登录奖励
            event("MemberLogin", [ 'member_id' => $info[ 'member_id' ] ], true);

            //用户第三方信息刷新
            $this->refreshAuth($info[ 'member_id' ], $data);
            return $this->success($info);
        }
    }

    /**
     * 刷新第三方信息
     * @param $member_id
     * @param $data
     * @return array
     * @throws DbException
     */
    private function refreshAuth($member_id, $data)
    {
        $data = [
            'qq_openid' => isset($data[ 'qq_openid' ]) ? $data[ 'qq_openid' ] : '',
            'wx_openid' => isset($data[ 'wx_openid' ]) ? $data[ 'wx_openid' ] : '',
            'weapp_openid' => isset($data[ 'weapp_openid' ]) ? $data[ 'weapp_openid' ] : '',
            'wx_unionid' => isset($data[ 'wx_unionid' ]) ? $data[ 'wx_unionid' ] : '',
            'ali_openid' => isset($data[ 'ali_openid' ]) ? $data[ 'ali_openid' ] : '',
            'baidu_openid' => isset($data[ 'baidu_openid' ]) ? $data[ 'baidu_openid' ] : '',
            'toutiao_openid' => isset($data[ 'toutiao_openid' ]) ? $data[ 'toutiao_openid' ] : '',
        ];
        if (!empty($data[ 'qq_openid' ])) {
            model("member")->update([ 'qq_openid' => '' ], [ [ 'qq_openid', '=', $data[ 'qq_openid' ] ], [ 'is_delete', '=', 0 ] ]);
            model("member")->update([ 'qq_openid' => $data[ 'qq_openid' ] ], [ [ 'member_id', '=', $member_id ], [ 'is_delete', '=', 0 ] ]);
        }
        if (!empty($data[ 'wx_openid' ])) {
            model("member")->update([ 'wx_openid' => '' ], [ [ 'wx_openid', '=', $data[ 'wx_openid' ] ], [ 'is_delete', '=', 0 ] ]);
            model("member")->update([ 'wx_openid' => $data[ 'wx_openid' ] ], [ [ 'member_id', '=', $member_id ], [ 'is_delete', '=', 0 ] ]);
        }
        if (!empty($data[ 'weapp_openid' ])) {
            model("member")->update([ 'weapp_openid' => '' ], [ [ 'weapp_openid', '=', $data[ 'weapp_openid' ] ], [ 'is_delete', '=', 0 ] ]);
            model("member")->update([ 'weapp_openid' => $data[ 'weapp_openid' ] ], [ [ 'member_id', '=', $member_id ], [ 'is_delete', '=', 0 ] ]);
        }
        if (!empty($data[ 'wx_unionid' ])) {
            model("member")->update([ 'wx_unionid' => '' ], [ [ 'wx_unionid', '=', $data[ 'wx_unionid' ] ], [ 'is_delete', '=', 0 ] ]);
            model("member")->update([ 'wx_unionid' => $data[ 'wx_unionid' ] ], [ [ 'member_id', '=', $member_id ], [ 'is_delete', '=', 0 ] ]);
        }
        if (!empty($data[ 'ali_openid' ])) {
            model("member")->update([ 'ali_openid' => '' ], [ [ 'ali_openid', '=', $data[ 'ali_openid' ] ], [ 'is_delete', '=', 0 ] ]);
            model("member")->update([ 'ali_openid' => $data[ 'ali_openid' ] ], [ [ 'member_id', '=', $member_id ], [ 'is_delete', '=', 0 ] ]);
        }
        if (!empty($data[ 'baidu_openid' ])) {
            model("member")->update([ 'baidu_openid' => '' ], [ [ 'baidu_openid', '=', $data[ 'baidu_openid' ] ], [ 'is_delete', '=', 0 ] ]);
            model("member")->update([ 'baidu_openid' => $data[ 'baidu_openid' ] ], [ [ 'member_id', '=', $member_id ], [ 'is_delete', '=', 0 ] ]);
        }
        if (!empty($data[ 'toutiao_openid' ])) {
            model("member")->update([ 'toutiao_openid' => '' ], [ [ 'toutiao_openid', '=', $data[ 'toutiao_openid' ] ], [ 'is_delete', '=', 0 ] ]);
            model("member")->update([ 'toutiao_openid' => $data[ 'toutiao_openid' ] ], [ [ 'member_id', '=', $member_id ], [ 'is_delete', '=', 0 ] ]);
        }
        return $this->success();
    }

    /**
     * 检测openid是否存在
     * @param array $data
     * @return array
     */
    public function openidIsExits(array $data)
    {
        if (isset($data[ 'wx_unionid' ]) && !empty($data[ 'wx_unionid' ])) {
            $count = model("member")->getCount([ [ 'wx_unionid', '=', $data[ 'wx_unionid' ] ], [ 'is_delete', '=', 0 ] ]);
            if ($count) return $this->success($count);
        }
        if (isset($data[ 'wx_openid' ]) && !empty($data[ 'wx_openid' ])) {
            $count = model("member")->getCount([ [ 'wx_openid', '=', $data[ 'wx_openid' ] ], [ 'is_delete', '=', 0 ] ]);
            if ($count) return $this->success($count);
        }
        if (isset($data[ 'weapp_openid' ]) && !empty($data[ 'weapp_openid' ])) {
            $count = model("member")->getCount([ [ 'weapp_openid', '=', $data[ 'weapp_openid' ] ], [ 'is_delete', '=', 0 ] ]);
            if ($count) return $this->success($count);
        }
        if (isset($data[ 'qq_openid' ]) && !empty($data[ 'qq_openid' ])) {
            $count = model("member")->getCount([ [ 'qq_openid', '=', $data[ 'qq_openid' ] ], [ 'is_delete', '=', 0 ] ]);
            if ($count) return $this->success($count);
        }
        if (isset($data[ 'ali_openid' ]) && !empty($data[ 'ali_openid' ])) {
            $count = model("member")->getCount([ [ 'ali_openid', '=', $data[ 'ali_openid' ] ], [ 'is_delete', '=', 0 ] ]);
            if ($count) return $this->success($count);
        }
        if (isset($data[ 'baidu_openid' ]) && !empty($data[ 'baidu_openid' ])) {
            $count = model("member")->getCount([ [ 'baidu_openid', '=', $data[ 'baidu_openid' ] ], [ 'is_delete', '=', 0 ] ]);
            if ($count) return $this->success($count);
        }
        if (isset($data[ 'toutiao_openid' ]) && !empty($data[ 'toutiao_openid' ])) {
            $count = model("member")->getCount([ [ 'toutiao_openid', '=', $data[ 'toutiao_openid' ] ], [ 'is_delete', '=', 0 ] ]);
            if ($count) return $this->success($count);
        }
        return $this->success(0);
    }

    /**
     * 用户登录
     * @param $data
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function mobileLogin($data)
    {
        //必然传输usern
        $info = model("member")->getInfo(
            [ [ 'mobile', '=', $data[ 'mobile' ] ], [ 'is_delete', '=', 0 ] ], 'member_id,
            username, nickname, mobile, email, status,last_login_time');
        if (empty($info)) {
            return $this->error('', 'MEMBER_NOT_EXIST');
        } elseif ($info[ 'status' ] == 0) {
            return $this->error('', 'MEMBER_IS_LOCKED');
        } else {
            //更新登录时间
            model("member")->update([
                'login_time' => time(),
                'login_type' => empty($data['app_type'])?'':$data['app_type'],
                'login_type_name' => empty($data['app_type_name'])?'':$data['app_type_name'],
                'last_login_time' => time(),
                'login_ip' => request()->ip(),
            ], [ [ 'member_id', '=', $info[ 'member_id' ] ] ]);

            event("MemberLogin", [ 'member_id' => $info[ 'member_id' ] ], true);

            //用户第三方信息刷新
            $this->refreshAuth($info[ 'member_id' ], $data);
            return $this->success($info);
        }
    }

    /**
     * 登录动态码
     * @param $data
     * @return array|mixed|void
     */
    public function loginCode($data)
    {
        //发送短信
        $sms_model = new Sms();
        $var_parse = array (
            "code" => $data[ "code" ],
        );
        $data[ "sms_account" ] = $data[ "mobile" ] ?? '';//手机号
        $data[ "var_parse" ] = $var_parse;
        $sms_result = $sms_model->sendMessage($data);
        if ($sms_result[ "code" ] < 0) {
            return $sms_result;
        }

        //发送邮箱
        $email_model = new Email();
        //有邮箱才发送
        $data[ "email_account" ] = $data[ "email" ] ?? '';//邮箱号
        $email_result = $email_model->sendMessage($data);
        if ($email_result[ "code" ] < 0) {
            return $email_result;
        }

        return $this->success();
    }

    /**
     * 登录通知
     * @param $data
     * @return array|mixed|void
     */
    public function loginSuccess($data)
    {
        $member_model = new Member();
        $member_info_result = $member_model->getMemberInfo(
            [ [ "member_id", "=", $data[ "member_id" ] ] ],
            "username,mobile,email,reg_time,wx_openid,last_login_type,login_time"
        );
        $member_info = $member_info_result[ "data" ];

        //发送短信
        $sms_model = new Sms();
        $var_parse = array (
            "name" => $member_info[ "username" ],//验证码
        );
        $data[ "sms_account" ] = $member_info[ "mobile" ] ?? '';//手机号
        $data[ "var_parse" ] = $var_parse;
        $sms_result = $sms_model->sendMessage($data);
        //        if($sms_result["code"] < 0)
        //            return $sms_result;

        //发送邮箱
        $email_model = new Email();
        //有邮箱才发送
        $data[ "email_account" ] = $member_info[ "email" ] ?? '';//邮箱号
        $email_result = $email_model->sendMessage($data);
        //        if($email_result["code"] < 0)
        //            return $email_result;


        //发送模板消息
        $wechat_model = new WechatMessage();
        $data[ "openid" ] = $member_info[ "wx_openid" ];

//         if(!empty($member_info["username"])){
//            $user_account = $member_info["username"];
//         }else{
//            if(!empty($member_info["mobile"])){
//              $user_account = $member_info["mobile"];
//            }else{
//              $user_account = $member_info["email"];
//            }
//         }
        $username = '';
        if (!empty($member_info[ "username" ])) {
            $username = $member_info[ "username" ];
        }
        if (empty($username) && !empty($member_info[ "mobile" ])) {
            $username = $member_info[ "mobile" ];
        }
        if (empty($username)) {
            $username = '访客';
        }

        $data[ "template_data" ] = [
            'keyword1' => $username,
            'keyword2' => '登录成功',
            'keyword3' => time_to_date($member_info[ "login_time" ]),
        ];

        $data[ "page" ] = '';
        $wechat_model->sendMessage($data);

        return $this->success();
    }
}
