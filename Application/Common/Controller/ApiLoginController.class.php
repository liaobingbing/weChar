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
    /**
     * 调用微信登录接口 获取用户信息
     * @param null $code
     * @param null $encryptedData
     * @param null $iv
     * @return mixed
     */
    public  function get_weixin($code=null,$encryptedData=null,$iv=null){
            if($code&&$encryptedData&&$iv){
                $arr=array(
                    'appid'=>C('WECHAT_APPID'),
                    'secret'=>C('WECHAT_APPSECRET'),
                    'js_code'=>$code,
                    'grant_type'=>'authorization_code'
                );
                $code_session = post_url('https://api.weixin.qq.com/sns/jscode2session', $arr);
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




}