<?php
namespace app\admin\controller;

use think\Controller;
use app\admin\controller\Checklogin;
use think\Db;
use app\admin\model\Revert;

//基础设置模块
class Edit extends Checklogin
{
    public function edit_f_menu($code = null)
    {
        if ($this->request->isPost()) {
            $data['f_name'] = input('name');
            $data['rank'] = input('rank');
            $data['f_type'] = input('menu_type');
            $keyword = input('keyword');
            $url = input('url');
            $code = input('code');
            $eid = input('eid');
            if (empty($data['f_name']) || $data['f_name'] == '') {
                $this->error('菜单名称不能为空');
            } else if ($data['f_type'] == '1' && empty($keyword)) {
                $this->error('关键字不能为空');
            } else if ($data['f_type'] == '2' && empty($url)) {
                $this->error('链接地址不能为空');
            } else if (!empty($keyword) && $data['f_type'] == '1') {
                $hf_text = Db::name('hf_text')->where(['keyword' => $keyword])->find();
                if (!$hf_text) {
                    $this->error('关键词未添加', '/index/text');
                } else {
                    $data['f_key'] = $hf_text['id'];
                }
            }
            if ($data['f_type'] == '1') {
                $data['f_text'] = $keyword;
            } else if ($data['f_type'] == '2') {
                $data['f_url'] = $url;
            }
            $result = Db::name('f_menu')->where(['id' => $eid, 'code' => $code])->update($data);
            if ($result > 0) {
                $this->success('修改成功', '/index/menu');
            } else {
                $this->error('修改失败');
            }
        } else {
            $revert = new Revert();
            $menu = $revert->getMenu();
            $data = Db::name('f_menu')->where(['code' => $code])->find();
            $this->assign('data', $data);
            $this->assign('menu', $menu);
            return view();
        }
    }

    public function edit_son_menu($code = null)
    {
        if ($this->request->isPost()) {
            $data['f_id'] = trim(input('fid'));
            $data['son_name'] = input('name');
            $data['son_rank'] = input('rank');
            $data['son_type'] = input('menu_type');
            $keyword = input('keyword');
            $url = input('url');
            $code = input('code');
            $eid = input('eid');

            if (empty($data['son_name']) || $data['son_name'] == '') {
                $this->error('菜单名称不能为空');
            } else if ($data['son_type'] == '1' && empty($keyword)) {
                $this->error('关键字不能为空');
            } else if ($data['son_type'] == '2' && empty($url)) {
                $this->error('链接地址不能为空');
            } else if (!empty($keyword) && $data['son_type'] == '1') {
                $hf_text = Db::name('hf_text')->where(['keyword' => $keyword])->find();
                if (!$hf_text) {
                    $this->error('关键词未添加', '/index/text');
                } else {
                    $data['son_key'] = $hf_text['id'];
                }
            }

            if ($data['son_type'] == '1') {
                $data['son_text'] = $keyword;
            } else if ($data['son_type'] == '2') {
                $data['son_url'] = $url;
            }
            $result = Db::name('son_menu')->where(['id'=>$eid,'code'=>$code])->update($data);
            if ($result > 0) {
                $this->success('修改成功', '/index/menu');
            } else {
                $this->error('修改失败');
            }
        } else {
            $revert = new Revert();
            $menu = $revert->getMenu();
            $data = Db::name('son_menu')->where(['code' => $code])->find();
            $f = Db::name('f_menu')->where(['id' => $data['f_id']])->find();//父级
            $this->assign('data', $data);
            $this->assign('f', $f);
            $this->assign('menu', $menu);
            return view();
        }
    }
  //删除一级菜单
    public function delete_f_menu($code)
    {
        $id = Db::name('f_menu')->where(['code' => $code])->field('id')->find();
        Db::name('son_menu')->where(['f_id' => $id])->delete();
        $result = Db::name('f_menu')->where(['code' => $code])->delete();
        if ($result > 0) {
            return json(['state' => 1, 'message' => '已删除']);
        } else {
            return json(['state' => 0, 'message' => '删除失败']);
        }
    }

    //删除二级菜单
    public function delete_son_menu($code)
    {
        $result = Db::name('son_menu')->where(['code' => $code])->delete();
        if ($result > 0) {
            return json(['state' => 1, 'message' => '已删除']);
        } else {
            return json(['state' => 0, 'message' => '删除失败']);
        }
    }
  //删除图片回复
    public function delete_img($code)
    {
        $result = Db::name('hf_text')->where(['code'=>$code])->delete();
        if ($result > 0) {
            return json(['state' => 1, 'message' => '已删除']);
        } else {
            return json(['state' => 0, 'message' => '删除失败']);
        }
    }
  //删除文字回复
    public function delete_text($code)
    {
        $result = Db::name('hf_text')->where(['code' => $code])->delete();
        if ($result > 0) {
            return json(['state' => 1, 'message' => '已删除']);
        } else {
            return json(['state' => 0, 'message' => '删除失败']);
        }
    }
}