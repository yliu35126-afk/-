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

use app\model\upload\Album as AlbumModel;

/**
 * 相册
 * Class Album
 * @package app\shopapi\controller
 */
class Album extends BaseApi
{

    public function __construct()
    {
        //执行父类构造函数
        parent::__construct();
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) {
            echo $this->response($token);
            exit;
        }
    }

    /**
     * 获取相册分组
     * @return false|string
     */
    public function lists()
    {
        $album_model = new AlbumModel();
        $album_list = $album_model->getAlbumList([ [ 'site_id', "=", $this->site_id ] ],'album_id, album_name, num');
        return $this->response($album_list);
    }

    /**
     * 获取图片列表
     * @return false|string
     */
    public function picList()
    {
        $page = isset($this->params[ 'page' ]) ? $this->params[ 'page' ] : 1;
        $limit = isset($this->params[ 'page_size' ]) ? $this->params[ 'page_size' ] : PAGE_LIST_ROWS;
        $album_id = isset($this->params[ 'album_id' ]) ? $this->params[ 'album_id' ] : 0;

        $album_model = new AlbumModel();
        $condition = array (
            [ 'site_id', "=", $this->site_id ],
            [ 'album_id', "=", $album_id ],
        );
        if (!empty($pic_name)) {
            $condition[] = [ 'pic_name', 'like', '%' . $pic_name . '%' ];
        }
        $list = $album_model->getAlbumPicPageList($condition, $page, $limit, 'update_time desc','pic_path');
        return $this->response($list);
    }
}