<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/5/15
 * Time: 9:54
 */

namespace app\armyhand\controller;

use common\controller\ApiLogin;
use app\armyhand\model\User;
use think\Db;

class Login extends ApiLogin
{
    //授权的接口
    public function login()
    {
        $openId=input("post.openId");
        $userName=input("userName");
        $userImg=input("userImg");
        $userdao=new User();
        $user = $userdao->findByOpenid($openId);
        if($user&&empty($user['user_name'])){
            $data['nickname']=$userName;
            $data['avatar_url']=$userImg;
            if(Db::name("users")->where("openid",$openId)->update($data)){
                $arr=resCode(200,"ok",null);
                return $arr;
            }else{
                $arr=resCode(400,"已经更新头像",null);
                return $arr;
            }
        }else{
            $arr=resCode(400,"无此人",null);
            return $arr;
        }
    }
    //获取小程序的openid
    public function get_openid()
    {
        $code = input('post.code');
        $login_data = $this->test_weixin($code);
        if (empty($login_data['400'])&&$login_data['openid']) {
            $openid = $login_data['openid'];
            $session_key=$login_data['session_key'];
            $userdao=new User();
            $user = $userdao->findByOpenid($openid);
            $user_id=$user['id'];
            if(empty($user)){
                $data['openid']=$openid;
                $data['login_time']=time();
                $user_id=db("users")->insertGetId($data);
            }
            $arr=array("code"=>200,"msg"=>"success","data"=>array("openId"=>$openid,"wx_session_key"=>$session_key,"user_id"=>$user_id,"status"=>1));
            return $arr;
        }
        else{
            return $login_data;

        }
    }
    public function addXcxFormId()
{
    $form_id = input('form_id');
    $open_id = input('open_id');

    if (empty($form_id) || empty($open_id) || $form_id == 'the formId is a mock one' || $form_id == 'undefined') {
        $arr=resCode(200,"SUCCESS");
        return $arr;
    }
    $arr=['form_id' => $form_id,
        'open_id' => $open_id,
        'add_time' => time()
    ];
    $data=cache("army_hand_formid");
    if(empty($data)){
        $data[]=$arr;
        cache("army_hand_formid",$data);
    }else if(count($data)<5000){
        array_push($data,$arr);
        cache("army_hand_formid",$data);
    }else{
        Db::name('xcx_formid')->insertAll($data);
        cache("army_hand_formid",null);
    }
}

    public function cache_formid()
    {
        $data=cache("army_hand_formid");
        if(!empty($data)){
            Db::name('xcx_formid')->insertAll($data);
            cache("army_hand_formid",null);
        }
    }

    //查询缓存的长度
    public function cache_long(){
        $data=cache("army_hand_formid");
        return count($data);
    }
}