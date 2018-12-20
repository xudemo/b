<?php
namespace app\kjlog\model;

use think\Db;
use think\Model;
use wechat\WeChat;
use app\kjlog\model\WxPayException;

class WeChatPay
{
//基本信息
    //APPID：绑定支付的APPID（必须配置，开户邮件中可查看）
    //MCHID：商户号（必须配置，开户邮件中可查看）
    // KEY：商户支付密钥，参考开户邮件设置（必须配置，登录商户平台自行设置）, 请妥善保管， 避免密钥泄露 设置地址：https://pay.weixin.qq.com/index.php/account/api_cert
    //APPSECRET：公众帐号secert（仅JSAPI支付的时候需要配置， 登录公众平台，进入开发者中心可设置）， 请妥善保管， 避免密钥泄露
    public $appiD;
    public $mchiD;
    public $key;
    public $appSecret;
//证书路径
    public $SSLCERT_PATH = 'D:\www\data\cert\apiclient_cert.pem';
    public $SSLKEY_PATH = 'D:\www\data\cert\apiclient_key.pem';
//curl代理设置
    public $CURL_PROXY_HOST = "0.0.0.0";
    public $CURL_PROXY_PORT = 8080;
    public $REPORT_LEVENL = 1;

    protected $values = array();
    public function __construct($appid,$mchid,$key,$appSecret)
    {
        $this->appiD=$appid;
        $this->mchiD=$mchid;
        $this->key=$key;
        $this->appSecret=$appSecret;
    }
//④获取jsapi支付的参数
    public function GetJsApiParameters($UnifiedOrderResult)
    {
        if(!array_key_exists("appid", $UnifiedOrderResult)
            || !array_key_exists("prepay_id", $UnifiedOrderResult)
            || $UnifiedOrderResult['prepay_id'] == "")
        {
            throw new WxPayException("参数错误");
        }
        $this->SetAppid($UnifiedOrderResult["appid"]);
        $timeStamp = time();
        $this->SetTimeStamp("$timeStamp");
        $this->SetNonceStr($this->getNonceStr());
        $this->SetPackage("prepay_id=" . $UnifiedOrderResult['prepay_id']);
        $this->SetSignType("MD5");
        $this->SetPaySign($this->MakeSign());
        $parameters = json_encode($this->GetValues());
        return $parameters;
    }
    /**
     * 设置签名方式**/
    public function SetPaySign($value)
    {
        $this->values['paySign'] = $value;
    }
    /**设置签名方式**/
    public function SetSignType($value)
    {
        $this->values['signType'] = $value;
    }
    /**设置订单详情扩展字符串**/
    public function SetPackage($value)
    {
        $this->values['package'] = $value;
    }
    /**随机字符串**/
    public function SetNonceStr($value)
    {
        $this->values['nonceStr'] = $value;
    }
    /**设置支付时间戳**/
    public function SetTimeStamp($value)
    {
        $this->values['timeStamp'] = $value;
    }

//③同意下单
    public function unifiedOrder($timeOut = 6)
    {
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        //检测必填参数
        if(!$this->IsOut_trade_noSet()) {
            throw new WxPayException("缺少统一支付接口必填参数out_trade_no！");
        }else if(!$this->IsBodySet()){
            throw new WxPayException("缺少统一支付接口必填参数body！");
        }else if(!$this->IsTotal_feeSet()) {
            throw new WxPayException("缺少统一支付接口必填参数total_fee！");
        }else if(!$this->IsTrade_typeSet()) {
            throw new WxPayException("缺少统一支付接口必填参数trade_type！");
        }
        //关联参数
        if($this->GetTrade_type() == "JSAPI" && !$this->IsOpenidSet()){
            throw new WxPayException("统一支付接口中，缺少必填参数openid！trade_type为JSAPI时，openid为必填参数！");
        }
        if($this->GetTrade_type() == "NATIVE" && !$this->IsProduct_idSet()){
            throw new WxPayException("统一支付接口中，缺少必填参数product_id！trade_type为JSAPI时，product_id为必填参数！");
        }

        //异步通知url未设置，则使用配置文件中的url
        if(!$this->IsNotify_urlSet()){
            $this->SetNotify_url($this->NOTIFY_URL);//异步通知url
        }

        $this->SetAppid($this->appiD);//公众账号ID
        $this->SetMch_id($this->mchiD);//商户号
        $this->SetSpbill_create_ip($_SERVER['REMOTE_ADDR']);//终端ip
        $this->SetNonce_str(self::getNonceStr());//随机字符串
        //签名
        $this->SetSign();
        $xml = $this->ToXml();
        //file_put_contents("xml.txt", $xml);
        $startTimeStamp = self::getMillisecond();//请求开始时间
        $response = self::postXmlCurl($xml, $url, false, $timeOut);
        $result = $this->Init($response);
        self::reportCostTime($url, $startTimeStamp, $result);//上报请求花费时间
        return $result;
    }
    /*上报数据， 上报的时候将屏蔽所有异常流程*/
    public function reportCostTime($url, $startTimeStamp, $data)
    {
        //如果不需要上报数据
        if($this->REPORT_LEVENL == 0){
            return;
        }
        //如果仅失败上报
        if($this->REPORT_LEVENL == 1 &&
            array_key_exists("return_code", $data) &&
            $data["return_code"] == "SUCCESS" &&
            array_key_exists("result_code", $data) &&
            $data["result_code"] == "SUCCESS")
        {
            return;
        }
        //上报逻辑
        $endTimeStamp = self::getMillisecond();
        $this->SetInterface_url($url);
        $this->SetExecute_time_($endTimeStamp - $startTimeStamp);
        //返回状态码
        if(array_key_exists("return_code", $data)){
            $this->SetReturn_code($data["return_code"]);
        }
        //返回信息
        if(array_key_exists("return_msg", $data)){
            $this->SetReturn_msg($data["return_msg"]);
        }
        //业务结果
        if(array_key_exists("result_code", $data)){
            $this->SetResult_code($data["result_code"]);
        }
        //错误代码
        if(array_key_exists("err_code", $data)){
            $this->SetErr_code($data["err_code"]);
        }
        //错误代码描述
        if(array_key_exists("err_code_des", $data)){
            $this->SetErr_code_des($data["err_code_des"]);
        }
        //商户订单号
        if(array_key_exists("out_trade_no", $data)){
            $this->SetOut_trade_no($data["out_trade_no"]);
        }
        //设备号
        if(array_key_exists("device_info", $data)){
            $this->SetDevice_info($data["device_info"]);
        }

        try{
            self::report();
        } catch (WxPayException $e){
            //不做任何处理
        }
    }
    public function report($timeOut = 1)
    {
        $url = "https://api.mch.weixin.qq.com/payitil/report";
        //检测必填参数
        if(!$this->IsInterface_urlSet()) {
            throw new WxPayException("接口URL，缺少必填参数interface_url！");
        } if(!$this->IsReturn_codeSet()) {
        throw new WxPayException("返回状态码，缺少必填参数return_code！");
    } if(!$this->IsResult_codeSet()) {
        throw new WxPayException("业务结果，缺少必填参数result_code！");
    } if(!$this->IsUser_ipSet()) {
        throw new WxPayException("访问接口IP，缺少必填参数user_ip！");
    } if(!$this->IsExecute_time_Set()) {
        throw new WxPayException("接口耗时，缺少必填参数execute_time_！");
    }
        $this->SetAppid($this->appiD);//公众账号ID
        $this->SetMch_id($this->mchiD);//商户号
        $this->SetUser_ip($_SERVER['REMOTE_ADDR']);//终端ip
        $this->SetTime(date("YmdHis"));//商户上报时间
        $this->SetNonce_str(self::getNonceStr());//随机字符串

        $this->SetSign();//签名
        $xml = $this->ToXml();

        $startTimeStamp = self::getMillisecond();//请求开始时间
        $response = self::postXmlCurl($xml, $url, false, $timeOut);
        return $response;
    }
    /*设置系统时间**/
    public function SetTime($value)
    {
        $this->values['time'] = $value;
    }
    /**设置发起接口调用时的机器IP**/
    public function SetUser_ip($value)
    {
        $this->values['user_ip'] = $value;
    }
    /**判断接口耗时情况，单位为毫秒是否存在**/
    public function IsExecute_time_Set()
    {
        return array_key_exists('execute_time_', $this->values);
    }
    /**判断发起接口调用时的机器IP 是否存在**/
    public function IsUser_ipSet()
    {
        return array_key_exists('user_ip', $this->values);
    }
    /**判断SUCCESS/FAIL是否存在**/
    public function IsResult_codeSet()
    {
        return array_key_exists('result_code', $this->values);
    }
    /**判断SUCCESS/FAIL此字段是通信标识，非交易标识，交易是否成功需要查看trade_state来判断是否存在**/
    public function IsReturn_codeSet()
    {
        return array_key_exists('return_code', $this->values);
    }
    /*判断上报对应的接口的完整URL**/
    public function IsInterface_urlSet()
    {
        return array_key_exists('interface_url', $this->values);
    }
    /**设置微信支付分配的终端设备号，商户自定义**/
    public function SetDevice_info($value)
    {
        $this->values['device_info'] = $value;
    }
    /**设置结果信息描述**/
    public function SetErr_code_des($value)
    {
        $this->values['err_code_des'] = $value;
    }
    /**设置ORDERNOTEXIST—订单不存在SYSTEMERROR—系统错误**/
    public function SetErr_code($value)
    {
        $this->values['err_code'] = $value;
    }
    /**设置SUCCESS/FAIL**/
    public function SetResult_code($value)
    {
        $this->values['result_code'] = $value;
    }
    /**设置返回信息，如非空，为错误原因签名失败参数格式校验错误**/
    public function SetReturn_msg($value)
    {
        $this->values['return_msg'] = $value;
    }
    /*设置SUCCESS/FAIL此字段是通信标识，非交易标识，交易是否成功需要查看trade_state来判断**/
    public function SetReturn_code($value)
    {
        $this->values['return_code'] = $value;
    }
    /*设置接口耗时情况，单位为毫秒*/
    public function SetExecute_time_($value)
    {
        $this->values['execute_time_'] = $value;
    }
    /*设置上报对应的接口的完整URL**/
    public function SetInterface_url($value)
    {
        $this->values['interface_url'] = $value;
    }
    /*将xml转为array*/
    public function Init($xml)
    {
        $this->FromXml($xml);
        //fix bug 2015-06-29
        if($this->values['return_code'] != 'SUCCESS'){
            return $this->GetValues();
        }
        $this->CheckSign();
        return $this->GetValues();
    }
    /*检测签名*/
    public function CheckSign()
    {
        //fix异常
        if(!$this->IsSignSet()){
            throw new WxPayException("签名错误！");
        }

        $sign = $this->MakeSign();
        if($this->GetSign() == $sign){
            return true;
        }
        throw new WxPayException("签名错误！");
    }
    /*获取签名，详见签名生成算法的值**/
    public function GetSign()
    {
        return $this->values['sign'];
    }
    /*判断签名，详见签名生成算法是否存在**/
    public function IsSignSet()
    {
        return array_key_exists('sign', $this->values);
    }
    /*获取设置的值*/
    public function GetValues()
    {
        return $this->values;
    }
    /*将xml转为array*/
    public function FromXml($xml)
    {
        if(!$xml){
            throw new WxPayException("xml数据异常！");
        }
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $this->values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $this->values;
    }
    /**以post方式提交xml到对应的接口url $xml需要post的xml数据,$url url,$useCert是否需要证书，默认不需要,$second   url执行超时时间，默认30s*/
    public function postXmlCurl($xml, $url, $useCert = false, $second = 30)
    {
        $ch = curl_init();
        $curlVersion = curl_version();
        $ua = "WXPaySDK/0.9 (".PHP_OS.") PHP/".PHP_VERSION." CURL/".$curlVersion['version']." ".$this->mchiD;

        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);

        //如果有配置代理这里就设置代理
        if($this->CURL_PROXY_HOST != "0.0.0.0"
            && $this->CURL_PROXY_PORT != 0){
            curl_setopt($ch,CURLOPT_PROXY, $this->CURL_PROXY_HOST);
            curl_setopt($ch,CURLOPT_PROXYPORT, $this->CURL_PROXY_PORT);
        }
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);//严格校验
        curl_setopt($ch,CURLOPT_USERAGENT, $ua);
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        if($useCert == true){
            //设置证书
            //使用证书：cert 与 key 分别属于两个.pem文件
            //证书文件请放入服务器的非web目录下
            curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLCERT, $this->SSLCERT_PATH);
            curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLKEY, $this->SSLKEY_PATH);
        }
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        //返回结果
        if($data){
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            throw new WxPayException("curl出错，错误码:$error");
        }
    }
    /*获取毫秒级别的时间戳*/
    public function getMillisecond()
    {
        //获取毫秒的时间戳
        $time = explode ( " ", microtime () );
        $time = $time[1] . ($time[0] * 1000);
        $time2 = explode( ".", $time );
        $time = $time2[0];
        return $time;
    }
    /**输出xml字符**/
    public function ToXml()
    {
        if(!is_array($this->values)
            || count($this->values) <= 0)
        {
            throw new WxPayException("数组数据异常！");
        }

        $xml = "<xml>";
        foreach ($this->values as $key=>$val)
        {
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }
    /**设置签名，详见签名生成算法**/
    public function SetSign()
    {
        $sign = $this->MakeSign();
        $this->values['sign'] = $sign;
        return $sign;
    }
    /*生成签名*/
    public function MakeSign()
    {
        //签名步骤一：按字典序排序参数
        ksort($this->values);
        $string = $this->ToUrlParams();
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=".$this->key;
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }
    /*格式化参数格式化成url参数*/
    public function ToUrlParams()
    {
        $buff = "";
        foreach ($this->values as $k => $v)
        {
            if($k != "sign" && $v != "" && !is_array($v)){
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }
    /*产生随机字符串，不长于32位*/
    public static function getNonceStr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  {
            $str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);
        }
        return $str;
    }
    /*设置随机字符串，不长于32位。推荐随机数生成算法*/
    public function SetNonce_str($value)
    {
        $this->values['nonce_str'] = $value;
    }
    /*设置APP和网页支付提交用户端ip，Native支付填调用微信支付API的机器IP。*/
    public function SetSpbill_create_ip($value)
    {
        $this->values['spbill_create_ip'] = $value;
    }
    /*设置微信支付分配的商户号*/
    public function SetMch_id($value)
    {
        $this->values['mch_id'] = $value;
    }
    /*设置微信分配的公众账号ID*/
    public function SetAppid($value)
    {
        $this->values['appid'] = $value;
    }
    /*判断接收微信支付异步通知回调地址是否存在*/
    public function IsNotify_urlSet()
    {
        return array_key_exists('notify_url', $this->values);
    }
    /*判断trade_type=NATIVE，此参数必传。此id为二维码中包含的商品ID，商户自行定义。是否存在*/
    public function IsProduct_idSet()
    {
        return array_key_exists('product_id', $this->values);
    }
    /*判断trade_type=JSAPI，此参数必传，用户在商户appid下的唯一标识。下单前需要调用【网页授权获取用户信息】接口获取到用户的Openid。 是否存在*/
    public function IsOpenidSet()
    {
        return array_key_exists('openid', $this->values);
    }
    /*获取取值如下：JSAPI，NATIVE，APP，详细说明见参数规定的值*/
    public function GetTrade_type()
    {
        return $this->values['trade_type'];
    }
    /*判断取值如下：JSAPI，NATIVE，APP，详细说明见参数规定是否存在*/
    public function IsTrade_typeSet()
    {
        return array_key_exists('trade_type', $this->values);
    }
    /*判断订单总金额，只能为整数，详见支付金额是否存在*/
    public function IsTotal_feeSet()
    {
        return array_key_exists('total_fee', $this->values);
    }
    /*判断商户系统内部的订单号,32个字符内、可包含字母, 其他说明见商户订单号是否存在*/
    public function IsOut_trade_noSet()
    {
        return array_key_exists('out_trade_no', $this->values);
    }
    /*判断商品或支付单简要描述是否存在*/
    public function IsBodySet()
    {
        return array_key_exists('body', $this->values);
    }
//②设置相关参数
    /*设置商品或支付单简要描述*/
    public function SetBody($value)
    {
        $this->values['body'] = $value;
    }
    /*设置附加数据，在查询API和支付通知中原样返回，该字段主要用于商户携带订单的自定义数据*/
    public function SetAttach($value)
    {
        $this->values['attach'] = $value;
    }
    /*设置商户系统内部的订单号,32个字符内、可包含字母, 其他说明见商户订单号*/
    public function SetOut_trade_no($value)
    {
        $this->values['out_trade_no'] = $value;
    }
    /*设置订单总金额，只能为整数，详见支付金额*/
    public function SetTotal_fee($value)
    {
        $this->values['total_fee'] = $value;
    }
    /*设置订单生成时间，格式为yyyyMMddHHmmss，如2009年12月25日9点10分10秒表示为20091225091010*/
    public function SetTime_start($value)
    {
        $this->values['time_start'] = $value;
    }
    /*设置订单失效时间，格式为yyyyMMddHHmmss，如2009年12月27日9点10分10秒表示为20091227091010*/
    public function SetTime_expire($value)
    {
        $this->values['time_expire'] = $value;
    }
    /*设置商品标记，代金券或立减优惠功能的参数，说明详见代金券或立减优惠*/
    public function SetGoods_tag($value)
    {
        $this->values['goods_tag'] = $value;
    }
    /*设置接收微信支付异步通知回调地址*/
    public function SetNotify_url($value)
    {
        $this->values['notify_url'] = $value;
    }
    /*设置取值如下：JSAPI，NATIVE，APP，*/
    public function SetTrade_type($value)
    {
        $this->values['trade_type'] = $value;
    }
    /*设置trade_type=JSAPI，此参数必传，用户在商户appid下的唯一标识。下单前需要调用【网页授权获取用户信息】接口获取到用户的Openid。*/
    public function SetOpenid($value)
    {
        $this->values['openid'] = $value;
    }
//①获取openid
    public function GetOpenid()
    {
        //通过code获得openid
        if (!isset($_GET['code'])) {
            //触发微信返回code码
            $baseUrl = urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . $_SERVER['QUERY_STRING']);
            $url = $this->__CreateOauthUrlForCode($baseUrl);
            header("Location: $url");
            exit();
        } else {
            //获取code码，以获取openid
            $code = $_GET['code'];
            $openid = $this->getOpenidFromMp($code);
            return $openid;
        }
    }
    public function getOpenidFromMp($code)
    {
        $url = $this->__CreateOauthUrlForOpenid($code);
        //初始化curl
        $ch = curl_init();
        $curlVersion = curl_version();
        $ua = "WXPaySDK/0.0.5 (" . PHP_OS . ") PHP/" . PHP_VERSION . " CURL/" . $curlVersion['version'] . " " . $this->mchiD;
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_USERAGENT, $ua);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        if ($this->CURL_PROXY_HOST != "0.0.0.0"
            && $this->CURL_PROXY_PORT != 0
        ) {
            curl_setopt($ch, CURLOPT_PROXY, $this->CURL_PROXY_HOST);
            curl_setopt($ch, CURLOPT_PROXYPORT, $this->CURL_PROXY_PORT);
        }
        //运行curl，结果以jason形式返回
        $res = curl_exec($ch);
        curl_close($ch);
        //取出openid
        $data = json_decode($res, true);
        $this->data = $data;
        $openid = $data['openid'];
        return $openid;
    }
    public function __CreateOauthUrlForCode($redirectUrl)
    {
        $urlObj["appid"] = $this->appiD;
        $urlObj["redirect_uri"] = "$redirectUrl";
        $urlObj["response_type"] = "code";
        $urlObj["scope"] = "snsapi_base";
        $urlObj["state"] = "STATE" . "#wechat_redirect";
        $bizString = $this->ToUrlParam($urlObj);
        return "https://open.weixin.qq.com/connect/oauth2/authorize?" . $bizString;
    }
    private function __CreateOauthUrlForOpenid($code)
    {
        $urlObj["appid"] = $this->appiD;
        $urlObj["secret"] = $this->appSecret;
        $urlObj["code"] = $code;
        $urlObj["grant_type"] = "authorization_code";
        $bizString = $this->ToUrlParam($urlObj);
        return "https://api.weixin.qq.com/sns/oauth2/access_token?" . $bizString;
    }
    public function ToUrlParam($urlObj)
    {
        $buff = "";
        foreach ($urlObj as $k => $v) {
            if ($k != "sign") {
                $buff .= $k . "=" . $v . "&";
            }
        }
        $buff = trim($buff, "&");
        return $buff;
    }
}