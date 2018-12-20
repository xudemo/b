<?php
namespace app\packet\controller;

use think\Controller;
use think\Db;

//抢购
class Apply extends Controller
{
    public function index($name, $phone, $store,$state)
    {
        $id = cookie('hbMember')['id'];
        $hid = cookie('hbMember')['hid'];
		//$sid = cookie('hbMember')['sid'];
	//	$sid = session('sid');
		$activity = Db::name('hb_activity')
            ->where(['id' => $hid])
            ->field('finish_time')
            ->find();
        $time = strtotime($activity['finish_time']) - time();
        if ($time < 0) {
            return json(['data' => [], 'state' => 0, 'msg' => '活动已结束']);
        }
//		if(!empty($sid) && is_int($sid)){
//          $m = Db::name('hb_member')->where(['id'=>$sid])->field('masterid')->find();
//          $data['masterid']  = $m['masterid'];
//			$data['sid'] = $sid;
//      }
        $data['name'] = $name;
        $data['phone'] = $phone;
        $data['shop_site'] = $store;
		$data['state'] = $state;
		//$data['sid'] = $sid;
        $result = Db::name('hb_member')
            ->where(['id'=>$id,'hid'=>$hid])
            ->update($data);
        $url = "/packet/example/jsapi?id={$id}&hid={$hid}";
		return json(['data' => $url, 'state' => 1, 'msg' => '']);
        //return $url;
        //header("Location: $url");
    }
}