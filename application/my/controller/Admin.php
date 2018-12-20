<?php
namespace app\my\controller;

use app\my\controller\Checklogin;
use think\Db;

class Admin extends Checklogin
{

    public function index($searach = null)
    {
        if (empty($searach)) {
            $list = Db::name('admin')->paginate(15);
        } else {
            $list = Db::name('admin')->where('id|name', 'like', ['%' . $searach . '%'])->paginate(15);
        }
        $page = $list->render();
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->assign('searach', $searach);
        return view();
    }

    //编辑
    public function edit($code = null)
    {
        if ($this->request->isPost()) {
            $name = input('name');
            $data['authority'] = input('authority');
            $pwd = input('pwd');
            $repwd = input('repwd');
            $code = input('code');
            $admin = Db::name('admin')->where(['code' => $code])->find();
            if (!empty($pwd) && $pwd != $repwd) {
                return json(['state' => '0', 'message' => '两次密码不一致']);
            } else if (!empty($pwd) && $pwd == $repwd) {
                $data['pwd'] = md5($pwd . $admin['rand_str']);
            }
            $result = Db::name('admin')->where(['name' => $name, 'code' => $code])->update($data);
            if($result > 0){
                return json(['state' => '1', 'message' => '修改成功']);
            }else{
                return json(['state' => '0', 'message' => '修改失败']);
            }
        } else {
            $admin = Db::name('admin')->where(['code' => $code])->find();
            $this->assign('admin', $admin);
            return view();
        }
    }

    //删除
    public function delete($code){
        $authority = Db::name('admin')->where(['code' => $code])->field('authority')->find();
        if($authority['authority'] == '1'){
            return json(['state' => '0', 'message' => '超级管理员不能删除']);
        }
        $result = Db::name('admin')->where(['code' => $code])->delete();
        if($result > 0){
            return json(['state' => '1', 'message' => '删除成功']);
        }else{
            return json(['state' => '0', 'message' => '删除失败']);
        }
    }

    //添加管理员
    public function add()
    {
        if ($this->request->isPost()) {
            $data['name'] = input('name');
            $pwd = input('pwd');
            $repwd = input('repwd');
            $data['authority'] = input('authority');
            if (empty($data['name'])) {
                return json(['state' => '0', 'message' => '登录名不能为空']);
            } else if (empty($pwd)) {
                return json(['state' => '0', 'message' => '密码不能为空']);
            } else if ($pwd != $repwd) {
                return json(['state' => '0', 'message' => '两次密码不一致']);
            }
            $name = Db::name('admin')->where(['name' => $data['name']])->find();
            if ($name) {
                return json(['state' => '0', 'message' => '账号已存在']);
            }
            $data['register_time'] = date('Y-m-d');//注册时间
            $data['rand_str'] = rand_string(); //获取随机密码附加字符
            $data['pwd'] = md5($pwd . $data['rand_str']);//密码
            $data['code'] = md5(rand_string()); //生成随机修改码
            $result = Db::name('admin')->insert($data);
            if ($result > 0) {
                return json(['state' => '1', 'message' => '添加成功']);
            } else {
                return json(['state' => '0', 'message' => '添加失败']);
            }
        } else {
            return view();
        }
    }
}