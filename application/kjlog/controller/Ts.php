<?php
namespace app\kjlog\controller;

use think\Controller;
use think\Db;

class Ts extends Controller
{
    public function show($hid = null)
    {
        $this->assign('hid', $hid);
        return view('ts');
    }

    public function up()
    {
        $data['reason'] = input('reason'); //投诉原因
        $data['describe'] = input('describe'); //投诉描述
        $data['contact'] = input('contact'); //投诉描述
        $data['hid'] = input('hid');
        $result = Db::name('kj_ts')->insert($data);
        if ($result > 0){
            return json(['state'=>1,'message'=>'投诉成功']);
        } else {
            return json(['state'=>0,'message'=>'投诉失败']);
        }
    }

    public function test()
    {
        $tpl = new tpl("ts");
        $tpl->display("index.html");
    }

    public function ch()
    {
        $tpl = new tpl("ts");
        $tpl->display("ch.html");
    }
}