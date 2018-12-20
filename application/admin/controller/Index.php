<?php
namespace app\admin\controller;

use think\Controller;
use app\admin\controller\Checklogin;
use think\Db;
use app\diuber\model\WxAccessToken;
use app\admin\model\WxRevert;

//基础设置模块
class Index extends Checklogin
{
    public function getAppId()
    {
        $userId = session('nowUser')['id'];
        $appid = Db::name('wx_authorizer')->where(['user_id' => $userId])->field('authorizer_appid')->find();
        return $appid['authorizer_appid'];
    }

    //关注时回复
    public function index()
    {
        if ($this->request->isPost()) {
            $content = input('content');
            $appid = $this->getAppId();
            $revert = Db::name('hf_revert')->where(['appid' => $appid])->field('id')->find();
            if ($revert) {
                $resulet = Db::name('hf_revert')->where(['id' => $revert['id']])->update(['set_text' => $content]);
            } else {
                $resulet = Db::name('hf_revert')->insert(['appid' => $appid, 'set_text' => $content]);
            }
            if ($resulet > 0) {
                $this->success('设置成功', "/index/index");
            } else {
                $this->error('设置失败');
            }
        } else {
          	$appid = $this->getAppId();
			$result = Db::name('hf_revert')->where(['appid'=>$appid])->field('set_text')->find();
          	$this->assign('data',$result['set_text']);
            return view();
        }
    }

    //文本回复
    public function text()
    {
        if ($this->request->isPost()) {
            $data['keyword'] = trim(input('keyword'));
            $data['type'] = trim(input('type'));
            $data['set_text'] = input('text');

            if (empty($data['keyword'])) {
                $this->error('关键词不能为空');
            } else if (empty($data['set_text'])) {
                $this->error('内容不能为空');
            }
          	$keyword = Db::name('hf_text')->where(['keyword'=>$data['keyword']])->find();
            if($keyword){
                $this->error('关键词已添加');
            }
          	$data['set_time'] = date("Y-m-d H:i:s");
            $data['appid'] = $this->getAppId();
            $data['code'] = md5(rand_string());
            $resulet = Db::name('hf_text')->insert($data);
            if ($resulet > 0) {
                $this->success('设置成功', "/index/text");
            } else {
                $this->error('设置失败');
            }
        } else {
          	$appid = $this->getAppId();
            $list = Db::name('hf_text')->where(['appid'=>$appid,'revert_type'=>1])->paginate(20);
            $page = $list->render();
            $this->assign('data',$list);
            $this->assign('page',$page);
            return view();
        }
    }

    //图片回复
    public function img()
    {
       if($this->request->isPost()){
            $data['keyword'] = trim(input('keyword'));
            $data['type'] = input('type');
            $data['media_id'] = input('media_id');
            $data['img_url'] = input('pic'); //图片的url
            if(empty($data['keyword'])){
                return json(['state'=>0,'message'=>'关键词不能为空']);
            } else if(empty($data['img_url']) || empty($data['media_id'])){
                return json(['state'=>0,'message'=>'请先上传图片']);
            }
            $keyword = Db::name('hf_text')->where(['keyword'=>$data['keyword']])->find();
            if($keyword){
                return json(['state'=>0,'message'=>'关键词已添加']);
            }
         	$data['set_time'] = date("Y-m-d H:i:s");
            $data['revert_type'] = 2;
            $data['appid'] = $this->getAppId();
            $data['code'] = md5(rand_string());
            $result = Db::name('hf_text')->insert($data);
            if($result > 0){
                return json(['state'=>1,'message'=>'添加成功']);
            }else{
                return json(['state'=>0,'message'=>'添加失败']);
            }
        }else{
         	$appid = $this->getAppId();
            $list = Db::name('hf_text')->where(['appid' => $appid, 'revert_type' => 2])->paginate(20);
            $page = $list->render();
            $this->assign('data', $list);
            $this->assign('page', $page);
            return view();
        }
    }

    //自定义菜单
    public function menu()
    {
        if ($this->request->isPost()) {
            $fid = trim(input('fid')); //父菜单id 为0就是设置父菜单
            $name = input('name'); //菜单名称
            $rank = intval(input('rank'));  //排序
            $menu_type = trim(input('menu_type')); //菜单类型
            $keyword = input('keyword'); //要触发的关键字
            $url = input('url'); //要链接到的URL地址

            if (empty($name) || $name == '') {
                $this->error('菜单名称不能为空');
            } else if ($menu_type == '1' && empty($keyword)) {
                $this->error('关键字不能为空');
            } else if ($menu_type == '2' && empty($url)) {
                $this->error('链接地址不能为空');
            } else if (!empty($keyword) && $menu_type == '1') {
                $hf_text = Db::name('hf_text')->where(['keyword' => $keyword])->find();
                if (!$hf_text) {
                    $this->error('关键词未添加','/index/text');
                }else{
                    $data['f_key'] = $hf_text['id'];
                    $son['son_key'] = $hf_text['id'];
                }
            }
            $data['appid'] = $this->getAppId();
            $result = 0;
            if (empty($fid) || $fid == '') { //设置父菜单
                $count = Db::name('f_menu')->where(['appid' => $data['appid']])->count();
                if ($count >= 3) {
                    $this->error('最多添加3个一级菜单');
                }
                $data['f_name'] = $name;
                $data['rank'] = $rank;
                $data['f_type'] = $menu_type;
                $data['f_url'] = $url;
                $data['f_text'] = $keyword;
              	$data['code'] = md5(rand_string());
                $result = Db::name('f_menu')->insert($data);
            } else if (!empty($fid)) {   //设置子菜单
                $count = Db::name('son_menu')->where(['f_id' => $fid])->count();
                if ($count >= 5) {
                    $this->error('最多添加5个二级菜单');
                }
                $son['son_name'] = $name;
                $son['son_rank'] = $rank;
                $son['son_type'] = $menu_type;
                $son['son_url'] = $url;
                $son['son_text'] = $keyword;
                $son['f_id'] = $fid;
              	$son['code'] = md5(rand_string());
                $result = Db::name('son_menu')->insert($son);
            }
            if ($result > 0) {
                $this->success('添加成功', '/index/menu');
            } else {
                $this->error('添加失败');
            }
        } else {
            $appid = $this->getAppId();
            $menu = Db::name('f_menu')->where(['appid' => $appid])->order('rank desc')->select();
            for ($a = 0; $a < count($menu); $a++) {
                $menu[$a][] = Db::name('son_menu')->where(['f_id' => $menu[$a]['id']])->select();
            }
            $this->assign('menu', $menu);
            return view();
        }
    }
   //生成微信端自定义菜单
    public function setWeiMenu()
    {
     header("Content-type:application/json:charset=utf-8");
        $appid = $this->getAppId();
        $menu = Db::name('f_menu')->where(['appid'=>$appid])->order('rank desc')->select();
       
        $wxModel = new WxRevert();
        $authorizerAccessToken = $wxModel->getAuthorizerAccessToken();
        if(!$authorizerAccessToken){
            $this->error('获取authorizer_access_token失败');
        }
        $url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token={$authorizerAccessToken}";
		
      	$m = '{
            "button":[';
      $s = '';
        for($a=0;$a<count($menu);$a++){
            $son_menu = Db::name('son_menu')->where(['f_id'=>$menu[$a]['id']])->order('son_rank desc')->select();
          	$data = array();
            if(!$son_menu){
                $data['name'] = $menu[$a]['f_name'];
                if($menu[$a]['f_type'] == 1){
                    $data['type'] = 'click';
                    $data['key'] =$menu[$a]['f_key'];
                } else if($menu[$a]['f_type'] == 2){
                    $data['type'] = 'view';
                    $data['url'] = $menu[$a]['f_url'];
                }
              if($a == count($menu)-1){
                     $m.= json_encode($data,JSON_UNESCAPED_UNICODE);
                }else{
                     $m.= json_encode($data,JSON_UNESCAPED_UNICODE).',';
                }
            }else{
                 $name = '"'.$menu[$a]['f_name'].'"';
                $m .= "{\"name\":{$name},\"sub_button\":";
                $son['name'] = $menu[$a]['f_name'];
                $s = '[';
                for($b=0;$b<count($son_menu);$b++){
                    $son['sub_button']['name'] = $son_menu[$b]['son_name'];
                    if($son_menu[$b]['son_type'] == 1){
                        $son['sub_button']['type'] = 'click';
                        $son['sub_button']['key'] = $son_menu[$b]['son_key'];
                    } else if($son_menu[$b]['son_type'] == 2){
                        $son['sub_button']['type'] = 'view';
                        $son['sub_button']['url'] = $son_menu[$b]['son_url'];
                    }
                  	$count = count($son_menu)-1;
					if($b == $count){
                    	 $s .= json_encode($son['sub_button'],JSON_UNESCAPED_UNICODE);
                    }else{
                    	 $s .= json_encode($son['sub_button'],JSON_UNESCAPED_UNICODE).',';
                    }
                }
                $s .= ']}';
              if($a == count($menu)-1){
					 $m.= $s;
                }else{
					 $m.= $s.',';
                }
            }
        }
        $m .= ']}';
        $result = $wxModel->https_request($url,$m);
     	$result = json_decode($result,true);
        if($result['errcode'] == '0'){
            $this->success('生成微信菜单成功','/index/menu');
        }else{
            $this->error('生成失败errcode:'.$result['errcode']);
        }
    }
}