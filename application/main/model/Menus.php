<?php
namespace app\main\model;

use think\Db;

class Menus
{
    public static function setMenu()
    {
        $list = Db::name('admin_menu')->where(['grade' => 1, 'status' => 0])->order('sort asc')->select();
        for ($i = 0; $i < count($list); $i++) {
            $list[$i]['bottom_menu'] = Db::name('admin_menu')->where(['top_menu' => $list[$i]['id'], 'status' => 0])->order('sort asc')->select();
        }

        if($list){
            cookie('menu', $list);
            return true;
        }else{
            return false;
        }
    }
}
