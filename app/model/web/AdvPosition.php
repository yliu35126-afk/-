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

namespace app\model\web;


use think\facade\Cache;
use app\model\BaseModel;

/**
 * 广告位管理
 * @author Administrator
 *
 */
class AdvPosition extends BaseModel
{
    /**
     * 添加广告位
     * @param array $data
     */
    public function addAdvPosition($data)
    {
        //判断关键词是否已存在
        $count = model('adv_position')->getCount([ [ 'keyword', '=', $data[ 'keyword' ] ] ]);
        if ($count > 0) {
            return $this->error('', '该关键词已存在');
        }
        $ap_id = model('adv_position')->add($data);
        Cache::tag("adv_position")->clear();
        return $this->success($ap_id);
    }

    /**
     * 修改广告位
     * @param array $data
     */
    public function editAdvPosition($data, $condition)
    {
        //判断关键词是否已存在
        if (isset($data[ 'keyword' ])) {
            $count = model('adv_position')->getCount([ [ 'keyword', '=', $data[ 'keyword' ] ], [ 'ap_id', '<>', $data[ 'ap_id' ] ] ]);
            if ($count > 0) {
                return $this->error('', '该关键词已存在');
            }
        }

        $res = model('adv_position')->update($data, $condition);
        Cache::tag("adv_position")->clear();
        return $this->success($res);
    }

    /**
     * 删除广告位
     * @param unknown $condition
     */
    public function deleteAdvPosition($condition)
    {
        $adv_position_list = model('adv_position')->getList($condition, 'ap_id,is_system');
        model('adv_position')->startTrans();
        try {

            if (!empty($adv_position_list)) {
                $adv_model = new Adv();
                foreach ($adv_position_list as $v) {
                    if ($v[ 'is_system' ] == 0) {
                        //删除广告位
                        model('adv_position')->delete([ [ 'ap_id', '=', $v[ 'ap_id' ] ] ]);
                        //删除广告
                        $adv_model->deleteAdv([ [ 'ap_id', '=', $v[ 'ap_id' ] ] ]);
                    }
                }
            }

            Cache::tag("adv_position")->clear();
            model('adv_position')->commit();
            return $this->success();
        } catch (\Exception $e) {

            model('adv_position')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 获取广告位基础信息
     * @param $condition
     * @param string $file
     * @return array
     */
    public function getAdvPositionInfo($condition, $file = 'ap_id, keyword , ap_name, ap_intro, ap_height, ap_width, default_content, ap_background_color, type, is_system')
    {
        $data = json_encode([ $condition ]);
        $cache = Cache::get("adv_position_getAdvPositionInfo_" . $data);
        if (!empty($cache)) {
            return $this->success($cache);
        }
        $res = model('adv_position')->getInfo($condition, $file);
        Cache::tag("adv_position")->set("adv_position_getAdvPositionInfo_" . $data, $res);
        return $this->success($res);
    }

    /**
     * 获取广告位列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param string $limit
     */
    public function getAdvPositionList($condition = [], $field = 'ap_id, keyword , ap_name, ap_intro, ap_height, ap_width, default_content, ap_background_color, type', $order = '', $limit = null)
    {

        $data = json_encode([ $condition, $field, $order, $limit ]);
        $cache = Cache::get("adv_position_getAdvPositionList_" . $data);
        if (!empty($cache)) {
            return $this->success($cache);
        }
        $list = model('adv_position')->getList($condition, $field, $order, '', '', '', $limit);
        Cache::tag("adv_position")->set("adv_position_getAdvPositionList_" . $data, $list);

        return $this->success($list);
    }

    /**
     * 获取广告位分页列表
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getAdvPositionPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'ap_id desc', $field = 'ap_id, keyword , ap_name, ap_intro, ap_height, ap_width, default_content, ap_background_color, type,is_system')
    {
        $data = json_encode([ $condition, $field, $order, $page, $page_size ]);
        $cache = Cache::get("adv_position_getAdvPositionPageList_" . $data);
        if (!empty($cache)) {
            return $this->success($cache);
        }
        $list = model('adv_position')->pageList($condition, $field, $order, $page, $page_size);
        Cache::tag("adv_position")->set("adv_position_getAdvPositionPageList_" . $data, $list);
        return $this->success($list);
    }

}
