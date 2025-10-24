<?php
namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Option;
use think\facade\Db;

class SeedTurntableSlots extends Command
{
    protected function configure()
    {
        $this->setName('seed:turntable_slots')
            ->setDescription('Insert demo slots into lottery_slot table')
            ->addOption('board', null, Option::VALUE_REQUIRED, 'Board ID to seed')
            ->addOption('count', null, Option::VALUE_OPTIONAL, 'How many slots to add (1-16)', 6);
    }

    protected function execute(Input $input, Output $output)
    {
        $boardId = (int)$input->getOption('board');
        $count = (int)$input->getOption('count');

        if ($boardId <= 0) {
            $output->writeln('<error>--board is required and must be > 0</error>');
            return 1;
        }
        if ($count <= 0) $count = 6;
        if ($count > 16) $count = 16;

        // Read existing positions to avoid duplicates
        $existing = Db::name('lottery_slot')
            ->where('board_id', $boardId)
            ->column('position');
        $existing = array_map('intval', $existing);

        $now = time();
        $defaultImg = '/app/admin/view/public/img/default_goods.png';
        $titles = [
            '优惠券', '余额红包', '积分奖励', '再接再厉', '谢谢参与', '随机奖励',
            '幸运值+1', '体验卡', '折扣券', '代金券', '返现券', '补签卡',
            '加速卡', '小礼品', '神秘奖品', '鼓励奖'
        ];
        $prizeTypes = [
            'coupon', 'balance', 'point', 'thanks', 'thanks', 'virtual',
            'virtual', 'virtual', 'coupon', 'coupon', 'balance', 'virtual',
            'virtual', 'goods', 'virtual', 'thanks'
        ];

        $rows = [];
        $pos = 0; $added = 0;
        while ($added < $count && $pos < 16) {
            if (!in_array($pos, $existing, true)) {
                $rows[] = [
                    'board_id'   => $boardId,
                    'position'   => $pos,
                    'prize_type' => $prizeTypes[$pos],
                    'title'      => $titles[$pos],
                    'img'        => $defaultImg,
                    'goods_id'   => 0,
                    'sku_id'     => 0,
                    'inventory'  => 0,
                    'weight'     => 10,
                    'value'      => 0,
                    'status'     => 1,
                    'create_time'=> $now,
                    'update_time'=> $now,
                ];
                $added++;
            }
            $pos++;
        }

        if (empty($rows)) {
            $output->writeln('<comment>No available positions to seed on board '.$boardId.'</comment>');
            return 0;
        }

        $res = Db::name('lottery_slot')->insertAll($rows);
        if ($res) {
            $output->writeln('<info>Inserted '.$res.' slot(s) for board '.$boardId.'</info>');
            return 0;
        }
        $output->writeln('<error>Insert failed</error>');
        return 1;
    }
}