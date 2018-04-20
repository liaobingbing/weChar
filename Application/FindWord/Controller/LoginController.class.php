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

        $code = I('code');
        $encryptedDate=I('encryptedDate');
        $iv=I('iv');
        $result = array('code'=>400,'msg'=>'失败');


        $data = $this->do_login($code,$encryptedDate,$iv);

        if($data['code'] == 200){
            $Users = new UsersModel();
            $session_id = $Users->do_login($data);

           if($session_id){
               $result['code']  =   200;
               $result['msg']   =   '登录成功';
               $result['data']  =   array('session_id'=>$session_id);
           }else{
               $result['code']  = 403;
               $result['msg'] = '该用户已禁用';
           }
        }

        $this->ajaxReturn($result);

    }


}