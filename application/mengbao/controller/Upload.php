<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/6/4
 * Time: 10:29
 */

namespace app\mengbao\controller;


use Qiniu\Auth;
use Qiniu\Config;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;
use think\Controller;

class Upload extends Controller
{
    /**
     * 上传
     * @param array $file 图片参数
     * @return array
     */
    public function uploadOne_one() {
        $file = request()->file('file');
        // 要上传图片的本地路径
        $filePath = $file->getRealPath();
        $ext = pathinfo($file->getInfo('name'), PATHINFO_EXTENSION);  //后缀
        //获取当前控制器名称
        //$controllerName=$this->getContro();
        // 上传到七牛后保存的文件名
        $key =substr(md5($filePath) , 0, 5). date('YmdHis') . rand(0, 9999) . '.' . $ext;
        // 需要填写你的 Access Key 和 Secret Key
        $accessKey = config('ACCESSKEY');
        $secretKey = config('SECRETKEY');
        // 构建鉴权对象
        $auth = new Auth($accessKey, $secretKey);
        // 要上传的空间
        $bucket = config('BUCKET');
        $domain = config('DOMAIN');
        $token = $auth->uploadToken($bucket);
        // 初始化 UploadManager 对象并进行文件的上传
        $uploadMgr = new UploadManager();
        // 调用 UploadManager 的 putFile 方法进行文件的上传
       // echo $token."==".$key."==".$filePath;die;
        list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
        if ($err !== null) {
            $arr=resCode(400,$err,null);
        } else {
            //返回图片的完整URL\
            $arr=resCode(200,"上传完成",array("url"=>$domain . $ret['key'],"key"=>$key));
        }
        return $arr;
    }

    /**
     * 文件删除
     *
     * @access protected
     * @param array $key 当前文件信息
     * @return mixed
     */
    public function delete_one()
    {
        $key=input("post.key");
        $bucket = config('BUCKET');;
        $auth = new Auth( config('ACCESSKEY'), config('SECRETKEY'));
        $config = new Config();
        $bucketManager = new BucketManager($auth, $config);
        $err = $bucketManager->delete($bucket, $key);
        if($err !== null){
            $arr=resCode(400,$err,null);
        }else{
            $arr=resCode(200,"ok",null);
        }
        return $arr;
    }

    public function uploadOne()
    {
        $file =request()->file('file');
       // print_r($file);die;
        // 移动到框架应用根目录/public/uploads/ 目录下
        if($file){
            $info = $file->validate(['size'=>20480000,'ext'=>'jpg,png,gif'])->move(LOGO_ATAH. DS . 'img');
            if($info){
            // 成功上传后 获取上传信息

            // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
               $res=$info->getSaveName();
            //$arr=resCode(200,"ok",array("url"=>URL.$res,"save_path"=>$res));
                $arr=resCode(200,"上传完成",array("url"=>URL.$res,"key"=>$res));

            }else{
                // 上传失败获取错误信息
                $arr=resCode(400,"error",$file->getError());
            }
            return $arr;
        }else{
            $arr=resCode(400,"文件为空",null);
            return $arr;
        }
    }
    public function delete()
    {
        $key=input("post.key");
        $url=LOGO_ATAH."/img/".$key;
        $res=@unlink($url);
        if($res){
            $arr=resCode(200,"ok",null);
        }else{
            $arr=resCode(400,"文件删除失败",null);
        }
        return $arr;
    }
}