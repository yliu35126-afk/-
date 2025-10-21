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

namespace addon\fenxiao\api\controller;

use addon\fenxiao\model\FenxiaoAccount;
use addon\fenxiao\model\Fenxiao as FenxiaoModel;
use app\api\controller\BaseApi;


/**
 * 分销商流水
 */
class Account extends BaseApi
{

    /**
     * 分销商流水分页
     * @return false|string
     */
    public function page()
    {

        $token = $this->checkToken();
        if ($token['code'] < 0) return $this->response($token);

        $page = isset($this->params['page']) ? $this->params['page'] : 1;
        $page_size = isset($this->params['page_size']) ? $this->params['page_size'] : PAGE_LIST_ROWS;

        $model = new FenxiaoModel();
        $fenxiao_info = $model->getFenxiaoInfo([['member_id', '=', $this->member_id]], 'fenxiao_id');
        $fenxiao_info = $fenxiao_info['data'];
        if (!empty($fenxiao_info['fenxiao_id'])) {
            $condition = [
                ['fenxiao_id', '=', $fenxiao_info['fenxiao_id']]
            ];

            $account_model = new FenxiaoAccount();
            $list = $account_model->getFenxiaoAccountPageList($condition, $page, $page_size);
            return $this->response($list);
        }
        return $this->response($this->error('', 'FENXIAO_NOT_EXIST'));
    }

}