<?php
namespace app\home\controller;
use think\Controller;
class Checklogin extends Controller
{
    function __construct()
    {
        if(empty(session("nowUser"))){
            $this->redirect('/');
        }
        parent::__construct();
    }
}