<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use comms\wxBizMsgCrypt;
use comms\WxCrypt;
use think\cache\driver\Redis;
use app\admin\model\WxRevert;
class Wx extends Controller
{
    //encodingAesKey
    private $encodingAesKey='hkBTxfGyUMvRqbAtBoxXdhLTYrrIHYquZlMqwYPdapW';

    // token
    private $token= '840c71aeceaee9776bfd45e2a5a15484';

    // appId
    private $appId= 'wx2af2c6b1d5f2d819';

    /**
     * 授权事件接收URL
     * @access public
     *
     */
    public function sysMessage()
    {
        $wxComponentTicketModel = new \app\diuber\model\WxComponentTicket();
       $ip = $_SERVER["REMOTE_ADDR"];
     // file_put_contents('log.txt', 'ip:'.$ip, FILE_APPEND);//加入日志
      // $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];   //获取微信返回的xml
      // file_put_contents('log.txt',$postStr, FILE_APPEND);//加入日志
        $encodingAesKey = $this->encodingAesKey;
        $token = $this->token;
        $appId = $this->appId;
        
        $timeStamp = empty($_GET['timestamp']) ? "" : trim($_GET['timestamp']);
        $nonce = empty($_GET['nonce']) ? "" : trim($_GET['nonce']);
        $msg_sign = empty($_GET['msg_signature']) ? "" : trim($_GET['msg_signature']);
        $encryptMsg = file_get_contents('php://input', 'r');

        // file_put_contents('log.txt','timestamp：'.$timeStamp, FILE_APPEND);//加入日志
        // file_put_contents('log3.txt','nonce：'.$nonce, FILE_APPEND);//加入日志
        // file_put_contents('log4.txt','msg_sign：'.$msg_sign, FILE_APPEND);//加入日志
        // file_put_contents('log5.txt','encryptMsg：'.$encryptMsg.'         ', FILE_APPEND);//加入日志
        // file_put_contents('log6.txt',$encryptMsg, FILE_APPEND);//加入日志
        libxml_disable_entity_loader(true);
        $result = json_decode(json_encode(simplexml_load_string($encryptMsg, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        //获取到的component_verify_ticket 的 XML 存入redis
        $pc = new WxCrypt();
        $xml_tree = new \DOMDocument();
        $xml_tree->loadXML($encryptMsg);
        $array_e = $xml_tree->getElementsByTagName('Encrypt');
        $encrypt = $array_e->item(0)->nodeValue;
      
        $format = "<xml><ToUserName><![CDATA[toUser]]></ToUserName><Encrypt><![CDATA[%s]]></Encrypt></xml>";
        $from_xml = sprintf($format, $encrypt);
      
        // 第三方收到公众号平台发送的消息
        $msg = '';
        $errCode = $pc->decode($msg_sign, $timeStamp, $nonce, $from_xml, $encryptMsg);
        $msg = $errCode[1];
      //  file_put_contents('log.txt', $msg, FILE_APPEND);//加入日志
        if ($errCode[0] == 0) {
            $xml = new \DOMDocument();
            $xml->loadXML($msg);
            $array_e = $xml->getElementsByTagName('ComponentVerifyTicket');
            $component_verify_ticket = $array_e->item(0)->nodeValue;
           // file_put_contents('log.txt', $component_verify_ticket, FILE_APPEND);//加入日志

            //logResult('解密后的component_verify_ticket是：'.$component_verify_ticket);

            $dateline = time();
            $data = array(
                'app_id' => $result['AppId'],
                'encrypt' => $result['Encrypt'],
                'create_time' => $dateline + 600,
                'component_verify_ticket' => $component_verify_ticket,
                'time' => date('Y-m-d H:i:s')
            );
            //获取到的component_verify_ticket  存入redis
            $existComponentTicke = Db::name('wx_component_ticket')->where('component_verify_ticket',$component_verify_ticket)->select();
            if(!$existComponentTicke){
                $wx = Db::name('wx_component_ticket')->insert($data);
                if($wx){
                    echo 'success';
                  die();
                    exit;
                }else{
                    echo 'fail';
                  die();
                    exit;
                }
            }else{
                echo 'success';
              die();
                exit;
            }
        }else{
            echo 'fail';
          die();
            exit;
        }
    }

    /**
     * 公众号消息与事件接收URL
     * @access public
     *
     */
  
     public function callback()
    {
      header("Content-type:text/html;charset=utf-8");
    	//file_put_contents("get.txt",json_encode($_GET));
    	//$_GET = json_decode(file_get_contents("get.txt"),true );  //ethan add
       $timeStamp = empty($_GET['timestamp']) ? "" : trim($_GET['timestamp']);
        $nonce = empty($_GET['nonce']) ? "" : trim($_GET['nonce']);
        $msg_sign = empty($_GET['msg_signature']) ? "" : trim($_GET['msg_signature']);
        $userAppID = substr($_GET['appID'],1);

        $pc = new WxCrypt();
        $postStr = file_get_contents('php://input');   //获取微信返回的xml
        //file_put_contents("write.txt",$postStr);
        //$postStr = file_get_contents("write.txt"); //ethan add

      // $postStr = file_get_contents("ethan4.txt");
        //var_dump($_SERVER);

        $msg = '';
        $errCode = $pc->decode($msg_sign, $timeStamp, $nonce, $postStr, $msg);

        $msg = $errCode[1];
        // file_put_contents("ethan4.txt",$msg);
       // file_put_contents('ethan55.txt',$msg);
        file_put_contents('log.txt',$msg,FILE_APPEND);
        if ($errCode[0] == 0) {
           $wxRevertModel = new WxRevert();
             $postObj = simplexml_load_string($msg, 'SimpleXMLElement', LIBXML_NOCDATA);
           if(!empty($postStr)){
                $RX_TYPE = trim($postObj->MsgType);  //获取到的类型
                $result = "";
                switch ($RX_TYPE)
                {
                  case "event":
                      $result = $wxRevertModel->event($postObj,$userAppID);
                      break;
                  case "text":
                        $result = $wxRevertModel->text($postObj,$userAppID);
                        break;
               }
               file_put_contents('log.txt',$result,FILE_APPEND);
               if($result){
                  echo  $result.'<div>';
               }else{
                  echo 'success';
                    exit;
               }
              }else{
                  echo 'success';
                  exit;
              } 
        }else{
          echo 'success';
             exit;
        }
    }
  
    public function callbacksss()
    {
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];   //获取微信返回的xml
   
        file_put_contents('log.txt',$appId,FILE_APPEND);
      
        $wxComponentTicketModel = new \app\diuber\model\WxComponentTicket();
        $wxCallbackModel = new \app\diuber\model\WxCallback();
        $wxAccessTokenModel = new \app\diuber\model\WxAccessToken();

        $encodingAesKey = $this->encodingAesKey;
        $token = $this->token;
        $appId = $this->appId;
        $timeStamp  = empty($_GET['timestamp'])     ? ""    : trim($_GET['timestamp']) ;
        $nonce      = empty($_GET['nonce'])     ? ""    : trim($_GET['nonce']) ;
        $msg_sign   = empty($_GET['msg_signature']) ? ""    : trim($_GET['msg_signature']) ;

        $encryptMsg = file_get_contents('php://input');
        $pc = new \WXBizMsgCrypt($token, $encodingAesKey, $appId);

        $xml_tree = new \DOMDocument();
        $xml_tree->loadXML($encryptMsg);
        $array_e = $xml_tree->getElementsByTagName('Encrypt');
        $encrypt = $array_e->item(0)->nodeValue;


        $format = "<xml><ToUserName><![CDATA[toUser]]></ToUserName><Encrypt><![CDATA[%s]]></Encrypt></xml>";
        $from_xml = sprintf($format, $encrypt);

        // 第三方收到公众号平台发送的消息
        $msg = '';
        $errCode = $pc->decryptMsg($msg_sign, $timeStamp, $nonce, $from_xml, $msg);
        if ($errCode == 0) {
            $xml = new \DOMDocument();
            $xml->loadXML($msg);

            $array_e2 = $xml->getElementsByTagName('ToUserName');
            $ToUserName = $array_e2->item(0)->nodeValue;
            $array_e3 = $xml->getElementsByTagName('FromUserName');
            $FromUserName = $array_e3->item(0)->nodeValue;
            $array_e5 = $xml->getElementsByTagName('MsgType');
            $MsgType = $array_e5->item(0)->nodeValue;
            $nowTime = date('Y-m-d H:i:s');
            $contentx = '';


            if($MsgType=="text") {
                $array_e = $xml->getElementsByTagName('Content');
                $content = $array_e->item(0)->nodeValue;
                $needle ='QUERY_AUTH_CODE:';
                $tmparray = explode($needle,$content);
                if(count($tmparray) > 1){
                    //3、模拟粉丝发送文本消息给专用测试公众号，第三方平台方需在5秒内返回空串
                    //表明暂时不回复，然后再立即使用客服消息接口发送消息回复粉丝
                    $contentx = str_replace ($needle,'',$content);
                    $info = $wxAccessTokenModel->getMiniAppInfo($contentx);
                    $test_token = $info['info']['authorizer_access_token'];
                    $content_re = $contentx."_from_api";
                    echo '';
                    $data = '{
                            "touser":"'.$FromUserName.'",
                            "msgtype":"text",
                            "text":
                            {
                                 "content":"'.$content_re.'"
                            }
                        }';
                    $url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=".$test_token;
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_exec($ch);
                    curl_close($ch);
                }else{
                    //2、模拟粉丝发送文本消息给专用测试公众号
                    $contentx = "TESTCOMPONENT_MSG_TYPE_TEXT_callback";
                }
            }elseif($MsgType == "event"){ //1、模拟粉丝触发专用测试公众号的事件
                $array_e4 = $xml->getElementsByTagName('Event');
                $event = $array_e4->item(0)->nodeValue;
                $contentx = $event.'from_callback';
            }

            $text = "<xml>
            <ToUserName><![CDATA[$FromUserName]]></ToUserName>
            <FromUserName><![CDATA[$ToUserName]]></FromUserName>
            <CreateTime>$nowTime</CreateTime>
            <MsgType><![CDATA[text]]></MsgType>
            <Content><![CDATA[$contentx]]></Content>
                    </xml>";

            //加密消息
            $encryptMsg = '';
            $errCode = $pc->encryptMsg($text, $timeStamp, $nonce, $encryptMsg);

            $wxCallbackModel->create(array('from_user_name'=>$FromUserName,'to_user_name'=>$ToUserName,'msg_type'=>$MsgType,'content'=>$contentx,'create_time'=>$timeStamp));
            echo $encryptMsg;
            exit();
        } else {
            //存redis
            Cache::store('redis')->set('wx_call_back_err',$errCode);

            exit();
        }
    }
    
    

    /**
     * 发起授权页的体验URL获取预授权码
     * @access public
     *
     */
    public function auth()
    {
        $url = '';
        $redis = new Redis();
       //看预授权码redis过期没 没过期直接使用 过期了就从新获取
        $preAuthCodeRow = $redis->get('PreAuthCode');
        if(empty($preAuthCodeRow)){
          $wxAccessTokenModel = new \app\diuber\model\WxAccessToken();
          //获取预授权码
          $preAuthCodeRow = $wxAccessTokenModel->getPreAuthCode();
        }
        if($preAuthCodeRow['code'] == 1){
            if(!empty($preAuthCodeRow['info']['pre_auth_code'])){
                $preAuthCode = $preAuthCodeRow['info']['pre_auth_code'];
                //拼接获取预授权码的URL
                $url = 'https://mp.weixin.qq.com/cgi-bin/componentloginpage?component_appid='.$this->appId.'&pre_auth_code='.$preAuthCode.'&redirect_uri=http://www.znote.com.cn/wx/getAuthCode';
            }
        }

    
        //传入前端的URL
        $this->assign('url', $url);
        return $this->fetch('auth');
    }

    /**
     * 获取用户授权码getAuthCode
     * @access public
     *
     */
    public function getAuthCode()
    {
        $result = array();

        //实例化WxAccessToken模型，需要用到这个model中的getMiniAppInfo方法
        $wxAccessTokenModel = new \app\diuber\model\WxAccessToken();
        if(!empty($_GET)){
            if(!empty($_GET['auth_code'])){
                $result = $wxAccessTokenModel->getMiniAppInfo($_GET['auth_code']);
              if(!empty($result)){
                $this->redirect('home/index/index');
              }
            }
        }
    }
  public function https_request($url,$data=null){
    $headerArray = array("Content-type:application/json;", "Accept:application/json");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArray);
        $output = curl_exec($ch);
        $state = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return array("state" => $state, "data" => $output);
  }
  /*asd*/
    public function getAccessToken()
    {
         $wxAccessTokenModel = new \app\diuber\model\WxAccessToken();
        
     $a = $wxAccessTokenModel->getAccessToken();
        
        
         print_r($a);

        // return $returnArray;
    }

}