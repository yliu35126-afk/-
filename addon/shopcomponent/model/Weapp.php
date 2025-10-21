<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace addon\shopcomponent\model;

use addon\weapp\model\Config as WeappConfigModel;
use addon\wxoplatform\model\Config as WxOplatformConfigModel;
use app\model\BaseModel;
use EasyWeChat\Factory;
use think\facade\Cache;

class Weapp extends BaseModel
{
    public function __construct($site_id = 0)
    {
        //微信小程序配置
        $weapp_config_model = new WeappConfigModel();
        $weapp_config       = $weapp_config_model->getWeappConfig($site_id);
        $weapp_config       = $weapp_config["data"]["value"];

        if (isset($weapp_config['is_authopen']) && addon_is_exit('wxoplatform')) {
            $plateform_config_model = new WxOplatformConfigModel();
            $plateform_config       = $plateform_config_model->getOplatformConfig();
            $plateform_config       = $plateform_config["data"]["value"];

            $config        = [
                'app_id'  => $plateform_config["appid"] ?? '',
                'secret'  => $plateform_config["secret"] ?? '',
                'token'   => $plateform_config["token"] ?? '',
                'aes_key' => $plateform_config["aes_key"] ?? '',
                'log'     => [
                    'level'      => 'debug',
                    'permission' => 0777,
                    'file'       => 'runtime/log/wechat/oplatform.logs',
                ],
            ];
            $open_platform = Factory::openPlatform($config);
            $this->app     = $open_platform->miniProgram($weapp_config['authorizer_appid'], $weapp_config['authorizer_refresh_token']);
        } else {
            $config    = [
                'app_id'        => $weapp_config["appid"] ?? '',
                'secret'        => $weapp_config["appsecret"] ?? '',
                'response_type' => 'array',
                'log'           => [
                    'level'      => 'debug',
                    'permission' => 0777,
                    'file'       => 'runtime/log/wechat/easywechat.logs',
                ],
            ];
            $this->app = Factory::miniProgram($config);
        }
    }

    /**
     * 检测自定义交易组件接入状态
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function checkRegister(){
        try {
            $result = $this->app->mini_store->check();
            if (isset($result['errcode']) && $result['errcode'] == 0) {
                return $this->success($result['data']);
            } else {
                return $this->error('', $result['errmsg']);
            }
        } catch (\Exception $e) {
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 接入申请
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function apply(){
        try {
            $result = $this->app->mini_store->apply();
            if (isset($result['errcode']) && $result['errcode'] == 0) {
                return $this->success();
            } else {
                return $this->error('', $result['errmsg']);
            }
        } catch (\Exception $e) {
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 获取微信类目
     * @return array
     */

    public function getCatList()
    {
        try {
            $result = $this->app->mini_store->get();
            if (isset($result['errcode']) && $result['errcode'] == 0) {
                return $this->success($result['third_cat_list']);
            } else {
                return $this->error('', $result['errmsg']);
            }
        } catch (\Exception $e) {
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 提交类目资质
     * @param $param
     * @return array
     */
    public function auditCategory($param)
    {
        try {
            $result = $this->app->mini_store->auditCategory($param);
            if (isset($result['errcode']) && $result['errcode'] == 0) {
                return $this->success($result['audit_id']);
            } else {
                return $this->error($result['errcode'] ?? '', $result['errmsg']);
            }
        } catch (\Exception $e) {
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 添加商品
     * @param $param
     * @return array
     */
    public function addSpu($param){
        try {
            $result = $this->app->mini_store->addSpu($param);
            if (isset($result['errcode']) && $result['errcode'] == 0) {
                return $this->success($result['data']);
            } else {
                return $this->error('', $result['errmsg']);
            }
        } catch (\Exception $e) {
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 更新商品
     * @param $param
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function updateSpu($param){
        try {
            $result = $this->app->mini_store->updateSpu($param);
            if (isset($result['errcode']) && $result['errcode'] == 0) {
                return $this->success($result['data']);
            } else {
                return $this->error('', $result['errmsg']);
            }
        } catch (\Exception $e) {
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 获取商品
     * @param $param
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getSpuPage($param){
        try {
            $result = $this->app->mini_store->getSpuList($param);
            if (isset($result['errcode']) && $result['errcode'] == 0) {
                return $this->success(['total' => $result['total_num'], 'list' => $result['spus'] ]);
            } else {
                return $this->error('', $result['errmsg']);
            }
        } catch (\Exception $e) {
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 商品上架
     * @param $param
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function listing($param){
        try {
            $result = $this->app->mini_store->listingSpu($param['product_id'] ?? '', $param['out_product_id'] ?? '');
            if (isset($result['errcode']) && $result['errcode'] == 0) {
                return $this->success();
            } else {
                return $this->error('', $result['errmsg']);
            }
        } catch (\Exception $e) {
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 商品下架
     * @param $param
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function delisting($param){
        try {
            $result = $this->app->mini_store->delistingSpu($param['product_id'] ?? '', $param['out_product_id'] ?? '');
            if (isset($result['errcode']) && $result['errcode'] == 0) {
                return $this->success();
            } else {
                return $this->error('', $result['errmsg']);
            }
        } catch (\Exception $e) {
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 删除商品
     * @param $param
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function delSpu($param){
        try {
            $result = $this->app->mini_store->delSpu($param['product_id'] ?? '', $param['out_product_id'] ?? '');
            if (isset($result['errcode']) && $result['errcode'] == 0) {
                return $this->success();
            } else {
                return $this->error('', $result['errmsg']);
            }
        } catch (\Exception $e) {
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 创建订单
     * @param $param
     * @return array
     */
    public function addOrder($param){
        try {
            $result = $this->app->mini_store->addOrder($param);
            if (isset($result['errcode']) && $result['errcode'] == 0) {
                return $this->success($result['data']);
            } else {
                return $this->error('', $result['errmsg']);
            }
        } catch (\Exception $e) {
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 获取订单
     * @param $param
     * @return array
     */
    public function getOrder($param){
        try {
            $result = $this->app->mini_store->getOrder($param['order_id'] ?? '', $param['out_order_id'] ?? '', $param['openid']);
            if (isset($result['errcode']) && $result['errcode'] == 0) {
                return $this->success($result['order']);
            } else {
                return $this->error('', $result['errmsg']);
            }
        } catch (\Exception $e) {
            return $this->error('', $e->getMessage());
        }
    }


    /**
     * 订单支付状态同步
     * @param $param
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function pay($param){
        try {
            $result = $this->app->mini_store->payOrder($param);
            if (isset($result['errcode']) && $result['errcode'] == 0) {
                return $this->success();
            } else {
                return $this->error('', $result['errmsg']);
            }
        } catch (\Exception $e) {
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 获取快递公司列表
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getCompanyList(){
        $cache = Cache::get('weixinCompanyList');
        if ($cache) return $cache;

        try {
            $result = $this->app->mini_store->getDeliveryCompanyList();
            if (isset($result['errcode']) && $result['errcode'] == 0) {
                $data = $this->success($result['company_list']);
                Cache::set('weixinCompanyList', $data);
                return $data;
            } else {
                return $this->error('', $result['errmsg']);
            }
        } catch (\Exception $e) {
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 订单发货
     * @param $param
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendDelivery($param){
        try {
            $result = $this->app->mini_store->sendDelivery($param['order_id'] ?? '', $param['out_order_id'] ?? '', $param['openid'], $param['finish_all_delivery'], $param['delivery_list']);
            if (isset($result['errcode']) && $result['errcode'] == 0) {
                return $this->success();
            } else {
                return $this->error('', $result['errmsg']);
            }
        } catch (\Exception $e) {
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 订单收货
     * @param $param
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function recieveDelivery($param){
        try {
            $result = $this->app->mini_store->recieveDelivery($param['order_id'] ?? '', $param['out_order_id'] ?? '', $param['openid']);
            if (isset($result['errcode']) && $result['errcode'] == 0) {
                return $this->success();
            } else {
                return $this->error('', $result['errmsg']);
            }
        } catch (\Exception $e) {
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 创建售后
     * @param $param
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function addAftersale($param){
        try {
            $result = $this->app->mini_store->addAftersale($param);
            if (isset($result['errcode']) && $result['errcode'] == 0) {
                return $this->success();
            } else {
                return $this->error('', $result['errmsg']);
            }
        } catch (\Exception $e) {
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 获取审核状态
     * @param $audit_id
     * @return array
     */
    public function getAuditResult($audit_id){
        try {
            $result = $this->app->mini_store->auditResult($audit_id);
            if (isset($result['errcode']) && $result['errcode'] == 0) {
                return $this->success($result['data']);
            } else {
                return $this->error('', $result['errmsg']);
            }
        } catch (\Exception $e) {
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 完成接入任务
     * @param $item
     * @return array
     */
    public function finishAccessInfo($item){
        try {
            $result = $this->app->mini_store->finishAccessInfo($item);
            if (isset($result['errcode']) && $result['errcode'] == 0) {
                return $this->success();
            } else {
                return $this->error('', $result['errmsg']);
            }
        } catch (\Exception $e) {
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 获取图片信息
     * @param $item
     * @return array
     */
    public function getImg($url){
        try {
            $result = $this->app->mini_store->uploadImg($url);
            if (isset($result['errcode']) && $result['errcode'] == 0) {
                return $this->success($result);
            } else {
                return $this->error('', $result['errmsg']);
            }
        } catch (\Exception $e) {
            return $this->error('', $e->getMessage());
        }
    }

    public function updateShop($params){
        try {
            $result = $this->app->mini_store->updateShop($params);
            if (isset($result['errcode']) && $result['errcode'] == 0) {
                return $this->success($result);
            } else {
                return $this->error('', $result['errmsg']);
            }
        } catch (\Exception $e) {
            return $this->error('', $e->getMessage());
        }
    }

    public function updateOrderType($params){
        try {
            $result = $this->app->mini_store->updateOrderType($params);
            if (isset($result['errcode']) && $result['errcode'] == 0) {
                return $this->success($result);
            } else {
                return $this->error('', $result['errmsg']);
            }
        } catch (\Exception $e) {
            return $this->error('', $e->getMessage());
        }
    }

    public function createOrder($params){
        try {
            $result = $this->app->mini_store->addOrder($params);
            if (isset($result['errcode']) && $result['errcode'] == 0) {
                return $this->success($result);
            } else {
                return $this->error('', $result['errmsg']);
            }
        } catch (\Exception $e) {
            return $this->error('', $e->getMessage());
        }
    }

    public function getPaymentParams($params){
        try {
            $result = $this->app->mini_store->getPaymentParams($params);
            if (isset($result['errcode']) && $result['errcode'] == 0) {
                return $this->success($result);
            } else {
                return $this->error('', $result['errmsg']);
            }
        } catch (\Exception $e) {
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * @param $params
     * @return array
     * 同意退款
     */
    public function orderRefund($params){
        try {
            $result = $this->app->mini_store->orderRefund($params);
            if (isset($result['errcode']) && $result['errcode'] == 0) {
                return $this->success($result);
            } else {
                return $this->error('', $result['errmsg']);
            }
        } catch (\Exception $e) {
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * @param $params
     * @return array
     * 拒绝退款
     */
    public function orderNoRefund($params){
        try {
            $result = $this->app->mini_store->orderNoRefund($params);
            if (isset($result['errcode']) && $result['errcode'] == 0) {
                return $this->success($result);
            } else {
                return $this->error('', $result['errmsg']);
            }
        } catch (\Exception $e) {
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * @param $params
     * @return array
     * 同意退货
     */
    public function aceptreturn($params){
        try {
            $result = $this->app->mini_store->aceptreturn($params);
            if (isset($result['errcode']) && $result['errcode'] == 0) {
                return $this->success($result);
            } else {
                return $this->error('', $result['errmsg']);
            }
        } catch (\Exception $e) {
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * @param $params
     * @return array
     * 同意退货
     */
    public function cancel($params){
        try {
            $result = $this->app->mini_store->cancel($params);
            if (isset($result['errcode']) && $result['errcode'] == 0) {
                return $this->success($result);
            } else {
                return $this->error('', $result['errmsg']);
            }
        } catch (\Exception $e) {
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 卖家发货
     */
    public function uploadreturninfo($params){
        try {
            $result = $this->app->mini_store->uploadreturninfo($params);
            if (isset($result['errcode']) && $result['errcode'] == 0) {
                return $this->success($result);
            } else {
                return $this->error('', $result['errmsg']);
            }
        } catch (\Exception $e) {
            return $this->error('', $e->getMessage());
        }
    }

    //获取售后详情
    public function getAftersale($params){
        try {
            $result = $this->app->mini_store->getAftersale($params);
            if (isset($result['errcode']) && $result['errcode'] == 0) {
                return $this->success($result);
            } else {
                return $this->error('', $result['errmsg']);
            }
        } catch (\Exception $e) {
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 更新售后
     * @param $param
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function updateAftersale($params){
        try {
            $result = $this->app->mini_store->updateAftersale($params);
            if (isset($result['errcode']) && $result['errcode'] == 0) {
                return $this->success($result);
            } else {
                return $this->error('', $result['errmsg']);
            }
        } catch (\Exception $e) {
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * @param $params
     * @return array
     * 获取售后订单列表
     */
    public function getOrderList($params){
        try {
            $result = $this->app->mini_store->getOrderList($params);
            if (isset($result['errcode']) && $result['errcode'] == 0) {
                return $this->success($result);
            } else {
                return $this->error('', $result['errmsg']);
            }
        } catch (\Exception $e) {
            return $this->error('', $e->getMessage());
        }
    }
}