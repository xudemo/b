<?php
namespace app\kjlog\controller;

use think\Controller;
use think\Db;
use app\kjlog\model\Activity;
use wechat\WeChat;

//砍价
class Kj extends Controller
{
    //id 被砍者id  //hid活动id  kid 砍价者id
    function index($id = null, $hid = null)
    {
        //自己不能给自己砍价
        $kid = cookie('member')['id'];
        if ($kid == $id) {
            return json(['data' => [], 'state' => 0, 'message' => '不能给自己砍价']);
        }
        $date = date('Y-m-d');
        //当前砍价用户信息
        $nowUser = Db::name('kj_member')
            ->where(['id' => $kid])
            ->field('kj_time,kj_num,kj_state,share')
            ->find();
        //活动信息
        $activity = Db::name('kj_activity')
            ->where(['id' => $hid])
            ->field('num_people,kjnum,old,newly,finish_time,appid,name,day_num')
            ->find();
        $time = strtotime($activity['finish_time']) - time();
        if ($time < 0) {
            return json(['data' => [], 'state' => 0, 'message' => '活动已结束']);
        }
        if ($nowUser['kj_num'] >= $activity['day_num'] && $nowUser['kj_time'] == $date && $nowUser['share'] < 3) {
            return json(['data' => [], 'state' => 0, 'message' => '你今天已经砍过了,分享可以在砍。']);
        }
        if ($nowUser['kj_num'] >= $activity['day_num'] && $nowUser['kj_time'] == $date) {
            return json(['data' => [], 'state' => 0, 'message' => '你今天已经砍到极限了,分享出去邀请更多的人帮忙砍价。']);
        }
        //当前被砍价用户信息
        $member = Db::name('kj_member')
            ->where(['id' => $id])
            ->field('money,state,pay,low_price')
            ->find();
        if ($member['pay'] == 1) {
            return json(['data' => [], 'state' => 0, 'message' => '已支付，无须帮忙了']);
        }
        $d_money = $member['low_price'];
        if ($member['money'] <= $d_money) {
            return json(['data' => [], 'state' => 0, 'message' => '已砍至最低价，无须帮忙了']);
        }
        $num = Db::name('kj_kjlog')->where(['pid' => $kid, 'state' => 0])->count(); //这个人砍了多少次了
        if ($activity['kjnum'] <= $num) {
            return json(['data' => [], 'state' => 0, 'message' => "每人最多砍{$activity['kjnum']}次"]);
        }

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
        $data['addtime'] = $date;
        $result = Db::name('kj_kjlog')->insert($data);
        if ($result > 0) {
            $dMoney = $member['money'] - $data['money'];
            //修改砍价后用户信息
            $d['kj_time'] = $date;
            if ($nowUser['kj_time'] == $date) {
                $d['kj_num'] = $nowUser['kj_num'] + 1;
            } else {
                $d['kj_num'] = 1;
            }
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
            return json(['data' => $nowMember, 'state' => 1, 'message' => $data['money']]);
        } else {
            return json(['data' => [], 'state' => 0, 'message' => "砍价失败"]);
        }
    }

    public static function fxKj($id = null, $hid = null,$kid=null)
    {
        $date = date('Y-m-d');
        //当前砍价用户信息
        $nowUser = Db::name('kj_member')
            ->where(['id' => $kid])
            ->field('kj_time,kj_num,kj_state,share')
            ->find();
        //活动信息
        $activity = Db::name('kj_activity')
            ->where(['id' => $hid])
            ->field('num_people,kjnum,old,newly,finish_time,appid,name,day_num')
            ->find();
        $time = strtotime($activity['finish_time']) - time();
        if ($time < 0) {
            return ['data' => [], 'state' => 0, 'message' => '活动已结束'];
        }
        if($nowUser['share'] >= 3){
            return ['data' => [], 'state' => 6, 'message' => '分享成功，继续分享可以邀请更多人帮忙砍价！'];
        }
        //当前被砍价用户信息
        $member = Db::name('kj_member')
            ->where(['id' => $id])
            ->field('money,state,pay,low_price')
            ->find();
        if ($member['pay'] == 1) {
            return ['data' => [], 'state' => 0, 'message' => '已支付，无须帮忙了'];
        }
        $d_money = $member['low_price'];
        if ($member['money'] <= $d_money) {
            return ['data' => [], 'state' => 0, 'message' => '已砍至最低价，无须帮忙了'];
        }
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
        $data['addtime'] = $date;
        $data['state'] = 1;
        if($nowUser['share'] == 1){
            $d['share'] = $nowUser['share']+1;
            Db::name('kj_member')->where(['id' => $kid])->update($d);//修改砍价者消息
            return ['data' => $nowUser, 'state' => 7, 'message' => "再次分享可以在砍一次"];
        }
        $result = Db::name('kj_kjlog')->insert($data);
        if($result > 0){
            $dMoney = $member['money'] - $data['money'];
            Db::name('kj_member')->where(['id' => $id])->update(['money' => $dMoney]); //修改被砍价者消息
            //修改砍价后用户信息
            $d['share'] = $nowUser['share']+1;
            Db::name('kj_member')->where(['id' => $kid])->update($d);//修改砍价者消息

            //更新cookie
            $nowMember = Db::name('kj_member')->where(['id' => $kid])->find();
            cookie('member', $nowMember);
            //砍价成功通知
            $msg = '';
            if ($dMoney <= $d_money) {
                $msg = '恭喜你，已砍至最低价，请尽快支付。' . "\n";
            }
            Activity::get_kjId($activity['appid'], $id, $hid, $activity['name'], $dMoney, $data['money'], $msg);
            return ['data' => $nowMember, 'state' => 8, 'message' => $data['money']];
        }else{
            return ['data' => [], 'state' => 0, 'message' => "砍价失败"];
        }
    }
    //判断报名
    public function applysShow()
    {
    	if(cookie('subscribe') == 1){
            return json(['state' => 2, 'message' => true]);
        }
		
        $openid = cookie('member')['openid'];
        $hid = cookie('member')['hid'];
        //活动信息
        $activity = Db::name('kj_activity')
            ->where(['id' => $hid])
            ->field('appid')
            ->find();
        $users = Db::name('wx_users')
            ->where(['user_name' => $activity['appid']])
            ->field('appId,appSecret')
            ->find();
        $weChat = new WeChat();
        $token = $weChat->getToken($users['appId'], $users['appSecret']);
        if(isset($token['access_token'])){
             $access_token = $token['access_token'];
        }else{
            return json(['state'=>1,'message'=>$token]);
        }
        $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token={$access_token}&openid={$openid}&lang=zh_CN";
        $html = file_get_contents($url);
        $html = json_decode($html,true);
        if($html['subscribe'] == 0){
            return json(['state'=>1,'message'=>'请先关注公众号']);
        }else{
        	cookie('subscribe',1);
            return json(['state'=>2,'message'=>true]);
        }
       // return json($html);
    }

public function apply()
    {
        $openid = "oGR1N1WiFHkzxOkaVcYN1o2DTNAI";
        $hid = 18;
        //活动信息
        $activity = Db::name('kj_activity')
            ->where(['id' => $hid])
            ->field('appid')
            ->find();
        $users = Db::name('wx_users')
            ->where(['user_name' => $activity['appid']])
            ->field('appId,appSecret')
            ->find();
        $weChat = new WeChat();
        $token = $weChat->getToken($users['appId'], $users['appSecret']);
        if (isset($token['access_token'])) {
            $access_token = $token['access_token'];
        } else {
            return json(['state' => 1, 'message' => $token]);
        }
		dump($access_token);
        $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token={$access_token}&openid={$openid}&lang=zh_CN";
        $html = file_get_contents($url);
        $html = json_decode($html, true);
        dump($html);
    }
}