<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/5/14
 * Time: 20:28
 */

namespace common\controller;

use think\Controller;

class ApiLogin extends Controller
{
    //判断微信登陆
    public function test_weixin($code=null)
    {
        if($code){
            $arr=array(
                'appid'=>config('WECHAT_APPID'),
                'secret'=>config('WECHAT_APPSECRET'),
                'js_code'=>$code,
                'grant_type'=>'authorization_code'
            );
            $code_session = post_url('https://api.weixin.qq.com/sns/jscode2session', $arr);
            if(!empty($code_session['errcode'])) {
                $data['code'] = 400;
                $data['msg'] =$code_session['errcode']."==".$code_session['errmsg'];;
                return $data;
            }else{
                return $code_session;
            }
        }else {
            $data['code']=400;
            $data['msg']='参数code为空';
            return $data;
        }
    }
}