<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/6/29
 * Time: 11:28
 */

namespace app\chengyu\controller;

use common\controller\ApiLogin;
use think\Db;
use app\chengyu\model\Users;
use think\Controller;

class login extends ApiLogin
{
    //小程序登录
    public function login()
    {

        $userdao = new Users();
        $userInfo=input('post.userInfo');//获取前台传送的用户信息
        $userInfo=str_replace("&quot;","\"",$userInfo);
        $userInfo=json_decode($userInfo,true);
        $openid=input('post.openId');//获取opendId
        if($openid&&$userInfo){
            $user = $userdao->findByOpenid($openid);
            if (!$user) {
                $user_data = array();
                $user_data['openid'] = $openid;
                $user_data['gender'] = $userInfo['gender'];
                $user_data['city'] = $userInfo['city'];
                $user_data['add_time'] = time();
                $user_data['province'] = $userInfo['province'];
                $user_data['country'] = $userInfo['country'];
                $user_data['avatarUrl'] =  $userInfo['avatarUrl'];
                $user_data['add_time'] =time();
                $user_data['last_time'] =time();
                $user_data['nickname'] = $userInfo['nickName'];
                $uid = Db::name('users')->insertGetId($user_data);
                $user_game['uid']=$uid;
                $user_game['nickname']=$userInfo['nickName'];
                $user_game['gold_num']=200;
                $user_game['avatarUrl']=$userInfo['avatarUrl'];
                Db::name('user_game')->insert($user_game);
            }else{
                $user_data["id"]=$user["id"];
                $user_data['openid'] = $openid;
                $user_data['gender'] = $userInfo['gender'];
                $user_data['city'] = $userInfo['city'];
                $user_data['add_time'] = time();
                $user_data['province'] = $userInfo['province'];
                $user_data['country'] = $userInfo['country'];
                $user_data['avatarUrl'] =  $userInfo['avatarUrl'];
                $user_data['add_time'] =time();
                $user_data['last_time'] =time();
                $user_data['nickname'] = $userInfo['nickName'];
                Db::name('users')->where("id",$user["id"])->update($user_data);
                $user_game['uid']=$user["id"];
                $user_game['nickname']=$userInfo['nickName'];
                $user_game['gold_num']=200;
                $user_game['avatarUrl']=$userInfo['avatarUrl'];
                Db::name('user_game')->where("uid",$user["id"])->update($user_game);
            }
            $data['code']=200;
            $data['msg']='success';
            return $data;
        } else {
            $data['code']=400;
            $data['msg']='登录失败';
            return $data;
        }
    }

    //获取用户opendId
    public function get_openid()
    {
        $code = input('code');
        $login_data = $this->test_weixin($code);
       // $login_data=json_decode($login_data,true);
        if (empty($login_data['code'])&&$login_data['openid']) {
            $openid = $login_data['openid'];
            $session_key=$login_data['session_key'];
            $user_id = Db::name("users")->where(array('openid'=>$openid))->value('id');
            if(!$user_id){
                $data['openid']= $openid;
                $data['login_at']=time();
                $user_id = Db::name("users")->insertGetId($data);
                $game['uid']=$user_id;
                Db::name('user_game')->insert($game);
            }

            $arr=array("code"=>200,"msg"=>"success","data"=>array("openId"=>$openid,"wx_session_key"=>$session_key,"server_key"=>"","user_id"=>$user_id,"status"=>1));
           return $arr;
        }
        else{
            return $login_data;

        }
    }



    /**
     * 小程序formid写入
     * @param form_id 小程序formid
     * @param open_id 微信openid
     */
    public function addXcxFormId() {
        $form_id = input('form_id');
        $open_id = input('open_id');

        !$form_id && $arr=array("code"=>400,"msg"=>"form_id不能为空");
        !$open_id && $arr=array("code"=>400,"msg"=>"open_id不能为空");

        if ($form_id == 'the formId is a mock one' || $form_id == 'undefined') {
            $arr=array("code"=>200,"msg"=>"SUCCESS");
            return $arr;
        }
        if (Db::name('xcx_formid')->insert(array(
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