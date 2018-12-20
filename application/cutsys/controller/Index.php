<?php
namespace app\cutsys\controller;

use think\Controller;
use think\Db;

class Index extends Controller{
    public function index(){
      if(strpos($_SERVER['QUERY_STRING'],'&')>0)
		{
			header("location:/index/welcome/$id");
			exit;
		}
      
    }
   	public function test(){
        var_dump('1314520');
    }
}