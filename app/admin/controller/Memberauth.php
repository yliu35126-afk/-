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

use app\model\member\MemberAuth as MemberAuthModel;

/**
 * 实名认证列表
 */
class Memberauth extends BaseAdmin
{
    /**
     * 实名认证列表
     */
    public function lists()
    {
        if(request()->isAjax()){
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $search_text = input('search_text', '');

            $condition = [];
            $condition[] = ['member_username|auth_card_name', 'like', "%". $search_text ."%"];
           
            $field = '*';

            $member_auth_model = new MemberAuthModel();
            $lists = $member_auth_model->getMemberAuthPageList($condition, $page, $page_size,'', $field);
            return $lists;
        }else{
            return $this->fetch('memberauth/lists');
        }
    }
    /**
     * 申请详情
     */
    public function detail()
    {
        $auth_id = input('auth_id', 0);
        
        $member_auth_model = new MemberAuthModel();
        $detail = $member_auth_model->getMemberAuthInfo([ [ 'auth_id', '=', $auth_id ] ]);
        $auth_status_arr = $member_auth_model->getAuthStatus();

        $detail['data']['status_name'] = $auth_status_arr[ $detail['data']['status']];
        $this->assign('info', $detail['data']);
        
        return $this->fetch('memberauth/detail');
    }
    /**
     * 实名审核通过
     */
    public function pass()
    {
        $auth_id = input('auth_id', 0);
        $this->addLog("实名审核通过id:".$auth_id);
        $member_auth_model = new MemberAuthModel();
        return $member_auth_model->authPass($auth_id);
    }
    /**
     * 实名审核拒绝
     */
    public function reject()
    {
        $auth_id = input('auth_id', 0);
        $reason = input('reason', '');
        $this->addLog("实名审核拒绝id:".$auth_id);
        $member_auth_model = new MemberAuthModel();
        return $member_auth_model->authReject($auth_id, $reason);
    }
    
}