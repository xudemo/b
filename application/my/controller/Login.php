<?php
namespace app\my\controller;

use think\Controller;
use think\Db;

class Login extends Controller
{
    public function index(){
        if($this->request->isPost()){
            
        }else{
            return view();
        }
    }
    public function login(){
        if($this->request->isPost()){
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
                    session('adminUser',$isName);
                    return json(['state'=>1,'message'=>'登陆成功']);
                }
            }

        }
    }
}