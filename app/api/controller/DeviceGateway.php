<?php
namespace app\api\controller;

use think\facade\Db;

/**
 * 设备网关控制器（薄代理 + 适配）
 * 目的：为联调提供稳定入口，兼容前端传参并复用核心逻辑
 * 路由示例：/index.php/api/devicegateway/register, /index.php/api/devicegateway/profitprizes
 */
class DeviceGateway extends BaseApi
{
    /** 设备注册（兼容 { deviceCode, userId } 入参） */
    public function register()
    {
        // 读取联调期望的参数名
        $deviceCode = trim(strval($this->params['deviceCode'] ?? ''));
        $userId     = intval($this->params['userId'] ?? 0); // 目前不强依赖
        $secretKey  = strval($this->params['key'] ?? 'GATEWAY-DEFAULT');

        if ($deviceCode === '') {
            return $this->response($this->error('', '缺少设备标识 deviceCode'));
        }

        // 查询或创建设备（以 device_sn 识别）
        $now = time();
        $device = Db::name('device_info')->where('device_sn', $deviceCode)->find();
        if (empty($device)) {
            $record = [
                'device_sn'     => $deviceCode,
                'site_id'       => 0,
                'owner_id'      => 0,
                'installer_id'  => 0,
                'supplier_id'   => 0,
                'promoter_id'   => 0,
                'agent_code'    => '',
                'board_id'      => 0,
                'status'        => 1,
                'prob_mode'     => 'round',
                'prob_json'     => null,
                'auto_reset'    => 0,
                'device_secret' => $secretKey,
                'create_time'   => $now,
                'update_time'   => $now,
            ];
            try {
                $newId = Db::name('device_info')->insertGetId($record);
                $device = Db::name('device_info')->where('device_id', $newId)->find();
            } catch (\Throwable $e) {
                return $this->response($this->error('', '设备创建失败：' . $e->getMessage()));
            }
        } else {
            // 已存在则校验/绑定秘钥
            $secret = strval($device['device_secret'] ?? '');
            if ($secret === '') {
                Db::name('device_info')->where('device_id', intval($device['device_id']))->update([
                    'device_secret' => $secretKey,
                    'update_time'   => $now,
                ]);
                $device['device_secret'] = $secretKey;
            } elseif (!hash_equals($secret, $secretKey)) {
                return $this->response($this->error('', '设备秘钥不匹配'));
            }
        }

        // 绑定默认盘（board_id=1）
        $deviceId = intval($device['device_id']);
        $boardId  = intval($device['board_id'] ?? 0);
        if ($boardId <= 0) {
            $defaultBoard = Db::name('lottery_board')->where('board_id', 1)->find();
            if (empty($defaultBoard)) {
                return $this->response($this->error('', '未找到默认抽奖盘，请先初始化抽奖盘'));
            }
            Db::name('device_info')->where('device_id', $deviceId)->update([
                'board_id'    => 1,
                'update_time' => $now,
            ]);
            $boardId = 1;
        }

        // 绑定默认价档（tier_id=1，长期有效）
        $bind = Db::name('device_price_bind')->where([
            ['device_id', '=', $deviceId],
            ['status', '=', 1]
        ])->order('start_time desc')->find();
        $tierId = intval($bind['tier_id'] ?? 0);
        if ($tierId <= 0) {
            $defaultTier = Db::name('lottery_price_tier')->where('tier_id', 1)->find();
            if (empty($defaultTier)) {
                return $this->response($this->error('', '未找到默认价档，请先初始化价档'));
            }
            Db::name('device_price_bind')->insert([
                'device_id'   => $deviceId,
                'tier_id'     => 1,
                'status'      => 1,
                'start_time'  => $now,
                'end_time'    => 0,
                'create_time' => $now,
                'update_time' => $now,
            ]);
            $tierId = 1;
        }

        // 统一返回为联调期望格式
        return $this->response(success(0, 'registered', [
            'deviceCode' => $deviceCode,
            'status'     => 'active',
            'gameType'   => 'turntable',
            'poolId'     => $boardId,
        ]));
    }

    /** 查询设备利润奖品（代理至 System.Device.profitprizes） */
    public function profitprizes()
    {
        $controller = new \app\api\controller\Device();
        return $controller->profitprizes();
    }

    /** 自检（数据库、设备、绑定关系）（代理至 System.Device.selfcheck） */
    public function selfcheck()
    {
        $controller = new \app\api\controller\Device();
        return $controller->selfcheck();
    }

    /** 更新设备概率配置并推送（代理至 System.Device.probabilityUpdate） */
    public function probabilityupdate()
    {
        $controller = new \app\api\controller\Device();
        return $controller->probabilityUpdate();
    }
}