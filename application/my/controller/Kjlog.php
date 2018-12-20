<?php
namespace app\my\controller;

use app\my\controller\Checklogin;
use think\Controller;
use think\Db;
use think\Request;
use think\facade\App;
use app\my\model\Qiniu;

class Kjlog extends Checklogin
{
    public function index($searach = null)
    {
        if (empty($searach)) {
            $list = Db::name('wx_users')
                ->join('zn_user', 'zn_wx_users.user_id=zn_user.id')
                ->order('zn_wx_users.id desc')
                ->paginate(15);
        } else {
            $list = Db::name('wx_users')
                ->join('zn_user', 'zn_wx_users.user_id=zn_user.id')
                ->where('nick_name|nickname|phone', 'like', ['%' . $searach . '%'])
                ->order('zn_wx_users.id desc')
                ->paginate(15, false, ['query' => ['searach' => $searach]]);
        }

        $page = $list->render();
        $this->assign('page', $page);
        $this->assign('user', $list);
        $this->assign('searach', $searach);
        return view();
    }

    //砍价功能查询
    public function details($appid = null, $searach = null)
    {
        if (empty($searach)) {
            $list = Db::name('kj_activity')
                ->where(['appid' => $appid])
                ->order('id desc')
                ->paginate(15, false, ['query' => ['appid' => $appid]]);
            $count = Db::name('kj_activity')
                ->where(['appid' => $appid])
                ->order('id desc')
                ->count();
        } else {
            $list = Db::name('kj_activity')
                ->where('id|name', 'like', ['%' . $searach . '%'])
                ->where(['appid' => $appid])
                ->order('id desc')
                ->paginate(15, false, ['query' => ['appid' => $appid]]);
            $count = Db::name('kj_activity')
                ->where('id|name', 'like', ['%' . $searach . '%'])
                ->where(['appid' => $appid])
                ->order('id desc')
                ->count();
        }
        $page = $list->render();
        $this->assign('page', $page);
        $this->assign('user', $list);
        $this->assign('searach', $searach);
        $this->assign('count', $count);
        $this->assign('appid', $appid);
        return view();
    }

    //开通砍价功能
    public function activity($appid = null, Request $request)
    {
        if ($this->request->isPost()) {
            $data = $request->post();
            $code = $request->post('code');
			$attend = $request->post('attend');
            if(!empty($code)){
                $data['code'] = 1;
            }else{
                $data['code'] = 0;
            }
			if(!empty($attend)){
                $data['attend'] = 1;
            }else{
                $data['attend'] = 0;
            }
            if(isset($data['file'])){
                unset($data['file']);
            }
            $result = Db::name('kj_activity')->insert($data);
			$this->assign("begin_time",$data['begin_time']);
            $this->assign("finish_time",$data['finish_time']);
            if ($result > 0) {
                $this->assign('data', ['state' => 1, 'message' => '添加成功']);
                $this->assign('appid', $appid);
                return view();
            } else {
                $this->assign('data', ['state' => 2, 'message' => '添加失败']);
                $this->assign('appid', $appid);
                return view();
            }
        } else {
        	$begin_time = date("Y-m-d H:i");
            $finish_time = date("Y-m-d H:i",strtotime('+8 day'));
            $begin_time = str_replace(" ","T",$begin_time);
            $finish_time = str_replace(" ","T",$finish_time);

            $this->assign("begin_time",$begin_time);
            $this->assign("finish_time",$finish_time);
            $this->assign('data', null);
            $this->assign('appid', $appid);
            return view();
        }
    }

    //编辑砍价活动
    public function edit($id=null,$appid = null, Request $request)
    {
        $this->assign('data',null);
        if($this->request->isPost()){
            $data = $request->post();
            $code = $request->post('code');
			$attend = $request->post('attend');
            if(!empty($code)){
                $data['code'] = 1;
            }else{
                $data['code'] = 0;
            }
			if(!empty($attend)){
                $data['attend'] = 1;
            }else{
                $data['attend'] = 0;
            }
            if(isset($data['file'])){
                unset($data['file']);
            }
         //   $data['begin_time'] = str_replace("T"," ",$data['begin_time']);
         //   $data['finish_time'] = str_replace("T"," ",$data['finish_time']);
            $result = Db::name('kj_activity')->where(['id'=>$data['id'],'appid'=>$data['appid']])->update($data);
            if ($result > 0) {
                $this->assign('data', ['state' => 1, 'message' => '修改成功']);
            } else {
                $this->assign('data', ['state' => 2, 'message' => '修改失败']);
            }
        }
        $activity = Db::name('kj_activity')->where(['id'=>$id,'appid'=>$appid])->find();
        $this->assign('activity',$activity);
        $this->assign('appid',$appid);
        $this->assign('id',$id);
        return view();

    }
    //上传图片
    public function sc($img=null)
    {
//      $public_img = $_FILES;
//      $filetype = pathinfo($public_img['file']['name']);
//      $imgPath = time() . rand(0, 100) . '.' . $filetype['extension'];
//      $result = move_uploaded_file($public_img['file']['tmp_name'], "./uploads/" . $imgPath);
//      if ($result) {
//          // if(!empty($img) && file_exists($img)){
//          //     unlink($img);
//          // }
//          return json(['state' => 1, 'message' => '上传成功', 'data' => "/uploads/" . $imgPath]);
//      } else {
//          return json(['state' => 1, 'message' => '上传失败']);
//      }
		$file = $_FILES['file'];
        $qiniu = new Qiniu();
        $result = $qiniu->uploadQiNiu($file);
        if(isset($result[0]['key']) && !empty($result[0]['key'])){
            return json(['state' => 1, 'message' => '上传成功', 'data' => QI_URL.$result[0]['key']]);
        }else{
            return json(['state' => 1, 'message' => '上传失败']);
        }
    }
	//预览
    public function img($img,$width,$height,$x,$y,$appid)
    {
//        if(file_exists("./liuimg/".$appid."bj.jpg")){
//          unlink("./liuimg/".$appid."bj.jpg");
//        }
        $rootPath = App::getRootPath();
        $filename = $rootPath . 'public/twocode/two_code.png'; //二维码图片
        $scanpic = $rootPath . 'public' . $img; //背景图片
        if (!file_exists('./liuimg')) {
            mkdir('./liuimg');
        }
        $page = $this->setimg($img,$filename,$appid,$width,$height,$x,$y);
        return $page;
    }
    function setimg($scanpic, $photo, $appid,$width,$height,$x,$y)//背景图片 二维码 id
    {
     
        $img = imagecreatefromjpeg($scanpic); //img 是背景图片
        $pic = imagecreatefrompng($photo);//pic 是 二维码图片
        $arrlist = getimagesize($photo);
        $image_p = imagecreatetruecolor($width, $height);
        imagecopyresampled($image_p, $pic, 0, 0, 0, 0, $width, $height, $arrlist[0], $arrlist[1]); //修改二维码图片大小
        imagecopymerge($img, $image_p, $x, $y, 0, 0, $width, $height, 100);
        $savefile = strtotime(date("Y-m-d H:i:s", time()));
        $v = imagejpeg($img, "./liuimg/".$appid."bj.jpg");
        imagedestroy($img);
        imagedestroy($pic);
        imagedestroy($image_p);
        $page = "/liuimg/".$appid."bj.jpg";
        return $page;
    }
//信息编辑
    public function delete($appid=null,Request $request)
    {
        if($this->request->isPost()){
            $data = $request->post();
            $user_name = $data['user_name'];
            unset($data['user_name']);
            $result = Db::name('wx_users')->where(['user_name'=>$user_name])->update($data);
            if($result > 0){
                return json(['state'=>1,'message'=>'修改成功']);
            }else{
                return json(['state'=>0,'message'=>'修改失败']);
            }
        }else{
            $users = Db::name('wx_users')->where(['user_name'=>$appid])->find();
            $this->assign('user',$users);
            return view();
        }
    }

//删除
    public function deletes($id = null, $appid = null)
    {
        $result = Db::name('kj_activity')->where(['id' => $id, 'appid' => $appid])->delete();
        if ($result > 0) {
            return json(['state' => 1, 'message' => '删除成功']);
        } else {
            return json(['state' => 1, 'message' => '删除失败']);
        }
    }

    //投诉
    public function ts($id = null)
    {
        $list = Db::name('kj_ts')->where(['hid'=>$id])->select();
        $this->assign('list',$list);
        return view();
    }
    
    public function pay($appid)
    {
        $url = 'http://www.znote.com.cn/my/example/jsapi?appid='.$appid;
        $this->assign('url',$url);
        return view();
    }
}