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
use think\facade\Db;

/**
 * 商品导出记录
 */
class GoodsExport extends BaseModel
{

    /**
     * 添加导出记录
     * @param $data
     * @return array
     */
    public function addExport($data)
    {
        $res = model("goods_export")->add($data);
        return $this->success($res);
    }

    /**
     * 更新导出记录
     * @param $data
     * @return array
     */
    public function editExport($data, $condition)
    {
        $res = model("goods_export")->update($data, $condition);
        return $this->success($res);
    }

    /**
     * 删除导出记录
     * @param $data
     * @return array
     */
    public function deleteExport($condition)
    {
        //先查询数据
        $list = model("goods_export")->getList($condition, '*');
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                if (file_exists($v[ 'path' ])) {
                    //删除物理文件路径
                    if (!unlink($v[ 'path' ])) {
                        //失败
                    } else {
                        //成功
                    }
                }
            }
            $res = model("goods_export")->delete($condition);
        }

        return $this->success($res);
    }

    /**
     * 获取导出记录
     * @param $member_id
     * @return array
     */
    public function getExport($condition, $field = "*", $order = '')
    {

        $list = model("goods_export")->getList($condition, $field, $order);
        return $this->success($list);
    }

    /**
     * 导出记录
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getExportPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*')
    {
        $list = model('goods_export')->pageList($condition, $field, $order, $page, $page_size);
        return $this->success($list);
    }

    /**
     * 导出商品数据
     * @param $condition
     */
    public function exportData($condition, $condition_desc = [], $site_id = 0)
    {
        try {
            $column_condition = array_column($condition, 2, 0);
//            $site_id = $column_condition[ 'site_id' ] ?? 0;
//            if($site_id > 0){
//                $shop_category_model = new GoodsShopCategory();
//                $shop_category_list = $shop_category_model->getShopCategoryList([['site_id', '=', $site_id]])['data'] ?? [];
//                $shop_category_list = array_column($shop_category_list, 'category_name', 'category_id');
//            }

//            $condition_dict = array(
//                'goods_name' => ['name' => '商品名称'],
//                'goods_class' => ['name' => '商品类型'],
//                'goods_state' => ['name' => '上架状态', 'dict' => [1=>'正常', 0 => '下架']],
//                'verify_state' => ['name' => '审核状态', 'dict' => [1=>'已审核', 0 => '待审核', 10 => '违规下架', -1 => '审核中', -2 => '审核失败']],
//                'sale_num' => ['name' => '销量'],
//                'price' => ['name' => '价格'],
////                'goods_shop_category_ids' => ['name' => '店内分类', 'dict' => $shop_category_list ?? []],
//            );
            $field_dict = array (
                'goods_id' => [ 'name' => '商品id' ],
                'goods_name' => [ 'name' => '商品名称' ],
                'sku_id' => [ 'name' => '规格id' ],
                'spec_name' => [ 'name' => '商品规格' ],
                'goods_class_name' => [ 'name' => '商品类型' ],
                'sku_no' => [ 'name' => '规格编号' ],
                'price' => [ 'name' => '销售价' ],
                'market_price' => [ 'name' => '市场价' ],
                'cost_price' => [ 'name' => '成本价' ],
                'stock' => [ 'name' => '库存' ],
                'category_name' => [ 'name' => '所属分类' ],
                'brand_name' => [ 'name' => '商品品牌' ],
                'goods_state' => [ 'name' => '商品状态', 'dict' => [ 1 => '正常', 0 => '下架' ] ],
                'verify_state' => [ 'name' => '审核状态', 'dict' => [ 1 => '已审核', 0 => '待审核', 10 => '违规下架', -1 => '审核中', -2 => '审核失败' ] ],
                'click_num' => [ 'name' => '点击量' ],
                'sale_num' => [ 'name' => '销量' ],
                'collect_num' => [ 'name' => '收藏量' ],
            );

            set_time_limit(0);
            $file_name = date('YmdHis');//csv文件名

            $file_path = __UPLOAD__ . "/common/csv/" . date("Ymd") . '/';
            $file_name = $file_name . '.csv';

            if (dir_mkdir($file_path)) {
                //创建一个临时csv文件
                $fp = fopen($file_path . $file_name, 'w'); //生成临时文件
                fwrite($fp, chr(0xEF) . chr(0xBB) . chr(0xBF)); // 添加 BOM
//                $field_string = implode(',', array_keys($field_dict));
                $field_string = '';
                $temp_val = [];
                $temp_key = [];
                foreach ($field_dict as $k => $v) {
                    $temp_val[] = $v[ 'name' ];
                    $temp_key[] = "{\$$k}";

                    if (!empty($field_string)) {
                        $field_string .= ',';
                    }
                    if ($k == 'category_name') {
                        $field_string .= 'g.';
                    } else {
                        $field_string .= 'gs.';
                    }
                    $field_string .= $k;
                }
                $goods_table = Db::name('goods_sku')->where($condition)->alias('gs');
                $join = [
                    [
                        'goods g',
                        'g.goods_id = gs.goods_id',
                        'left'
                    ]
                ];
                $goods_table = $this->parseJoin($goods_table, $join);

                $first_line = implode(',', $temp_val);
                //写入第一行表头
                fwrite($fp, $first_line . "\n");
                $temp_line = implode(',', $temp_key) . "\n";
                //防止超出配置内存
                $goods_table->field($field_string)->chunk(5000, function($item_list) use ($fp, $temp_line, $field_dict) {
                    //写入导出信息
                    $this->itemExport($item_list, $temp_line, $fp, $field_dict);
                    unset($item_list);
                }, 'gs.sku_id');
                $goods_table->removeOption();
                fclose($fp);  //每生成一个文件关闭
                unset($goods_table);

                //创建记录
                $data = array (
                    'condition' => json_encode($condition_desc),
                    'status' => 1,
                    'create_time' => time(),
                    'path' => $file_path . $file_name,
                    'site_id' => $site_id
                );
                $result = $this->addExport($data);
                return $result;
            } else {
                return $this->error([], '');
            }
        } catch (\Exception $e) {
            return $this->error([], $e->getMessage() . $e->getLine());
        }
    }


    /**
     * 给csv写入新的数据
     * @param $item_list
     * @param $field_key
     * @param $temp_line
     * @param $fp
     */
    public function itemExport($item_list, $temp_line, $fp, $field_dict)
    {
        $item_list = $item_list->toArray();

        //特殊值的转换
        foreach ($item_list as $k => $v) {
            foreach ($v as $v_k => $v_v) {
                $tron_item = $field_dict[ $v_k ] ?? [];
                if (!empty($tron_item)) {
                    $tron_dict_item = $tron_item[ 'dict' ] ?? [];
                    if (!empty($tron_dict_item)) {
                        $item_list[ $k ][ $v_k ] = $tron_dict_item[ $v_v ];
                    }
                }
            }
        }

        foreach ($item_list as $k => $item_v) {
            $new_line_value = $temp_line;
            foreach ($item_v as $key => $value) {
                //CSV比较简单，记得转义 逗号就好
                $values = str_replace(',', '\\', $value . "\t");
                $values = str_replace("\n", '', $values);
                $new_line_value = str_replace("{\$$key}", $values, $new_line_value);
            }
            //写入第一行表头
            fwrite($fp, $new_line_value);
            //销毁变量, 防止内存溢出
            unset($new_line_value);
        }
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
            switch ( $type ) {
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

}
