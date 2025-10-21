<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\shopcomponent\shop\controller;

use app\shop\controller\BaseShop;
use addon\shopcomponent\model\Video as VideoModel;

class Video extends BaseShop
{
    /**
     * 店铺 视频号 微信号 申请 编辑
     */
    public function operation()
    {
        if (request()->isAjax()) {
            $param = input();

            if (empty($param)) return $this->error('必要参数必填');
            $data = [
                'id' => $param['id'] ?? '',
                'site_id' => $this->site_id ?? 0,
                'name' => $param['name'],
                'wx_username' => $param['wx_username'],

            ];
            $video_model = new VideoModel();

            return $video_model->operationVideo(array_filter($data));
        }
    }

    /**
     * @return mixed
     * 视频号直播设置
     */
    public function access()
    {
        $video_model = new VideoModel();
        $condition = [
            ['site_id', '=', $this->site_id ?? 0]
        ];
        $data = $video_model->getVideoInfo($condition);

        $this->assign('info', $data['data'] ?? []);
        return $this->fetch("video/access");
    }

}