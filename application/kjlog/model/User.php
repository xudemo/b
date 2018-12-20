<?php
namespace app\kjlog\model;

use think\Db;
use app\kjlog\model\Exception;

class User 
{
    //用原始appoid查询服务器配置信息
    public function get_appid($appid)
    {
        $users = Db::name('wx_users')
            ->where(['user_name' => $appid])
            ->field('appId,appSecret')
            ->find();
        if(empty($users['appId']) || empty($users['appSecret'])){
            Exception::exception('查询appId和appSecret失败');
        }
        return $users;
    }
}