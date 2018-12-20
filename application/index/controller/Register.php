<?php
namespace app\index\controller;

use think\Controller;
use think\Request;
use app\index\model\User;
use think\Db;

class Register extends Controller
{
    public function index()
    {
        return view();

    }
    //注册判断
    public function register(Request $request){
        if($request->isPost()){
            $user = $request->post();
            $nickname = $request->post('nickname'); //用户名
            $password = $request->post('password'); //密码
            $repassword = $request->post('repassword'); //第二次密码
            $email = $request->post('email'); //email
            $qq = $request->post('qq'); //qq
            $phone = $request->post('phone'); //手机
            if(!preg_match('/^[a-zA-Z]{1}([a-zA-Z0-9]|[_]){4,12}$/',$nickname)){
                return json(['state' => 0, 'message' => '用户名长度为5-13位，必须以字母开头，可包含字母,数字,_']);
            } else if(!preg_match('/^(\w){6,16}$/',$password)){
                return json(['state' => 0, 'message' => '密码长度为6-16位，可包含字母,数字,_']);
            } else if($repassword != $password){
                return json(['state' => 0, 'message' => '两次密码不一致']);
            } else if(!preg_match('/^((([a-z0-9_\.-]+)@([\da-z\.-]+)\.([a-z\.]{2,6}\;))*(([a-z0-9_\.-]+)@([\da-z\.-]+)\.([a-z\.]{2,6})))$/',$email)){
                return json(['state' => 0, 'message' => '请输入正确的邮箱地址']);
            } else if(empty($qq) || $qq == ''){
                return json(['state' => 0, 'message' => 'QQ号不能为空']);
            } else if(!preg_match('/^1\d{10}$/',$phone)){
                return json(['state' => 0, 'message' => '请输入正确的手机号']);
            }
            $model = new User();
            $resultNickname = $model->getUser(['nickname'=>$nickname]);
            if($resultNickname){
                return json(['state' => 0, 'message' => '该账号已存在']);
            }
            $resultEmail = $model->getUser(['email'=>$email]);
            if($resultEmail){
                return json(['state' => 0, 'message' => '该邮箱已注册']);
            }
            $user['rand_str'] = rand_string(); //获取随机密码附加字符
            $user['password'] = md5($password.$user['rand_str']);//密码
            $user['register_time'] = date('Y-m-d');//注册时间
            $user['code'] = md5(rand_string());//生成修改码
            unset($user['repassword']);
            $insert = $model->insertUser($user);
            if($insert){
                $u = $model->getUser(['nickname'=>$nickname]);
                session('nowUser',$u);
                return json(['state' => 1, 'message' => '注册成功']);
            }else{
                return json(['state' => 0, 'message' => '注册失败,请联系客服']);
            }

        }else{
            return view('register/index');
        }
    }
}