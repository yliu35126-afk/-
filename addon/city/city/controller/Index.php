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

namespace addon\city\city\controller;

use app\model\system\Config;
use app\model\web\Notice as NoticeModel;
use app\model\web\WebSite as WebsiteModel;

class Index extends BaseCity
{
    public function index()
    {
        $config_model = new Config();

        //站点信息
        $website_model = new WebsiteModel();
        $website_info = $website_model->getWebSite([ [ 'site_id', '=', $this->site_id ] ], 'account,account_withdraw,account_shop,account_order');
        $total_account = $website_info[ 'data' ][ 'account' ] + $website_info[ 'data' ][ 'account_withdraw' ];
        $this->assign('total_account', number_format($total_account, 2, '.', ''));
        $this->assign('website_info', $website_info[ 'data' ]);

        $system_config = $config_model->getSystemConfig();

        $this->assign('system_config', $system_config[ 'data' ]);

        //网站公告
        $notice_model = new NoticeModel();
        $notice_list = $notice_model->getNoticePageList([ [ 'receiving_type', 'like', '%website%' ] ], 1, 5, 'is_top desc,create_time desc', 'id,title');
        $this->assign('notice_list', $notice_list[ 'data' ][ 'list' ]);

        //平台配置信息
        $website = $website_model->getWebSite([ [ 'site_id', '=', 0 ] ], 'web_qrcode,web_phone');
        $this->assign('website', $website[ 'data' ]);

        return $this->fetch('index/index', [], $this->replace);

    }

}