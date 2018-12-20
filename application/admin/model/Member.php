<?php
namespace app\admin\model;

use think\Db;

class Member
{
    //设置备注
    function setNode($id, $type, $data)
    {
        $result = 0;
        switch ($type) {
            case 'kj':
                $result = $this->setKjNode($id, $data);
                break;
            case 'hb':
                $result = $this->setHbNode($id, $data);
                break;
        }
        return $result;
    }

    function setKjNode($id, $data)
    {
        $result = Db::name('kj_member')->where(['id' => $id])->update(['node'=>$data]);
        return $result;
    }

    function setHbNode($id, $data)
    {
        $result = Db::name('hb_member')->where(['id' => $id])->update(['node'=>$data]);
        return $result;
    }
}