<?php
namespace app\packet\model;

use wechat\WeChat;
use think\Db;
use app\kjlog\model\Exception;

class Activity
{
    //活动id查询该活动
    public static function get_activity($id)
    {
        $activity = Db::name('hb_activity')
            ->where(['id' => $id])
            ->find();
        if ($activity) {
            $activity['wgnumber'] = Db::name('hb_member')->where(['hid' => $id])->count()+intval($activity['liu_num']);//围观人数
           // $activity['paynumber'] = Db::name('hb_member')->where(['hid' => $id, 'pay' => 1])->count();//支付人数
            $activity['paynumber'] = Db::name('hb_orders')->where(['hid' => $id])->count()+intval($activity['pay_num']);//支付人数
           $activity['user'] = Db::name('hb_member')
                ->where(['hid' => $id])
                ->order('id desc')
                ->limit(0,30)
                ->field('pic,nickname')
                ->select();//最近参与用户
            // $activity['pay'] = Db::name('hb_orders')
            //     ->join('zn_hb_member','zn_hb_orders.uid=zn_hb_member.id')
            //     ->where(['zn_hb_orders.hid'=>$id])
            //     ->select();//支付消息
                $activity['pay'] = Db::name('hb_orders')
                ->join('zn_hb_member','zn_hb_orders.uid=zn_hb_member.id')
                ->where(['zn_hb_orders.hid'=>$id])
                 ->order('zn_hb_orders.id desc')
                ->limit(0,6)
                ->select();
            return $activity;
        } else {
            Exception::exception('活动不存在或者已经结束');
        }
    }
    
}