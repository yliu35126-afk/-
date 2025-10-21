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

use think\facade\Cache;
use app\model\BaseModel;

/**
 * 举报主题类型
 */
class InformSubjectType extends BaseModel
{
    /**
     * 添加举报类型
     * @param array $data
     */
    public function addSubjectType($data)
    {
        $type_id = model('inform_subject_type')->add($data);
        Cache::tag("inform_subject_type")->clear();
        return $this->success($type_id);
    }
    
    /**
     * 修改举报类型
     * @param array $data
     * @return multitype:string
     */
    public function editSubjectType($data)
    {
        $res = model('inform_subject_type')->update($data, [ [ 'type_id', '=', $data['type_id'] ] ]);
        Cache::tag("inform_subject_type")->clear();
        return $this->success($res);
    }
    
    /**
     * 删除举报类型
     * @param array $condition
     */
    public function deleteSubjectType($condition)
    {
        $res = model('inform_subject_type')->delete($condition);
        Cache::tag("inform_subject_type")->clear();
        return $this->success($res);
    }
    
    /**
     * 修改排序
     * @param int $sort
     * @param int $type_id
     */
    public function modifySubjectTypeSort($sort, $type_id)
    {
        $res = model('inform_subject_type')->update([ 'sort' => $sort ], [ [ 'SubjectType_id', '=', $type_id ] ]);
        Cache::tag("inform_subject_type")->clear();
        return $this->success($res);
    }
    
    /**
     * 获取举报类型信息
     * @param array $condition
     * @param string $field
     */
    public function getSubjectTypeInfo($condition, $field = '*')
    {
        $data = json_encode([ $condition, $field ]);
        $cache = Cache::get("inform_subject_type_getSubjectTypeInfo_" . $data);
        if (!empty($cache)) {
            return $this->success($cache);
        }
        $res = model('inform_subject_type')->getInfo($condition, $field);
        Cache::tag("inform_subject_type")->set("inform_subject_type_getSubjectTypeInfo_" . $data, $res);
        return $this->success($res);
    }
    
    /**
     * 获取举报类型列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param string $limit
     */
    public function getSubjectTypeList($condition = [], $field = '*', $order = '', $limit = null)
    {
        $data = json_encode([ $condition, $field, $order, $limit ]);
        $cache = Cache::get("inform_subject_type_getSubjectTypeList_" . $data);
        if (!empty($cache)) {
            return $this->success($cache);
        }
        $list = model('inform_subject_type')->getList($condition, $field, $order, '', '', '', $limit);
        Cache::tag("inform_subject_type")->set("inform_subject_type_getSubjectTypeList_" . $data, $list);
    
        return $this->success($list);
    }
    
    /**
     * 获取举报类型分页列表
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getSubjectTypePageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*')
    {
        $data = json_encode([ $condition, $field, $order, $page, $page_size ]);
        $cache = Cache::get("inform_subject_type_getSubjectTypePageList_" . $data);
        if (!empty($cache)) {
            return $this->success($cache);
        }
        $list = model('inform_subject_type')->pageList($condition, $field, $order, $page, $page_size);
        Cache::tag("inform_subject_type")->set("inform_subject_type_getSubjectTypePageList_" . $data, $list);
        return $this->success($list);
    }
}