<?php
/**
 * Created by PhpStorm.
 * User: HZM
 * Date: 2018/4/18
 * Time: 18:45
 */

namespace FindColor\Controller;


use Common\Controller\ApiLoginController;
use FindColor\Model\QuestionsModel;
use FindColor\Model\UserGameModel;
use FindColor\Model\UsersModel;

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
        $userInfo=I('post.userInfo');//获取前台传送的用户信息
        $userInfo=str_replace("&quot;","\"",$userInfo);
        $userInfo=json_decode($userInfo,true);
        $login_data=$this->test_weixin($code);
        if($login_data['code']!=400&&$userInfo){
            $session_key = $login_data['session_key'];
            session('wx_session_key',$session_key);
            $openid = $login_data['openid'];
            $user = $userdao->findByOpenid($openid);
            if (!$user) {
                $user_data['openid'] = $openid;
                $user_data['unionid'] = $userInfo['unionId'];
                $user_data['gender'] = $userInfo['gender'];
                $user_data['city'] = $userInfo['city'];
                $user_data['login_time'] = time();
                $user_data['province'] = $userInfo['province'];
                $user_data['country'] = $userInfo['country'];
                $user_data['avatar_url'] =  str_replace('/0','/132',$userInfo['avatarUrl'] );
                $user_data['name'] = $userInfo['nickName'];
                $uid = M('users')->data($user_data)->add();
                $user_game['uid']=$uid;
                $user_game['nickname']=$userInfo['nickName'];
                $user_game['login_time'] = time();
                $user_game['avatar_url']=str_replace('/0','/132',$userInfo['avatarUrl'] );
                M('user_game')->add($user_game);
            }else{
                if($user['status']==0) {
                    $data['code']=403;//已经被拉黑
                    $data['msg']='已经被拉黑';
                    $data['data']['user_id']=$user['id'];
                    $this->ajaxReturn($data,'JSON');
                }
                /*if($user['login_time']<strtotime(date("Y-m-d"),time())){
                    M('users')->where('id='.$user['id'])->setField("avatar_url", str_replace('/0','/132',$login_data['avatarUrl']));
                    M('user_game')->where('uid='.$user['id'])->setField("avatar_url", str_replace('/0','/132',$login_data['avatarUrl']));

                }*/
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
// 获取题目
    public function get_question(){
        $layer = I('layer',1);
        if($layer == 1){
           // session('questions',null);
             S('findColorQuestions',null);
            $Questions = new QuestionsModel();
            $questions = $Questions->get_rand_questions();
            //print_r($questions);die;
           // session('find_color_questions',$questions);//获取所有的题目并且放在session中
            S('findColorQuestions',$questions);//获取所有的题目并且放在session中
        }

       // $questions = session('questions');
       $questions =S('findColorQuestions');

        $option = array_shift($questions);
       S('findColorQuestions',$questions);
        //print_r($questions);die;
        if( !$option ){
            $this->ajaxReturn(array('code' => 400, 'msg' => '获取失败'));
        }


        if( $layer <= 2 ){
            $i = 4;//4个色块
            $j = 1;//色块大小100%
        }else if( $layer <= 5 ){
            $i = 9;//9块色块
            $j = 0.65;//色块大小65%
        }else if( $layer <= 9 ){
            $i = 16;
            $j = 0.48;
        }else if( $layer <= 14 ){
            $i = 25;
            $j = 0.38;
        }else if( $layer <= 20 ){
            $i = 36;
            $j = 0.32;
        }else if( $layer <= 27 ){
            $i = 49;
            $j = 0.28;
        }else if( $layer <= 35 ){
            $i = 64;
            $j = 0.23;
        }else if( $layer <= 45 ){
            $i = 81;
            $j = 0.21;
        }else{
            $this->ajaxReturn(array('code' => 400, 'msg' => 'layer不能超过44'));
        }

        $arr = array();

        if($option['answer'] != 'option_1'){
            for($a = 0; $a < $i - 1; $a++){
                $arr[$a]['text']    = $option['option_1'];
                $arr[$a]['percent'] = $j;
            }
            if( $layer == 44 ){
                $arr[$i - 1]['text'] = $option['option_1'];
                $arr[$i - 1]['percent'] = $j;
            }else{
                $arr[$i - 1]['text'] = $option['option_2'];
                $arr[$i - 1]['percent'] = $j;
            }

            $answer = $option['option_2'];
        }else{
            for($a = 0; $a < $i - 1; $a++){
                $arr[$a]['text']    = $option['option_2'];
                $arr[$a]['percent'] = $j;
            }
            if( $layer == 44 ){
                $arr[$i - 1]['text'] = $option['option_2'];
                $arr[$i - 1]['percent'] = $j;
            }else{
                $arr[$i - 1]['text'] = $option['option_1'];
                $arr[$i - 1]['percent'] = $j;
            }
            $answer = $option['option_1'];
        }

        shuffle($arr);

        $result['code'] = 200;
        $result['msg']  = '获取成功';
        $result['data']['words'] = $arr;
        $result['data']['answer'] = $answer;
        $result['data']['next_layer'] = $layer+1;


        $this->ajaxReturn($result);
    }
    // 测试 - 登录退出
    public function test_logout(){
        session(null);
        echo '退出成功';
    }

    public function set_session(){
        session("user_id",8);

    }



}