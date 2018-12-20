<?php
namespace app\home\controller;

use app\home\controller\Checklogin;
use think\Db;
use think\Request;

class Edit extends Checklogin
{
    //资料修改
    public function index()
    {
        $userId = session('nowUser')['id'];
        if($this->request->isPost()){
            $nickname = input('nickname');
            $email = input('email');
            $phone = input('phone');
            if(!preg_match('/^((([a-z0-9_\.-]+)@([\da-z\.-]+)\.([a-z\.]{2,6}\;))*(([a-z0-9_\.-]+)@([\da-z\.-]+)\.([a-z\.]{2,6})))$/',$email)) {
                $this->error('邮箱格式错误');
            } else if(!preg_match('/^1\d{10}$/',$phone)){
                $this->error('手机号格式错误');
            }
            $result = Db::name('user')->where(['id'=>$userId,'nickname'=>$nickname])->update(['phone'=>$phone,'email'=>$email]);
            if($result>0){
                $this->success('修改成功',"/home/edit/index");
            }else{
                $this->error('修改失败');
            }
        }else{
            $user = Db::name('user')->where(['id'=>$userId])->field('nickname,email,phone')->find();
            $this->assign('user',$user);
            return view();
        }
    }
    //修改密码
    public function edit_pwd()
    {
        if($this->request->isPost()){
            $userId = session('nowUser')['id'];
            $rawpassword = input('rawpassword');//原始密码
            $newpassword = input('newpassword');//新密码
            $repassword = input('repassword');//确认密码
            $user = Db::name('user')->where(['id'=>$userId])->field('password,rand_str')->find();
            $password = md5($rawpassword.$user['rand_str']);

            if($password != $user['password']){
                $this->error('原始密码错误');
            } else if(!preg_match('/^(\w){6,16}$/',$newpassword)){
                $this->error('新密码格式错误');
            } else if($newpassword != $repassword){
                $this->error('两次密码不一致');
            }
            $pwd = md5($newpassword.$user['rand_str']);
            $result = Db::name('user')->where(['id'=>$userId])->update(['password'=>$pwd]);
            if($result>0){
                $this->success('修改成功',"/home/edit/edit_pwd");
            }else{
                $this->error('修改失败');
            }
        }else{
            return view();
        }
    }
  //编辑公众号信息
    public function public_num($user_name=null,Request $request)
    {
        $user = session('nowUser');
        if($this->request->isPost()){
            $file = $_FILES['file'];
            $Img = false;
            if(!empty($file['name'])){
                $filetype = pathinfo($file["name"]);
                if($filetype['extension'] != 'txt'){
                    $this->error('上传文件格式不对');
                }
                $name = $file['name'];
                $Img = move_uploaded_file($file['tmp_name'],'./'.$name);//保存文件
            }

            $data = $request->post();
            $user_name = $data['user_name'];
            unset($data['user_name']);
            $result = Db::name('wx_users')->where(['user_name'=>$user_name,'user_id'=>$user['id']])->update($data);
            if($result > 0 || $Img == true){
                $this->success('修改成功');
            }else{
                $this->error('修改失败');
            }
        }else{
            $users = Db::name('wx_users')->where(['user_name'=>$user_name,'user_id'=>$user['id']])->find();
            $this->assign('user',$users);
            return view();
        }
    }
}