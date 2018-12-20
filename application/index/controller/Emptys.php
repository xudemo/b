<?php
namespace app\index\controller;

use think\Controller;

class Emptys extends Controller
{
    public function _empty(){
        $this->redirect('/');
    }
}