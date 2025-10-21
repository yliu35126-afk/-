<?php
/**
 * tshade�̳�ϵͳ - �Ŷ�ʮ����̾���㼯����!
 * =========================================================
 * Copy right 2019-2029 �Ƽ����޹�˾, ��������Ȩ����
 * ----------------------------------------------
 * �ٷ���ַ: https://www.shade.com

 * =========================================================
 */

namespace app\event;

use app\model\system\Addon;
use think\facade\Event;
use think\facade\Cache;

/**
 * ��ʼ�����
 */
class InitAddon
{
    // ��Ϊ��չ��ִ����ڱ�����run
    public function handle()
    {
        if (defined('BIND_MODULE') && BIND_MODULE === 'install')
            return;
        $this->initEvent();
    }

    /**
     * ��ʼ���¼�
     */
    private function InitEvent()
    {
        $cache = Cache::get("addon_event_list");

        if (empty($cache)) {
            $addon_model = new Addon();
            $addon_data  = $addon_model->getAddonList([], 'name');

            $listen_array = [];
            foreach ($addon_data['data'] as $k => $v) {
                if (file_exists('addon/' . $v['name'] . '/config/event.php')) {
                    $addon_event = require_once 'addon/' . $v['name'] . '/config/event.php';

                    $listen = isset($addon_event['listen']) ? $addon_event['listen'] : [];
                    if (!empty($listen)) {
                        $listen_array[] = $listen;
                    }
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

    }

}