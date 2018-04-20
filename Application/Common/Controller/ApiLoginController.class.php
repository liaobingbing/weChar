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
                    $data['msg']=$code_session;
                    return $data;

                }
            }else {
                $data['code']=400;
                $data['msg']='参数code为空';
                return $data;
            }

    }

    public function do_login($code,$encryptedDate,$iv){

        $code_session = $this->wx_session_key($code);

        if($code_session['openid'] && $code_session['session_key']){

            $session_key = $code_session['session_key'];
            $data = $this->wx_biz_data_crypt($encryptedDate,$iv,$session_key);

            if( $data['errCode'] == 0){
                $json_data = json_decode($data['data'], true);
                $result = $json_data;
                $result['code'] = 200;
                $result['session_key'] = $code_session['session_key'];
            }else{
                $result['code']=1;
                $result['msg']='登录失败';
            }


        }else{
            $result['code'] = 2;
            $result['msg'] = $code_session['errmsg'];
            //$result['msg'] = $code_session;
        }


        return $result;
    }

    public function wx_session_key($code){
        $url = 'https://api.weixin.qq.com/sns/jscode2session';
        $parameter = array(
            'appid'     =>  C('WECHAT_APPID'),
            'secret'    =>  C('WECHAT_APPSECRET'),
            'js_code'   =>  $code,
            'grant_type'=>  'authorization_code'
        );

        $code_session = post_url($url,$parameter);

        return $code_session;
    }

    public function wx_biz_data_crypt($encryptedData,$iv,$session_key){
        vendor("wxaes.WXBizDataCrypt");
        $wxBizDataCrypt = new \WXBizDataCrypt(C("WECHAT_APPID"), $session_key);

        $errCode = $wxBizDataCrypt->decryptData($encryptedData, $iv, $data);

        $resutl['errCode'] = $errCode;
        $resutl['data']    = $data;

        return $resutl;
    }




}