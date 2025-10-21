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

namespace addon\servicer\admin\controller;

use app\admin\controller\BaseAdmin;
use addon\servicer\model\Keyword as KeywordModel;

/**
 * 关键词回复
 */
class Keyword extends BaseAdmin
{
    /**
     * 列表
     */
    public function index()
    {
        if (request()->isAjax()) {
            $page        = input('page', 1);
            $page_size   = input('page_size', PAGE_LIST_ROWS);
            $search_text = input('search_text', '');

            $condition = [['site_id', '=', 0]];
            if (!empty($search_text)) {
                $condition[] = ['keyword', 'like', '%' . $search_text . '%'];
            }

            $keyword_model = new KeywordModel();
            $res           = $keyword_model->getPageList($condition, $page, $page_size);
            return $res;
        } else {
            return $this->fetch('keyword/list');
        }
    }

    /**
     * 添加
     */
    public function add()
    {
        if (request()->isAjax()) {
            $keyword_model = new KeywordModel();
            return $keyword_model->add([
                'site_id'      => 0,
                'keyword'      => input('keyword', ''),
                'content_type' => KeywordModel::CONTENT_TYPE_TEXT,
                'content'      => input('content', ''),
                'is_use'       => input('is_use', 0),
            ]);
        }
    }

    /**
     * 编辑
     */
    public function edit()
    {
        $keyword_model = new KeywordModel();
        if (request()->isAjax()) {
            return $keyword_model->edit([
                'keyword'      => input('keyword', ''),
                'content_type' => KeywordModel::CONTENT_TYPE_TEXT,
                'content'      => input('content', ''),
                'is_use'       => input('is_use', 0),
            ], [['site_id', '=', 0], ['id', '=', input('id', 0)]]);
        }
    }

    /**
     * 删除
     */
    public function delete()
    {
        if (request()->isAjax()) {
            $keyword_model = new KeywordModel();
            return $keyword_model->delete([
                ['site_id', '=', 0],
                ['id', 'in', (string)input('ids', '')]
            ]);
        }
    }

    /**
     * 使用状态变更
     */
    public function changeUse()
    {
        if (request()->isAjax()) {
            $keyword_model = new KeywordModel();
            return $keyword_model->update(['is_use' => input('is_use', 0)], [
                ['site_id', '=', 0],
                ['id', 'in', (string)input('ids', '')]
            ]);
        }
    }
}