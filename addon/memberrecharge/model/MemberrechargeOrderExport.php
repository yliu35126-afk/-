<?php


namespace addon\memberrecharge\model;


use app\model\BaseModel;
use think\facade\Db;

class MemberrechargeOrderExport extends BaseModel
{
    public $order_field = [
        'member_id' => '用户ID',
        'nickname' => '用户昵称',
        'order_no' => '订单编号',
        'out_trade_no' => '订单流水号',
        'recharge_name' => '套餐名称',
        'face_value' => '面值',
        'price' => '价格',
        'point' => '积分',
        'growth' => '成长值',
        'buy_price' => '实付金额',
//        'pay_type' => '支付方式',
        'pay_type_name' => '支付方式名称',
        'status' => '支付状态',
        'create_time' => '创建时间',
        'pay_time' => '支付时间',
        'order_from' => '订单来源',
        'order_from_name' => '订单来源名称'
    ];

    public $define_data = [
        'status' => [ 'type' => 2, 'data' => [1 => '未支付', 2 => '已支付' ] ],//支付状态
        'create_time' => [ 'type' => 1 ],//创建时间
        'pay_time' => [ 'type' => 1 ],//支付时间
    ];

    /**
     * 查询订单项数据并导出
     * @param $condition
     */
    public function orderExport($condition, $condition_desc, $site_id = 0, $join = [])
    {
        try {
            $alias = 'mro';
            $field = $this->order_field;
            //通过分批次执行数据导出(防止内存超出配置设置的)
            set_time_limit(0);
            $file_name = date('Y年m月d日H时i分s秒').'充值订单';//csv文件名
                $file_path =  $file_name . '.csv';
                //创建一个临时csv文件
                $fp = fopen($file_path, 'w'); //生成临时文件
                fwrite($fp, chr(0xEF) . chr(0xBB) . chr(0xBF)); // 添加 BOM
                $field_value = [];
                $field_key = [];
                $field_key_array = [];
                //为了防止部分代码被筛选中替换, 给变量前后两边增加字符串
                foreach ($field as $k => $v) {
                    $field_value[] = $v;
                    $field_key[] = "{\$$k}";
                    $field_key_array[] = $k;
                }
                $order_table = Db::name('member_recharge_order')->where($condition)->alias($alias);

                if (!empty($join)) {
                    $order_table = $this->parseJoin($order_table, $join);
                }
                $first_line = implode(',', $field_value);
                //写入第一行表头
                fwrite($fp, $first_line . "\n");
                $temp_line = implode(',', $field_key) . "\n";
                $table_field = 'mro.*';
                $order_table->field($table_field)->chunk(5000, function ($item_list) use ($fp, $temp_line, $field_key_array) {
                    //写入导出信息
                    $this->itemExport($item_list, $field_key_array, $temp_line, $fp);
                    unset($item_list);
                }, 'mro.order_id');

                $order_table->removeOption();
                fclose($fp);  //每生成一个文件关闭
                return $this->success(['file'=>$file_path]);
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage() . $e->getFile() . $e->getLine());
        }

    }

    /**
     *  数据处理
     * @param $data
     * @param $field
     * @return array
     */
    public function handleData($data, $field)
    {
        $define_data = $this->define_data;
        foreach ($data as $k => $v) {
            //获取键
            $keys = array_keys($v);

            foreach ($keys as $key) {

                if (in_array($key, $field)) {

                    if (array_key_exists($key, $define_data)) {

                        $type = $define_data[ $key ][ 'type' ];

                        switch ( $type ) {

                            case 1:
                                $data[ $k ][ $key ] = time_to_date($v[ $key ]);
                                break;
                            case 2:
                                $define_data_data = $define_data[ $key ][ 'data' ];
                                $data[ $k ][ $key ] = !empty($v[ $key ]) ? $define_data_data[ $v[ $key ] ] : '';
                        }

                    }
                }
            }

        }
        return $data;
    }

    /**
     *
     * @param $db_obj
     * @param $join
     * @return mixed
     */
    public function parseJoin($db_obj, $join)
    {
        foreach ($join as $item) {
            list($table, $on, $type) = $item;
            $type = strtolower($type);
            switch ($type) {
                case "left":
                    $db_obj = $db_obj->leftJoin($table, $on);
                    break;
                case "inner":
                    $db_obj = $db_obj->join($table, $on);
                    break;
                case "right":
                    $db_obj = $db_obj->rightjoin($table, $on);
                    break;
                case "full":
                    $db_obj = $db_obj->fulljoin($table, $on);
                    break;
                default:
                    break;
            }
        }
        return $db_obj;
    }

    /**
     * 给csv写入新的数据
     * @param $item_list
     * @param $field_key
     * @param $temp_line
     * @param $fp
     */
    public function itemExport($item_list, $field_key, $temp_line, $fp)
    {
        $item_list = $item_list->toArray();
        $item_list = $this->handleData($item_list, $field_key);
        foreach ($item_list as $k => $item_v) {

            $new_line_value = $temp_line;
            foreach ($item_v as $key => $value) {
                if ($key == 'full_address') {
                    $address = $item_v['address'] ?? '';
                    $value = $value . $address;
                }
                //CSV比较简单，记得转义 逗号就好
                $values = str_replace(',', '\\', $value . "\t");
                $values = str_replace("\n", '', $values);
                $new_line_value = str_replace("{\$$key}", $values, $new_line_value);
//                $new_line_value = iconv("UTF-8", "GB2312//IGNORE", $new_line_value);
            }
            //写入第一行表头
            fwrite($fp, $new_line_value);
            //销毁变量, 防止内存溢出
            unset($new_line_value);
        }
    }
}