<?php
namespace app\my\controller;

use app\main\controller\Check;
use think\Db;
use think\Request;
use app\main\model\Menus;

class Menu extends Check
{
    public function index()
    {
        $list = Db::name('admin_menu')
            ->where(['grade' => 1])
            ->order('sort asc')
            ->select();
        for ($i = 0; $i < count($list); $i++) {
            $list[$i]['bottom_menu'] = Db::name('admin_menu')
                ->where(['top_menu' => $list[$i]['id']])
                ->order('sort asc')
                ->select();
        }
        $list = $this->lists($list);
        $this->assign('list', $list);
        return view();
    }

    //添加菜单
    public function add(Request $request)
    {
        if ($request->isPost()) {
            $data = $request->post();
            if ($data['grade'] == '1') {
                unset($data['top_menu']);
            }
            $result = Db::name('admin_menu')->insert($data);
            if ($result > 0) {
                return json(['code' => 200, 'msg' => '添加成功']);
            } else {
                return json(['code' => 400, 'msg' => '添加失败']);
            }
        } else {
            $list = Db::name('admin_menu')
                ->where(['grade' => 1])
                ->field('id,menu_name')
                ->select();
            $this->assign('list', $list);
            return view();
        }
    }

    //修改状态 显示或隐藏
    public function status($id = null, $status = null)
    {
        $result = Db::name('admin_menu')->where(['id' => $id])->update(['status' => $status]);
        if ($result > 0) {
            return json(['code' => 200, 'msg' => '修改成功']);
        } else {
            return json(['code' => 400, 'msg' => '修改失败']);
        }
    }

    //编辑
    public function edit($id = null, Request $request)
    {
        if ($request->isPost()) {
            $data = $request->post();
            unset($data['id']);
            $result = Db::name('admin_menu')->where(['id' => $id])->update($data);
            if ($result > 0) {
                return json(['code' => 200, 'msg' => '修改成功']);
            } else {
                return json(['code' => 400, 'msg' => '修改失败']);
            }
        } else {
            $menu = Db::name('admin_menu')->where(['id' => $id])->find();
            $list = Db::name('admin_menu')
                ->where(['grade' => 1])
                ->field('id,menu_name')
                ->select();

            $this->assign('menu', $menu);
            $this->assign('list', $list);
            return view();
        }
    }

    //删除
    public function delete($id = null, $grade = null)
    {
        if ($grade == 1) {
            Db::name('admin_menu')->where(['top_menu' => $id])->delete();
        }
        $result = Db::name('admin_menu')->where(['id' => $id])->delete();
        if ($result > 0) {
            return json(['code' => 200, 'msg' => '删除成功']);
        } else {
            return json(['code' => 400, 'msg' => '删除失败']);
        }
    }

    //修改排序
    public function sort($id = null, $sort = null)
    {
        $result = Db::name('admin_menu')->where(['id' => $id])->update(['sort' => $sort]);
        if ($result > 0) {
            return json(['code' => 200, 'msg' => '修改成功']);
        } else {
            return json(['code' => 400, 'msg' => '修改失败']);
        }
    }

    //更新菜单
    public function menu()
    {
        $result = Menus::setMenu();
        if ($result) {
            return json(['code' => 200, 'msg' => '更新成功']);
        } else {
            return json(['code' => 400, 'msg' => '更新失败']);
        }
    }

    public function lists($list)
    {
        $retult = array();
        $a = 0;
        $c = 0;
        $d = 1;
        for ($i = 0; $i < count($list); $i++) {
            $retult[$a] = $list[$i];
            $retult[$a]['fid'] = 0;
            unset($retult[$a]['bottom_menu']);
            $a++;
            $c += $d;
            $bottom_menu = $list[$i]['bottom_menu'];
            for ($b = 0; $b < count($bottom_menu); $b++) {
                $retult[$a] = $list[$i]['bottom_menu'][$b];
                $retult[$a]['fid'] = $c;
                $a++;
                $d++;
            }
        }
        return $retult;
    }
}