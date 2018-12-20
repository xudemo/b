<?php 
namespace app\diuber\model;

use think\Model;
use think\Db;
use think\cache\driver\Redis;

/**
 * 微信AccessToken模型
 * @author zhangming
 * @date 2018-08-28 15:50
 * @version 1.0
 */

class WxAccessToken extends Model
{
    // 表名
    private $table_name = 'wx_access_token';
    
    //所有键值
    private $table_key = array(
    );   
    
    // appid
    private $appid= 'wx2af2c6b1d5f2d819';
    
    // appsecret
    private $appsecret= 'ef62623bfd04b7e6757d0b1cbf24ce8d';
    
    
    /**
     * 通过component_access_token获取pre_auth_code
     * @access private
     *
     */
    public function getPreAuthCode()
    {
        $returnArray = array();
        //获取component_access_token
        $componentAccessTokenRow = self::getComponentAccessToken();
       // return $componentAccessTokenRow;
        if($componentAccessTokenRow['code'] == 1){
            $componentAccessToken = $componentAccessTokenRow['info']['access_token'];
            $row = json_encode(array(
                'component_appid' => $this->appid
            ));
            $url = 'https://api.weixin.qq.com/cgi-bin/component/api_create_preauthcode?component_access_token='.$componentAccessToken;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $row);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $output = curl_exec($ch);
            curl_close($ch);
            $returnArray = array(
                'code' => 1,
                'info' => json_decode($output, true)
            );
          	//存redis 10分钟过期
          	$redis = new Redis();
        	$redis->set('PreAuthCode', $returnArray ,600);
        }else{
            $returnArray = array(
                'code' => 0,
                'info' => '获取component_access_token失败'
            );
        }
        
        return $returnArray;
    }
    
    
    
    /**
     * 微信获取ComponentAccessToken
     * @access public
     *
     */
    public function getComponentAccessToken()
    {
        $returnArray = array();
        //这里'type'是不同类型的AccessToken，我这边有小程序、公众号、第三方平台的，所以用'type'区分开来，2代表第三方平台
        //获取最新的第三方平台的AccessToken
        $wxAccessToken = Db::name('wx_access_token')->where('type',2)->order('id desc')->limit(1)->select();
        if(!empty($wxAccessToken)){
            $wxAccessToken = $wxAccessToken[0];
            //判断是否过期
            if((time() - $wxAccessToken['check_time']) < -3600){
                $returnArray = array(
                    'code' => 1,
                    'info' => array(
                        'access_token' => $wxAccessToken['access_token'],
                        'end_time' => $wxAccessToken['check_time']
                    )
                );
            }else{
                //过期了重新获取
                $returnArray = self::setComponentAccessToken();
            }
        }else{
            //没有AccessToken重新获取
            $returnArray = self::setComponentAccessToken();
        }
        
        return $returnArray;
    }
    
    
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
    /**
     * 通过component_verify_ticket获取component_access_token并保存
     * @access public
     *
     */
    public function setComponentAccessToken()
    {
        $returnArray = array();
        //初始化ComponentTicket模型（保存微信每10分钟传过来的ComponentTicket）
        $wxComponentTicketModel = new \app\diuber\model\WxComponentTicket();
        //获取数据库中最新的一个ComponentTicket
        $componentTicketRow = Db::name('wx_component_ticket')->order('id desc')->limit(1)->select();
        //return $componentTicketRow;
        if($componentTicketRow){
            $componentTicket = $componentTicketRow[0]['component_verify_ticket'];
            $row = json_encode(array(
                'component_appid' => $this->appid,
                'component_appsecret' => $this->appsecret,
              	'component_verify_ticket' => $componentTicket
            ));
            $url = 'https://api.weixin.qq.com/cgi-bin/component/api_component_token';
            $output = self::https_request($url,$row);
		//	return $output;
            $output = json_decode($output, true);
            if(!empty($output['component_access_token']) && !empty($output['expires_in'])){
                $checkTime= time() + $output['expires_in'];
                $data = array(
                    'access_token' => $output['component_access_token'], //AccessToken
                    'create_time' => date('Y-m-d H:i:s'),
                    'check_time' =>  $checkTime //校验时间戳
                );
                //创建AccessToken记录
                $wx = Db::name('wx_access_token')->insert($data);
                if(!$wx){
                    $returnArray = array(
                        'code' => 0,
                        'info' => '后台保存失败！'
                    );
                }else{
                    $returnArray = array(
                        'code' => 1,
                        'info' => array(
                            'access_token' => $data['access_token'],
                            'end_time' => $data['check_time']
                        )
                    );
                }
            }else{
                $returnArray = array(
                    'code' => 0,
                    'info' => '微信端获取失败！'
                );
            }
        }
        return $returnArray;
    }
  
    
    
    /**
     * 通过授权码换取小程序的接口调用凭据和授权信息并保存
     * @param $companyId 公司编号 区分不同小程序授权账号
     * @access public
     *
     */
    public function getMiniAppInfo($authCode, $companyId = 0)
    {
        $returnArray = array();
        $wxComponentTicketModel = new \app\diuber\model\WxComponentTicket();
        $wxAuthorizerModel = new \app\diuber\model\WxAuthorizer();
        //获取ComponentAccessToken
        $componentAccessTokenRow = self::getComponentAccessToken();
        if($componentAccessTokenRow['code'] == 1){
            $componentAccessToken = $componentAccessTokenRow['info']['access_token'];
            if($authCode){
                $row = json_encode(array(
                    'component_appid' => $this->appid,
                    'authorization_code' => $authCode
                ));
                //通过授权码获取公众号或小程序的接口调用凭据和授权信息
                $url = 'https://api.weixin.qq.com/cgi-bin/component/api_query_auth?component_access_token='.$componentAccessToken;
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $row);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                $output = curl_exec($ch);
                curl_close($ch);
                $output = json_decode($output, true);
                //判断授权信息并且存入数据库中
                if(!empty($output['authorization_info'])){	
                        $output['authorization_info']['company_id'] = $companyId;
                        $authResult = $wxAuthorizerModel->setMiniAppInfo($output['authorization_info']);
                        if($authResult['code'] == 1){
                          $url = "https://api.weixin.qq.com/cgi-bin/component/api_get_authorizer_info?component_access_token=$componentAccessToken";
                          $row1 = json_encode(array(
                   				 'component_appid' => $this->appid,
                   				 'authorizer_appid' => $output['authorization_info']['authorizer_appid']
                		  ));
                          $dat = self::https_request($url,$row1);
                          $data = json_decode($dat, true)['authorizer_info'];
                          $b = self::setwxusers($data);
                          
                            $returnArray = array(
                                'code' => 1,
                                'info' => $output['authorization_info']
                            );
                        }else{
                            $returnArray = array(
                                'code' => 0,
                                'info' => 'create or update mini app cgi info fail'
                            );
                        }
                }else{
                    $returnArray = array(
                        'code' => 0,
                        'info' => 'not found authorization_info'
                    );
                }
            }else{
                $returnArray = array(
                    'code' => 0,
                    'info' => 'not found $authCode'
                );
            }
        }else{
            $returnArray = array(
                'code' => 0,
                'info' => 'get component_access_token fail'
            );
        }
        
        return $returnArray;
    }
    
 	 //post请求
    function postUrl($data,$url=null){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $tmpInfo = curl_exec($ch);
        if (curl_errno($ch)) {
            return curl_error($ch);
        }
        curl_close($ch);
        return $tmpInfo;
    }
    
    /**
     * 微信公众号获取AccessToken
     * @access public
     *
     */
    /*asd*/
  	public function getAccessToken(){
        $userId = session('nowUser')['id'];
        $wx_authorizer = Db::name('wx_authorizer')->where('user_id',$userId)->find();
        $time = time()-$wx_authorizer['check_time'];
        if($time > 6800){
            $componentAccessTokenRow = self::getComponentAccessToken();
            $componentAccessToken = $componentAccessTokenRow['info']['access_token'];
            $refresh_token = $wx_authorizer['authorizer_refresh_token'];
            $appid = $wx_authorizer['authorizer_appid'];
            $url = "https://api.weixin.qq.com /cgi-bin/component/api_authorizer_token?component_access_token=$componentAccessToken";
            $row = json_encode(array(
                'component_appid' => $this->appid,
                'authorizer_appid' => $appid,
                'authorizer_refresh_token' => $refresh_token
            ));
            //$output = $this->postUrl($url,$row);
            return $componentAccessToken;
        }else{
            return $wx_authorizer['authorizer_access_token'];
        }
    }
    
 
 
  	/*存用户公众号相关信息*/
  	public function setwxusers($data){
        $user =  session('nowUser');
        $row = array(
            'nick_name' => $data['nick_name'],
            'head_img' => $data['head_img'],
            'service_type_info' => $data['service_type_info']['id'],
            'verify_type_info' => $data['verify_type_info']['id'],
            'user_name' => $data['user_name'],
            'principal_name' => $data['principal_name'],
          	'user_id' => $user['id'],
            'qrcode_url' => $data['qrcode_url']
        );
      	$date = Db::name('wx_users')->where('user_id',$user['id'])->select();
      	if(empty($date)){
            $b = Db::name('wx_users')->insert($row);
        }else{
            $b = Db::name('wx_users')->where('user_id',$user['id'])->update($row);
        }
        return $b;
    }
    
}