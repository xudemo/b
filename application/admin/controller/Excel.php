<?php
namespace app\admin\controller;

use think\Controller;
use app\admin\controller\Checklogin;
use think\Db;
use think\Loader;

class Excel extends Checklogin
{
    //导出excel
    public function index($id = null, $appid = null)
    {
        //活动信息
        $activity = Db::name('kj_activity')->where(['id' => $id])->field('name')->find();
        //报名的用户信息
        $member = Db::name('kj_member')
            ->where(['hid' => $id, 'h_appid' => $appid])
            ->where('state', 'NEQ', 0)
            ->order('id desc')
            ->select();
        for ($m = 0; $m < count($member); $m++) {
            $member[$m]['t_num'] = Db::name('kj_member')->where(['tid' => $member[$m]['id']])->count(); //团人数
            $member[$m]['master'] = null;
            $master = Db::name('kj_member')->where(['id' => $member[$m]['masterid']])->field('nickname as master')->find(); //员工
            if ($master) {
                $member[$m]['master'] = $master['master'];
            }
            $member[$m]['t_pay'] = Db::name('kj_member')->where(['tid' => $member[$m]['id'], 'pay' => 1])->count(); //团支付数
            $member[$m]['tg_num'] = Db::name('kj_member')->where(['sid' => $member[$m]['id']])->count(); //推广人数
        }

        require '../extend/excel/PHPExcel.php';
        require '../extend/excel/PHPExcel/Writer/Excel2007.php';
        $objPHPExcel = new \PHPExcel();
        //设置属性
        $objPHPExcel->getProperties()
            ->setCreator("WOLF")
            ->setLastModifiedBy("WOLF")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.");
        //3.填充表格
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0); //填充表头
        $objActSheet->setCellValue('A1', '活动名称');
        $objActSheet->setCellValue('B1', '微信名');
        $objActSheet->setCellValue('C1', '指向员工');
        $objActSheet->setCellValue('D1', '团人数');
        $objActSheet->setCellValue('E1', '团支付数');
        $objActSheet->setCellValue('F1', '推广人数');
        $objActSheet->setCellValue('G1', '新生老生');
        $objActSheet->setCellValue('H1', '当前价');
        $objActSheet->setCellValue('I1', '是否支付');
        $objActSheet->setCellValue('J1', '信息');
        $objActSheet->setCellValue('K1', '注册时间');
		$objActSheet->setCellValue('L1', '报名校区');
//填充内容
        for ($a = 0; $a < count($member); $a++) {
            $key = $a + 2;//行数
            $pay = '';
            if($member[$a]['pay'] == 0){
                $pay = '未支付';
            } else if($member[$a]['pay'] == 1){
                $pay = '已支付';
            }
            $state = '';
            if($member[$a]['state'] == 0){
                $state = '普通砍价用户';
            } else if($member[$a]['state'] == 1){
                $state = '新生';
            } else if($member[$a]['state'] == 2) {
                $state = '老生';
            }else if($member[$a]['state'] == 3) {
                $state = '员工';
            }
            $addTime = date('Y-m-d H:i:s', $member[$a]['savedate']);
            $objActSheet->setCellValue('A' . $key, $activity['name']);
            $objActSheet->setCellValue('B' . $key, $member[$a]['nickname']);
            $objActSheet->setCellValue('C' . $key, $member[$a]['master']);
            $objActSheet->setCellValue('D' . $key, $member[$a]['t_num']);
            $objActSheet->setCellValue('E' . $key, $member[$a]['t_pay']);
            $objActSheet->setCellValue('F' . $key, $member[$a]['tg_num']);
            $objActSheet->setCellValue('G' . $key, $state);
            $objActSheet->setCellValue('H' . $key, '￥'.$member[$a]['money']);
            $objActSheet->setCellValue('I' . $key, $pay);
            $objActSheet->setCellValue('J' . $key, $member[$a]['name'] . '-' . $member[$a]['phone']);
            $objActSheet->setCellValue('K' . $key, $addTime);  
			$objActSheet->setCellValue('L' . $key, $member[$a]['shop_site']);     
		}
//4.输出
        $objPHPExcel->getActiveSheet()->setTitle('报名表');
        $objPHPExcel->setActiveSheetIndex(0);
        $filename = '报名表.xls';
        ob_end_clean();//清除缓冲区,避免乱码
        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header('Content-Disposition: attachment;filename=' . $filename);
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }

    //导出红包excel
    public function packet($id = null, $appid = null,$payexcel=null)
    {
        $activity = Db::name('hb_activity')->where(['id' => $id])->field('name,hb_name')->find();
        $this->assign('activity', $activity);

        $map['hid'] = $id;
        $map['h_appid'] = $appid;
		if($payexcel != ''){
            $map['pay'] = $payexcel;
        }
        $field = "a.name,a.nickname,a.pay,a.phone,a.new_integral,a.savedate,a.shop_site,a.state,a.yzm,
        (select count(*) from zn_hb_member where sid=a.id) as t_num,
        (select count(*) from zn_hb_member where sid=a.id and pay=1) as pay_num,
        (select nickname from zn_hb_member where id=a.masterid) as master";
        $member = Db::name('hb_member')
            ->alias('a')
            ->where($map)
            ->where('name','NEQ',null)
			->where('state','NEQ',2)
            ->order('id desc')
            ->field($field)
            ->select();
        require '../extend/excel/PHPExcel.php';
        require '../extend/excel/PHPExcel/Writer/Excel2007.php';
        $objPHPExcel = new \PHPExcel();
        //设置属性
        $objPHPExcel->getProperties()
            ->setCreator("WOLF")
            ->setLastModifiedBy("WOLF")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.");
        //3.填充表格
        $objActSheet = $objPHPExcel->setActiveSheetIndex(0); //填充表头
        $objActSheet->setCellValue('A1', '活动名称');
        $objActSheet->setCellValue('B1', '微信名');
        $objActSheet->setCellValue('C1', '推广人数');
        $objActSheet->setCellValue('D1', '推广支付数');
        $objActSheet->setCellValue('E1', '是否支付');
        $objActSheet->setCellValue('F1', '信息');
        $objActSheet->setCellValue('G1', '红包金额');
        $objActSheet->setCellValue('H1', '注册时间');
		$objActSheet->setCellValue('I1', '活动门店');
		$objActSheet->setCellValue('J1', '指向员工');
		$objActSheet->setCellValue('K1', '新生老生');
		if($payexcel == '1'){
            $objActSheet->setCellValue('L1', '付款凭证验证码');
        }

        //填充内容
        for ($a = 0; $a < count($member); $a++) {
            $key = $a + 2;//行数
            $pay = '';
			$state = '';
            if ($member[$a]['pay'] == 0) {
                $pay = '未支付';
            } else if ($member[$a]['pay'] == 1) {
                $pay = '已支付';
            }
            if ($member[$a]['state'] == 3) {
                $state = '新生';
            } else if ($member[$a]['state'] == 4) {
                $state = '老生';
            }

            $addTime = date('Y-m-d H:i:s', $member[$a]['savedate']);
            $objActSheet->setCellValue('A' . $key, $activity['name']);
            $objActSheet->setCellValue('B' . $key, $member[$a]['nickname']);
            $objActSheet->setCellValue('C' . $key, $member[$a]['t_num']);
            $objActSheet->setCellValue('D' . $key, $member[$a]['pay_num']);
            $objActSheet->setCellValue('E' . $key, $pay);
            $objActSheet->setCellValue('F' . $key, $member[$a]['name'] . '-' . $member[$a]['phone']);
            $objActSheet->setCellValue('G' . $key, $member[$a]['new_integral']);
            $objActSheet->setCellValue('H' . $key, $addTime);
			$objActSheet->setCellValue('I' . $key, $member[$a]['shop_site']);
			$objActSheet->setCellValue('J' . $key, $member[$a]['master']);
			$objActSheet->setCellValue('K' . $key, $state);
			if($payexcel == '1'){
                $objActSheet->setCellValue('L' . $key, $member[$a]['yzm']);
            }
        }
        //4.输出
        $objPHPExcel->getActiveSheet()->setTitle('红包参与用户表');
        $objPHPExcel->setActiveSheetIndex(0);
        if($payexcel == ''){
            $filename = "{$activity['hb_name']}参与用户表.xls";
        }
        if($payexcel == '0'){
            $filename = "{$activity['hb_name']}未支付用户表.xls";
        }
        if($payexcel == '1'){
            $filename = "{$activity['hb_name']}已支付用户表.xls";
        }
        ob_end_clean();//清除缓冲区,避免乱码
        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header('Content-Disposition: attachment;filename=' . $filename);
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }
}