<?php
namespace app\api\controller;

use app\api\controller\BaseApi;
use think\facade\Db;

class Prizeapi extends BaseApi
{
    /**
     * 获取所有奖品列表
     * 返回：prize_id、name、price、stock
     */
    public function list()
    {
        $page  = intval($this->params['page'] ?? 1);
        $limit = intval($this->params['limit'] ?? 20);
        $status = $this->params['status'] ?? null;

        $query = Db::name('lottery_prize');
        if ($status !== null && $status !== '') {
            $query->where('status', intval($status));
        }
        $total = $query->count();
        $list = $query->order('prize_id desc')->page($page, $limit)->select()->toArray();
        $items = [];
        foreach ($list as $row) {
            $items[] = [
                'prize_id' => intval($row['prize_id']),
                'name' => strval($row['name']),
                'price' => floatval($row['price']),
                'stock' => intval($row['stock']),
            ];
        }
        return $this->response($this->success([ 'total' => $total, 'list' => $items ]));
    }

    /**
     * 新增奖品
     * 请求：name, type, price, stock
     */
    public function create()
    {
        $token = $this->checkToken();
        if ($token['code'] < 0) return $this->response($token);

        $name  = trim($this->params['name'] ?? '');
        $type  = strtolower(trim($this->params['type'] ?? 'goods'));
        $price = floatval($this->params['price'] ?? 0);
        $stock = intval($this->params['stock'] ?? 0);
        if (!$name || !in_array($type, ['virtual','cash','goods'])) {
            return $this->response($this->error('', '参数不合法'));
        }
        $data = [
            'name' => $name,
            'type' => $type,
            'price' => $price,
            'stock' => $stock,
            'status' => 1,
            'create_time' => time(),
            'update_time' => time(),
        ];
        Db::name('lottery_prize')->insert($data);
        return $this->response($this->success([ 'status' => 'success' ]));
    }
}