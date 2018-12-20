<?php
namespace app\my\controller;

use app\my\controller\Checklogin;
use think\Db;
use app\my\model\Activity;
use app\my\model\FinanceDetails;

//财务统计
class Finance extends Checklogin
{
    //砍价财务统计
    public function kjlog($company = null, $searach = null)
    {
        $field = "a.id,a.name,a.appid,a.begin_time,a.finish_time,a.company,
        (select count(*) from zn_kj_orders where hid=a.id) as pay_num,
        (select sum(money) from zn_kj_orders where hid=a.id) as pay_money,
        (select nick_name from zn_wx_users where user_name=a.appid) as nick_name";
        $where = [];
        if(!empty($company)){
            $where['company'] = $company;
        }
        if(empty($searach)){
            $list = Db::name('kj_activity')
                ->alias('a')
                ->where($where)
				->order('id desc')
                ->field($field)
                ->select();
        }else{
            $list = Db::name('kj_activity')
                ->alias('a')
                ->where($where)
                ->where('id|name','like',['%'.$searach.'%'])
				->order('id desc')
                ->field($field)
                ->select();
        }
        $this->assign('list', $list);
        $this->assign('company',$company);
        $this->assign('searach',$searach);
        return view();
    }

    //红包财务统计
    public function packet($company = null, $searach = null)
    {
        $field = "a.id,a.name,a.appid,a.begin_time,a.finish_time,a.company,
        (select nick_name from zn_wx_users where user_name=a.appid) as nick_name,
        (select count(*) from zn_hb_orders where hid=a.id) as pay_num,
        (select sum(money) from zn_hb_orders where hid=a.id) as pay_money";
        $where = [];
        if(!empty($company)){
            $where['company'] = $company;
        }
        if(empty($searach)){
            $list = Db::name('hb_activity')
                ->alias('a')
                ->where($where)
				->order('id desc')
                ->field($field)
                ->select();
        }else{
            $list = Db::name('hb_activity')
                ->alias('a')
                ->where($where)
                ->where('id|name','like',['%'.$searach.'%'])
				->order('id desc')
                ->field($field)
                ->select();
        }
        $this->assign('list', $list);
        $this->assign('company',$company);
        $this->assign('searach',$searach);
        return view();
    }

    //支付详情
    public function details($id, $type)
    {
        $activity = new Activity();
        $list = $activity->payDetails($id, $type);
        $this->assign('list', $list);
        return view();
    }
    
	 //收支详情
    public function pay_details($id,$page=null)
    {
        $list = [];
        $field = "a.money,a.time,b.nickname,b.pay,
		(select integral from zn_hb_integral as c where c.transactionId=a.transaction_id) as integral,
		(select c.use from zn_hb_integral as c where c.transactionId=a.transaction_id) as sendUse";
        if(empty($page)){
            $f = new FinanceDetails();
            $list = $f->getFinance($id,'hb');
            $page = 0;
            $order = Db::name('hb_orders')
                ->alias('a')
                ->join('zn_hb_member b','b.id=a.uid')
                ->where(['a.hid'=>$id])
                
                ->field($field)
                ->select();
        }else{
            $order = Db::name('hb_orders')
                ->alias('a')
                ->join('zn_hb_member b','b.id=a.uid')
                ->where(['a.hid'=>$id])
                ->limit($page,20)
                ->field($field)
                ->select();
            return json($order);
        }
        $this->assign('list',$list);
        $this->assign('order',$order);
        $this->assign('page',$page);
        $this->assign('id',$id);

        return view();
    }
}
