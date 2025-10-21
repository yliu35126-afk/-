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

namespace addon\supply\supply\controller;

use addon\supply\model\Supplier;
use addon\supply\model\SupplyService as SupplyServiceModel;

/**
 * 供应商服务
 * Class Services
 * @package addon\supply\supply\controller
 */
class Services extends BaseSupply
{
    /**
     * 服务首页
     */
    public function lists()
    {
        $service_model = new SupplyServiceModel();

        $service_name_arr = $service_model->serviceApplyList($this->supply_id);

        $this->assign('service_name_arr', $service_name_arr);

        return $this->fetch("services/lists");
    }

    /**
     * 服务申请
     */
    public function apply()
    {
        if (request()->isAjax()) {
            $model = new SupplyServiceModel();
            //查询对应的服务类型列表
            $service_name_arr = $model->getServiceNameList();

            $service_key = input("service_key", "");

            $key = array_search($service_key, array_column($service_name_arr, 'key'));

            $service_type_name = $service_name_arr[$key]['name'];
            $service_type_key  = $service_name_arr[$key]['key'];
            //获取供应商信息
            $supplier        = new Supplier();
            $info   = $supplier->getSupplierInfo([['supplier_site_id', '=', $this->supply_id]], 'title');
            $reopen_data = [
                'site_id'           => $this->supply_id,//供应商ID
                'site_name'         => $info['data']['title'],
                'service_type'      => $service_type_key,
                'service_type_name' => $service_type_name,
                'status'            => 0,
                'create_time'       => time()
            ];
            $result      = $model->ServiceApply($reopen_data);
            return $result;
        }
    }

    /**
     * 服务退出
     * @return array|void
     */
    public function quit()
    {
        if (request()->isAjax()) {
            $service_model = new SupplyServiceModel();
            $service_key        = input("service_key", "");
            $data               = [$service_key => 0];
            $result             = $service_model->ServiceQuit($data, $this->supply_id);
            return $result;
        }
        return $this->error('fail');
    }
}
