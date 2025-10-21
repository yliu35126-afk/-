<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace addon\goodsgrab\model;

use app\model\system\Config;
use app\model\BaseModel;

/**
 * 商品采集记录
 */
class GoodsGrab extends BaseModel
{

    /**
     * 获取采集信息
     * @param $condition
     * @param string $field
     * @return array
     */
    public function getGoodsGrabInfo($condition, $field = '*')
    {
        $info = model('goods_grab')->getInfo($condition, $field);
        return $this->success($info);
    }

    /**
     * 获取商品采集记录
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param null $limit
     * @return array
     */
    public function getGoodsGrabList($condition = [], $field = '*', $order = 'grab_id desc', $limit = null)
    {
        $list = model('goods_grab')->getList($condition, $field, $order, '', '', '', $limit);
        return $this->success($list);
    }

    /**
     * 获取商品采集分页记录
     *
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getGoodsGrabPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'grab_id desc', $field = '*')
    {

        $list = model('goods_grab')->pageList($condition, $field, $order, $page, $page_size);
        return $this->success($list);
    }



    /**
     * 获取采集明细分页记录
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getGoodsGrabDetailPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*')
    {
        $list = model('goods_grab_detail')->pageList($condition, $field, $order, $page, $page_size);
        return $this->success($list);
    }

    /**
     * 删除采集信息
     * @param $condition
     * @param string $field
     * @return array
     */
    public function delGoodsGrab($condition)
    {
        model('goods_grab')->startTrans();
        try{
            //删除采集表信息
            model('goods_grab')->delete($condition);
            model('goods_grab_detail')->delete($condition);
            model('goods_grab')->commit();
            return $this->success();
        }catch(\Exception $e){
            model()->rollback();
            return $this->error('',$e->getMessage());
        }
    }

    /************************************ 商品采集设置 start **********************************************************/

    /**
     * 商品采集设置
     * @param $data
     * @return array
     */
    public function setGoodsGrabConfig($data,$site_id)
    {
        $config = new Config();
        $res    = $config->setConfig($data, '商品采集设置', 1, [['site_id', '=', $site_id], ['app_module', '=', 'shop'], ['config_key', '=', 'GOODSGRAB_CONFIG']]);
        return $this->success($res);
    }

    /**
     * 获取商品采集设置
     * @return array
     */
    public function getGoodsGrabConfig($site_id)
    {
        $config = new Config();
        $res    = $config->getConfig([['site_id', '=', $site_id], ['app_module', '=', 'shop'], ['config_key', '=', 'GOODSGRAB_CONFIG']]);
        if (empty($res['data']['value'])) {
            $res['data']['value'] = [
                'type' => '99api',
                'key' => ''
            ];
        }
        return $res;
    }

    /************************************ 商品采集设置 end **********************************************************/
}