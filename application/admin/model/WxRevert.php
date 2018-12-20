<?php
namespace app\admin\model;

use app\admin\model\Revert;
use think\Db;

class WxRevert
{
  	protected $appid = 'wx2af2c6b1d5f2d819';
    private $appsecret = 'ef62623bfd04b7e6757d0b1cbf24ce8d';

    //获取ComponentAccessToken
    public function getComponentAccessToken()
    {
        $component_verify_ticket = Db::name('wx_component_ticket')->order('id desc')->field('component_verify_ticket')->find();
        $url = "https://api.weixin.qq.com/cgi-bin/component/api_component_token";
        $arr['component_appid'] = $this->appid;
        $arr['component_appsecret'] = $this->appsecret;
        $arr['component_verify_ticket'] = $component_verify_ticket['component_verify_ticket'];
        $data = json_encode($arr);
        $result = $this->https_request($url, $data);
        $component_access_token = json_decode($result, true)['component_access_token'];
        if ($component_access_token) {
            return $component_access_token;
        } else {
            return false;
        }
    }

    //获取authorizer_access_token
    public function getAuthorizerAccessToken(){
        $userId = session('nowUser')['id'];
        $wx_authorizer = Db::name('wx_authorizer')->where('user_id',$userId)->find();
        $time = time()-$wx_authorizer['check_time'];
        if($time > 5400){
            $arr['component_appid'] = $this->appid;
            $arr['authorizer_appid'] = $wx_authorizer['authorizer_appid'];
            $arr['authorizer_refresh_token'] = $wx_authorizer['authorizer_refresh_token'];
            $data = json_encode($arr);
            $componentAccessToken = $this->getComponentAccessToken();
            if(!$componentAccessToken){
                return false;
                exit;
            }
            $url = "https://api.weixin.qq.com/cgi-bin/component/api_authorizer_token?component_access_token=$componentAccessToken";
            $result = $this->https_request($url,$data);
            $access_token = json_decode($result,true);
            if(isset($access_token['authorizer_access_token'])){
                $d['authorizer_access_token'] = $access_token['authorizer_access_token'];
                $d['authorizer_refresh_token'] = $access_token['authorizer_refresh_token'];
                $d['check_time'] = time();
                Db::name('wx_authorizer')->where('user_id',$userId)->update($d);
                return $access_token['authorizer_access_token'];
            }else{
                return false;
            }
        }else{
            if(isset($wx_authorizer['authorizer_access_token'])){
                return $wx_authorizer['authorizer_access_token'];
            }else{
                return false;
            }
        }
    }
    //event类型回复
    public function event($postObj,$userAppID)
    {
        $revertModel = new Revert();
        $content = "";
      	$hf_text = array();
        switch ($postObj->Event)
        {
            case "subscribe":   //关注时回复
                // $content = $revertModel->getRevert($userAppID);
            $openid = $postObj->FromUserName;
                $data = $revertModel->getMember($openid);
                if ($data) {
                    return $this->transmitNews($postObj,$data);
                } else {
                    $content = $revertModel->getRevert($userAppID);
                }
                break;
            case "CLICK":
                $key = $postObj->EventKey; //获取之前设置的菜单的key
                $hf_text = $revertModel->getKeys($key);
                break;
        }
      $result = '';
       if($hf_text['revert_type'] == '2' && isset($hf_text['revert_type'])){
            $result = $this->transmitImg($postObj, $hf_text['media_id']);
        }else if($hf_text['revert_type'] == '1' && isset($hf_text['revert_type'])){
            $result = $this->transmitText($postObj, $hf_text['set_text']);
        }else{
            $result = $this->transmitText($postObj, $content);
        }
      if($result == ''){
      	return  false;
      }else{
      	 return $result;
      } 
    }
    //text类型
    public function text($postObj,$userAppID)
    {
        $keyword = trim($postObj->Content);  //发送给公众号的类容
        $revertModel = new Revert();
        $content = $revertModel->getText($userAppID, $keyword);

        if (!$content) {
            $content = $revertModel->getLikeText($userAppID, $keyword);
        }
      	$result = "";
        if ($content && $content['revert_type'] == 1) {
            $result = $this->transmitText($postObj, $content['set_text']);
        } else if($content && $content['revert_type'] == 2) {
            $result = $this->transmitImg($postObj, $content['media_id']);
        }
      	if($result == ""){
        	return  false;
        }else{
        	return $result;
        }   
    }
    //回复文字
    public function transmitText($object, $content)
    {
        if (!isset($content) || empty($content)){
            return "";
        }
       // $textTpl = "<xml><ToUserName><![CDATA[%s]]></ToUserName><FromUserName><![CDATA[%s]]></FromUserName><CreateTime>%s</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA[%s]]></Content></xml>";
       
        $textTpl = "<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[text]]></MsgType>
                    <Content><![CDATA[%s]]></Content>
                    </xml>";
       // $textTpl = '<xml> <ToUserName>< ![CDATA[%s] ]></ToUserName> <FromUserName>< ![CDATA[%s] ]></FromUserName> <CreateTime>%s</CreateTime> <MsgType>< ![CDATA[%s] ]></MsgType> <Content>< ![CDATA[%s] ]></Content> </xml>';
        $result = sprintf($textTpl, $object->FromUserName, $object->ToUserName, time(), $content);
        return $result;
    }
  
  	//回复图片
    public function transmitImg($object, $mediaId)
    {
               $imgTpl = "<xml>
               <ToUserName><![CDATA[%s]]></ToUserName>
               <FromUserName><![CDATA[%s]]></FromUserName>
               <CreateTime>%s</CreateTime>
               <MsgType><![CDATA[image]]></MsgType>
               <Image><MediaId><![CDATA[%s]]></MediaId></Image>
               </xml>";
        $result = sprintf($imgTpl, $object->FromUserName, $object->ToUserName, time(), $mediaId);
        return $result;
    }
    //回复图文
    public function transmitNews($object, $data)
    {
        $newsTpl = "<xml>
                <ToUserName><![CDATA[%s]]></ToUserName>
                <FromUserName><![CDATA[%s]]></FromUserName>
                <CreateTime>%s</CreateTime>
                <MsgType><![CDATA[news]]></MsgType>
                <ArticleCount>1</ArticleCount>
                <Articles><item>
                <Title><![CDATA[%s]]></Title>
                <Description><![CDATA[%s]]></Description>
                <PicUrl><![CDATA[%s]]></PicUrl>
                <Url><![CDATA[%s]]></Url>
                </item></Articles>
                </xml>";
        $picUrl = $data['public_img'];
        $url = URL."/kjlog/index/welcome?hid={$data['hid']}&sid={$data['sid']}";
        $result = sprintf($newsTpl,$object->FromUserName, $object->ToUserName,time(),$data['name'],$data['intro'],$picUrl,$url);
        return $result;
    }
  
  //get请求
    public function getUrl($url)
    {
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
    //post请求
  public function https_request($url,$data=null){
    $curl = curl_init();
    curl_setopt($curl,CURLOPT_URL,$url);
    curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,FALSE);
    curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,FALSE);
    if(!empty($data)){
        curl_setopt($curl,CURLOPT_POST,1);
        curl_setopt($curl,CURLOPT_POSTFIELDS,$data);
    }
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
    $output = curl_exec($curl);
    curl_close($curl);
    return $output;
	}

}