<?php
namespace app\my\controller;

use think\Controller;
use think\Request;

class Checklogin extends Controller
{
    function __construct(Request $request)
    {
        //判断是否登录
        if(empty(session("adminUser"))){
            $this->redirect('/my/login/index');
        }
        parent::__construct();
    }
}
