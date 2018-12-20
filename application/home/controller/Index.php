<?php
namespace app\home\controller;

use app\home\controller\Checklogin;
use think\cache\driver\Redis;
use think\Db;

class Index extends Checklogin
{
    
  	private $appId= 'wx2af2c6b1d5f2d819';
  
  	//我的公众号
    public function index()
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
		//dump($preAuthCodeRow);
		//exit;
        if($preAuthCodeRow['code'] == 1){
            if(!empty($preAuthCodeRow['info']['pre_auth_code'])){
                $preAuthCode = $preAuthCodeRow['info']['pre_auth_code'];
                //拼接获取预授权码的URL
                $url = 'https://mp.weixin.qq.com/cgi-bin/componentloginpage?component_appid='.$this->appId.'&pre_auth_code='.$preAuthCode.'&redirect_uri=http://www.znote.com.cn/wx/getAuthCode';
            }
        }

        //传入前端的URL
        $this->assign('url', $url);
      	
      	$user =  session('nowUser');
      	$users  = Db::name('wx_users')->where('user_id',$user['id'])->find();
       	$this->assign('users', $users);
        return $this->fetch('index');
    }
   //删除公众号
    public function delete($user_name)
    {
        $user = session('nowUser');
        $result = Db::name('wx_users')->where(['user_id'=>$user['id'],'user_name'=>$user_name])->delete();
        if($result>0){
            return json(['state'=>1,'message'=>'删除成功']);
        }else{
            return json(['state'=>0,'message'=>'删除失败']);
        }
    }
}
