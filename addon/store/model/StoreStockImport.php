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
namespace addon\store\model;

use app\model\BaseModel;

class StoreStockImport extends BaseModel
{

    /**
     * 详情
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getStoreStockImportInfo($condition = [], $field = '*')
    {
        $info = model('store_stock_import')->getInfo($condition,$field);
        return $this->success($info);
    }

    /**
     * 删除
     * @param $id
     * @param $store_id
     * @return array
     */
    public function deleteStoreStockImport($id,$store_id)
    {
        model('store_stock_import')->startTrans();
        try {
            model('store_stock_import')->delete([['id', '=', $id],['store_id','=',$store_id]]);
            model('store_stock_import_log')->delete([['file_id', '=', $id]]);

            model('store_stock_import')->commit();
            return $this->success();
        } catch (\Exception $e) {

            model('store_stock_import')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 获取导入文件列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getStoreStockImportPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'id desc', $field = '*')
    {
        $list = model('store_stock_import')->pageList($condition, $field, $order, $page, $page_size);
        return $this->success($list);
    }


    /**
     * 获取导入文件列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getStoreStockImportPageLogList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'id desc', $field = '*')
    {
        $list = model('store_stock_import_log')->pageList($condition, $field, $order, $page, $page_size);
        return $this->success($list);
    }


}
