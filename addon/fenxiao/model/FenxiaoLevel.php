<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\fenxiao\model;

use app\model\BaseModel;


/**
 * 分销
 */
class FenxiaoLevel extends BaseModel
{

    /**
     * 添加分销等级
     * @param $data
     * @return array
     */
    public function addLevel($data)
    {
        $data[ 'create_time' ] = time();
        $data[ 'status' ] = 1;

        //判断是否重复
        $count = model('fenxiao_level')->getCount([ [ 'level_name', '=', $data[ 'level_name' ] ] ]);
        if ($count > 0) return $this->error('', '等级名称已存在');

        $res = model('fenxiao_level')->add($data);
        return $this->success($res);
    }

    /**
     * 编辑分销等级
     * @param $data
     * @param array $condition
     * @return array
     */
    public function editLevel($data, $condition = [])
    {
        $data[ 'update_time' ] = time();

        $check_condition = array_column($condition, 2, 0);
        $level_id = isset($check_condition[ 'level_id' ]) ? $check_condition[ 'level_id' ] : 0;
        //判断是否重复
        $count = model('fenxiao_level')->getCount([ [ 'level_name', '=', $data[ 'level_name' ] ], [ 'level_id', '<>', $level_id ] ]);
        if ($count > 0) return $this->error('', '等级名称已存在');

        $res = model('fenxiao_level')->update($data, $condition);
        if ($res) {
            //使商品对应该分销等级数据的更正
            $fenxiao_goods_data = [
                'one_rate' => $data['one_rate'],
                'two_rate' => $data['two_rate'],
                'three_rate' => $data['three_rate'],
            ];
            model('fenxiao_goods_sku')->update($fenxiao_goods_data, $condition);

            if (isset($data[ 'level_name' ]) && $data[ 'level_name' ] != '') {
                model('fenxiao')->update([ 'level_name' => $data[ 'level_name' ] ], $condition);
            }
        }
        return $this->success($res);
    }


    /**
     * 删除分销等级
     * @param array $condition
     * @return array
     */
    public function deleteLevel($level_id)
    {
        $fenxiao_model = new Fenxiao();
        $fenxiao_list = $fenxiao_model->getFenxiaoList([ [ 'level_id', '=', $level_id ] ], 'fenxiao_id');
        if (empty($fenxiao_list[ 'data' ])) {
            $res = model('fenxiao_level')->delete([ [ 'level_id', '=', $level_id ] ]);
            return $this->success($res);
        } else {
            return $this->error('', '该分销等级存在其他分销商，无法删除');
        }
    }

    /**
     * 获取分销等级信息
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getLevelInfo($condition = [], $field = '*')
    {
        $res = model('fenxiao_level')->getInfo($condition, $field);
        return $this->success($res);
    }

    /**
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getLevelColumn($condition = [], $field = 'level_id')
    {
        $list = model('fenxiao_level')->getColumn($condition, $field);
        return $this->success($list);
    }

    /**
     * 获取分销商等级列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param string $limit
     */
    public function getLevelList($condition = [], $field = '*', $order = 'level_num asc,one_rate asc', $limit = null)
    {

        $list = model('fenxiao_level')->getList($condition, $field, $order, '', '', '', $limit);
        return $this->success($list);
    }

    /**
     * 获取分销商等级分页列表
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getLevelPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'level_num asc,one_rate asc', $field = '*')
    {
        $list = model('fenxiao_level')->pageList($condition, $field, $order, $page, $page_size);
        return $this->success($list);
    }

    /**
     * 获取最低的分销商等级
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getMinLevel($condition = [], $field = '*', $order = 'level_num asc,one_rate asc')
    {
        $info = model('fenxiao_level')->getFirstData($condition, $field, $order);
        return $this->success($info);
    }
}