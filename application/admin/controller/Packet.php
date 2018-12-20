<?php
namespace app\admin\controller;

use think\Controller;
use app\admin\controller\Checklogin;
use think\Db;
use app\admin\model\Member;
use app\packet\model\HongBao;
//红包
class Packet extends Checklogin
{
    //活动表
    public function activity()
    {
    	$oppid = $this->getAppId();
        $field = "a.id,a.name,a.intro,a.begin_time,a.finish_time,a.appid,
        (select count(*) from zn_hb_member where hid=a.id and pay=1 and state!=2) as payNum";
        $activity = Db::name('hb_activity')
                ->alias('a')
                ->where(['appid' => $oppid])
                ->order('id desc')
                ->field($field)
                ->paginate(20);
        $page = $activity->render();
        $this->assign('activity', $activity);
        $this->assign('page', $page);
        return view();
    }

    //用户列表
    public function index($id = null, $appid = null,$search = null,$pay=null)
    {
       $activity = Db::name('hb_activity')->where(['id' => $id])->field('name')->find();
        //t_num推广人数 pay_num推广支付数
        $field = "*,
        (select count(*) from zn_hb_member where sid=a.id) as t_num,
        (select count(*) from zn_hb_member where sid=a.id and pay=1) as pay_num,
        (select nickname from zn_hb_member where id=a.masterid) as master";
        $where['hid'] = $id;
        $where['h_appid'] = $appid;
        if($pay != ''){
            $where['pay'] = $pay;
        }
        if($search == ''){
            $member = Db::name('hb_member')
                ->alias('a')
                ->where($where)
                ->where('state', 'NEQ', 2)
				->where('state', 'NEQ', 5)
                ->order('id desc')
                ->field($field)
                ->paginate(20, false, ['query' => ['id' => $id, 'appid' => $appid,'search'=>$search,'pay'=>$pay]]);
        }else{
            $member = Db::name('hb_member')
                ->alias('a')
                ->where($where)
                ->where('state', 'NEQ', 2)
				->where('state', 'NEQ', 5)
				->where('id|nickname|name|phone', 'like', ['%' . $search . '%'])
                ->order('id desc')
                ->field($field)
                ->paginate(20, false, ['query' => ['id' => $id, 'appid' => $appid,'search'=>$search,'pay'=>$pay]]);
        }
        $page = $member->render();
        $this->assign('activity', $activity);
        $this->assign('member', $member);
        $this->assign('page', $page);
        $this->assign('id', $id);
        $this->assign('appid', $appid);
        $this->assign('search', $search);
        $this->assign('pay', $pay);
        return view();
    }

    //参与用户
    public function apply($id = null, $appid = null, $search = null,$pay=null)
    {
        $activity = Db::name('hb_activity')->where(['id' => $id])->field('name')->find();
        $this->assign('activity', $activity);
        $map['hid'] = $id;
        $map['h_appid'] = $appid;
		if ($pay != '') {
            $map['pay'] = $pay;
        }
        //$map['pay'] = 1;
        //t_num推广人数 pay_num推广支付数
        $field = "*,
        (select count(*) from zn_hb_member where sid=a.id) as t_num,
        (select count(*) from zn_hb_member where sid=a.id and pay=1) as pay_num,
        (select nickname from zn_hb_member where id=a.masterid) as master";
        $member = [];
        if ($search == '') {
            $member = Db::name('hb_member')
                ->alias('a')
                ->where($map)
				->where('name','NEQ',null)
				->where('state','NEQ',2)
				->order('id desc')
                ->field($field)
                ->paginate(20, false, ['query' => ['id' => $id, 'appid' => $appid,'pay'=>$pay,'search'=>$search]]);
        }else{
            $member = Db::name('hb_member')
                ->alias('a')
                ->where($map)
				->where('name','NEQ',null)
				->where('state','NEQ',2)
                ->where('id|nickname|name|phone', 'like', ['%' . $search . '%'])
				->order('id desc')
                ->field($field)
                ->paginate(20, false, ['query' => ['id' => $id, 'appid' => $appid,'pay'=>$pay,'search'=>$search]]);
        }
        $page = $member->render();
        $this->assign('page', $page);
        $this->assign('member', $member);
        $this->assign('id', $id);
        $this->assign('appid', $appid);
        $this->assign('search', $search);
        $this->assign('pay', $pay);
        return view();
    }
    
    //员工列表
    public function staff($id = null, $appid = null)
    {
        //$list = Db::query("select id,nickname,(select count(*) from zn_hb_member as a where pay=1 and masterid=a.id) as payNum,(select count(*) from zn_hb_member sid=(select sid from zn_hb_member masterid=a.id)) as liuNum from zn_hb_member where state=2");
		$activity = Db::name('hb_activity')->where(['id' => $id])->field('name')->find();
        $this->assign('activity', $activity);
//		$list = Db::query("select id,nickname from zn_hb_member where state=2 and hid={$id}");
//		//dump($list);
//      for ($i = 0; $i < count($list); $i++) {
//          $list[$i]['pay_num'] = Db::name('hb_member')
//              ->where(['hid'=>$id,'pay'=>1,'masterid' => $list[$i]['id']])
//              ->count();
//          $bottom = Db::name('hb_member')
//              ->where(['masterid' => $list[$i]['id']])
//              ->field('id,masterid')
//              ->select();
////				Db::name('hb_member')->where(['sid'=>$list[$i]['id']])
////              ->update(['masterid'=>$list[$i]['id']]);
//          $list[$i]['liuNum'] = 0;
//          for ($b = 0; $b < count($bottom); $b++) {
//              $list[$i]['liuNum'] += Db::name('hb_member')
//                  ->where(['sid'=>$bottom[$b]['id']])
//                  ->count();
////                  Db::name('hb_member')
////                  ->where(['sid' => $bottom[$b]['id'],'hid'=>$id])
////                  ->update(['masterid'=>$bottom[$b]['masterid']]);
//          }
//      }
        $field = "a.nickname,a.id,a.state,a.hid,
        (select count(*) from zn_hb_member where masterid=a.id and id!=a.id) as liuNum,
        (select count(*) from zn_hb_member where masterid=a.id and pay=1 and id!=a.id) as pay_num,
        (select sum(integral) from zn_hb_integral where sid=a.id) as money,
        (select count(*) from zn_hb_integral where sid=a.id) as hb_num";
        $list = Db::name('hb_member')
            ->alias('a')
             ->where(['state'=>2])
            ->where(['hid'=>$id])
            ->field($field)
            ->select();
		//	dump($list);
      	$this->assign('id', $id);
        $this->assign('appid', $appid);
		$this->assign('member',$list);
        return view();
    }

    //支付列表
    public function pay($id = null, $appid = null)
    {
        $activity = Db::name('hb_activity')->where(['id' => $id])->field('name')->find();
        $this->assign('activity', $activity);
        $field = "a.transaction_id,a.time,a.money,b.new_integral,b.name,b.phone,b.masterid,b.sid,
        (select nickname from zn_hb_member where id=b.masterid) as master,
        (select nickname from zn_hb_member where id=b.sid) as sName,
        (select integral from zn_hb_integral where transactionId=a.transaction_id) as integral";
        $member = Db::name('hb_orders')
            ->alias('a')
            ->join('zn_hb_member b', 'b.id=a.uid')
            ->where(['a.hid' => $id])
            ->order('a.id desc')
            ->field($field)
            ->paginate(20, false, ['query' => ['id' => $id, 'appid' => $appid]]);
			
		//	$mb = json_decode(json_encode($member), true)['data'];
      //  dump($mb);
			
        $page = $member->render();
        $this->assign('member', $member);
        $this->assign('page', $page);
        $this->assign('id', $id);
        $this->assign('appid', $appid);
        return view();
    }

//红包列表
    public function hb($id = null, $appid = null)
    {
        $activity = Db::name('hb_activity')->where(['id' => $id])->field('name')->find();
        $this->assign('activity', $activity);

        $map['hid'] = $id;
        $map['h_appid'] = $appid;

        $field = "a.nickname,a.id,
        (select count(*) from zn_hb_integral where sid=a.id) as hNum,
        (select sum(integral) from zn_hb_integral where sid=a.id) as money";
        $list = Db::name('hb_member')
            ->alias('a')
            ->where($map)
            ->where('new_integral','GT',0)
            ->field($field)
            ->paginate(20, false, ['query' => ['id' => $id, 'appid' => $appid]]);
        $page = $list->render();
        $this->assign('member', $list);
        $this->assign('page', $page);
        $this->assign('id', $id);
        $this->assign('appid', $appid);
        return view();
    }
	//设置红包状态
    public function set_hb($id=null,$appid=null)
    {
       	$h = new HongBao();
        $setTime = cookie('setHb');
        $time = date('d');
        if($setTime['id'] == $id && $setTime['time'] == $time){
            return json(['state'=>0,'message'=>'一天只能点一次']);
        }
        $result = $h->getHongBao($id,$appid,'hb');
        if($result == true){
            cookie('setHb',['id'=>$id,'time'=>$time]);
            return json(['state'=>1,'message'=>'更新成功']);
        }else{
            return json(['state'=>0,'message'=>'更新失败']);
        }
    }

    //凭证查询
    public function proof($id = null, $appid = null)
    {
        if ($this->request->isPost()) {
            $data['zn_hb_member.phone'] = input('phone');
            $data['zn_hb_member.hid'] = input('id');
            $yzm = input('yzm');
            if (!empty($yzm)) {
                $data['zn_hb_member.yzm'] = $yzm;
            }
            // $member = Db::name('hb_member')->where($data)->select();
            // for ($m = 0; $m < count($member); $m++) {
            //     $member[$m]['order'] = Db::name('hb_orders')->where(['hid' => $id, 'uid' => $member[$m]['id']])->find();
            // }
            $member = Db::name('hb_member')
                ->join('zn_hb_orders','zn_hb_orders.uid=zn_hb_member.id')
                ->where($data)
                ->field('*,zn_hb_orders.id as orderId,zn_hb_member.id as id')
                ->select();
            $this->assign('member', $member);
        } else {
            $this->assign('member', false);
        }
        $activity = Db::name('hb_activity')->where(['id' => $id])->field('name')->find();
        $this->assign('activity', $activity);
        $this->assign('id', $id);
        $this->assign('appid', $appid);
        return view();
    }

    //活动地址
    public function site($id = null, $appid = null)
    {
        $activity = Db::name('hb_activity')->where(['id' => $id])->field('name')->find();
        $url = "http://www.42461.cn/packet/index/welcome/hid/{$id}";
        $url = urlencode($url);
		$this->assign('u',URL);
        $this->assign('url', $url);
        $this->assign('activity', $activity);
        $this->assign('id', $id);
        $this->assign('appid', $appid);
        return view();
    }

    //折线图
    public function examples($id = null, $appid = null)
    {
        $activity = Db::name('hb_activity')->where(['id' => $id])->field('name,begin_time,finish_time')->find();

        $begin_time = $activity['begin_time']; //开始时间
        $finish_time = $activity['finish_time'];//结束时间
        $number = strtotime($finish_time, time()) - strtotime($begin_time, time());
        $number = intval($number / 86400);  //时间天数
        $member = [];
        for ($i = 0; $i <= $number; $i++) {
            $datTime = strtotime(date('Y-m-d', strtotime($begin_time, time()))) + $i * 86400;
            $dayTime = strtotime(date('Y-m-d', strtotime($begin_time, time()))) + ($i + 1) * 86400;
            $member[$i]['date'] = date('Y-m-d', $datTime);
            $member[$i]['liu_num'] = Db::name('hb_member')
                ->where(['hid' => $id, 'h_appid' => $appid])
                ->where('savedate', 'between', [$datTime, $dayTime])
                ->count();
			$datTime = date('Y-m-d',$datTime);
            $dayTime = date('Y-m-d',$dayTime);
            $member[$i]['pay_num'] = Db::name('hb_orders')
                ->where(['hid' => $id])
                ->where('time', 'between', [$datTime, $dayTime])
                ->count();
        }
        $data['date'] = '';
        $data['liu_num'] = '';
        $data['pay_num'] = '';
        for ($a = 0; $a < count($member); $a++) {
            $data['date'] .= "'" . $member[$a]['date'] . "'" . ',';
            $data['liu_num'] .= "'" . $member[$a]['liu_num'] . "'" . ',';
            $data['pay_num'] .= "'" . $member[$a]['pay_num'] . "'" . ',';
        }
        $data['date'] = substr($data['date'],0,strlen($data['date'])-1);
        $data['liu_num'] = substr($data['liu_num'],0,strlen($data['liu_num'])-1);
        $data['pay_num'] = substr($data['pay_num'],0,strlen($data['pay_num'])-1);

        $m['sumup'] = Db::name('hb_member')->where(['hid'=>$id])->count();
        //$m['pay_sumup'] = Db::name('hb_member')->where(['hid'=>$id,'pay'=>1])->count();
        $m['hb_money'] = Db::name('hb_integral')->where(['hid'=>$id])->sum('integral');
        $m['pay_sumup'] = Db::name('hb_orders')->where(['hid' => $id])->count();
        $m['order'] = Db::name('hb_orders')->where(['hid'=>$id])->sum('money');
		$m['oldNum'] = Db::name('hb_member')->where(['state'=>4,'hid'=>$id,'pay'=>1])->count();
		$m['hbNum'] = Db::name('hb_integral')->where(['hid' => $id])->count();
        $this->assign('activity',$activity);
        $this->assign('data',$data);
        $this->assign('m',$m);
        $this->assign('id', $id);
        $this->assign('appid', $appid);
        return view();
    }

//修改积分
    public function yes($id,$openid,$money)
    {
        $result = Db::name('hb_member')->query("update zn_hb_member set new_integral=new_integral-{$money} where id={$id}");
        return $result;
    }

    //积分信息
    public function integ($id)
    {
    	$list = Db::query("select a.use,a.integral,b.name,b.nickname,b.phone,b.pay,c.time from zn_hb_integral as a join zn_hb_member as b on a.uid=b.id join zn_hb_orders as c on a.uid=c.uid where a.sid={$id}");
        $this->assign('list',$list);
        return view();
    }
    
	//设置为员工
    public function setMember($id, $openid,$hid,$state)
    {
        $result = Db::name('hb_member')->where(['id'=>$id,'hid'=>$hid,'openid'=>$openid])->update(['sid'=>$id,'masterid'=>$id,'state'=>$state,'pay'=>1]);
        if ($result > 0) {
            return json(['state' => 1, 'message' => '修改成功']);
        } else {
            return json(['state' => 0, 'message' => '修改失败']);
        }
    }
	
	//设置备注
    public function nodes($id,$data)
    {
        $m = new Member();
        $result = $m->setNode($id,'hb',$data);
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