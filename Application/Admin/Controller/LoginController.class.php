<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/12
 * Time: 17:34
 */

namespace Admin\Controller;


use Admin\Model\AdminModel;
use Think\Controller;
use Think\Verify;

class LoginController extends Controller
{
    // 登录页面
    public function index(){

        $admin_id = session('admin_id');

        if( $admin_id ){
            $this->redirect('Admin/Index/index');
        }

        $this->assign('title','后台登录');

        $this->display();
    }

    // 登录接口
    public function do_login(){
        $username = I('post.username');
        $password = I('post.password');
        $code     = I('post.code');

        $result = array('code'=>400);

        if($username && $password &&$code){
            if( !$this->checkVerify($code)){
                $result['msg']  = '验证码错误';
            }else{
                $AdminModel = new AdminModel();
                $status = $AdminModel->do_login($username,$password);

                $AdminController = new AdminController();
                $result = $AdminController->get_msg($status);
            }
        }else{
            $result['msg'] = '请填完整表单!';
        }

        $this->ajaxReturn($result);
    }

    //检测验证码是否正确
    public function checkVerify($code){
        $Verify=new Verify();
        return $Verify->check($code);
    }

    //验证码
    public function verify(){
        ob_clean();
        $Verify = new Verify();
        $Verify->fontSize = 18;
        $Verify->length = 4;
        $Verify->useNoise = false;
        $Verify->codeSet = '0123456789';
        $Verify->imageW = 176;
        $Verify->imageH = 41;
        $Verify->type = "png";
        $Verify->verifyName = "verify";
        $Verify->useCurve = false;
        $Verify->expire = 600;
        $Verify->entry();
    }

}