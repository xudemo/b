<?php
namespace app\admin\controller;

use think\Controller;
use app\admin\controller\Checklogin;
use think\Db;
use app\admin\model\WxRevert;
//基础设置模块
class Dialog extends Checklogin
{
    public function upload()
    {
        if($this->request->isPost()){
         	$img = $_FILES['img'];
            $filetype = pathinfo($img["name"])['extension'];
            $filetype = strtolower($filetype);//转成小写
            $size = $img['size'];
            if(empty($img)){
                $this->assign('data',['state'=>1,'message'=>'请选择图片']);
                return view();
            } else if($filetype!='bmp' && $filetype!='png' && $filetype!='jpeg' && $filetype!='jpg' && $filetype!='gif'){
                $this->assign('data',['state'=>1,'message'=>'文件类型错误,请上传图片类型']);
                return view();
            } else if($size > 2097152){
                $this->assign('data',['state'=>1,'message'=>'上传的文件不能超过2MB']);
                return view();
            }
          	$img = request()->file('img');
            $info = $img->move('./uploads');
            $path = './uploads/'.$info->getSaveName();
            $wxModel = new WxRevert();
            $authorizerAccessToken = $wxModel->getAuthorizerAccessToken();
            if(!$authorizerAccessToken){
                $this->assign('data',['state'=>1,'message'=>'获取authorizer_access_token失败']);
                return view();
            }
            $url = "https://api.weixin.qq.com/cgi-bin/material/add_material?access_token={$authorizerAccessToken}&type=image";
            $filedata = array(
               'media'=>new \CURLFile($path)
            );
            $result = $wxModel->https_request($url,$filedata);
            unlink($path); //删除图片
            $media = json_decode($result,true);
          	if(isset($media['errcode'])){
              	 $this->assign('data',['state'=>1,'message'=>$media['errcode']]);
                return view();
            }
            if($media['media_id']){
                $this->assign('data',['state'=>2,'message'=>'上传成功']);
                $this->assign('media',$media);
                return view();
            }else{
                $this->assign('data',['state'=>1,'message'=>'上传失败']);
                return view();
            }
        }else{
          	$this->assign('data',null);
          	$this->assign('media',null);
            return view();
        }
    }
  public function postfile($url,$data,$header=array())
	{
		 if(function_exists('curl_init')) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                if(is_array($header) && !empty($header)){
                    $set_head = array();
                    foreach ($header as $k=>$v){
                        $set_head[] = "$k:$v";
                    }
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $set_head);
                }
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
                curl_setopt($ch, CURLOPT_TIMEOUT, 0);// 1s to timeout.
                $response = curl_exec($ch);
                if(curl_errno($ch)){
                    //error
                    return curl_error($ch);
                }
                $reslut = curl_getinfo($ch);
                print_r($reslut);
                curl_close($ch);
                $info = array();
                if($response){
                    //$info = json_decode($response, true);
                    $info = $response;
                }
                return $info;
            } else {
                throw new Exception('Do not support CURL function.');
            }
	}
}