<?php
namespace app\my\model;

use think\Model;
use think\Db;

class User extends Model
{
    //会员数
    public function countUser(){
        $count = Db::name('user')->count();
        return $count;
    }
    
    
}