<?php
namespace app\packet\controller;

use think\Controller;
use think\Db;
use wechat\WeChat;
use app\packet\model\Activity;
use app\kjlog\model\User;
use app\kjlog\model\Exception;
use app\packet\model\HongBao;

class Index extends Controller
{
    //红包
    public function welcome($hid = null, $sid = null, $from = null, $isappinstalled = null)
    {
    	file_put_contents('s1.txt','SID：'.$sid.'  ',FILE_APPEND);
        header("Content-type:text/html;charset=utf-8");
        if (!empty($from)) {
            $this->redirect("/packet/index/welcome?hid={$hid}&sid={$sid}");
        }
        $wechat = new WeChat();
//查询活动信息
        $activity = Activity::get_activity($hid);
        $this->assign('time', $activity['finish_time']);
        $this->assign('activity', $activity);

//查询该公众号的appId appSecret
        $user = new User();
        $users = $user->get_appid($activity['appid']);
//判断是否授权登陆

        if ($wechat->hblogin($hid) == false) {
           $this->login($users['appId'], $hid, $sid);
            exit;
        }
        $member = cookie('hbMember');
		if($member['id'] != $sid){
            header("Location:/packet/index/welcome?hid={$hid}&sid={$member['id']}");
			exit;
        }
		file_put_contents('s2.txt','ID:'.$member['id'].'的SID：'.$sid.'   ',FILE_APPEND);
		//session('sid',$sid);
//修改分享信息
        $rootPath = 'http://' . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
        $arr = $wechat->jsapiget($users['appId'], $users['appSecret'], $rootPath);
        if (isset($arr['errmsg']) && isset($arr['errcode'])) {
            Exception::exception('修改分享信息失败，' . '错误代码：' . $arr['errcode'] . '。错误消息：' . $arr['errmsg']);
        }
        $this->assign('url',URL);
        $this->assign('member',$member);
        $this->assign('hid',$hid);
        $this->assign('arr',$arr);
        return view();
    }

    //积分兑换
    public function integral()
    {
        $hid = cookie('hbMember')['hid'];
        $activity = Db::name('hb_activity')->where(['id'=>$hid])->field('integral_img')->find();
        $this->assign('img',$activity['integral_img']);
        return view();
    }

//投诉
    public function ts()
    {
        if($this->request->isPost()){
            $data['reason'] = input('reason'); //投诉原因
            $data['describe'] = input('describe'); //投诉描述
            $data['contact'] = input('contact'); //投诉描述
            $data['hid'] = cookie('hbMember')['hid'];
            $result = Db::name('hb_ts')->insert($data);
            if ($result > 0){
                return json(['state'=>1,'message'=>'投诉成功']);
            } else {
                return json(['state'=>0,'message'=>'投诉失败']);
            }
        }else{
            return view();
        }
    }
	
	//员工
    public function member()
    {
        $id = cookie('hbMember')['id'];
        $hid = cookie('hbMember')['hid'];
        $member = Db::name('hb_member')
        ->where(['hid'=>$hid,'masterid'=>$id])
		->where('phone', 'NEQ', null)
        ->select();
        $this->assign('member',$member);
        return view();
    }

    //登陆
    public function login($appid, $hid, $sid)
    {
        $wechat = new WeChat();
       $wechat->get_hb_login($appid, $hid, $sid);
    }
	
	 function a()
    {
        $url = "https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack";
        $hb = new HongBao();
        $restlu = $hb->http($url,'','');
        dump($restlu);
    }
}