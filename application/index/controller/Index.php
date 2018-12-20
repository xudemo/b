<?php
namespace app\index\controller;

use think\Controller;
use think\Cache;
use think\cache\driver\Redis;
use think\Db;

class Index extends Controller
{
    //首页
    public function index()
    {
        return view();
    }
    //功能
    public function func()
    {
        return view();

    }
    //案例
    public function cases()
    {
        return view();

    }
    //联系
    public function contact()
    {
        return view();
    }
    
    //注销
    public function exits(){
        session('nowUser',null);
        $this->redirect("/");
    }
}