<?php
namespace app\kjlog\controller;

use think\Controller;
use think\Db;
use wechat\WeChat;

class Login extends Controller
{
    public function index($code = null, $sid = null, $hid = null)
    {
        $activity = Db::name('kj_activity')
            ->where(['id' => $hid])
            ->field('appid')
            ->find();     //活动appid
        $users = Db::name('wx_users')
            ->where(['user_name' => $activity['appid']])
            ->field('appId,appSecret')
            ->find();
        $wechat = new WeChat();
        if(!empty(cookie('member')['id']) && cookie('member')['hid'] == $hid){
          $member = Db::name('kj_member')
                ->where(['id' => cookie('member')['id'], 'hid' => $hid])
                ->find();
            cookie('member', $member);
            $this->redirect("/kjlog/index/welcome?hid={$hid}&sid={$sid}");
            exit;
        }
        $data = $wechat->get_access_token($users['appId'], $users['appSecret'], $code);
        $userInfo = $wechat->get_userinfo($data["access_token"], $data["openid"]);
        $member = Db::name('kj_member')
            ->where(['openid' => $userInfo['openid'], 'hid' => $hid])
            ->find();
        if (!$member) {
        	if(!empty($sid)){
                $m = Db::name('kj_member')->where(['id'=>$sid])->field('masterid')->find();
                $d['masterid'] = $m['masterid'];
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
            $getId = Db::name('kj_member')->insertGetId($d);
            if (empty($sid)) {  //如果链接中上级id为空 则上级为自己
                Db::name('kj_member')
                    ->where(['id' => $getId])
                    ->update(['sid' => $getId]);
            }
            $member = Db::name('kj_member')
                ->where(['id' => $getId])
                ->find();
        }
        cookie('member', $member);
        $this->redirect("/kjlog/index/welcome?hid={$hid}&sid={$sid}");
    }

    public static function indexs($code = null, $sid = null, $hid = null)
    {
        $activity = Db::name('kj_activity')
            ->where(['id' => $hid])
            ->field('appid')
            ->find();     //活动appid
        $users = Db::name('wx_users')
            ->where(['user_name' => $activity['appid']])
            ->field('appId,appSecret')
            ->find();
        $wechat = new WeChat();
        if (empty($code)) {
            // $url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            $url = 'http://' . $_SERVER['HTTP_HOST'] . "/kjlog/login/index/hid/{$hid}/sid/{$sid}";
            $authorize_url = $wechat->get_authorize_url($users['appId'], $url, 1);
            header("Location: $authorize_url");
        } else {
            if (cookie('member')['hid'] == $hid) {
                header("Location: /kjlog/index/welcome/hid/{$hid}/sid/{$sid}");
                exit;
            }
            $data = $wechat->get_access_token($users['appId'], $users['appSecret'], $code);
            $userInfo = $wechat->get_userinfo($data["access_token"], $data["openid"]);
            $member = Db::name('kj_member')
                ->where(['openid' => $userInfo['openid'], 'hid' => $hid])
                ->find();
            if (!$member) {
                $d['nickname'] = $userInfo['nickname'];
                $d['openid'] = $userInfo['openid'];
                $d['sex'] = $userInfo['sex'];
                $d['address'] = $userInfo['country'] . $userInfo['province'] . $userInfo['city'];
                $d['pic'] = $userInfo['headimgurl'];
                $d['sid'] = $sid;
                $d['hid'] = $hid;
                $d['savedate'] = time();
                $d['h_appid'] = $activity['appid'];
                $getId = Db::name('kj_member')->insertGetId($d);
//              if (empty($sid)) {  //如果链接中上级id为空 则上级为自己
//                  Db::name('kj_member')
//                      ->where(['id' => $getId])
//                      ->update(['sid' => $getId]);
//              }
                $member = Db::name('kj_member')
                    ->where(['id' => $getId])
                    ->find();
            }
            cookie('member', $member);
            header("Location: /kjlog/index/welcome/hid/{$hid}/sid/{$sid}");
        }
    }
}