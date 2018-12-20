<?php
namespace app\kjlog\model;

use think\Controller;

class Exception extends Controller
{
    public static function exception($message)
    {
        header("Location:/kjlog/index/message?message={$message}");
        exit;
    }
}