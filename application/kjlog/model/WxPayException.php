<?php
/**微信支付API异常类*/
namespace app\kjlog\model;
class WxPayException extends \Exception {
    public function errorMessage()
    {
        return $this->getMessage();
    }
}