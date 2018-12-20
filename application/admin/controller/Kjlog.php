<?php
namespace app\admin\controller;

use think\Controller;
use app\admin\controller\Checklogin;
use think\Db;
use app\admin\model\Member;

class Kjlog extends Checklogin
{
    //活动列表
    public function activity()
    {
        $oppid = $this->getAppId(); //原始id
        $activity = Db::name('kj_activity')
        ->where(['appid' => $oppid])
		->order('id desc')
        ->paginate(20);
        $page = $activity->render();
        $this->assign('activity', $activity);
        $this->assign('page', $page);
        return view();
    }

    //用户列表
    public function index($id = null, $appid = null)
    {
        $activity = Db::name('kj_activity')->where(['id' => $id])->field('name')->find();
        $this->assign('activity', $activity);
        $field = "a.id,a.hid,a.openid,a.nickname,a.state,a.pay,a.name,a.phone,a.savedate,
            (select count(*) from zn_kj_member where tid=a.id) as t_num,
            (select count(*) from zn_kj_member where tid=a.id and pay=1) as t_pay,
            (select count(*) from zn_kj_kjlog where uid=a.id) as kj_num";
        $member = Db::name('kj_member')
            ->alias('a')
            ->where(['hid' => $id, 'h_appid' => $appid])
			->order('id desc')
            ->field($field)
            ->paginate(20, false, ['query' => ['id' => $id, 'appid' => $appid]]);
        $page = $member->render();
        $this->assign('member', $member);
        $this->assign('page', $page);
        $this->assign('id', $id);
        $this->assign('appid', $appid);
        return view();
    }

    //报名列表
    public function apply($id = null, $appid = null, $pay = null, $search = null)
    {
        $activity = Db::name('kj_activity')->where(['id' => $id])->field('name')->find();
        $this->assign('activity', $activity);
        $map['hid'] = $id;
        $map['h_appid'] = $appid;
        if ($pay != '') {
            $map['pay'] = $pay;
        }
        $field = "a.id,a.hid,a.openid,a.nickname,a.state,a.pay,a.name,a.phone,a.savedate,a.money,a.node,
            (select nickname from zn_kj_member where id=a.masterid) as master,
            (select count(*) from zn_kj_member where tid=a.id) as t_num,
            (select count(*) from zn_kj_member where tid=a.id and pay=1) as t_pay,
            (select count(*) from zn_kj_member where sid=a.id) as tg_num";
        if($search == ''){
            $member = Db::name('kj_member')
                ->alias('a')
                ->where($map)
                ->where('state', 'NEQ', 0)
                ->where('state', 'NEQ', 3)
                ->order('id desc')
                ->field($field)
                ->paginate(20, false, ['query' => ['id' => $id, 'appid' => $appid,'pay'=>$pay,'search'=>$search]]);
        }else{
            $member = Db::name('kj_member')
                ->alias('a')
                ->where($map)
                ->where('state', 'NEQ', 0)
                ->where('state', 'NEQ', 3)
                ->where('id|nickname|name|phone', 'like', ['%' . $search . '%'])
                ->order('id desc')
                ->field($field)
                ->paginate(20, false, ['query' => ['id' => $id, 'appid' => $appid,'pay'=>$pay,'search'=>$search]]);
        }
        $page = $member->render();
        $this->assign('member', $member);
        $this->assign('page', $page);
        $this->assign('id', $id);
        $this->assign('appid', $appid);
        $this->assign('search', $search);
        $this->assign('pay', $pay);
        return view();
    }

    //员工列表
    public function staff($id = null, $appid = null)
    {
        $activity = Db::name('kj_activity')->where(['id' => $id])->field('name')->find();
        $this->assign('activity', $activity);
        $member = Db::name('kj_member')
            ->where(['hid' => $id, 'h_appid' => $appid, 'state' => 3])
            ->select();
        for ($a = 0; $a < count($member); $a++) {
            //支付人数
            $member[$a]['paycount'] = Db::name('kj_member')->where(['hid' => $id, 'h_appid' => $appid, 'pay' => 1, 'masterid' => $member[$a]['id']])->count();
            //参团人数
            $member[$a]['ctcount'] = Db::name('kj_member')
                ->where(['hid' => $id, 'h_appid' => $appid, 'masterid' => $member[$a]['id']])
                ->where('state', 'neq', 0)
                ->count();
            //浏览量
            $bottom = Db::name('kj_member')
                ->where(['hid' => $id, 'h_appid' => $appid, 'masterid' => $member[$a]['id']])
                ->field('id')
                ->select();
            $member[$a]['lcount'] = 0;
            for($b=0;$b<count($bottom);$b++){
                $member[$a]['lcount'] += Db::name('kj_member')
                    ->where(['hid' => $id, 'h_appid' => $appid, 'sid' => $bottom[$b]['id']])
                    ->count();
            }
        }
        $this->assign('id', $id);
        $this->assign('appid', $appid);
        $this->assign('member', $member);
        return view();
    }

    //支付列表
    public function pay($id = null, $appid = null)
    {
//  	$activity = Db::name('kj_activity')->where(['id' => $id])->field('name')->find();
//      $this->assign('activity', $activity);
//      $field = "a.transaction_id,a.time,a.money,b.name,b.phone";
//      $member = Db::name('kj_orders')
//          ->alias('a')
//          ->join('zn_kj_member b','b.id=a.uid')
//          ->where(['b.hid' => $id, 'b.h_appid' => $appid, 'b.pay' => 1])
//          ->field($field)
//          ->paginate(20, false, ['query' => ['id' => $id, 'appid' => $appid]]);
//      $member = json_decode(json_encode($member), true)['data'];
//			exit;
//		
        $activity = Db::name('kj_activity')->where(['id' => $id])->field('name')->find();
        $this->assign('activity', $activity);
        $member = Db::name('kj_member')
            ->where(['hid' => $id, 'h_appid' => $appid, 'pay' => 1])
            ->paginate(20, false, ['query' => ['id' => $id, 'appid' => $appid]]);
        if ($member) {
            $mb = json_decode(json_encode($member), true)['data'];
            for ($m = 0; $m < count($mb); $m++) {
                //支付信息
                $mb[$m]['order'] = Db::name('kj_orders')->where(['hid' => $id, 'uid' => $member[$m]['id']])->find();
                //指向员工
                $mb[$m]['master'] = Db::name('kj_member')->where(['id' => $mb[$m]['masterid']])->find();
                $mb[$m]['tnum'] = Db::name('kj_member')->where(['tid' => $member[$m]['id'], 'hid' => $id])->count();
            }
            $page = $member->render();
            $this->assign('member', $mb);
            $this->assign('page', $page);
        } else {
            $this->error('', null, '', 0);
        }
        $this->assign('id', $id);
        $this->assign('appid', $appid);
        return view();
    }

    //凭证查询
    public function proof($id = null, $appid = null)
    {
        if ($this->request->isPost()) {
            $data['phone']= input('phone');
            $data['hid'] = input('id');
            $yzm = input('yzm');
            if(!empty($yzm)){
                $data['yzm'] = $yzm;
            }
            $member = Db::name('kj_member')->where($data)->select();
            for($m=0;$m<count($member);$m++){
                $member[$m]['order'] = Db::name('kj_orders')->where(['hid' => $id, 'uid' => $member[$m]['id']])->find();
            }
            $this->assign('member',$member);
        }else{
            $this->assign('member',false);
        }
        $activity = Db::name('kj_activity')->where(['id' => $id])->field('name')->find();
        $this->assign('activity', $activity);
        $this->assign('id', $id);
        $this->assign('appid', $appid);
        return view();
    }
  
  //活动地址
    public function site($id = null, $appid = null)
    {
        $activity = Db::name('kj_activity')->where(['id' => $id])->field('name')->find();
        $url = URL . "/kjlog/index/welcome/hid/{$id}";
        $url = urlencode($url);
		$this->assign('u',URL);
        $this->assign('url',$url);
        $this->assign('activity', $activity);
        $this->assign('id', $id);
        $this->assign('appid', $appid);
        return view();
    }

    //折线图
    public function examples($id = null, $appid = null)
    {
        $activity = Db::name('kj_activity')->where(['id' => $id])->field('name,begin_time,finish_time')->find();
        $nowtime = strtotime(date('Y-m-d', time())); //今天时间戳
        $day = date('Y-m-d', strtotime("+0 day")); //明天

        $begin_time = $activity['begin_time']; //开始时间
        $finish_time = $activity['finish_time'];//结束时间
        $number = strtotime($finish_time, time()) - strtotime($begin_time, time());
        $number = intval($number / 86400);  //时间天数
        $member = [];
        for ($i = 0; $i <= $number; $i++) {
            $datTime = strtotime(date('Y-m-d', strtotime($begin_time, time()))) + $i * 86400;
            $dayTime = strtotime(date('Y-m-d', strtotime($begin_time, time()))) + ($i + 1) * 86400;
            $member[$i]['date'] = date('Y-m-d', $datTime);
            $member[$i]['liu_num'] = Db::name('kj_member')
                ->where(['hid' => $id, 'h_appid' => $appid])
                ->where('savedate', 'between', [$datTime, $dayTime])
                ->count();
            $member[$i]['bao_num'] = Db::name('kj_member')
                ->where(['hid' => $id, 'h_appid' => $appid])
                ->where('state', 'NEQ', 0)
                ->where('savedate', 'between', [$datTime, $dayTime])
                ->count();
            $member[$i]['pay_num'] = Db::name('kj_member')
                ->where(['hid' => $id, 'h_appid' => $appid, 'pay' => 1])
                ->where('savedate', 'between', [$datTime, $dayTime])
                ->count();
        }
        $data['date'] = '';
        $data['liu_num'] = '';
        $data['bao_num'] = '';
        $data['pay_num'] = '';
        for ($a = 0; $a < count($member); $a++) {
            $data['date'] .= "'" . $member[$a]['date'] . "'" . ',';
            $data['liu_num'] .= "'" . $member[$a]['liu_num'] . "'" . ',';
            $data['bao_num'] .= "'" . $member[$a]['bao_num'] . "'" . ',';
            $data['pay_num'] .= "'" . $member[$a]['pay_num'] . "'" . ',';
        }
        $data['date'] = substr($data['date'],0,strlen($data['date'])-1);
        $data['liu_num'] = substr($data['liu_num'],0,strlen($data['liu_num'])-1);
        $data['bao_num'] = substr($data['bao_num'],0,strlen($data['bao_num'])-1);
        $data['pay_num'] = substr($data['pay_num'],0,strlen($data['pay_num'])-1);

        $member['sumup'] = Db::name('kj_member')
            ->where(['hid' => $id, 'h_appid' => $appid])
            ->count();
        $member['bao_sumup'] = Db::name('kj_member')
            ->where(['hid' => $id, 'h_appid' => $appid])
            ->where('state', 'NEQ', 0)
			->where('state', 'NEQ', 3)
            ->count();
        $newly = Db::query("select count(*) as num from zn_kj_member where hid={$id} and h_appid='{$appid}' and money=low_price and state!=3");
        $member['old'] = $newly[0]['num'];
        $member['newly'] = Db::name('kj_member')
            ->where(['hid' => $id, 'h_appid' => $appid,'pay'=>1])
            ->where('state','EQ',2)
            ->count();
        $member['pay_sumup'] = Db::name('kj_member')
            ->where(['hid' => $id, 'h_appid' => $appid, 'pay' => 1])
            ->count();
        $member['order'] = Db::name('kj_orders')->where(['hid'=>$id])->sum('money');

        $this->assign('data', $data);
        $this->assign('member',$member);
        $this->assign('activity', $activity);
        $this->assign('id', $id);
        $this->assign('appid', $appid);
        return view();
    }

    //使用
    public function yes($id,$openid)
    {
        $result = Db::name('kj_member')->where(['id'=>$id,'openid'=>$openid])->update(['use'=>1]);
        if($result>0){
            return json(['state' => 1, 'message' => '使用成功']);
        }else{
            return json(['state' => 0, 'message' => '使用失败']);
        }
    }

    //设置为员工
    public function setMember($id, $openid, $hid)
    {
        $activity = Db::name('kj_activity')->where(['id' => $hid])->field('finish_time,money,old')->find();
        $time = strtotime($activity['finish_time']) - time();
        if ($time < 0) {
            return json(['state' => 0, 'message' => '活动已结束']);
        }
        $member = Db::name('kj_member')->where(['id' => $id, 'openid' => $openid, 'hid' => $hid])->field('state')->find();
        if($member['state'] == 0){
            $data['dmoney'] = $activity['money'];
            $data['money'] = $activity['money'];
            $data['low_price'] = $activity['old'];
        }
        $data['state'] = 3;
        $data['masterid'] = $id;
		$data['tid'] = $id;
        $result = Db::name('kj_member')->where(['id' => $id, 'openid' => $openid, 'hid' => $hid])->update($data);
        if ($result > 0) {
            return json(['state' => 1, 'message' => '修改成功']);
        } else {
            return json(['state' => 0, 'message' => '修改失败']);
        }
    }

     //查询团成员
    public function member($id=null,$hid=null)
    {
        $member = Db::name('kj_member')->where(['tid'=>$id,'hid'=>$hid])->select();
        for($m=0;$m<count($member);$m++){
            $member[$m]['order'] = Db::name('kj_orders')->where(['uid'=>$member[$m]['id'],'hid'=>$hid])->find();
        }
        $this->assign('member',$member);
        return view();
    }
	
	//设置备注
    public function nodes($id,$data)
    {
        $m = new Member();
        $result = $m->setNode($id,'kj',$data);
        if($result > 0){
            return json(['state'=>1,'message'=>'修改成功']);
        }else{
            return json(['state'=>0,'message'=>'修改失败']);
        }
    }
    
    //查询原始id
    public function getAppId()
    {
        $userId = session('nowUser')['id'];
        $user_name = Db::name('wx_users')->where(['user_id' => $userId])->field('user_name')->find();
        return $user_name;
    }
}