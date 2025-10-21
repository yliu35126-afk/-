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

namespace app\model\member;

use app\model\BaseModel;
use app\model\message\Email;
use app\model\message\Sms;
use app\model\system\Address;
use app\model\system\Stat;
use app\model\upload\Upload;
use think\facade\Db;
use think\facade\Cache;

/**
 * 会员筛选管理
 */
class MemberSearch extends BaseModel
{

    public $dict = [
        'site_id' => [
            'name' => '站点id'
        ],
        'sex' => [
            'name' => '性别',
            'dict'
        ]
    ];

//    public function getDict($dict_keys){
//        $dict = [];
//        //性别
//        if(in_array('sex', $dict_keys)){
//            $dict['sex'] = [
//                'name' => '性别',
//                'value' => [
//                    0 => '保密',
//                    1 => '男',
//                    2 => '女'
//                ],
//                'type' => 'select'
//            ];
//        }
//        //注册时间
//        if(in_array('reg_time', $dict_keys)){
//            $dict['sex'] = [
//                'name' => '性别',
//                'value' => [
//
//                ],
//                'type' => 'between'
//            ];
//        }
//        //会员等级
//        if(in_array('member_level', $dict_keys)){
//            $member_level_model = new MemberLevel();
//            $member_level_condition = array(
//
//            );
//            $member_level_list = $member_level_model->getMemberLevelList($member_level_condition)['data'] ?? [];
//            if(empty($member_level_list)){
//
//            }
//            $dict['member_level'] = [
//                'name' => '会员等级',
//                'value' => [
//
//                ],
//                'type' => 'tab'
//            ];
//        }
//
//
//
//
//    }

    //筛选客户
    public function search($params = [])
    {
        $condition = [];
        $site_id = $params['site_id'];
        if ($site_id > 0) {
            $condition[] = ['site_id', '=', $site_id];
        }
        //会员等级
        $member_level = $params['member_level'] ?? [];
        if (!empty($member_level)) {
            $condition[] = ['member_level', 'in', $member_level];
        }
        //会员标签
        $member_label = $params['member_label'] ?? [];
        if (!empty($member_label)) {
            //循环加  find_in_set
            $sql = '';
            $member_label_arr = $member_label;
            foreach ($member_label_arr as $k => $v) {
                if(!empty($sql)){
                    $sql .= " OR ";
                }
                $sql .= " FIND_IN_SET($v,member_label) ";
            }
            $condition[] = ['', 'EXP', Db::raw($sql)];
        }
        //排除会员标签
//        $member_label = $params['remove_member_label'] ?? [];
//        if (!empty($member_label)) {
            //循环加  find_in_set
//            $condition[] = ['member_label', 'in', explode(',',$member_label)];
//        }
        //性别
        $sex = $params['sex'] ?? 'all';
        if ($sex != 'all') {
            $condition[] = ['sex', '=', $sex];
        }
        //注册时间
        $reg_time_start = $params['reg_time_start'] ?? 0;
        $reg_time_end = $params['reg_time_end'] ?? 0;
        if ($reg_time_start > 0 && $reg_time_end > 0) {
            $condition[] = ['reg_time', 'between', [$reg_time_start, $reg_time_end]];
        } else if ($reg_time_start > 0 && $reg_time_end == 0) {
            $condition[] = ['reg_time', '>=', $reg_time_start];
        } else if ($reg_time_start == 0 && $reg_time_end > 0) {
            $condition[] = ['reg_time', '<=', $reg_time_end];
        }
        $member_model = new Member();
        $member_list = $member_model->getMemberList($condition, 'member_id')['data'];
        $member_level_column = array_column($member_list, 'member_id');
        return $this->success($member_level_column);
    }
}