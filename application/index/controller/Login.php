<?php
namespace app\index\controller;

use think\Controller;
use think\Request;
use app\index\model\User;

class Login extends Controller
{
    public function index()
    {
        return view();
    }
    //登陆判断
    public function login(Request $request){
        if($request->isPost()){
            $nickname = $request->post('nickname');
            $password = $request->post('password');
            if(empty($nickname)){
                return json(['state' => 0, 'message' => '账号不能为空']);
            } else if(empty($password)){
                return json(['state' => 0, 'message' => '密码不能为空']);
            }
            $model = new User();
            $result = $model->getUser(['nickname'=>$nickname]);
            if(!$result){
                return json(['state' => 0, 'message' => '用户不存在']);
            } else if($result['status'] == 2){
                return json(['state' => 0, 'message' => '账号已禁用']);
            }
            $isPwd = md5($password.$result['rand_str']);
            if($isPwd != $result['password']){
                return json(['state' => 0, 'message' => '密码错误']);
            }else{
                session('nowUser',$result);
                return json(['state' => 1, 'message' => '登陆成功']);
            }
        }else{
            return view('login/index');
        }
    }
}