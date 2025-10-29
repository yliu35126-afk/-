<?php
/**
 * 格子管理控制器
 */
namespace addon\turntable\admin\controller;

use app\admin\controller\BaseAdmin;

class Slot extends BaseAdmin
{
    /**
     * 格子列表
     */
    public function lists()
    {
        if (request()->isAjax()) {
            $condition = [];
            $board_id = (int)input('board_id', 0);
            if ($board_id > 0) {
                $condition[] = ['board_id', '=', $board_id];
            }

            $page = (int)input('page', 1);
            $page_size = (int)input('page_size', PAGE_LIST_ROWS);

            $model = model('lottery_slot');
            // 正确顺序：condition, field, order, page, page_size（参考 app\model\Model::pageList）
            $result = $model->pageList($condition, '*', 'board_id asc, position asc', $page, $page_size);
            return success(0, '', $result);
        } else {
            $board_id = (int)input('board_id', 0);
            $this->assign('board_id', $board_id);
            return $this->fetch('slot/lists');
        }
    }

    /**
     * 新增格子
     */
    public function add()
    {
        if (request()->isAjax()) {
            $board_id = (int)input('board_id', 0);
            $position = (int)input('position', 0);
            $prize_type = input('prize_type', 'thanks');
            $source_type = input('source_type', 'self');
            $title = input('title', '');
            $img = input('img', '');
            $goods_id = (int)input('goods_id', 0);
            $sku_id = (int)input('sku_id', 0);
            $inventory = (int)input('inventory', 0);
            $weight = (int)input('weight', 0);
            $value = (float)input('value', 0);

            if ($board_id <= 0) return success(-1, '缺少盘ID', null);
            if ($position < 0 || $position > 15) return success(-1, '位置需在0-15之间', null);
            if ($title === '') return success(-1, '标题不能为空', null);

            $data = [
                'board_id' => $board_id,
                'position' => $position,
                'prize_type' => $prize_type,
                'source_type' => $source_type,
                'title' => $title,
                'img' => $img,
                'goods_id' => $goods_id,
                'sku_id' => $sku_id,
                'inventory' => $inventory,
                'weight' => $weight,
                'value' => $value,
                'create_time' => time(),
                'update_time' => time(),
            ];

            $model = model('lottery_slot');
            $res = $model->add($data);
            if ($res) return success(0, '添加成功', ['slot_id' => $res]);
            return success(-1, '添加失败', null);
        } else {
            $board_id = (int)input('board_id', 0);
            $this->assign('board_id', $board_id);
            return $this->fetch('slot/add');
        }
    }

    /**
     * 编辑格子
     */
    public function edit()
    {
        $slot_id = (int)input('slot_id', 0);
        $model = model('lottery_slot');
        if (request()->isAjax()) {
            if ($slot_id <= 0) return success(-1, '参数错误', null);
            $position = (int)input('position', 0);
            $prize_type = input('prize_type', 'thanks');
            $source_type = input('source_type', 'self');
            $title = input('title', '');
            $img = input('img', '');
            $goods_id = (int)input('goods_id', 0);
            $sku_id = (int)input('sku_id', 0);
            $inventory = (int)input('inventory', 0);
            $weight = (int)input('weight', 0);
            $value = (float)input('value', 0);

            if ($title === '') return success(-1, '标题不能为空', null);

            // 业务校验与清理：根据奖品类型处理商品字段
            if ($prize_type === 'goods') {
                if ($goods_id <= 0) {
                    return success(-1, '请选择商品', null);
                }
                // sku_id 可选，允许为 0
            } else {
                // 非商品奖，清空商品相关字段，避免脏数据
                $goods_id = 0;
                $sku_id = 0;
            }

            $data = [
                'position' => $position,
                'prize_type' => $prize_type,
                'source_type' => $source_type,
                'title' => $title,
                'img' => $img,
                'goods_id' => $goods_id,
                'sku_id' => $sku_id,
                'inventory' => $inventory,
                'weight' => $weight,
                'value' => $value,
                'update_time' => time(),
            ];
            $res = $model->update($data, [['slot_id', '=', $slot_id]]);
            if ($res) return success(0, '修改成功', null);
            return success(-1, '修改失败', null);
        } else {
            $info = $model->getInfo([["slot_id", "=", $slot_id]]);
            $this->assign('info', $info);
            return $this->fetch('slot/edit');
        }
    }

    /**
     * 删除格子
     */
    public function delete()
    {
        if (request()->isAjax()) {
            $slot_id = (int)input('slot_id', 0);
            if ($slot_id <= 0) return success(-1, '参数错误', null);
            $model = model('lottery_slot');
            $res = $model->delete([["slot_id", "=", $slot_id]]);
            if ($res) return success(0, '删除成功', null);
            return success(-1, '删除失败', null);
        }
    }
}
?>