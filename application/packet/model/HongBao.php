<?php
namespace app\packet\model;

use think\Db;
use think\facade\App;

class HongBao
{
    function setHongBao($mch_billno,$appid,$send_name,$wishing,$re_openid,$money)
    {
        //$appid公众号原始ID   appId服务器配置id
        $user = Db::name('wx_users')->where(['user_name'=>$appid])->field('appId,mchiD,key')->find();
        $arr['nonce_str'] = 'xydemo'; //随机字符串
        $arr['mch_billno'] = trim($mch_billno);  //商户订单号
        $arr['mch_id'] = trim($user['mchiD']); //商户号
        $arr['wxappid'] = trim($user['appId']);  //公众号appid
        $arr['send_name'] = $send_name;
        $arr['re_openid'] = trim($re_openid); //用户openid
        $arr['total_amount'] = $money*100;  //金额
        $arr['total_num'] = 1;  //人数
        $arr['wishing'] = $wishing;  //详情
        $arr['client_ip'] = '119.29.234.211';  //ip
        $arr['act_name'] = $send_name;
        $arr['remark'] = '快来抢！';
        $arr['scene_id'] = '';
        $arr['risk_info'] = '';
        $arr['consume_mch_id'] = '';
//        ksort($arr);
//        $c = http_build_query($arr);
//        $c .= $c . '&key=iieuruieurieuirkljdfkljaiodf1231';
//        $sign = md5($c); //注：MD5签名方式
        $key = trim($user['key']);
        $sign = $this->MakeSign($arr,$key);

        $data = "<xml>
<sign><![CDATA[%s]]></sign>
<mch_billno><![CDATA[%s]]></mch_billno>
<mch_id><![CDATA[%s]]></mch_id>
<wxappid><![CDATA[%s]]></wxappid>
<send_name><![CDATA[%s]]></send_name>
<re_openid><![CDATA[%s]]></re_openid>
<total_amount><![CDATA[%s]]></total_amount>
<total_num><![CDATA[1]]></total_num>
<wishing><![CDATA[%s]]></wishing>
<client_ip><![CDATA[%s]]></client_ip>
<act_name><![CDATA[%s]]></act_name>
<remark><![CDATA[%s]]></remark>
<scene_id><![CDATA[%s]]></scene_id>
<consume_mch_id><![CDATA[%s]]></consume_mch_id>
<nonce_str><![CDATA[%s]]></nonce_str>
<risk_info>%s</risk_info></xml>";

        $result = sprintf($data, $sign, $arr['mch_billno'], $arr['mch_id'], $arr['wxappid'], $arr['send_name'], $arr['re_openid'], $arr['total_amount'], $arr['wishing'], $arr['client_ip'], $arr['act_name'], $arr['remark'], $arr['scene_id'],$arr['consume_mch_id'], $arr['nonce_str'],$arr['risk_info']);
        $url = "https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack";
        $result = $this->http($url,$result,$appid);
		//file_put_contents('hb.txt',$result,FILE_APPEND);
        $hb = json_decode(json_encode(simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
		if($hb['result_code'] != "SUCCESS" || $hb['return_code'] != "SUCCESS"){
            file_put_contents('hb.txt','失败时间：'.date("Y-m-d H:i:s").$result,FILE_APPEND);
        }
        return $hb;
    }

	function getHongBao($hid, $appid,$type)
    {
        $url = "https://api.mch.weixin.qq.com/mmpaymkttransfers/gethbinfo";
        //$appid公众号原始ID   appId服务器配置id
        $user = Db::name('wx_users')->where(['user_name' => $appid])->field('appId,mchiD,key')->find();
        //查询该获得的所有积分
        if($type == 'hb'){
            $integral = Db::name('hb_integral')->where(['hid' => $hid])->select();
        }else{
            return false;
        }
        $data['nonce_str'] = 'xydemo'; //随机字符串
        $data['mch_id'] = trim($user['mchiD']); //商户号
        $data['appid'] = trim($user['appId']); //Appid
        $data['bill_type'] = 'MCHT'; //Appid
        for ($i = 0; $i < count($integral); $i++) {
            if (!empty($integral[$i]['transactionId'])) {
                $data['mch_billno'] = $integral[$i]['transactionId']; //商户订单号
                $key = trim($user['key']);
                $sign = $this->MakeSign($data, $key);
                $xml = "<xml>
                            <sign><![CDATA[%s]]></sign>
                            <mch_billno><![CDATA[%s]]></mch_billno>
                            <mch_id><![CDATA[%s]]></mch_id>
                            <appid><![CDATA[%s]]></appid>
                            <bill_type><![CDATA[MCHT]]></bill_type> 
                            <nonce_str><![CDATA[%s]]></nonce_str>
                        </xml>";
                $xml = sprintf($xml,$sign,$data['mch_billno'],$data['mch_id'],$data['appid'],$data['nonce_str']);
                $result = $this->http($url,$xml,$appid);
				$result = json_decode(json_encode(simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
                if($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS'){
                    $status = 6; //未知
                    if($result['status'] == 'SENDING'){
                        $status = 0;//发放中
                    }
                    if($result['status'] == 'SENT'){
                        $status = 1;//已发放待领取
                    }
                    if($result['status'] == 'FAILED'){
                        $status = 2;//发放失败
                    }
                    if($result['status'] == 'RECEIVED'){
                        $status = 3;//已领取
                    }
                    if($result['status'] == 'RFUND_ING'){
                        $status = 4;//退款中
                    }
                    if($result['status'] == 'REFUND'){
                        $status = 5;//已退款
                    }
                    if($type == 'hb'){
                        Db::name('hb_integral')
                            ->where(['transactionId'=>$integral[$i]['transactionId']])
                            ->update(['use'=>$status]);
                    }else{
                        return false;
                        exit;
                    }
                }else{
                    $status = 6;  //未知
                    if($type == 'hb'){
                        Db::name('hb_integral')
                            ->where(['transactionId'=>$integral[$i]['transactionId']])
                            ->update(['use'=>$status]);
                    }else{
                        return false;
                        exit;
                    }
                }
            }
        }
        return true;
    }

    public function MakeSign($arr,$key)
    {
        //签名步骤一：按字典序排序参数
        ksort($arr);
        $string = $this->ToUrlParams($arr);
        //签名步骤二：在string后加入KEY
        $string = $string . "&key={$key}";
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }
    /**
     * 格式化参数格式化成url参数
     */
    public function ToUrlParams($arr)
    {
        $buff = "";
        foreach ($arr as $k => $v)
        {
            if($k != "sign" && $v != "" && !is_array($v)){
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }

    function http($url, $params,$appid, $method = 'POST', $header = array("Content-type: text/xml; charset=utf-8"), $multi = false,$useCert=true)
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
        if($useCert == true){
            //设置证书
            //使用证书：cert 与 key 分别属于两个.pem文件
            curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
            curl_setopt($ch, CURLOPT_SSLCERT, App::getRootPath().'public'.DIRECTORY_SEPARATOR.'cert'.DIRECTORY_SEPARATOR.$appid.'_apiclient_cert.pem');
            curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLKEY, App::getRootPath().'public'.DIRECTORY_SEPARATOR.'cert'.DIRECTORY_SEPARATOR.$appid.'_apiclient_key.pem');
        }
        // return DIRECTORY_SEPARATOR;
        curl_setopt_array($ch, $opts);
        $data  = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if($error) throw new \Exception('请求发生错误：' . $error);
        return  $data;
    }
}