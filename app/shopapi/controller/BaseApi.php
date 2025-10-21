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

use app\model\shop\Shop;
use app\model\system\Api;
use app\model\system\Group as GroupModel;
use extend\RSA;
use think\facade\Cache;

class BaseApi
{
    public $lang;

    public $params;

    public $token;

    protected $user_info;

    protected $uid;

    protected $url;

    protected $site_id;

    protected $website_id;

    protected $group_info;

    protected $shop_info;

    public $app_type;

    protected $app_module = 'shop';

    protected $api_config;

    protected $addon = '';

    public function __construct()
    {
        $this->url = strtolower(request()->parseUrl());

        $this->addon = request()->addon() ? request()->addon() : '';

        //获取参数
        $this->params = input();
        $this->getApiConfig();
        $this->decryptParams();
    }

    /**
     * api请求参数解密
     */
    private function decryptParams()
    {
        if ($this->api_config[ 'is_use' ] && !empty($this->api_config[ 'value' ]) && isset($this->params[ 'encrypt' ])) {
            $decrypted = RSA::decrypt(
                $this->params[ 'encrypt' ],
                $this->api_config[ 'value' ][ 'private_key' ],
                $this->api_config[ 'value' ][ 'public_key' ]
            );
            if ($decrypted[ 'code' ] >= 0) {
                $this->params = json_decode($decrypted[ 'data' ], true);
            } else {
                $this->params = [];
            }
        }
    }

    /**
     * 获取api配置
     */
    private function getApiConfig()
    {
        $api_model = new Api();
        $config_result = $api_model->getApiConfig();
        $this->api_config = $config_result[ "data" ];
    }

    /**
     * 检测token(使用私钥检测)
     */
    protected function checkToken() : array
    {
        if (empty($this->params[ 'token' ])) {
            return $this->error('', 'TOKEN_NOT_EXIST');
        }

        if ($this->api_config[ 'is_use' ] && isset($this->api_config[ 'value' ][ 'private_key' ])
            && !empty($this->api_config[ 'value' ][ 'private_key' ])) {
            $decrypt = decrypt($this->params[ 'token' ], $this->api_config[ 'value' ][ 'private_key' ]);
        } else {
            $decrypt = decrypt($this->params[ 'token' ]);
        }
        if (empty($decrypt)) {
            return $this->error('', 'TOKEN_ERROR');
        }
        $data = json_decode($decrypt, true);

        if (!empty($data[ 'expire_time' ]) && $data[ 'expire_time' ] > time()) {
            return $this->error('', 'TOKEN_EXPIRE');
        }
        $this->user_info = $data[ 'user_info' ];

        $this->uid = $data[ 'user_info' ][ 'uid' ];

        $this->site_id = $data[ 'user_info' ][ 'site_id' ];

        $this->getShopInfo();
        $this->getGroupInfo();

        //判断权限
        if (!$this->checkAuth()) {
            return error(-1, '权限不足');
        }

        return success(0, '', $data);
    }

    /**
     * 创建token
     * @param $user_info
     * @param int $expire_time 有效时间  0为永久 单位s
     * @return string
     */
    protected function createToken($user_info, $expire_time = 0)
    {
        $data = [
            'user_info' => $user_info,
            'expire_time' => empty($expire_time) ? 0 : time() + $expire_time
        ];
        if ($this->api_config[ 'is_use' ] && isset($this->api_config[ 'value' ][ 'private_key' ])
            && !empty($this->api_config[ 'value' ][ 'private_key' ])) {
            $token = encrypt(json_encode($data), $this->api_config[ 'value' ][ 'private_key' ]);
        } else {
            $token = encrypt(json_encode($data));
        }
        return $token;
    }

    public function getShopInfo()
    {
        //获取店铺信息
        $shop = new Shop();
        $shop_info = $shop->getShopInfo([ [ 'site_id', '=', $this->site_id ] ], 'website_id, cert_id,site_name,logo,is_own,level_id,category_id,group_id,seo_keywords,seo_description,expire_time,shop_status');
        $this->website_id = $shop_info[ 'data' ][ 'website_id' ];
        $this->shop_info = $shop_info[ 'data' ];
//        if ($this->shop_info[ 'expire_time' ] == 0) {
//            $this->shop_info[ 'is_reopen' ] = 1;//永久有效
//        } elseif ($this->shop_info[ 'expire_time' ] > time()) {
//            $cha = $this->shop_info[ 'expire_time' ] - time();
//            $date = ceil(( $cha / 86400 ));
//            if ($date < 30) {
//                $this->shop_info[ 'is_reopen' ] = 2;//离到期一月内才可以申请续签
//                $this->shop_info[ 'expires_date' ] = (int) $date;
//            }
//        } else {
//            $this->shop_info[ 'is_reopen' ] = 3;
//            $this->shop_info[ 'expires_date' ] = 0;
//        }

//        if ($this->shop_info[ 'is_reopen' ] == 2 || $this->shop_info[ 'is_reopen' ] == 3) {
//				is_reopen == 2，店铺即将到期，请尽快续费
//            	is_reopen == 3，data.shop_info.shop_status == 0 ? '店铺已暂停服务，无法正常营业' : '店铺已经过期，请尽快续费'
//				店铺剩余$this->shop_info[ 'expire_time' ]天到期
//				$this->shop_info[ 'cert_id' ] == 0 ? '立即认证' : data.is_reopen == 1 ? '立即续费' : '立即续费'
//        }
    }

    /**
     * 获取当前用户的用户组
     */
    private function getGroupInfo()
    {
        $group_model = new GroupModel();

        $group_info_result = $group_model->getGroupInfo([ [ "group_id", "=", $this->user_info[ "group_id" ] ], [ "site_id", "=", $this->site_id ], [ "app_module", "=", $this->app_module ] ]);

        $this->group_info = $group_info_result[ "data" ];

    }

    /**
     * 返回数据
     * @param $data
     * @return false|string
     */
    public function response($data)
    {
        $data[ 'timestamp' ] = time();
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 操作成功返回值函数
     * @param string $data
     * @param string $code_var
     * @return array
     */
    public function success($data = '', $code_var = 'SUCCESS')
    {
        $lang_array = $this->getLang();
        $code_array = $this->getCode();
        $lang_var = isset($lang_array[ $code_var ]) ? $lang_array[ $code_var ] : $code_var;
        $code_var = isset($code_array[ $code_var ]) ? $code_array[ $code_var ] : $code_array[ 'SUCCESS' ];
        return success($code_var, $lang_var, $data);
    }

    /**
     * 操作失败返回值函数
     * @param string $data
     * @param string $code_var
     * @return array
     */
    public function error($data = '', $code_var = 'ERROR')
    {
        $lang_array = $this->getLang();
        $code_array = $this->getCode();
        $lang_var = isset($lang_array[ $code_var ]) ? $lang_array[ $code_var ] : $code_var;
        $code_var = isset($code_array[ $code_var ]) ? $code_array[ $code_var ] : $code_array[ 'ERROR' ];
        return error($code_var, $lang_var, $data);
    }

    /**
     * 获取语言包数组
     * @return array|mixed
     */
    private function getLang()
    {
        $default_lang = config("lang.default_lang");
        $addon = request()->addon();
        $addon = isset($addon) ? $addon : '';
        $cache_common = Cache::get("lang_app/api/lang/" . $default_lang);

        if (!empty($addon)) {
            $addon_cache_common = Cache::get("lang_app/api/lang/" . $addon . '_' . $default_lang);
            if (!empty($addon_cache_common)) {
                $cache_common = array_merge($cache_common, $addon_cache_common);
            }
        }

        if (empty($cache_common)) {
            $cache_common = include 'app/api/lang/' . $default_lang . '.php';
            Cache::tag("lang")->set("lang_app/api/lang/" . $default_lang, $cache_common);
            if (!empty($addon)) {
                try {
                    $addon_cache_common = include 'addon/' . $addon . '/api/lang/' . $default_lang . '.php';
                    if (!empty($addon_cache_common)) {
                        $cache_common = array_merge($cache_common, $addon_cache_common);
                        Cache::tag("lang")->set(
                            "lang_app/api/lang/" . $addon . '_' . $default_lang,
                            $addon_cache_common
                        );
                    }
                } catch (\Exception $e) {
                }
            }
        }
        $lang_path = isset($this->lang) ? $this->lang : '';
        if (!empty($lang_path)) {
            $cache_path = Cache::get("lang_" . $lang_path . "/" . $default_lang);
            if (empty($cache_path)) {
                $cache_path = include $lang_path . "/" . $default_lang . '.php';
                Cache::tag("lang")->set("lang_" . $lang_path . "/" . $default_lang, $cache_path);
            }
            $lang = array_merge($cache_common, $cache_path);
        } else {
            $lang = $cache_common;
        }
        return $lang;
    }

    /**
     * 获取code编码
     * @return array|mixed
     */
    private function getCode()
    {
        $addon = request()->addon();
        $addon = isset($addon) ? $addon : '';
        $cache_common = Cache::get("lang_code_app/api/lang");

        if (!empty($addon)) {
            $addon_cache_common = Cache::get("lang_code_app/api/lang/" . $addon);
            if (!empty($addon_cache_common)) {
                $cache_common = array_merge($cache_common, $addon_cache_common);
            }
        }

        if (empty($cache_common)) {
            $cache_common = include 'app/api/lang/code.php';
            Cache::tag("lang_code")->set("lang_code_app/api/lang", $cache_common);

            if (!empty($addon)) {
                try {
                    $addon_cache_common = include 'addon/' . $addon . '/api/lang/code.php';
                    if (!empty($addon_cache_common)) {
                        Cache::tag("lang_code")->set("lang_code_app/api/lang/" . $addon, $addon_cache_common);
                        $cache_common = array_merge($cache_common, $addon_cache_common);
                    }
                } catch (\Exception $e) {
                }
            }
        }
        $lang_path = isset($this->lang) ? $this->lang : '';
        if (!empty($lang_path)) {
            $cache_path = Cache::get("lang_code_" . $lang_path);
            if (empty($cache_path)) {
                $cache_path = include $lang_path . '/code.php';
                Cache::tag("lang")->set("lang_code_" . $lang_path, $cache_path);
            }
            $lang = array_merge($cache_common, $cache_path);
        } else {
            $lang = $cache_common;
        }
        return $lang;
    }


    /**
     * 检测权限
     */
    private function checkAuth()
    {
        if (empty($addon)) {
            $auth_name = 'config/auth_shopapi.php';
        } else {
            $auth_name = 'addon/' . $addon . '/config/auth_shopapi.php';
        }

        $auth_array = require $auth_name;
        $this->url = strtolower($this->url);

        if ($this->group_info[ 'is_system' ] == 1) {
            return true;
        }
        if (!isset($auth_array[ $this->url ])) {
            return true;
        }
        $auth_control = event("AuthControl", [ 'key' => $auth_array[ $this->url ], 'app_module' => $this->app_module, 'ajax' => 1 ], 1);
        if (!empty($auth_control)) {
            if ($auth_control[ 'code' ] < 0) {
                return false;
            }
        }

        if (array_key_exists($this->url, $auth_array)) {

            if (strpos(',' . $this->group_info[ 'menu_array' ] . ',', ',' . $auth_array[ $this->url ] . ',')) {
                return true;
            } else {
                return false;
            }

        } else {
            return true;
        }

    }
}
