<?php
namespace app\kjlog\controller;

use think\Controller;
use think\Db;
use app\kjlog\model\JsApiPay;
use app\kjlog\model\wechatpay\WxPayUnifiedOrder;
use app\kjlog\model\wechatpay\WxPayApi;
use wechat\WeChat;
use app\kjlog\model\Activity;

class Example extends Controller
{
    function jsapi($id, $hid)
    {
        //用户id查询当前价格
        $member = Db::name('kj_member')->where(['id' => $id])->field('money')->find();
        //活动id查询appid
        $activity = Db::name('kj_activity')->where(['id' => $hid])->field('appid,pay_add,name,code')->find();
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
            $money = $member['money'] * 100;
        }
//①、获取用户openid
        $tools = new JsApiPay($appiD, $mchiD, $key, $appSecret);
        $openId = $tools->GetOpenid();
//②、统一下单
        $input = new WxPayUnifiedOrder();
        $input->SetBody($activity['name']);//商品描述
        $input->SetAttach($id);//附加数据
        $input->SetOut_trade_no($appiD . date("YmdHis"));//订单编号
    //    $input->SetTotal_fee($member['money'] * 100);//价格  单位为分
        $input->SetTotal_fee($money);//价格  单位为分
        $input->SetTime_start(date("YmdHis"));//交易起始时间
        $input->SetTime_expire(date("YmdHis", time() + 600));//交易结束时间，让订单好久失效。10分钟后失效
        $input->SetGoods_tag("test");//订单优惠标记  比如一些卡卷
        $input->SetNotify_url(URL."/kjlog/example/notify");//通知地址
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
        libxml_disable_entity_loader(true);
        $val = json_decode(json_encode(simplexml_load_string($xmls, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
         
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
            $member = Db::name('kj_member')->where(['id' => $id])->field('pay,hid,shop_site')->find();
        //  file_put_contents('log.txt','用户支付状态'.$member['pay'],FILE_APPEND);
            if ($member['pay'] == 0) {
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
                $order = Db::name('kj_orders')->insert($data); 
                //修改支付状态
                $yzm = mt_rand(100000, 999999);
                $m = Db::name('kj_member')->where(['id' => $id])->update(['pay' => 1, 'yzm' => $yzm]);    
                //发送支付成功通知
                $activity = Db::name('kj_activity')->where(['id'=>$member['hid']])->field('finish_time,appid')->find();
                $a =  Activity::get_zfId($activity['appid'],$openid,$member['hid'],$transaction_id,$total_fee,$member['shop_site'],$activity['finish_time'],$yzm);
                 // file_put_contents('log.txt',$a."        ",FILE_APPEND);
            }
        }
    }
    public function http_post($url, $data_string) {

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 信任任何证书
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string))
        );
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            print curl_error($ch);
        }
        curl_close($ch);
        //echo $result;
    }
    public function reply()
    {
        $appid = 'wx445319502570068b';
        $appSecret = '707a144bf1f55ecd4887fa4708336506';
        $openid = 'oGR1N1WiFHkzxOkaVcYN1o2DTNAI';
        $weChat = new WeChat();
        $access_token = $weChat->getToken($appid,$appSecret);
        $access_token = $access_token['access_token'];
        //获取设置的行业信息
        $url = "https://api.weixin.qq.com/cgi-bin/template/get_industry?access_token={$access_token}";
        $h = file_get_contents($url);
        dump($h);

        //获取模板id
        $url = "https://api.weixin.qq.com/cgi-bin/template/api_add_template?access_token={$access_token}";
        $d['template_id_short'] = 'TM';
        $data = $this->http($url, $d,'POST',array("Content-type: text/html; charset=utf-8"));
        dump($data);

        $url = "https://api.weixin.qq.com/cgi-bin/template/api_set_industry?access_token={$access_token}";
        $u = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token={$access_token}";
        $arr['touser'] = $openid;
        $a = 'kY03cIubfg86ybmrw9L1UpNoMNOp9OUoNpMHN5Od5dA';
        $arr['template_id'] = 'mjkuSMWkykt-m_UiHtTDO5AeRsgoRCJNm9DCl2AtQZA';
        $arr['url'] = 'http://www.znote.com.cn/kjlog/index/welcome/hid/10';
        $arr['topcolor'] = "#7b68ee";
        $arr['data'] = array(
            'first'=>array('value'=>"标题",'color'=>"#173177"),
            'keyword1'=>array('value'=>"消息",'color'=>"#e70505"),
            'keyword2'=>array('value'=>date('y-m-d h:i:s',time()),'color'=>"#173177"),
            'keyword3'=>array('value'=>"2019",'color'=>"#173177"),
            'keyword4'=>array('value'=>"2019",'color'=>"#173177"),
            'remark'=>array('value'=>"2019",'color'=>"#173177")
        );
        $m = json_encode($arr);
        dump($m);
        $html = $this->http($u, $m,'POST',array("Content-type: text/html; charset=utf-8"));
        dump($html);
    }
    function http($url, $params, $method = 'POST', $header = array(), $multi = false)
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
                throw new Exception('不支持的请求方式！');
        }
        /* 初始化并执行curl请求 */
        $ch = curl_init();
        curl_setopt_array($ch, $opts);
        $data  = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if($error) throw new Exception('请求发生错误：' . $error);
        return  $data;
    }
}