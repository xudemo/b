<?php
namespace app\index\model;

use think\Db;
use think\Model;

class User extends Model
{
    // 查询账号是否存在
    public function getUser($where)
    {
        $user = Db::name('user')->where($where)->find();
        return $user;
    }

    //写入user表
    public function insertUser($user)
    {
        $result = Db::name('user')->insert($user);
        return $result;
    }

}