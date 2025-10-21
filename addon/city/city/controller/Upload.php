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

namespace addon\city\city\controller;

use app\model\upload\Upload as UploadModel;
use app\model\upload\Config as ConfigModel;

/**
 * 上传 控制器
 */
class Upload extends BaseCity
{


    /**
     * 上传(不存入相册)
     * @return \app\model\upload\Ambigous|\multitype
     */
    public function upload()
    {
        $upload_model = new UploadModel($this->site_id);
        $thumb_type = input("thumb", "");
        $name = input("name", "");
        $param = array (
            "thumb_type" => "",
            "name" => "file"
        );
        $result = $upload_model->setPath("common/images/" . date("Ymd") . '/')->image($param);
        return $result;
    }

    /**
     * 上传 存入相册
     * @return \multitype
     */
    public function uploadToAlbum()
    {
        $upload_model = new UploadModel($this->site_id);
        $album_id = input("album_id", 0);
        $name = input("name", "");
        $param = array (
            "thumb_type" => [ "big", "mid", "small" ],
            "name" => "file",
            'album_id' => $album_id
        );
        $result = $upload_model->setPath("common/images/" . date("Ymd") . '/')->imageToAlbum($param);
        return $result;
    }

    /**
     * 云上传方式
     */
    public function oss()
    {
        if (request()->isAjax()) {
            $config_model = new ConfigModel();
            $list = event('OssType', []);
            return $config_model->success($list);
        } else {
            $this->forthMenu();
            return $this->fetch("upload/oss");
        }
    }
}