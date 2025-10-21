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

namespace app\model\upload;

use app\model\BaseModel;
use extend\Upload as UploadExtend;
use Intervention\Image\ImageManagerStatic as Image;

class Upload extends BaseModel
{

    public $upload_path = __UPLOAD__;//公共上传文件
    public $config = []; //上传配置
    public $site_id;
    public $rule_type;//允许上传 mime类型
    public $rule_ext;// 允许上传 文件后缀
    public $path;//上传路径
    public $is_water = false;//是否参与水印
    public $is_cloud = true;//是否云上传

    public function __construct($site_id = 0)
    {
        $this->site_id = $site_id;
        $config_model = new Config();
        $config_result = $config_model->getUploadConfig();
        $this->config = $config_result[ "data" ][ "value" ];//上传配置
    }
    /************************************************************上传开始*********************************************/

    /**
     * 单图上传
     * @param number $site_id
     * @param string $thumb_type 生成缩略图类型
     */
    public function image($param)
    {
        $check_res = $this->checkImg();
        if ($check_res[ "code" ] >= 0) {
            $file = request()->file($param[ "name" ]);
            if (empty($file))
                return $this->error();

            $tmp_name = $file->getPathname();//获取上传缓存文件
            $original_name = $file->getOriginalName();//文件原名
//            $file_path = $this->upload_path."/".$this->site_id . "/images/".date("Ymd"). '/';
            $file_path = $this->path;
            // 检测目录
            $checkpath_result = $this->checkPath($file_path);//验证写入文件的权限
            if ($checkpath_result[ "code" ] < 0)
                return $checkpath_result;


            $file_name = $file_path . $this->createNewFileName();
            $extend_name = $file->getOriginalExtension();

            $thumb_type = $param[ "thumb_type" ];
            //原图保存
            $new_file = $file_name . "." . $extend_name;
            $image = Image::make($tmp_name);
            $width = $image->width();//图片宽
            $height = $image->height();//图片高

            $image = $this->imageWater($image);
            $result = $this->imageCloud($image, $new_file);//原图云上传(文档流上传)
            if ($result[ "code" ] < 0)
                return $result;

            $thumb_res = $this->thumbBatch($tmp_name, $file_name, $extend_name, $thumb_type);//生成缩略图
            if ($thumb_res[ "code" ] < 0)
                return $thumb_res;


            $data = array (
                "pic_path" => $result[ "data" ],//图片云存储
                "pic_name" => $original_name,
                "file_ext" => $extend_name,
                "pic_spec" => $width . "*" . $height,
                "update_time" => time(),
                "site_id" => $this->site_id
            );
            return $this->success($data, "UPLOAD_SUCCESS");
        } else {
            //返回错误信息
            return $check_res;
        }
    }

    /**
     * 相册图片上传
     * @param number $site_id
     * @param number $category_id
     * @param string $thumb_type
     */
    public function imageToAlbum($param)
    {
        $check_res = $this->checkImg();
        if ($check_res[ "code" ] >= 0) {
            $file = request()->file($param[ "name" ]);
            if (empty($file))
                return $this->error();

            $tmp_name = $file->getPathname();//获取上传缓存文件
            $original_name = $file->getOriginalName();//文件原名
//            $file_path = $this->upload_path."/".$this->site_id . "/images/".date("Ymd"). '/';
            $file_path = $this->path;
            // 检测目录
            $checkpath_result = $this->checkPath($file_path);//验证写入文件的权限
            if ($checkpath_result[ "code" ] < 0)
                return $checkpath_result;

            $file_name = $file_path . $this->createNewFileName();
            $extend_name = $file->getOriginalExtension();

            $thumb_type = $param[ "thumb_type" ];//所留
            $album_id = $param[ "album_id" ] ?? 0;
            //原图保存
            $new_file = $file_name . "." . $extend_name;
            $image = Image::make($tmp_name);
            $width = $image->width();//图片宽
            $height = $image->height();//图片高

            $image = $this->imageWater($image);

            $result = $this->imageCloud($image, $new_file);//原图云上传(文档流上传)
            if ($result[ "code" ] < 0)
                return $result;

            $thumb_res = $this->thumbBatch($tmp_name, $file_name, $extend_name, $thumb_type);//生成缩略图
            if ($thumb_res[ "code" ] < 0)
                return $result;

            $pic_name_first = substr(strrchr($original_name, '.'), 1);

            $pic_name = basename($original_name, "." . $pic_name_first);

            $data = array (
                "pic_path" => $result[ "data" ],//图片云存储
                "pic_name" => $pic_name,
                "pic_spec" => $width . "*" . $height,
                "update_time" => time(),
                "site_id" => $this->site_id,
                "album_id" => $album_id
            );
            $album_model = new Album();
            $res = $album_model->addAlbumPic($data);
            if ($res[ 'code' ] >= 0) {
                $data[ "id" ] = $res[ "data" ];
                return $this->success($data, "UPLOAD_SUCCESS");
            } else {
                return $this->error($res);
            }
        } else {
            //返回错误信息
            return $check_res;
        }

    }

    /*
     * 替换图片文件
     * */
    public function modifyFile($param)
    {
//        参数校验
        if (empty($param[ 'album_id' ])) {
            return $this->error('', "PARAMETER_ERROR");
        }

        if (empty($param[ 'pic_id' ])) {
            return $this->error('', "PARAMETER_ERROR");
        }

        if (empty($param[ 'filename' ])) {
            return $this->error('', "PARAMETER_ERROR");
        }

        if (empty($param[ 'suffix' ])) {
            return $this->error('', "PARAMETER_ERROR");
        }

        $check_res = $this->checkImg();

        if ($check_res[ "code" ] >= 0) {

            $file = request()->file($param[ "name" ]);
            if (empty($file))
                return $this->error();

            $tmp_name = $file->getPathname();//获取上传缓存文件
            $original_name = $file->getOriginalName();//文件原名

            $file_path = $this->path;
            // 检测目录
            $checkpath_result = $this->checkPath($file_path);//验证写入文件的权限
            if ($checkpath_result[ "code" ] < 0) {
                return $checkpath_result;
            }

//            保留原文件名和后缀
            $file_name = $file_path . $param[ 'filename' ];
            $extend_name = $param[ 'suffix' ];
            $thumb_type = $param[ "thumb_type" ];//所留
            //原图保存
            $new_file = $file_name . "." . $extend_name;
            $image = Image::make($tmp_name);
            $width = $image->width();//图片宽
            $height = $image->height();//图片高
            $image = $this->imageWater($image);

            $result = $this->imageCloud($image, $new_file);//原图云上传(文档流上传)
            if ($result[ "code" ] < 0) {
                return $result;
            }

            $thumb_res = $this->thumbBatch($tmp_name, $file_name, $extend_name, $thumb_type);//生成缩略图
            if ($thumb_res[ "code" ] < 0) {
                return $thumb_res;
            }

            $pic_name_first = substr(strrchr($original_name, '.'), 1);
            $pic_name = basename($original_name, "." . $pic_name_first);

            $data = array (
                "pic_path" => $result[ "data" ],//图片云存储
                "pic_spec" => $width . "*" . $height,
                "update_time" => time(),
            );

            $album_model = new Album();
            $condition = array (
                [ "pic_id", "=", $param[ 'pic_id' ] ],
                [ "site_id", "=", $this->site_id ],
                [ 'album_id', "=", $param[ 'album_id' ] ],
            );

            $res = $album_model->editAlbumPic($data, $condition);

            if ($res[ 'code' ] >= 0) {
                $data[ "id" ] = $res[ "data" ];
                return $this->success($data, "UPLOAD_SUCCESS");
            } else {
                return $this->error($res);
            }

        } else {
            //返回错误信息
            return $check_res;
        }

    }

    /**
     * 视频上传
     * @param $param
     */
    public function video($param)
    {
        $check_res = $this->checkFile();
        if ($check_res[ "code" ] >= 0) {
            // 获取表单上传文件
            $file = request()->file($param[ "name" ]);
            try {
                $extend_name = $file->getOriginalExtension();
                $new_name = $this->createNewFileName() . "." . $extend_name;

                $file_path = $this->path;
                \think\facade\Filesystem::disk('public')->putFileAs($file_path, $file, $new_name);
                $file_name = $file_path . $new_name;
                $result = $this->fileCloud($file_name);

                return $this->success([ "path" => $result[ 'data' ] ?? '' ], "UPLOAD_SUCCESS");
            } catch (\think\exception\ValidateException $e) {
                return $this->error('', $e->getMessage());
            }
        } else {
            return $check_res;
        }
    }

    /**
     * 上传文件
     * @param $param
     */
    public function file($param)
    {
        $check_res = $this->checkFile();
        if ($check_res[ "code" ] >= 0) {
            // 获取表单上传文件
            $file = request()->file($param[ "name" ]);
            try {
                $extend_name = $file->getOriginalExtension();
                $new_name = $this->createNewFileName() . "." . $extend_name;
                $file_path = $this->path;
                \think\facade\Filesystem::disk('public')->putFileAs($file_path, $file, $new_name);
                $file_name = $file_path . $new_name;
                $result = $this->fileCloud($file_name);
                return $this->success([ "path" => $result[ 'data' ] ?? '', 'name' => $new_name ], "UPLOAD_SUCCESS");
            } catch (\think\exception\ValidateException $e) {
                return $this->error('', $e->getMessage());
            }
        } else {
            return $check_res;
        }

    }
    /************************************************************上传结束*********************************************/
    /************************************************************上传功能组件******************************************/


    /**
     * 缩略图生成
     * @param unknown $file_name
     * @param unknown $extend_name
     * @param unknown $thumb_type
     * @return Ambigous <string, multitype:multitype:string  >
     */
    private function thumbBatch($file_path, $file_name, $extend_name, $thumb_type = [])
    {
        $thumb_type_array = array (
            "big" => array (
                "size" => "big",
                "width" => $this->config[ "thumb" ][ "thumb_big_width" ],
                "height" => $this->config[ "thumb" ][ "thumb_big_height" ],
                "thumb_name" => ""
            ),
            "mid" => array (
                "size" => "mid",
                "width" => $this->config[ "thumb" ][ "thumb_mid_width" ],
                "height" => $this->config[ "thumb" ][ "thumb_mid_height" ],
                "thumb_name" => ""
            ),
            "small" => array (
                "size" => "small",
                "width" => $this->config[ "thumb" ][ "thumb_small_width" ],
                "height" => $this->config[ "thumb" ][ "thumb_small_height" ],
                "thumb_name" => ""
            )
        );
        foreach ($thumb_type_array as $k => $v) {
            if (!empty($thumb_type) && in_array($k, $thumb_type)) {
                $new_path_name = $file_name . "_" . $v[ "size" ] . "." . $extend_name;
                $result = $this->imageThumb($file_path, $new_path_name, $v[ "width" ], $v[ "height" ]);
                //返回生成的缩略图路径
                if ($result[ "code" ] >= 0) {
                    $thumb_type_array[ $k ][ "thumb_name" ] = $new_path_name;
                } else {
                    return $result;
                }
            }
        }
        return $this->success($thumb_type_array);
    }

    /**
     * 缩略图
     * @param unknown $file_name
     * @param unknown $new_path
     * @param unknown $width
     * @param unknown $height
     * @return multitype:boolean unknown |multitype:boolean
     */
    /**
     * 缩略图
     * @param unknown $file_name
     * @param unknown $new_path
     * @param unknown $width
     * @param unknown $height
     * @return multitype:boolean unknown |multitype:boolean
     */
    public function imageThumb($file, $thumb_name, $width, $height)
    {
        $image = Image::make($file)->resize($width, $height, function($constraint) {
            $constraint->aspectRatio();
//            $constraint->upsize();
        });
        $now_width = $image->width();
        $now_height = $image->height();
        $new_height = $height - $now_height;
        $new_width = $width - $now_width;
        $image = $image->resizeCanvas($new_width, $new_height, 'center', true, 'ffffff');
//        $image = $this->imageWater($image);
        $result = $this->imageCloud($image, $thumb_name);
        return $result;
    }

    /**
     * 添加水印
     */
    public function imageWater($image)
    {
        try {
            if ($this->is_water) {
                //判断是否有水印(具体走配置)
                if ($this->config[ "water" ][ "is_watermark" ]) {
                    $watermark_x = $this->config[ "water" ][ "watermark_x" ] == '' ? 0 : $this->config[ "water" ][ "watermark_x" ];
                    $watermark_y = $this->config[ "water" ][ "watermark_y" ] == '' ? 0 : $this->config[ "water" ][ "watermark_y" ];
                    switch ( $this->config[ "water" ][ "watermark_type" ] ) {
                        case "1"://图片水印
                            if (!empty($this->config[ "water" ][ "watermark_source" ]) && is_file($this->config[ "water" ][ "watermark_source" ])) {
                                $watermark = Image::make($this->config[ "water" ][ "watermark_source" ])->opacity(empty($this->config[ "water" ][ "watermark_opacity" ])? 0 :$this->config[ "water" ][ "watermark_opacity" ])->rotate(empty($this->config[ "water" ][ "watermark_rotate" ])? 0 : $this->config[ "water" ][ "watermark_rotate" ]);;
                                $image->insert($watermark, $this->config[ "water" ][ "watermark_position" ], $watermark_x, $watermark_y);
                            }
                            break;
                        case "2"://文字水印
                            if (!empty($this->config[ "water" ][ "watermark_text" ])) {

                                $image->text($this->config[ "water" ][ "watermark_text" ], $watermark_x, $watermark_y, function($font) {
//                        $font->file($this->config["water"]["watermark_text_file"]);//设置字体文件位置
                                    $font->size($this->config[ "water" ][ "watermark_text_size" ]);//设置字号大小
                                    $font->color($this->config[ "water" ][ "watermark_text_color" ]);//设置字号颜色
                                    $font->align($this->config[ "water" ][ "watermark_text_align" ]);//设置字号水平位置
                                    $font->valign($this->config[ "water" ][ "watermark_text_valign" ]);//设置字号 垂直位置
                                    $font->angle($this->config[ "water" ][ "watermark_text_angle" ]);//设置字号倾斜角度
                                });
                            }
                            break;
                    }
                }
            }
            return $image;
        } catch (\Exception $e) {
            return $image;
        }

    }

    /**
     * 删除文件
     * @param $file_name
     */
    private function deleteFile($file_name)
    {
        $res = @unlink($file_name);
        if ($res) {
            return $this->success();
        } else {
            return $this->error();
        }

    }

    /**
     * 图片云上传中转
     * @param $image
     * @param $file
     * @return array|mixed|string
     */
    public function imageCloud($image, $file)
    {
        try {
            $image->save($file);
            $result = $this->fileCloud($file);
            $image->destroy();
            //云上传没有成功  保存到本地
            return $result;
        } catch (\Exception $e) {
            return $this->error('', $e->getMessage());
        }

    }

    /**
     * 云上传
     */
    public function fileCloud($file)
    {
        try {
            if($this->is_cloud) {
                //走 云上传
                $put_result = event("Put", ["file_path" => $file, "key" => $file], true);
                if (!empty($put_result)) {
                    $this->deleteFile($file);
                    if ($put_result["code"] >= 0) {
                        $file = $put_result["data"]["path"];
                    } else {
                        return $put_result;
                    }
                }
            }
            //云上传没有成功  保存到本地
            return $this->success($file, "UPLOAD_SUCCESS");
        } catch (\Exception $e) {
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 检测图片上传 类型  大小
     * @param $file_info
     * @return \multitype
     */
    public function checkImage1($file_info)
    {
        $upload_extend = new UploadExtend('');//实例化上传类
        $upload_extend->setFilename($file_info[ "tmp_name" ]);
        $rule_type = $this->config[ "upload" ][ "image_allow_mime" ];//规则mine类型
        $rule_ext = $this->config[ "upload" ][ "image_allow_ext" ];//规则 允许上传后缀
//        $rule = [ "type" => "image/png,image/jpeg,image/gif,image/bmp", "ext" => "gif,jpg,jpeg,bmp,png" ];//上传文件验证规则
        $rule = [ "type" => $rule_type, "ext" => $rule_ext ];//上传文件验证规则
        $old_name = $upload_extend->getFileName($file_info[ "name" ]);//文件原名
        $file_name = $this->site_id . "/images/" . date("Ymd") . "/" . $upload_extend->createNewFileName();
        $extend_name = $upload_extend->getFileExt($file_info[ "name" ]);
        $size_data = $upload_extend->getImageInfo($file_info[ "tmp_name" ]);//获取图片信息
        $check = $upload_extend->setValidate($rule)->setUploadInfo($file_info)->checkAll($this->upload_path, $file_name . "." . $extend_name);
        if (!$check)
            return $this->error("", $upload_extend->getError());

        $data = array (
            "file_name" => $file_name,
            "old_name" => $old_name,
            "extend_name" => $extend_name,
            "size_data" => $size_data,
        );
        return $this->success($data);

    }

    /**
     * 图片验证
     * @param $file
     * @return \multitype
     */
    public function checkImg()
    {
        try {
            $file = request()->file();
            $rule_array = [];
            $size_rule = $this->config[ "upload" ][ "max_filesize" ];
            $ext_rule = $this->config[ "upload" ][ "image_allow_ext" ];
            $mime_rule = $this->config[ "upload" ][ "image_allow_mime" ];

//            $size_rule = 10240;
//            $ext_rule = "jpg,jpeg,png,gif,pem";
//            $mime_rule = "image/jpeg,image/gif,image/png,text/plain";

            if (!empty($size_rule)) {
                $rule_array[] = "fileSize:{$size_rule}";
            }
            if (!empty($ext_rule)) {
                $rule_array[] = "fileExt:{$ext_rule}";
            }
            if (!empty($mime_rule)) {
                $rule_array[] = "fileMime:{$mime_rule}";
            }
            if (!empty($rule_array)) {
                //            'image'=>'filesize:10240|fileExt:jpg,jpeg,png,gif,pem|fileMime:image/jpeg,image/gif,image/png,text/plain'
                $rule = implode("|", $rule_array);
                validate([ 'file' => $rule ])->check($file);
            }
            return $this->success();
        } catch (\think\exception\ValidateException $e) {
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 文件验证
     * @param $file
     * @return \multitype
     */
    public function checkFile()
    {
        try {
            $file = request()->file();
            $rule_array = [];
            $size_rule = $this->config[ "upload" ][ "max_filesize" ];
//            $ext_rule = $this->config["upload"]["image_allow_ext"];
//            $mime_rule = $this->config["upload"]["image_allow_mime"];

//            $size_rule = 10240*100;
//            $ext_rule = "jpg,jpeg,png,gif,pem";
//            $mime_rule = "image/jpeg,image/gif,image/png,text/plain";

//            if(!empty($size_rule)){
//                $rule_array[] = "fileSize:{$size_rule}";
//            }
//            if(!empty($ext_rule)){
//                $rule_array[] = "fileExt:{$ext_rule}";
//            }
//            if(!empty($mime_rule)){
//                $rule_array[] = "fileMime:{$mime_rule}";
//            }
//            $rule = implode("|", $rule_array);
//            'image'=>'filesize:10240|fileExt:jpg,jpeg,png,gif,pem|fileMime:image/jpeg,image/gif,image/png,text/plain'
//            $res = validate(['file'=>$rule])->check($file);
//            if($res){
//                return $this->success();
//            }else{
//                return $this->error();
//            }
            return $this->success();
        } catch (\think\exception\ValidateException $e) {
            echo $e->getMessage();
        }
    }
    /************************************************************上传功能组件******************************************/


    /**
     *获取一个新文件名
     */
    public function createNewFileName()
    {
        $name = date('Ymdhis', time())
            . sprintf('%03d', microtime(true) * 1000)
            . sprintf('%02d', mt_rand(10, 99));
        return $name;
    }

    /**
     * 验证目录是否可写
     * @param unknown $path
     * @return boolean
     */
    public function checkPath($path)
    {
        if (is_dir($path) || mkdirs($path, 0755)) {
            return $this->success();
        }

        return $this->error('', "directory {$path} creation failed");
    }

    /**
     * 设置上传目录
     * @param $path
     */
    public function setPath($path)
    {
        if ($this->site_id > 0) {
            $this->path = $this->site_id . "/" . $path;
        } else {
            $this->path = $path;
        }
        $this->path = $this->upload_path . "/" . $this->path;
        return $this;
    }

    /**
     *设置是否有水印
     */
    public function setWater($is_water)
    {
        $this->is_water = $is_water;
        return $this;
    }

    /**
     * 远程拉取图片
     * @param $path
     */
    public function remotePull($path)
    {

        $file_path = $this->path;
        // 检测目录
        $checkpath_result = $this->checkPath($file_path);//验证写入文件的权限
        if ($checkpath_result[ "code" ] < 0)
            return $checkpath_result;

        $file_name = $file_path . $this->createNewFileName();
        $new_file = $file_name . ".png";


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $path);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        $file = curl_exec($ch);
        curl_close($ch);

        $image = Image::make($file);
//        $image = $this->imageWater($image);
        $result = $this->imageCloud($image, $new_file);//原图云上传(文档流上传)
        if ($result[ "code" ] < 0)
            return $result;

        return $this->success([ "pic_path" => $result[ "data" ] ]);
    }

    public function remotePullBinary($file)
    {
        $file_path = $this->path;
        // 检测目录
        $checkpath_result = $this->checkPath($file_path);//验证写入文件的权限
        if ($checkpath_result[ "code" ] < 0)
            return $checkpath_result;

        $file_name = $file_path . $this->createNewFileName();
        $new_file = $file_name . ".png";

        $image = Image::make($file);
        $result = $this->imageCloud($image, $new_file);//原图云上传(文档流上传)
        if ($result[ "code" ] < 0)
            return $result;

        return $this->success([ "pic_path" => $result[ "data" ] ]);
    }

    /**
     * 二维码生成  返回base64
     * @param $url
     * @return array
     */
    public function qrcode($url)
    {

        $file_path = qrcode($url, "weixinpay/qrcode/" . date("Ymd") . '/', date("Ymd") . 'qrcode');
        //$file：图片地址
        //Filetype: JPEG,PNG,GIF
        $file = $file_path;
        if ($fp = fopen($file, "rb", 0)) {
            $gambar = fread($fp, filesize($file_path));
            fclose($fp);
            $base64 = "data:image/jpg/png/gif;base64," . chunk_split(base64_encode($gambar));
            $this->deleteFile($file_path);
            return $this->success($base64);
        } else {
            return $this->error();
        }

    }


    /**
     * 远程拉取图片到本地
     * @param $path
     */
    public function remotePullToLocal($path)
    {

        if (stristr($path, 'http://') || stristr($path, 'https://')) {
            $file_path = $this->path;
            // 检测目录
            $checkpath_result = $this->checkPath($file_path);//验证写入文件的权限
            if ($checkpath_result[ "code" ] < 0)
                return $checkpath_result;

            $file_name = $file_path . $this->createNewFileName();
            $new_file = $file_name . ".png";


            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $path);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
            $file = curl_exec($ch);
            curl_close($ch);

            $image = Image::make($file);
            $image = $this->imageWater($image);
            $image->save($new_file);
            return $this->success([ "path" => $new_file ]);
        } else {
            return $this->success([ "path" => $path ]);
        }
    }


    /**
     * 实例化组件图片
     */
    public function imageMake($img)
    {
        $image = Image::make($img);
        return $image;
    }

    /**
     * 远程拉取商品图片
     */
    public function remoteGoodsPullToLocal($param)
    {
        $remote_result = $this->remotePullToLocal($param[ 'img' ]);
        if ($remote_result[ 'code' ] < 0) {
            return $remote_result;
        }
        $img_path = $remote_result[ 'data' ][ 'path' ];//原图本地化的图片路径
        $file_path = $this->path;
        $file_name = $file_path . $this->createNewFileName();//生成新的完整文件路径
        $img_array = explode('.', $param[ 'img' ]);
        $extend_name = end($img_array);//获取文件的后缀名
        $thumb_type = $param[ "thumb_type" ];
        //原图保存
        $new_file = $file_name . "." . $extend_name;
        $image = Image::make($img_path);
        $width = $image->width();//图片宽
        $height = $image->height();//图片高

        $image = $this->imageWater($image);

        $result = $this->imageCloud($image, $new_file);//原图云上传(文档流上传)
        if ($result[ "code" ] < 0)
            return $result;

        $thumb_res = $this->thumbBatch($img_path, $file_name, $extend_name, $thumb_type);//生成缩略图
        if ($thumb_res[ "code" ] < 0)
            return $result;

        $data = array (
            "pic_path" => $result[ "data" ],//图片云存储
            "file_ext" => $extend_name,
            "pic_spec" => $width . "*" . $height,
            "update_time" => time(),
            "site_id" => $this->site_id
        );
        return $this->success($data, "UPLOAD_SUCCESS");
    }

    /**
     * 设置是否上传
     * @param $is_cloud
     */
    public function setCloud($is_cloud){
        $this->is_cloud = $is_cloud;
        return $this;
    }
}