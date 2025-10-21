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

namespace addon\printer\shop\controller;

use app\shop\controller\BaseShop;
use addon\printer\model\PrinterTemplate;

class Template extends BaseShop
{
    /*
     *  模板管理列表
     */
    public function lists()
    {
        $model = new PrinterTemplate();

        if (request()->isAjax()) {

            $condition[] = [ 'site_id', '=', $this->site_id ];
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $list = $model->getPrinterTemplatePageList($condition, $page, $page_size, 'template_id desc');
            return $list;
        }
        return $this->fetch("template/lists");
    }

    /**
     * 添加模板管理
     */
    public function add()
    {
        if (request()->isAjax()) {
            $data = [
                'site_id' => $this->site_id,
                'site_name' => $this->shop_info['site_name'],
                'template_type' => input('template_type',''),
                'template_name' => input('template_name',''),

                'title' => input('title',''),
                'head' => input('head',''),
                'buy_notes' => input('buy_notes',''),
//                'seller_notes' => input('seller_notes',''),
                'buy_name' => input('buy_name',''),
                'buy_mobile' => input('buy_mobile',''),
                'buy_address' => input('buy_address',''),
                'shop_mobile' => input('shop_mobile',''),
                'shop_address' => input('shop_address',''),
                'shop_qrcode' => input('shop_qrcode',''),
                'qrcode_url' => input('qrcode_url',''),
                'bottom' => input('bottom','')
            ];
            $model = new PrinterTemplate();
            return $model->addPrinterTemplate($data);

        } else {

            return $this->fetch("template/add");
        }
    }

    /**
     * 编辑模板管理
     */
    public function edit()
    {
        $model = new PrinterTemplate();
        $template_id = input('template_id',0);
        if (request()->isAjax()) {
            $data = [
                'template_id' => $template_id,
                'site_id' => $this->site_id,
                'template_type' => input('template_type',''),
                'template_name' => input('template_name',''),

                'title' => input('title',''),
                'head' => input('head',''),
                'buy_notes' => input('buy_notes',''),
//                'seller_notes' => input('seller_notes',''),
                'buy_name' => input('buy_name',''),
                'buy_mobile' => input('buy_mobile',''),
                'buy_address' => input('buy_address',''),
                'shop_mobile' => input('shop_mobile',''),
                'shop_address' => input('shop_address',''),
                'shop_qrcode' => input('shop_qrcode',''),
                'qrcode_url' => input('qrcode_url',''),
                'bottom' => input('bottom','')
            ];
            return $model->editPrinterTemplate($data);

        } else {

            $info = $model->getPrinterTemplateInfo([['template_id','=',$template_id],['site_id','=',$this->site_id]]);
            $this->assign('info',$info['data']);

            return $this->fetch("template/edit");
        }
    }

    /*
     *  删除
     */
    public function delete()
    {
        $template_id = input('template_id', '');

        $printer_model = new PrinterTemplate();
        return $printer_model->deletePrinterTemplate([ ['template_id','=',$template_id], ['site_id','=',$this->site_id] ]);
    }

}