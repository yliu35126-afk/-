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


use app\model\shop\Shop;
use think\facade\Db;
use think\facade\Cache;
use app\model\BaseModel;
use app\model\order\Config as OrderConfig;

/**
 * 商品评价
 */
class GoodsEvaluate extends BaseModel
{
    private $evaluate_status = [
        0 => '未评价',
        1 => '已评价',
        2 => '已追评'
    ];

    /**
     * 添加评价
     * @param array $data
     */
    public function addEvaluate($data)
    {
        $res = model('goods_evaluate')->getInfo([['order_id', '=', $data['order_id']]], 'evaluate_id');
        if (empty($res)) {

            //订单评价设置  (评价审核配置)
            $config_model = new OrderConfig();

            $order_evaluate_config = $config_model->getOrderEvaluateConfig();
            $order_evaluate_config = $order_evaluate_config['data']['value'];
            $data_arr = [];
            $is_audit = $order_evaluate_config['evaluate_audit'] == 1 ? 0 : 1;
            foreach ($data['goods_evaluate'] as $k => $v) {
                $item = [
                    'order_id' => $data['order_id'],
                    'order_no' => $data['order_no'],
                    'member_id' => $data['member_id'],
                    'member_name' => $data['member_name'],
                    'member_headimg' => $data['member_headimg'],
                    'is_anonymous' => $data['is_anonymous'],
                    'order_goods_id' => $v['order_goods_id'],
                    'goods_id' => $v['goods_id'],
                    'sku_id' => $v['sku_id'],
                    'site_id' => $v['site_id'],
                    'sku_name' => $v['sku_name'],
                    'sku_price' => $v['sku_price'],
                    'sku_image' => $v['sku_image'],
                    'content' => $v['content'],
                    'images' => $v['images'],
                    'scores' => $v['scores'],
                    'explain_type' => $v['explain_type'],
                    'create_time' => time(),

                    'shop_desccredit' => $data["shop_desccredit"],
                    'shop_servicecredit' => $data["shop_servicecredit"],
                    'shop_deliverycredit' => $data["shop_deliverycredit"],

                    'is_audit' => 0,//审核状态 ：0 未审核 1 审核通过，-1 审核拒绝
                ];
                $data_arr[] = $item;
                $evaluate_id = model('goods_evaluate')->add($item);
                if($is_audit == 1){

                    $audit_data = [
                        'is_audit' => $is_audit
                    ];
                    $audit_condition = [
                        [ 'evaluate_id', 'in', $evaluate_id ],
                        [ 'is_audit', '=', 0 ],
                    ];
                    $result = $this->modifyAuditEvaluate($audit_data, $audit_condition);
                    if($result['code'] < 0){
                        return $result;
                    }
                }
            }
            //修改订单表中的评价标识
            model("order")->update(['is_evaluate' => 1, 'evaluate_status' => 1, 'evaluate_status_name' => $this->evaluate_status[1]], [['order_id', '=', $data['order_id']]]);
//            $evaluate_id = model('goods_evaluate')->addList($data_arr);
            Cache::tag("goods_evaluate")->clear();
            return $this->success();
        } else {
            return $this->error([], '当前订单已评价');
        }

    }

    /**
     * 评价回复
     * @param $data
     * @return array
     */
    public function evaluateApply($data)
    {
        $res = model("goods_evaluate")->update($data, [['evaluate_id', '=', $data['evaluate_id']]]);
        Cache::tag("goods_evaluate")->clear();
        return $this->success($res);
    }

    /**
     * 追评
     * @param $data
     * @return array
     */
    public function evaluateAgain($data)
    {
        foreach ($data['goods_evaluate'] as $k => $v) {
            $item = [
                'order_id' => $data['order_id'],
                'order_goods_id' => $v['order_goods_id'],
                'goods_id' => $v['goods_id'],
                'sku_id' => $v['sku_id'],
                'again_content' => $v['again_content'],
                'again_images' => $v['again_images'],
                'again_time' => time()
            ];
            $res = model("goods_evaluate")->update($item, [['order_goods_id', '=', $v['order_goods_id']]]);
            if ($res) {
                model("goods")->setInc([['goods_id', '=', $v['goods_id']]], 'evaluate_zhuiping', 1);
                model("goods_sku")->setInc([['sku_id', '=', $v['sku_id']]], 'evaluate_zhuiping', 1);
            }
        }
        model("order")->update(['is_evaluate' => 0, 'evaluate_status' => 2, 'evaluate_status_name' => $this->evaluate_status[2]], [['order_id', '=', $data['order_id']]]);
        Cache::tag("goods_evaluate")->clear();
        return $this->success($res);
    }

    /**
     * 删除评价
     * @param $evaluate_id
     * @return array
     */
    public function deleteEvaluate($evaluate_id)
    {
        $info = model('goods_evaluate')->getInfo([['evaluate_id', '=', $evaluate_id]], 'goods_id,sku_id,explain_type,order_id,shop_desccredit,shop_servicecredit,shop_deliverycredit');

        $goods_evaluate = 0; //评价
        $goods_evaluate_haoping = 0; //好评
        $goods_evaluate_zhongping = 0; //中评
        $goods_evaluate_chaping = 0; //差评
        $goods_evaluate_shaitu = 0; //晒图

        $sku_evaluate = 0; //评价
        $sku_evaluate_haoping = 0; //好评
        $sku_evaluate_zhongping = 0; //中评
        $sku_evaluate_chaping = 0; //差评
        $sku_evaluate_shaitu = 0; //晒图

        $symbol = "-";
        if ($info['explain_type'] == 1) {
            //好评
            $goods_evaluate = 1; //评价
            $sku_evaluate = 1;
            $goods_evaluate_haoping = 1; //好评
            $sku_evaluate_haoping = 1;
        } elseif ($info['explain_type'] == 2) {
            //中评
            $goods_evaluate = 1; //评价
            $goods_evaluate_zhongping = 1; //中评
            $sku_evaluate_zhongping = 1;
        } elseif ($info['explain_type'] == 3) {
            //差评
            $goods_evaluate = 1; //评价
            $goods_evaluate_chaping = 1; //差评
            $sku_evaluate_chaping = 1;
        }

        if (!empty($info['images'])) {
            $goods_evaluate_shaitu = 1; //晒图
            $sku_evaluate_shaitu = 1;
        }

        Db::name('goods')->where([['goods_id', '=', $info['goods_id']]])
            ->update(
                [
                    "evaluate" => Db::raw('evaluate' . $symbol . $goods_evaluate),
                    "evaluate_shaitu" => Db::raw('evaluate_shaitu' . $symbol . $goods_evaluate_shaitu),
                    "evaluate_haoping" => Db::raw('evaluate_haoping' . $symbol . $goods_evaluate_haoping),
                    "evaluate_zhongping" => Db::raw('evaluate_zhongping' . $symbol . $goods_evaluate_zhongping),
                    "evaluate_chaping" => Db::raw('evaluate_chaping' . $symbol . $goods_evaluate_chaping),
                ]);
        Db::name('goods_sku')->where([['sku_id', '=', $info['sku_id']]])
            ->update(
                [
                    "evaluate" => Db::raw('evaluate' . $symbol . $sku_evaluate),
                    "evaluate_shaitu" => Db::raw('evaluate_shaitu' . $symbol . $sku_evaluate_shaitu),
                    "evaluate_haoping" => Db::raw('evaluate_haoping' . $symbol . $sku_evaluate_haoping),
                    "evaluate_zhongping" => Db::raw('evaluate_zhongping' . $symbol . $sku_evaluate_zhongping),
                    "evaluate_chaping" => Db::raw('evaluate_chaping' . $symbol . $sku_evaluate_chaping),
                ]);

        //计算商家的评价分值
        $shop_data = [
            'shop_desccredit' => $info['shop_desccredit'],
            'shop_servicecredit' => $info['shop_servicecredit'],
            'shop_deliverycredit' => $info['shop_deliverycredit'],
        ];
        $this->shopEvaluate($info['order_id'], $shop_data);

        $res = model('goods_evaluate')->delete(['evaluate_id' => $evaluate_id]);
        Cache::tag("goods_evaluate")->clear();
        return $this->success($res);
    }

    /**
     * 获取评价信息
     * @param $condition
     * @param $field
     * @param $order
     * @return \multitype
     */
    public function getFirstEvaluateInfo($condition, $field = 'evaluate_id,order_goods_id,goods_id,sku_id,sku_name,sku_price,content,images,explain_first,member_name,member_headimg,member_id,is_anonymous,again_content,again_images,again_explain,create_time,again_time', $order = "create_time desc")
    {
        $data = json_encode([$condition, $field]);
        $cache = Cache::get("goods_evaluate_getFirstEvaluateInfo_" . $data);
        if (!empty($cache)) {
            return $this->success($cache);
        }
        $info = model('goods_evaluate')->getFirstData($condition, $field, $order);
        if (!empty($info)) {
            //匿名评价的话不显示用户昵称
            if ($info['is_anonymous'] == 1) {
                $item_member_name = $info['member_name'] ?? '';
                $info['member_name'] = mb_substr($item_member_name, 0, 1, 'utf-8') . '***' . mb_substr($item_member_name, -1, 1, 'utf-8') . '(匿名)';
            }
        }
        Cache::tag("goods_evaluate")->set("goods_evaluate_getFirstEvaluateInfo_" . $data, $info);
        return $this->success($info);
    }

    /**
     * 获取评价列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param string $limit
     */
    public function getEvaluateList($condition = [], $field = 'evaluate_id, order_id, order_no, order_goods_id, goods_id, sku_id, sku_name, sku_price, sku_image, content, images, explain_first, member_name, member_id, is_anonymous, scores, again_content, again_images, again_explain, explain_type, is_show, create_time, again_time,shop_desccredit,shop_servicecredit,shop_deliverycredit', $order = '', $limit = null)
    {
        $data = json_encode([$condition, $field, $order, $limit]);
        $cache = Cache::get("goods_evaluate_getEvaluateList_" . $data);
        if (!empty($cache)) {
            return $this->success($cache);
        }
        $list = model('goods_evaluate')->getList($condition, $field, $order, '', '', '', $limit);
        Cache::tag("goods_evaluate")->set("goods_evaluate_getEvaluateList_" . $data, $list);
        return $this->success($list);
    }

    /**
     * 获取评价分页列表
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getEvaluatePageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'create_time desc', $field = 'evaluate_id, order_id, order_no, order_goods_id, goods_id, sku_id, sku_name, sku_price, sku_image, content, images, explain_first, member_name,member_headimg, member_id, is_anonymous, scores, again_content, again_images, again_explain, explain_type, is_show, create_time, again_time,shop_desccredit,shop_servicecredit,shop_deliverycredit,is_audit')
    {
        $data = json_encode([$condition, $field, $order, $page, $page_size]);
        $cache = Cache::get("goods_evaluate_getEvaluatePageList_" . $data);
        if (!empty($cache)) {
            return $this->success($cache);
        }
        $list = model('goods_evaluate')->pageList($condition, $field, $order, $page, $page_size);
        foreach ($list['list'] as $k => $v) {
            //匿名评价的话不显示用户昵称
            if ($v['is_anonymous'] == 1) {
                $item_member_name = $list['list'][$k]['member_name'] ?? '';
                $list['list'][$k]['member_name'] = mb_substr($item_member_name, 0, 1, 'utf-8') . '***' . mb_substr($item_member_name, -1, 1, 'utf-8') . '(匿名)';
            }
        }
        Cache::tag("goods_evaluate")->set("goods_evaluate_getEvaluatePageList_" . $data, $list);
        return $this->success($list);
    }


    public function modifyAuditEvaluate($data, $condition = [])
    {
        $list = model("goods_evaluate")->getList($condition, 'goods_id,sku_id,is_audit,explain_type,images,order_id,shop_desccredit,shop_servicecredit,shop_deliverycredit');
        if (!empty($list)) {

            $goods_evaluate = 0; //评价
            $goods_evaluate_haoping = 0; //好评
            $goods_evaluate_zhongping = 0; //中评
            $goods_evaluate_chaping = 0; //差评
            $goods_evaluate_shaitu = 0; //晒图

            $sku_evaluate = 0; //评价
            $sku_evaluate_haoping = 0; //好评
            $sku_evaluate_zhongping = 0; //中评
            $sku_evaluate_chaping = 0; //差评
            $sku_evaluate_shaitu = 0; //晒图

            foreach ($list as $k => $v) {

                if ($data['is_audit'] == 1) {
                    $symbol = "+";
                    if ($v['explain_type'] == 1) {
                        //好评
                        $goods_evaluate = 1; //评价
                        $sku_evaluate = 1;
                        $goods_evaluate_haoping = 1; //好评
                        $sku_evaluate_haoping = 1;
                    } elseif ($v['explain_type'] == 2) {
                        //中评
                        $goods_evaluate = 1; //评价
                        $goods_evaluate_zhongping = 1; //中评
                        $sku_evaluate_zhongping = 1;
                    } elseif ($v['explain_type'] == 3) {
                        //差评
                        $goods_evaluate = 1; //评价
                        $goods_evaluate_chaping = 1; //差评
                        $sku_evaluate_chaping = 1;
                    }

                    if (!empty($v['images'])) {
                        $goods_evaluate_shaitu = 1; //晒图
                        $sku_evaluate_shaitu = 1;
                    }

                    Db::name('goods')->where([['goods_id', '=', $v['goods_id']]])
                        ->update(
                            [
                                "evaluate" => Db::raw('evaluate' . $symbol . $goods_evaluate),
                                "evaluate_shaitu" => Db::raw('evaluate_shaitu' . $symbol . $goods_evaluate_shaitu),
                                "evaluate_haoping" => Db::raw('evaluate_haoping' . $symbol . $goods_evaluate_haoping),
                                "evaluate_zhongping" => Db::raw('evaluate_zhongping' . $symbol . $goods_evaluate_zhongping),
                                "evaluate_chaping" => Db::raw('evaluate_chaping' . $symbol . $goods_evaluate_chaping),
                            ]);
                    Db::name('goods_sku')->where([['sku_id', '=', $v['sku_id']]])
                        ->update(
                            [
                                "evaluate" => Db::raw('evaluate' . $symbol . $sku_evaluate),
                                "evaluate_shaitu" => Db::raw('evaluate_shaitu' . $symbol . $sku_evaluate_shaitu),
                                "evaluate_haoping" => Db::raw('evaluate_haoping' . $symbol . $sku_evaluate_haoping),
                                "evaluate_zhongping" => Db::raw('evaluate_zhongping' . $symbol . $sku_evaluate_zhongping),
                                "evaluate_chaping" => Db::raw('evaluate_chaping' . $symbol . $sku_evaluate_chaping),
                            ]);

                    //计算商家的评价分值
                    $shop_data = [
                        'shop_desccredit' => $v['shop_desccredit'],
                        'shop_servicecredit' => $v['shop_servicecredit'],
                        'shop_deliverycredit' => $v['shop_deliverycredit'],
                    ];
                    $this->shopEvaluate($v['order_id'], $shop_data);
                }
            }
        }
        $res = model("goods_evaluate")->update($data, $condition);
        Cache::tag("goods_evaluate")->clear();
        return $this->success($res);
    }

    /**
     * 修改评价
     * @param $data
     * @param array $condition
     * @return array
     */
    public function editEvaluate($data, $condition = [])
    {
        $res = model("goods_evaluate")->update($data, $condition);
        Cache::tag("goods_evaluate")->clear();
        return $this->success($res);
    }

    /******************************************************************** 计算店铺分数 **********************************************************************/

    /**
     * 商品评价后 计算店铺评价
     * @param $site_id
     * @param $evaluate
     * @param $num
     */
    public function shopEvaluate($order_id, $evaluate)
    {
        $order_info = model("order")->getInfo([["order_id", "=", $order_id]], "site_id");
        $site_id = $order_info["site_id"];
        $num = model("order_goods")->getCount([["order_id", "=", $order_id]], "order_goods_id");
        $count = model("goods_evaluate")->getCount([["site_id", "=", $site_id]], "evaluate_id");
        $shop_model = new Shop();
        $shop_info_result = $shop_model->getShopInfo([["site_id", "=", $site_id]], "shop_desccredit,shop_servicecredit,shop_deliverycredit,shop_status");
        $shop_info = $shop_info_result["data"];
        $last_count = $count + $num;
        //控制非法分值
        foreach ($evaluate as $k => $v) {
            if ($v < 0) {
                $v = 0;
            }
            if ($v > 5) {
                $v = 5;
            }
            $evaluate[$k] = $v;
        }
        $data = [
            'shop_status' => $shop_info['shop_status']
        ];
        if ($evaluate["shop_desccredit"] > 0) {
            $avg_desccredit = ($shop_info["shop_desccredit"] * $count + $evaluate["shop_desccredit"] * $num) / $last_count;
            $data["shop_desccredit"] = $avg_desccredit;
        }
        if ($evaluate["shop_servicecredit"] > 0) {
            $avg_servicecredit = ($shop_info["shop_servicecredit"] * $count + $evaluate["shop_servicecredit"] * $num) / $last_count;
            $data["shop_servicecredit"] = $avg_servicecredit;
        }
        if ($evaluate["shop_deliverycredit"] > 0) {
            $avg_deliverycredit = ($shop_info["shop_deliverycredit"] * $count + $evaluate["shop_deliverycredit"] * $num) / $last_count;
            $data["shop_deliverycredit"] = $avg_deliverycredit;
        }
        if (!empty($data)) {
            $result = $shop_model->editShop($data, [["site_id", "=", $site_id]]);
        } else {
            $result = $this->success();
        }

        return $result;
    }
    /******************************************************************** 计算店铺分数 **********************************************************************/

}