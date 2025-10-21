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

namespace addon\qiniu\model;

use app\model\BaseModel;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;

// 引入鉴权类
// 引入上传类

/**
 * 七牛云上传
 */
class Qiniu extends BaseModel
{

    /**
     * 字节组上传
     * @param $data
     * @param $key
     * @return array
     */
    public function put($param)
    {
        $data = $param[ "data" ];
        $key = $param[ "key" ];
        $config_model = new Config();
        $config_result = $config_model->getQiniuConfig();
        $config = $config_result[ "data" ];

        if ($config[ "is_use" ] == 1) {
            $config = $config[ "value" ];
            $accessKey = $config[ "access_key" ];
            $secretKey = $config[ "secret_key" ];
            $bucket = $config[ "bucket" ];
            $auth = new Auth($accessKey, $secretKey);
            $token = $auth->uploadToken($bucket);
            $uploadMgr = new UploadManager();
            //----------------------------------------upload demo1 ----------------------------------------
            // 上传字符串到七牛
            list($ret, $err) = $uploadMgr->put($token, $key, $data);
            if ($err !== null) {
                return $this->error($err->getResponse()->error);
            } else {
                //返回图片的完整URL
                $domain = $config[ "domain" ];//自定义域名
                $data = array (
                    "path" => $domain . "/" . $key,
                    "domain" => $domain,
                    "bucket" => $bucket
                );
                return $this->success($data);
            }
        }
    }

    /**
     * 设置七牛参数配置
     * @param unknown $filePath 上传图片路径
     * @param unknown $key 上传到七牛后保存的文件名
     */
    public function putFile($param)
    {
        $file_path = $param[ "file_path" ];
        $key = $param[ "key" ];
        $config_model = new Config();
        $config_result = $config_model->getQiniuConfig();
        $config = $config_result[ "data" ];
        if ($config[ "is_use" ] == 1) {
            $config = $config[ "value" ];
            $accessKey = $config[ "access_key" ];
            $secretKey = $config[ "secret_key" ];
            $bucket = $config[ "bucket" ];
            $auth = new Auth($accessKey, $secretKey);
            //要上传的空间
            $domain = $config[ "domain" ];
            $token = $auth->uploadToken($bucket);
            // 初始化 UploadManager 对象并进行文件的上传
            $uploadMgr = new UploadManager();
            // 调用 UploadManager 的 putFile 方法进行文件的上传
            list($ret, $err) = $uploadMgr->putFile($token, $key, $file_path);
            if ($err !== null) {
                return $this->error($err->getResponse()->error);
            } else {
                //返回图片的完整URL
                $domain = $config[ "domain" ];//自定义域名
                $data = array (
                    "path" => $domain . "/" . $key,
                    "domain" => $domain,
                    "bucket" => $bucket
                );
                return $this->success($data);
            }
        }
    }

}