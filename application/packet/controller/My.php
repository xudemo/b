<?php
namespace app\packet\controller;

use think\Controller;
use think\Db;

//个人中心
class My extends Controller
{
    public function index()
    {
       $id = cookie('hbMember')['id'];
        $hid = cookie('hbMember')['hid'];
        $field = "a.id,a.nickname,a.pic,a.new_integral,a.pay,a.state,a.name,a.phone,shop_site,
        (select count(*) from zn_hb_member where zn_hb_member.sid=a.id) as xNum,
        (select count(*) from zn_hb_integral where zn_hb_integral.sid=a.id) as integNum";
        $list = Db::name('hb_member')
            ->alias('a')
            ->where(['id'=>$id,'hid'=>$hid])
            ->field($field)
            ->find();

        $order = array();
        if($list['pay'] == 1 && $list['state'] != 2){
            $order = Db::name('hb_orders')
                ->where(['uid'=>$id,'hid'=>$hid])
                ->field('money,time')
                ->find();
        }

        //红包详情
        $integralfield = "a.integral,b.pic,b.nickname,b.savedate";
        $integral = Db::name('hb_integral')
            ->alias('a')
            ->join('zn_hb_member b','b.id=a.sid')
            ->where(['a.sid'=>$id,'a.hid'=>$hid])
            ->field($integralfield)
            ->select();
        //我的下级
        $xiajifield = "a.pic,a.nickname,a.savedate,a.pay";
        $xiaji = Db::name('hb_member')
            ->alias('a')
            ->where(['sid'=>$id,'hid'=>$hid])
            ->field($xiajifield)
            ->select();

        $this->assign('list',$list);
        $this->assign('integral',$integral);
        $this->assign('xiaji',$xiaji);
        $this->assign('order',$order);
        return view();
    }
}