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

namespace app\admin\controller;

use app\model\upload\Album as AlbumModel;
use app\model\upload\Upload as UploadModel;

/**
 * 相册
 * Class Order
 * @package app\shop\controller
 */
class Album extends BaseAdmin
{

    public function __construct()
    {
        //执行父类构造函数
        parent::__construct();
    }

    /**
     * 图像
     */
    public function lists()
    {
        $album_model = new AlbumModel();
        if (request()->isAjax()) {
            $page = input('page', 1);
            $limit = input('limit', PAGE_LIST_ROWS);
            $album_id = input('album_id', '');
            $pic_name = input("pic_name", "");
            $order = input("order", "update_time desc");
            $condition = array (
                [ 'site_id', "=", $this->site_id ],
                [ 'album_id', "=", $album_id ],
            );
            if (!empty($pic_name)) {
                $condition[] = [ 'pic_name', 'like', '%' . $pic_name . '%' ];
            }

            $list = $album_model->getAlbumPicPageList($condition, $page, $limit, $order);
            return $list;
        } else {
            $album_model->getModuleAlbum($this->site_id,$this->app_module);
            $album_list = $album_model->getAlbumList([ [ 'site_id', "=", $this->site_id ], [ 'app_module', '=', $this->app_module ] ]);
            $this->assign("album_list", $album_list[ 'data' ]);
            return $this->fetch('album/lists');
        }
    }

    /**
     * 获取相册分组
     */
    function getAlbumList()
    {
        if (request()->isAjax()) {
            $album_model = new AlbumModel();
            $album_list = $album_model->getAlbumList([ [ 'site_id', "=", $this->site_id ], [ 'app_module', '=', $this->app_module ] ]);
            return $album_list;
        }

    }

    /**
     * 添加分组
     */
    public function addAlbum()
    {
        if (request()->isAjax()) {
            $album_name = input('album_name', '');
            $data = array (
                'site_id' => $this->site_id,
                'album_name' => $album_name,
                'app_module' => $this->app_module
            );
            $album_model = new AlbumModel();
            $res = $album_model->addAlbum($data);
            return $res;
        }
    }

    /**
     * 修改分组
     */
    public function editAlbum()
    {
        if (request()->isAjax()) {
            $album_name = input('album_name');
            $album_id = input('album_id');
            $data = array (
                'album_name' => $album_name
            );
            $condition = array (
                [ 'site_id', "=", $this->site_id ],
                [ 'album_id', "=", $album_id ],
                [ 'app_module', '=', $this->app_module ]
            );
            $album_model = new AlbumModel();
            $res = $album_model->editAlbum($data, $condition);
            return $res;
        }
    }

    /**
     * 删除分组
     */
    public function deleteAlbum()
    {
        if (request()->isAjax()) {
            $album_id = input('album_id');
            $album_model = new AlbumModel();
            $condition = array (
                [ "album_id", "=", $album_id ],
                [ "site_id", "=", $this->site_id ]
            );
            $res = $album_model->deleteAlbum($condition);
            return $res;
        }
    }

    /**
     * 修改文件名
     */
    public function modifyPicName()
    {
        if (request()->isAjax()) {
            $pic_id = input('pic_id', 0);
            $pic_name = input('pic_name', '');
            $album_id = input('album_id', 0);

            $album_model = new AlbumModel();
            $condition = array (
                [ "pic_id", "=", $pic_id ],
                [ "site_id", "=", $this->site_id ],
                [ 'album_id', '=', $album_id ],
            );
            $data = array (
                "pic_name" => $pic_name
            );
            $res = $album_model->editAlbumPic($data, $condition);
            return $res;
        }
    }

    /**
     * 修改图片分组
     */
    public function modifyFileAlbum()
    {
        if (request()->isAjax()) {
            $pic_id = input('pic_id', 0);//图片id
            $album_id = input('album_id', 0);//相册id
            $album_model = new AlbumModel();
            $condition = array (
                [ "pic_id", "in", $pic_id ],
                [ "site_id", "=", $this->site_id ],
            );
            $res = $album_model->modifyAlbumPicAlbum($album_id, $condition);
            return $res;
        }
    }

    /**
     * 删除图片
     */
    public function deleteFile()
    {
        if (request()->isAjax()) {
            $pic_id = input('pic_id', 0);//图片id
            $album_id = input('album_id', 0);
            $album_model = new AlbumModel();
            $condition = array (
                [ "pic_id", "in", $pic_id ],
                [ "site_id", "=", $this->site_id ],
                [ 'album_id', '=', $album_id ]
            );
            $res = $album_model->deleteAlbumPic($condition);
            return $res;
        }
    }

    /**
     * 相册管理界面
     * @return mixed
     */
    public function album()
    {
        $album_model = new AlbumModel();
        if (request()->isAjax()) {
            $page_index = input('page', 1);
            $list_rows = input('limit', PAGE_LIST_ROWS);
            $album_id = input('album_id', '');
            $pic_name = input("pic_name", "");
            $condition = array (
                [ 'site_id', "=", $this->site_id ],
                [ 'album_id', "=", $album_id ],
            );
            if (!empty($pic_name)) {
                $condition[] = [ 'pic_name', 'like', '%' . $pic_name . '%' ];
            }
            $list = $album_model->getAlbumPicPageList($condition, $page_index, $list_rows, 'update_time desc');
            return $list;
        } else {
            $album_model->getModuleAlbum($this->site_id,$this->app_module);
            $album_list = $album_model->getAlbumList([ [ 'site_id', "=", $this->site_id ], [ 'app_module', '=', $this->app_module ] ]);
            $this->assign("album_list", $album_list[ 'data' ]);
            return $this->fetch('album/album');

        }

    }

    /*
     * 替换图片文件
     * */
    public function modifyFile()
    {

//      实例化响应数据结构生成类

        try {
//            参数
            $album_id = input("album_id", '');
            $pic_id = input("pic_id", '');

//            获取图片信息
            $album_model = new AlbumModel();
            $get_pic_info = array (
                [ "pic_id", "=", $pic_id ],
                [ "site_id", "=", $this->site_id ],
            );

//            图片信息
            $pic_info = $album_model->getAlbumPicInfo($get_pic_info);
//            判断是否找到有效图片
            if (empty($pic_info) || empty($pic_info[ 'data' ])) {
                return $album_model->error('', 'FAIL');
            }

//            文件名及后缀
            $file_full_name = basename($pic_info[ 'data' ][ 'pic_path' ]);
            $filename_arr = explode('.', $file_full_name);
            $filename = $filename_arr[ 0 ];
            $suffix = $filename_arr[ 1 ];

//            实例化文件上传类
            $upload_model = new UploadModel($this->site_id);

            $upload_param = array (
                "name" => "file",
                "album_id" => $album_id,
                "pic_id" => $pic_id,
                "thumb_type" => [ "big", "mid", "small" ],
                "filename" => $filename,
                "suffix" => $suffix
            );


            $result = $upload_model->setPath("common/images/" . date("Ymd") . '/')->modifyFile($upload_param);

            return $result;

        } catch (\Exception $e) {
            return $album_model->error($e, 'FAIL');
        }

    }
}