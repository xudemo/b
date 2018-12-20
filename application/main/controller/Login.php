<?php
namespace app\main\controller;

use think\Controller;
use think\Db;
use think\Request;
use app\main\model\Menus;

class Login extends Controller
{
    public function index(Request $request){
        if($request->isPost()){
            $name = input('name');
            $pwd = input('pwd');
            $isName = Db::name('admin')->where(['name'=>$name])->find();
            if(!$isName){
                return json(['state'=>0,'message'=>'账号不存在']);
            }else{
                $isPwd = md5($pwd.$isName['rand_str']);
                if($isPwd != $isName['pwd']){
                    return json(['state'=>0,'message'=>'密码错误']);
                }else{
                    Menus::setMenu();
                    session('adminUser',$isName);
                    return json(['state'=>1,'message'=>'登陆成功']);
                }
            }
        }else{
            return view();
        }
    }
}