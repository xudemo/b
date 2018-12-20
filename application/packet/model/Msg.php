<?php
namespace app\packet\model;

use think\Controller;
use think\Db;
use wechat\WeChat;

class Msg extends Controller
{
    //支付成功通知
    public static function get_zfMsg($hid,$openid,$transaction_id,$total_fee,$yzm,$shop_site)
    {
        $activity = Db::name('hb_activity')->where(['id'=>$hid])->field('finish_time,appid')->find();
        $users = Db::name('wx_users')
            ->where(['user_name' => $activity['appid']])
            ->field('appId,appSecret,zf_id')
            ->find();
        if (!isset($users['appId']) && !isset($users['appSecret']) && !isset($users['zf_id'])) {
            return false;
        }
        $finish_time =  str_replace("T"," ",$activity['finish_time']);
        $weChat = new WeChat();
        $token = $weChat->getToken($users['appId'], $users['appSecret']);
       if(isset($token['access_token'])){
            $access_token = $token['access_token'];
            $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token={$access_token}";
            $arr['touser'] = $openid;
            $arr['template_id'] = $users['zf_id']; //模板ID
            $arr['url'] = URL . "/packet/index/welcome/hid/{$hid}";
            $arr['topcolor'] = "#7b68ee";
            $arr['data'] = array(
                'first' => array('value' => "您好，您的微信支付已成功", 'color' => "#173177"),
                'keyword1' => array('value' => "{$transaction_id}", 'color' => "#173177"), //订单编号
                'keyword2' => array('value' => "{$total_fee}", 'color' => "#173177"), //消费金额
                'keyword3' => array('value' => "{$shop_site}", 'color' => "#173177"), //消费门店
                'keyword4' => array('value' => "{$finish_time}", 'color' => "#173177"), //消费时间
                'remark' => array('value' => "恭喜您支付成功，到店消费验证码为{$yzm}", 'color' => "#e70505")
            );
            $data = json_encode($arr);
            $result = self::http($url, $data, 'POST', array("Content-type: text/html; charset=utf-8"));
            return $result;
        }else{
            return false;
        }
    }
    //积分红包通知
    public static function get_packetMsg($hid, $openid,$name,$id)
    {
        $activity = Db::name('hb_activity')->where(['id' => $hid])->field('finish_time,appid')->find();
        $users = Db::name('wx_users')
            ->where(['user_name' => $activity['appid']])
            ->field('appId,appSecret,hb_id')
            ->find();
        if (!isset($users['appId']) && !isset($users['appSecret']) && !isset($users['hb_id'])) {
            return false;
        }
        $finish_time = str_replace("T", " ", $activity['finish_time']);
        $weChat = new WeChat();
        $token = $weChat->getToken($users['appId'], $users['appSecret']);
        if (isset($token['access_token'])) {
            $access_token = $token['access_token'];
            $date = date("Y-m-d H:i:s");
            $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token={$access_token}";
            $arr['touser'] = $openid;
            $arr['template_id'] = $users['hb_id']; //模板ID
            $arr['url'] = URL . "/packet/getpacket/index/id/{$id}";
            $arr['topcolor'] = "#7b68ee";
            $arr['data'] = array(
                'first' => array('value' => "您好，有用户通过您的推荐已经参加该活动！", 'color' => "#173177"),
                'keyword1' => array('value' => "{$name}", 'color' => "#173177"), //微信昵称
                'keyword2' => array('value' => "{$date}", 'color' => "#173177"), //时间
                'remark' => array('value' => "你成功获得一个微信积分红包，点击消息打开领取。", 'color' => "#e70505")
            );
            $data = json_encode($arr);
            $result = self::http($url, $data, 'POST', array("Content-type: text/html; charset=utf-8"));
            return $result;
        } else {
            return false;
        }
    }
    public static function http($url, $params, $method = 'POST', $header = array(), $multi = false)
    {
        $opts = array(
            CURLOPT_TIMEOUT => 30,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => $header
        );
        /* 根据请求类型设置特定参数 */
        switch (strtoupper($method)) {
            case 'GET':
                $opts[CURLOPT_URL] = $url . '?' . http_build_query($params);
                break;
            case 'POST':
                //判断是否传输文件
                $opts[CURLOPT_URL] = $url;
                $opts[CURLOPT_POST] = 1;
                $opts[CURLOPT_POSTFIELDS] = $params;
                break;
            default:
                throw new \Exception('不支持的请求方式！');
        }
        /* 初始化并执行curl请求 */
        $ch = curl_init();
        curl_setopt_array($ch, $opts);
        $data = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error) throw new \Exception('请求发生错误：' . $error);
        return $data;
    }
}