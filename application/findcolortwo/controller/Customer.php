<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/6/22
 * Time: 9:37
 */

namespace app\findcolortwo\controller;



use common\controller\Wechat;
use think\Controller;
define("TOKEN", "WEIXIN");
class Customer extends Controller
{

    public function index()
    {
        $wechatObj =new Wechat() ;
        if (!isset($_GET['echostr'])) {
            $this->responseMsg();
        }else{
            $wechatObj->valid();
        }

    }
    public function responseMsg()
    {
        //$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        $postStr = file_get_contents("php://input");
        if (!empty($postStr)) {
            $postObj = json_decode($postStr,true);
            $RX_TYPE = trim($postObj['MsgType']);
           // print_r($RX_TYPE);die;
            switch ($RX_TYPE)
            {

                case "text":
                   $this->send($postObj['FromUserName']);
                    break;
            }

        }
    }
    //获取access_token;
    public function accessToken()
    {
        $admin=model('Admin');
        $appId=config('WECHAT_APPID');
        $secrt=config('WECHAT_APPSECRET');
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

    public function send($openId)
    {
        $access_token=$this->accessToken();
        //echo $access_token;die;
        $url='https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$access_token;
        //print_r($openId);die;
        $arr=array('touser'=>$openId,
            'msgtype'=>"link",
            'link'=>[
                'title'=>'客服',
                'description'=>'关注公众号，点击菜单栏获取客服微信',
                'url'=>'https://mp.weixin.qq.com/s/_lP5rbcQrVKb5DqAdKTnxA',
                'thumb_url'=>'http://img.ky121.com/20180623103636.jpg',
            ],
           );
        $arr=json_encode($arr,JSON_UNESCAPED_UNICODE);
      //  print_r($arr);die;
        post_url($url,$arr);
    }
    function post_url($url,$parameter)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT,15);   //只需要设置一个秒的数量就可以
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // post数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, $parameter);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
        ));
        $output = curl_exec($ch);
        curl_close($ch);
        $output = json_decode($output,true);
        return $output;
    }
}