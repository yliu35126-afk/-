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

namespace addon\notes\model;

use app\model\BaseModel;

/**
 * 笔记点赞记录
 */
class Record extends BaseModel
{
    /**
     * 添加点赞记录
     * @param $data
     * @return array
     */
    public function addRecord($data)
    {
        $info = model('notes_dianzan_record')->getInfo([['member_id', '=', $data['member_id']], ['note_id', '=', $data['note_id']]], 'record_id');
        if (empty($info)) {

            $res = model('notes_dianzan_record')->add($data);
            if ($res) {
                model("notes")->setInc([['note_id', '=', $data['note_id']]], 'dianzan_num', 1);
            }
            return $this->success($res);
        } else {
            return $this->error();
        }

    }

    /**
     * 取消点赞
     * @param $member_id
     * @param $note_id
     * @return array
     */
    public function deleteRecord($member_id, $note_id)
    {
        $res = model('notes_dianzan_record')->delete([['member_id', '=', $member_id], ['note_id', '=', $note_id]]);
        if ($res) {
            model("notes")->setDec([['note_id', '=', $note_id]], 'dianzan_num', 1);
        }

        return $this->success($res);
    }

    /**
     * 检测商品是否收藏
     * @param $note_id
     * @param $member_id
     * @return array
     */
    public function getIsDianzan($note_id, $member_id)
    {
        $count = model('notes_dianzan_record')->getCount([['member_id', '=', $member_id], ['note_id', '=', $note_id]]);
        return $this->success($count);
    }

}