<?php
namespace app\kjlog\model;

use think\Db;
use app\kjlog\model\Exception;
use app\kjlog\model\Msg;
use qrcode\QRcode;
use think\facade\App;
class Activity
{
    //活动id查询该活动
    public function get_activity($id)
    {
        $activity = Db::name('kj_activity')
            ->where(['id' => $id])
            ->find();
        if ($activity) {
            return $activity;
        } else {
            Exception::exception('活动不存在或者已经结束');
        }
    }

    //砍价通知
    public static function get_kjId($appid, $id, $hid, $name, $money,$k_money,$message)
    {
        $users = Db::name('wx_users')
            ->where(['user_name' => $appid])
            ->field('appId,appSecret,kj_id')
            ->find();
        if (isset($users['appId']) && isset($users['appSecret']) && isset($users['kj_id'])) {
            $member = Db::name('kj_member')->where(['id' => $id])->field('openid')->find();
        } else {
            return false;
        }
        if (isset($member['openid'])) {
            $msg = new Msg($users['appId'], $users['appSecret'], $member['openid']);
            $result = $msg->kjReply($users['kj_id'], $hid, $name, $money,$k_money,$message);
            $result = json_decode($result, true);
            return $result;
            if ($result['errcode'] == 0) {
                return $result;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    //报名通知
    public static function get_bmId($appid, $id, $hid, $name, $time, $store, $intro)
    {

        $users = Db::name('wx_users')
            ->where(['user_name' => $appid])
            ->field('appId,appSecret,bm_id')
            ->find();
        if (isset($users['appId']) && isset($users['appSecret']) && isset($users['bm_id'])) {
            $member = Db::name('kj_member')->where(['id' => $id])->field('openid')->find();
        } else {
            return false;
        }
        if (isset($member['openid'])) {
            $msg = new Msg($users['appId'], $users['appSecret'], $member['openid']);
            $result = $msg->bmReply($users['bm_id'], $hid, $name, $time, $store, $intro);
            $result = json_decode($result, true);
            return $result;
            if ($result['errcode'] == 0) {
                return $result;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    //参团成功通知
    public static function get_ctId($appid, $id, $hid, $name, $date, $finish_time)
    {
        $users = Db::name('wx_users')
            ->where(['user_name' => $appid])
            ->field('appId,appSecret,ct_id')
            ->find();
        if (isset($users['appId']) && isset($users['appSecret']) && isset($users['ct_id'])) {
            $member = Db::name('kj_member')->where(['id' => $id])->field('openid')->find();
        } else {
            return false;
        }
        if (isset($member['openid'])) {
            $msg = new Msg($users['appId'], $users['appSecret'], $member['openid']);
            $result = $msg->ctReply($users['ct_id'], $hid, $name, $date, $finish_time);
            $result = json_decode($result, true);
            return $result;
            if ($result['errcode'] == 0) {
                return $result;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    //支付成功通知
    public static function get_zfId($appid,$openid,$hid,$transaction_id,$total_fee,$shop_site,$finish_time,$yzm)
    {
        $users = Db::name('wx_users')
            ->where(['user_name' => $appid])
            ->field('appId,appSecret,zf_id')
            ->find();
        if (!isset($users['appId']) && !isset($users['appSecret']) && !isset($users['zf_id'])) {
            return false;
        }
        $msg = new Msg($users['appId'], $users['appSecret'], $openid);
        $result = $msg->zfReply($users['zf_id'], $hid, $transaction_id, $total_fee, $shop_site,$finish_time,$yzm);
        if ($result['errcode'] == 0) {
            return $result;
        } else {
            return false;
        }
    }
  //生成带二维码的图片
    public static function img($hid,$id,$scan_img,$width,$height,$x,$y)
    {
        //生成二维码
        $url = URL . "/kjlog/index/welcome/hid/{$hid}/sid/{$id}";
        if (!file_exists('./img')) {
            mkdir('./img');
        }
        $filename = './img/' .$id.'qrcode.png';
        QRcode::png($url, $filename, "L", 6, 2);
        $filename = '/img/' .$id.'qrcode.png';

        $rootPath = App::getRootPath();
        $filename = $rootPath . 'public' . $filename;
		
        //$scanpic = $rootPath . 'public' . $scan_img;//扫码图片
        if(!file_exists('./uploads/'.$hid.'main.jpg')){
            self::downfile($scan_img, './uploads/' . $hid . 'main.jpg');
        }
        $scanpic = $rootPath. 'public' . '/uploads/' . $hid . 'main.jpg';
        
        $ret = self::setimg($scanpic, $filename, $id,$width,$height,$x,$y); //生成图片  +  背景图片 二维码图片
        return $ret;
    }
    public static function setimg($scanpic, $photo, $id,$width,$height,$x,$y)//背景图片 二维码 id
    {
        $img = imagecreatefromjpeg($scanpic); //img 是背景图片
        $pic = imagecreatefrompng($photo);//pic 是 二维码图片
        $arrlist = getimagesize($photo);
        $image_p = imagecreatetruecolor($width, $height);
        imagecopyresampled($image_p, $pic, 0, 0, 0, 0, $width, $height, $arrlist[0], $arrlist[1]); //修改二维码图片大小

        imagecopymerge($img, $image_p, $x, $y, 0, 0, $width, $height, 100);
        $savefile = strtotime(date("Y-m-d H:i:s", time()));
        $v = imagejpeg($img, "./img/".$id."bj.jpg");
        imagedestroy($img);
        imagedestroy($pic);
        imagedestroy($image_p);
        $page = "/img/".$id."bj.jpg";
        return $page;
    }
	public static function downfile($filename, $savename)
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
