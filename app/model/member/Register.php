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
use app\model\system\Stat;

/**
 * 注册
 * Class Register
 * @package app\model\member
 */
class Register extends BaseModel
{

    /**
     * 用户名密码注册(必传username， password),之前检测重复性,判断用户名是否为手机，邮箱
     * @param $data
     * @return array|mixed
     */
    public function usernameRegister($data)
    {
        //为空检测
        if(empty($data['username'])){
            return $this->error(null, '用户名不可为空');
        }
        if(empty($data['password'])){
            return $this->error(null, '密码不可为空');
        }

        //长度检测
        $len_limit = 50;
        if(mb_strlen($data['username']) > $len_limit){
            return $this->error(null, '用户名的长度不可大于'.$len_limit);
        }

        //存在检测
        $count = model('member')->getCount([['username', '=', $data['username']]], 'member_id');
        if($count > 0){
            return $this->error(null, '用户名已存在');
        }

        $this->cancelBind($data);
        $member_level = new MemberLevel();
        $member_level_info = $member_level->getMemberLevelInfo([['is_default', '=', 1]], 'level_id,level_name');
        $member_level_info = $member_level_info['data'];
        if (isset($data['suid']) && !empty($data['suid'])) {
            $count = model("member")->getCount([['member_id', '=', $data['suid']], ['is_delete', '=', 0]]);
            if (!$count) $data['suid'] = 0;
        }
        $data_reg = [
            'source_member' => isset($data['suid']) ? $data['suid'] : 0,
            'username' => $data['username'],
            'nickname' => $data['username'], //默认昵称为用户名
            'password' => data_md5($data['password']),
            'qq_openid' => isset($data['qq_openid']) ? $data['qq_openid'] : '',
            'wx_openid' => isset($data['wx_openid']) ? $data['wx_openid'] : '',
            'weapp_openid' => isset($data['weapp_openid']) ? $data['weapp_openid'] : '',
            'wx_unionid' => isset($data['wx_unionid']) ? $data['wx_unionid'] : '',
            'ali_openid' => isset($data['ali_openid']) ? $data['ali_openid'] : '',
            'baidu_openid' => isset($data['baidu_openid']) ? $data['baidu_openid'] : '',
            'toutiao_openid' => isset($data['toutiao_openid']) ? $data['toutiao_openid'] : '',
            'headimg' => isset($data['headimg']) ? $data['headimg'] : '',
            'member_level' => $member_level_info['level_id'],
            'member_level_name' => $member_level_info['level_name'],
            'reg_time' => time(),
            'login_time' => time(),
            'login_type' => empty($data['app_type']) ? '' : $data['app_type'],
            'login_type_name' => empty($data['app_type_name']) ? '' : $data['app_type_name'],
            'last_login_time' => time()
        ];
        $res = model("member")->add($data_reg);
        if ($res) {
            //添加统计
            $stat = new Stat();
            $stat->addShopStat(['member_count' => 1, 'site_id' => 0]);
            //会员注册事件
            event("MemberRegister", ['member_id' => $res]);
            $data['member_id'] = $res;
            $this->pullHeadimg($data);
            return $this->success($res);
        } else {
            return $this->error();
        }
    }

    /**
     * 手机号密码注册(必传mobile， password),之前检测重复性
     * @param $data
     * @return array|mixed
     */
    public function mobileRegister($data)
    {
        $this->cancelBind($data);
        $member_level = new MemberLevel();
        $member_level_info = $member_level->getMemberLevelInfo([['is_default', '=', 1]], 'level_id,level_name');
        $member_level_info = $member_level_info['data'];
        if (isset($data['suid']) && !empty($data['suid'])) {
            $count = model("member")->getCount([['member_id', '=', $data['suid']], ['is_delete', '=', 0]]);
            if (!$count) $data['suid'] = 0;
        }
        $data_reg = [
            'source_member' => isset($data['suid']) ? $data['suid'] : 0,
            'password' => '',
            'qq_openid' => isset($data['qq_openid']) ? $data['qq_openid'] : '',
            'wx_openid' => isset($data['wx_openid']) ? $data['wx_openid'] : '',
            'weapp_openid' => isset($data['weapp_openid']) ? $data['weapp_openid'] : '',
            'wx_unionid' => isset($data['wx_unionid']) ? $data['wx_unionid'] : '',
            'ali_openid' => isset($data['ali_openid']) ? $data['ali_openid'] : '',
            'baidu_openid' => isset($data['baidu_openid']) ? $data['baidu_openid'] : '',
            'toutiao_openid' => isset($data['toutiao_openid']) ? $data['toutiao_openid'] : '',
            'headimg' => isset($data['headimg']) ? $data['headimg'] : '',
            'member_level' => $member_level_info['level_id'],
            'member_level_name' => $member_level_info['level_name'],
            'reg_time' => time(),
            'login_time' => time(),
            'login_type' => empty($data['app_type']) ? '' : $data['app_type'],
            'login_type_name' => empty($data['app_type_name']) ? '' : $data['app_type_name'],
            'last_login_time' => time()
        ];

        if (!empty($data['username'])) $data_reg['username'] = $data['username'];
        !empty($data['username']) && $data_reg['nickname'] = $data['username'];
        if (!empty($data['mobile'])) $data_reg['mobile'] = $data['mobile'];
        !empty($data['mobile']) && $data_reg['nickname'] = $data['mobile'];

        //新增2021.06.30
        if (isset($data['wx_openid']) && !empty($data['nickname'])){
            $data_reg['nickname'] = $data['nickname'];
        }

        $res = model("member")->add($data_reg);
        if ($res) {
            //添加统计
            $stat = new Stat();
            $stat->addShopStat(['member_count' => 1, 'site_id' => 0]);
            //会员注册事件
            event("MemberRegister", ['member_id' => $res]);
            $data['member_id'] = $res;
            $this->pullHeadimg($data);
            return $this->success($res);
        } else {
            return $this->error();
        }
    }

    /**
     * 邮箱密码注册(必传email， password),之前检测重复性
     * @param $data
     * @return array|mixed
     */
    public function emailRegister($data)
    {
        $config = new Config();
        $config_info = $config->getRegisterConfig();
        $type_array = explode(",", $config_info['value']['type']);
        if (in_array('email', $type_array)) {
            $this->cancelBind($data);
            $member_level = new MemberLevel();
            $member_level_info = $member_level->getMemberLevelInfo([['is_default', '=', 1]], 'level_id,level_name');
            $member_level_info = $member_level_info['data'];
            if (isset($data['suid']) && !empty($data['suid'])) {
                $count = model("member")->getCount([['member_id', '=', $data['suid']], ['is_delete', '=', 0]]);
                if (!$count) $data['suid'] = 0;
            }
            $data_reg = [
                'source_member' => isset($data['suid']) ? $data['suid'] : 0,
                'email' => $data['email'],
                'nickname' => $data['email'], //默认昵称为邮箱
                'password' => data_md5($data['password']),
                'qq_openid' => isset($data['qq_openid']) ? $data['qq_openid'] : '',
                'wx_openid' => isset($data['wx_openid']) ? $data['wx_openid'] : '',
                'weapp_openid' => isset($data['weapp_openid']) ? $data['weapp_openid'] : '',
                'wx_unionid' => isset($data['wx_unionid']) ? $data['wx_unionid'] : '',
                'ali_openid' => isset($data['ali_openid']) ? $data['ali_openid'] : '',
                'baidu_openid' => isset($data['baidu_openid']) ? $data['baidu_openid'] : '',
                'toutiao_openid' => isset($data['toutiao_openid']) ? $data['toutiao_openid'] : '',
                'headimg' => isset($data['headimg']) ? $data['headimg'] : '',
                'member_level' => $member_level_info['level_id'],
                'member_level_name' => $member_level_info['level_name'],
                'reg_time' => time(),
                'login_time' => time(),
                'login_type' => empty($data['app_type']) ? '' : $data['app_type'],
                'login_type_name' => empty($data['app_type_name']) ? '' : $data['app_type_name'],
                'last_login_time' => time()
            ];
            $res = model("member")->add($data_reg);
            if ($res) {
                //添加统计
                $stat = new Stat();
                $stat->addShopStat(['member_count' => 1, 'site_id' => 0]);
                //会员注册事件
                event("MemberRegister", ['member_id' => $res]);
                $data['member_id'] = $res;
                $this->pullHeadimg($data);
                return $this->success($res);
            } else {
                return $this->error();
            }
        } else {
            return $this->error('', "REGISTER_REFUND");
        }
    }

    /**
     * 第三方注册
     * @param $data
     */
    public function authRegister($data)
    {
        $this->cancelBind($data);

        $member_level = new MemberLevel();
        $member_level_info = $member_level->getMemberLevelInfo([['is_default', '=', 1]], '*');
        $member_level_info = $member_level_info['data'];

        if (isset($data['suid']) && !empty($data['suid'])) {
            $count = model("member")->getCount([['member_id', '=', $data['suid']], ['is_delete', '=', 0]]);
            if (!$count) $data['suid'] = 0;
        }

        $username = $this->createRandUsername();
        $nickname = $username;
        if (isset($data['nickName']) && !empty($data['nickName'])) {
            $nickname = preg_replace_callback('/./u',
                function (array $match) {
                    return strlen($match[0]) >= 4 ? '' : $match[0];
                },
                $data['nickName']);
        }

        $data_reg = [
            'source_member' => isset($data['suid']) ? $data['suid'] : 0,
            'username' => $username,
            'nickname' => $nickname,
            'password' => '',
            'qq_openid' => isset($data['qq_openid']) ? $data['qq_openid'] : '',
            'wx_openid' => isset($data['wx_openid']) ? $data['wx_openid'] : '',
            'weapp_openid' => isset($data['weapp_openid']) ? $data['weapp_openid'] : '',
            'wx_unionid' => isset($data['wx_unionid']) ? $data['wx_unionid'] : '',
            'ali_openid' => isset($data['ali_openid']) ? $data['ali_openid'] : '',
            'baidu_openid' => isset($data['baidu_openid']) ? $data['baidu_openid'] : '',
            'toutiao_openid' => isset($data['toutiao_openid']) ? $data['toutiao_openid'] : '',
            'headimg' => isset($data['avatarUrl']) ? $data['avatarUrl'] : '',
            'member_level' => $member_level_info['level_id'],
            'member_level_name' => $member_level_info['level_name'],
            'reg_time' => time(),
            'login_time' => time(),
            'login_type' => empty($data['app_type']) ? '' : $data['app_type'],
            'login_type_name' => empty($data['app_type_name']) ? '' : $data['app_type_name'],
            'last_login_time' => time()
        ];
        $res = model("member")->add($data_reg);
        if ($res) {
            //添加统计
            $stat = new Stat();
            $stat->addShopStat(['member_count' => 1, 'site_id' => 0]);
            //会员注册事件
            event("MemberRegister", ['member_id' => $res]);
            $data['member_id'] = $res;
            $this->pullHeadimg($data);
            return $this->success($res);
        } else {
            return $this->error();
        }
    }

    /**
     * 生成随机用户名
     * @param $site_id
     */
    private function createRandUsername()
    {
        $usernamer = 'u_' . random_keys(10);
        $count = model('member')->getCount([['username', '=', $usernamer], ['is_delete', '=', 0]]);
        if ($count) {
            $usernamer = $this->createRandUsername();
            return $usernamer;
        } else {
            return $usernamer;
        }
    }

    /**
     * 清除账号绑定(用户重新进行绑定)
     * @param $data
     * @return array
     */
    public function cancelBind($data)
    {
        $data = [
            'qq_openid' => isset($data['qq_openid']) ? $data['qq_openid'] : '',
            'wx_openid' => isset($data['wx_openid']) ? $data['wx_openid'] : '',
            'weapp_openid' => isset($data['weapp_openid']) ? $data['weapp_openid'] : '',
            'wx_unionid' => isset($data['wx_unionid']) ? $data['wx_unionid'] : '',
            'ali_openid' => isset($data['ali_openid']) ? $data['ali_openid'] : '',
            'baidu_openid' => isset($data['baidu_openid']) ? $data['baidu_openid'] : '',
            'toutiao_openid' => isset($data['toutiao_openid']) ? $data['toutiao_openid'] : '',
        ];
        if (!empty($data['qq_openid'])) {
            model("member")->update(['qq_openid' => ''], [['qq_openid', '=', $data['qq_openid']], ['is_delete', '=', 0]]);
        }
        if (!empty($data['wx_openid'])) {
            model("member")->update(['wx_openid' => ''], [['wx_openid', '=', $data['wx_openid']], ['is_delete', '=', 0]]);
        }
        if (!empty($data['weapp_openid'])) {
            model("member")->update(['weapp_openid' => ''], [['weapp_openid', '=', $data['weapp_openid']], ['is_delete', '=', 0]]);
        }
        if (!empty($data['wx_unionid'])) {
            model("member")->update(['wx_unionid' => ''], [['wx_unionid', '=', $data['wx_unionid']], ['is_delete', '=', 0]]);
        }
        if (!empty($data['ali_openid'])) {
            model("member")->update(['ali_openid' => ''], [['ali_openid', '=', $data['ali_openid']], ['is_delete', '=', 0]]);
        }
        if (!empty($data['baidu_openid'])) {
            model("member")->update(['baidu_openid' => ''], [['baidu_openid', '=', $data['baidu_openid']], ['is_delete', '=', 0]]);
        }
        if (!empty($data['toutiao_openid'])) {
            model("member")->update(['toutiao_openid' => ''], [['toutiao_openid', '=', $data['toutiao_openid']], ['is_delete', '=', 0]]);
        }
        return $this->success();

    }

    /**
     * 检测用户存在性(用户名)
     * @param $username
     * @return int
     */
    public function usernameExist($username)
    {
        $member_info = model("member")->getInfo([['username|mobile', '=', $username], ['is_delete', '=', 0]], 'member_id');
        if (!empty($member_info)) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * 检测邮箱存在性 存在返回1
     * @param $email
     * @return int
     */
    public function emailExist($email)
    {
        $member_info = model("member")->getInfo([['email', '=', $email], ['is_delete', '=', 0]], 'member_id');
        if (!empty($member_info)) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * 检测用户存在性(用户名) 存在返回1
     * @param $mobile
     * @return int
     */
    public function mobileExist($mobile)
    {
        $member_info = model("member")->getInfo([['mobile', '=', $mobile], ['is_delete', '=', 0]], 'member_id');
        if (!empty($member_info)) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * 检测用户存在性(wx_openid) 存在返回1 新增2021.06.30
     * @param $mobile
     * @return int
     */
    public function openidExist($mobile)
    {
        $member_info = model("member")->getInfo(
            [
                [ 'mobile', '=', $mobile ],
                [ 'is_delete', '=', 0]
            ], 'wx_openid'
        );
        if (!empty($member_info['wx_openid'])) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * 获取用户ID 新增2021.06.30
     * @param $mobile
     * @return int
     */
    public function getMemberId($mobile)
    {
        $member_info = model("member")->getInfo(
            [
                [ 'mobile', '=', $mobile ],
                [ 'is_delete', '=', 0]
            ], 'member_id'
        );
        if (!empty($member_info)) {
            return $member_info['member_id'];
        } else {
            return 0;
        }
    }

    /**
     * 重置用户微信openid 新增2021.06.30
     * @param $data
     * @return array
     */
    public function wxopenidBind($data)
    {
        $res = model("member")->update(['wx_openid'=>$data['wx_openid']],[ [ 'member_id', '=', $data[ 'member_id' ] ], ['is_delete','=',0] ]);
        if ($res){
            return $this->success($res);
        } else {
            return $this->error();
        }
    }

    /**
     * 注册发送验证码
     * @param $data
     * @return array|mixed|void
     */
    public function registerCode($data)
    {
        //发送短信
        $sms_model = new Sms();
        $var_parse = array(
            "code" => $data["code"],//验证码
            "site_name" => $data["site_name"],//站点名称
        );
        $data["sms_account"] = $data["mobile"] ?? '';//手机号
        $data["var_parse"] = $var_parse;
        $sms_result = $sms_model->sendMessage($data);
        if ($sms_result["code"] < 0)
            return $sms_result;

        //发送邮箱
        $email_model = new Email();
        //有邮箱才发送
        $data["email_account"] = $data["email"] ?? '';//邮箱号
        $email_result = $email_model->sendMessage($data);
        if ($email_result["code"] < 0)
            return $email_result;

        return $this->success();
    }

    /**
     * 注册发送验证码
     * @param $data
     * @return array|mixed|void
     */
    public function registerSuccess($data)
    {

        $member_model = new Member();
        $member_info_result = $member_model->getMemberInfo([["member_id", "=", $data["member_id"]]], "username,mobile,email,reg_time,wx_openid,last_login_type");
        $member_info = $member_info_result["data"];
        //发送短信
        $var_parse = [
            "shopname" => $data['site_name'],   //商城名称
            "username" => $member_info["username"],    //会员名称
        ];
        $data["sms_account"] = $member_info["mobile"] ?? '';//手机号
        $data["var_parse"] = $var_parse;
        $sms_model = new Sms();
        $sms_result = $sms_model->sendMessage($data);
//        if ($sms_result["code"] < 0) return $sms_result;

        //邮箱发送总
        $email_model = new Email();
        $data["email_account"] = $member_info["email"] ?? '';//邮箱号
        $email_result = $email_model->sendMessage($data);
//        if ($email_result["code"] < 0) return $email_result;
        //发送模板消息
        $wechat_model = new WechatMessage();
        $data["openid"] = $member_info["wx_openid"];

        $data["template_data"] = [
            'keyword1' => $member_info["username"],
            'keyword2' => time_to_date($member_info["reg_time"]),
        ];
        $data["page"] = '';
        $wechat_model->sendMessage($data);

        return $this->success();
    }

    /**
     * 拉取用户头像
     * @param unknown $info
     */
    private function pullHeadimg($data)
    {
        if (!empty($data['headimg']) && is_url($data['headimg'])) {
            $url = __ROOT__ . '/api/member/pullhaedimg?member_id=' . $data['member_id'];
            http($url, 1);
        }
    }
}