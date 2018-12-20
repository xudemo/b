<?php
namespace app\packet\controller;

use think\Controller;
use think\Db;
use app\kjlog\model\JsApiPay;
use app\kjlog\model\wechatpay\WxPayUnifiedOrder;
use app\kjlog\model\wechatpay\WxPayApi;
use wechat\WeChat;
use app\kjlog\model\Activity;
use app\kjlog\model\Exception;
use app\packet\model\Msg;
use app\packet\model\HongBao;

class Example extends Controller
{
    function jsapi($id, $hid)
    {
        //活动id查询appid
        $activity = Db::name('hb_activity')->where(['id' => $hid])->field('appid,pay_add,name,code,money')->find();
        //appid查询支付信息
        $users = Db::name('wx_users')->where(['user_name' => $activity['appid']])->field('appId,appSecret,mchiD,key')->find();
        //我们平台自己支付信息
        $appiD = 'wx445319502570068b';
        $mchiD = '1508102401';
        $key = 'iieuruieurieuirkljdfkljaiodf1231';
        $appSecret = '707a144bf1f55ecd4887fa4708336506';
        //客服公众号支付信息
        if ($activity['pay_add'] == 0) {
            $appiD = trim($users['appId']);
            $mchiD = trim($users['mchiD']);
            $key = trim($users['key']);
            $appSecret = trim($users['appSecret']);
        }
        if($activity['code'] == 1){
            $money = 1;
        }else{
            $money = $activity['money'] * 100;
        }
//①、获取用户openid
        $tools = new JsApiPay($appiD, $mchiD, $key, $appSecret);
        $openId = $tools->GetOpenid();
//②、统一下单
        $input = new WxPayUnifiedOrder();
        $input->SetBody($activity['name']);//商品描述
        $input->SetAttach($id);//附加数据
        $input->SetOut_trade_no($appiD . date("YmdHis"));//订单编号
        $input->SetTotal_fee($money);//价格  单位为分
        $input->SetTime_start(date("YmdHis"));//交易起始时间
        $input->SetTime_expire(date("YmdHis", time() + 600));//交易结束时间，让订单好久失效。10分钟后失效
        $input->SetGoods_tag("test");//订单优惠标记  比如一些卡卷
        $input->SetNotify_url(URL."/packet/example/notify");//通知地址
        $input->SetTrade_type("JSAPI");//交易类型  公众号支付
        $input->SetOpenid($openId);//当前支付用户
        $WxPayApi = new WxPayApi($appiD, $mchiD, $key, $appSecret);
        $order = $WxPayApi->unifiedOrder($input);
        $jsApiParameters = $tools->GetJsApiParameters($order);
        $this->assign('jsApiParameters', $jsApiParameters);
        $this->assign('hid', $hid);
        return view();
    }

    //支付回调
    public function notify()
    {
        $xmls = file_get_contents("php://input");
       // libxml_disable_entity_loader(true);
        $val = json_decode(json_encode(simplexml_load_string($xmls, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        // file_put_contents('packet.txt',$val,FILE_APPEND);
        $appid = $val['appid'];//公众号appid
        $result_code = $val['result_code'];//业务结果
        $return_code = $val['return_code'];//业务结果
        $transaction_id = $val['transaction_id'];//微信支付订单号
        $out_trade_no = $val['out_trade_no'];//商户订单号
        $total_fee = $val['total_fee'] / 100;//订单总金额，单位为分
        $time_end = $val['time_end'];//支付完成时间
        $openid = $val['openid'];//用户标识
        $id = $val['attach'];//商家数据包，原样返回  member表用户id
        //判断是否支付成功
        if ($return_code == "SUCCESS" && $result_code == "SUCCESS") {
            $member = Db::name('hb_member')->where(['id'=>$id])->find();
            if($member['pay'] == 0){
                $data['money'] = $total_fee;
                $data['hid'] = $member['hid'];
                $data['uid'] = $id;
                $data['appid'] = $appid;
                $data['openid'] = $openid;
                $data['transaction_id'] = $transaction_id;
                $data['out_trade_no'] = $out_trade_no;
                $data['time_end'] = $time_end;
                $data['time'] = date('Y-m-d H:i:s');
                //生成订单表
                $order = Db::name('hb_orders')->insert($data);
                //修改支付状态
                $yzm = mt_rand(100000, 999999);
                $list['pay'] = 1;
                $list['yzm'] = $yzm;
                $m = Db::name('hb_member')->where(['id' => $id])->update($list);
                //发送支付成功通知
                // Msg::get_zfMsg($member['hid'],$openid,$transaction_id,$total_fee,$yzm,$member['shop_site']);
                //发送红包
                if(!empty($member['sid'])){
                    $activity = Db::name('hb_activity')->where(['id' => $member['hid']])->field('no_master,no_pay,appid,hb_name,hb_intro')->find();
                    $me = Db::name('hb_member')->where(['id' => $member['sid']])->field('pay,openid,state')->find();
                    if($activity['no_pay'] == 0 || $activity['no_pay'] == 1 && $me['pay'] == 1){
                       	if($me['state'] == 2 && $activity['no_master'] == 1){
                            $str='<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg><sign><![CDATA[签名]]></sign></xml>';
                            echo $str;
                            exit;
                        }
						if($me['pay'] == 0){
                                $rand = $this->noRandom();
                            }else{
                                $rand = $this->random();
                            }
                        $hb = new HongBao();
                        $result = $hb->setHongBao($transaction_id,$activity['appid'],$activity['hb_name'],$activity['hb_intro'],$me['openid'],$rand);
                        if($result['result_code'] == "SUCCESS" && $result['return_code'] == "SUCCESS"){
                            $d['uid'] = $member['id'];
                            $d['sid'] = $member['sid'];
                            $d['hid'] = $member['hid'];
                            $d['integral'] = $rand;
                            $d['use'] = 0;
							$d['transactionId'] = $transaction_id;
                            Db::name('hb_integral')->insert($d);
                            Db::name('hb_member')
                                ->query("update zn_hb_member set new_integral=new_integral+{$rand} where id={$member['sid']}");
                        }
                    }
                    //发送积分通知
                    //file_put_contents('packet.txt',$a,FILE_APPEND);
                    // $me = Db::name('hb_member')->where(['id'=>$member['sid']])->field('openid')->find();
                    //$a = Msg::get_packetMsg($member['hid'],$me['openid'],$member['nickname'],$id);
                    //file_put_contents('packet.txt',$a,FILE_APPEND);
                }
            }
			$str='<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg><sign><![CDATA[签名]]></sign></xml>';
            echo $str;
        }
    }
    public function random()
    {
        //首先定义概率数组
        $Probability["5-9"] = 0.8;
        $Probability["9-10"] = 0.2;
        //扩大1000倍便于计算
        foreach($Probability as $k => $v){
            $Probability[$k] = $v*1000;
        }
        $Num = 0;
        $Random = 5 + mt_rand() / mt_getrandmax() * (10 - 5);
        foreach($Probability as $k => $v){
            if($Num < $Random && $Random <= $v+$Num){
                //进入这里表示随机数在哪一个范围内
                $Range = explode("-", $k);
                //生成范围区间的随机数
                $Result = $Range[0] + mt_rand() / mt_getrandmax() * ($Range[1] - $Range[0]);
                $result = sprintf("%.2f", $Result);
                return $result;
                break;
            }else{
                $Num += $v;
            }
        }
    }
	
	public function noRandom()
    {
        //首先定义概率数组
        $Probability["1-4"] = 0.7;
        $Probability["4-5"] = 0.3;
        //扩大1000倍便于计算
        foreach($Probability as $k => $v){
            $Probability[$k] = $v*1000;
        }
        $Num = 0;
        $Random = 1 + mt_rand() / mt_getrandmax() * (5 - 1);
        foreach($Probability as $k => $v){
            if($Num < $Random && $Random <= $v+$Num){
                //进入这里表示随机数在哪一个范围内
                $Range = explode("-", $k);
                //生成范围区间的随机数
                $Result = $Range[0] + mt_rand() / mt_getrandmax() * ($Range[1] - $Range[0]);
                $result = sprintf("%.2f", $Result);
                return $result;
                break;
            }else{
                $Num += $v;
            }
        }
    }
    public function reply()
    {
        $a = Msg::get_zfMsg(3,'oGR1N1WiFHkzxOkaVcYN1o2DTNAI',123,0.01,621991,"活动门店");
        dump($a);
    }
}