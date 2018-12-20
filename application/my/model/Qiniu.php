<?php
namespace app\my\model;

use Qiniu\Auth;
use Qiniu\Storage\UploadManager;

class Qiniu
{
    public function uploadQiNiu($file)
    {
        $filePath = $file['tmp_name'];  //本地地址
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);//后缀
        $key = substr(md5($filePath), 0, 5) . date('YmdHis') . rand(0, 9999) . '.' . $ext;//上传到七牛后保存的文件名

        $auth = new Auth(QI_ACCKEY, QI_SECKEY);
        $token = $auth->uploadToken(BUCKET);

        $uploadMgr = new UploadManager();
        $result = $uploadMgr->putFile($token,$key,$filePath);
        return $result;
    }
}