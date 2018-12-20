<?php
namespace app\packet\controller;

use think\Controller;
use think\Request;
use think\Db;

//领取红包
class Getpacket extends Controller
{
    public function index($id = null, Request $request)
    {
        if ($request->isPost()) {
            $id = $request->post('id');
            $result = $this->get_packet($id);
            if ($result == false) {
                return json(['state' => 0, 'msg' => '你没有抽中']);
            } else {
               return json(['state' => 1, 'msg' => "获得{$result}积分"]);
            }
        } else {
            $this->assign('id', $id);
            return view();
        }
    }

//查询红包
    public function get_packet($id)
    {
        $member = Db::name('hb_member')->where(['id' => $id])->find();
        if ($member) {
            if ($member['pay'] == 0) {
                return false;
            }
        } else {
            return false;
        }

        $m = Db::name('hb_member')->where(['id' => $member['sid']])->find();
        if ($m) {
            if ($m['pay'] == 0) {
                return false;
            }
        } else {
            return false;
        }
        $integ = Db::name('hb_integral')->where(['uid'=>$id,'sid'=>$member['sid']])->find();
        if($integ){
            if($integ['use'] == 1){
                return false;
            }else{
                $rand = rand(5, 10);
                Db::name('hb_integral')->where(['uid'=>$id,'sid'=>$member['sid']])->update(['integral'=>$rand]);
                return $rand;
            }
        }else{
            return false;
        }
    }

    //领取
    public function set_packet($id)
    {
        $member = Db::name('hb_member')->where(['id' => $id])->find();
        $integ = Db::name('hb_integral')->where(['uid'=>$id,'sid'=>$member['sid']])->find();
        if(!$member || !$integ){
            return json(['state' => 0, 'msg' => '领取失败']);
        }
		if($integ['use'] == 1){
            return json(['state' => 0, 'msg' => '领取失败']);
        }
        Db::name('hb_member')
            ->query("update zn_hb_member set new_integral=new_integral+{$integ['integral']} where id={$member['sid']}");
        Db::name('hb_integral')->where(['uid'=>$id,'sid'=>$member['sid']])->update(['use'=>1]);
        return json(['state' => 1, 'msg' => '领取成功']);
    }
}