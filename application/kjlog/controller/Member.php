<?php
namespace app\kjlog\controller;

use think\Controller;
use think\Db;

class Member extends Controller
{
    public function index($hid=null)
    {
        $m = cookie('member');
        $member = Db::name('kj_member')
        ->where(['hid'=>$hid,'masterid'=>$m['id']])
        ->select();
        $this->assign('member',$member);
        return view();
    }
}