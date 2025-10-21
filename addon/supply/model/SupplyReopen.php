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

namespace addon\supply\model;

use app\model\BaseModel;
use Exception;

/**
 * 店铺续签表
 */
class SupplyReopen extends BaseModel
{
    /**
     * @param $data
     * @return array
     */
    public function addReopen($data)
    {
        //查询是否已有申请记录
        $reopen = $this->getReopenList([['site_id', '=', $data['site_id']], ['apply_state', '=', 1]], 'id');
        if (!empty($reopen['data'])) {
            return $this->error('', '请勿重复申请续签');
        }
        //查询店铺的结束时间
        $supplier_model  = new Supplier();
        $supply        = $supplier_model->getSupplierInfo([['supplier_site_id', '=', $data['site_id']]], 'expire_time');
        $expire_time = $supply['data']['expire_time'];
        if ($expire_time == 0) {
            return $this->error('', '您的供应商已永久授权，不需要申请续签');
        }
        if ($expire_time > time()) {
            $cha  = $expire_time - time();
            $date = ceil(($cha / 86400));
            if ($date > 30) {
                return $this->error('', '离到期30天内才可以申请续签');
            }
        }

        $data['create_time'] = time();
        $data['apply_state'] = 1;
        $data['reopen_no']   = date('YmdHi') . rand(1111, 9999);
        $res                 = model("supply_reopen")->add($data);
        return $this->success($res);
    }

    /**
     * 获取续签申请金额
     * @param $apply_year
     * @param $category_id
     * @return array
     */
    public function getReopenMoney($apply_year)
    {
        $config_model = new Config();
        $config = $config_model->getSupplyConfig()['data']['value'] ?? [];
        $fee = $config['fee'] ?? 0;
        return $this->success(['money'=>$fee * $apply_year,'fee' => $fee]);
    }

    /**
     * 编辑续签信息
     * @param $data
     * @return array
     */
    public function editReopen($data)
    {
        $data['apply_state'] = 1;
        $res = model("supply_reopen")->update($data, [['id', '=', $data['id'], ['site_id', '=', $data['site_id']]]]);
        return $this->success($res);
    }

    /**
     * 拒绝续签
     * @param $id
     * @param $reason
     * @return array
     */
    public function refuse($id, $reason)
    {
        $res = model("supply_reopen")->update(['apply_message' => $reason, 'apply_state' => -1], [['id', '=', $id]]);
        return $this->success($res);
    }

    /**
     * 续签通过
     * @param $id
     * @param $site_id
     * @return array
     */
    public function pass($id, $site_id)
    {
        $data = [
            'apply_state' => 2,
            'audit_time'  => time()
        ];
        model("supply_reopen")->startTrans();
        try {
            $supplier_info   = model("supplier")->getInfo([['supplier_site_id', '=', $site_id]], '*');
            $reopen_info = model("supply_reopen")->getInfo([['id', '=', $id], ['site_id', '=', $site_id]]);

            model("supply_reopen")->update($data, [['id', '=', $id], ['site_id', '=', $site_id]]);

            model("supplier")->setInc([['supplier_site_id', '=', $site_id]], "open_fee", $reopen_info['paying_amount']);

            if($supplier_info['expire_time'] >= time()){
                $expire_time = strtotime('+' . $reopen_info['apply_year'] . 'year', $supplier_info['expire_time']);
            }else{
                $expire_time = strtotime('+' . $reopen_info['apply_year'] . 'year', time());
            }
            //修改店铺信息
            $shop_data = [
                'status' => 1,
                'expire_time' => $expire_time,
            ];
            model('supplier')->update($shop_data, [['supplier_site_id', '=', $site_id]]);

            //添加入驻费用流水
            if (floatval($reopen_info['paying_amount']) > 0) {
                $open_data = [
                    'account_no'  => $reopen_info['reopen_no'],
                    'site_id'     => $reopen_info['site_id'],
                    'site_name'   => $supplier_info['title'],
                    'money'       => floatval($reopen_info['paying_amount']),
                    'type'        => 2,
                    'type_name'   => '供应商续签费用',
                    'relate_id'   => $reopen_info['id'],
                    'create_time' => time(),
                ];
                model('supply_open_account')->add($open_data);
            }
            model("supply_reopen")->commit();
            return $this->success();
        } catch (Exception $e) {
            model("supply_reopen")->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 续签申请通过事件执行
     * @param $id
     * @return array
     */
    public function cronSupplyReopen($id)
    {
        $reopen_info = model("supply_reopen")->getInfo([['id', '=', $id]], '*');

        if ($reopen_info['apply_state'] == 2) {
            try {
                $data = [
                    'expire_time' => $reopen_info['expire_time'] + 365 * 24 * 3600 * $reopen_info['apply_year'],
                ];
                $res  = model("supplier")->update($data, [['supplier_site_id', '=', $reopen_info['site_id']]]);
                return $this->success($res);
            } catch (Exception $e) {
                return $this->error($e->getMessage());
            }
        } else {
            return $this->error("unknow Reopen info");
        }
    }

    /**
     * 获取申请信息
     * @param $condition
     * @param string $field
     * @return array
     */
    public function getReopenInfo($condition, $field = '*')
    {
        $field = $field == '*' ? 'sr.*,s.title' : $field;
        $alias = 'sr';
        $join  = [
            [
                'supplier s',
                's.supplier_site_id = sr.site_id',
                'left'
            ],
        ];
        $info  = model("supply_reopen")->getInfo($condition, $field, $alias, $join);
        return $this->success($info);
    }

    /**
     * 获取申请列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param null $limit
     * @return array
     */
    public function getReopenList($condition = [], $field = '*', $order = '', $limit = null)
    {

        $list = model('supply_reopen')->getList($condition, $field, $order, '', '', '', $limit);
        return $this->success($list);
    }

    /**
     * 获取店铺分页列表
     * @param array $where
     * @param int $page
     * @param int $size
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getReopenPageList($where = [], $page = 1, $size = PAGE_LIST_ROWS, $order = 'create_time desc', $field = '*')
    {

        $list = model('supply_reopen')->pageList($where, $field, $order, $page, $size);
        return $this->success($list);
    }


    /**
     * 后台获取店铺申请续签分页列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @return array
     */
    public function getApplyReopenPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS)
    {
        $field = 'r.*,s.title,s.category_name,r.paying_money_certificate';
        $alias = 'r';
        $join  = [
            [
                'supplier s',
                'r.site_id = s.supplier_site_id',
                'left'
            ],

        ];
        $list  = model("supply_reopen")
            ->pageList($condition, $field, 'r.create_time desc', $page, $page_size, $alias, $join);
        return $this->success($list);
    }


    /*
     *  删除续签
     */
    public function deleteReopen($id)
    {
        $reopen = model("supply_reopen")->getInfo([['id', '=', $id]]);
        if (in_array($reopen['apply_state'], [1, -1])) {
            $res = model("supply_reopen")->delete([['id', '=', $id]]);
            return $this->success($res);
        } else {
            return $this->error("error");
        }
    }

    /**
     * 续签数量
     * @param $condition
     * @return array
     */
    public function getApplyReopenCount($condition)
    {
        $count = model("supply_reopen")->getCount($condition);
        return $this->success($count);
    }
}
