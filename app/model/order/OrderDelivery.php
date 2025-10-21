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

namespace app\model\order;


use addon\electronicsheet\model\ElectronicsheetDelivery;
use app\model\BaseModel;
use app\model\system\Address as AddressModel;
use think\facade\Cache;
use think\facade\Db;


/**
 * 订单发货
 * Class OrderDelivery
 * @package app\model\order
 */
class OrderDelivery extends BaseModel
{
    /**
     * 删除
     * @param $id
     * @return array
     */
    public function deleteOrderBatchDelivery($batch_id,$site_id)
    {
        model('order_batch_delivery')->startTrans();
        try {
            model('order_batch_delivery')->delete([['batch_id', '=', $batch_id],['site_id','=',$site_id]]);
            model('order_batch_delivery_log')->delete([['batch_id', '=', $batch_id]]);

            model('order_batch_delivery')->commit();
            return $this->success();
        } catch (\Exception $e) {

            model('order_batch_delivery')->rollback();
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
    public function getOrderBatchDeliveryPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'batch_id desc', $field = '*')
    {
        $list = model('order_batch_delivery')->pageList($condition, $field, $order, $page, $page_size);
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
    public function getOrderBatchDeliveryPageLogList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'batch_id desc', $field = '*')
    {
        $list = model('order_batch_delivery_log')->pageList($condition, $field, $order, $page, $page_size);
        return $this->success($list);
    }



    /**
     * 批量订单发货（导入excel文件发货）
     * @param $filename
     */
    public function batchDelivery($file, $site_id)
    {
        set_time_limit(0);
        $tmp_name = $file->getPathname();//获取上传缓存文件
        $csv_list = readCsv($tmp_name);
        if (empty($csv_list)) {
            return $this->error();
        }
        unset($csv_list[0]);

        $list = [];
        $key_dict = array(
            'order_no', 'order_name', 'delivery_type', 'delivery_company', 'delivery_no'
        );
        foreach ($csv_list as $list_k => $list_v) {
            $item_list = [];
            foreach ($list_v as $item_k => $item_v) {
                $item_list[$key_dict[$item_k]] = trim($item_v);
            }
            $list[] = $item_list;
        }
        $addon_is_exit = addon_is_exit('electronicsheet', $site_id); //电子面单插件
        if (empty($list)) {
            return $this->error('', '导入了一个空文件');
        }
        $chunk_list = array_chunk($list, 100);//防止数组太大 造成内存或超出数据库最大允许

        $order_model = new Order();
        $electronicsheet_model = new ElectronicsheetDelivery();
        $import_id = 0;

        $order_count_num = 0;
        $error_count_num = 0;//错误数量
        $success_count_num = 0;//成功数量
        $log_list = [];
        model('order_batch_delivery')->startTrans();
        try {
            foreach ($chunk_list as $chunk_k => $chunk_v) {
//            $result = $this->batchDelivery($list, $this->site_id);
                $order_num = count($chunk_v);
                $success_log_list = [];
                $error_log_list = [];
                foreach ($chunk_v as $list_k => $list_v) {
                    $item_error = '';
                    $delivery_data = [
                        'type' => '',//发货方式（手动发货、电子面单）
                        'express_company_id' => 0,//物流公司
                        'delivery_type' => 1,//是否需要物流
                        'site_id' => $site_id,
                        'template_id' => 0,//电子面单模板id
                        'delivery_no' => ''
                    ];
                    $order_no = trim($list_v['order_no'], "\t");//订单编号
                    $order_name = trim($list_v['order_name'], "\t");//订单名称
                    $delivery_type = trim($list_v['delivery_type'], "\t");//发货方式
                    $delivery_company = trim($list_v['delivery_company'], "\t");//物流公司名称或电子面单名称
                    $delivery_no = trim($list_v['delivery_no'], "\t");//物流单号
                    if (empty($delivery_type)) {
//                    $item_error = '发货方式为空';
                        $error_log_list[] = ['batch_id' => &$import_id, 'order_no' => $order_no, 'order_name' => $order_name, 'reason' => '发货方式为空', 'type' => 'fail', 'type' => 'fail'];
                        continue;
                    }
                    if ($delivery_type == '电子面单' && $addon_is_exit == 1) {
                        if (empty($delivery_company)) {
//                        $item_error = '电子面单模板为空';
                            $error_log_list[] = ['batch_id' => &$import_id, 'order_no' => $order_no, 'order_name' => $order_name, 'reason' => '电子面单模板为空', 'type' => 'fail'];
                            continue;
                        }
                        $delivery_data['type'] = 'electronicsheet';
                        $template_id = model('express_electronicsheet')->getValue([['template_name', '=', $delivery_company], ['site_id', '=', $site_id]], 'id');
                        if (empty($template_id)) {
//                        $item_error = '电子面单模板不存在';
                            $error_log_list[] = ['batch_id' => &$import_id, 'order_no' => $order_no, 'order_name' => $order_name, 'reason' => '电子面单模板不存在', 'type' => 'fail'];
                            continue;
                        }
                        $delivery_data['template_id'] = $template_id;
                    } elseif ($delivery_type == '电子面单' && $addon_is_exit != 1) {
//                    $item_error = '电子面单插件暂不支持使用';
                        $error_log_list[] = ['batch_id' => &$import_id, 'order_no' => $order_no, 'order_name' => $order_name, 'reason' => '电子面单插件暂不支持使用', 'type' => 'fail'];
                        continue;
                    } else {
                        $delivery_data['type'] = 'manual';
                        if (empty($delivery_no) || empty($delivery_company)) {//无需物流
                            $delivery_data['delivery_type'] = 0;
                        } else {
                            $company_id = model('express_company')->getInfo([['company_name', '=', $delivery_company]], 'company_id')['company_id'] ?? 0;
                            if ($company_id <= 0) {
//                            $item_error = '物流公司不存在';
                                $error_log_list[] = ['batch_id' => &$import_id, 'order_no' => $order_no, 'order_name' => $order_name, 'reason' => '物流公司不存在', 'type' => 'fail'];
                                continue;
                            }
                            $shop_company_info = model('express_company_shop')->getInfo([['site_id', '=', $site_id], ['company_id', '=', $company_id]]);
                            if (empty($shop_company_info)) {
                                $item_error = '店铺未配置' . $delivery_company . '物流公司';
                                $error_log_list[] = ['batch_id' => &$import_id, 'order_no' => $order_no, 'order_name' => $order_name, 'reason' => $item_error, 'type' => 'fail'];
                                continue;
                            }
                            $delivery_data['express_company_id'] = $company_id;
                            $delivery_data['delivery_no'] = $delivery_no;
                        }
                    }
                    //订单信息
                    $order_info = model('order')->getInfo([['order_no', '=', $order_no], ['site_id', '=', $site_id]], 'order_id,order_status');
                    if (empty($order_info) || $order_info['order_status'] != Order::ORDER_PAY) {
//                    $item_error = '订单不存在或者已发货';
                        $error_log_list[] = ['batch_id' => &$import_id, 'order_no' => $order_no, 'order_name' => $order_name, 'reason' => '订单不存在或者已发货', 'type' => 'fail'];
                        continue;
                    }
                    $delivery_data['order_id'] = $order_info['order_id'];
                    $delivery_data['order_goods_ids'] = '';
                    if ($delivery_data['type'] == 'electronicsheet') {//电子面单发货
                        $result = $electronicsheet_model->delivery($delivery_data);
                        if ($result['code'] < 0) {
//                        $item_error = $result['message'];
                            $error_log_list[] = ['batch_id' => &$import_id, 'order_no' => $order_no, 'order_name' => $order_name, 'reason' => $result['message'], 'type' => 'fail'];
                            continue;
                        }
                        $delivery_data['delivery_no'] = $result['data']['Order']['LogisticCode'];
                    }

                    $result = $order_model->orderGoodsDelivery($delivery_data, 2);
                    if ($result['code'] < 0) {
//                    $item_error = $result['message'];
                        $error_log_list[] = ['batch_id' => &$import_id, 'order_no' => $order_no, 'order_name' => $order_name, 'reason' => $result['message'], 'type' => 'fail'];
                        continue;
                    }
                    $success_log_list[] = ['batch_id' => &$import_id, 'site_id' => $site_id, 'order_no' => $order_no, 'order_name' => $order_name, 'type' => 'success'];

                }

                $error_num = count($error_log_list);
                $success_num = $order_num - $error_num;
                $order_count_num += $order_num;
                $error_count_num += $error_num;
                $success_count_num += $success_num;

                $log_list[] = array_merge($error_log_list, $success_log_list);
                unset($chunk_v);
                unset($error_log_list);
                unset($success_log_list);
            }

            //批量发货记录
            $data = [
                'site_id' => $site_id,
                'error_num' => $error_count_num,
                'order_num' => $order_count_num,
                'success_num' => $success_count_num,
                'create_time' => time(),
                'batch_no' => $this->createBatchDeliveryNo()
            ];
            $import_id = model('order_batch_delivery')->add($data);

            //分步执行insert 行为
            foreach($log_list as $log_k => $log_v){
                model('order_batch_delivery_log')->addList($log_v);
            }
            model('order_batch_delivery')->commit();
            return $this->success();
        } catch ( \Exception $e ) {
            model('order_batch_delivery')->rollback();
            return $this->error('', $e->getMessage() . $e->getFile() . $e->getLine());
        }


//        $order_num = count($list);
//        model('order_batch_delivery')->startTrans();
//        try {
//
//            $error_num = count($error_log_list);
//            $success_num = $order_num - $error_num;
//            $data = [
//                'site_id' => $site_id,
//                'error_num' => $error_num,
//                'order_num' => $order_num,
//                'success_num' => $success_num,
//                'create_time' => time(),
//                'batch_no' => $this->createBatchDeliveryNo()
//            ];
//            $import_id = model('order_batch_delivery')->add($data);
//            //增加失败记录
//            $log_list = array_merge($error_log_list, $success_log_list);
//            model('order_batch_delivery_log')->addList($log_list);
//            model('order_batch_delivery')->commit();
//            return $this->success();
//        } catch (\Exception $e) {
//            model('order_batch_delivery')->rollback();
//            return $this->error('', $e->getMessage().$e->getFile().$e->getLine());
//        }
    }

    /**
     * 创建支付流水号
     */
    public function createBatchDeliveryNo()
    {
        $cache = Cache::get("batch_delivery_no" . time());
        if (empty($cache)) {
            Cache::set("niubfd" . time(), 1000);
            $cache = Cache::get("batch_delivery_no" . time());
        } else {
            $cache = $cache + 1;
            Cache::set("batch_delivery_no" . time(), $cache);
        }
        $no = time() . rand(1000, 9999) . $cache;
        return $no;
    }

    /**
     * 导出待发货订单
     * @param $site_id
     */
    public function exportDeliveryOrder($site_id){
        $addon_is_exit = addon_is_exit('electronicsheet', $site_id);
        set_time_limit(0);
        //创建一个临时csv文件

        $order_model = new Order();
        $order_status_list = $order_model->delivery_order_status;

        $condition = [
            ['order_status', 'in', array_keys($order_status_list)],
            ['order_type', '=', 1],
            ['site_id', '=', $site_id],
            ['is_delete', '=', 0]
        ];


        if($addon_is_exit == 1) {
            $field_dict = array(
                '订单编号' => 'order_no',
                '订单内容' => 'order_name',
                '发货方式（手动发货/电子面单）' => '',
                '物流公司名称（电子面单发货时为面单模板名称）' => '',
                '物流单号（手动发货无需物流和电子面单时为空）' => '',
                '收件人' => 'name',
                '购买人手机'    => 'mobile',
                '省'         => 'province_name',
                '市'         => 'city_name',
                '区'         => 'district_name',
                '详细地址'         => 'address',
                '购买人完整地址'    =>'full_address',
            );
        }else{
            $field_dict = array(
                '订单编号' => 'order_no',
                '订单内容' => 'order_name',
                '发货方式' => '',
                '物流公司名称' => '',
                '物流单号（无需物流时为空）' => '',
                '收件人' => 'name',
                '购买人手机'    => 'mobile',
                '省'         => 'province_name',
                '市'         => 'city_name',
                '区'         => 'district_name',
                '详细地址'         => 'address',
                '购买人完整地址'   =>'full_address',
            );
        }
        $temp_val = [];
        $temp_key = [];
        foreach ($field_dict as $k => $v) {
            $temp_val[] = $k;
            if(empty($v)){
                $temp_key[] = "";
            }else{
                $temp_key[] = "{\$$v}";
            }
        }
        $first_line = implode(',', $temp_val);
        //写入第一行表头
        $csv_html = $first_line. "\n";
        $temp_line = implode(',', $temp_key) . "\n";

        $field = 'order_no,order_name,order_id,name,mobile,province_id AS province_name,city_id AS city_name,district_id AS district_name,address,full_address';
        $order_table = Db::name('order')->where($condition);
        $order_table->field($field)->chunk(5000, function($item_list) use (&$csv_html, $temp_line){
            $item_list = $item_list->toArray();
            $city_model = new AddressModel();
            foreach($item_list as $k => $item_v){
                $new_line_value = $temp_line;
                $address = '';
                foreach ($item_v as $key => $value) {
                    if(in_array($key,['province_name','city_name','district_name'])){
                        $province_name = $city_model->getAreaInfo($value);
                        $value = $province_name['data']['name'];
                    }
                    if($key == 'address'){
                        $address = $value;
                    }
                    if($key == 'full_address'){
                        $value = $value.'-'.$address;
                    }
                    //CSV比较简单，记得转义 逗号就好
                    $values = str_replace(',', '\\', $value."\t");
                    $new_line_value = str_replace("{\$$key}", $values, $new_line_value);
                }
                $csv_html .= $new_line_value;
                //销毁变量, 防止内存溢出
                unset($new_line_value);
            }
            unset($item_list);
        });
        $order_table->removeOption();
        unset($order_table);

        $file_name = '批量发货模板'.date('Y-m-d',time());
        $file_name = empty($file_name) ? date('Ymd his') : $file_name;

        $filename = $file_name.'.csv'; //设置文件名
        header("Content-type:text/csv");
        header("Content-Disposition:attachment;filename=".$filename);
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        exit(mb_convert_encoding($csv_html, "GBK", "UTF-8"));


    }
}
