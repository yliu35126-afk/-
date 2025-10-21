<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\admin\controller;

use app\model\system\H5 as H5Model;
use app\model\web\Config;
use app\model\system\Upgrade;

class H5 extends BaseAdmin
{
    /**
     * 刷新前端代码
     */
    public function refreshH5()
    {
        if (request()->isAjax()) {
            $h5 = new H5Model();
            $res = $h5->refresh();
            return $res;
        } else {
            $refresh_time = 0;
            if (file_exists('h5/refresh.log')) {
                $refresh_time = file_get_contents('h5/refresh.log');
            }
            $this->assign('refresh_time', $refresh_time);
            $this->assign("root_url",  __ROOT__);

            $config_model = new Config();
            $config = $config_model->geth5DomainName();
            $this->assign('config', $config['data']['value']);

            // 检测授权
            $upgrade_model = new Upgrade();
            $auth_info = $upgrade_model->authInfo();
            $this->assign('is_auth', ($auth_info['code'] == 0));
            return $this->fetch('h5/refresh_h5');
        }
    }

    /**
     * h5域名配置
     */
    public function h5DomainName()
    {
        $config_model = new Config();
        $domain_name = input("domain", "");
        $deploy_way = input("deploy_way", "default");

        if ($deploy_way == 'default') $domain_name =  __ROOT__ . '/h5';

        $result = $config_model->seth5DomainName([
            'domain_name_h5' => $domain_name,
            'deploy_way' => $deploy_way
        ]);
        return $result;
    }

    /**
     * 独立部署版下载
     */
    public function downloadIndep(){
        if (strstr(ROOT_URL, 'niuteam.cn') === false) {
            $domain_name = input("domain", "");
            $h5 = new H5Model();
            $res = $h5->downloadH5Indep($domain_name);
            if (isset($res['code']) && $res['code'] != 0) $this->error($res['message']);
        }
    }

    /**
     * 下载uniapp源码
     */
    public function downloadUniapp(){
        if (strstr(ROOT_URL, 'niuteam.cn') === false) {
            $app_info = config('info');

            $upgrade_model = new Upgrade();
            $res = $upgrade_model->downloadUniapp($app_info['version_no']);
            if ($res['code'] == 0) {
                $filename = "upload/{$app_info['version_no']}_uniapp.zip";
                $res = file_put_contents($filename, base64_decode($res['data']));

                header("Content-Type: application/zip");
                header("Content-Transfer-Encoding: Binary");
                header("Content-Length: " . filesize($filename));
                header("Content-Disposition: attachment; filename=\"" . basename($filename) . "\"");
                readfile($filename);
                @unlink($filename);
            } else {
                return $this->error($res['message']);
            }
        }
    }
}
