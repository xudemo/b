<?php
namespace app\kjlog\model;

use think\Db;
use think\Model;
use wechat\WeChat;

class Member extends Model
{
    public static function get_activity($id)
    {
        $activity = Db::name('kj_activity')->where(['id'=>$id])->find();
        if($activity){
            return $activity;
        }else{
            return false;
        }
    }
}