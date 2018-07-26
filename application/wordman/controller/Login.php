<?php
/**
 * Created by PhpStorm.
 * User: HZM
 * Date: 2018/4/18
 * Time: 18:45
 */

namespace app\wordman\controller;


use app\wordman\model\Questions;
use app\wordman\model\UserGame;
use app\wordman\model\Users;
use common\controller\ApiLogin;
use think\Db;
use think\Cache;


class Login extends ApiLogin
{

    // index
    public function index()
    {
        echo 'index';
    }

    // 用户登录接口
    public function login(){
        $userdao=new Users();
        $userInfo=input('post.userInfo');//获取前台传送的用户信息
        $userInfo=str_replace("&quot;","\"",$userInfo);
        $userInfo=json_decode($userInfo,true);
        $openid=input('post.openId');//获取opendId
        $wx_key=input('post.session_key');
        if($openid&&$userInfo){
            session('wx_session_key',$wx_key);
            $user = $userdao->findByOpenid($openid);
            if (!$user) {
                $user_data['openid'] = $openid;
               // $user_data['unionid'] = $login_data['unionId'];
                $user_data['gender'] = $userInfo['gender'];
                $user_data['city'] = $userInfo['city'];
                $user_data['province'] = $userInfo['province'];
                $user_data['country'] = $userInfo['country'];
                $user_data['avatar_url'] =  $userInfo['avatarUrl'];
                $user_data['name'] = $userInfo['nickName'];
                $uid = db('users')->data($user_data)->insertGetId();
                $user_game['uid']=$uid;
                $user_game['nickname']=$userInfo['nickName'];
                $user_game['login_time'] = time();
                $user_game['avatar_url']=$userInfo['avatarUrl'];
                db('user_game')->insert($user_game);
            }else{
                if($user['status']==0) {
                    $data['code']=403;//已经被拉黑
                    $data['msg']='已经被拉黑';
                    $data['data']['user_id']=$user['id'];
                    return $data;
                }
               // $user_data['id'] = $user['id'];
                $user_data['openid'] = $openid;
                $user_data['gender'] = $userInfo['gender'];
                $user_data['city'] = $userInfo['city'];
                $user_data['province'] = $userInfo['province'];
                $user_data['country'] = $userInfo['country'];
                $user_data['avatar_url'] = $userInfo['avatarUrl'];
                $user_data['name'] = $userInfo['nickName'];
                db('users')->where("id",$user['id'])->update($user_data);
                $user_game['uid']=$user['id'];
                $user_game['nickname']=$userInfo['nickName'];
                $user_game['login_time'] = time();
                $user_game['avatar_url']=$userInfo['avatarUrl'];
                db('user_game')->where("uid",$user['id'])->update($user_game);
                $uid=$user['id'];
            }
            $data['code']=200;
            $data['msg']='success';
            return $data;

        }
        else{
            $arr=array("code"=>400,"msg"=>"参数不全",null);
            return $arr;
        }

    }
    // 获取题目
    public function get_question(){
        $layer = input('layer',1);
        $openId=input("post.openId");
        if($layer == 1){
            cache($openId,null);
            cache("find_word_questions",null);
            $Questions = new Questions();
            $questions = $Questions->get_rand_questions(44);
            cache($openId,$questions,3600);
        }

        $questions = cache($openId);

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

        return $result;
    }

    // 总挑战次数
    public function challenge_num(){
        $count_challenge=cache("wordman_challenge");
        if(!$count_challenge){
            $count_challenge=Db::name('user_game')->sum('challenge_num');
            cache("wordman_challenge",$count_challenge+5000,86400);
        }else{
            Cache::inc("wordman_challenge");
        }
        $result=resCode(200,"success",array('count_challenge' => $count_challenge));
        return $result;
    }

    // 毅力榜
    public function challenge_top(){
        $UserGame = new UserGame();
        $rankings = $UserGame->get_rankings('challenge_num',8);
        $result = array('code'=>400, 'msg'=>'获取失败');

        if($rankings){
            $result['code'] = 200;
            $result['msg']  = '获取成功';
            $result['data'] = array('ranking_list' => $rankings);
        }

        return $result;
    }

    // 荣耀榜
    public function get_prize_top(){
        $UserGame = new UserGame();
        $rankings = $UserGame->get_rankings('get_prize',8);
        $result = array('code'=>400, 'msg'=>'获取失败');

        if($rankings){
            $result['code'] = 200;
            $result['msg']  = '获取成功';
            $result['data'] = array('ranking_list' => $rankings);
        }

        return $result;
    }

    // 测试 - 登录接口
    public function test_login(){
        $user_id = input('id',4);
        $Users = new Users();
        $user = $Users->find_by_user_id($user_id);

        $session_id =session_id();
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
        return $data;
    }
    public function get_openid()
    {
        $code = input('post.code');
        $login_data = $this->test_weixin($code);
        if (empty($login_data['code'])&&$login_data['openid']) {
            $openid = $login_data['openid'];
            $session_key=$login_data['session_key'];
            $arr=array("code"=>200,"msg"=>"success","data"=>array("openId"=>$openid,"wx_session_key"=>$session_key));
            $userdao=new Users();
            $user = $userdao->findByOpenid($openid);
            if(empty($user)){
                $data['openid']=$openid;
                $uid = db("users")->insertGetId($data);
                $game['uid']=$uid;
                $game['login_time'] = time();
                db("user_game")->insert($game);

            }
            return $arr;
        }
        else{
            return $login_data;

        }
    }

    //开始 挑战
    public function begin_challenge(){
         $openId=input("post.openId");
        $UserGame = new UserGame();
        $userdao=new Users();
        $user=$userdao->findByOpenid($openId);
        $user_id=$user['id'];
        $user_game = $UserGame->find_by_user_id($user_id);
        if($user_game) {
            db('user_game')->where(array('uid' => $user_id))->setInc('challenge_num');
            $result['code'] =   200;
            $result['msg']  =   '开始挑战成功';
            $result['data'] = null;

        }else{
            $result['code'] =400;
            $result['msg']  = '用户不存在';
            $result['data'] = null;
        }
        return $result;

    }

    /**
     * 小程序formid写入
     * @param form_id 小程序formid
     * @param open_id 微信openid
     */
    public function addXcxFormId() {
        $form_id = input('form_id');
        $open_id = input('open_id');

        if (empty($form_id) || empty($open_id) || $form_id == 'the formId is a mock one' || $form_id == 'undefined') {
            $arr=array("code"=>200,"msg"=>"SUCCESS");
            return $arr;
        }
        if (db('xcx_formid')->insert(array(
            'form_id' => $form_id,
            'open_id' => $open_id,
            'add_time' => time()
        ))) {
            $arr=array("code"=>200,"msg"=>"SUCCESS");
        }else{
            $arr=array("code"=>400,"msg"=>"网络错误");
        }
       return $arr;
    }

}