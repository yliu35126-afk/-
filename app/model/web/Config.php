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

namespace app\model\web;

use app\model\system\Config as ConfigModel;
use app\model\BaseModel;
use app\model\system\Upgrade;

/**
 * 网站系统性设置
 */
class Config extends BaseModel
{
    //缓存类型
    private $cache_list = [
        [
            'name' => '数据缓存',
            'desc' => '数据缓存',
            'key' => 'content',
            'icon' => 'public/static/img/cache/data.png'
        ],
        [
            'name' => '数据表缓存',
            'desc' => '数据表缓存',
            'key' => 'data_table_cache',
            'icon' => 'public/static/img/cache/data_table.png'
        ],
        [
            'name' => '模板缓存',
            'desc' => '模板缓存',
            'key' => 'template_cache',
            'icon' => 'public/static/img/cache/template.png'
        ],
    ];

    /**
     * 验证码设置
     * array $data
     */
    public function setCaptchaConfig($data)
    {
        $config = new ConfigModel();
        $res = $config->setConfig($data, '验证码设置', 1, [ [ 'site_id', '=', 0 ], [ 'app_module', '=', 'admin' ], [ 'config_key', '=', 'CAPTCHA_CONFIG' ] ]);
        return $res;
    }

    /**
     * 查询验证码设置
     */
    public function getCaptchaConfig()
    {
        $config = new ConfigModel();
        $res = $config->getConfig([[ 'site_id', '=', 0 ], [ 'app_module', '=', 'admin' ], [ 'config_key', '=', 'CAPTCHA_CONFIG' ]]);
        // 防御空值，避免 "Trying to access array offset on value of type null"
        if (empty($res['data']) || empty($res['data']['value'])) {
            $res['data'] = $res['data'] ?? [];
            $res['data']['value'] = [
                'shop_login' => 1,
                'admin_login' => 0,
                'store_login' => 1,
                'supply_login' => 1,
                'city_login' => 1,
                'shop_reception_login' => 1,
                'shop_pc_reception_login' => 1
            ];
        }
        return $res;
    }

    /**
     * 默认图上传配置
     * array $data
     */
    public function setDefaultImg($data)
    {
        $config = new ConfigModel();
        $res = $config->setConfig($data, '默认图设置', 1, [ [ 'site_id', '=', 0 ], [ 'app_module', '=', 'admin' ], [ 'config_key', '=', 'DEFAULT_IMAGE' ] ]);
        return $res;
    }

    public function setCopyright($data)
    {
        $config = new ConfigModel();
        $res = $config->setConfig($data, '版权设置', 1, [ [ 'site_id', '=', 0 ], [ 'app_module', '=', 'admin' ], [ 'config_key', '=', 'COPYRIGHT' ] ]);
        return $res;
    }

    /**
     * 默认图查询上传配置
     */
    public function getDefaultImg()
    {
        $config = new ConfigModel();
        $res = $config->getConfig([ [ 'site_id', '=', 0 ], [ 'app_module', '=', 'admin' ], [ 'config_key', '=', 'DEFAULT_IMAGE' ] ]);
        // 兼容 data/value 为空的情况，避免对 null 做数组下标访问
        $defaults = [
            "default_goods_img" => "upload/default/default_img/goods.png",
            "default_headimg" => "upload/default/default_img/head.png",
            "default_shop_img" => "upload/default/default_img/shop.png",
            "default_city_img" => "upload/default/default_img/city.png",
            "default_supply_img" => "upload/default/default_img/supply.png",
            "default_store_img" => "upload/default/default_img/store.png",
            "default_category_img" => "upload/default/default_img/category.png",
            "default_brand_img" => "upload/default/default_img/brand.png"
        ];
        if (empty($res['data']) || empty($res['data']['value'])) {
            $res['data'] = $res['data'] ?? [];
            $res['data']['value'] = $defaults;
        }
        return $res;
    }

    /**
     * 获取缓存类型
     */
    public function getCacheList()
    {
        return $this->cache_list;
    }

    /**
     * 获取版权信息
     * @return array
     */
    public function getCopyright()
    {
        $config = new ConfigModel();
        $res = $config->getConfig([[ 'site_id', '=', 0 ], [ 'app_module', '=', 'admin' ], [ 'config_key', '=', 'COPYRIGHT' ]]);

        // 统一空值兼容，防止对 null 做数组下标访问
        $defaults = [
            'logo' => '',
            'company_name' => '',
            'copyright_link' => '',
            'copyright_desc' => '',
            'icp' => '',
            'gov_record' => '',
            'gov_url' => '',
            'market_supervision_url' => ''
        ];

        if (empty($res['data']) || empty($res['data']['value'])) {
            $res['data'] = $res['data'] ?? [];
            $res['data']['value'] = $defaults;
        } else {
            // 保留已有值同时补齐缺失字段
            $res['data']['value'] = array_merge($defaults, (array)$res['data']['value']);
        }
        return $res;
    }

    /**
     * 商城配置
     * array $data
     */
    public function setBasicConfig($data)
    {
        $config = new ConfigModel();
        $res = $config->setConfig($data, '商城配置', 1, [ [ 'site_id', '=', 0 ], [ 'app_module', '=', 'admin' ], [ 'config_key', '=', 'BASIC_CONFIG' ] ]);
        return $res;
    }

    /**
     * 商城配置
     */
    public function getBasicConfig()
    {
        $config = new ConfigModel();
        $res = $config->getConfig([ [ 'site_id', '=', 0 ], [ 'app_module', '=', 'admin' ], [ 'config_key', '=', 'BASIC_CONFIG' ] ]);
        return $res;
    }

    /**
     * 授权设置
     * @param $data
     * @param int $site_id
     * @param string $app_model
     * @return array
     */
    public function setAuth($data, $site_id = 0, $app_model = 'admin')
    {
        $config = new ConfigModel();
        $res = $config->setConfig($data, '授权设置', 1, [ [ 'site_id', '=', $site_id ], [ 'app_module', '=', $app_model ], [ 'config_key', '=', 'AUTH' ] ]);
        return $res;
    }

    /**
     * 获取授权设置
     * @param int $site_id
     * @param string $app_module
     * @return array
     */
    public function getAuth($site_id = 0, $app_module = 'admin')
    {
        $config = new ConfigModel();
        $res = $config->getConfig([ [ 'site_id', '=', $site_id ], [ 'app_module', '=', $app_module ], [ 'config_key', '=', 'AUTH' ] ]);
        if (empty($res[ 'data' ][ 'value' ])) {
            $res[ 'data' ][ 'value' ] = [
                'code' => '',
            ];
        }
        return $res;
    }

    /**
     * 地图设置
     * @param $data
     * @return array
     */
    public function setMapConfig($data)
    {
        $config = new ConfigModel();
        $res = $config->setConfig($data, '地图设置', 1, [ [ 'site_id', '=', 0 ], [ 'app_module', '=', 'admin' ], [ 'config_key', '=', 'MAP_CONFIG' ] ]);
        return $res;
    }

    /**
     * 获取地图设置
     * @return array
     */
    public function getMapConfig()
    {
        $config = new ConfigModel();
        $res = $config->getConfig([ [ 'site_id', '=', 0 ], [ 'app_module', '=', 'admin' ], [ 'config_key', '=', 'MAP_CONFIG' ] ]);
        if (empty($res[ 'data' ][ 'value' ])) {
            $res[ 'data' ][ 'value' ] = [
                'tencent_map_key' => '',
                'gaode_map_key' => '',
            ];
        }
        return $res;
    }


    /**
     * 设置热门搜索关键词  todo  各端口的热搜和默认搜索
     * @param $data
     * @param $site_id
     * @param $app_module
     * @return array
     */
    public function setHotSearchWords($data, $site_id, $app_module)
    {
        $config = new ConfigModel();
        $res = $config->setConfig($data, '商品热门搜索关键词', 1, [ [ 'site_id', '=', $site_id ], [ 'app_module', '=', $app_module ], [ 'config_key', '=', 'GOODS_HOT_SEARCH_WORDS_CONFIG' ] ]);
        return $res;
    }

    /**
     * 获取热门搜索关键词
     * @param $site_id
     * @param $app_module
     * @return array
     */
    public function getHotSearchWords($site_id, $app_module)
    {
        $config = new ConfigModel();
        $res = $config->getConfig([ [ 'site_id', '=', $site_id ], [ 'app_module', '=', $app_module ], [ 'config_key', '=', 'GOODS_HOT_SEARCH_WORDS_CONFIG' ] ]);
        if (empty($res[ 'data' ][ 'value' ])) {
            $res[ 'data' ][ 'value' ] = [
                'words' => ''
            ];
        }
        return $res;
    }

    /**
     * 设置默认搜索关键词
     * @param $data
     * @param $site_id
     * @param $app_module
     * @return array
     */
    public function setDefaultSearchWords($data, $site_id, $app_module)
    {
        $config = new ConfigModel();
        $res = $config->setConfig($data, '默认搜索关键词', 1, [ [ 'site_id', '=', $site_id ], [ 'app_module', '=', $app_module ], [ 'config_key', '=', 'GOODS_DEFAULT_SEARCH_WORDS_CONFIG' ] ]);
        return $res;
    }

    /**
     * 获取默认搜索关键词
     * @param $site_id
     * @param $app_module
     * @return array
     */
    public function getDefaultSearchWords($site_id, $app_module)
    {
        $config = new ConfigModel();
        $res = $config->getConfig([ [ 'site_id', '=', $site_id ], [ 'app_module', '=', $app_module ], [ 'config_key', '=', 'GOODS_DEFAULT_SEARCH_WORDS_CONFIG' ] ]);
        if (empty($res[ 'data' ][ 'value' ])) {
            $res[ 'data' ][ 'value' ] = [
                'words' => '搜索 商品'
            ];
        }
        return $res;
    }

    /**
     * h5域名配置
     */
    public function seth5DomainName($data)
    {
        $config = new ConfigModel();
        $res = $config->setConfig($data, 'H5域名配置', 1, [ [ 'site_id', '=', 0 ], [ 'app_module', '=', 'admin' ], [ 'config_key', '=', 'H5_DOMAIN_NAME' ] ]);
        return $res;
    }

    /**
     * 获取h5域名配置
     */
    public function geth5DomainName()
    {
        $config = new ConfigModel();
        $res = $config->getConfig([ [ 'site_id', '=', 0 ], [ 'app_module', '=', 'admin' ], [ 'config_key', '=', 'H5_DOMAIN_NAME' ] ]);
        if (empty($res[ 'data' ][ 'value' ])) {
            $res[ 'data' ][ 'value' ] = [
                'domain_name_h5' => __ROOT__ . '/h5',
                'deploy_way' => 'default'
            ];
        }
        return $res;
    }

    /**
     * pc域名配置
     */
    public function setPcDomainName($data)
    {
        $config = new ConfigModel();
        $res = $config->setConfig($data, 'PC域名配置', 1, [ [ 'site_id', '=', 0 ], [ 'app_module', '=', 'admin' ], [ 'config_key', '=', 'PC_DOMAIN_NAME' ] ]);
        return $res;
    }

    /**
     * 获取pc域名配置
     */
    public function getPcDomainName()
    {
        $config = new ConfigModel();
        $res = $config->getConfig([ [ 'site_id', '=', 0 ], [ 'app_module', '=', 'admin' ], [ 'config_key', '=', 'PC_DOMAIN_NAME' ] ]);
        if (empty($res[ 'data' ][ 'value' ])) {
            $res[ 'data' ][ 'value' ] = [
                'domain_name_pc' => __ROOT__ . '/web',
                'deploy_way' => 'default'
            ];
        }
        return $res;
    }

    /**
     * 设置pc端分类显示
     */
    public function setCategoryConfig($data)
    {
        $config = new ConfigModel();
        $res = $config->setConfig($data, 'PC分类显示配置', 1, [ [ 'site_id', '=', 0 ], [ 'app_module', '=', 'admin' ], [ 'config_key', '=', 'PC_CATEGORY_CONFIG' ] ]);
        return $res;
    }

    /**
     * 获取pc端分类显示配置
     * @return array
     */
    public function getCategoryConfig()
    {
        $config = new ConfigModel();
        $res = $config->getConfig([ [ 'site_id', '=', 0 ], [ 'app_module', '=', 'admin' ], [ 'config_key', '=', 'PC_CATEGORY_CONFIG' ] ]);
        if (empty($res[ 'data' ][ 'value' ])) {
            $res[ 'data' ][ 'value' ] = [
                'category' => 1,
                'img' => 1
            ];
        }
        return $res;
    }
}