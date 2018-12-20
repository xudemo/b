<?php
namespace app\my\controller;

use app\my\controller\Checklogin;
use think\Db;

//投诉列表
class Ts extends Checklogin
{
    public function table($id = null, $type = null)
    {
        $list = $this->tsTable($id,$type);
        $this->assign('list',$list);
        return $this->fetch();
    }

    public function tsTable($id,$type)
    {
        $result = false;
        switch ($type) {
            case 'kj':
                $result = $this->getKjTsModel($id);
                break;
            case 'hb':
                $result = $this->getHbTsModel($id);
                break;
        }
        return $result;
    }

    public function getKjTsModel($id)
    {
        $list = Db::name('kj_ts')->where(['hid'=>$id])->select();
        return $list;
    }

    public function getHbTsModel($id)
    {
        $list = Db::name('hb_ts')->where(['hid'=>$id])->select();
        return $list;
    }
}
