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

namespace app\model\system;

use app\model\BaseModel;
use app\model\web\DiyViewLink;
use think\facade\Cache;
use think\facade\Db;

/**
 * 插件表
 */
class Addon extends BaseModel
{

    /**
     * 获取单条插件信息
     * @param $condition
     * @param string $field
     * @return array
     */
    public function getAddonInfo($condition, $field = "*")
    {
        $data = json_encode([$condition, $field]);
        $cache = Cache::get("addon_getAddonInfo_" . $data);
        if (!empty($cache)) {
            return $this->success($cache);
        }
        $addon_info = model('addon')->getInfo($condition, $field);
        Cache::tag("addon")->set("addon_getAddonInfo_" . $data, $addon_info);
        return $this->success($addon_info);
    }


    /**
     * 获取插件列表
     * @param array $condition
     * @param string $field
     * @param string $order
     * @param null $limit
     * @return array
     */
    public function getAddonList($condition = [], $field = '*', $order = '', $limit = null)
    {
        $data = json_encode([$condition, $field, $order, $limit]);
        $cache = Cache::get("addon_getAddonList_" . $data);
        if (!empty($cache)) {
            return $this->success($cache);
        }
        $addon_list = model('addon')->getList($condition, $field, $order, '', '', '', $limit);
        Cache::tag("addon")->set("addon_getAddonList_" . $data, $addon_list);
        return $this->success($addon_list);
    }

    /**
     * 获取插件分页列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getAddonPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*')
    {
        $data = json_encode([$condition, $page, $page_size, $order, $field]);
        $cache = Cache::get("addon_getAddonPageList_" . $data);
        if (!empty($cache)) {
            return $this->success($cache);
        }
        $list = model('addon')->pageList($condition, $field, $order, $page, $page_size);
        Cache::tag("addon")->set("addon_getAddonPageList_" . $data, $list);
        return $this->success($list);
    }

    /**
     * 获取所有插件
     * @return array
     */
    public function getAddonAllList()
    {
        //获取官网记录的所有数据
        $upgrade_service = new Upgrade();
        $data = $upgrade_service->getAuthPlugin();
        if (isset($data['code']) && $data['code'] >= 0) {
            $temp_auth_addon_list = $data['data'];
        } else {
            $temp_auth_addon_list = [];
        }
        //以code为key组装正式数据
        $auth_addon_list = [];
        foreach ($temp_auth_addon_list as $key => $val) {
            $auth_addon_list[$val['code']] = $val;
        }
        // 使用项目根路径定位 addon 目录，避免在 docroot=public 下相对路径失效
        $root = root_path();
        //存在的插件
        $addonRoot = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'addon' . DIRECTORY_SEPARATOR;
        $existed_addons = [];
        if (is_dir($addonRoot)) {
            foreach (scandir($addonRoot) as $d) {
                if ($d === '.' || $d === '..') continue;
                if (is_dir($addonRoot . $d)) $existed_addons[] = $d;
            }
        }
        //已安装的插件
        $installed_addon_array = model('addon')->getColumn([], 'name');
        //初始化数据
        $undownload_addons = [];
        $uninstall_addons = [];
        $install_addons = [];
        //获取未下载插件
        foreach ($auth_addon_list as $key => $val) {
            $index = array_search($val['code'], $existed_addons);
            if ($index === false) {
                $undownload_addons[] = [
                    'name' => $val['code'],
                    'title' => $val['goods_name'],
                    'icon' => $val['logo'],
                    'description' => $val['introduction'],
                    'version' => $val['last_online_version_name'],
                    'download' => 1,
                    'auth' => true,
                    'update' => false
                ];
            }
        }
        //获取已下载插件 区分已安装和为安装 是否需要升级 是否已授权
        foreach ($existed_addons as $key => $val) {
            $info_file_path = $root . 'addon/' . $val . '/config/info.php';
            if (file_exists($info_file_path)) {
                $info = include_once $info_file_path;
                // 输出路径仍保留相对形式，便于 img() 生成 URL
                $info['icon'] = 'addon/' . $val . '/icon.png';
                $info['download'] = 0;
                $info['auth'] = isset($auth_addon_list[$val]) ? true : false;
                $info['update'] = (isset($auth_addon_list[$val]) && $auth_addon_list[$val]['last_online_version_no'] > $info['version_no']) ? true : false;
                $info['last_online_version_no'] = isset($auth_addon_list[$val]) ? $auth_addon_list[$val]['last_online_version_no'] : '';
                if (!in_array($val, $installed_addon_array)) {
                    $uninstall_addons[] = $info;
                } else {
                    $install_addons[] = $info;
                }
            }
        }
        return $this->success([
            'uninstall' => array_merge($undownload_addons, $uninstall_addons),
            'install' => $install_addons,
        ]);
    }


    /**
     * 获取未安装的插件列表
     * @return array
     */
    public function getUninstallAddonList()
    {

        // 使用项目根路径定位 addon 目录，避免在 docroot=public 下相对路径失效
        $root = root_path();
        $addonRoot = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'addon' . DIRECTORY_SEPARATOR;
        $dirs = [];
        if (is_dir($addonRoot)) {
            foreach (scandir($addonRoot) as $d) {
                if ($d === '.' || $d === '..') continue;
                if (is_dir($addonRoot . $d)) $dirs[] = $d;
            }
        }
        $addon_names = model('addon')->getColumn([], 'name');
        $addons = [];
        foreach ($dirs as $key => $value) {
            if (!in_array($value, $addon_names)) {
                $info_name = $root . 'addon/' . $value . '/config/info.php';
                if (file_exists($info_name)) {
                    $info = include_once $info_name;
                    $info['icon'] = 'addon/' . $value . '/icon.png';
                    $addons[] = $info;
                }

            }
        }
        return $this->success($addons);
    }

    /*******************************************************************插件安装方法开始****************************************************/
    /**
     * 插件安装
     * @param $addon_name
     * @return array
     */
    public function install($addon_name)
    {

        Db::startTrans();
        try {
            // 插件预安装

            $res2 = $this->preInstall($addon_name);
            if ($res2['code'] != 0) {
                Db::rollback();
                return $res2;
            }

            // 安装菜单
            $res3 = $this->installMenu($addon_name);

            if ($res3['code'] != 0) {
                Db::rollback();
                return $res3;
            }

            // 安装自定义模板
            $res4 = $this->refreshDiyView($addon_name);
            if ($res4['code'] != 0) {
                Db::rollback();
                return $res4;
            }
            // 添加插件入表
            $addons_model = model('addon');
            $addon_info = require 'addon/' . $addon_name . '/config/info.php';
            $addon_info['create_time'] = time();
            $addon_info['icon'] = 'addon/' . $addon_name . '/icon.png';

            $data = $addons_model->add($addon_info);

            if (!$data) {
                Db::rollback();
                return $this->error($data, 'ADDON_ADD_FAIL');
            }
            // 清理缓存
            Cache::clear();

            Db::commit();
            return $this->success();
        } catch ( \Exception $e ) {
            // 清理缓存
            Cache::clear();
            Db::rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 插件预安装
     * @param $addon_name
     * @return array
     */
    private function preInstall($addon_name)
    {
        $class_name = "addon\\" . $addon_name . "\\event\\Install";
        $install = new $class_name;
        $res = $install->handle($addon_name);
        if ($res['code'] != 0) {
            return $res;
        }
        return $this->success();
    }


    /**
     * 插件修复安装：仅执行预安装事件（不写入插件表）
     * @param string $addon_name
     * @return array
     */
    public function repairInstall($addon_name)
    {
        return $this->preInstall($addon_name);
    }

    /**
     * 安装插件菜单
     * @param $addon_name
     * @return array
     */
    private function installMenu($addon)
    {
        $menu = new Menu();
        $menu->refreshMenu('admin', $addon);
        $menu->refreshMenu('shop', $addon);
        return $this->success();
    }


    /**
     * 刷新插件自定义页面配置
     * @param $addon
     * @return array
     */
    public function refreshDiyView($addon)
    {
        try {
            if (empty($addon)) {
                $diy_view_file = 'config/diy_view.php';
                model('diy_view_temp')->delete([['addon_name', '=', '']]);
                model('link')->delete([['addon_name', '=', '']]);
                model('diy_view_util')->delete([['addon_name', '=', '']]);
            } else {
                $diy_view_file = 'addon/' . $addon . '/config/diy_view.php';
                model('diy_view_temp')->delete([['addon_name', '=', $addon]]);
                model('link')->delete([['addon_name', '=', $addon]]);
                model('diy_view_util')->delete([['addon_name', '=', $addon]]);
            }

            if (!file_exists($diy_view_file)) {
                return $this->success();
            }

            $diy_view = require $diy_view_file;

            // 自定义模板
            if (isset($diy_view['template'])) {
                $diy_view_addons_data = [];
                foreach ($diy_view['template'] as $k => $v) {
                    $addons_item = [
                        'addon_name' => isset($addon) ? $addon : '',
                        'name' => $v['name'],
                        'title' => $v['title'],
                        'value' => $v['value'],
                        'type' => $v['type'],
                        'icon' => $v['icon'],
                        'create_time' => time()
                    ];
                    $diy_view_addons_data[] = $addons_item;
                }
                if ($diy_view_addons_data) {
                    model('diy_view_temp')->addList($diy_view_addons_data);
                }
            }
            // 自定义链接
            if (isset($diy_view['link'])) {
                $link_model = new DiyViewLink();
                $diy_view_link_data = $link_model->getViewLinkList($diy_view['link'], $addon);
                if ($diy_view_link_data) {
                    model('link')->addList($diy_view_link_data);
                }
            }
            // 自定义模板组件
            if (isset($diy_view['util'])) {
                $diy_view_util_data = [];
                foreach ($diy_view['util'] as $k => $v) {
                    $util_item = [
                        'name' => $v['name'],
                        'title' => $v['title'],
                        'type' => $v['type'],
                        'controller' => $v['controller'],
                        'value' => $v['value'],
                        'sort' => $v['sort'],
                        'support_diy_view' => $v['support_diy_view'],
                        'addon_name' => $addon,
                        'max_count' => $v['max_count'],
                        'is_delete' => $v['is_delete'] ?? 0,
                        'icon' => $v['icon'] ?? '',
                        'icon_selected' => $v['icon_selected'] ?? ''
                    ];
                    $diy_view_util_data[] = $util_item;
                }
                if ($diy_view_util_data) {
                    model('diy_view_util')->addList($diy_view_util_data);
                }
            }
            return $this->success();
        } catch ( \Exception $e ) {
            return $this->error('', $e->getMessage().'-' .$e->getFile().'-'. $e->getLine());
        }

    }

    /**************************************************************插件安装结束*********************************************************/

    /**************************************************************插件卸载开始*********************************************************/
    /**
     * 卸载插件
     * @param $addon_name
     * @return array|mixed
     */
    public function uninstall($addon_name)
    {
        Db::startTrans();
        try {
            // 插件预卸载
            $res1 = $this->preUninstall($addon_name);
            if ($res1['code'] != 0) {
                Db::rollback();
                return $res1;
            }
            // 卸载菜单
            $res2 = $this->uninstallMenu($addon_name);
            if ($res2['code'] != 0) {
                Db::rollback();
                return $res2;
            }
            $res3 = $this->uninstallDiyView($addon_name);
            if ($res3['code'] != 0) {
                Db::rollback();
                return $res3;
            }
            $delete_res = model('addon')->delete([
                ['name', '=', $addon_name]
            ]);
            if ($delete_res === false) {
                Db::rollback();
                return $this->error();
            }
            //清理缓存
            Cache::clear();
            Db::commit();
            return $this->success();
        } catch ( \Exception $e ) {
            //清理缓存
            Cache::clear();
            Db::rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     * 插件预卸载
     * @param $addon_name
     * @return mixed
     */
    private function preUninstall($addon_name)
    {
        $class_name = "addon\\" . $addon_name . "\\event\\UnInstall";
        $install = new $class_name;
        $res = $install->handle($addon_name);
        return $res;
    }

    /**
     * 卸载插件菜单
     * @param $addon_name
     * @return array
     */
    private function uninstallMenu($addon_name)
    {
        $res = model('menu')->delete([
            ['addon', '=', $addon_name]
        ]);
        return $this->success($res);
    }

    /**
     * 卸载自定义模板
     * @param $addon_name
     * @return array
     */
    private function uninstallDiyView($addon_name)
    {
        model('diy_view_temp')->delete([['addon_name', '=', $addon_name]]);
        model('link')->delete([['addon_name', '=', $addon_name]]);
        model('diy_view_util')->delete([['addon_name', '=', $addon_name]]);
        return $this->success();
    }

    /***************************************************************插件卸载结束********************************************************/

    /************************************************************* 安装全部插件 start *************************************************************/

    /**
     * 安装全部插件
     */
    public function installAllAddon()
    {
        $addon_list_result = $this->getUninstallAddonList();
        $addon_list = $addon_list_result["data"];
        foreach ($addon_list as $k => $v) {
            $item_result = $this->install($v["name"]);
            if ($item_result["code"] < 0)
                return $item_result;

        }
        return $this->success();
    }
    /************************************************************* 安装全部插件 end *************************************************************/
}