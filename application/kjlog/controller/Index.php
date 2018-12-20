<?php
namespace app\kjlog\controller;

use think\Controller;
use think\Db;
use wechat\WeChat;
use app\kjlog\model\Member;
use app\kjlog\model\Activity;
use app\kjlog\model\User;
use app\kjlog\model\Msg;
use app\kjlog\model\Exception;
use app\kjlog\controller\Kj;

class Index extends Controller
{
//首页
    public function welcome($hid = null, $sid = null, $from = null, $isappinstalled = null)
    {
//         $start_time = array_sum(explode(' ', microtime()));
//        dump(cookie('member'));
//        exit;
        // cookie('member',null);
        // exit;
        header("Content-type:text/html;charset=utf-8");
        if (!empty($from)) {
            $this->redirect("/kjlog/index/welcome?hid={$hid}&sid={$sid}");
        }
        $wechat = new WeChat();
        //查询活动
        $memberModel = new Activity();
        $activity = $memberModel->get_activity($hid); //查询活动信息
        $time = strtotime($activity['finish_time']) - time();//活动剩余时间
        $wgnumber = Db::name('kj_member')->where(['hid' => $hid])->count()+intval($activity['liu_num']);//围观人数
        $bmnumber = Db::name('kj_member')->where(['hid' => $hid])->where('state', 'NEQ', 0)->count()+intval($activity['bao_num']);//报名人数
        $this->assign('activity', $activity);
        $this->assign('time', $activity['finish_time']);
        $this->assign('wgnumber', $wgnumber);
        $this->assign('bmnumber', $bmnumber);
        //查询该公众号的appId appSecret
        $user = new User();
        $users = $user->get_appid($activity['appid']);
        //判断是否授权登陆
       if ($wechat->islogin($hid) == false) {
           $this->login($users['appId'], $hid, $sid);
           exit;
       }
      //  $member = Db::name('kj_member')->where(['id' => 96])->find();
       	$member = cookie('member');
       // cookie('member',$member);
        //修改分享信息
        $rootPath = 'http://' . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
        $arr = $wechat->jsapiget($users['appId'], $users['appSecret'], $rootPath);
        if (isset($arr['errmsg']) && isset($arr['errcode'])) {
            Exception::exception('修改分享信息失败，'.'错误代码：'.$arr['errcode'].'。错误消息：'.$arr['errmsg']);
        }
        $this->assign('arr', $arr);
        //最新参与的用户
        $pic = Db::name('kj_member')->where(['hid' => $hid])->order('id desc')->limit(0, 30)->field('pic,nickname')->select();
        $this->assign('pic',$pic);
   //访问的信息可能性
        //①只有一个报名
        if (empty($sid) && $member['state'] == 0) {
            $this->assign('data',null);
            $this->assign('state', 1);  //报名
            $this->assign('member', $member); //成员
        }
        //②我的进度 一个立即支付 一个请人围观
        if (empty($sid) && $member['state'] != 0) {
            $data = Member::get_member($hid);
            $data['page'] = str_replace('&laquo;','上一页',$data['page']);
            $data['page'] = str_replace('&raquo;','下一页',$data['page']);    
            if($member['dmoney'] != '' || $member['dmoney'] != 0){
                $data['rate'] = ($data['k_money']/$member['dmoney'])*100;
                 if($data['rate'] > 74){
                    $data['rate'] = 74;
                }
            }else{
                $data['rate'] = 0;
            }
          	
            $this->assign('data', $data);
            $this->assign('data',$data);
            if($member['pay'] == 0){
                $this->assign('state', 2);  //我的进度 一个立即支付 一个请人围观
            }else{
                $this->assign('state', 5);  //我的进度 一个查看支付凭证 一个请人围观
            }
            $this->assign('member', $member); //成员
        }
        $oneself = array();
        if (!empty($sid)) {
          	$data['rate'] = 0;
            $this->assign('data',$data);
            $oneself = Db::name('kj_member')->where(['id' => $sid, 'hid' => $hid])->find(); //上级信息
            $data = Member::getMember($oneself,$sid);
            if($oneself['dmoney'] != '' || $oneself['dmoney'] != 0){
                $data['rate'] = ($data['k_money']/$oneself['dmoney'])*100;
              if($data['rate'] > 74){
                  $data['rate'] = 74;
              }
            }else{
                $data['rate'] = 0;
            }
          	
            $this->assign('data',$data);
        }
        //③提示可以报名 一个帮他砍价 一个我要报名
        if (!empty($sid) && $member['state'] == 0) {
            $this->assign('state', 3);  //提示可以报名 一个帮他砍价 一个我要报名
            $this->assign('member', $oneself); //成员
        }
        //④提示可以查看我的进度 一个帮他砍价 一个查看我的进度
        if (!empty($sid) && $member['state'] != 0) {
            $this->assign('state', 4);  //提示可以查看我的进度 一个帮他砍价 一个查看我的进度
            $this->assign('member', $oneself); //成员
        }
		$this->assign('url',URL);
        $this->assign('hid', $hid);
        $this->assign('sid',$sid);
        $this->assign('user', cookie('member'));//登陆用户消息

        // $end_time = array_sum(explode(' ', microtime()));
        // $differ = $end_time - $start_time;
        // dump($differ);
        // exit;
        return view('index');
    }

//帮他砍价
    //id 被砍者id  //hid活动id  kid 砍价者id
    public function kj($id, $hid)
    {
        //自己不能给自己砍价
        $kid = cookie('member')['id'];
        if ($kid == $id) {
            return json(['data' => [], 'state' => 0, 'message' => '不能给自己砍价']);
        }
        // 先判断这个人今天在这个活动是否砍过
        $data['addtime'] = date('Y-m-d');
        $nowUser = Db::name('kj_member')//当前砍价用户信息
        ->where(['id' => $kid])
            ->field('kj_time,kj_num,kj_state')
            ->find();

        if ($nowUser['kj_num'] == 1 && $nowUser['kj_time'] == $data['addtime'] && $nowUser['kj_state'] == 1) {
            return json(['data' => [], 'state' => 0, 'message' => '今天已砍过了，分享可以在砍一次。']);
        }
        if ($nowUser['kj_num'] >= 2 && $nowUser['kj_time'] == $data['addtime']) {
            return json(['data' => [], 'state' => 0, 'message' => '你今天已经砍到极限了']);
        }

        //砍价已经砍到最低
        $activity = Db::name('kj_activity')
            ->where(['id' => $hid])
            ->field('num_people,kjnum,old,newly,finish_time,appid,name')
            ->find();
        $time = strtotime($activity['finish_time']) - time();
        if ($time < 0) {
            return json(['data' => [], 'state' => 0, 'message' => '活动已结束']);
        }
        $member = Db::name('kj_member')//当前被砍价用户信息
        ->where(['id' => $id])
            ->field('money,state,pay,low_price')
            ->find();
        if ($member['pay'] == 1) {
            return json(['data' => [], 'state' => 0, 'message' => '已支付，无须帮忙了']);
        }
        $d_money = $member['low_price'];
        if ($member['money'] <= $d_money) {
            return json(['data' => [], 'state' => 0, 'message' => '当前价格已最低']);
        }
        //每人最多砍多少次
        $num = Db::name('kj_kjlog')->where(['pid' => $kid])->count(); //这个人砍了多少次了
        if ($activity['kjnum'] <= $num) {
            return json(['data' => [], 'state' => 0, 'message' => "每人最多砍{$activity['kjnum']}次"]);
        }
        // 最后一个用户将砍剩下的
        $k_num = Db::name('kj_kjlog')->where(['uid' => $id])->count();//已有多少人帮忙砍
        if ($activity['num_people'] <= $k_num + 1) {
            $data['money'] = $member['money'] - $d_money;
        } else {
            $xnum = $activity['num_people'] - $k_num; //这里是还需要多少。。人砍价。。
            $dmoney = $member['money'] - $d_money; //这里是当前还剩多少钱
            $pjmoney = $dmoney / $xnum; //获取平均值
            $pjmoney = mt_rand($pjmoney / 2, $pjmoney);
            $data['money'] = $pjmoney;
        }

        $data['uid'] = $id;
        $data['pid'] = $kid;
        $data['hid'] = $hid;
        $result = Db::name('kj_kjlog')->insert($data);
       // $result = 1;
        if ($result > 0) {
            $dMoney = $member['money'] - $data['money'];
            //修改砍价后用户信息
            $d['kj_time'] = $data['addtime'];
            //今日砍价次数
            if ($nowUser['kj_num'] == 2) {
                $d['kj_num'] = 1;
            } else {
                $d['kj_num'] = $nowUser['kj_num'] + 1;
            }
            $d['kj_state'] = 1;
            Db::name('kj_member')->where(['id' => $kid])->update($d);//修改砍价者消息
            Db::name('kj_member')->where(['id' => $id])->update(['money' => $dMoney]); //修改被砍价者消息
            //更新cookie
            $nowMember = Db::name('kj_member')->where(['id' => $kid])->find();
            cookie('member', $nowMember);
            //砍价成功通知
            $msg = '';
            if ($dMoney <= $d_money) {
                $msg = '恭喜你，已砍至最低价，请尽快支付。' . "\n";
            }
            Activity::get_kjId($activity['appid'], $id, $hid, $activity['name'], $dMoney, $data['money'], $msg);
            // return $a;
            return json(['data' => $nowMember, 'state' => 1, 'message' => $data['money']]);
        } else {
            return json(['data' => [], 'state' => 0, 'message' => "砍价失败"]);
        }
    }

//报名
    public function apply($name, $phone, $store, $old, $tid, $masterid)
    {
        $id = cookie('member')['id']; //报名者id
        $hid = cookie('member')['hid']; //活动id
        $activity = Db::name('kj_activity')
            ->where(['id' => $hid])
            ->field('money,newly_money,appid,name,begin_time,finish_time,intro,scan_img,twocode_width,twocode_height,twocode_x,twocode_y,newly,old')
            ->find(); //查询活动原价
            $time = strtotime($activity['finish_time']) - time();//活动剩余时间
        if ($time < 0) {
            return json(['state' => 0, 'message' => '活动已结束']);
        }
        $data['name'] = $name;
        $data['phone'] = $phone;
        $data['shop_site'] = $store;
        $data['state'] = $old;
        if ($old == 1) { //新生
            $data['dmoney'] = $activity['newly_money'];
            $data['money'] = $activity['newly_money'];
            $data['low_price'] = $activity['newly'];
        }
        if ($old == 2) { //老生
            $data['dmoney'] = $activity['money'];
            $data['money'] = $activity['money'];
            $data['low_price'] = $activity['old'];
        }
        $data['tid'] = $tid;
        $data['masterid'] = $masterid;
        $result = Db::name('kj_member')->where(['id' => $id])->update($data);
        if ($result > 0) {
           //更新session
            $member = Db::name('kj_member')->where(['id' => $id])->find();
            cookie('member', $member);
            //生成带二维码的首页图片
            Activity::img($hid,$id,$activity['scan_img'],$activity['twocode_width'],$activity['twocode_height'],$activity['twocode_x'],$activity['twocode_y']);
            //报名成功通知
            $time = str_replace("T"," ",$activity['begin_time']) . '至' . str_replace("T"," ",$activity['finish_time']);
            Activity::get_bmId($activity['appid'], $id, $hid, $activity['name'], $time, $store, $activity['intro']);
            //参团成功通知
            Activity::get_ctId($activity['appid'], $tid, $hid, $name, date('Y-m-d H-i-s'), $activity['finish_time']);
           // return $a;
            return json(['state' => 1, 'message' => '报名成功']);
        } else {
            return json(['state' => 0, 'message' => '报名失败']);
        }
    }

//查看我的亲友团
    public function friend($id)
    {
        $pic = Db::name('kj_member')->where(['hid' => $id])->order('id desc')->limit(0, 20)->field('pic')->select();
        return json($pic);
    }

//省钱攻略
    public function gl($hid)
    {
        $activity = Db::name('kj_activity')->where(['id' => $hid])->field('strategy')->find();
        $this->assign('activity', $activity);
        return view();
    }

//请人围观
    public function onlooker($id=null)
    {
        $this->assign('id',$id);
        return view();
    }

//登陆
    public function login($appid, $hid, $sid)
    {
        $wechat = new WeChat();
        $wechat->get_authorize_url($appid, $hid, $sid);
    }

//判断支付
    public function pay($id, $hid)
    {
        $activity = Db::name('kj_activity')
            ->where(['id' => $hid])
            ->field('finish_time')
            ->find();
        $time = strtotime($activity['finish_time']) - time();//活动剩余时间
        if ($time < 0) {
            return json(['state' => 0, 'message' => '活动已结束']);
        }
        $member = Db::name('kj_member')
            ->where(['id' => $id])
            ->field('pay')
            ->find();
        if ($member['pay'] == 1) {
            return json(['state' => 0, 'message' => '您已支付过了']);
        }
        return json(['state' => 1, 'message' => '快去支付吧']);
    }
    //分享成功后修改
    //userId 当前登录用户id memberId 访问你的用户Id 相当于上级Id
    public function fx($userId = null, $memberId = null,$hid=null)
    {
        //砍价分享
        if ($userId != $memberId) {
            $result = Kj::fxKj($memberId,$hid,$userId);
            return json($result);
        }
        //自己分享自己的
        if ($userId == $memberId) {
            $member = Db::name('kj_member')->where(['id' => $memberId])->field('share_num')->find();
            //提示分享到一次朋友圈
            if ($member['share_num'] == 0) {
                Db::name('kj_member')->where(['id' => $memberId])->update(['share_num' => 1]);
                return json(['state' => 2, 'message' => '朋友圈', 'data' => 0]);
            }
            //提示分享到两次群聊
            if ($member['share_num'] >= 1 && $member['share_num'] < 2) {
                Db::name('kj_member')->where(['id' => $memberId])->update(['share_num' => $member['share_num'] + 1]);
                return json(['state' => 3, 'message' => '群聊', 'data' => $member['share_num']]);
            }
            if ($member['share_num'] >= 2 && $member['share_num'] < 10) {
                Db::name('kj_member')->where(['id' => $memberId])->update(['share_num' => $member['share_num'] + 1]);
                return json(['state' => 4, 'message' => '朋友', 'data' => $member['share_num'] - 2]);
            }
            Db::name('kj_member')->where(['id' => $memberId])->update(['share_num' => $member['share_num'] + 1]);
            return json(['state' => 5, 'message' => '分享成功', 'data' => $member['share_num'] + 1]);
        }
    }

    //查询当前分享进度
    public function share()
    {
        $id = cookie('member')['id'];
        $member = Db::name('kj_member')->where(['id' => $id])->field('share_num')->find();
        //提示分享到一次朋友圈
        if ($member['share_num'] == 0) {
            return json(['state' => 2, 'message' => '朋友圈', 'data' => 0]);
        } else if ($member['share_num'] >= 1 && $member['share_num'] <= 2) {
            return json(['state' => 3, 'message' => '群聊', 'data' => $member['share_num'] - 1]);
        } else if ($member['share_num'] > 2 && $member['share_num'] < 10) {
            return json(['state' => 4, 'message' => '朋友', 'data' => $member['share_num'] - 3]);
        }else{
            return json(['state' => 5, 'message' => '提示成功', 'data' => $member['share_num']]);
        }
    }

//错误消息提示
    public function message($message = null)
    {
        $this->assign('message', $message);
        return view();
    }
//活动策划
    public function ch($type=null)
    {
        $list['company'] = 1;
        if($type == 'kj'){
            $hid = cookie('member')['hid'];
            $list = Db::name('kj_activity')->where(['id'=>$hid])->field('company')->find();
        }
        if($type == 'hb'){
            $hid = cookie('hbMember')['hid'];
            $list = Db::name('hb_activity')->where(['id'=>$hid])->field('company')->find();
        }
        $this->assign('company',$list['company']);
        return view();
    }
//验证签名配置服务器
    public function checkSignature($signature, $timestamp, $nonce, $token)
    {
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        if ($tmpStr == $signature) {
            return true;
        } else {
            return false;
        }
    }
//配置服务器
    // id 活动id sid  上级id
    public function index($hid = null, $sid = null, $code = null, $signature = null, $timestamp = null, $nonce = null, $echostr = null)
    {
        return $this->fetch('welcome');
        exit;
       
        header("Content-type:text/html;charset=utf-8");
        if (!empty($echostr)) { //配置服务器
            $token = 'znote';  //配置时设置的token
            if ($this->checkSignature($signature, $timestamp, $nonce, $token)) {
                echo $echostr;
                exit();
            }
        } else {
            $wechat = new WeChat();
            $activity = Db::name('kj_activity')->where(['id' => $hid])->find();//活动
            if ($activity) {
                $time = strtotime($activity['finish_time']) - time();//活动剩余时间
                $num = Db::name('kj_member')->where(['hid' => $hid])->count();
                $wgnumber = Db::name('kj_member')->where(['hid' => $hid, 'state' => 0])->count();//围观人数
                $bmnumber = $num - $wgnumber;  //报名人数
                $this->assign('activity', $activity);
                $this->assign('time', $time);
                $this->assign('wgnumber', $wgnumber);
                $this->assign('bmnumber', $bmnumber);
                $users = Db::name('wx_users')->where(['user_name' => $activity['appid']])->field('appId,appSecret')->find();
            } else {
                $this->assign('message', '活动不存在或者已经结束');
                return view('message');
                exit;
            }
//            if (isset($users)) {  //获取微信用户信息
//                if (empty($code)) {
//                    $url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
//                    $authorize_url = $wechat->get_authorize_url($users['appId'], $url, 1);
//                    $this->redirect($authorize_url);
//                } else {
//                    $data = $wechat->get_access_token($users['appId'], $users['appSecret'], $code);
//                    $userInfo = $wechat->get_userinfo($data["access_token"], $data["openid"]);
//                }
//            }
            //$u = 'http://' . $_SERVER['HTTP_HOST'] . "/kjlog/index/index/hid/{$hid}/sid/{$sid}";
            $rootPath = 'http://' . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
            $arr = $wechat->jsapiget($users['appId'], $users['appSecret'], $rootPath);
            $this->assign('arr', $arr);
            $userInfo['openid'] = 'oGR1N1WiFHkzxOkaVcYN1o2DTNAI';
            $member = array();
            $member = $this->set_member($activity['appid'], $userInfo, $hid, $sid); //openid生成的用户信息
            session('member', $member);  //把登陆的用户信息存入session方便砍价
            if (!empty($sid)) {
                $oneself = Db::name('kj_member')->where(['id' => $sid, 'hid' => $hid])->find(); //上级信息
            }
            if (empty($sid) && $member['state'] == 0) {
                $this->assign('state', 1);  //报名
                $this->assign('member', $member); //成员
            }
            if (empty($sid) && $member['state'] != 0) {
                $k_num = Db::name('kj_kjlog')->where(['uid' => $member['id']])->count();//已有多少人帮忙砍
                $k_money = $member['dmoney'] - $member['money']; //省了多少钱

                $myMember = Db::name('kj_member')->where(['tid' => $member['id']])->select();
                $this->assign('myMember', $myMember);

                $friend = Db::name('kj_kjlog')->where(['uid' => $member['id']])->limit(0, 5)->select();
                for ($a = 0; $a < count($friend); $a++) {
                    $friend[$a]['member'] = Db::name('kj_member')->where(['id' => $friend[$a]['pid']])->field('nickname,pic')->find();
                }
                $this->assign('friend', $friend);
                $this->assign('k_num', $k_num);
                $this->assign('k_money', $k_money);
                $this->assign('d_money', $member['money']);
                $this->assign('state', 2);  //我的进度 一个立即支付 一个请人围观
                $this->assign('member', $member); //成员
            }
            if (!empty($sid) && $member['state'] == 0) {
                $k_num = Db::name('kj_kjlog')->where(['uid' => $oneself['id']])->count();//已有多少人帮忙砍
                $k_money = $oneself['dmoney'] - $oneself['money']; //省了多少钱
                $friend = Db::name('kj_kjlog')->where(['uid' => $sid])->limit(0, 5)->select();
                for ($a = 0; $a < count($friend); $a++) {
                    $friend[$a]['member'] = Db::name('kj_member')->where(['id' => $friend[$a]['pid']])->field('nickname,pic')->find();
                }
                $this->assign('friend', $friend);
                $this->assign('k_num', $k_num);
                $this->assign('k_money', $k_money);
                $this->assign('d_money', $oneself['money']);
                $this->assign('state', 3);  //提示可以报名 一个帮他砍价 一个我要报名
                $this->assign('member', $oneself); //成员
            }
            if (!empty($sid) && $member['state'] != 0) {
                $k_num = Db::name('kj_kjlog')->where(['uid' => $oneself['id']])->count();//已有多少人帮忙砍
                $k_money = $oneself['dmoney'] - $oneself['money']; //省了多少钱
                $friend = Db::name('kj_kjlog')->where(['uid' => $sid])->limit(0, 5)->select();
                for ($a = 0; $a < count($friend); $a++) {
                    $friend[$a]['member'] = Db::name('kj_member')->where(['id' => $friend[$a]['pid']])->field('nickname,pic')->find();
                }
                $this->assign('friend', $friend);
                $this->assign('k_num', $k_num);
                $this->assign('k_money', $k_money);
                $this->assign('d_money', $oneself['money']);
                $this->assign('state', 4);  //提示可以查看我的进度 一个帮他砍价 一个查看我的进度
                $this->assign('member', $oneself); //成员
            }
            $this->assign('hid', $hid);
            return view();
        }
    }

//用openid查询是否添加用户
    public function set_member($appid, $userInfo, $hid, $sid)
    {
        $member = Db::name('kj_member')->where(['openid' => $userInfo['openid'], 'hid' => $hid])->find();
        if (!$member) {
            $d['nickname'] = $userInfo['nickname'];
            $d['openid'] = $userInfo['openid'];
            $d['sex'] = $userInfo['sex'];
            $d['address'] = $userInfo['country'] . $userInfo['province'] . $userInfo['city'];
            $d['pic'] = $userInfo['headimgurl'];
            $d['sid'] = $sid;
            $d['hid'] = $hid;
            $d['savedate'] = time();
            $d['h_appid'] = $appid;
            $getId = Db::name('kj_member')->insertGetId($d);
            if (empty($sid)) {  //如果链接中上级id为空 则上级为自己
                Db::name('kj_member')->where(['id' => $getId])->update(['sid' => $getId]);
            }
            $member = Db::name('kj_member')->where(['id' => $getId])->find();
        }
        return $member;
    }
}