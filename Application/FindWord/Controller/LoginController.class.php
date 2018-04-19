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
    public function login(){
        $code=I('code');
        $encryptedData=I('encryptedData');
        $iv=I('iv');
        $result = array('code'=>400,'msg'=>'失败');
        // 调用微信登录接口 获取用户信息
        $user_info = $this->get_weixin($code,$encryptedData,$iv);

        if( $user_info['code'] != 400){
            $Users = new UsersModel();
            $user = $Users->find_by_openid($user_info['code']);

            if(!$user){
                $user = $Users->add_user($user_info);

                $session_id = get_session_id(60*60*24);
                session('user_id',$user['id']);
                session('openid',$user['openid']);

                $result['code'] = 200;
                $result['msg']  = '登录成功';
                $result['data'] = array('session_id'=>$session_id);


            }else{
                if ($user['status'] == 0){
                    $result['code'] = 403;
                    $result['msg']  = '已拉黑';
                    session(null);
                }else{
                    $today_0 = date('Y-m-d',time());

                    if($user['update_time'] < $today_0){
                        $user = $Users->update_user($user_info);

                        $session_id = get_session_id(60*60*24);
                        session('user_id',$user['id']);
                        session('openid',$user['openid']);

                        $result['code'] = 200;
                        $result['msg']  = '登录成功';
                        $result['data'] = array('session_id'=>$session_id);
                    }
                }
            }

        }else{
            $result = $user_info;
        }


        $this->ajaxReturn($result);

    }
}