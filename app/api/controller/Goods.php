<?php

namespace app\api\controller;

use app\model\goods\Goods as GoodsModel;
use app\model\system\Poster;
use app\model\goods\Config as GoodsConfigModel;
use app\model\web\Config as WebConfig;

class Goods extends BaseApi
{

    /**
     * 修改商品点击量
     * @return string
     */
    public function modifyclicks()
    {
        $sku_id = isset($this->params[ 'sku_id' ]) ? $this->params[ 'sku_id' ] : 0;

        if (empty($sku_id)) {
            return $this->response($this->error('', 'REQUEST_SKU_ID'));
        }

        $goods_model = new GoodsModel();
        $res = $goods_model->modifyClick($sku_id);
        return $this->response($res);
    }

    /**
     * 获取商品海报
     */
    public function poster()
    {
        if (!empty($qrcode_param)) return $this->response($this->error('', '缺少必须参数qrcode_param'));

        $promotion_type = 'null';
        $qrcode_param = json_decode($this->params[ 'qrcode_param' ], true);

        //todo  小程序链接限制url长度,所以用suid
        $qrcode_param[ 'suid' ] = $qrcode_param[ 'suid' ] ?? 0;
        $poster = new Poster();
        $res = $poster->goods($this->params[ 'app_type' ], $this->params[ 'page' ], $qrcode_param, $promotion_type);
        return $this->response($res);
    }

    /**
     * 售后保障
     * @return false|string
     */
    public function aftersale()
    {
        $site_id = isset($this->params[ 'site_id' ]) ? $this->params[ 'site_id' ] : '';
        if (empty($site_id)) {
            return $this->response($this->error('', '缺少必须参数site_id'));
        }

        $goods_config_model = new GoodsConfigModel();
        $res = $goods_config_model->getAfterSaleConfig($site_id);
        return $this->response($res);
    }

    /**
     * 获取热门搜索关键词
     */
    public function hotSearchWords()
    {
        $config_model = new WebConfig();
        $info = $config_model->getBasicConfig();
        return $this->response($this->success($info[ 'data' ][ 'value' ]));
    }

    /**
     * 获取默认搜索关键词
     */
    public function defaultSearchWords()
    {
        $config_model = new WebConfig();
        $info = $config_model->getDefaultSearchWords(0, 'admin');
        return $this->response($this->success($info[ 'data' ][ 'value' ]));
    }

}