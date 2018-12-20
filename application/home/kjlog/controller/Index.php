<?php
namespace app\kjlog\controller;

use think\Controller;
use think\Db;
use wechat\WeChat;
use app\kjlog\model\Member;
use think\facade\App;

class Index extends Controller
{
//首页
    public function welcome($hid = null, $sid = null)
    {
        header("Content-type:text/html;charset=utf-8");
        $wechat = new WeChat();
//        session('member',null);
//        exit;
        //判断是否授权登陆
        if ($wechat->islogin($hid) == false) {
            header("Location: /kjlog/login/index/hid/{$hid}/sid/{$sid}");
        }
        $member = session('member');
        //$member = Db::name('kj_member')->where(['id' => 37])->find();
        //查询活动
        $memberModel = new Member();
        $activity = $memberModel->get_activity($hid);
        if ($activity == false) {
            $this->assign('message', '活动不存在或者已经结束');
            return view('message');
            exit;
        }
        $time = strtotime($activity['finish_time']) - time();//活动剩余时间
        $num = Db::name('kj_member')->where(['hid' => $hid])->count(); //活动总人数
        $wgnumber = Db::name('kj_member')->where(['hid' => $hid, 'state' => 0])->count();//围观人数
        $bmnumber = $num - $wgnumber;  //报名人数
        $this->assign('activity', $activity);
        $this->assign('time', $time);
        $this->assign('wgnumber', $wgnumber);
        $this->assign('bmnumber', $bmnumber);
        //查询该公众号的appId appSecret
        $users = Db::name('wx_users')->where(['user_name' => $activity['appid']])->field('appId,appSecret')->find();
        if (!$users) {
            $this->assign('message', '查询appId和appSecret失败');
            return view('message');
            exit;
        }
        //修改分享信息
        $rootPath = 'http://' . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
        $arr = $wechat->jsapiget($users['appId'], $users['appSecret'],$rootPath);
        if(isset($arr['errmsg'])){
            $this->assign('message', $arr['errmsg']);
            return view('message');
            exit;
        }
        $this->assign('arr', $arr);
        //访问的信息可能性
        //①报名
        if (empty($sid) && $member['state'] == 0) {
            $this->assign('state', 1);  //报名
            $this->assign('member', $member); //成员
        }
        //②我的进度 一个立即支付 一个请人围观
        if (empty($sid) && $member['state'] != 0) {
            $k_num = Db::name('kj_kjlog')->where(['uid' => $member['id']])->count();//已有多少人帮忙砍
            $k_money = $member['dmoney'] - $member['money']; //省了多少钱
            $myMember = Db::name('kj_member')->where(['tid' => $member['id']])->select(); //我的团员
            $this->assign('myMember', $myMember);
            $friend = Db::name('kj_kjlog')->where(['uid' => $member['id']])->limit(0, 5)->select();//帮我砍价的
            for ($a = 0; $a < count($friend); $a++) {
                $friend[$a]['member'] = Db::name('kj_member')->where(['id' => $friend[$a]['pid']])->field('nickname,pic')->find();
            }
            //我的团所有人
            $num = Db::name('kj_member')->where(['tid' => $member['id']])->count();
            //我的团已支付人数
            $pay_num = Db::name('kj_member')->where(['tid' => $member['id'], 'pay' => 1])->count();
            //我的团未支付人数
            $nopay_num = $num - $pay_num;
            //我的团长姓名
            $t_name = Db::name('kj_member')->where(['id' => $member['tid']])->field('name')->find();
            //我所在团所有人
            $tnum = Db::name('kj_member')->where(['tid' => $member['tid']])->count();
            //团已支付人数
            $tpay_num = Db::name('kj_member')->where(['tid' => $member['tid'], 'pay' => 1])->count();
            $this->assign('t_name', $t_name['name']);
            $this->assign('tnum', $tnum);
            $this->assign('tpay_num', $tpay_num);
            $this->assign('num', $num);
            $this->assign('pay_num', $pay_num);
            $this->assign('nopay_num', $nopay_num);
            $this->assign('friend', $friend);
            $this->assign('k_num', $k_num);
            $this->assign('k_money', $k_money);
            $this->assign('d_money', $member['money']);
            $this->assign('state', 2);  //我的进度 一个立即支付 一个请人围观
            $this->assign('member', $member); //成员
        }
        $oneself = array();
        if (!empty($sid)) {
            $oneself = Db::name('kj_member')->where(['id' => $sid, 'hid' => $hid])->find(); //上级信息
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
        $this->assign('hid', $hid);
        return view('index');
    }

//帮他砍价
    //id 被砍者id  //hid活动id  kid 砍价者id
    public function kj($id, $hid)
    {
        //自己不能给自己砍价
        $kid = session('member')['id'];
        if ($kid == $id) {
            return json(['data' => [], 'state' => 0, 'message' => '不能给自己砍价']);
        }
        // 先判断这个人今天在这个活动是否砍过
        $data['addtime'] = date('Y-m-d');
        $kj_date = Db::name('kj_kjlog')->where(['pid' => $kid, 'addtime' => $data['addtime']])->find();
        if ($kj_date) {
            return json(['data' => [], 'state' => 0, 'message' => '今天已经砍过价格了']);
        }
        //砍价已经砍到最低
        $activity = Db::name('kj_activity')->where(['id' => $hid])->field('num_people,kjnum,old,newly,finish_time')->find();
        $time = strtotime($activity['finish_time']) - time();
        if ($time < 0) {
            return json(['data' => [], 'state' => 0, 'message' => '活动已结束']);
        }
        $member = Db::name('kj_member')->where(['id' => $id])->field('money,state,pay')->find();
        if ($member['pay'] == 1) {
            return json(['data' => [], 'state' => 0, 'message' => '已支付，无须帮忙了']);
        }
        $d_money = 0;
        if ($member['state'] == 1) {  //新生低价
            $d_money = $activity['newly'];
            if ($member['money'] <= $activity['newly']) {
                return json(['data' => [], 'state' => 0, 'message' => '当前价格已最低']);
            }
        } else if ($member['state'] == 2 || $member['state'] == 3) { //老生和员工低价
            $d_money = $activity['old'];
            if ($member['money'] <= $activity['old']) {
                return json(['data' => [], 'state' => 0, 'message' => '当前价格已最低']);
            }
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
        $result = Db::name('kj_kjlog')->insert($data);
        if ($result > 0) {
            $d_money = $member['money'] - $data['money'];
            Db::name('kj_member')->where(['id' => $id])->update(['money' => $d_money]);
            return json(['data' => [], 'state' => 1, 'message' => "已经砍下来{$data['money']}元"]);
        } else {
            return json(['data' => [], 'state' => 0, 'message' => "砍价失败"]);
        }
    }

//报名
    public function apply($name, $phone, $store, $old, $tid, $masterid)
    {
        $id = session('member')['id']; //报名者id
        $hid = session('member')['hid']; //活动id
        $activity = Db::name('kj_activity')->where(['id' => $hid])->field('money,newly_money')->find(); //查询活动原价
        $data['name'] = $name;
        $data['phone'] = $phone;
        $data['shop_site'] = $store;
        $data['state'] = $old;
        if ($old == 1) { //新生
            $data['dmoney'] = $activity['newly_money'];
            $data['money'] = $activity['newly_money'];
        }
        if ($old == 2) { //老生
            $data['dmoney'] = $activity['money'];
            $data['money'] = $activity['money'];
        }
        $data['tid'] = $tid;
        $data['masterid'] = $masterid;
        $result = Db::name('kj_member')->where(['id' => $id])->update($data);
        if ($result > 0) {
            //更新session
            $member = Db::name('kj_member')->where(['id' => $id])->find();
            session('member', $member);
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
    public function gl()
    {
        return view();
    }

//请人围观
    public function onlooker()
    {
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
        header("Content-type:text/html;charset=utf-8");
        if (!empty($echostr)) { //配置服务器
            $token = 'xuxiaoer';  //配置时设置的token
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