<?php
namespace app\admin\controller;
use think\Controller;
use comms\wxBizMsgCrypt;

class Wx extends Controller
{
    public function sysMessage()
    {
        file_put_contents('log.txt', 0, FILE_APPEND);//加入日志
        $encodingAesKey = 'hkBTxfGyUMvRqbAtBoxXdhLTYrrIHYquZlMqwYPdapW';
        $token= '840c71aeceaee9776bfd45e2a5a15484';
        $appId= 'wx2af2c6b1d5f2d819';

      file_put_contents('log.txt', strlen($encodingAesKey).'    ', FILE_APPEND);//加入日志
      
        $timeStamp  = empty($_GET['timestamp'])     ? ""    : trim($_GET['timestamp']) ;
        $nonce      = empty($_GET['nonce'])     ? ""    : trim($_GET['nonce']) ;
        $msg_sign   = empty($_GET['msg_signature']) ? ""    : trim($_GET['msg_signature']) ;
        $encrypt_type   = empty($_GET['encrypt_type']) ? ""    : trim($_GET['encrypt_type']) ;

        file_put_contents('log.txt', $timeStamp.'  ', FILE_APPEND);//加入日志
        file_put_contents('log.txt', $nonce.'  ', FILE_APPEND);//加入日志
        file_put_contents('log.txt', $msg_sign.'  ', FILE_APPEND);//加入日志
        file_put_contents('log.txt', $encrypt_type.'  ', FILE_APPEND);//加入日志

        $encryptMsg = file_get_contents('php://input');
        file_put_contents('log.txt', $encryptMsg.'  ', FILE_APPEND);//加入日志

        $pc = new WXBizMsgCrypt();

        $xml_tree = new \DOMDocument();
        $xml_tree->loadXML($encryptMsg);
        $array_e = $xml_tree->getElementsByTagName('Encrypt');
        $encrypt = $array_e->item(0)->nodeValue;

        file_put_contents('log.txt', $encrypt.'  ', FILE_APPEND);//加入日志

        $format = "<xml><ToUserName><![CDATA[toUser]]></ToUserName><Encrypt><![CDATA[%s]]></Encrypt></xml>";
        $from_xml = sprintf($format, $encrypt);
        file_put_contents('log.txt', $from_xml.'  ', FILE_APPEND);//加入日志
// 第三方收到公众号平台发送的消息
        $msg = '';
        $errCode = $pc->decryptMsg($msg_sign, $timeStamp, $nonce, $from_xml, $msg);
        file_put_contents('log.txt', $msg.'      ', FILE_APPEND);//加入日志
      file_put_contents('log.txt', $errCode, FILE_APPEND);//加入日志
        if ($errCode == 0) {
            //print("解密后: " . $msg . "\n");
            $xml = new \DOMDocument();
            $xml->loadXML($msg);
            $array_e = $xml->getElementsByTagName('ComponentVerifyTicket');
            $component_verify_ticket = $array_e->item(0)->nodeValue;
            file_put_contents('log.txt', $component_verify_ticket, FILE_APPEND);//加入日志
            echo 'success';

        } else {
            file_put_contents('log.txt', $errCode, FILE_APPEND);//加入日志
        }
    }
}