<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\api\controller;

use app\model\system\Addon as AddonModel;
use think\facade\Db;

/**
 * 插件管理
 * @author Administrator
 *
 */
class Addon extends BaseApi
{

    /**
     * 列表信息
     */
    public function lists()
    {
        $addon = new AddonModel();
        $list = $addon->getAddonList();
        return $this->response($list);
    }

    public function addonisexit()
    {
        $res = [];
        $res[ 'fenxiao' ] = addon_is_exit('fenxiao');                        // 分销
        $res[ 'pintuan' ] = addon_is_exit('pintuan');                        // 拼团
		$res[ 'presale' ] = addon_is_exit('presale');                        // 预售
        $res[ 'membersignin' ] = addon_is_exit('membersignin');            // 会员签到
        $res[ 'memberrecharge' ] = addon_is_exit('memberrecharge');        // 会员充值
        $res[ 'memberwithdraw' ] = addon_is_exit('memberwithdraw');        // 会员提现
        $res[ 'gift' ] = addon_is_exit('gift');                            // 礼品
        $res[ 'pointexchange' ] = addon_is_exit('pointexchange');            // 积分兑换
        $res[ 'city' ] = addon_is_exit('city');                            //城市分站
        $res[ 'manjian' ] = addon_is_exit('manjian');                        //满减
        $res[ 'memberconsume' ] = addon_is_exit('memberconsume');            //会员消费
        $res[ 'memberregister' ] = addon_is_exit('memberregister');        //会员注册
        $res[ 'coupon' ] = addon_is_exit('coupon');                        //优惠券
        $res[ 'bundling' ] = addon_is_exit('bundling');                    //组合套餐
        $res[ 'discount' ] = addon_is_exit('discount');                    //限时折扣
        $res[ 'seckill' ] = addon_is_exit('seckill');                        //秒杀
        $res[ 'topic' ] = addon_is_exit('topic');                            //专题活动
        $res[ 'store' ] = addon_is_exit('store');                            //门店管理
        $res[ 'groupbuy' ] = addon_is_exit('groupbuy');                    //团购
        $res[ 'bargain' ] = addon_is_exit('bargain');                    //砍价
        $res[ 'live' ] = addon_is_exit('live');                    //直播
        $res[ 'wholesale' ] = addon_is_exit('wholesale');                    //批发
        $res[ 'servicer' ] = addon_is_exit('servicer');                    //客服
        $res[ 'platformcoupon' ] = addon_is_exit('platformcoupon');                    //平台优惠券
        $res[ 'membercancel' ] = addon_is_exit('membercancel');                    //会员注销
        $res[ 'notes' ] = addon_is_exit('notes');                         //店铺笔记
        $res[ 'cards' ] = addon_is_exit('cards');        // 刮刮乐
        $res[ 'egg' ] = addon_is_exit('egg');        // 砸金蛋
        $res[ 'turntable' ] = addon_is_exit('turntable');        // 幸运抽奖
        $res[ 'sitemessage' ] = addon_is_exit('sitemessage');        // 站内信
        return $this->response($this->success($res));
    }

    /**
     * 插件是否存在
     */
    public function isexit()
    {
        $name = $this->params[ 'name' ] ?? '';
        $res = 0;
        if (!empty($name)) $res = addon_is_exit($name);
        return $this->response($this->success($res));
    }

    /**
     * 修复安装：仅触发插件 Install 事件
     */
    public function repair()
    {
        $name = $this->params['name'] ?? '';
        if (empty($name)) {
            return $this->response($this->error('', '缺少参数 name'));
        }
        try {
            $addon = new AddonModel();
            $res = $addon->repairInstall($name);
            return $this->response($res);
        } catch (\Throwable $e) {
            try {
                $logFile = app()->getRuntimePath() . 'ping.log';
                @file_put_contents($logFile, '[repair] '.date('c').' '.$e->getMessage()."\n", FILE_APPEND);
            } catch (\Throwable $ignore) {}
            return $this->response($this->error(-1, 'repair 异常: '.$e->getMessage()));
        }
    }

    /**
     * 数据库连接检查
     */
    public function ping()
    {
        try {
            $res = Db::query('SELECT 1');
            return $this->response($this->success(['ok' => true, 'res' => $res]));
        } catch (\Throwable $e) {
            try {
                $logFile = app()->getRuntimePath() . 'ping.log';
                @file_put_contents($logFile, '[ping] '.date('c').' '.$e->getMessage()."\n", FILE_APPEND);
            } catch (\Throwable $ignore) {}
            return $this->response($this->error(-1, 'DB 连接失败: '.$e->getMessage()));
        }
    }
}