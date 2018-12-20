<?php
namespace app\diuber\model;

use think\Model;
use think\Db;

class WxAuthorizer {
    
    /**
     * 保存小程序的接口调用凭据和授权信息
     * @access public
     *
     */
    public function setMiniAppInfo($data)
    {
        $returnArray = array();
        if($data){
            if(!empty($data['authorizer_appid']) && !empty($data['authorizer_access_token']) && !empty($data['authorizer_refresh_token']) && !empty($data['func_info']) && !empty($data['expires_in'])){
                $result = '';
                //判断用户是否已经授权过
                $existInfo = Db::name('wx_authorizer')->where('authorizer_appid',$data['authorizer_appid'])->select();
              	$user = session('nowUser');
                if($existInfo){
                    //已授权
                    $existInfo = $existInfo[0];
                        $row = array(
                            'authorizer_appid' => $data['authorizer_appid'],
                            'authorizer_access_token' => $data['authorizer_access_token'],
                            'authorizer_refresh_token' => $data['authorizer_refresh_token'],
                            'func_info' => json_encode($data['func_info']),
                            'update_time' => date('Y-m-d H:i:s'),
                            'check_time' => time(),
                          	'user_id' => $user['id'],
                            'company_id' => $data['company_id']
                        );
                        $result = Db::name('wx_authorizer')->where('id',$existInfo['id'])->update($row);
                }else{
                    //未授权，新增授权信息
                    $row = array(
                        'authorizer_appid' => $data['authorizer_appid'],
                        'authorizer_access_token' => $data['authorizer_access_token'],
                        'authorizer_refresh_token' => $data['authorizer_refresh_token'],
                        'func_info' => json_encode($data['func_info']),
                        'create_time' => date('Y-m-d H:i:s'),
                        'check_time' => (time() + $data['expires_in']),
                      	'user_id' => $user['id'],
                        'company_id' => $data['company_id']
                    );
                    $result = Db::name('wx_authorizer')->insert($row);
                }
                if($result){
                    $returnArray = array(
                        'code' => 1,
                        'info' => '创建或者更新成功'
                    );
                }
            }else{
                $returnArray = array(
                    'code' => 0,
                    'info' => 'data格式不正确'
                );
            }
        }

        return $returnArray;
    }
}