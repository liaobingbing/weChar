<?php
/**
 * Created by PhpStorm.
 * User: HZM
 * Date: 2018/4/18
 * Time: 18:45
 */

namespace FindWord\Controller;


use Common\Controller\ApiLoginController;
use FindWord\Model\QuestionsModel;
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
        $userInfo=I('post.userInfo');//获取前台传送的用户信息
        $userInfo=str_replace("&quot;","\"",$userInfo);
        $userInfo=json_decode($userInfo,true);
        $openid=I('post.openId');//获取opendId
        $wx_key=I('post.session_key');
        if($openid&&$userInfo){
            session('wx_session_key',$wx_key);
            $user = $userdao->findByOpenid($openid);
            if (!$user) {
                $user_data['openid'] = $openid;
               // $user_data['unionid'] = $login_data['unionId'];
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
                $user_data['id'] = $user['id'];
                $user_data['openid'] = $openid;
                $user_data['gender'] = $userInfo['gender'];
                $user_data['city'] = $userInfo['city'];
                $user_data['login_time'] = time();
                $user_data['province'] = $userInfo['province'];
                $user_data['country'] = $userInfo['country'];
                $user_data['avatar_url'] =  str_replace('/0','/132',$userInfo['avatarUrl'] );
                $user_data['name'] = $userInfo['nickName'];
                M('users')->data($user_data)->save();
                $user_game['uid']=$user['id'];
                $user_game['nickname']=$userInfo['nickName'];
                $user_game['login_time'] = time();
                $user_game['avatar_url']=str_replace('/0','/132',$userInfo['avatarUrl'] );
                M('user_game')->where("uid=%d",$user['id'])->save($user_game);
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
            $arr=array("code"=>400,"msg"=>"参数不全",null);
            $this->ajaxReturn($arr);
        }

    }
    // 获取题目
    public function get_question(){
        $layer = I('layer',1);
        $openId=I("post.openId");
        if($layer == 1){
            S($openId,null);
            S("find_word_questions",null);
            $Questions = new QuestionsModel();
            $questions = $Questions->get_rand_questions(44);
            S($openId,$questions,3600);
        }

        $questions = S($openId);

        $option = $questions[$layer-1];

        if( $layer <= 2 ){
            $i = 4;
            $j = 1;
        }else if( $layer <= 5 ){
            $i = 9;
            $j = 0.65;
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
            $j = 0.27;
        }else if( $layer <= 35 ){
            $i = 64;
            $j = 0.23;
        }else if( $layer <= 44 ){
            $i = 81;
            $j = 0.21;
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
    //分享群
    public function share_group(){
        $data=array("code"=>200,"msg"=>"success","data"=>null);
        $this->ajaxReturn($data,'JSON');
    }
    public function get_openid()
    {
        $code = I('post.code');
        $login_data = $this->test_weixin($code);
        if ($login_data['code'] != 400) {
            $openid = $login_data['openid'];
            $session_key=$login_data['session_key'];
            $arr=array("code"=>200,"msg"=>"success","data"=>array("openId"=>$openid,"wx_session_key"=>$session_key));
            $userdao=new UsersModel();
            $user = $userdao->findByOpenid($openid);
            if(empty($user)){
                $data['openid']=$openid;
                $uid = M("users")->add($data);
                $game['uid']=$uid;
                $game['login_time'] = time();
                M("user_game")->add($game);

            }
            $this->ajaxReturn($arr);
        }
        else{
            $this->ajaxReturn($login_data);

        }
    }

    //开始 挑战
    public function begin_challenge(){
         $openId=I("post.openId"); 
        $UserGame = new UserGameModel();
        $userdao=new UsersModel();
        $user=$userdao->findByOpenid($openId);
        $user_id=$user['id'];
        $user_game = $UserGame->find_by_user_id($user_id);
        if($user_game) {
            M('UserGame')->where(array('uid' => $user_id))->setInc('challenge_num');
            $result['code'] =   200;
            $result['msg']  =   '开始挑战成功';
            $result['data'] = null;

        }else{
            $result['code'] =400;
            $result['msg']  = '用户不存在';
            $result['data'] = null;
        }
        $this->ajaxReturn($result);

    }

}