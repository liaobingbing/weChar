<?php
/**
 * 未登陆时的操作
 * Created by PhpStorm.
 * User: HZM
 * Date: 2018/4/14
 * Time: 9:02
 */

namespace Common\Controller;

use Think\Controller;

class ApiLoginController extends Controller
{
    public  function get_weixin($code=null,$encryptedData=null,$iv=null){
            if($code&&$encryptedData&&$iv){
                $arr=array(
                    'appid'=>C('WECHAT_APPID'),
                    'secret'=>C('WECHAT_APPSECRET'),
                    'js_code'=>$code,
                    'grant_type'=>'authorization_code'
                );
                $code_session = $this->post_url('https://api.weixin.qq.com/sns/jscode2session', $arr);
                $code_session = json_decode($code_session, true);
                if($code_session['errcode']==40163){
                    $data['code']=400;
                    $data['msg']='code been used';
                    return $data;
                }
                if ($code_session['openid'] && $code_session['session_key']) {
                    $session_key = $code_session['session_key'];
                    session('session_key',$session_key);
                    vendor("wxaes.WXBizDataCrypt");
                    $wxBizDataCrypt = new \WXBizDataCrypt(C("WECHAT_APPID"), $session_key);
                    $data_arr = array();
                    $errCode = $wxBizDataCrypt->decryptData($encryptedData, $iv, $data_arr);
                    if ($errCode == 0) {
                        $json_data = json_decode($data_arr, true);
                        return   $json_data;
                    } else {
                        $data['code']=400;
                        $data['msg']='登录失败';
                        return $data;
                    }

                }else {

                    $data['code']=400;
                    $data['msg']=$code_session['errcode'];
                    return $data;

                }
            }else {
                $data['code']=400;
                $data['msg']='参数code为空';
                return $data;
            }

    }
    public function post_url($url,$data){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT,15);   //只需要设置一个秒的数量就可以
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // post数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $output = curl_exec($ch);
        curl_close($ch);
        //打印获得的数据
        //print_r($output);
        return $output;
    }
}