<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\shop\controller;

use addon\electronicsheet\model\ExpressElectronicsheet as ExpressElectronicsheetModel;
use app\model\express\ExpressCompany;
use app\model\order\OrderDelivery as OrderDeliveryModel;
use phpoffice\phpexcel\Classes\PHPExcel;
use phpoffice\phpexcel\Classes\PHPExcel\Writer\Excel2007;

/**
 * 订单发货相关
 * Class Express
 * @package app\shop\controller
 */
class Orderdelivery extends BaseShop
{

    public function __construct()
    {
        //执行父类构造函数
        parent::__construct();

    }


    /**
     * 批量发货（订单导入）
     * @return array|mixed
     */
    public function lists()
    {
        //电子面单插件
        $addon_is_exit = addon_is_exit('electronicsheet', $this->site_id);

        if (request()->isAjax()) {
            $page_index = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);

            $model = new OrderDeliveryModel();
            $list = $model->getOrderBatchDeliveryPageList([], $page_index, $page_size);
            return $list;
        }
        $this->assign('addon_is_exit', $addon_is_exit);
        return $this->fetch('orderdelivery/lists');
    }

    /**
     * 导出待发货订单
     */
    public function exportDeliveryOrder()
    {
        $model = new OrderDeliveryModel();
        $model->exportDeliveryOrder($this->site_id);
    }

    /**
     * 导出物流公司和电子面单模板
     */
    public function exportExpressTemplate()
    {
        //电子面单插件
        $addon_is_exit = addon_is_exit('electronicsheet', $this->site_id);

        $express_company_model = new ExpressCompany();
        //店铺物流公司
        $company_list = $express_company_model->getExpressCompanyShopList([ [ "site_id", "=", $this->site_id ] ])[ 'data' ] ?? [];
        $list = [];
        if (!empty($company_list)) {
            $company_condition = array (
                [ 'company_id', 'in', array_column($company_list, 'company_id') ]
            );
            $list = $express_company_model->getExpressCompanyList($company_condition)[ 'data' ] ?? [];
        }

        $field_dict = array (
            'company_name' => '物流公司',
            'express_no_kdniao' => '快递鸟物流公司编码',
            'express_no_kd100_free' => '快递100物流公司编码',
            'express_no_kd100' => '快递100物流公司编码',
        );
        $temp_val = [];
        $temp_key = [];
        foreach ($field_dict as $k => $v) {
            $temp_val[] = $v;
            $temp_key[] = "{\$$k}";

        }
        $array = implode(',', $temp_val);
        $export_content = $array . "\n";
        $temp_line = implode(',', $temp_key) . "\n";
        foreach ($list as $listvalue) {
            $newvalue = $temp_line;
            foreach ($listvalue as $key => $value) {
                //CSV比较简单，记得转义 逗号就好
                $values = str_replace(',', '\\', $value . "\t");
                $newvalue = str_replace("{\$$key}", $values, $newvalue);
            }
            $export_content .= $newvalue;
        }
        if ($addon_is_exit == 1) {
            $export_content .= "\n";
            $export_content .= "\n";
            $export_content .= "\n";
            //获取电子面单模板
            $electronicsheet_model = new ExpressElectronicsheetModel();
            $condition[] = [ 'site_id', '=', $this->site_id ];
            $field = 'id,template_name,company_name';
            $electronicsheet_list_result = $electronicsheet_model->getExpressElectronicsheetList($condition, $field, 'is_default desc');
            $electronicsheet_list = $electronicsheet_list_result[ 'data' ];

            $electronicsheet_field_dict = array (
                'template_name' => '电子面单模板名称',
                'company_name' => '物流公司',
            );
            $temp_electronicsheet_val = [];
            $temp_electronicsheet_key = [];
            foreach ($electronicsheet_field_dict as $electronicsheet_k => $electronicsheet_v) {
                $temp_electronicsheet_val[] = $electronicsheet_v;
                $temp_electronicsheet_key[] = "{\$$electronicsheet_k}";

            }
            $electronicsheet_array = implode(',', $temp_electronicsheet_val);
            $export_content .= $electronicsheet_array . "\n";
            $electronicsheet_temp_line = implode(',', $temp_electronicsheet_key) . "\n";
            foreach ($electronicsheet_list as $electronicsheet_listvalue) {
                $electronicsheet_newvalue = $electronicsheet_temp_line;
                foreach ($electronicsheet_listvalue as $electronicsheet_key => $electronicsheet_value) {
                    //CSV比较简单，记得转义 逗号就好
                    $electronicsheet_values = str_replace(',', '\\', $electronicsheet_value . "\t");
                    $electronicsheet_newvalue = str_replace("{\$$electronicsheet_key}", $electronicsheet_values, $electronicsheet_newvalue);
                }
                $export_content .= $electronicsheet_newvalue;
            }
        }

        $filename = date('Y年m月d日-物流公司和电子面单对照表', time()) . '.csv'; //设置文件名
        header("Content-type:text/csv");
        header("Content-Disposition:attachment;filename=" . $filename);
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        exit(mb_convert_encoding($export_content, "GBK", "UTF-8"));
    }

    /**
     *  导入订单发货
     */
    public function batchDelivery()
    {
        $order_delivery_model = new OrderDeliveryModel();
        if (request()->isAjax()) {
            $file = request()->file('csv');
            if (empty($file)) {
                return $this->error();
            }
            $result = $order_delivery_model->batchDelivery($file, $this->site_id);
            return $result;
        }
    }

    /**
     * 删除导入的订单文件记录
     */
    public function delete()
    {
        if (request()->isAjax()) {

            $batch_id = input('batch_id', '');

            $model = new OrderDeliveryModel();
            $res = $model->deleteOrderBatchDelivery($batch_id, $this->site_id);
            return $res;
        }
    }

    /**
     * 失败记录
     * @return array|mixed
     */
    public function orderDeliveryLog()
    {
        $batch_id = input('batch_id', 0);
        if (request()->isAjax()) {

            $page_index = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);

            $model = new OrderDeliveryModel();

            $condition = [
                [ 'batch_id', '=', $batch_id ],
                [ 'type', '=', 'fail' ]
            ];
            $list = $model->getOrderBatchDeliveryPageLogList($condition, $page_index, $page_size);
            return $list;
        }
        $this->assign('batch_id', $batch_id);
        return $this->fetch('orderdelivery/orderdelivery_log');
    }
}