<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/6/12
 * Time: 10:28
 */

namespace app\findcolor\controller;

use think\Db;
use think\Cache;
use app\findcolor\model\Questions;
use app\findcolor\model\UserGame;
use app\findcolor\model\Users;
use common\controller\ApiLogin;

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
                $uid = db('users')->insertGetId($user_data);
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

    // 总挑战次数
    public function challenge_num()
    {
        $count_challenge=cache("findcolor_challenge");
        if(!$count_challenge){
            $count_challenge=Db::name('user_game')->sum('challenge_num');
            cache("findcolor_challenge",$count_challenge+5000,86400);
        }else{
            Cache::inc("findcolor_challenge");
        }
        $result=resCode(200,"success",array('count_challenge' => $count_challenge));
        return $result;
    }

    // 毅力榜
    public function challenge_top()
    {
        $UserGame = new UserGame();
        $rankings = $UserGame->get_rankings('challenge_num', 8);
        $result = array('code' => 400, 'msg' => '获取失败');

        if ($rankings) {
            $result['code'] = 200;
            $result['msg'] = '获取成功';
            $result['data'] = array('ranking_list' => $rankings);
        }

        return $result;
    }

    // 荣耀榜
    public function get_prize_top()
    {
        $UserGame = new UserGame();
        $rankings = $UserGame->get_rankings('get_prize', 8);
        $result = array('code' => 400, 'msg' => '获取失败');

        if ($rankings) {
            $result['code'] = 200;
            $result['msg'] = '获取成功';
            $result['data'] = array('ranking_list' => $rankings);
        }

        return $result;
    }

    // 测试 - 登录接口
    public function test_login()
    {
        $user_id = input('id', 4);
        $Users = new Users();
        $user = $Users->find_by_user_id($user_id);

        $session_id = session_id();
        session('user_id', $user['id']);
        session('openid', $user['openid']);
        if ($session_id) {
            print_r(session());
            echo '登录成功';
        }
    }

// 获取题目
    public function get_question()
    {
        $layer = input('layer', 1);
        $openId = input("post.openId");
        if ($layer == 1) {
            cache($openId, null);
            cache('find_color_questions', null);
            $Questions = new Questions();
            $questions = $Questions->get_rand_questions();
            //print_r($questions);die;
            cache($openId, $questions, 3600);//获取所有的题目并且放在session中
            // S('color_questions',$questions);//获取所有的题目并且放在session中
        }

        $questions = cache($openId);
        $option = array_shift($questions);
        cache($openId, $questions, 3600);
        //print_r($questions);die;
        if (!$option) {
           $arr=array('code' => 400, 'msg' => '获取失败');
           return $arr;
        }


        if ($layer <= 2) {
            $i = 4;//4个色块
            $j = 1;//色块大小100%
        } else if ($layer <= 5) {
            $i = 9;//9块色块
            $j = 0.65;//色块大小65%
        } else if ($layer <= 9) {
            $i = 16;
            $j = 0.48;
        } else if ($layer <= 14) {
            $i = 25;
            $j = 0.38;
        } else if ($layer <= 20) {
            $i = 36;
            $j = 0.32;
        } else if ($layer <= 27) {
            $i = 49;
            $j = 0.28;
        } else if ($layer <= 35) {
            $i = 64;
            $j = 0.23;
        } else if ($layer <= 45) {
            $i = 81;
            $j = 0.21;
        } else {
            return ['code' => 400, 'msg' => 'layer不能超过44'];
        }

        $arr = array();

        if ($option['answer'] != 'option_1') {
            for ($a = 0; $a < $i - 1; $a++) {
                $arr[$a]['text'] = $option['option_1'];
                $arr[$a]['percent'] = $j;
            }
            if ($layer == 44) {
                $arr[$i - 1]['text'] = $option['option_1'];
                $arr[$i - 1]['percent'] = $j;
            } else {
                $arr[$i - 1]['text'] = $option['option_2'];
                $arr[$i - 1]['percent'] = $j;
            }

            $answer = $option['option_2'];
        } else {
            for ($a = 0; $a < $i - 1; $a++) {
                $arr[$a]['text'] = $option['option_2'];
                $arr[$a]['percent'] = $j;
            }
            if ($layer == 44) {
                $arr[$i - 1]['text'] = $option['option_2'];
                $arr[$i - 1]['percent'] = $j;
            } else {
                $arr[$i - 1]['text'] = $option['option_1'];
                $arr[$i - 1]['percent'] = $j;
            }
            $answer = $option['option_1'];
        }

        shuffle($arr);

        $result['code'] = 200;
        $result['msg'] = '获取成功';
        $result['data']['words'] = $arr;
        $result['data']['answer'] = $answer;
        $result['data']['next_layer'] = $layer + 1;


        return $result;
    }

    // 测试 - 登录退出
    public function test_logout()
    {
        session(null);
        echo '退出成功';
    }

    public function set_session()
    {
        session("user_id", 8);
    }

    //分享群
    public function share_group()
    {
        $data = array("code" => 200, "msg" => "success", "data" => null);
        return $data;
    }

    public function get_openid()
    {
        $code = input('post.code');
        $login_data = $this->test_weixin($code);
        if (empty($login_data['code'])&&$login_data['openid']) {
            $openid = $login_data['openid'];
            $session_key = $login_data['session_key'];
            $arr = array("code" => 200, "msg" => "success", "data" => array("openId" => $openid, "wx_session_key" => $session_key));
            return $arr;
        } else {
            return $login_data;
        }
    }
}