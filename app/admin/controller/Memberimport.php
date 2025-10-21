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

namespace app\admin\controller;

use app\model\upload\Upload as UploadModel;
use app\model\member\MemberLevel as MemberLevelModel;
use app\model\member\MemberImport as MemberImportModel;
use phpoffice\phpexcel\Classes\PHPExcel;
use phpoffice\phpexcel\Classes\PHPExcel\Writer\Excel2007;

/**
 * 会员导入
 */
class Memberimport extends BaseAdmin
{

    /**
     * 会员导入
     */
    public function lists()
    {
        if (request()->isAjax()) {
            $member_model = new MemberImportModel();
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $condition = [];
            $result = $member_model->getMemberImportRecordList($condition, $page, $page_size,'create_time desc');
            return $result;
        }
        return $this->fetch('memberimport/lists');
    }

    /**
     * 下载会员导入模板
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    public function download()
        {
            // 实例化excel
            $phpExcel = new \PHPExcel();

            $phpExcel->getProperties()->setTitle("会员导入模板");
            $phpExcel->getProperties()->setSubject("会员导入模板");
            // 对单元格设置居中效果
            $phpExcel->getActiveSheet()->getStyle('A')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $phpExcel->getActiveSheet()->getStyle('B')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $phpExcel->getActiveSheet()->getStyle('C')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $phpExcel->getActiveSheet()->getStyle('D')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $phpExcel->getActiveSheet()->getStyle('E')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $phpExcel->getActiveSheet()->getStyle('F')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $phpExcel->getActiveSheet()->getStyle('G')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $phpExcel->getActiveSheet()->getStyle('H')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $phpExcel->getActiveSheet()->getStyle('I')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $phpExcel->getActiveSheet()->getStyle('J')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $phpExcel->getActiveSheet()->getStyle('K')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            $phpExcel->getActiveSheet()->getColumnDimension('B')->setWidth(13);
            $phpExcel->getActiveSheet()->getColumnDimension('D')->setWidth(11);
            $phpExcel->getActiveSheet()->getColumnDimension('E')->setWidth(19);
            $phpExcel->getActiveSheet()->getColumnDimension('F')->setWidth(19);
            $phpExcel->getActiveSheet()->getColumnDimension('G')->setWidth(10);
            $phpExcel->getActiveSheet()->getColumnDimension('J')->setWidth(15);
            $phpExcel->getActiveSheet()->getColumnDimension('K')->setWidth(16);
            //单独添加列名称
            $phpExcel->setActiveSheetIndex(0);
            $phpExcel->getActiveSheet()->setCellValue('A1', '用户名');//可以指定位置
            $phpExcel->getActiveSheet()->setCellValue('B1', '手机号');
            $phpExcel->getActiveSheet()->setCellValue('C1', '昵称');
            $phpExcel->getActiveSheet()->setCellValue('D1', '密码(明文)');
            $phpExcel->getActiveSheet()->setCellValue('E1', '微信公众号openid');
            $phpExcel->getActiveSheet()->setCellValue('F1', '微信小程序openid');
            $phpExcel->getActiveSheet()->setCellValue('G1', '真实姓名');
            $phpExcel->getActiveSheet()->setCellValue('H1', '积分');
            $phpExcel->getActiveSheet()->setCellValue('I1', '成长值');
            $phpExcel->getActiveSheet()->setCellValue('J1', '余额(可提现)');
            $phpExcel->getActiveSheet()->setCellValue('K1', '余额(不可提现)');

            $phpExcel->getActiveSheet()->setTitle('会员导入');
            // 设置第一个sheet为工作的sheet
            $phpExcel->setActiveSheetIndex(0);
            // 保存Excel 2007格式文件，保存路径为当前路径，名字为export.xlsx
            $objWriter = \PHPExcel_IOFactory::createWriter($phpExcel, 'Excel2007');
            $file = date('Y年m月d日-会员导入模板', time()) . '.xlsx';
            $objWriter->save($file);

            header("Content-type:application/octet-stream");

            $filename = basename($file);
            header("Content-Disposition:attachment;filename = " . $filename);
            header("Accept-ranges:bytes");
            header("Accept-length:" . filesize($file));
            readfile($file);
            unlink($file);
            exit;
        }

    /**
     * 上传文件
     */
    public function file()
    {
        $upload_model = new UploadModel($this->site_id);

        $param = array (
            "name" => "file",
            'extend_type' => [ 'xlsx','xls' ]
        );

        $result = $upload_model->setPath("common/member/member_import/" . date("Ymd") . '/')->file($param);
        return $result;
    }

    /**
     * 导入
     */
    public function import()
    {
        if (request()->isAjax()) {
            $filename = input('filename', '');
            $path = input('path', '');
            $index = input('index', '');
            $success_num = input('success_num', 0);
            $error_num = input('error_num', 0);
            $record = input('record', 0);
            $member_model = new MemberImportModel();

            $params = [
                'filename' => $filename,
                'path' => $path,
                'index' => $index,
                'success_num' => $success_num,
                "error_num" => $error_num,
                "record" => $record
            ];
            $res = $member_model->importMember($params);
            return $res;
        }
    }


    /*
     *  会员导入记录明细
     */
    public function detail()
    {
        if(request()->isAjax()){
            $member_model = new MemberImportModel();
            $id = input("id", 0);
            $page = input('page', 1);
            $page_size = input('page_size', PAGE_LIST_ROWS);
            $condition['record_id'] = $id;
            $list = $member_model->getMemberImportLogList($condition, $page, $page_size);

            return $list;
        }

        $id = request()->get("id", 0);
        $member_model = new MemberImportModel();
        $info = $member_model->getMemberImportRecordInfo($id);
        $this->assign('info', $info);
        $this->assign('id', $id);
        return $this->fetch('memberimport/import_log');
    }

}