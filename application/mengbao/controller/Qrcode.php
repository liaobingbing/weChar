<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/7/10
 * Time: 14:42
 */

namespace app\mengbao\controller;

use think\Controller;
class Qrcode extends Controller
{
    public function index(){
        header('content-type:image/jpg');
        $access_token=$this->accessToken();
        $url="https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=".$access_token;
        $scene=input("scene");
        $page=input("page");
        $arr=array("scene"=>$scene,"page"=>$page,"is_hyaline"=>true);
        $arr=json_encode($arr);
        //$res=post_url($url,$arr);
        $res=$this->get_http_array($url,$arr);
        print_r($res);
        die;
       /* $res=json_encode($res);
        $filename=$scene.".jpg";
        $dir=LOGO_ATAH."/img/mengbao/".$filename;
        file_put_contents($dir, $res);
        print_r(URL."mengbao/".$filename);*/
    }
    public function get_http_array($url,$post_data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        //curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);   //没有这个会自动输出，不用print_r()也会在后面多个1
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $output = curl_exec($ch);
        curl_close($ch);
        $out = json_decode($output);
        return $out;
    }
    //获取access_token;
    public function accessToken()
    {
        $admin=model('Admin');
        $appId=config('WECHAT_APPID');
        //$appId='wx4a407df220f9ee2e';
        $secrt=config('WECHAT_APPSECRET');
        //$secrt='4e7285ab46ab932497580661c3d25eae';
        $result=$admin->where('app_id', $appId)->find();
        if(empty($result)){
            $url="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appId&secret=$secrt";
            $res=post_url($url,null);
            $access_token=$res['access_token'];
            if(!empty($access_token)){
                $admin->app_id = $appId;
                $admin->expires_at = time()+7200;
                $admin->access_token = $access_token;
                $admin->save();
            }
        }
        else{
            if($result['expires_at']>time()){
                $access_token=$result['access_token'];
            }else{
                $url="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appId&secret=$secrt";
                $res=post_url($url,null);
                //$res=json_decode($res,true);
                $access_token=$res['access_token'];
                $admin->save([
                    'expires_at' => time()+7200,
                    'access_token' => $access_token,
                ],['app_id' => $appId]);
            }
        }
        return $access_token;
    }

}