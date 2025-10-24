<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\model\system;

use app\model\BaseModel;
use extend\api\HttpClient;
use think\facade\Cache;

class Web extends BaseModel
{
    private $url = "https://www.niushop.com";

    /**
     * 官网资讯
     */
    public function news()
    {
        $cache = Cache::get("new_day");
        if (!empty($cache) && is_array($cache)) return $cache;

        $result = $this->doPost('/api/article/companynews', []);
        $res = json_decode($result, true);

        // 兼容外网不可达或返回异常的情况，避免 500 错误
        if (is_array($res) && isset($res['code'])) {
            if ($res['code'] >= 0) {
                Cache::set("new_day", $res, 86400);
            }
            return $res;
        }

        return [
            'code' => -1,
            'message' => '官方资讯接口不可用',
            'data' => [
                'list' => []
            ]
        ];
    }

    /**
     * post 服务器请求
     */
    private function doPost($post_url, $post_data)
    {
        $url = $this->url . $post_url;
        // 如果未启用 curl 扩展，使用 PHP 流上下文作为回退方案
        if (function_exists('curl_init')) {
            $httpClient = new HttpClient();
            return $httpClient->post($url, $post_data);
        } else {
            $opts = [
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-Type: application/x-www-form-urlencoded',
                    'content' => http_build_query($post_data),
                    'timeout' => 10,
                ]
            ];
            $context = stream_context_create($opts);
            $res = @file_get_contents($url, false, $context);
            return $res !== false ? $res : json_encode([
                'code' => -1,
                'message' => 'request failed',
                'data' => ['list' => []]
            ]);
        }
    }
}
