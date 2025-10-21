<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace app\model\system;

use app\model\BaseModel;
use extend\api\HttpClient;
use think\facade\Db;

class Upgrade extends BaseModel
{
    private $url;
    private $cert;

    public function __construct($code = '')
    {
        $this->cert = defined('NIUSHOP_AUTH_CODE') ? NIUSHOP_AUTH_CODE : '';
        $this->url = '';
    }

    /**
     * 获取可升级的版本信息
     */
    public function getUpgradeVersion()
    {
        $app_info = config('info');
        $addon_array = array_map('basename', glob('addon/*', GLOB_ONLYDIR));
        $plugin_info = [];
        foreach ($addon_array as $addon) {
            $addon_info_path = "addon/{$addon}/config/info.php";
            if (file_exists($addon_info_path)) {
                $info = include_once $addon_info_path;
                $plugin_info[] = [
                    'code' => $info['name'],
                    'version_no' => $info['version_no'],
                    'version_name' => $info['version'],
                ];
            }
        }

        $post_data = [
            'code' => $this->cert,
            'app_info' => [
                'code' => $app_info['name'],
                'version_no' => $app_info['version_no'],
                'version_name' => $app_info['version'],
            ],
            'plugin_info' => $plugin_info,
        ];
        $res = $this->doPost('/upgrade/upgrade/updateinfo', $post_data);
        $res = json_decode($res, true);

        //处理返回数据
        if (!empty($res) && $res['code'] == 0) {
            //整合系统和插件数据
            $app_data = $res['data']['app_data'];
            $client_data = $res['data']['client_data'];
            $plugin_data = $res['data']['plugin_data'];
            $install_data = $res['data']['install_data'];
            $data = [];
            if ($app_data['code'] == 0) {
                $app_data['data']['action'] = 'upgrade';
                $app_data['data']['action_name'] = '升级';
                $app_data['data']['type'] = 'system';
                $app_data['data']['type_name'] = '系统';
                $data[] = $app_data['data'];
            }
            foreach ($client_data as $key => $val) {
                if ($val['code'] == 0) {
                    $val['data']['action'] = 'download';
                    $val['data']['action_name'] = '下载';
                    $val['data']['type'] = 'client';
                    $val['data']['type_name'] = '客户端';
                    $data[] = $val['data'];
                }
            }
            foreach ($plugin_data as $key => $val) {
                if ($val['code'] == 0) {
                    $val['data']['action'] = 'upgrade';
                    $val['data']['action_name'] = '升级';
                    $val['data']['type'] = 'addon';
                    $val['data']['type_name'] = '插件';
                    $data[] = $val['data'];
                }
            }
            foreach ($install_data as $key => $val) {
                if ($val['code'] == 0) {
                    $val['data']['action'] = 'install';
                    $val['data']['action_name'] = '安装';
                    $val['data']['type'] = 'addon';
                    $val['data']['type_name'] = '插件';
                    $data[] = $val['data'];
                }
            }

            //处理更新说明的换行
            foreach ($data as $key => $val) {
                foreach ($val['scripts'] as $k => $v) {
                    $val['scripts'][$k]['description'] = str_replace("\n", '<br/>', $v['description']);
                }
                $data[$key] = $val;
            }
            $res['data'] = $data;
        }

        return $res;
    }

    /**
     * 在线下载文件
     * @param $param
     */
    public function download($param)
    {
        $data = array(
            "file_token" => $param["token"]
        );
        $result = $this->doPost('/upgrade/upgrade/download', $data);//授权
        if (empty($result)) {
            return $this->error();
        }

        $result = json_decode($result, true);
        return $result;
    }

    /**
     * 获取授权信息
     * @return bool|mixed|string
     */
    public function authInfo()
    {
        $app_info = config('info');
        $post_data = [
            'product_key' => $app_info['name']
        ];
        $re = '{"code":0,"message":"操作成功","data":{"product_name":"多商户V5","auth_type":0,"devolution_code":"1","product_key":"B2B2C","devolution_url":"xxx.cn","devolution_version_name":"多商户旗舰版","account_num":0,"devolution_type":"公司","devolution_attribution":"公司","devolution_name":"xxx","devolution_phone":"1","devolution_date":1604308379,"devolution_expire_date":4083461080,"newest_version":"见更新日志"},"timestamp":1622011270}';
        $re = json_decode($re, true);
        
        // $re = json_decode($re);
        // dump($re);
        return $re;
    }

    /**
     * 获取最新的版本信息
     * @return bool|string
     */
    public function getLatestVersion()
    {
        $re = $this->doPost('/upgrade/upgrade/newestVersion', []);
        $re = json_decode($re, true);
        return $re;
    }

    /**
     * 查询所有表
     */
    public function getDatabaseList()
    {
        $databaseList = Db::query("SHOW TABLE STATUS");
        return $databaseList;
    }

    /**
     * post 服务器请求
     */
    private function doPost($post_url, $post_data)
    {
        $post_data['code'] = $this->cert;
        $httpClient = new HttpClient();
        $res = $httpClient->post($this->url . $post_url, $post_data);
        return $res;
    }

    /******************************* 升级日志相关 start *****************************/

    /**
     * 添加升级日志
     * @param $data
     * upgrade_time 升级时间
     * version_info 升级的版本信息
     * backup_root 备份文件和sql的根目录
     * download_root 下载文件和sql的根目录
     * @return array
     */
    public function addUpgradeLog($data)
    {
        $res = model('sys_upgrade_log')->add($data);
        if ($res == false) {
            return $this->error('', 'UNKNOW_ERROR');
        } else {
            return $this->success();
        }
    }

    /**
     * 修改日志
     * @return array
     */
    public function editUpgradeLog($data, $condition)
    {
        try {
            $res = model('sys_upgrade_log')->update($data, $condition);
            if ($res == false) {
                return $this->error('', 'UNKNOW_ERROR');
            } else {
                return $this->success();
            }
        } catch ( \Exception $e ) {
            //升级失败
            return $this->error('', 'UNKNOW_ERROR');
        }
    }

    /**
     * 获取升级日志信息
     * @param $condition
     * @param string $field
     * @return array
     */
    public function getUpgradeLogInfo($condition, $field = '*')
    {
        $info = model('sys_upgrade_log')->getInfo($condition, $field);
        if (!empty($info)) {
            $info['version_info'] = json_decode($info['version_info'], true);
        }
        return $info;
    }

    /**
     * 获取升级分页列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getUpgradeLogPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*')
    {
        $list = model('sys_upgrade_log')->pageList($condition, $field, $order, $page, $page_size);
        return $this->success($list);
    }

    /**
     * 获取升级日志列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param null $limit
     * @return array
     */
    public function getUpgradeLogList($condition = [], $field = '*', $order = '', $limit = null)
    {
        $list = model('sys_upgrade_log')->getList($condition, $field, $order, '', '', '', $limit);
        return $this->success($list);
    }

    /**
     * 删除升级日志
     * @param $condition
     * @return array
     */
    public function deleteUpgradeLog($condition)
    {
        $log_list = model('sys_upgrade_log')->getList($condition, '*', 'upgrade_time asc');
        try {
            foreach ($log_list as $log) {
                $backup_root = $log['backup_root'];
                if (is_dir($backup_root)) {
                    unlink($backup_root);
                }
            }
            model('sys_upgrade_log')->delete($condition);
            return $this->success();
        } catch ( \Exception $e ) {
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 获取应用更新日志
     * @return mixed
     */
    public function getUpdateLog()
    {
        $app_info = config('info');
        $post_data = [
            'product_key' => $app_info['name']
        ];
        $re = $this->doPost('/upgrade/upgrade/updateLog', $post_data);
        $re = json_decode($re, true);
        return $re;
    }
    /******************************* 升级日志相关 end *****************************/

    /**
     * 下载uniapp
     * @param $version
     * @return array|mixed
     */
    public function downloadUniapp($version)
    {
        $data = array(
            "version" => $version
        );
        $result = $this->doPost('/upgrade/upgrade/downloaduniapp', $data);//授权

        if (empty($result))
            return $this->error();

        $result = json_decode($result, true);
        return $result;
    }

    /**
     * 获取已授权的插件
     */
    public function getAuthAddons()
    {
        $result = $this->doPost('/upgrade/auth/plugin', []);//授权

        if (empty($result))
            return $this->error();

        $result = json_decode($result, true);
        return $result;
    }

    /**
     * 获取所有插件
     * @return array|mixed
     */
    public function getAuthPlugin()
    {
        $result = $this->doPost('/upgrade/auth/plugin', []);//授权
        if (empty($result))
            return $this->error();
        $result = json_decode($result, true);
        return $result;
    }

}
