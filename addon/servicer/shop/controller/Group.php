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

namespace addon\servicer\shop\controller;

use app\shop\controller\BaseShop;
use app\model\system\Group as GroupModel;

/**
 * 客服分组
 */
class Group extends BaseShop
{
    /**
     * 客服分组
     */
    public function index()
    {
        if (request()->isAjax()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $search_keys = input('search_keys', "");

            $condition = array (
                [ 'site_id', "=", $this->site_id ],
                [ "app_module", "=", 'servicer' ]
            );
            if (!empty($search_keys)) {
                $condition[] = [ 'desc', 'like', '%' . $search_keys . '%' ];
            }

            $group_model = new GroupModel();
            $list = $group_model->getGroupPageList($condition, $page, $page_size);
            return $list;
        } else {
            return $this->fetch("group/list");
        }
    }

    /**
     *添加分组
     */
    public function add()
    {
        if (request()->isAjax()) {
            $group_name = input('group_name', '');
            $desc = input('desc', '');
            $group_model = new GroupModel();
            $data = array (
                "group_name" => $group_name,
                "site_id" => $this->site_id,
                "app_module" => 'servicer',
                "group_status" => 1,
                "menu_array" => '',
                "desc" => $desc,
                "is_system" => 0
            );
            $result = $group_model->addGroup($data);
            return $result;
        } else {
            return $this->fetch('group/add');
        }
    }

    /**
     * 编辑分组
     */
    public function edit()
    {
        $group_model = new GroupModel();
        if (request()->isAjax()) {
            $group_name = input('group_name', '');
            $group_id = input('group_id', 0);
            $desc = input('desc', '');

            $data = array (
                "group_name" => $group_name,
                "desc" => $desc,
            );
            $condition = array (
                [ "group_id", "=", $group_id ],
                [ "site_id", "=", $this->site_id ],
                [ "app_module", "=", 'servicer' ]
            );
            $result = $group_model->editGroup($data, $condition);
            return $result;
        } else {
            $group_id = input('group_id', 0);
            $condition = array (
                [ "group_id", "=", $group_id ],
                [ "site_id", "=", $this->site_id ],
                [ "app_module", "=", 'servicer' ]
            );
            $group_info_result = $group_model->getGroupInfo($condition);
            $group_info = $group_info_result[ "data" ];
            $this->assign("group_info", $group_info);
            $this->assign("group_id", $group_id);

            return $this->fetch('group/edit');
        }
    }

    /**
     * 删除分组
     */
    public function delete()
    {
        if (request()->isAjax()) {
            $group_id = input('group_id', '');
            $condition = array (
                [ "group_id", "=", $group_id ],
                [ "site_id", "=", $this->site_id ],
                [ "app_module", "=", 'servicer' ],
            );
            $group_model = new GroupModel();
            $result = $group_model->deleteGroup($condition);
            return $result;
        }
    }
}
