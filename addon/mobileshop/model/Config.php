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

namespace addon\mobileshop\model;

use app\model\BaseModel;
use app\model\system\Config as ConfigModel;
use app\model\web\Config as WebConfigModel;

/**
 * 手机版商家端配置
 */
class Config extends BaseModel
{
    private $path = 'addon/mobileshop/source';

    private $not_found_file_error = "未找到源码包，请检查目录文件";

    /*************************************************网站部署******************************************/

    /**
     * 默认部署：无需下载，一键刷新，API接口请求地址为当前域名，编译代码存放到mobileshop文件夹中
     * @return array
     */
    public function downloadCsDefault()
    {
        try {

            $path = $this->path . '/cs_default';
            $mshop_path = 'mshop'; // mobileshop端生成目录
            $config_path = 'mshop/static/js'; // mobileshop模板文件目录
            if (!is_dir($path) || count(scandir($path)) <= 3) {
                return $this->error('', $this->not_found_file_error);
            }

            if (is_dir($mshop_path)) {
                // 先将之前的文件删除
                if (count(scandir($mshop_path)) > 1) deleteDir($mshop_path);
            } else {
                // 创建mobileshop目录
                mkdir($mshop_path, intval('0777', 8), true);
            }

            // 将原代码包拷贝到mobileshop目录下
            recurseCopy($path, $mshop_path);
            $this->copyFile($config_path);
            file_put_contents($mshop_path . '/refresh.log', time());
            return $this->success();
        } catch (\Exception $e) {
            return $this->error('', $e->getMessage() . $e->getLine());
        }
    }

    /**
     * 独立部署：下载编译代码包后，放到网站根目录下运行
     * @param $domain
     * @return array
     */
    public function downloadCsIndep($domain)
    {
        try {

            $path = $this->path . '/cs_indep';
            $source_file_path = 'upload/mshop/cs_indep'; // mobileshop端生成目录
            $config_path = $source_file_path . '/static/js'; // mobileshop模板文件目录
            if (!is_dir($path) || count(scandir($path)) <= 3) {
                return $this->error('', $this->not_found_file_error);
            }

            if (is_dir($source_file_path)) {
                // 先将之前的文件删除
                if (count(scandir($source_file_path)) > 2) deleteDir($source_file_path);
            } else {
                // 创建mobileshop目录
                mkdir($source_file_path, intval('0777', 8), true);
            }

            // 将原代码包拷贝到mobileshop目录下
            recurseCopy($path, $source_file_path);
            $this->copyFile($config_path, $domain);

            // 生成压缩包
            $file_arr = getFileMap($source_file_path);

            if (!empty($file_arr)) {
                $zipname = 'mshop_cs_indep_' . date('YmdHi') . '.zip';
                $zip = new \ZipArchive();
                $res = $zip->open($zipname, \ZipArchive::CREATE);
                if ($res === TRUE) {
                    foreach ($file_arr as $file_path => $file_name) {
                        if (is_dir($file_path)) {
                            $file_path = str_replace($source_file_path . '/', '', $file_path);
                            $zip->addEmptyDir($file_path);
                        } else {
                            $zip_path = str_replace($source_file_path . '/', '', $file_path);
                            $zip->addFile($file_path, $zip_path);
                        }
                    }
                    $zip->close();

                    header("Content-Type: application/zip");
                    header("Content-Transfer-Encoding: Binary");
                    header("Content-Length: " . filesize($zipname));
                    header("Content-Disposition: attachment; filename=\"" . basename($zipname) . "\"");
                    readfile($zipname);
                    @unlink($zipname);
                }
            }
            return $this->success();
        } catch (\Exception $e) {
            return $this->error('', $e->getMessage() . $e->getLine());
        }
    }

    /**
     * 源码下载：下载uni-app代码包，可进行二次开发
     * @return array
     */
    public function downloadOs()
    {
        try {
            $source_file_path = $this->path . '/os';
            if (!is_dir($source_file_path) || count(scandir($source_file_path)) <= 3) {
                return $this->error('', $this->not_found_file_error);
            }
            $file_arr = getFileMap($source_file_path);

            if (!empty($file_arr)) {
                $zipname = 'mshop_os_' . date('YmdHi') . '.zip';
                $zip = new \ZipArchive();
                $res = $zip->open($zipname, \ZipArchive::CREATE);
                if ($res === TRUE) {
                    foreach ($file_arr as $file_path => $file_name) {
                        if (is_dir($file_path)) {
                            $file_path = str_replace($source_file_path . '/', '', $file_path);
                            $zip->addEmptyDir($file_path);
                        } else {
                            $zip_path = str_replace($source_file_path . '/', '', $file_path);
                            $zip->addFile($file_path, $zip_path);
                        }
                    }
                    $zip->close();

                    header("Content-Type: application/zip");
                    header("Content-Transfer-Encoding: Binary");
                    header("Content-Length: " . filesize($zipname));
                    header("Content-Disposition: attachment; filename=\"" . basename($zipname) . "\"");
                    readfile($zipname);
                    @unlink($zipname);
                }
            }
        } catch (\Exception $e) {
            return $this->error('', $e->getMessage() . $e->getLine());
        }
    }

    /**
     * 替换配置信息，API请求域名地址、图片、地图密钥等
     * @param $source_path
     * @param string $domain
     */
    private function copyFile($source_path, $domain = __ROOT__)
    {
        $files = scandir($source_path);
        foreach ($files as $path) {
            if ($path != '.' && $path != '..') {
                $temp_path = $source_path . '/' . $path;
                if (file_exists($temp_path)) {
                    if (preg_match("/(index.)(\w{8})(.js)$/", $temp_path)) {
                        $content = file_get_contents($temp_path);
                        $content = $this->paramReplace($content, $domain);
                        file_put_contents($temp_path, $content);
                    }
                }
            }
        }
    }

    /**
     * 参数替换
     * @param $string
     * @param string $domain
     * @return string|string[]|null
     */
    private function paramReplace($string, $domain = __ROOT__)
    {
        $web_config_model = new WebConfigModel();
        $web_config = $web_config_model->getMapConfig();
        $web_config = $web_config[ 'data' ][ 'value' ];
        $patterns = [
            '/\{\{\$baseUrl\}\}/',
            '/\{\{\$imgDomain\}\}/',
            '/\{\{\$h5Domain\}\}/',
            '/\{\{\$mpKey\}\}/',
        ];
        $replacements = [
            $domain,
            $domain,
            $domain . '/mshop',
            $web_config[ 'tencent_map_key' ] ?? '',
        ];
        $string = preg_replace($patterns, $replacements, $string);
        return $string;
    }

    /**
     * 设置移动版商家端域名配置
     * @param $data
     * @param int $site_id
     * @param string $app_modle
     * @return array
     */
    public function setMShopDomainName($data, $site_id = 0, $app_modle = 'admin')
    {
        $config = new ConfigModel();
        $res = $config->setConfig($data, '移动版商家端域名配置', 1, [ [ 'site_id', '=', $site_id ], [ 'app_module', '=', $app_modle ], [ 'config_key', '=', 'MOBILE_SHOP_DOMAIN_NAME' ] ]);
        return $res;
    }

    /**
     * 获取移动版商家端域名配置
     * @param int $site_id
     * @param string $app_module
     * @return array
     */
    public function getMShopDomainName($site_id = 0, $app_module = 'admin')
    {
        $config = new ConfigModel();
        $res = $config->getConfig([ [ 'site_id', '=', $site_id ], [ 'app_module', '=', $app_module ], [ 'config_key', '=', 'MOBILE_SHOP_DOMAIN_NAME' ] ]);
        if (empty($res[ 'data' ][ 'value' ])) {
            $res[ 'data' ][ 'value' ] = [
                'domain_name_mobileshop' => ROOT_URL . '/mshop',
            ];
        } else if ($res[ 'data' ][ 'value' ][ 'domain_name_mobileshop' ] == '') {
            $res[ 'data' ][ 'value' ] = [
                'domain_name_mobileshop' => ROOT_URL . '/mshop',
            ];
        }
        return $res;
    }

    /******************************************************************** 微信小程序配置 start ****************************************************************************/
    /**
     * 设置微信小程序配置
     * @param $data
     * @param $is_use
     * @return array
     */
    public function setWeappConfig($data, $is_use)
    {
        $config = new ConfigModel();
        $res = $config->setConfig($data, '商家端微信小程序设置', $is_use, [ [ 'site_id', '=', 0 ], [ 'app_module', '=', 'admin' ], [ 'config_key', '=', 'MOBILE_SHOP_WEAPP_CONFIG' ] ]);
        return $res;
    }

    /**
     * 获取微信小程序配置信息
     * @param int $site_id
     * @return array
     */
    public function getWeappConfig($site_id = 0)
    {
        $config = new ConfigModel();
        $res = $config->getConfig([ [ 'site_id', '=', $site_id ], [ 'app_module', '=', 'admin' ], [ 'config_key', '=', 'MOBILE_SHOP_WEAPP_CONFIG' ] ]);
        return $res;
    }
}