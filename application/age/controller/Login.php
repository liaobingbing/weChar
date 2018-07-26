<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/5/15
 * Time: 9:54
 */

namespace app\age\controller;

use common\controller\ApiLogin;
use app\age\model\User;
use app\age\model\WechatTmpMsg;
use think\Db;

class Login extends ApiLogin
{
    public function login()
    {
        $userdao =new User();
        $code = input("post.code");
        $userInfo=input('post.userInfo');//获取前台传送的用户信息
        $userInfo=str_replace("&quot;","\"",$userInfo);
        $userInfo=json_decode($userInfo,true);
        //dump($userInfo);die;
      //  $login_data=$this->test_weixin($code);
        $openid=input('post.openId');//获取opendId
        $wx_key=input('post.session_key');
        if ($openid&&!empty($userInfo)) {
            session('wx_session_key',$wx_key);
            $user = $userdao->findByOpenid($openid);
            if (!$user) {
                $user_data = array();
                $user_data['openid'] = $openid;
                // $user_data['unionid'] = $userInfo['unionId'];
                $user_data['gender'] = $userInfo['gender'];
                $user_data['city'] = $userInfo['city'];
                $user_data['province'] = $userInfo['province'];
                $user_data['country'] = $userInfo['country'];
                $user_data['avatar_url'] =  str_replace('/0','/132',$userInfo['avatarUrl'] );
                $user_data['login_time'] =time();
                $user_data['nickname'] = $userInfo['nickName'];
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
            $arr=resCode(200,$userInfo,array('session_key'=>$session_k));
            return $arr;
        }else {
            $arr=resCode(400,"code",$login_data);
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
            $arr=array("code"=>200,"msg"=>"success","data"=>array("openId"=>$openid,"wx_session_key"=>$session_key),"status"=>2);
            //$arr=array("code"=>200,"msg"=>"success","data"=>array("openId"=>$openid,"wx_session_key"=>$session_key));
            return $arr;
        }
        else{
           return $login_data;

        }
    }

    // 测试发送模板消息
    public function test_send_template()
    {
        // $form_id = db('xcx_formid')->where(array(
        //     'open_id' => 'o449r5bQMFk4vdn036CwSodYNi48',
        // ))
        //     ->order('add_time ASC')
        //     ->field('form_id')->find();
        //     var_dump($form_id['form_id']);exit();
        $WechatTmpMsg =new WechatTmpMsg();
        // $ACCESS_TOKEN = $WechatTmpMsg->new_get_token('wx211282b5a1734159','e9e27a093fef74c04bcf228f25297a7c');
        // var_dump($ACCESS_TOKEN);exit();
        // $userList = db('xcx_formid')->select();
        $openid = 'o449r5bQMFk4vdn036CwSodYNi48';
        $data =  array(0=>'模板名称5',1=>'模板内容5',2=>'备注消息5');
        $url = 'pages/index/index?isGo=1';
        $res = $WechatTmpMsg->send_template($openid,$data,$url);
        var_dump($res);
    }

    /**
     * 小程序formid写入
     * @param form_id 小程序formid
     * @param open_id 微信openid
     */
    /*public function addXcxFormId() {
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
    }*/
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
    $data=cache("age_formid");
    if(empty($data)){
        $data[]=$arr;
        cache("age_formid",$data);
    }else if(count($data)<5000){
        array_push($data,$arr);
        cache("age_formid",$data);
    }else{
        Db::name('xcx_formid')->insertAll($data);
        cache("age_formid",null);
    }
}

    public function cache_formid()
    {
        $data=cache("age_formid");
        if(!empty($data)){
            Db::name('xcx_formid')->insertAll($data);
            cache("age_formid",null);
        }
    }

    //查询缓存的长度
    public function cache_long(){
        $data=cache("age_formid");
        return count($data);
    }
}