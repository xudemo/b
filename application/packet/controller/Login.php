<?php
namespace app\packet\controller;

use think\Controller;
use think\Db;
use wechat\WeChat;
use app\kjlog\model\Exception;
use app\kjlog\model\User;


class Login extends Controller
{
    public function index($code = null,$hid = null, $sid = null)
    {
        $activity = Db::name('hb_activity')
            ->where(['id' => $hid])
            ->field('appid')
            ->find();     //活动appid
        $user = new User();
        $users = $user->get_appid($activity['appid']);//查询微信服务器配置信息
        $wechat = new WeChat();
        if (!empty(cookie('hbMember')['id']) && cookie('hbMember')['hid'] == $hid) {
            $member = Db::name('hb_member')
                ->where(['id' => cookie('hbMember')['id'], 'hid' => $hid])
                ->find();
            cookie('hbMember', $member);
            $this->redirect("/packet/index/welcome?hid={$hid}&sid={$sid}");
            exit;
        }
        //获取access_token
        $data = $wechat->get_access_token($users['appId'], $users['appSecret'], $code);
        if (isset($data['errmsg']) && isset($data['errcode'])) {
            Exception::exception('登陆获取access_token失败，' . '错误代码：' . $data['errcode'] . '。错误消息：' . $data['errmsg']);
        }
        //拉取用户信息
        $userInfo = $wechat->get_userinfo($data["access_token"], $data["openid"]);
        if (isset($data['errmsg']) && isset($data['errcode'])) {
            Exception::exception('拉取用户信息失败，' . '错误代码：' . $data['errcode'] . '。错误消息：' . $data['errmsg']);
        }
        
        $member = Db::name('hb_member')
            ->where(['openid' => $userInfo['openid'], 'hid' => $hid])
            ->find();
        if (!$member) {
        	if(!empty($sid)){
                $m = Db::name('hb_member')->where(['id'=>$sid])->field('masterid')->find();
                $d['masterid']  = $m['masterid'];
            }
            $d['nickname'] = $userInfo['nickname'];
            $d['openid'] = $userInfo['openid'];
            $d['sex'] = $userInfo['sex'];
            $d['address'] = $userInfo['country'] . $userInfo['province'] . $userInfo['city'];
            $d['pic'] = $userInfo['headimgurl'];
            $d['sid'] = $sid;
            $d['hid'] = $hid;
            $d['savedate'] = time();
            $d['h_appid'] = $activity['appid'];
            $getId = Db::name('hb_member')->insertGetId($d);
            $member = Db::name('hb_member')
                ->where(['id' => $getId])
                ->find();
        }
        cookie('hbMember', $member);
        $this->redirect("/packet/index/welcome?hid={$hid}&sid={$sid}");
    }
}