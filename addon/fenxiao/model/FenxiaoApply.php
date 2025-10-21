<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\fenxiao\model;

use app\model\BaseModel;
use app\model\member\Member;


/**
 * 分销
 */
class FenxiaoApply extends BaseModel
{

    public $fenxiao_status_zh = [
        1 => '待审核',
        2 => '已审核',
        -1 => '拒绝',
    ];

    /**
     * 判断分销商名称是否存在
     * @param $fenxiao_name
     */
    public function existFenxiaoName($fenxiao_name)
    {
        $res = model('fenxiao_apply')->getCount([ [ 'fenxiao_name', '=', $fenxiao_name ] ]);
        if ($res > 0) {
            return $this->error('', '该分销商名称已存在');
        }
        return $this->success();
    }

    /**
     * 申请成为分销商
     * @param $member_id
     * @param $fenxiao_name
     * @param $mobile
     * @return array
     */
    public function applyFenxiao($member_id, $fenxiao_name = '', $mobile = '')
    {
        //判断该用户是否已经申请
        $apply_info = model('fenxiao_apply')->getInfo([ [ 'member_id', '=', $member_id ] ], 'apply_id,status');
        if (!empty($apply_info) && $apply_info[ 'status' ] != -1) {
            return $this->error('', '已经申请过，请不要重复申请');
        }

        // 分销商基础设置
        $config = new Config();
        $basics_config = $config->getFenxiaoBasicsConfig();
        $basics_config = $basics_config[ 'data' ][ 'value' ];

        if (!$basics_config['is_apply']) {
            return $this->error('', '暂未开启分销商申请');
        }

        //获取分销配置信息
        $fenxiao_config = $config->getFenxiaoConfig();
        $fenxiao_config = $fenxiao_config[ 'data' ][ 'value' ];

        //获取用户信息
        $member_model = new Member();
        $member_field = 'source_member,fenxiao_id,nickname,headimg,reg_time,order_money,order_complete_money,order_num,order_complete_num';
        $member_info = $member_model->getMemberInfo([ [ 'member_id', '=', $member_id ] ], $member_field);

        // 判断用户是否可以成为申请分销商
        if ($fenxiao_config[ 'fenxiao_condition' ] == 2) {
            if ($fenxiao_config[ 'consume_condition' ] == 1 && $fenxiao_config[ 'consume_count' ] > $member_info[ 'data' ][ 'order_num' ]) {
                return $this->error('', '您的消费次数未满足申请条件');
            } elseif ($fenxiao_config[ 'consume_condition' ] == 2 && $fenxiao_config[ 'consume_count' ] > $member_info[ 'data' ][ 'order_complete_num' ]) {
                return $this->error('', '您的消费次数未满足申请条件');
            }
        } elseif ($fenxiao_config[ 'fenxiao_condition' ] == 3) {
            if ($fenxiao_config[ 'consume_condition' ] == 1 && $fenxiao_config[ 'consume_money' ] > $member_info[ 'data' ][ 'order_money' ]) {
                return $this->error('', '您的消费金额未满足申请条件');
            } elseif ($fenxiao_config[ 'consume_condition' ] == 2 && $fenxiao_config[ 'consume_money' ] > $member_info[ 'data' ][ 'order_complete_money' ]) {
                return $this->error('', '您的消费金额未满足申请条件');
            }
        }

        if (empty($fenxiao_name)) $fenxiao_name = $member_info[ 'data' ][ 'nickname' ];

        //获取分销等级信息
        $level_model = new FenxiaoLevel();
        $level_info = $level_model->getLevelInfo([ [ 'is_default', '=', 1 ] ], 'level_id,level_name');
        // 成为分销商是否需要审核
        if ($basics_config[ 'is_examine' ]) {
            $apply_data = [
                'fenxiao_name' => $fenxiao_name,
                'parent' => $member_info[ 'data' ][ 'fenxiao_id' ],
                'member_id' => $member_id,
                'mobile' => $mobile,
                'nickname' => $member_info[ 'data' ][ 'nickname' ],
                'headimg' => $member_info[ 'data' ][ 'headimg' ],
                'level_id' => $level_info[ 'data' ][ 'level_id' ],
                'level_name' => $level_info[ 'data' ][ 'level_name' ],
                'order_complete_money' => $member_info[ 'data' ][ 'order_complete_money' ],
                'order_complete_num' => $member_info[ 'data' ][ 'order_complete_num' ],
                'reg_time' => $member_info[ 'data' ][ 'reg_time' ],
                'create_time' => time(),
                'status' => 1
            ];
            if (!empty($apply_info)) {
                $res = model('fenxiao_apply')->update($apply_data, [ [ 'member_id', '=', $member_id ] ]);
            } else {
                $res = model('fenxiao_apply')->add($apply_data);
            }
            return $this->success($res);
        } else {
            $fenxiao_data = [
                'fenxiao_name' => $fenxiao_name,
                'mobile' => $mobile,
                'member_id' => $member_id,
                'parent' => $member_info[ 'data' ][ 'fenxiao_id' ],
                'level_id' => $level_info[ 'data' ][ 'level_id' ],
                'level_name' => $level_info[ 'data' ][ 'level_name' ]
            ];
            $fenxiao_model = new Fenxiao();
            $res = $fenxiao_model->addFenxiao($fenxiao_data);
            return $res;
        }
    }

    /**
     * 管理员添加分销商
     */
    public function addFenxiao($fenxiao_data)
    {

        if (empty($fenxiao_data['fenxiao_name'])) {
            return $this->error('', '分销商名称不能为空');
        }
        if (empty($fenxiao_data['level_id'])) {
            return $this->error('', '请选择分销商等级');
        }
        if (empty($fenxiao_data['member_id'])) {
            return $this->error('', '请选择关联会员');
        }

        //判断该用户是否已经申请
        $apply_info = model('fenxiao_apply')->getInfo([['member_id', '=', $fenxiao_data['member_id']]], 'apply_id,status');
        if (!empty($apply_info) && $apply_info['status'] != -1) {
            return $this->error('', '已经申请过，请不要重复申请');
        }

        // 分销商基础设置
        $config        = new Config();

        //获取分销配置信息
        $fenxiao_config = $config->getFenxiaoConfig();
        $fenxiao_config = $fenxiao_config['data']['value'];

        //获取用户信息
        $member_model = new Member();
        $member_field = 'source_member,fenxiao_id,nickname,headimg,reg_time,order_money,order_complete_money,order_num,order_complete_num';
        $member_info  = $member_model->getMemberInfo([['member_id', '=', $fenxiao_data['member_id']]], $member_field);

        // 判断用户是否可以成为申请分销商
        if ($fenxiao_config[ 'fenxiao_condition' ] == 2) {
            if ($fenxiao_config[ 'consume_condition' ] == 1 && $fenxiao_config[ 'consume_count' ] > $member_info[ 'data' ][ 'order_num' ]) {
                return $this->error('', '您的消费次数未满足申请条件');
            } elseif ($fenxiao_config[ 'consume_condition' ] == 2 && $fenxiao_config[ 'consume_count' ] > $member_info[ 'data' ][ 'order_complete_num' ]) {
                return $this->error('', '您的消费次数未满足申请条件');
            }
        } elseif ($fenxiao_config[ 'fenxiao_condition' ] == 3) {
            if ($fenxiao_config[ 'consume_condition' ] == 1 && $fenxiao_config[ 'consume_money' ] > $member_info[ 'data' ][ 'order_money' ]) {
                return $this->error('', '您的消费金额未满足申请条件');
            } elseif ($fenxiao_config[ 'consume_condition' ] == 2 && $fenxiao_config[ 'consume_money' ] > $member_info[ 'data' ][ 'order_complete_money' ]) {
                return $this->error('', '您的消费金额未满足申请条件');
            }
        }

        //获取分销等级信息
        $level_model = new FenxiaoLevel();
        $level_info  = $level_model->getLevelInfo([['level_id', '=', $fenxiao_data['level_id']]], 'level_name');
        $fenxiao_data['level_name'] = $level_info['data']['level_name'];

        $fenxiao_model = new Fenxiao();
        return $fenxiao_model->addFenxiao($fenxiao_data);

    }

    /**
     * 审核通过
     * @param $fenxiao_id
     * @return array
     */
    public function pass($apply_id)
    {
        $info = model('fenxiao_apply')->getInfo([ [ 'apply_id', '=', $apply_id ] ]);
        if ($info[ 'status' ] == 2) {
            return $this->success();
        }

        model('fenxiao_apply')->startTrans();
        try {
            $data = [
                'status' => 2,
                'update_time' => time(),
            ];
            $res = model('fenxiao_apply')->update($data, [ [ 'apply_id', '=', $apply_id ] ]);

            $fenxiao_data = [
                'fenxiao_name' => $info[ 'fenxiao_name' ],
                'mobile' => $info[ 'mobile' ],
                'member_id' => $info[ 'member_id' ],
                'parent' => $info[ 'parent' ],
                'level_id' => $info[ 'level_id' ],
                'level_name' => $info[ 'level_name' ]
            ];

            $fenxiao_model = new Fenxiao();
            $result = $fenxiao_model->addFenxiao($fenxiao_data);
            if ($result[ 'code' ] != 0) {
                model('fenxiao_apply')->rollback();
                return $result;
            }

            model('fenxiao_apply')->commit();

            return $this->success($res);
        } catch (\Exception $e) {
            model('fenxiao_apply')->rollback();
            return $this->error('', $e->getMessage());
        }

    }

    /**
     * 审核不通过
     * @param $fenxiao_id
     * @return array
     */
    public function refuse($apply_id)
    {
        $data = [
            'status' => -1,
            'update_time' => time()
        ];

        $res = model('fenxiao_apply')->update($data, [ [ 'apply_id', '=', $apply_id ] ]);
        return $this->success($res);
    }

    /**
     * 获取分销商信息
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getFenxiaoInfo($condition = [], $field = '*')
    {
        $res = model('fenxiao')->getInfo($condition, $field);
        return $this->success($res);
    }


    /**
     * 获取分销列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param string $limit
     */
    public function getFenxiaoList($condition = [], $field = '*', $order = '', $limit = null)
    {
        $list = model('fenxiao')->getList($condition, $field, $order, '', '', '', $limit);
        return $this->success($list);
    }

    /**
     * 获取分销商申请信息
     * @param array $condition
     * @param string $field
     * @return array
     */
    public function getFenxiaoApplyInfo($condition = [], $field = '*')
    {
        $res = model('fenxiao_apply')->getInfo($condition, $field);
        return $this->success($res);
    }

    /**
     * 获取分销商申请分页列表
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getFenxiaoApplyPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*')
    {
        $list = model('fenxiao_apply')->pageList($condition, $field, $order, $page, $page_size);
        return $this->success($list);
    }

    /**
     * 获取分销商申请数量
     * @param array $condition
     * @param string $field
     */
    public function getFenxiaoApplyCount($condition = [], $field = 'id')
    {
        $count = model('fenxiao_apply')->getCount($condition, $field);
        return $this->success($count);
    }
}