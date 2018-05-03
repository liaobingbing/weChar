<?php
/**
 * Created by PhpStorm.
 * User: HZM
 * Date: 2018/4/18
 * Time: 18:45
 */

namespace FindWord\Controller;


use Common\Controller\ApiLoginController;
use FindWord\Model\UserGameModel;
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
               $result['data'] = array('user_id'=>1);
           }
        }else{
            $result = $data;
        }

        $this->ajaxReturn($result);

    }

    // 总挑战次数
    public function challenge_num(){
        $result = array('code' => 400, 'msg' => '获取失败', 'data' => 0);
        $count_challenge = M('UserGame')->sum('challenge_num');

        if($count_challenge <= 5000){
            $count_challenge += 5000;
        }

        if($count_challenge){
            $result['code'] =   200;
            $result['msg']  =   '获取成功';
            $result['data'] = array('count_challenge' => $count_challenge);
        }

        $this->ajaxReturn($result);
    }

    // 毅力榜
    public function challenge_top(){
        $UserGame = new UserGameModel();
        $rankings = $UserGame->get_rankings('challenge_num',8);
        $result = array('code'=>400, 'msg'=>'获取失败');

        if($rankings){
            $result['code'] = 200;
            $result['msg']  = '获取成功';
            $result['data'] = array('ranking_list' => $rankings);
        }

        $this->ajaxReturn($result);
    }

    // 荣耀榜
    public function get_prize_top(){
        $UserGame = new UserGameModel();
        $rankings = $UserGame->get_rankings('get_prize',8);
        $result = array('code'=>400, 'msg'=>'获取失败');

        if($rankings){
            $result['code'] = 200;
            $result['msg']  = '获取成功';
            $result['data'] = array('ranking_list' => $rankings);
        }

        $this->ajaxReturn($result);
    }

    // 测试 - 登录接口
    public function test_login(){
        $user_id = I('id',4);
        $Users = new UsersModel();
        $user = $Users->find_by_user_id($user_id);

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