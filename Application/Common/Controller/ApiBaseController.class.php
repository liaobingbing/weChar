<?php
/**
 * Created by PhpStorm.
 * User: HZM
 * Date: 2018/4/14
 * Time: 8:59
 */

namespace Common\Controller;

use Think\Controller;

class ApiBaseController extends Controller
{
    // 签到标记 需拼接openid eg: $openid.$sign_key
    protected $sign_key = '_sign_status';
    // session 保存时间
    protected $session_time = 60*60*24*3;

    // 初始化
    public function _initialize(){
        $this->is_login();
    }

    // 登录判断
    public function is_login(){
        $user_id = session('user_id');
        if( !$user_id ){
            $result['code'] = 401;
            $result['msg']  = '未登录';
            $this->ajaxReturn($result);
        }
    }

    // 判断今天是否签到
    public function is_sign(){
        $user_id = session('user_id');
        $openid  = session('openid');
        $result  = false;
        $key = $openid.$this->sign_key;

        $is_sign = S($key);

        if($is_sign){
            $result = true;
        }

        return $result;
    }

    // 获取 session_id
    public function get_session_id(){
        //设置 session 过期时间
        session('expire',$this->session_time);
        $session_id = session_id();

        return $session_id;
    }





}