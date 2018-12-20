<?php
namespace app\packet\controller;

use think\Controller;
use think\Db;
use qrcode\QRcode;
use think\facade\App;
use wechat\WeChat;
use app\kjlog\model\User;

//生成我的专属海报
class Poster extends Controller
{
    public function index()
    {
        $id = cookie('hbMember')['id'];
        $hid = cookie('hbMember')['hid'];
        $member = Db::name('hb_member')->where(['id' => $id])->field('nickname,pic')->find();
        $activity = Db::name('hb_activity')->where(['id' => $hid])->find();
//①判断是否存在专属二维码 不在就生成
        $filename = './poster/twocode/' . $id . 'qrcode.png';
        $url = URL . "/packet/index/welcome/hid/{$hid}/sid/{$id}";
        QRcode::png($url, $filename, "L", 6, 2);
        $filename = '/poster/twocode/' . $id . 'qrcode.png';
//②保存微信头像
        if (!file_exists('./userpic')) {
            mkdir('./userpic');
        }
        $this->downfile($member['pic'], './userpic/' . $id . 'pic.jpg');
        $pic = '/userpic/' . $id . 'pic.jpg';
//③生成专属海报
        $rootPath = App::getRootPath();  //项目路径
        $twocode = $rootPath . 'public' . $filename;  //二维码路径
        
        //$scanpic = $rootPath . 'public' . $activity['poster_img'];  //海报路径
        if(!file_exists('./uploads/'.$hid.'haibao.jpg')){
            $this->downfile($activity['poster_img'], './uploads/' . $hid . 'haibao.jpg');
        }
        $scanpic = $rootPath . 'public'.'/uploads/' . $hid . 'haibao.jpg';
		
        $nickname = $member['nickname'];//微信昵称
        $pic = $rootPath . 'public' . $pic;//微信头像
        $ret = self::setimg($scanpic, $twocode, $pic, $nickname, $id, $activity); //生成图片  +  背景图片 二维码图片
        
        //查询该公众号的appId appSecret
        $user = new User();
        $users = $user->get_appid($activity['appid']);
        $wechat = new WeChat();
		//修改分享信息
        $rootPath = 'http://' . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
        $arr = $wechat->jsapiget($users['appId'], $users['appSecret'], $rootPath);
        if (isset($arr['errmsg']) && isset($arr['errcode'])) {
            Exception::exception('修改分享信息失败，' . '错误代码：' . $arr['errcode'] . '。错误消息：' . $arr['errmsg']);
        }
		$member = cookie('hbMember');
		
		$this->assign('member',$member);
		$this->assign('activity', $activity);
		$this->assign('url',URL);
        $this->assign('arr',$arr);
        $this->assign('img',$ret);
        $this->assign('id',$id);
        $this->assign('hid',$hid);
        return view();
    }

    public function setimg($scanpic, $twocode, $pic, $nickname, $id, $activity)//背景图片 二维码 id
    {
        $img = imagecreatefromjpeg($scanpic); //img 是背景图片
        //添加二维码
        $two = imagecreatefrompng($twocode);//pic 是 二维码图片
        $arrlist = getimagesize($twocode);
        $image_p = imagecreatetruecolor($activity['two_width'], $activity['two_height']);
        imagecopyresampled($image_p, $two, 0, 0, 0, 0, $activity['two_width'], $activity['two_height'], $arrlist[0], $arrlist[1]); //修改二维码图
        imagecopymerge($img, $image_p, $activity['two_x'], $activity['two_y'], 0, 0, $activity['two_width'], $activity['two_height'], 100);
        //添加头像
        $p = imagecreatefromjpeg($pic);
        $piclist = getimagesize($pic);
        $image_p2 = imagecreatetruecolor($activity['pic_width'], $activity['pic_height']);
        imagecopyresampled($image_p2, $p, 0, 0, 0, 0, $activity['pic_width'], $activity['pic_height'], $piclist[0], $piclist[1]); //修改二维码图
        imagecopymerge($img, $image_p2, $activity['pic_x'], $activity['pic_y'], 0, 0, $activity['pic_width'], $activity['pic_height'], 100);
        //添加文字
        if (mb_strlen($nickname) > 7) {
            $nickname = mb_substr($nickname, 0, 2, "utf-8") . "*" . mb_substr($nickname, mb_strlen($nickname, 'utf-8') - 1, 1, "utf-8");
        }
        $black = imagecolorallocate($img, 255, 255, 255);
        imagettftext($img, 28, 0, $activity['text_x'], $activity['text_y'], $black, "../simhei.ttf", $nickname);
        //保存
        imagejpeg($img, "./img/" . $id . "poster.jpg");
        $page = "/img/" . $id . "poster.jpg";
        return $page;
    }

    public function downfile($filename, $savename)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_URL, $filename);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $file_content = curl_exec($ch);
        curl_close($ch);
        $downloaded_file = fopen($savename, 'w');
        fwrite($downloaded_file, $file_content);
        fclose($downloaded_file);
    }
}