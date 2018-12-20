<?php
namespace app\kjlog\model\wechatpay;

use app\kjlog\model\WxPayException;

class WxPayUnifiedOrder
{
    protected $values = array();
//①    /**设置商品或支付单简要描述**/
    public function SetBody($value)
    {
        $this->values['body'] = $value;
    }
    /**设置附加数据，在查询API和支付通知中原样返回，该字段主要用于商户携带订单的自定义数据**/
    public function SetAttach($value)
    {
        $this->values['attach'] = $value;
    }
    /**设置商户系统内部的订单号,32个字符内、可包含字母, 其他说明见商户订单号**/
    public function SetOut_trade_no($value)
    {
        $this->values['out_trade_no'] = $value;
    }
    /**设置订单总金额，只能为整数，详见支付金额**/
    public function SetTotal_fee($value)
    {
        $this->values['total_fee'] = $value;
    }
    /**设置订单生成时间**/
    public function SetTime_start($value)
    {
        $this->values['time_start'] = $value;
    }
    /**设置订单失效时间**/
    public function SetTime_expire($value)
    {
        $this->values['time_expire'] = $value;
    }
    /**设置商品标记，代金券或立减优惠功能的参数**/
    public function SetGoods_tag($value)
    {
        $this->values['goods_tag'] = $value;
    }
    /**设置接收微信支付异步通知回调地址**/
    public function SetNotify_url($value)
    {
        $this->values['notify_url'] = $value;
    }
    /**设置取值如下：JSAPI，NATIVE，APP，**/
    public function SetTrade_type($value)
    {
        $this->values['trade_type'] = $value;
    }
    /**设置trade_type=JSAPI，此参数必传，用户在商户appid下的唯一标识。**/
    public function SetOpenid($value)
    {
        $this->values['openid'] = $value;
    }
//②
    /**判断商户系统内部的订单号,32个字符内、可包含字母**/
    public function IsOut_trade_noSet()
    {
        return array_key_exists('out_trade_no', $this->values);
    }
    /**判断商品或支付单简要描述是否存在**/
    public function IsBodySet()
    {
        return array_key_exists('body', $this->values);
    }
    /**判断订单总金额，只能为整数，详见支付金额是否存在**/
    public function IsTotal_feeSet()
    {
        return array_key_exists('total_fee', $this->values);
    }
    /**判断取值如下：JSAPI，NATIVE，APP，详细说明见参数规定是否存在**/
    public function IsTrade_typeSet()
    {
        return array_key_exists('trade_type', $this->values);
    }
    /**获取取值如下：JSAPI，NATIVE，APP，详细说明见参数规定的值**/
    public function GetTrade_type()
    {
        return $this->values['trade_type'];
    }
    /**判断trade_type=JSAPI，此参数必传，用户在商户appid下的唯一标识。**/
    public function IsOpenidSet()
    {
        return array_key_exists('openid', $this->values);
    }
    /**判断trade_type=NATIVE，此参数必传。此id为二维码中包含的商品ID，商户自行定义。是否存在**/
    public function IsProduct_idSet()
    {
        return array_key_exists('product_id', $this->values);
    }
    /**判断接收微信支付异步通知回调地址是否存在**/
    public function IsNotify_urlSet()
    {
        return array_key_exists('notify_url', $this->values);
    }
    /**设置微信分配的公众账号ID**/
    public function SetAppid($value)
    {
        $this->values['appid'] = $value;
    }
    /**设置微信支付分配的商户号**/
    public function SetMch_id($value)
    {
        $this->values['mch_id'] = $value;
    }
    /**设置APP和网页支付提交用户端ip，Native支付填调用微信支付API的机器IP。**/
    public function SetSpbill_create_ip($value)
    {
        $this->values['spbill_create_ip'] = $value;
    }
    /**设置随机字符串，不长于32位。推荐随机数生成算法**/
    public function SetNonce_str($value)
    {
        $this->values['nonce_str'] = $value;
    }

    //错误
    /**
     * 设置签名，详见签名生成算法
     * @param string $value
     **/
    public function SetSign($key)
    {
        $sign = $this->MakeSign($key);
        $this->values['sign'] = $sign;
        return $sign;
    }
    /**
     * 生成签名
     * @return 签名，本函数不覆盖sign成员变量，如要设置签名需要调用SetSign方法赋值
     */
    public function MakeSign($key)
    {
        //签名步骤一：按字典序排序参数
        ksort($this->values);
        $string = $this->ToUrlParams();
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=".$key;
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }
    /**
     * 格式化参数格式化成url参数
     */
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
    /**
     * 输出xml字符
     * @throws WxPayException
     **/
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
    /**
     * 将xml转为array
     * @param string $xml
     * @throws WxPayException
     */
    public static function Init($xml,$key)
    {
        $obj = new self();
        $obj->FromXml($xml);
        //fix bug 2015-06-29
        if($obj->values['return_code'] != 'SUCCESS'){
            return $obj->GetValues();
        }
        $obj->CheckSign($key);
        return $obj->GetValues();
    }
    /**
     * 将xml转为array
     * @param string $xml
     * @throws WxPayException
     */
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
    /**
     * 获取设置的值
     */
    public function GetValues()
    {
        return $this->values;
    }
    /**
     *
     * 检测签名
     */
    public function CheckSign($key)
    {
        //fix异常
        if(!$this->IsSignSet()){
            throw new WxPayException("签名错误！");
        }

        $sign = $this->MakeSign($key);
        if($this->GetSign() == $sign){
            return true;
        }
        throw new WxPayException("签名错误！");
    }
    /**
     * 判断签名，详见签名生成算法是否存在
     * @return true 或 false
     **/
    public function IsSignSet()
    {
        return array_key_exists('sign', $this->values);
    }
    /**
     * 获取签名，详见签名生成算法的值
     * @return 值
     **/
    public function GetSign()
    {
        return $this->values['sign'];
    }
}