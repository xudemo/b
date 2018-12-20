<?php
namespace app\kjlog\model;

use wechat\WeChat;

class Msg
{
    public $openid;
    public $access_token;

    public function __construct($appid, $appSecret, $openid)
    {
        $this->openid = $openid;
        $weChat = new WeChat();
        $token = $weChat->getToken($appid, $appSecret);
        $this->access_token = $token['access_token'];
    }

    //报名成功通知
    public function bmReply($template_id, $hid, $name, $time, $site, $particulars)
    {
      	//$u = urlencode($this->access_token);
        $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token={$this->access_token}";
        $arr['touser'] = $this->openid;
        $arr['template_id'] = $template_id;//模板ID
        $arr['url'] = URL . "/kjlog/index/welcome/hid/{$hid}";
        $arr['topcolor'] = "#7b68ee";
        $arr['data'] = array(
            'first' => array('value' => "尊敬的用户，您已成功报名该活动。", 'color' => "#173177"),
            'keyword1' => array('value' => "{$name}", 'color' => "#173177"), //活动名称
            'keyword2' => array('value' => "{$time}", 'color' => "#173177"), //活动时间  2017-06-14至2017-06-30
            'keyword3' => array('value' => "{$site}", 'color' => "#173177"), //活动地点
            'keyword4' => array('value' => "{$particulars}", 'color' => "#173177"), //活动详情
            'remark' => array('value' => "更多活动信息请关注官方微信公众号。", 'color' => "#173177")
        );
        $data = json_encode($arr);
        $result = $this->http($url, $data, 'POST', array("Content-type: text/html; charset=utf-8"));
        return $result;
    }

    //砍价成功通知
    public function kjReply($template_id, $hid, $name, $money,$k_money,$msg)
    {
        //return $this->access_token;
        $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token={$this->access_token}";
        $arr['touser'] = $this->openid;
        $arr['template_id'] = $template_id; //模板ID
        $arr['url'] = URL . "/kjlog/index/welcome/hid/{$hid}";
        $arr['topcolor'] = "#7b68ee";
        $arr['data'] = array(
            'first' => array('value' => "好厉害！又有朋友帮你砍了{$k_money}元！", 'color' => "#173177"),
            'keyword1' => array('value' => "{$name}", 'color' => "#173177"), //商品名称
            'keyword2' => array('value' => "￥"."{$money}", 'color' => "#173177"), //现在低价
            'remark' => array('value' => "{$msg}感谢您的参与！", 'color' => "#173177")
        );
        $data = json_encode($arr);
        $result = $this->http($url, $data, 'POST', array("Content-type: text/html; charset=utf-8"));
        return $result;
    }

    //参团通知
    public function ctReply($template_id, $hid, $name, $time, $date)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token={$this->access_token}";
        $arr['touser'] = $this->openid;
        $arr['template_id'] = $template_id; //模板ID
        $arr['url'] = URL . "/kjlog/index/welcome/hid/{$hid}";
        $arr['topcolor'] = "#7b68ee";
        $arr['data'] = array(
            'first' => array('value' => "你好，1位用户成功参团。", 'color' => "#173177"),
            'keyword1' => array('value' => "{$name}", 'color' => "#173177"), //参团用户
            'keyword2' => array('value' => "{$time}", 'color' => "#173177"), //参团时间
            'keyword3' => array('value' => "{$date}", 'color' => "#173177"), //有效期
            'remark' => array('value' => "点击了解详情", 'color' => "#173177")
        );
        $data = json_encode($arr);
        $result = $this->http($url, $data, 'POST', array("Content-type: text/html; charset=utf-8"));
        return $result;
    }

    //支付成功通知
    public function zfReply($template_id, $hid, $order_id, $money, $site, $time, $yzm)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token={$this->access_token}";
        $arr['touser'] = $this->openid;
        $arr['template_id'] = $template_id; //模板ID
        $arr['url'] = URL . "/kjlog/index/welcome/hid/{$hid}";
        $arr['topcolor'] = "#7b68ee";
        $arr['data'] = array(
            'first' => array('value' => "您好，您的微信支付已成功", 'color' => "#173177"),
            'keyword1' => array('value' => "{$order_id}", 'color' => "#173177"), //订单编号
            'keyword2' => array('value' => "{$money}", 'color' => "#173177"), //消费金额
            'keyword3' => array('value' => "{$site}", 'color' => "#173177"), //消费门店
            'keyword4' => array('value' => "{$time}", 'color' => "#173177"), //消费时间
            'remark' => array('value' => "恭喜您支付成功，到店消费验证码为{$yzm}", 'color' => "#e70505")
        );
        $data = json_encode($arr);
        $result = $this->http($url, $data, 'POST', array("Content-type: text/html; charset=utf-8"));
        return $result;
    }

    public function http($url, $params, $method = 'POST', $header = array(), $multi = false)
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