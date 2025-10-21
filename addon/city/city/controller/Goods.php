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

namespace addon\city\city\controller;

use app\model\goods\Goods as GoodsModel;
use app\model\goods\Config as GoodsConfigModel;
use app\model\goods\GoodsCategory as GoodsCategoryModel;
use app\model\goods\GoodsEvaluate;
use app\model\express\ExpressTemplate as ExpressTemplateModel;
use app\model\goods\GoodsBrowse as GoodsBrowseModel;

/**
 * 商品管理 控制器
 */
class Goods extends BaseCity
{
    /******************************* 正常商品列表及相关操作 ***************************/

    /**
     * 商品列表
     */
    public function lists()
    {
        $goods_model = new GoodsModel();
        if (request()->isAjax()) {
            $page_index = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $search_text = input('search_text', "");
            $search_text_type = input('search_text_type', "goods_name");
            $goods_state = input('goods_state', "");
            $verify_state = input('verify_state', "");
            $category_id = input('category_id', "");
            $brand_id = input('goods_brand', '');
            $goods_attr_class = input("goods_attr_class", "");
            $site_id = input("site_id", "");
            $goods_class = input('goods_class', "");
            $order = input('order', '');
            $sort = input('sort', '');
            $promotion_type = input('promotion_type', "");
            $start_sale = input('start_sale', 0);
            $end_sale = input('end_sale', 0);

            if (empty($order)) {
                $order_by = 'sort desc,create_time desc';
            } else {
                $order_by = $order . ' ' . $sort;
            }

            $condition = [ [ 'is_delete', '=', 0 ], [ 'website_id', '=', $this->site_id ] ];
            if (!empty($search_text)) {
                $condition[] = [ $search_text_type, 'like', '%' . $search_text . '%' ];
            }

            if ($goods_class !== "") {
                $condition[] = [ 'goods_class', '=', $goods_class ];
            }
            if ($goods_state !== '') {
                $condition[] = [ 'goods_state', '=', $goods_state ];
            }
            if ($verify_state !== '') {
                $condition[] = [ 'verify_state', '=', $verify_state ];
            }
            if (!empty($category_id)) {
                $condition[] = [ 'category_id', 'like', '%,' . $category_id . ',%' ];
            }
            if ($brand_id) {
                $condition[] = [ 'brand_id', '=', $brand_id ];
            }
            if ($goods_attr_class) {
                $condition[] = [ 'goods_attr_class', '=', $goods_attr_class ];
            }
            if (!empty($site_id)) {
                $condition[] = [ 'site_id', '=', $site_id ];
            }
            //参与活动
            if (!empty($promotion_type)) {
                $condition[] = [ 'promotion_addon', 'like', "%{$promotion_type}%" ];
            }
            //销量
            if (!empty($start_sale)) $condition[] = [ 'sale_num', '>=', $start_sale ];
            if (!empty($end_sale)) $condition[] = [ 'sale_num', '<=', $end_sale ];

            $res = $goods_model->getGoodsPageList($condition, $page_index, $page_size, $order_by);
            if (!empty($res[ 'data' ][ 'list' ])) {
                $goods_promotion_type = event('GoodsPromotionType');
                foreach ($res[ 'data' ][ 'list' ] as $k => $v) {
                    if (!empty($v[ 'promotion_addon' ])) {
                        $v[ 'promotion_addon' ] = json_decode($v[ 'promotion_addon' ], true);
                        foreach ($v[ 'promotion_addon' ] as $ck => $cv) {
                            foreach ($goods_promotion_type as $gk => $gv) {
                                if ($gv[ 'type' ] == $ck) {
                                    $res[ 'data' ][ 'list' ][ $k ][ 'promotion_addon_list' ][] = $gv;
                                    break;
                                }
                            }
                        }
                    }
                }
            }
            return $res;
        } else {
            $verify_state = $goods_model->getVerifyState();
            $arr = [];
            foreach ($verify_state as $k => $v) {
                // 过滤已审核状态
                if ($k != 1) {
                    $total = $goods_model->getGoodsTotalCount([ [ 'verify_state', '=', $k ], [ 'is_delete', '=', 0 ] ]);
                    $total = $total[ 'data' ];
                    $arr[] = [
                        'state' => $k,
                        'value' => $v,
                        'count' => $total
                    ];
                }
            }
            $verify_state = $arr;
            $this->assign("verify_state", $verify_state);

            //获取运费模板
            $express_template_model = new ExpressTemplateModel();
            $express_template_list = $express_template_model->getExpressTemplateList([], 'template_id,template_name', 'is_default desc');
            $express_template_list = $express_template_list[ 'data' ];
            $this->assign("express_template_list", $express_template_list);

            //获取一级商品分类
            $goods_category_model = new GoodsCategoryModel();
            $condition = [
                [ 'pid', '=', 0 ]
            ];

            $goods_category_list = $goods_category_model->getCategoryList($condition, 'category_id,category_name,level,commission_rate');
            $goods_category_list = $goods_category_list[ 'data' ];
            $this->assign("goods_category_list", $goods_category_list);

            // 营销活动
            $goods_promotion_type = event('GoodsPromotionType');
            $this->assign('promotion_type', $goods_promotion_type);

            return $this->fetch('goods/lists', [], $this->replace);
        }
    }

    /**
     * 刷新审核状态商品数量
     */
    public function refreshVerifyStateCount()
    {
        if (request()->isAjax()) {
            $goods_model = new GoodsModel();
            $verify_state = $goods_model->getVerifyState();
            $arr = [];
            foreach ($verify_state as $k => $v) {
                // 过滤已审核状态
                if ($k != 1) {
                    $total = $goods_model->getGoodsTotalCount([ [ 'verify_state', '=', $k ], [ 'is_delete', '=', 0 ], [ 'website_id', '=', $this->site_id ] ]);
                    $total = $total[ 'data' ];
                    $arr[] = [
                        'state' => $k,
                        'value' => $v,
                        'count' => $total
                    ];
                }
            }
            $verify_state = $arr;
            return $verify_state;
        }
    }

    /**
     * 获取SKU商品列表
     * @return \multitype
     */
    public function getGoodsSkuList()
    {
        if (request()->isAjax()) {
            $goods_id = input("goods_id", 0);
            $goods_model = new GoodsModel();
            $res = $goods_model->getGoodsSkuList([ [ 'goods_id', '=', $goods_id ] ], 'sku_id,sku_name,price,stock,sale_num,sku_image,spec_name');
            return $res;
        }
    }

    /**
     * 商品浏览记录
     */
    public function goodsBrowse()
    {
        $goods_id = input('goods_id', 0);
        $goods_browse_model = new GoodsBrowseModel();
        if (request()->isAjax()) {
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $search = input('search', '');
            $condition = [];
            if ($goods_id > 0) {
                $condition[] = [ 'gb.goods_id', '=', $goods_id ];
            }
            if (!empty($search))
                $condition[] = [ 'gs.sku_name', 'like', '%' . $search . '%' ];

            $order = 'browse_time desc';
            $field = 'gb.*,gs.sku_name,gs.sku_image,gs.price,gs.goods_state,gs.stock,gs.click_num,m.nickname,m.headimg';
            $alias = 'gb';
            $join = [
                [ 'goods_sku gs', 'gs.sku_id = gb.sku_id', 'left' ],
                [ 'member m', 'm.member_id = gb.member_id', 'left' ]
            ];
            return $goods_browse_model->getBrowsePageList($condition, $page, $page_size, $order, $field, $alias, $join);
        } else {
            $this->assign('goods_id', $goods_id);
            return $this->fetch('goods/goods_browse', [], $this->replace);
        }
    }
    /******************************* 违规下架商品列表及相关操作 ***************************/

    /**
     * 违规下架
     */
    public function lockup()
    {
        if (request()->isAjax()) {
            $verify_state_remark = input("verify_state_remark", 0);
            $goods_ids = input("goods_ids", 0);
            $goods_model = new GoodsModel();
            $res = $goods_model->lockup([ [ 'goods_id', 'in', (string) $goods_ids ], [ 'website_id', '=', $this->site_id ] ], $verify_state_remark);
            $this->addLog("商品违规下架id:" . $goods_ids . "原因:" . $verify_state_remark);
            return $res;
        }
    }

    /**
     * 获取商品违规或审核失败说明
     * @return \multitype
     */
    public function getVerifyStateRemark()
    {
        if (request()->isAjax()) {
            $goods_id = input("goods_id", 0);
            $goods_model = new GoodsModel();
            $res = $goods_model->getGoodsInfo([ [ 'goods_id', '=', $goods_id ], [ 'verify_state', 'in', [ -2, 10 ] ] ], 'verify_state_remark');
            return $res;
        }
    }

    /******************************* 待审核商品列表及相关操作 ***************************/

    /**
     * 商品审核
     */
    public function verifyOn()
    {
        if (request()->isAjax()) {
            $goods_ids = input("goods_ids", 0);
            $verify_state = input("verify_state", -2);
            $verify_state_remark = input("verify_state_remark", '');
            $goods_model = new GoodsModel();
            $res = $goods_model->modifyVerifyState($goods_ids, $verify_state, $verify_state_remark);
            return $res;
        }

    }

    /**
     * 审核设置
     */
    public function verifyConfig()
    {
        if (request()->isAjax()) {

            $is_open = input("is_open", 0);
            $data = [
                'is_open' => $is_open
            ];
            $goods_config = new GoodsConfigModel();
            $res = $goods_config->setVerifyConfig($data);
            return $res;
        } else {
            $goods_config = new GoodsConfigModel();
            $goods_verify_info = $goods_config->getVerifyConfig();
            $goods_verify_info = $goods_verify_info[ 'data' ];
            $this->assign("goods_verify_info", $goods_verify_info[ 'value' ]);
            return $this->fetch('goods/verify_config', [], $this->replace);
        }
    }

    /******************************* 商品评价列表及相关操作 ***************************/

    /**
     * 商品评价
     */
    public function evaluateList()
    {
        if (request()->isAjax()) {
            $page_index = input('page', 1);
            $page_size = input('limit', PAGE_LIST_ROWS);
            $site_id = input("site_id", "");
            $explain_type = input('explain_type', ''); //1好评2中评3差评
            $search_text = input('search_text', "");
            $search_type = input('search_type', "sku_name");

            $condition[] = [ 'website_id', '=', $this->site_id ];
            //评分类型
            if ($explain_type != "") {
                $condition[] = [ "explain_type", "=", $explain_type ];
            }
            if (!empty($search_text)) {
                if (!empty($search_type)) {
                    $condition[] = [ $search_type, 'like', '%' . $search_text . '%' ];
                } else {
                    $condition[] = [ 'sku_name', 'like', '%' . $search_text . '%' ];
                }
            }
            if (!empty($site_id)) {
                $condition[] = [ 'site_id', '=', $site_id ];
            }

            $evaluate_model = new GoodsEvaluate();
            $res = $evaluate_model->getEvaluatePageList($condition, $page_index, $page_size);
            return $res;
        } else {
            return $this->fetch('goods/evaluate_list', [], $this->replace);
        }
    }

    /**
     * 评价删除
     */
    public function deleteEvaluate()
    {
        if (request()->isAjax()) {
            $id = input('id', '');
            $evaluate_model = new GoodsEvaluate();
            $res = $evaluate_model->deleteEvaluate($id);
            $this->addLog("删除商品评价id:" . $id);
            return $res;
        }
    }

    /**
     * 商品推广
     * return
     */
    public function goodsUrl()
    {
        $goods_id = input('goods_id', '');
        $goods_model = new GoodsModel();
        $goods_sku_info = $goods_model->getGoodsSkuInfo([ [ 'goods_id', '=', $goods_id ] ], 'sku_id,goods_name');
        $goods_sku_info = $goods_sku_info[ 'data' ];
        $res = $goods_model->qrcode($goods_sku_info[ 'sku_id' ], $goods_sku_info[ 'goods_name' ]);
        return $res;
    }

    /**
     * 商品预览
     * return
     */
    public function goodsPreview()
    {
        $goods_id = input('goods_id', '');
        $goods_model = new GoodsModel();
        $goods_sku_info = $goods_model->getGoodsSkuInfo([ [ 'goods_id', '=', $goods_id ] ], 'sku_id,goods_name');
        $goods_sku_info = $goods_sku_info[ 'data' ];
        $res = $goods_model->qrcode($goods_sku_info[ 'sku_id' ], $goods_sku_info[ 'goods_name' ]);
        return $res;
    }



    /**************************************************************** 评价 ********************************************************/
    /**
     * 修改商品评价审核状态
     */
    public function modifyAuditEvaluate()
    {
        if (request()->isAjax()) {
            $goods_evaluate = new GoodsEvaluate();
            $evaluate_ids = input("evaluate_ids", '');
            $is_audit = input('is_audit', 0);
            $data = [
                'is_audit' => $is_audit
            ];
            $condition = [
                [ 'evaluate_id', 'in', $evaluate_ids ],
                [ 'is_audit', '=', 0 ],
                [ 'website_id', '=', $this->site_id ]
            ];
            $res = $goods_evaluate->modifyAuditEvaluate($data, $condition);
            return $res;
        }
    }

    /**************************************************************** 评价 ********************************************************/
}