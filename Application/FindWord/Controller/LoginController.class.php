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
        $userdao=new UsersModel();
        $code=I('post.code');
        $encryptedData=I('post.encryptedData');
        $iv=I('post.iv');
        $login_data=$this->get_weixin($code,$encryptedData,$iv);
        if($login_data['code']!=400){
            $openid = $login_data['openId'];
            $user = $userdao->findByOpenid($openid);
            if (!$user) {
                $user_data['openid'] = $openid;
                $user_data['unionid'] = $login_data['unionId'];
                $user_data['gender'] = $login_data['gender'];
                $user_data['city'] = $login_data['city'];
                $user_data['login_time'] = time();
                $user_data['province'] = $login_data['province'];
                $user_data['country'] = $login_data['country'];
                $user_data['avatar_url'] =  str_replace('/0','/132',$login_data['avatarUrl'] );
                $user_data['name'] = $login_data['nickName'];
                $uid = M('users')->data($user_data)->add();
                $user_game['uid']=$uid;
                $user_game['nickname']=$login_data['nickName'];
                $user_game['login_time'] = time();
                $user_game['avatar_url']=str_replace('/0','/132',$login_data['avatarUrl'] );
                M('user_game')->add($user_game);
            }else{
                if($user['status']==0) {
                    $data['code']=403;//已经被拉黑
                    $data['msg']='已经被拉黑';
                    $data['data']['user_id']=$user['id'];
                    $this->ajaxReturn($data,'JSON');
                }
                if($user['login_time']<strtotime(date("Y-m-d"),time())){
                    M('user_game')->where('uid='.$user['id'])->setField("chance_num",5);
                    M('users')->where('id='.$user['id'])->setField("avatar_url", str_replace('/0','/132',$login_data['avatarUrl']));
                    M('user_game')->where('uid='.$user['id'])->setField("avatar_url", str_replace('/0','/132',$login_data['avatarUrl']));

                }
                M('users')->where('id='.$user['id'])->setField("login_time",time());
                $uid=$user['id'];
            }
            $session_k=session_id();
            session('user_id',$uid,3600);
            session("openid",$openid);
            $data['code']=200;
            $data['msg']='success';
            $data['data']=array('session_key'=>$session_k);
            $this->ajaxReturn($data,'JSON');

        }
        else{
            $this->ajaxReturn($login_data);
        }

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