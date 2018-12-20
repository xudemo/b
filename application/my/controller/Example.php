<?php
namespace app\my\controller;

use think\Controller;
use think\Db;
use app\kjlog\model\JsApiPay;
use app\kjlog\model\wechatpay\WxPayUnifiedOrder;
use app\kjlog\model\wechatpay\WxPayApi;
use wechat\WeChat;
use app\kjlog\model\Activity;

class Example extends Controller
{
    public function jsapi($appid = null)
    {
        $users = Db::name('wx_users')->where(['user_name' => $appid])->field('appId,appSecret,mchiD,key')->find();

        $appiD = trim($users['appId']);
        $mchiD = trim($users['mchiD']);
        $key = trim($users['key']);
        $appSecret = trim($users['appSecret']);

        //①、获取用户openid
        $tools = new JsApiPay($appiD, $mchiD, $key, $appSecret);
        $openId = $tools->GetOpenid();

//②、统一下单
        $money = rand(10,500);
        $input = new WxPayUnifiedOrder();
        $input->SetBody('支付');//商品描述
        $input->SetAttach('123');//附加数据
        $input->SetOut_trade_no($appiD . date("YmdHis"));//订单编号
        $input->SetTotal_fee($money);//价格  单位为分
        $input->SetTime_start(date("YmdHis"));//交易起始时间
        $input->SetTime_expire(date("YmdHis", time() + 600));//交易结束时间，让订单好久失效。10分钟后失效
        $input->SetGoods_tag("test");//订单优惠标记  比如一些卡卷
        $input->SetNotify_url(URL."/my/example/notify");//通知地址
        $input->SetTrade_type("JSAPI");//交易类型  公众号支付
        $input->SetOpenid($openId);//当前支付用户
        $WxPayApi = new WxPayApi($appiD, $mchiD, $key, $appSecret);
        $order = $WxPayApi->unifiedOrder($input);
        $jsApiParameters = $tools->GetJsApiParameters($order);
        $this->assign('jsApiParameters', $jsApiParameters);
        return view();
    }
    public function notify()
    {

    }
}