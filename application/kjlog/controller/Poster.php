<?php
namespace app\kjlog\controller;

use think\Controller;
use think\Db;
use qrcode\QRcode;
use think\facade\App;

//生成我的专属海报
class Poster extends Controller
{
    public function index()
    {
        $id = cookie('member')['id'];
        $hid = cookie('member')['hid'];
        $member = Db::name('kj_member')->where(['id' => $id])->field('nickname,pic')->find();
        $activity = Db::name('kj_activity')->where(['id' => $hid])->find();
//①判断是否存在专属二维码 不在就生成
        $filename = './img/' . $id . 'qrcode.png';
        if (!file_exists($filename)) {
            $url = URL . "/kjlog/index/welcome/hid/{$hid}/sid/{$id}";
            QRcode::png($url, $filename, "L", 6, 2);
        }
        $filename = '/img/' . $id . 'qrcode.png';
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
        if(!file_exists('./uploads/'.$hid.'kjhaibao.jpg')){
            $this->downfile($activity['poster_img'], './uploads/' . $hid . 'kjhaibao.jpg');
        }
        $scanpic = $rootPath . 'public'.'/uploads/' . $hid . 'kjhaibao.jpg';
        
        $nickname = $member['nickname'];//微信昵称
        $pic = $rootPath . 'public' . $pic;//微信头像
        $ret = self::setimg($scanpic, $twocode, $pic, $nickname, $id, $activity); //生成图片  +  背景图片 二维码图片
        $this->assign('img',$ret);
        $this->assign('id',$id);
        $this->assign('hid',$hid);
        return view();
    }

    //生成带二维码的图片
    public function img($hid, $id, $scan_img, $width, $height, $x, $y)
    {
//生成二维码
        $url = URL . "/kjlog/index/welcome/hid/{$hid}/sid/{$id}";
//①判断是否存在这个目录
        if (!file_exists('./img')) {
            mkdir('./img');
        }
//②判断是否存在专属二维码 不在就生成
        $filename = './img/' . $id . 'qrcode.png';
        if (!file_exists($filename)) {
            QRcode::png($url, $filename, "L", 6, 2);
        }
        $filename = '/img/' . $id . 'qrcode.png';
//③保存微信头像
        $pic = "http://thirdwx.qlogo.cn/mmopen/vi_32/MzFnxgHvzOwiaspQmB4DDBdUnRwepJEhbWV9aoJtuYhp5wgcl4kP3ugKajCr7uibDEiaN8et8PdMjmiaUwjhjAW7TQ/132";//微信头像
        $this->downfile($pic, './userpic/' . $id . 'pic.jpg');
//生成专属海报
        $rootPath = App::getRootPath();  //项目路径
        $twocode = $rootPath . 'public' . $filename;  //二维码路径
        $scanpic = $rootPath . 'public' . $scan_img;  //海报路径
        $nickname = "路过 ";//微信昵称
        $pic = $rootPath . 'public' . '/userpic/' . $id . 'pic.jpg';//微信头像
        $ret = self::setimg($scanpic, $twocode, $pic, $nickname, $id, $width, $height, $x, $y); //生成图片  +  背景图片 二维码图片
        return $ret;
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