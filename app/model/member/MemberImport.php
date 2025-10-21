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

namespace app\model\member;

use app\model\BaseModel;

/**
 * 会员导入
 */
class MemberImport extends BaseModel
{

    /**
     * @param $param
     * @return array
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     */
    public function importMember($param)
    {
        $PHPExcel = new \PHPExcel();
        //如果excel文件后缀名为.xls，导入这个类
        $PHPReader = new \PHPExcel_Reader_Excel2007();
        //载入文件
        $PHPExcel = $PHPReader->load($param[ 'path' ]);

        //获取表中的第一个工作表，如果要获取第二个，把0改为1，依次类推
        $currentSheet = $PHPExcel->getSheet(0);

        //获取总行数
        $allRow = $currentSheet->getHighestRow();

        if ($allRow < 2) {
            return $this->error('', '导入了一个空文件');
        }

        $index = $param[ 'index' ];

        //每次导入100条
        $length = $index * 100;
        if($index == 1){
            $num = 2;
            $success_num = 0;
            $error_num = 0;

            $data_record = [
                'filename' => $param['filename'],
                'path' => $param['path'],
                'member_num' => ($allRow - 1),
                'success_num' => 0,
                'error_num' => 0,
                'create_time' => time(),
                'status_name' => '等待导入'
            ];
            $record = model('member_import_record')->add($data_record);

        }else{
            $num = (($index - 1 ) * 100) + 1;
            $success_num = $param['success_num'];
            $error_num = $param['error_num'];
            $record = $param['record'];
        }
        $type_num = 0;
        model('member')->startTrans();
        try {

            for ($i = $num; $i <= $length; $i++) {

                if($i > $allRow){
                    break;
                }
                $type_num = $i;
                //用户名
                $username = $PHPExcel->getActiveSheet()->getCell('A' . $i)->getValue();
                $username = trim($username, ' ');

                //手机号
                $mobile = $PHPExcel->getActiveSheet()->getCell('B' . $i)->getValue();
                $mobile = trim($mobile, ' ');

                //昵称
                $nickname = $PHPExcel->getActiveSheet()->getCell('C' . $i)->getValue();
                $nickname = trim($nickname, ' ');

                //密码（明文）
                $password = $PHPExcel->getActiveSheet()->getCell('D' . $i)->getValue();
                $password = trim($password, ' ');

                //微信公众号openid
                $wx_openid = $PHPExcel->getActiveSheet()->getCell('E' . $i)->getValue();
                $wx_openid = trim($wx_openid, ' ');

                //微信小程序openid
                $weapp_openid = $PHPExcel->getActiveSheet()->getCell('F' . $i)->getValue();
                $weapp_openid = trim($weapp_openid, ' ');

                //真实姓名
                $realname = $PHPExcel->getActiveSheet()->getCell('G' . $i)->getValue();
                $realname = trim($realname, ' ');

                //积分
                $point = $PHPExcel->getActiveSheet()->getCell('H' . $i)->getValue();
                $point = trim($point, ' ');

                //成长值
                $growth = $PHPExcel->getActiveSheet()->getCell('I' . $i)->getValue();
                $growth = trim($growth, ' ');

                //余额(可提现)
                $balance_money = $PHPExcel->getActiveSheet()->getCell('J' . $i)->getValue();
                $balance_money = trim($balance_money, ' ');

                //余额(不可提现)
                $balance = $PHPExcel->getActiveSheet()->getCell('K' . $i)->getValue();
                $balance = trim($balance, ' ');

                $not_data = [
                    'username' => $username,
                    'mobile' => $mobile,
                    'nickname' => $nickname,
                    'password' => $password,
                    'wx_openid' => $wx_openid,
                    'weapp_openid' => $weapp_openid,
                    'realname' => $realname,
                    'create_time' => time(),
                    'record_id' => $record
                ];

                if ($username == '' && $mobile == '') {
                    $not_data[ 'content' ] = '失败，用户名或手机号必须存在一个';
                    model('member_import_log')->add($not_data);
                    $error_num ++;
                    continue;
                }

                if ($nickname == '') {
                    $not_data[ 'content' ] = '失败，用户昵称不能为空';
                    model('member_import_log')->add($not_data);
                    $error_num ++;
                    continue;
                }

                if ($password == '') {
                    $not_data[ 'content' ] = '失败，用户密码不能为空';
                    model('member_import_log')->add($not_data);
                    $error_num ++;
                    continue;
                }
                if($username){
                    $username_res = model('member')->getInfo([ 'username' => $username ]);//根据用户名查找
                    if ($username_res) {
                        $not_data[ 'content' ] = '失败，已存在相同的用户名';
                        model('member_import_log')->add($not_data);
                        $error_num ++;
                        continue;
                    }
                }

                if($mobile){
                    $mobile_res = model('member')->getInfo([ 'mobile' => $mobile ]);//根据手机号查找
                    if ($mobile_res) {
                        $not_data[ 'content' ] = '失败，已存在相同的手机号';
                        model('member_import_log')->add($not_data);
                        $error_num ++;
                        continue;
                    }
                }

                if($wx_openid){
                    $wx_openid_res = model('member')->getInfo([ 'wx_openid' => $wx_openid ]);//根据微信公众号ID查找
                    if ($wx_openid_res) {
                        $not_data[ 'content' ] = '失败，已存在相同的公众号openid';
                        model('member_import_log')->add($not_data);
                        $error_num ++;
                        continue;
                    }
                }

                if($weapp_openid){
                    $weapp_openid_res = model('member')->getInfo([ 'weapp_openid' => $weapp_openid ]);//根据小程序ID查找
                    if ($weapp_openid_res) {
                        $not_data[ 'content' ] = '失败，已存在相同的小程序openid';
                        model('member_import_log')->add($not_data);
                        $error_num ++;
                        continue;
                    }
                }

                $member_level_info = model('member_level')->getInfo([ 'is_default' => 1 ]);
                if (empty($member_level_info)) {
                    $not_data[ 'content' ] = '失败，未查到该会员等级,并且未设置默认会员等级';
                    model('member_import_log')->add($not_data);
                    $error_num ++;
                    break;
                }

                $data = [
                    'username' => isset($username) ? $username : '',
                    'mobile' => isset($mobile) ? $mobile : '',
                    'nickname' => $nickname,
                    'password' => data_md5($password),
                    'member_level' => $member_level_info[ 'level_id' ],
                    'wx_openid' => isset($wx_openid) ? $wx_openid : '',
                    'weapp_openid' => isset($weapp_openid) ? $weapp_openid : '',
                    'realname' => isset($realname) ? $realname : '',
                    'member_level_name' => $member_level_info[ 'level_name' ],
                    'point' => isset($point) ? $point : 0,
                    'growth' => isset($growth) ? $growth : 0,
                    'balance_money' => isset($balance_money) ? $balance_money : 0.00,
                    'balance' => isset($balance) ? $balance : 0.00,
                    'reg_time' => time(),
                    'login_time' => time(),
                    'last_login_time' => time()
                ];

                model('member')->add($data);
                $not_data[ 'content' ] = '成功';
                model('member_import_log')->add($not_data);
                $success_num ++;
            }
            model('member')->commit();
            if($success_num + $error_num == ($allRow - 1)){
                $data_record = [
                    'member_num' => ($allRow - 1),
                    'success_num' => $success_num,
                    'error_num' => $error_num,
                    'create_time' => time(),
                    'status_name' => '导入成功'
                ];
                model('member_import_record')->update($data_record, ['id' => $record]);

            }
            return $this->success([
                'allRow' => $allRow,
                'num' => $type_num,
                'path' => $param[ 'path' ],
                'name' => $param['filename'],
                'success_num' => $success_num,
                'error_num' => $error_num,
                'record' => $record
            ]);
        } catch (\Exception $e) {
            model('member')->rollback();
            return $this->error('', $e->getMessage());
        }
    }

    /**
     *  获取会员导入记录列表
     */
    public function getMemberImportRecordList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*')
    {
        $list = model('member_import_record')->pageList($condition, $field, $order, $page, $page_size, '', '', '');
        return $this->success($list);
    }

    /**
     * 导入明细
     * @param array $condition
     * @param int $page
     * @param int $page_size
     * @param string $order
     * @param string $field
     * @return array
     */
    public function getMemberImportLogList($condition = [], $page = 1, $page_size = PAGE_LIST_ROWS, $order = '', $field = '*')
    {
        $list = model('member_import_log')->pageList($condition, $field, $order, $page, $page_size, '', '', '');
        return $this->success($list);
    }

    /**
     * 获取导入记录单条数据
     */
    public function getMemberImportRecordInfo($id){
        $info = model('member_import_record')->getInfo(['id' => $id]);
        return $this->success($info);
    }

}