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

use app\model\web\Notice as NoticeModel;

/**
 * 网站公告
 */
class Notice extends BaseSupply
{

    /**
     * 公告列表
     */
    public function lists()
    {
        if(request()->isAjax()){
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);

            $condition[] = ['receiving_type', 'like', '%supply%'];

            $notice_model = new NoticeModel();
            $list = $notice_model->getNoticePageList($condition, $page, $page_size,'is_top desc,create_time desc');
            return $list;
        }else{

            $this->assign("menu_info", [ 'title' => "网站公告" ]);
            return $this->fetch('notice/lists');
        }
    }


    /**
     * 公告详情
     */
    public function detail()
    {
        $id = input('id', 1);
        $notice_model = new NoticeModel();
        $info = $notice_model->getNoticeInfo([['id','=',$id]]);

        $this->assign("info", $info['data']);
        $this->assign("menu_info", [ 'title' => "网站公告" ]);
        return $this->fetch('notice/detail');
    }

}