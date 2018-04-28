<?php
/**
 * Created by PhpStorm.
 * User: HZM
 * Date: 2018/4/18
 * Time: 18:45
 */

namespace FindWord\Controller;


use Common\Controller\ApiLoginController;
use FindWord\Model\UsersModel;

class LoginController extends ApiLoginController
{

    // index
    public function index()
    {
        echo 'index';
    }

    // 用户登录接口
    public function login(){

        $code = I('code');
        $encryptedData=I('encryptedData');
        $iv=I('iv');

        $result = array('code'=>400,'msg'=>'失败');

        $data = $this->do_login($code,$encryptedData,$iv);

        if($data['code'] == 200){
            $Users = new UsersModel();
            $data = $Users->do_login($data);

           if($data){
               $result['code']  =   200;
               $result['msg']   =   '登录成功';
               $result['data']  =   array(
                   'session_id'  => $data['session_id'],
                   'session_key' => $data['session_key'],
                   'nickname'    => $data['nickname'],
                   'avatar_url'  => $data['avatar_url'],
               );
           }else{
               $result['code']  = 403;
               $result['msg'] = '该用户已禁用';
           }
        }else{
            $result = $data;
        }

        $this->ajaxReturn($result);

    }

    // 测试 - 登录接口
    public function test_login(){
        $Users = new UsersModel();
        $user = $Users->find_by_user_id(4);

        session(null);
        $session_id = get_session_id();
        session('user_id',$user['id']);
        session('openid',$user['openid']);
        if($session_id){
            print_r(session());
            echo '登录成功';
        }
    }

    // 测试 - 登录退出
    public function test_logout(){
        session(null);
        echo '退出成功';
    }



}