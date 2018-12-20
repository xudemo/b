<?php
namespace app\my\controller;

use app\my\controller\Checklogin;
use think\Db;
use app\my\model\Activity;

//正在进行的活动
class Proceed extends Checklogin
{
    //砍价活动列表
    public function kjlog($searach = null)
    {
        $date = date("Y-m-d H:i");
        $field = "id,name,begin_time,finish_time,appid,
        (select count(*) from zn_kj_member where hid=a.id) as browseNum,
        (select count(*) from zn_kj_orders where hid=a.id) as pay_num,
        (select sum(money) from zn_kj_orders where hid=a.id) as money,
        (select count(*) from zn_kj_ts where hid=a.id) as tsNum,
        (select nick_name from zn_wx_users where user_name=a.appid) as nick_name";
        if (empty($searach)) {
            $list = Db::name('kj_activity')
                ->alias('a')
                ->where('finish_time', 'GT', $date)
				->order('id desc')
                ->field($field)
                ->paginate(15, false, ['query' => ['searach' => $searach]]);
        } else {
            $list = Db::name('kj_activity')
                ->alias('a')
                ->where('finish_time', 'GT', $date)
                ->where('id|name', 'like', ['%' . $searach . '%'])
				->order('id desc')
                ->field($field)
                ->paginate(15, false, ['query' => ['searach' => $searach]]);
        }
        $page = $list->render();
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->assign('searach', $searach);
        return view();
    }

    //红包活动列表
    public function packet($searach = null)
    {
        $date = date("Y-m-d H:i");
        $field = "id,name,begin_time,finish_time,appid,
        (select count(*) from zn_hb_member where hid=a.id) as browseNum,
        (select count(*) from zn_hb_orders where hid=a.id) as pay_num,
        (select sum(money) from zn_hb_orders where hid=a.id) as money,
        (select count(*) from zn_hb_ts where hid=a.id) as tsNum,
        (select nick_name from zn_wx_users where user_name=a.appid) as nick_name";
        if (empty($searach)) {
            $list = Db::name('hb_activity')
                ->alias('a')
                ->where('finish_time', 'GT', $date)
				->order('id desc')
                ->field($field)
                ->paginate(15, false, ['query' => ['searach' => $searach]]);
        } else {
            $list = Db::name('hb_activity')
                ->alias('a')
                ->where('finish_time', 'GT', $date)
                ->where('id|name', 'like', ['%' . $searach . '%'])
				->order('id desc')
                ->field($field)
                ->paginate(15, false, ['query' => ['searach' => $searach]]);
        }
        $page = $list->render();
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->assign('searach', $searach);
        return view();
    }

    public function finish($id, $type)
    {
        $activity = new Activity();
        $result = $activity->finishActivity($id, $type);
        if ($result > 0) {
            return json(['state' => 1, 'msg' => '修改成功']);
        } else {
            return json(['state' => 0, 'msg' => '修改失败']);
        }
    }
}