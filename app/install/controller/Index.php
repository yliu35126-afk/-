<?php

namespace app\install\controller;

use app\model\shop\Shop;
use app\model\shop\ShopCategory;
use app\model\shop\ShopGroup as ShopGroupModel;
use app\model\system\Addon;
use app\model\system\Api;
use app\model\system\Cron;
use app\model\system\DiyTemplate;
use app\model\system\Document;
use app\model\system\Group;
use app\model\system\H5;
use app\model\system\Menu;
use app\model\system\Menu as MenuModel;
use app\model\system\Promotion as PromotionModel;
use app\model\system\User;
use app\model\upload\Album;
use app\model\web\Adv as AdvModel;
use app\model\web\AdvPosition;
use app\model\web\DiyView;
use app\model\web\Help as HelpModel;
use addon\pc\model\Pc;
use app\model\web\WebSite;
use think\facade\Cache;
use think\facade\Event;


class Index extends BaseInstall
{

    public function __construct()
    {
        //执行父类构造函数
        parent::__construct();
    }

    /**
     *安装
     */
    public function index()
    {
        if (file_exists($this->lock_file)) {
            $this->redirect(__ROOT__);
        }

        $step = input("step", 1);

        if ($step == 1) {
            return $this->fetch('index/step-1', [], $this->replace);
        } elseif ($step == 2) {
            //系统变量
            $system_variables = [];
            $phpv = phpversion();
            $os = PHP_OS;
            $server = $_SERVER['SERVER_SOFTWARE'];

            $host = (empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_HOST'] : $_SERVER['REMOTE_ADDR']);
            $name = $_SERVER['SERVER_NAME'];

            $verison = version_compare(PHP_VERSION, '7.1.0') == -1 ? false : true;
            //pdo
            $pdo = extension_loaded('pdo') && extension_loaded('pdo_mysql');
            $system_variables[] = ["name" => "pdo", "need" => "开启", "status" => $pdo];
            //curl
            $curl = extension_loaded('curl') && function_exists('curl_init');
            $system_variables[] = ["name" => "curl", "need" => "开启", "status" => $curl];
            //openssl
            $openssl = extension_loaded('openssl');
            $system_variables[] = ["name" => "openssl", "need" => "开启", "status" => $openssl];
            //gd
            $gd = extension_loaded('gd');
            $system_variables[] = ["name" => "GD库", "need" => "开启", "status" => $gd];
            //fileinfo
            $fileinfo = extension_loaded('fileinfo');
            $system_variables[] = ["name" => "fileinfo", "need" => "开启", "status" => $fileinfo];

            $root_path = str_replace("\\", DIRECTORY_SEPARATOR, dirname(dirname(dirname(dirname(__FILE__)))));
            $root_path = str_replace("/", DIRECTORY_SEPARATOR, $root_path);
            $dirs_list = [
                ["path" => $root_path, "path_name" => "/", "name" => "整目录"],
                ["path" => $root_path . DIRECTORY_SEPARATOR . "public", "path_name" => "public", "name" => "public"],
                ["path" => $root_path . DIRECTORY_SEPARATOR . "config", "path_name" => "config", "name" => "config"],
                ["path" => $root_path . DIRECTORY_SEPARATOR . 'runtime', "path_name" => "runtime", "name" => "runtime"],
                ["path" => $root_path . DIRECTORY_SEPARATOR . 'app/install', "path_name" => "app/install", "name" => "安装目录"]
            ];
            //目录 可读 可写检测
            $is_dir = true;
            foreach ($dirs_list as $k => $v) {
                $is_readable = is_readable($v["path"]);
                $is_write = is_write($v["path"]);
                $dirs_list[$k]["is_readable"] = $is_readable;
                $dirs_list[$k]["is_write"] = $is_write;
                if ($is_readable == false || $is_write == false) {
                    $is_dir = false;
                }
            }
            $this->assign("root_path", $root_path);
            $this->assign("system_variables", $system_variables);
            $this->assign("phpv", $phpv);
            $this->assign("server", $server);
            $this->assign("host", $host);
            $this->assign("os", $os);
            $this->assign("name", $name);
            $this->assign("verison", $verison);
            $this->assign("dirs_list", $dirs_list);
            if ($verison && $pdo && $curl && $openssl && $gd && $fileinfo && $is_dir) {
                $continue = true;
            } else {
                $continue = false;
            }
            $this->assign("continue", $continue);
            return $this->fetch('index/step-2', [], $this->replace);
        } elseif ($step == 3) {
            return $this->fetch('index/step-3', [], $this->replace);
        } elseif ($step == 4) {
            set_time_limit(300);
            $source_file = "./app/install/source/database.php";//源配置文件

            $target_dir = "./config";
            $target_file = "database.php";

            $file_name = "./app/install/source/database.sql";//数据文件

            //数据库
            $dbport = input("dbport", "3306");
            $dbhost = input("dbhost", "localhost");
            $dbuser = input("dbuser", "root");
            $dbpwd = input("dbpwd", "root");
            $dbname = input("dbname", "niushop_b2b2c");//数据库名称
            $dbprefix = input("dbprefix", "");//前缀

            //平台
            $site_name = input('site_name', "");
            $username = input('username', "");
            $password = input('password', "");
            $password2 = input('password2', "");

            //店铺
            $shop_name = input('shop_name', "");
            $shop_username = input('shop_username', "");
            $shop_password = input('shop_password', "");
            //演示数据开关
            $yanshi = input('yanshi', "");// 演示数据开关

            if ($dbhost == '' || $dbuser == '') {
                return $this->returnError([], '数据库链接配置信息丢失!');
            }
//            if ($dbprefix == '') {
//                return $this->returnError('数据表前缀为空!');
//            }

            //可写测试
            $write_result = is_write($target_dir);
            if (!$write_result) {
                //判断是否有可写的权限，linux操作系统要注意这一点，windows不必注意。
                return $this->returnError([], '配置文件不可写，权限不够!');
            }

            //数据库连接测试
            $conn = @mysqli_connect($dbhost, $dbuser, $dbpwd, "", $dbport);
            if (!$conn) {
                return $this->returnError([], '连接数据库失败！请检查连接参数!');
            }

            //平台
            if ($site_name == '' || $username == '' || $password == '') {
                return $this->returnError([], '平台信息不能为空!');
            }

            if ($password != $password2) {
                return $this->returnError([], '两次密码输入不一样，请重新输入');
            }

            //店铺是否为空
            if ($shop_name == '' || $shop_username == '' || $shop_password == '') {
                return $this->returnError([], '站点名称是否为空！');
            }

            //数据库可写和是否存在测试
            $empty_db = mysqli_select_db($conn, $dbname);
            if ($empty_db) {
                $sql = "DROP DATABASE `$dbname`";
                $retval = mysqli_query($conn, $sql);
                if (!$retval) {
                    return $this->returnError([], '删除数据库失败: ' . mysqli_error($conn));
                }
            }

            //如果数据库不存在，我们就进行创建。
            $dbsql = "CREATE DATABASE `$dbname`";
            $db_create = mysqli_query($conn, $dbsql);
            if (!$db_create) {
                return $this->returnError([], '创建数据库失败，请确认是否有足够的权限!');
            }

            //链接数据库
            @mysqli_select_db($conn, $dbname);

            //修改配置文件
            $fp = fopen($source_file, "r");
            $configStr = fread($fp, filesize($source_file));
            fclose($fp);

            $configStr = str_replace('model_hostname', $dbhost, $configStr);
            $configStr = str_replace('model_database', $dbname, $configStr);
            $configStr = str_replace("model_username", $dbuser, $configStr);
            $configStr = str_replace("model_password", $dbpwd, $configStr);
            $configStr = str_replace("model_port", $dbport, $configStr);
            $configStr = str_replace("model_prefix", $dbprefix, $configStr);

            $fp = fopen($target_dir . DIRECTORY_SEPARATOR . $target_file, "w");
            if ($fp == false) {
                return $this->returnError([], '写入配置失败，请检查' . $target_dir . '/' . $target_file . '是否可写入！');
            }

            fwrite($fp, $configStr);
            fclose($fp);

            //导入SQL并执行。
            $get_sql_data = file_get_contents($file_name);

            @mysqli_query($conn, "SET NAMES utf8");
            //提取create
            preg_match_all("/Create table .*\(.*\).*\;/iUs", $get_sql_data, $create_sql_arr);
            $create_sql_arr = $create_sql_arr[0];

            foreach ($create_sql_arr as $create_sql_item) {
                //正则匹配到数据表名,
                $match_item = preg_match('/CREATE TABLE [`]?(\\w+)[`]?/is', $create_sql_item, $match_data);
                if ($match_item > 0) {
                    $table_name = $match_data["1"];
                    $new_table_name = $dbprefix . $table_name;
                    $create_sql_item = $this->str_replace_first($table_name, $new_table_name, $create_sql_item);
                    @mysqli_query($conn, $create_sql_item);
                } else {
                    return $this->returnError('数据表解析失败！');
                }
            }

            //插入索引
            preg_match_all("/ALTER TABLE .*\(.*\)?;/iUs", $get_sql_data, $alter_sql_arr);
            $alter_sql_arr = $alter_sql_arr[0];

            foreach ($alter_sql_arr as $alter_sql_item) {
                $match_item = preg_match('/ALTER TABLE [`]?(\\w+)[`]?/is', $alter_sql_item, $match_data);
                if ($match_item > 0) {
                    $table_name = $match_data["1"];
                    $new_table_name = $dbprefix . $table_name;
                    $alter_sql_item = $this->str_replace_first($table_name, $new_table_name, $alter_sql_item);
                    @mysqli_query($conn, $alter_sql_item);
                } else {
                    return $this->returnError([], '索引插入解析失败！');
                }
            }

            //提取insert
            preg_match_all("/INSERT INTO .*\(.*\)\;/iUs", $get_sql_data, $insert_sql_arr);
            $insert_sql_arr = $insert_sql_arr[0];

            //插入数据
            foreach ($insert_sql_arr as $insert_sql_item) {
                $match_item = preg_match('/INSERT INTO [`]?(\\w+)[`]?/is', $insert_sql_item, $match_data);
                if ($match_item > 0) {
                    $table_name = $match_data["1"];
                    $new_table_name = $dbprefix . $table_name;
                    $insert_sql_item = $this->str_replace_first($table_name, $new_table_name, $insert_sql_item);
                    @mysqli_query($conn, $insert_sql_item);
                } else {
                    return $this->returnError([], '数据插入解析失败！');
                }
            }

            @mysqli_close($conn);
            $database_config = include $target_dir . DIRECTORY_SEPARATOR . $target_file;
//			config("database", $database_config);
            \think\facade\Config::set($database_config, "database");

            //安装菜单
            $menu = new Menu();
            //安装平台端菜单
            $admin_menu_res = $menu->refreshMenu('admin', '');
            if ($admin_menu_res["code"] < 0) {
                return $this->returnError([], '平台菜单安装失败！');
            }
            //安装店铺端菜单
            $shop_menu_res = $menu->refreshMenu('shop', '');
            if ($shop_menu_res["code"] < 0) {
                return $this->returnError([], '店铺菜单失败！');
            }

            //安装插件
            $addon_model = new Addon();
            //初始化自定义页面
            $diy_view_result = $addon_model->refreshDiyView('');
            if ($diy_view_result["code"] < 0) {
                return $this->returnError([], '自定义页面初始化失败！');
            }
            //安装所有插件
            $addon_result = $addon_model->installAllAddon();
            if ($addon_result["code"] < 0) {
                return $this->returnError([], $addon_result["message"]);
            }

            $this->init_data = include "./app/install/source/init.php";//源配置文件

            $initdata_result = $this->initData(input());
            if ($initdata_result["code"] < 0) {
                return $this->returnError([], '默认数据添加失败！');
            }
            $init_data = $initdata_result["data"];

            // H5端刷新(没有安装包也没必要报错)
            $h5 = new H5();
            $h5_res = $h5->refresh();
//            if ($h5_res['code'] < 0) {
//                return $this->returnError([], 'h5部署失败！');
//            }
            // 刷新内置模板
            $template = new DiyTemplate();
            $template_result = $template->refresh();
            if ($template_result['code'] < 0) {
                return $this->returnError([], '自定义模板刷新失败！');
            }


            //默认平台用户组(系统管理员)
            $group_model = new Group();
            $group_data = array(
                "site_id" => 0,
                "app_module" => "admin",
                "group_name" => "系统管理员",
                "group_status" => "1",
                "is_system" => "1",
                "menu_array" => "",
                "desc" => "",
            );
            $group_result = $group_model->addGroup($group_data);
            if ($group_result["code"] < 0) {
                return $this->returnError([], '后台管理员权限组添加失败！');
            }

            //创建默认相册
            $album_model = new Album();
            $album_data = array(
                'site_id' => 0,
                'album_name' => '默认相册',
                'app_module' => 'admin'
            );
            $album_result = $album_model->addAlbum($album_data);
            if ($album_result['code'] < 0) {
                return $this->returnError([], '平台默认相册添加失败！');
            }
            $group_id = $group_result["data"];
            $user_model = new User();
            $user_data = array(
                "app_module" => "admin",
                "app_group" => 0,
                "is_admin" => 1,
                "site_id" => 0,
                "group_id" => $group_id,
                "username" => $username,
                "password" => $password
            );
            $user_result = $user_model->addUser($user_data);
            if ($user_result["code"] < 0) {
                return $this->returnError([], '后台管理员添加失败！');
            }

            $shop_model = new Shop();
            $shop_data = array(
                "site_name" => $shop_name,
                "group_id" => 1,
                "group_name" => "官方直营",
                "category_id" => $init_data["shop_category_id"],
                "category_name" => "官方直营店",
                "is_own" => 1,
                "logo" => "/upload/default/shop/shop_logo.png"
            );
            $shop_user_data = array(
                "username" => $shop_username,
                "password" => data_md5($shop_password),
            );
            $shop_result = $shop_model->installShop($shop_data, $shop_user_data);
            if ($shop_result["code"] < 0) {
                return $this->returnError([], '默认店铺添加失败！');
            }
            $site_id = $shop_result['data'];
            //添加演示数据
            if ($yanshi) {
                // 演示数据
                $yanshi_data_result = $this->yanShiData($site_id);
                if ($yanshi_data_result["code"] < 0) {
                    return $this->returnError([], '演示数据添加失败！');
                }
            }

            $fp = fopen($this->lock_file, "w");
            if ($fp == false) {
                return $this->returnError([], "写入失败，请检查目录" . dirname(dirname(__FILE__)) . "是否可写入！'");
            }
            fwrite($fp, '已安装');
            fclose($fp);
            return $this->returnSuccess([], "安装成功");
        }
    }

    public function installSuccess()
    {
        return $this->fetch('index/step-4', [], $this->replace);
    }

    /**
     * 测试数据库
     */
    public function testDb($dbhost = '', $dbport = '', $dbuser = '', $dbpwd = '', $dbname = '')
    {
        $dbport = input("dbport", "");
        $dbhost = input("dbhost", "");
        $dbuser = input("dbuser", "");
        $dbpwd = input("dbpwd", "");
        $dbname = input("dbname", "");
        try {

            if ($dbport != "" && $dbhost != "") {
                $dbhost = $dbport != '3306' ? $dbhost . ':' . $dbport : $dbhost;
            }

            if ($dbhost == '' || $dbuser == '') {
                return $this->returnError([
                    "status" => -1,
                    "message" => "数据库账号或密码不能为空"
                ]);
            }

            if (!function_exists("mysqli_connect")) {
                return $this->returnError([
                    "status" => -1,
                    "message" => "mysqli扩展类必须开启"
                ]);
            }

            $conn = @mysqli_connect($dbhost, $dbuser, $dbpwd);
            if ($conn) {
                if (empty($dbname)) {
                    $result = [
                        "status" => 1,
                        "message" => "数据库连接成功"
                    ];
                } else {
                    if (@mysqli_select_db($conn, $dbname)) {
                        $result = [
                            "status" => 2,
                            "message" => "数据库存在，系统将覆盖数据库"
                        ];
                    } else {
                        $result = [
                            "status" => 1,
                            "message" => "数据库不存在,系统将自动创建"
                        ];
                    }
                }
            } else {
                $result = [
                    "status" => -1,
                    "message" => "数据库连接失败！"
                ];

            }
            @mysqli_close($conn);
            return $this->returnSuccess($result);
        } catch ( \Exception $e ) {
            $result = [
                "status" => -1,
                "message" => $e->getMessage()
            ];
            return $this->returnSuccess($result);
        }
    }

    /**
     * 初始化平台数据
     */
    private function initData($param)
    {
        $init_event_result = $this->initEvent();
        if ($init_event_result['code'] < 0) {
            return $init_event_result;
        }

        // 初始化自定义组件、链接
        $diyview_result = $this->initDiyView();
        if ($diyview_result['code'] < 0) {
            return $this->returnError([], '自定义组件初始化失败!');
        }

        $api_model = new Api();
        $data = array(
            "public_key" => $this->init_data['api']['public_key'],
            "private_key" => $this->init_data['api']['private_key'],
        );
        $api_result = $api_model->setApiConfig($data, 1);
        if ($api_result['code'] < 0) {
            return $this->returnError([], 'api秘钥配置失败!');
        }

        //增加默认站点(website)
        $this->initWebsite($param);

        //入驻帮助(默认文章协议)
        $this->initDocument();

        //默认店铺等级(官方直营店)
        $shop_category_result = $this->initShopCategory();

        if ($shop_category_result["code"] < 0) {
            return $shop_category_result;
        }

        $shop_category_id = $shop_category_result["data"];

        //默认开店套餐(旗舰店铺)
        $shop_group_result = $this->initShopGroup();

        // 广告位
        $this->initAdv();

        // 帮助
        $this->initHelp();
        if(addon_is_exit('pc')) {
            $this->initPcFloorBlock();
        }
        // 手机端广告位
        event('InitAdv');

        //PC导航
        if(addon_is_exit('pc')){
            event('InitPcNav');
        }


        //默认自动事件
        $this->initCron();
        return $this->returnSuccess(["shop_category_id" => $shop_category_id]);
    }

    /**
     * 初始化平台主页
     * @return array
     */
    private function initDiyView()
    {
        $addon = new Addon();
        $addon->refreshDiyView('');

        //平台主页
        $diy_view_info = $this->init_data["diy_view"];
        $diy_view_model = new DiyView();
        $result = $diy_view_model->addSiteDiyViewList($diy_view_info);
        if ($result["code"] < 0) {
            return $result;
        }

        //底部导航
        $diy_view_bottom_nav = $this->init_data["bottom_nav"];
        $result = $diy_view_model->setBottomNavConfig(json_encode($diy_view_bottom_nav), 0);

        return $result;
    }

    /**
     * 初始化店铺等级
     * @return array
     */
    public function initShopCategory()
    {
        $shop_category_model = new ShopCategory();
        $data = $this->init_data["shop_category"];

        $value = array(
            "category_name" => $data["category_name"],
            "baozheng_money" => $data["baozheng_money"],
            "sort" => $data["sort"],
        );
        $result = $shop_category_model->addCategory($value);
        return $result;
    }

    /**
     * 初始化开店套餐
     * @return array
     */
    public function initShopGroup()
    {
        $menu_model = new MenuModel();
        $promotion_model = new PromotionModel();
        $promotions = $promotion_model->getPromotions();
        foreach ($promotions['shop'] as $key => $promotion) {
            if (!empty($promotion['is_developing'])) {
                unset($promotions['shop'][$key]);
                continue;
            }
        }

        $data = $this->init_data["shop_group"];
        $menu_list = $menu_model->getMenuList([['app_module', "=", 'shop']], "name");
        $data['menu_array'] = join(',', array_column($menu_list['data'], 'name'));// 营销插件权限组
        $data['addon_array'] = join(',', array_column($promotions['shop'], 'name'));// 营销插件
        $shop_group_model = new ShopGroupModel();
        $shop_group_result = $shop_group_model->addGroup($data);
        return $shop_group_result;
    }

    /**
     * 初始化站点信息
     * @param $param
     * @return array
     */
    public function initWebsite($param)
    {
        $website_model = new WebSite();
        $data = array(
            "site_id" => 0,
            "title" => $param["site_name"],
            "create_time" => time(),
            "web_status" => 1,//网站状态  默认开启
            "web_phone" => "400-886-7993"
        );
        $condition = array(
            "site_id" => 0
        );
        $result = $website_model->setWebSite($data, $condition);
        return $result;
    }

    /**
     * 初始化文章
     */
    public function initDocument()
    {
        $document_model = new Document();
        $data = $this->init_data["document"];

        foreach ($data as $k => $v) {
            $condition = array(
                ["site_id", "=", $v["site_id"]],
                ["app_module", "=", $v["app_module"]],
                ["document_key", "=", $v["document_key"]],
            );
            $document_model->setDocument($v["title"], $v["content"], $condition);
        }
    }

    /**
     * 初始化插件
     */
    private function initEvent()
    {
        try {
            $cache = Cache::get("addon_event_list");

            if (empty($cache)) {
                $addon_model = new Addon();
                $addon_data = $addon_model->getAddonList([], 'name');

                $listen_array = [];
                foreach ($addon_data['data'] as $k => $v) {
                    $addon_event = require_once 'addon/' . $v['name'] . '/config/event.php';

                    $listen = isset($addon_event['listen']) ? $addon_event['listen'] : [];
                    if (!empty($listen)) {
                        $listen_array[] = $listen;
                    }
                }
                Cache::tag("addon")->set("addon_event_list", $listen_array);
            } else {
                $listen_array = $cache;
            }

            if (!empty($listen_array)) {
                foreach ($listen_array as $k => $listen) {
                    if (!empty($listen)) {
                        Event::listenEvents($listen);
                    }

                }
            }
            return $this->returnSuccess();
        } catch ( \Exception $e ) {
            return $this->returnError('', $e->getMessage());
        }
    }

    /**
     * 初始化自动事件
     */
    public function initCron()
    {
        $data = $this->init_data["cron"];
        $cron_model = new Cron();
        foreach ($data as $k => $v) {
            $item_result = $cron_model->addCron($v["type"], $v["period"], $v["name"], $v["event"], $v["execute_time"], $v["relate_id"], $v["period_type"]);
            if ($item_result["code"] < 0) {
                return $item_result;
            }
        }
        return $this->returnSuccess();
    }

    function str_replace_first($search, $replace, $subject)
    {
        return implode($replace, explode($search, $subject, 2));
    }

    /**
     * 初始化广告图
     */
    public function initAdv()
    {
        $adv_position_model = new AdvPosition();
        $adv_model = new AdvModel();

        $adv_position_data = $this->init_data["adv_position"];
        foreach ($adv_position_data as $k => $v) {
            $adv_data = $v['adv'];
            unset($v['adv']);
            $res_adv_position = $adv_position_model->addAdvPosition($v);
            $ap_id = $res_adv_position['data'];
            if (!empty($ap_id) && !empty($adv_data)) {
                foreach ($adv_data as $ck => $cv) {
                    $cv['ap_id'] = $ap_id;
                    $adv_model->addAdv($cv);
                }
            }

        }
    }

    /**
     * 初始化帮助
     */
    public function initHelp()
    {
        $help_model = new HelpModel();

        $help_data = $this->init_data["help"];
        foreach ($help_data as $k => $v) {
            $child_list = $v['child_list'];
            unset($v['child_list']);
            $res_help = $help_model->addHelpClass($v);
            $class_id = $res_help['data'];
            if (!empty($class_id)) {
                foreach ($child_list as $ck => $cv) {
                    $cv['class_id'] = $class_id;
                    $cv['class_name'] = $v['class_name'];
                    $help_model->addHelp($cv);
                }
            }
        }
    }

    /**
     * 初始化PC端首页楼层模板
     * @return array
     */
    public function initPcFloorBlock()
    {
        $pc_model = new Pc();
        $floor_block_data = $this->init_data["floor_block"];
        $res = $pc_model->addFloorBlockList($floor_block_data);
        return $res;
    }

    /**
     * 清空目录
     * @param $path
     */
    public function clean_dir($path)
    {
        if (!is_dir($path)) {
            if (is_file($path)) {
                unlink($path);
            }
            return;
        }
        $p = opendir($path);
        while ($f = readdir($p)) {
            if ($f == "." || $f == "..") {
                continue;
            }
            $this->clean_dir($path . $f);
        }
        @rmdir($path);
        return;
    }


    /**
     * 演示数据
     * @param $sys_uid
     * @return array
     */
    private function yanShiData($site_id)
    {
        $result_array = event("AddYanshiData", ['site_id' => $site_id]);
        if (!empty($result_array)) {
            foreach ($result_array as $item) {
                if (!empty($item) && $item['code'] < 0) {
                    return $this->returnError([], $item['message']);
                }
            }
        }
        return $this->returnSuccess();
    }

}
