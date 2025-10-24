<?php
/**
 * 抽奖盘管理控制器
 */
namespace addon\turntable\admin\controller;

use app\admin\controller\BaseAdmin;

class Board extends BaseAdmin
{
    /**
     * 抽奖盘列表
     */
    public function lists()
    {
        if (request()->isAjax()) {
            $condition = [
                ['site_id', '=', $this->site_id]
            ];

            $status = input('status', '');
            if ($status !== '') {
                $condition[] = ['status', '=', $status];
            }

            $name = input('name', '');
            if (!empty($name)) {
                $condition[] = ['name', 'like', '%' . $name . '%'];
            }

            $page = (int)input('page', 1);
            $page_size = (int)input('page_size', PAGE_LIST_ROWS);

            $model = model('lottery_board');
            $result = $model->pageList($condition, '*', 'board_id desc', $page, $page_size);
            return success(0, '', $result);
        } else {
            return $this->fetch('board/lists');
        }
    }

    /**
     * 新增抽奖盘
     */
    public function add()
    {
        if (request()->isAjax()) {
            $name = input('name', '');
            $status = (int)input('status', 1);
            $round_control = (int)input('round_control', 0);

            if ($name === '') {
                return success(-1, '名称不能为空', null);
            }

            $data = [
                'site_id' => $this->site_id,
                'name' => $name,
                'status' => $status,
                'round_control' => $round_control,
                'create_time' => time(),
                'update_time' => time(),
            ];

            $model = model('lottery_board');
            $res = $model->add($data);
            if ($res) {
                return success(0, '添加成功', [ 'board_id' => $res ]);
            }
            return success(-1, '添加失败', null);
        } else {
            return $this->fetch('board/add');
        }
    }

    /**
     * 编辑抽奖盘
     */
    public function edit()
    {
        $board_id = (int)input('board_id', 0);
        $model = model('lottery_board');

        if (request()->isAjax()) {
            if ($board_id <= 0) {
                return success(-1, '参数错误', null);
            }

            $name = input('name', '');
            $status = (int)input('status', 1);
            $round_control = (int)input('round_control', 0);

            if ($name === '') {
                return success(-1, '名称不能为空', null);
            }

            $data = [
                'name' => $name,
                'status' => $status,
                'round_control' => $round_control,
                'update_time' => time(),
            ];

            $res = $model->update($data, [['board_id', '=', $board_id], ['site_id', '=', $this->site_id]]);
            if ($res) {
                return success(0, '修改成功', null);
            }
            return success(-1, '修改失败', null);
        } else {
            $info = $model->getInfo([['board_id', '=', $board_id], ['site_id', '=', $this->site_id]]);
            $this->assign('info', $info);
            return $this->fetch('board/edit');
        }
    }

    /**
     * 删除抽奖盘
     */
    public function delete()
    {
        if (request()->isAjax()) {
            $board_id = (int)input('board_id', 0);
            if ($board_id <= 0) {
                return success(-1, '参数错误', null);
            }
            $model = model('lottery_board');
            $res = $model->delete([['board_id', '=', $board_id], ['site_id', '=', $this->site_id]]);
            if ($res) {
                return success(0, '删除成功', null);
            }
            return success(-1, '删除失败', null);
        }
    }
}