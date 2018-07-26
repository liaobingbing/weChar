<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/5/15
 * Time: 9:54
 */

namespace app\word\controller;


use common\controller\ApiLogin;
use app\word\model\User;
class Login extends ApiLogin
{
    //login 接口
    public function login()
    {
        $userdao =new User();
        $openid=input('post.openId');//获取opendId
        $wx_key=input('post.session_key');
        if ($openid) {
            session('wx_session_key',$wx_key);
            $user = $userdao->findByOpenid($openid);
            if (!$user) {
                $user_data = array();
                $user_data['openid'] = $openid;
                $user_data['login_time'] =time();
                $uid =db('users')->insertGetId($user_data);;
            }else{
                if($user['status']==0) {
                    $arr=resCode(403,"已经被拉黑",array("user_id"=>$user['id']));
                    return $arr;
                }
                $uid=$user['id'];
            }
            $session_k=session_id();
            session('user_id',$uid);
            $arr=resCode(200,"success",array('session_key'=>$session_k));
            return $arr;
        }else {
            $arr=resCode(400,"code","参数错误");
            return $arr;
        }
    }
    public function set_session()
    {
        session("user_id",1);
    }
    //获取openId
    public function get_openid()
    {
        $code = input('post.code');
        $login_data = $this->test_weixin($code);
        if (empty($login_data['code'])) {
            $openid = $login_data['openid'];
            $session_key=$login_data['session_key'];
            $arr=array("code"=>200,"msg"=>"success","data"=>array("openId"=>$openid,"wx_session_key"=>$session_key));
            return $arr;
        }
        else{
            return login_data;

        }
    }
    //分享群
    public function share_group(){
        $data=array("code"=>200,"msg"=>"success","data"=>null);
        return $data;
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
            $arr=resCode(200,"SUCCESS");
            return $arr;
        }
        if (db('xcx_formid')->insert(array(
            'form_id' => $form_id,
            'open_id' => $open_id,
            'add_time' => time()
        ))) {
            $arr=resCode(200,"SUCCESS");
        }else{
            $arr=resCode(400,"网络错误");
        }
        return $arr;
    }
}