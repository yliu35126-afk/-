<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\topic\model;

use app\model\BaseModel;
use app\model\system\Cron;
use think\facade\Db;

/**
 * 专题活动
 */
class Topic extends BaseModel
{
    /**
     * 添加专题活动
     * @param $data
     * @return array
     */
    public function addTopic($data)
    {
        //时间段检测
        $topic_count = model('promotion_topic')->getCount([
            [ '', 'exp', Db::raw('not ( (`start_time` > ' . $data[ 'end_time' ] . ' and `start_time` > ' . $data[ 'start_time' ] . ' )  or (`end_time` < ' . $data[ 'start_time' ] . ' and `end_time` < ' . $data[ 'end_time' ] . '))') ]//todo  修正  所有的优惠都要一样
        ]);
        if ($topic_count > 0) {
            return $this->error('', '专题时间段设置冲突');
        }

        if (time() > $data[ 'start_time' ] && time() < $data[ 'end_time' ]) {
            $data[ 'status' ] = 2;
        } else {
            $data[ 'status' ] = 1;
        }
        //添加数据
        $data[ 'create_time' ] = time();
        $topic_id = model('promotion_topic')->add($data);

        // 添加定时任务
        $cron = new Cron();
        if ($data[ 'status' ] == 2) {
            $cron->addCron(1, 0, "专题活动关闭", "CloseTopic", $data[ 'end_time' ], $topic_id);
        } else {
            $cron->addCron(1, 0, "专题活动开启", "OpenTopic", $data[ 'start_time' ], $topic_id);
            $cron->addCron(1, 0, "专题活动关闭", "CloseTopic", $data[ 'end_time' ], $topic_id);
        }

        return $this->success($topic_id);
    }

    /**
     * 修改专题活动
     * @param $data
     * @return array
     */
    public function editTopic($data)
    {
        //时间段检测
        $topic_count = model('promotion_topic')->getCount([
            [ '', 'exp', Db::raw('not ( (`start_time` > ' . $data[ 'end_time' ] . ' and `start_time` > ' . $data[ 'start_time' ] . ' )  or (`end_time` < ' . $data[ 'start_time' ] . ' and `end_time` < ' . $data[ 'end_time' ] . '))') ],//todo  修正  所有的优惠都要一样
            [ 'topic_id', '<>', $data[ 'topic_id' ] ]
        ]);

        if ($topic_count > 0) {
            return $this->error('', '专题时间段设置冲突');
        }

        if (time() > $data[ 'start_time' ] && time() < $data[ 'end_time' ]) {
            $data[ 'status' ] = 2;
        } else {
            $data[ 'status' ] = 1;
        }
        //更新数据
        $res = model('promotion_topic')->update($data, [ [ 'topic_id', '=', $data[ 'topic_id' ] ] ]);
        $goods_data = [
            'start_time' => $data[ 'start_time' ],
            'end_time' => $data[ 'end_time' ],
        ];
        model('promotion_topic_goods')->update($goods_data, [ [ 'topic_id', '=', $data[ 'topic_id' ] ] ]);

        // 添加定时任务
        $cron = new Cron();
        if ($data[ 'status' ] == 2) {
            //活动商品启动
            $this->cronOpenTopic($data[ 'topic_id' ]);
            $cron->deleteCron([ [ 'event', '=', 'OpenTopic' ], [ 'relate_id', '=', $data[ 'topic_id' ] ] ]);
            $cron->deleteCron([ [ 'event', '=', 'CloseTopic' ], [ 'relate_id', '=', $data[ 'topic_id' ] ] ]);

            $cron->addCron(1, 0, "专题活动关闭", "CloseTopic", $data[ 'end_time' ], $data[ 'topic_id' ]);
        } else {
            $cron->deleteCron([ [ 'event', '=', 'OpenTopic' ], [ 'relate_id', '=', $data[ 'topic_id' ] ] ]);
            $cron->deleteCron([ [ 'event', '=', 'CloseTopic' ], [ 'relate_id', '=', $data[ 'topic_id' ] ] ]);

            $cron->addCron(1, 0, "专题活动开启", "OpenTopic", $data[ 'start_time' ], $data[ 'topic_id' ]);
            $cron->addCron(1, 0, "专题活动关闭", "CloseTopic", $data[ 'end_time' ], $data[ 'topic_id' ]);
        }
        return $this->success($res);
    }

    /**
     * 删除专题活动
     * @param unknown $topic_id
     */
    public function deleteTopic($topic_id)
    {
        $res = model('promotion_topic')->delete([ [ 'topic_id', '=', $topic_id ] ]);
        if ($res) {
            model('promotion_topic_goods')->delete([ [ 'topic_id', '=', $topic_id ] ]);
        }
        return $this->success($res);
    }

    /**
     * 获取专题活动信息
     * @param array $condition
     * @param string $field
     */
    public function getTopicInfo($condition, $field = '*')
    {
        $res = model('promotion_topic')->getInfo($condition, $field);
        return $this->success($res);
    }

    /**
     * 获取专题活动列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param string $limit
     */
    public function getTopicList($condition = [], $field = '*', $order = '', $limit = null)
    {
        if (!empty($cache)) {
            return $this->success($cache);
        }
        $list = model('promotion_topic')->getList($condition, $field, $order, '', '', '', $limit);

        return $this->success($list);
    }

    /**
     * 获取专题分页列表
     * @param array $condition
     * @param number $page
     * @param string $page_size
     * @param string $order
     * @param string $field
     */
    public function getTopicPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'modify_time desc,create_time desc', $field = '*')
    {
        $list = model('promotion_topic')->pageList($condition, $field, $order, $page, $page_size);
        return $this->success($list);
    }

    /**
     * 开启专题活动
     * @param $topic_id
     * @return array
     */
    public function cronOpenTopic($topic_id)
    {
        $topic_info = model('promotion_topic')->getInfo([ [ 'topic_id', '=', $topic_id ] ], 'start_time,status');
        if (!empty($topic_info)) {
            if ($topic_info[ 'start_time' ] <= time() && $topic_info[ 'status' ] == 1) {
                $res = model('promotion_topic')->update([ 'status' => 2 ], [ [ 'topic_id', '=', $topic_id ] ]);

                return $this->success($res);
            } else {
                return $this->error("", "专题活动已开启或者关闭");
            }

        } else {
            return $this->error("", "专题活动不存在");
        }

    }

    /**
     * 关闭专题活动
     * @param $topic_id
     * @return array
     */
    public function cronCloseTopic($topic_id)
    {
        $topic_info = model('promotion_topic')->getInfo([ [ 'topic_id', '=', $topic_id ] ], 'start_time,status');
        if (!empty($topic_info)) {
            if ($topic_info[ 'status' ] != 3) {
                $res = model('promotion_topic')->update([ 'status' => 3 ], [ [ 'topic_id', '=', $topic_id ] ]);
                return $this->success($res);
            } else {
                return $this->error("", "该活动已结束");
            }

        } else {
            return $this->error("", "专题活动不存在");
        }
    }
}