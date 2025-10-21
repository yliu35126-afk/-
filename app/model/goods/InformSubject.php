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

namespace app\model\goods;

use app\model\BaseModel;
use think\facade\Cache;

/**
 * 举报主题
 */
class InformSubject extends BaseModel
{
    /**
     * 添加举报主题
     * @param array $data
     */
    public function addSubject($data)
    {
        $type_id = model('inform_subject')->add($data);
        Cache::tag("inform_subject")->clear();
        return $this->success($type_id);
    }

    /**
     * 修改举报主题
     * @param array $data
     * @return multitype:string
     */
    public function editSubject($data)
    {
        $res = model('inform_subject')->update($data, [ [ 'subject_id', '=', $data[ 'subject_id' ] ] ]);
        Cache::tag("inform_subject")->clear();
        return $this->success($res);
    }

    /**
     * 删除举报主题
     * @param array $condition
     */
    public function deleteSubject($condition)
    {
        $res = model('inform_subject')->delete($condition);
        Cache::tag("inform_subject")->clear();
        return $this->success($res);
    }

    /**
     * 获取举报主题信息
     * @param array $condition
     * @param string $field
     */
    public function getSubjectInfo($condition, $field = '*')
    {
        $data = json_encode([ $condition, $field ]);
        $cache = Cache::get("inform_subject_getSubjectInfo_" . $data);
        if (!empty($cache)) {
            return $this->success($cache);
        }
        $res = model('inform_subject')->getInfo($condition, $field);
        Cache::tag("inform_subject")->set("inform_subject_getSubjectInfo_" . $data, $res);
        return $this->success($res);
    }

    /**
     * 获取举报主题列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param string $limit
     */
    public function getSubjectList($condition = [], $field = '*', $order = '', $limit = null)
    {
        $data = json_encode([ $condition, $field, $order, $limit ]);
        $cache = Cache::get("inform_subject_getSubjectList_" . $data);
        if (!empty($cache)) {
            return $this->success($cache);
        }
        $list = model('inform_subject')->getList($condition, $field, $order, '', '', '', $limit);
        Cache::tag("inform_subject")->set("inform_subject_getSubjectList_" . $data, $list);

        return $this->success($list);
    }

    /**
     * 获取举报主题分页列表
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getSubjectPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*')
    {
        $data = json_encode([ $condition, $field, $order, $page, $page_size ]);
        $cache = Cache::get("inform_subject_getSubjectPageList_" . $data);
        if (!empty($cache)) {
            return $this->success($cache);
        }
        $list = model('inform_subject')->pageList($condition, $field, $order, $page, $page_size);
        Cache::tag("inform_subject")->set("inform_subject_getSubjectPageList_" . $data, $list);
        return $this->success($list);
    }
}