<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\weapp\model;

use app\model\BaseModel;
use EasyWeChat\Factory;
use think\facade\Cache;

/**
 * 微信小程序配置
 */
class Weapp extends BaseModel
{
    private $app;//微信模型

    public function __construct()
    {
        //微信支付配置
        $config_model = new Config();
        $config_result = $config_model->getWeappConfig();
        $config = $config_result["data"];
        if (!empty($config)) {
            $config_info = $config["value"];
        }

        $config = [
            'app_id' => $config_info["appid"] ?? '',
            'secret' => $config_info["appsecret"] ?? '',

            // 下面为可选项
            // 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
            'response_type' => 'array',
            'token'   => $plateform_config["token"] ?? '',
            'aes_key' => $plateform_config["encodingaeskey"] ?? '',
            'log' => [
                'level' => 'debug',
                'permission' => 0777,
                'file'       => 'runtime/log/wechat/easywechat.logs',
            ],
            // 修复 Windows/开发环境下 cURL error 60（证书链不可用），仅限开发/联调环境
            // 线上建议在 PHP 配置中正确设置 CA 证书；此为兜底避免阻断功能
            'http' => [
                'verify'  => false,
                'timeout' => 10.0
            ],
        ];
        $this->app = Factory::miniProgram($config);
    }

    /**
     * 根据 jsCode 获取用户 session 信息
     * @param $param
     * @return array
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     */
    public function authCodeToOpenid($param)
    {
        //正常返回的JSON数据包
        //{"openid": "OPENID", "session_key": "SESSIONKEY", "unionid": "UNIONID"}
        //错误时返回JSON数据包(示例为Code无效)
        //{"errcode": 40029, "errmsg": "invalid code"}
        $result = $this->app->auth->session($param['code']);
        if (isset($result['errcode'])) {
            return $this->error('', $result['errmsg']);
        } else {
            Cache::set('weapp_' . $result['openid'], $result);
            return $this->success($result);
        }
    }

    /**
     * 生成二维码
     * @param $param
     * @return multitype|array
     */
    public function createQrcode($param)
    {
        try {
            $checkpath_result = $this->checkPath($param['qrcode_path']);
            if ($checkpath_result["code"] != 0) return $checkpath_result;
            
            // scene:场景值最大32个可见字符，只支持数字，大小写英文以及部分特殊字符：!#$&'()*+,/:;=?@-._~
            $scene = '';
            if (!empty($param['data'])) {
                foreach ($param['data'] as $key => $value) {
                    if ($scene == '') $scene .= $key . '-' . $value;
                    else $scene .= '&' . $key . '-' . $value;
                }
            }
            $response = $this->app->app_code->getUnlimit($scene, [
                'page' => substr($param['page'], 1),
                'width' => isset($param['width']) ? $param['width'] : 120
            ]);

            if ($response instanceof \EasyWeChat\Kernel\Http\StreamResponse) {
                $filename = $param['qrcode_path'] . '/';
                $filename .= $response->saveAs($param['qrcode_path'], $param['qrcode_name'] . '_' . $param['app_type'] . '.png');
                return $this->success(['path' => $filename]);
            } else {
                if (isset($response['errcode']) && $response['errcode'] > 0) {
                    return $this->error('', $response['errmsg']);
                }
            }
            return $this->error();
        } catch (\Exception $e) {
            return $this->error('', $e->getMessage());
        }
    }
    
    /**
     * 校验目录是否可写
     * @param unknown $path
     * @return multitype:number unknown |multitype:unknown
     */
    private function checkPath($path)
    {
        if (is_dir($path) || mkdir($path, intval('0755', 8), true)) {
            return $this->success();
        }
        return $this->error('', "directory {$path} creation failed");
    }
    /*************************************************************  数据统计与分析 start **************************************************************/
    /**
     * 访问日趋势
     * @param $from  格式 20170313
     * @param $to 格式 20170313
     */
    public function dailyVisitTrend($from, $to)
    {
        try {
            $result = $this->app->data_cube->dailyVisitTrend($from, $to);
            if (isset($result['errcode']) && $result['errcode'] != 0) {
                return $this->error([], $result["errmsg"]);
            }
            return $this->success($result["list"]);
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage());
        }
    }

    /**
     * 访问周趋势
     * @param $from
     * @param $to
     * @return array|\multitype
     */
    public function weeklyVisitTrend($from, $to)
    {
        try {
            $result = $this->app->data_cube->weeklyVisitTrend($from, $to);
            if (isset($result['errcode']) && $result['errcode'] != 0) {
                return $this->error([], $result["errmsg"]);
            }
            return $this->success($result["list"]);
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage());
        }
    }

    /**
     * 访问月趋势
     * @param $from
     * @param $to
     * @return array|\multitype
     */
    public function monthlyVisitTrend($from, $to)
    {
        try {
            $result = $this->app->data_cube->monthlyVisitTrend($from, $to);
            if (isset($result['errcode']) && $result['errcode'] != 0) {
                return $this->error([], $result["errmsg"]);
            }
            return $this->success($result["list"]);
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage());
        }
    }

    /**
     * 访问分布
     * @param $from
     * @param $to
     */
    public function visitDistribution($from, $to)
    {
        try {
            $result = $this->app->data_cube->visitDistribution($from, $to);
            if (isset($result['errcode']) && $result['errcode'] != 0) {
                return $this->error($result, $result["errmsg"]);
            }
            return $this->success($result["list"]);
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage());
        }
    }

    /**
     * 访问页面
     * @param $from
     * @param $to
     */
    public function visitPage($from, $to)
    {
        try {
            $result = $this->app->data_cube->visitPage($from, $to);
            if (isset($result['errcode']) && $result['errcode'] != 0) {
                return $this->error([], $result["errmsg"]);
            }
            return $this->success($result["list"]);
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage());
        }
    }
    /*************************************************************  数据统计与分析 end **************************************************************/

    /**
     * 消息解密
     * @param array $param
     * @return array
     */
    public function decryptData($param = [])
    {
        try {
            $cache = Cache::get('weapp_' . $param['weapp_openid']);
            $session_key = $cache['session_key'] ?? $param;
            $result = $this->app->encryptor->decryptData($session_key, $param['iv'], $param['encryptedData']);
            if (isset($result['errcode']) && $result['errcode'] != 0) {
                return $this->error([], $result["errmsg"]);
            }
            return $this->success($result);
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage());
        }
    }


    /**
     * 获取订阅消息template_id
     * @param array $param
     */
    public function getTemplateId(array $param){
        try {
            $result = $this->app->subscribe_message->addTemplate($param['tid'], $param['kidList'], $param['sceneDesc']);
            return $result;
        } catch (\Exception $e) {
            return ['errcode' => -1, 'errmsg' => $e->getMessage()];
        }
    }

    /**
     * 发送订阅消息
     * @param array $param
     * @return array
     */
    public function sendTemplateMessage(array $param){

        $result = $this->app->subscribe_message->send([
            'template_id' => $param['template_id'],// 模板id
            'touser'      => $param['openid'], // openid
            'page' => $param['page'], // 点击模板卡片后的跳转页面 支持带参数
            'data'        => $param['data'] // 模板变量
        ]);

        if (isset($result['errcode']) && $result['errcode'] != 0) {
            return $this->error($result, $result["errmsg"]);
        }
        return $this->success($result);
    }

    /**
     * 消息推送
     */
    public function relateWeixin(){
        $server  = $this->app->server;
        $message = $server->getMessage();
        if (isset($message['MsgType'])) {
            switch ($message['MsgType']) {
                case 'event':
                    $this->app->server->push(function ($res) {
                        // 商品审核结果通知
                        if ($res['Event'] == 'open_product_spu_audit' && addon_is_exit('shopcomponent')) {
                            model('shopcompoent_goods')->update([
                                'edit_status' => $res['OpenProductSpuAudit']['status'],
                                'reject_reason' => $res['OpenProductSpuAudit']['reject_reason'],
                                'audit_time' => time()
                            ], [
                                ['out_product_id', '=', $res['OpenProductSpuAudit']['out_product_id'] ]
                            ]);
                        }
                        // 类目审核结果通知
                        if ($res['Event'] == 'open_product_category_audit' && addon_is_exit('shopcomponent')) {
                            model('shopcompoent_category_audit')->update([
                                'status' => $res['QualificationAuditResult']['status'],
                                'reject_reason' => $res['QualificationAuditResult']['reject_reason'],
                                'audit_time' => time()
                            ], [
                                ['audit_id', '=', $res['QualificationAuditResult']['audit_id'] ]
                            ]);
                        }
                        // 视频号支付订单回调
                        if ($res['Event'] == 'open_product_order_pay' && addon_is_exit('shopcomponent')) {
                            event("shopcomponentNotify", $res);
                        }
                    });
                    break;
            }
        }
        $response = $this->app->server->serve();
        return $response->send();
    }

    /**
     * 检查场景值是否在支付校验范围内
     * @param $scene
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sceneCheck($scene){
        try {
            $result = $this->app->mini_store->checkScene($scene);
            if (isset($result['errcode']) && $result['errcode'] == 0) {
                return $this->success($result['is_matched']);
            } else {
                return $this->error('', $result['errmsg']);
            }
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage());
        }
    }
}