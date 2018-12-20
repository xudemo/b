<?php
namespace app\main\controller;

use think\Controller;
use think\Request;

class Check extends Controller
{
    function __construct(Request $request)
    {
        if (empty(session("adminUser"))) {
            $this->redirect('/main/login/index');
        }
        //判断操作是否有操作权限
        $action = $request->action();
        $result = $this->judgeSetAuth($action);
        if (!$result) {
            $module = $request->module();
            $controller = $request->controller();
            $msg = "您没得权限";
            $url = "/{$module}/{$controller}/index";
            $this->error($msg,$url,'',2);
            exit;
        }
        parent::__construct();
    }

    /*
     *判断 是否有修改编辑权限
     * @action string $action 当前访问的方法名
     * @result boole  $result 是否有权限
     */
    function judgeSetAuth($action)
    {
        $result = true;
        switch ($action) {
            case 'edit':
                $result = false;
                break;
            case 'delete':
                $result = false;
                break;
        }
        return $result;
    }
}
