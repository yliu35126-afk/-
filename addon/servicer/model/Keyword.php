<?php
/**
 * Niushop商城系统 - 团队十年电商经验汇集巨献!
 * =========================================================
 * Copy right 2019-2029 上海牛之云网络科技有限公司, 保留所有权利。
 * ----------------------------------------------
 * 官方网址: https://www.niushop.com
 * =========================================================
 */

namespace addon\servicer\model;

use app\model\BaseModel;
use think\facade\Cache;

/**
 * 关键词回复
 * Class Keyword
 * @package addon\servicer\model
 */
class Keyword extends BaseModel
{
    /**
     * 缓冲标签名称
     */
    const CACHE_TAG_NAME = 'servicer_keyword_reply';

    /**
     * 表名称
     */
    const TABLE_NAME = 'servicer_keyword_reply';

    /**
     * 消息类型 -- 文本
     */
    const CONTENT_TYPE_TEXT = 0;

    /**
     * 消息类型 -- 商品
     */
    const CONTENT_TYPE_GOODS = 1;

    /**
     * 消息类型 -- 订单
     */
    const CONTENT_TYPE_ORDER = 2;

    /**
     * 消息类型 -- 图片
     */
    const CONTENT_TYPE_IMAGE = 3;

    /**
     * 状态
     * @param mixed $content_type
     * @param string $type
     * @return array
     */
    public static function contentType($content_type = null, $type = 'content_type')
    {
        $data   = array(
            array(
                'content_type' => self::CONTENT_TYPE_TEXT,
                'name'         => '文本',
                'type'         => 'text',
                'const'        => 'CONTENT_TYPE_STRING',
            ),
            array(
                'content_type' => self::CONTENT_TYPE_GOODS,
                'name'         => '商品',
                'type'         => 'goods',
                'const'        => 'CONTENT_TYPE_GOODS',
            ),
            array(
                'content_type' => self::CONTENT_TYPE_ORDER,
                'name'         => '订单',
                'type'         => 'order',
                'const'        => 'CONTENT_TYPE_ORDER',
            ),
            array(
                'content_type' => self::CONTENT_TYPE_IMAGE,
                'name'         => '图片',
                'type'         => 'image',
                'const'        => 'CONTENT_TYPE_IMAGE',
            ),
        );
        $result = [];
        if (is_null($content_type)) {
            $result = $data;
        } else {
            if (is_array($content_type)) {

                foreach ($data as $val) {

                    if (in_array($val[$type], $content_type)) {
                        $result[] = $val;
                    }
                }
            } else {
                foreach ($data as $val) {
                    if ($content_type == $val[$type]) {
                        $result = $val;
                        break;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * 添加
     * @param array $params
     * @return array
     */
    public function add(array $params)
    {
        $site_id      = $params['site_id'] ?? '';
        $keyword      = $params['keyword'] ?? '';
        $content_type = $params['content_type'] ?? '';

        if ($site_id === '') {
            return $this->error('', 'REQUEST_SITE_ID');
        }
        if ($keyword === '') {
            return $this->error('', 'PARAMETER_ERROR');
        }
        if (is_null($content_type)) {
            return $this->error('', 'PARAMETER_ERROR');
        }
        if (empty(self::contentType($content_type))) {
            return $this->error('', 'PARAMETER_ERROR');
        }

        // 检测重复
        $info = $this->getInfo([['site_id', '=', $site_id], ['keyword', '=', $keyword]], 'id')['data'];
        if (!empty($info)) {
            return $this->error('', '关键词重复');
        }

        $res = model(self::TABLE_NAME)->add(array_merge($params, ['create_time' => time()]));

        Cache::tag(self::CACHE_TAG_NAME)->clear();
        return $this->success($res);
    }

    /**
     * 编辑
     * @param array $params
     * @param array $condition
     * @return array
     */
    public function edit(array $params, array $condition)
    {
        $check_condition = array_column($condition, 2, 0);
        $site_id         = $check_condition['site_id'] ?? 0;
        $id              = $check_condition['id'] ?? 0;
        $keyword         = $params['keyword'] ?? '';
        $content_type    = $params['content_type'] ?? '';

        if ($site_id === '') {
            return $this->error('', 'REQUEST_SITE_ID');
        }
        if (empty($id)) {
            return $this->error('', 'PARAMETER_ERROR');
        }
        if ($keyword === '') {
            return $this->error('', 'PARAMETER_ERROR');
        }
        if (is_null($content_type)) {
            return $this->error('', 'PARAMETER_ERROR');
        }
        if (empty(self::contentType($content_type))) {
            return $this->error('', 'PARAMETER_ERROR');
        }

        // 检测是否存在
        $info = $this->getInfo($condition, 'id')['data'];
        if (empty($info)) {
            return $this->error('', '数据不存在');
        }

        // 检测重复
        $info = $this->getInfo([
            ['site_id', '=', $site_id],
            ['keyword', '=', $keyword],
            ['id', '<>', $id],
        ], 'id')['data'];
        if (!empty($info)) {
            return $this->error('', '关键词重复');
        }

        $res = model(self::TABLE_NAME)->update($params, $condition);
        Cache::tag(self::CACHE_TAG_NAME)->clear();
        return $this->success($res);
    }

    /**
     * 修改
     * @param $data
     * @param array $condition
     * @return array
     */
    public function update($data, array $condition)
    {
        $res = model(self::TABLE_NAME)->update($data, $condition);

        Cache::tag(self::CACHE_TAG_NAME)->clear();
        return $this->success($res);
    }

    /**
     * 删除
     * @param array $condition
     * @return array
     */
    public function delete(array $condition)
    {
        $check_condition = array_column($condition, 2, 0);
        $site_id         = $check_condition['site_id'] ?? 0;

        if ($site_id === '') {
            return $this->error('', 'REQUEST_SITE_ID');
        }

        $res = model(self::TABLE_NAME)->delete($condition);
        Cache::tag(self::CACHE_TAG_NAME)->clear();
        return $this->success($res);
    }

    /**
     * 获取信息
     * @param $condition
     * @param bool $field
     * @return array
     */
    public function getInfo($condition, $field = true)
    {
        $params     = json_encode(func_get_args());
        $cache_name = self::CACHE_TAG_NAME . '_' . __FUNCTION__ . '_' . $params;

        $res = Cache::get($cache_name);
        if (empty($res)) {
            $res = model(self::TABLE_NAME)->getInfo($condition, $field);
            Cache::tag(self::CACHE_TAG_NAME)->set($cache_name, $res);
        }
        return $this->success($res);
    }

    /**
     * 根据关键词获取回复内容
     * @param $site_id
     * @param $keyword
     * @param int $content_type
     * @param int $is_use
     * @return mixed|string
     */
    public function getContentByKeyword($site_id, $keyword, $content_type = self::CONTENT_TYPE_TEXT, $is_use = 1)
    {
        $content = '';

        if ($site_id !== '' && !empty($keyword)) {
            $info = $this->getInfo([
                ['site_id', '=', $site_id],
                ['keyword', '=', $keyword],
                ['is_use', '=', $is_use],
                ['content_type', '=', $content_type],
            ], 'content')['data'];
            if (!empty($info)) {
                $content = $info['content'];
            }
        }
        return $content;
    }

    /**
     * 获取分页列表
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param bool $field
     * @param string $alias
     * @param array $join
     * @return array
     */
    public function getPageList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = 'id desc', $field = true, $alias = '', $join = [])
    {
        $params     = json_encode(func_get_args());
        $cache_name = self::CACHE_TAG_NAME . '_' . __FUNCTION__ . '_' . $params;

        $res = Cache::get($cache_name);
        if (empty($res)) {

            $res = model(self::TABLE_NAME)->pageList($condition, $field, $order, $page, $page_size, $alias, $join);
            Cache::tag(self::CACHE_TAG_NAME)->set($cache_name, $res);
        }

        return $this->success($res);
    }

    /**
     * 获取列表
     * @param array $condition
     * @param mixed $field
     * @param string $order
     * @param null $limit
     * @return array
     */
    public function getList($condition = [], $field = true, $order = 'id desc', $limit = null)
    {
        $params     = json_encode(func_get_args());
        $cache_name = self::CACHE_TAG_NAME . '_' . __FUNCTION__ . '_' . $params;

        $res = Cache::get($cache_name);
        if (empty($res)) {

            $res = model(self::TABLE_NAME)->getList($condition, $field, $order, '', '', '', $limit);
            Cache::tag(self::CACHE_TAG_NAME)->set($cache_name, $res);
        }

        return $this->success($res);
    }

    /**
     * 获取数量
     * @param $condition
     * @param string $field
     * @return array
     */
    public function getCount($condition, $field = 'id')
    {
        $params     = json_encode(func_get_args());
        $cache_name = self::CACHE_TAG_NAME . '_' . __FUNCTION__ . '_' . $params;

        $res = Cache::get($cache_name);
        if (empty($res)) {

            $res = model(self::TABLE_NAME)->getCount($condition, $field);

            Cache::tag(self::CACHE_TAG_NAME)->set($cache_name, $res);
        }

        return $this->success($res);
    }
}