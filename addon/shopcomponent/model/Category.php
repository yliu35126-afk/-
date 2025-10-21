<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com

 * =========================================================
 */

namespace addon\shopcomponent\model;

use addon\live\model\Live;
use addon\weapp\model\Config as WeappConfigModel;
use addon\wxoplatform\model\Config as WxOplatformConfigModel;
use app\model\BaseModel;
use app\model\system\Cron;
use app\model\upload\Upload;
use EasyWeChat\Factory;
use think\facade\Cache;
use think\facade\Log;

class Category extends BaseModel
{
    private $statusName = [
        '0' => '待提交',
        '1' => '审核成功',
        '9' => '审核拒绝'
    ];
    private $type = [
        '0' => '不需要',
        '1' => '需要',
        '2' => '可选'
    ];

    /**
     * 获取类目列表
     * @param array $condition
     * @param bool $field
     * @param string $order
     * @param int $page
     * @param int $list_rows
     * @param string $alias
     * @param array $join
     * @return array
     */
    public function getCategoryPageList($condition = [], $field = true, $order = '', $page = 1, $list_rows = PAGE_LIST_ROWS, $alias = 'a', $join = [])
    {
        $check_condition = array_column($condition, 2, 0);
        $site_id = isset($check_condition[ 'site_id' ]) ? $check_condition[ 'site_id' ] : 0;

        $site_id_index = array_search('site_id', array_keys(array_column($condition, 2, 0)));
        unset($condition[$site_id_index]);

        $data = model('shopcompoent_category')->pageList($condition, $field, $order, $page, $list_rows, $alias, $join);
        if (!empty($data['list'])) {
            foreach ($data['list'] as $k => $item) {
                $audit_info = model('shopcompoent_category_audit')->getInfo([ ['site_id', '=', $site_id], ['third_cat_id', '=', $item['third_cat_id'] ] ]);
                if (empty($audit_info)) {
                    $audit_info = [
                        'certificate' => '',
                        'qualification_pics' => '',
                        'audit_id' => '',
                        'audit_time' => 0,
                        'status' => 0,
                        'reject_reason' => ''
                    ];
                }

                $item = array_merge($item, $audit_info);
                $data['list'][$k] = $item;

                if($item['audit_time']>0 && $item['status']==0){
                    $data['list'][$k]['status_name'] = '审核中';
                }else if($item['qualification_type']==0 && $item['product_qualification_type']==0){
                    $data['list'][$k]['status_name'] = '--';
                }else{
                    $data['list'][$k]['status_name'] = $this->statusName[$item['status']] ?? '';
                }
                $data['list'][$k]['create_time'] = $item['create_time']>0 ? time_to_date($item['create_time']) : '--';
                $data['list'][$k]['audit_time'] = $item['audit_time']>0 ? time_to_date($item['audit_time']) : '--';
                $data['list'][$k]['leimu'] = $item['first_cat_name'].'>'.$item['second_cat_name'].'>'.$item['third_cat_name'];
                $data['list'][$k]['product_qualification_type_name'] = $this->type[$item['product_qualification_type']] ?? '';
                $data['list'][$k]['qualification_type_name'] = $this->type[$item['qualification_type']] ?? '';
                $data['list'][$k]['license'] = Cache::get('license_'.$item['third_cat_id']);
                $data['list'][$k]['reject_reason'] = str_replace('https://developers.weixin.qq.com/miniprogram/dev/framework/ministore/minishopopencomponent2/API/audit/audit_brand.html','',$item['reject_reason']);
            }
        }

        return $this->success($data);
    }

    /**
     * 同步商品类目
     */
    public function syncCategory($site_id)
    {
        $res = (new Weapp())->getCatList();
        if ($res['code'] < 0) return $res;

        if (!empty($res['data'])) {
            model('shopcompoent_category')->delete([['create_time', '<', time()]]);
            $data = [];
            foreach ($res['data'] as $item) {
                array_push($data, [
                    'first_cat_id'      => $item['first_cat_id'],
                    'second_cat_id'     => $item['second_cat_id'],
                    'third_cat_id'      => $item['third_cat_id'],
                    'first_cat_name'    => $item['first_cat_name'],
                    'second_cat_name'   => $item['second_cat_name'],
                    'third_cat_name'    => $item['third_cat_name'],
                    'qualification'     => $item['qualification'],
                    'qualification_type'=> $item['qualification_type'],
                    'product_qualification_type' => $item['product_qualification_type'],
                    'product_qualification' => $item['product_qualification'],
                    'create_time' => time(),
                ]);
            }
            model('shopcompoent_category')->addList($data);
            Cache::tag('wxCategory')->clear();
            return $this->success(1);
        }
    }

    /**
     * 上传类目资质
     */
    public function uploadQualifications($param, $site_id)
    {
        $zhengshu = "";
        if (isset($param['zhengshu'])){
            $zhengshu_info = (new Weapp($site_id))->getImg(img($param['zhengshu']));
            if($zhengshu_info['code'] == 0){
                $zhengshu = $zhengshu_info['data']['img_info']['temp_img_url'];
            }
        }

        $data = [
            'license' => $zhengshu,
            'category_info'=>[
                'level1' => $param['first_cat_id'],
                'level2' => $param['second_cat_id'],
                'level3' => $param['third_cat_id'],
                'certificate' => []
            ]
        ];

        $img1 = "";
        if(!empty($param['leimu_qualification'])){
            $img_info1 = (new Weapp($site_id))->getImg(img($param['leimu_qualification']));
            if($img_info1['code'] == 0){
                $img1 = $img_info1['data']['img_info']['temp_img_url'];
            }
        }

        $img2 = "";
        if(!empty($param['product_qualification'])){
            $img_info2 = (new Weapp($site_id))->getImg(img($param['product_qualification']));
            if($img_info2['code'] == 0){
                $img2 = $img_info2['data']['img_info']['temp_img_url'];
            }
        }

        if (isset($param['leimu_qualification'])) array_push($data['category_info']['certificate'], $img1);
        if (isset($param['product_qualification'])) array_push($data['category_info']['certificate'], $img2);

        $res = (new Weapp())->auditCategory($data);
        if ($res['code'] < 0) return $res;

        $audit_data = [
            'site_id' => $site_id,
            'third_cat_id' => $param['third_cat_id'],
            'audit_id' => $res['data'],
            'audit_time' => time(),
            'certificate' => $img1,
            'qualification_pics' => $img2
        ];
        Cache::set('license_' . $param['third_cat_id'], $zhengshu);

        $is_exit = model('shopcompoent_category_audit')->getCount([ ['site_id', '=', $site_id ], ['third_cat_id', '=', $param['third_cat_id'] ] ]);
        if ($is_exit) {
            model('shopcompoent_category_audit')->update($audit_data, [ ['site_id', '=', $site_id ], ['third_cat_id', '=', $param['third_cat_id'] ] ]);
        } else {
            model('shopcompoent_category_audit')->add($audit_data);
        }
        return $this->success();
    }

    /**
     * 获取类目通过上级类目ID
     * @param $level
     * @param int $pid
     * @return array
     */
    public function getCategoryByParent($level, $pid = 0){
        $cache = Cache::get("wxCategory_{$level}_{$pid}");
        if ($cache) return $cache;

        switch ($level) {
            case 1:
                $list = model('shopcompoent_category')->getList([], '*', '', '', null, 'first_cat_id');
                break;
            case 2:
                $list = model('shopcompoent_category')->getList([ ['first_cat_id', '=', $pid ] ], '*', '', '', null, 'second_cat_id');
                break;
            case 3:
                $list = model('shopcompoent_category')->getList([ ['second_cat_id', '=', $pid ] ], '*', '', '', null, 'third_cat_name');
                break;
        }

        $data = $this->success($list);
        Cache::tag('wxCategory')->set("wxCategory_{$level}_{$pid}", $data);
        return $data;
    }

    /**
     * 查询分类是否需要上传资质
     * @param $goods_id
     * @param $site_id
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function isQualifications($param){
        $info = model('shopcompoent_category')->getInfo([ ['third_cat_id', '=', $param['third_cat_id']] ], 'qualification_type,product_qualification_type');
        if ($info['qualification_type'] != 0 || $info['product_qualification_type'] != 0) {
            $audit_info = model('shopcompoent_category_audit')->getInfo([ ['status', '=', 1], ['site_id', '=', $param['site_id'] ], ['third_cat_id', '=', $param['third_cat_id'] ] ]);
            if (empty($audit_info)) return $this->error();
        }
        return $this->success();
    }

    /**
     * 获取分类信息
     * @param $third_cat_id
     * @param $site_id
     */
    public function getCategoryInfo($third_cat_id, $site_id){
        $info = model('shopcompoent_category')->getInfo([ ['third_cat_id', '=', $third_cat_id] ]);
        if (empty($info)) return $info;

        $audit_info = model('shopcompoent_category_audit')->getInfo([ ['site_id', '=', $site_id], ['third_cat_id', '=', $third_cat_id ] ]);
        if (empty($audit_info)) {
            $audit_info = [
                'certificate' => '',
                'qualification_pics' => '',
                'audit_id' => '',
                'audit_time' => 0,
                'status' => 0,
                'reject_reason' => ''
            ];
            if (($info['qualification_type'] == 0 && $info['product_qualification_type'] == 0) || ($info['qualification_type'] == 2 && $info['product_qualification_type'] == 2)) $audit_info['status'] = 1;
        }

        return array_merge($info, $audit_info);
    }
}