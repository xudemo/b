<?php
namespace app\my\controller;

use app\my\controller\Checklogin;
use think\Db;

class User extends Checklogin
{
    public function index($searach=null)
    {
        if(empty($searach)){
            $list = Db::name('user')->order('id desc')->paginate(15);
        }else{
            $list = Db::name('user')->where('id|nickname|phone|qq|email', 'like', ['%' . $searach . '%'])->order('id desc')->paginate(15,false,['query'=>['searach'=>$searach]]);
        }
        $page = $list->render();
        $this->assign('page', $page);
        $this->assign('user',$list);
        $this->assign('searach',$searach);
        return view();
    }

    //编辑
    public function edit($code=null){
        if($this->request->isPost()){
            $nickname = input('nickname');
            $data['phone'] = input('phone');
            $data['qq'] = input('qq');
            $data['email'] = input('email');
            $code = input('code');
            $result = Db::name('user')->where(['nickname'=>$nickname,'code'=>$code])->update($data);
            if($result > 0){
                return json(['state'=>1,'message'=>'修改成功']);
            }else{
                return json(['state'=>0,'message'=>'修改失败']);
            }
        }else{
            $user = Db::name('user')->where(['code'=>$code])->find();
            $this->assign('user',$user);
            return view();
        }
    }

    //禁用
    public function block_up($code){
        $result = Db::name('user')->where(['code'=>$code])->update(['status'=>2]);
        if($result > 0){
            return json(['state'=>1,'message'=>'修改成功']);
        }else{
            return json(['state'=>0,'message'=>'修改失败']);
        }
    }
    //启用
    public function enabled($code){
        $result = Db::name('user')->where(['code'=>$code])->update(['status'=>1]);
        if($result > 0){
            return json(['state'=>1,'message'=>'修改成功']);
        }else{
            return json(['state'=>0,'message'=>'修改失败']);
        }
    }
    //修改密码
    public function edit_pwd($code=null){
        if($this->request->isPost()){
            $nickname = input('nickname');
            $pwd = input('pwd');
            $repwd = input('repwd');
            $code = input('code');
            if(empty($pwd)){
                return json(['state'=>0,'message'=>'密码不能为空']);
            }else if(!preg_match('/^(\w){6,16}$/',$pwd)){
                return json(['state'=>0,'message'=>'密码格式错误']);
            } else if($pwd != $repwd){
                return json(['state'=>0,'message'=>'两次密码不一致']);
            }
            $u = Db::name('user')->where(['nickname'=>$nickname,'code'=>$code])->find();
            $password = md5($pwd.$u['rand_str']);
            $result = Db::name('user')->where(['nickname'=>$nickname,'code'=>$code])->update(['password'=>$password]);
            if($result > 0){
                return json(['state'=>1,'message'=>'修改成功']);
            }else{
                return json(['state'=>0,'message'=>'修改失败']);
            }
        }else{
            $user = Db::name('user')->where(['code'=>$code])->find();
            $this->assign('user',$user);
            return view();
        }
    }
}