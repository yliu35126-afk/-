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

use app\model\system\User;
use app\model\shop\ShopApply;
/**
 * 店铺用户控制器
 * @author Administrator
 *
 */
class Shopuser extends BaseAdmin
{
    /**
     * 用户列表
     */
    public function lists(){
        if (request()->isAjax()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $condition = [];
            $condition[] = ["nu.app_module", "=", 'shop'];
            $status = input('status', 'all');
            $search_keys = input('search_keys', "");
            $site_id = input("site_id", "");
            if (!empty($search_keys)) {
                $condition[] = ['nu.username', 'like', '%' . $search_keys . '%' ];
            }
            if($status != "all" && $status != ""){
                $condition[] = ["nu.status", "=", $status];
            }
            if($site_id != "")
            {
                $condition[] = ["nu.site_id", "=", $site_id];
            }
            $user_model = new User();
            $list = $user_model->getSiteUserPageList($condition, $page, $page_size, "nu.create_time desc");
            return $list;
        }else{
            return $this->fetch('shopuser/lists');
        }
    }
    /**
     * 重置密码
     */
    public function modifyPassword()
    {
        if(request()->isAjax()){
            $password = input('password', '123456');

            $username = input('username','');
            $site_id = input('site_id','');

            $user_model = new User();
            return $user_model->modifyUserPassword($password, [['username', '=', $username],['site_id','=',$site_id]]);
        }
    }
    
    /**
     * 删除用户
     */
    public function deleteUser(){
        if (request()->isAjax()) {
            $uid = input("uid", 0);
            //查询是否有正在申请中的店铺
            $shop_apply_model = new ShopApply();
            $shop_apply_info = $shop_apply_model->getApplyInfo(['uid' => $uid]);
            if (!empty($shop_apply_info['data'])) {
                return error(0, '该用户正在申请店铺中，不可删除');exit;
            }
            $user_model = new User();
            $condition = array(
                ["uid", "=",$uid],
                ["app_module", "=",'shop'],
            );
            $result = $user_model->deleteUser($condition);
            return $result;
        }
    }
    
}