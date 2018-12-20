<?php
namespace app\admin\model;

use think\Model;
use think\Db;

class Revert extends Model
{
    public function getRevert($appid)
    {
        $revert = Db::name('hf_revert')->where(['appid' => $appid])->field('set_text')->find();
        if ($revert) {
            return $revert['set_text'];
        } else {
            return false;
        }
    }
    //完全匹配
    public function getText($appid,$keyword)
    {
        $text = Db::name('hf_text')->where(['appid' => $appid,'keyword'=>$keyword])->find();
        if ($text) {
            return $text;
        } else {
            return false;
        }
    }
    //包含匹配
    public function getLikeText($appid,$keyword){
        $text = Db::name('hf_text')->where(['appid' => $appid])->where('keyword', 'like', ['%' . $keyword . '%'])->order('id desc')->select();
        $result = false;
        for($a=0;$a<count($text);$a++){
            if($text[$a]['type'] == 1){
                $result = $text[$a];
                break;
            }
        }
        if ($result) {
            return $result;
        } else {
            return false;
        }
    }
  //设置好的菜单
    public function getMenu(){
        $userId = session('nowUser')['id'];
        $appid = Db::name('wx_authorizer')->where(['user_id' => $userId])->field('authorizer_appid')->find();
        $appid = $appid['authorizer_appid'];
        $menu = Db::name('f_menu')->where(['appid' => $appid])->order('rank desc')->select();
        for ($a = 0; $a < count($menu); $a++) {
            $menu[$a][] = Db::name('son_menu')->where(['f_id' => $menu[$a]['id']])->select();
        }
        return $menu;
    }
  //key查询
    public function getKeys($key)
    {
        $result = Db::name('hf_text')->where(['id'=>$key])->find();
        if ($result) {
            return $result;
        } else {
            return false;
        }
    }
    //砍价关注查询
    public function getMember($openid = null)
    {
        $member = Db::name('kj_member')->where(['openid' => $openid])->order('id desc')->find();
        if ($member) {
            $activity = Db::name('kj_activity')->where(['id' => $member['hid']])->find();
            $time = strtotime($activity['finish_time']) - time();//活动剩余时间
            if ($time > 0) {
                $data['hid'] = $member['hid'];
                $data['sid'] = $member['sid'];
                $data['public_img'] = $activity['public_img'];
                $data['name'] = $activity['name'];
                $data['intro'] = $activity['intro'];
                return $data;
            }else{
                return false;
            }
        } else {
            return false;
        }
    }
}