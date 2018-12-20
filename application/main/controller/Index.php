<?php
namespace app\main\controller;

use app\main\controller\Check;
use think\Db;

//后台首页
class Index extends Check
{
    public function index()
    {
        header("Content-type:text/html;charset=utf-8");
        //halt(cookie('menu'));
        $this->assign('list',cookie('menu'));
        return view();
    }
}
