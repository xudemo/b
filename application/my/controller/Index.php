<?php
namespace app\my\controller;

use app\my\controller\Checklogin;
use app\my\model\User;
use think\Db;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;

class Index extends Checklogin
{
    public function index()
    {
        return view();
    }
    public function welcome()
    {
        $model = new User();
        $countUser = $model->countUser();
        $this->assign('countUser',$countUser);
        return view();
    }
    //退出

    public function exits(){
        session('adminUser',null);
        $this->redirect("/my/login/index");
    }
	
	//前台登陆
    public function main($appid)
    {
        $user = Db::name("wx_users")->where(['user_name'=>$appid])->field('user_id')->find();
        $nowUser = Db::name("user")->where(['id'=>$user['user_id']])->find();
        session('nowUser',$nowUser);
		$this->redirect('/home/index/index');
    }
}