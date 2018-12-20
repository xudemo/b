<?php
namespace app\my\controller;

use app\my\controller\Checklogin;
use think\Db;
use think\Request;
use qrcode\QRcode;
use think\facade\App;
//海报预览
class Poster extends Checklogin
{
    public function index(Request $request)
    {
        $data = $request->post();
        $rootPath = App::getRootPath();  //项目路径
        $scanpic = $rootPath . 'public' . $data['poster_img']; //背景图片
        $twocode = $rootPath . 'public/twocode/two_code.png'; //二维码图片
        $pic = $rootPath . 'public/twocode/weixinpic.jpg';  //微信头像
        $nickname = "微信昵称";//微信昵称
        return $this->setimg($data['poster_img'],$twocode,$pic,$nickname,$data);
    }
    public function setimg($scanpic, $twocode,$pic,$nickname, $data)//背景图片 二维码 id
    {
        $img = imagecreatefromjpeg($scanpic); //img 是背景图片
        //添加二维码
        $two = imagecreatefrompng($twocode);
        $arrlist = getimagesize($twocode);
        $image_p = imagecreatetruecolor($data['two_width'], $data['two_height']);
        imagecopyresampled($image_p, $two, 0, 0, 0, 0, $data['two_width'], $data['two_height'], $arrlist[0], $arrlist[1]); //修改二维码图
        imagecopymerge($img, $image_p, $data['two_x'], $data['two_y'], 0, 0, $data['two_width'], $data['two_height'], 100);
        //添加头像
        $p = imagecreatefromjpeg($pic);
        $piclist = getimagesize($pic);
        $image_p2 = imagecreatetruecolor($data['pic_width'],$data['pic_height']);
        imagecopyresampled($image_p2, $p, 0, 0, 0, 0, $data['pic_width'], $data['pic_height'], $piclist[0], $piclist[1]); //修改二维码图
        imagecopymerge($img, $image_p2, $data['pic_x'], $data['pic_y'], 0, 0, $data['pic_width'], $data['pic_height'], 100);
        //添加文字
        if(mb_strlen($nickname)>7)
        {
            $nickname =mb_substr($nickname,0,2,"utf-8")."*".mb_substr($nickname,mb_strlen($nickname,'utf-8')-1,1,"utf-8");
        }
        $black = imagecolorallocate($img,255,255,255);

        imagettftext($img,28,0,$data['text_x'],$data['text_y'],$black,"../simhei.ttf",$nickname);
        //保存
        imagejpeg($img, "./liuimg/".$data['appid']."poster.jpg");
        $page = "/liuimg/".$data['appid']."poster.jpg";
        return $page;
    }
}