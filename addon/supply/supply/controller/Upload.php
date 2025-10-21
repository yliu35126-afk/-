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

namespace addon\supply\supply\controller;

use app\model\upload\Upload as UploadModel;
use app\Controller;
use app\model\system\User as UserModel;

/**
 * 图片上传
 */
class Upload extends Controller
{
    public $supply_id = 0;
    protected $app_module = "supply";

    public function __construct()
    {
        //执行父类构造函数
        parent::__construct();
        $user_model = new UserModel();
        $this->user_info = $user_model->userInfo($this->app_module);
        $this->assign("user_info", $this->user_info);
        $this->supply_id = $this->user_info[ "site_id" ];
    }


    /**
     * 上传(不存入相册)
     * @return \app\model\upload\Ambigous|\multitype
     */
    public function image()
    {
        $upload_model = new UploadModel($this->supply_id);
        $thumb_type = input("thumb", "");
        $name = input("name", "");
        $param = array (
            "thumb_type" => "",
            "name" => "file"
        );
        $path = $this->supply_id > 0 ? "common/images/" . date("Ymd") . '/' : "common/images/" . date("Ymd") . '/';
        $result = $upload_model->setPath($path)->image($param);
        return $result;
    }


    /**
     * 上传 存入相册
     * @return \multitype
     */
    public function album()
    {
        $upload_model = new UploadModel($this->supply_id);
        $album_id = input("album_id", 0);
        $name = input("name", "");
        $param = array (
            "thumb_type" => [ "big", "mid", "small" ],
            "name" => "file",
            "album_id" => $album_id
        );
        $result = $upload_model->setPath("common/images/" . date("Ymd") . '/')->imageToAlbum($param);
        return $result;
    }


    /**
     * 视频上传
     * @return \multitype
     */
    public function video()
    {
        $upload_model = new UploadModel($this->supply_id);
        $name = input("name", "");
        $param = array (
            "name" => "file"
        );
        $result = $upload_model->setPath("common/video/" . date("Ymd") . '/')->video($param);
        return $result;
    }

    /**
     * 上传(不存入相册)
     * @return \app\model\upload\Ambigous|\multitype
     */
    public function upload()
    {
        $upload_model = new UploadModel();
        $thumb_type = input("thumb", "");
        $name = input("name", "");
        $param = array (
            "thumb_type" => "",
            "name" => "file"
        );
        $result = $upload_model->setPath("common/images/" . date("Ymd") . '/')->image($param);
        return $result;
    }

}