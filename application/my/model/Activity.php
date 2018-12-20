<?php
namespace app\my\model;

use think\Db;

class Activity
{
    //结束活动
    public function finishActivity($id, $type)
    {
        $result = 0;
        switch ($type) {
            case 'kj':
                $result = $this->finishKj($id);
                break;
            case 'hb':
                $result = $this->finishHb($id);
                break;
        }
        return $result;
    }

    public function finishKj($id)
    {
        $date = date("Y-m-d H:i");
        $date = str_replace(' ', 'T', $date);
        $result = Db::name('kj_activity')->where(['id' => $id])->update(['finish_time' => $date]);
        return $result;
    }

    public function finishHb($id)
    {
        $date = date("Y-m-d H:i");
        $date = str_replace(' ', 'T', $date);
        $result = Db::name('hb_activity')->where(['id' => $id])->update(['finish_time' => $date]);
        return $result;
    }
	
	//支付详情
    public function payDetails($id, $type)
    {
        $result = [];
        switch ($type) {
            case 'kj':
                $result = $this->kjPayDetails($id);
                break;
            case 'hb':
                $result = $this->hbPayDetails($id);
                break;
        }
        return $result;
    }

    public function kjPayDetails($id)
    {
 //   	$list = Db::name('kj_member')
//            ->alias('a')
//            ->join('zn_kj_orders','zn_kj_orders.uid=a.id')
//            ->where(['zn_kj_member.hid'=>$id])
//            ->field('a.nickname,a.name,a.phone,zn_kj_orders.money,zn_kj_orders.time')
//            ->select();
        $list = Db::query("select a.money,a.time,b.nickname,b.name,b.phone from zn_kj_orders as a join zn_kj_member as b on a.uid=b.id where a.hid={$id}");
        return $list;
    }

    public function hbPayDetails($id)
    {
        $list = Db::name('hb_member')
            ->alias('a')
            ->join('zn_hb_orders','zn_hb_orders.uid=a.id')
            ->where(['zn_hb_member.hid'=>$id])
            ->field('a.nickname,a.name,a.phone,zn_hb_orders.money,zn_hb_orders.time')
            ->select();
        return $list;
    }
}