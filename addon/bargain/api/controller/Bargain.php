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

namespace addon\bargain\api\controller;

use app\api\controller\BaseApi;
use addon\bargain\model\Bargain as BargainModel;

/**
 * 砍价
 */
class Bargain extends BaseApi
{
    /**
     * 获取我的砍价详情
     */
    public function info()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $id = isset($this->params[ 'id' ]) ? $this->params[ 'id' ] : 0;
        if (empty($id)) {
            return $this->response($this->error('', 'REQUEST_ID'));
        }

        $condition = [
            [ 'launch_id', '=', $id ],
        ];
        $bargain = new BargainModel();
        $data = $bargain->getBargainLaunchDetail($condition);
        if ($data[ 'code' ] == 0) {

            if ($token[ 'code' ] == 0) {
                $bargain_goods_info = $bargain->getBargainGoodsDetail([ [ 'pbg.sku_id', '=', $data[ 'data' ][ 'sku_id' ] ] ], 'pbg.bargain_stock');
                $bargain_goods_info = $bargain_goods_info[ 'data' ];
                $data[ 'data' ][ 'bargain_stock' ] = $bargain_goods_info[ 'bargain_stock' ];
                if ($data[ 'data' ][ 'member_id' ] == $this->member_id) {
                    $data[ 'data' ][ 'self' ] = 1;
                } else {
                    $data[ 'data' ][ 'self' ] = 0;
                    $record_info = $bargain->getBargainRecordInfo([ [ 'launch_id', '=', $id ], [ 'member_id', '=', $this->member_id ] ], 'id');
                    $data[ 'data' ][ 'cut' ] = empty($record_info[ 'data' ]) ? 0 : 1;
                }
            } else {
                $data[ 'data' ][ 'self' ] = 0;
                $data[ 'data' ][ 'cut' ] = 0;
            }
        }
        return $this->response($data);
    }

    /**
     * 获取我的砍价分页列表
     */
    public function launchPage()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $page = $this->params[ 'page' ] ?? 1;
        $page_size = $this->params[ 'page_size' ] ?? PAGE_LIST_ROWS;
        $status = $this->params[ 'status' ] ?? 'all';

        $condition = [
            [ 'member_id', '=', $this->member_id ]
        ];
        if ($status != 'all') {
            $condition[] = [ 'status', '=', $status ];
        }
        $bargain = new BargainModel();
        $data = $bargain->getBargainLaunchPageList($condition, '*', 'launch_id desc', $page, $page_size);

        return $this->response($data);
    }

    /**
     * 发起砍价
     * @return false|string
     */
    public function launch()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $id = isset($this->params[ 'id' ]) ? $this->params[ 'id' ] : 0;
        if (empty($id)) {
            return $this->response($this->error('', 'REQUEST_ID'));
        }

        $bargain = new BargainModel();
        $res = $bargain->launch($id, $this->member_id);

        return $this->response($res);
    }

    /**
     * 砍价
     */
    public function bargain()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $id = isset($this->params[ 'id' ]) ? $this->params[ 'id' ] : 0;
        if (empty($id)) {
            return $this->response($this->error('', 'REQUEST_ID'));
        }

        $bargain = new BargainModel();
        $res = $bargain->bargain($id, $this->member_id);

        return $this->response($res);
    }

    /**
     * 获取砍价记录
     * @return false|string
     */
    public function record()
    {
        $token = $this->checkToken();
        if ($token[ 'code' ] < 0) return $this->response($token);

        $id = isset($this->params[ 'id' ]) ? $this->params[ 'id' ] : 0;
        if (empty($id)) {
            return $this->response($this->error('', 'REQUEST_ID'));
        }

        $page = $this->params[ 'page' ] ?? 1;
        $page_size = $this->params[ 'page_size' ] ?? PAGE_LIST_ROWS;

        $condition = [
            [ 'launch_id', '=', $id ]
        ];

        $bargain = new BargainModel();
        $data = $bargain->getBargainRecordPageList($condition, '*', 'id desc', $page, $page_size);

        return $this->response($data);
    }
}