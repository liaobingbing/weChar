<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/5/14
 * Time: 20:24
 */

namespace common\controller;
use think\Controller;

class ApiBase extends Controller
{
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
            print_r(json_encode($result));die;
        }
    }
}