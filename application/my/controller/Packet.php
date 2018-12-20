<?php
namespace app\my\controller;

use app\my\controller\Checklogin;
use think\Db;
use think\Request;

class Packet extends Checklogin
{
    //首页
    public function index($searach = null)
    {
        if (empty($searach)) {
            $list = Db::name('wx_users')
                ->join('zn_user', 'zn_wx_users.user_id=zn_user.id')
                ->order('zn_wx_users.id desc')
                ->paginate(15);
        } else {
            $list = Db::name('wx_users')
                ->join('zn_user', 'zn_wx_users.user_id=zn_user.id')
                ->where('nick_name|nickname|phone', 'like', ['%' . $searach . '%'])
                ->order('zn_wx_users.id desc')
                ->paginate(15, false, ['query' => ['searach' => $searach]]);
        }

        $page = $list->render();
        $this->assign('page', $page);
        $this->assign('user', $list);
        $this->assign('searach', $searach);
        return view();
    }

    //开通
    public function activity($appid = null, Request $request)
    {
        if ($this->request->isPost()) {
            $data = $request->post();
            $code = $request->post('code');
			$no_pay = $request->post('no_pay');
			$no_master = $request->post('no_master');
            if (!empty($code)) {
                $data['code'] = 1;
            } else {
                $data['code'] = 0;
            }
			if(empty($no_pay)){
                $data['no_pay'] = 1;
            }else{
                $data['no_pay'] = 0;
            }
			if(empty($no_master)){
                $data['no_master'] = 1;
            }else{
                $data['no_master'] = 0;
            }
            if (isset($data['file'])) {
                unset($data['file']);
            }
            $result = Db::name('hb_activity')->insert($data);
			$this->assign("begin_time",$data['begin_time']);
            $this->assign("finish_time",$data['finish_time']);
            if ($result > 0) {
                $this->assign('data', ['state' => 1, 'message' => '添加成功']);
                $this->assign('appid', $appid);
                return view();
            } else {
                $this->assign('data', ['state' => 2, 'message' => '添加失败']);
                $this->assign('appid', $appid);
                return view();
            }
        } else {
        	$begin_time = date("Y-m-d H:i");
            $finish_time = date("Y-m-d H:i",strtotime('+8 day'));
            $begin_time = str_replace(" ","T",$begin_time);
            $finish_time = str_replace(" ","T",$finish_time);

            $this->assign("begin_time",$begin_time);
            $this->assign("finish_time",$finish_time);
            $this->assign('data', null);
            $this->assign('appid', $appid);
            return view();
        }
    }

    //列表
    public function details($appid = null, $searach = null)
    {
        if (empty($searach)) {
            $list = Db::name('hb_activity')
                ->where(['appid' => $appid])
                ->order('id desc')
                ->paginate(15, false, ['query' => ['appid' => $appid]]);
            $count = Db::name('hb_activity')
                ->where(['appid' => $appid])
                ->order('id desc')
                ->count();
        } else {
            $list = Db::name('hb_activity')
                ->where('id|name', 'like', ['%' . $searach . '%'])
                ->where(['appid' => $appid])
                ->order('id desc')
                ->paginate(15, false, ['query' => ['appid' => $appid]]);
            $count = Db::name('hb_activity')
                ->where('id|name', 'like', ['%' . $searach . '%'])
                ->where(['appid' => $appid])
                ->order('id desc')
                ->count();
        }
        $page = $list->render();
        $this->assign('page', $page);
        $this->assign('user', $list);
        $this->assign('searach', $searach);
        $this->assign('count', $count);
        $this->assign('appid', $appid);
        return view();
    }

    //编辑
    public function edit($id = null, $appid = null, Request $request)
    {
        $this->assign('data', null);
        if ($this->request->isPost()) {
            $data = $request->post();
            $code = $request->post('code');
			$no_pay = $request->post('no_pay');
			$no_master = $request->post('no_master');
            if(!empty($code)){
                $data['code'] = 1;
            }else{
                $data['code'] = 0;
            }
			if(empty($no_pay)){
                $data['no_pay'] = 1;
            }else{
                $data['no_pay'] = 0;
            }
			if(empty($no_master)){
                $data['no_master'] = 1;
            }else{
                $data['no_master'] = 0;
            }
            if (isset($data['file'])) {
                unset($data['file']);
            }
            $result = Db::name('hb_activity')->where(['id' => $data['id'], 'appid' => $data['appid']])->update($data);
            if ($result > 0) {
                $this->assign('data', ['state' => 1, 'message' => '修改成功']);
            } else {
                $this->assign('data', ['state' => 2, 'message' => '修改失败']);
            }
        }
        $activity = Db::name('hb_activity')->where(['id' => $id, 'appid' => $appid])->find();
        $this->assign('activity', $activity);
        $this->assign('appid', $appid);
        $this->assign('id', $id);
        return view();
    }

    //信息编辑
    public function delete($appid = null,Request $request)
    {
        if($request->isPost()){
            $data = $request->post();
            $user_name = $data['user_name'];
            unset($data['user_name']);
            $result = Db::name('wx_users')->where(['user_name' => $user_name])->update($data);
            if ($result > 0) {
                return json(['state' => 1, 'message' => '修改成功']);
            } else {
                return json(['state' => 0, 'message' => '修改失败']);
            }
        }else{
            $users = Db::name('wx_users')->where(['user_name' => $appid])->find();
            $this->assign('user', $users);
            return view();
        }
    }
    //删除
    public function deletes($id = null, $appid = null)
    {
        $result = Db::name('hb_activity')->where(['id' => $id, 'appid' => $appid])->delete();
        if ($result > 0) {
            return json(['state' => 1, 'message' => '删除成功']);
        } else {
            return json(['state' => 1, 'message' => '删除失败']);
        }
    }

    //投诉
    public function ts($id = null)
    {
        $list = Db::name('hb_ts')->where(['hid'=>$id])->select();
        $this->assign('list',$list);
        return view();
    }
}