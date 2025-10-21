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

namespace addon\store\store\controller;

use app\model\upload\Upload as UploadModel;

/**
 * 图片上传
 * Class Verify
 * @package app\shop\controller
 */
class Upload extends BaseStore
{
    public $site_id = 0;
    protected $app_module = "store";

    public function __construct()
    {
        //执行父类构造函数
        parent::__construct();

    }

    /**
     * 上传文件
     */
    public function file()
    {
        $upload_model = new UploadModel($this->site_id);

        $param = array (
            "name" => "file",
            'extend_type' => [ 'xlsx' ]
        );

        $result = $upload_model->setPath("common/store/file/" . date("Ymd") . '/')->file($param);
        return $result;
    }

    /**
     * 上传(不存入相册)
     * @return \app\model\upload\Ambigous|\multitype
     */
    public function image()
    {
        $upload_model = new UploadModel($this->site_id);
        $thumb_type = input("thumb", "");
        $name = input("name", "");
        $cloud = input("cloud", 1);
        $param = array (
            "thumb_type" => "",
            "name" => "file",
            "cloud" => $cloud
        );
        $path = $this->site_id > 0 ? "common/images/" . date("Ymd") . '/' : "common/images/" . date("Ymd") . '/';
        $result = $upload_model->setPath($path)->image($param);
        return $result;
    }
}