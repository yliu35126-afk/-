<?php
/**
 * Index.php
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2015-2025 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用。
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * =========================================================
 * @author : niuteam
 * @date : 2015.1.17
 * @version : v1.0.0.0
 */

namespace addon\notes\api\controller;

use addon\notes\model\Group;
use app\api\controller\BaseApi;
use addon\notes\model\Notes as NotesModel;
use addon\notes\model\Record as RecordModel;

class Notes extends BaseApi
{

    /**
     *  获取笔记分组
     */
    public function group()
    {
        $model = new Group();
        $list = $model->getNotesGroupList([], 'group_id,group_name,notes_num,release_num', 'sort asc');
        return $this->response($list);
    }

    /**
     * 获取文章列表
     */
    public function lists()
    {
        $token = $this->checkToken();
        $page = isset($this->params[ 'page' ]) ? $this->params[ 'page' ] : 1;
        $page_size = isset($this->params[ 'page_size' ]) ? $this->params[ 'page_size' ] : PAGE_LIST_ROWS;
        $group_id = isset($this->params[ 'group_id' ]) ? $this->params[ 'group_id' ] : '';
        $goods_id_arr = isset($this->params[ 'goods_id_arr' ]) ? $this->params[ 'goods_id_arr' ] : '';
        $site_id = $this->params[ 'site_id' ] ?? 0;
        $condition = [
            ['pn.status','=',1]
        ];
        if ($group_id) {
            $condition[] = [ 'pn.group_id', '=', $group_id ];
        }

        if (!empty($goods_id_arr)) {
            $condition[] = [ 'pn.note_id', 'in', $goods_id_arr ];
        }
        //站点笔记和平台公用接口
        if ($site_id > 0) {
            $condition[] = [ 'pn.site_id', '=', $site_id ];
        }
        $note_model = new NotesModel();
        $list_result = $note_model->getNotesPageList($condition, $page, $page_size);
        if ($token[ 'code' ] >= 0) {
            $list = $list_result[ 'data' ][ 'list' ];

            $record_model = new RecordModel();
            foreach ($list as $k => $v) {
                //获取用户是否点赞
                $is_dianzan = $record_model->getIsDianzan($v[ 'note_id' ], $this->member_id);
                $list[ $k ][ 'is_dianzan' ] = $is_dianzan[ 'data' ];
            }
            $list_result[ 'data' ][ 'list' ] = $list;
        }
        return $this->response($list_result);
    }

    /**
     * 文章详情
     */
    public function detail()
    {
        $token = $this->checkToken();

        $note_id = isset($this->params[ 'note_id' ]) ? $this->params[ 'note_id' ] : '';
        if (empty($note_id)) {
            return $this->response($this->error('', 'REQUEST_NOTE_ID'));
        }
        $condition = [
            [ 'note_id', '=', $note_id ]
        ];

        $note_model = new NotesModel();
        $info_result = $note_model->getNotesDetailInfo($condition, '*', 2);
        if ($token[ 'code' ] >= 0) {
            $info = $info_result[ 'data' ];
            $record_model = new RecordModel();
            $is_dianzan = $record_model->getIsDianzan($info[ 'note_id' ], $this->member_id);
            $info[ 'is_dianzan' ] = $is_dianzan[ 'data' ];
            $info_result[ 'data' ] = $info;
        }
        return $this->response($info_result);
    }


}