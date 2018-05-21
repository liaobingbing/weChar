<?php
namespace Place\Controller;

use Common\Controller\ApiLoginController;
use Place\Model\UsersModel;


class LoginController extends ApiLoginController {

    private $key='kuaiyu666666';


    //小程序登录
    public function login(){
        $userdao=new UsersModel();
        $openid=I("post.openId");
        $session_key=I('post.session_key');
        $userInfo=I('post.userInfo');//获取前台传送的用户信息
        $userInfo=str_replace("&quot;","\"",$userInfo);
        $userInfo=json_decode($userInfo,true);
        if($openid&&$userInfo){
            session('wx_session_key',$session_key);
            $user = $userdao->findByOpenid($openid);
            if ($user) {
                if($user['status']==0) {
                    $data['code']=403;//已经被拉黑
                    $data['msg']='已经被拉黑';
                    $this->ajaxReturn($data,'JSON');
                }
                $user_data['id'] = $user["id"];
                $user_data['gender'] = $userInfo['gender'];
                $user_data['city'] = $userInfo['city'];
                $user_data['province'] = $userInfo['province'];
                $user_data['country'] = $userInfo['country'];
                $user_data['avatar_url'] =  str_replace('/0','/132',$userInfo['avatarUrl'] );
                $user_data['nickname'] = $userInfo['nickName'];
                $user_data['add_time'] = time();
                $user_data['last_time'] = time();
                $user_data['login_time'] = time();
                M('users')->save($user_data);
                $user_game['uid']=$user['id'];
                $user_game['nickname']=$userInfo['nickName'];
                $user_game['avatar_url']=str_replace('/0','/132',$userInfo['avatarUrl'] );
                M('user_game')->where('uid',$user['id'])->save($user_game);
            }
           // $session_k=session_id();
            session('user_id',$user["id"],3600);
            session("openid",$openid);
            $data['code']=200;
            $data['msg']='success';
            //$data['data']=array('session_key'=>$session_k);
            $this->ajaxReturn($data,'JSON');

        }

    }

//更新世界排行
    public function gd_world_ranking(){
        $key=I('get.key');
        if($key==$this->key){
            $world_arr=array();
            $ranking_arr=M('user_game')->field('uid,avatar_url,nickname,gold_num,success_num')->order('success_num desc')->select();
            foreach($ranking_arr as $k=>$v){
                $ranking=$k+1;
                $world_arr[$ranking]=$v;
                $world_arr[$ranking]['ranking']=$ranking;
            }
            S('gd_world_ranking',$world_arr);
            $s=date("Y-m-d H:i:s",time());
            file_put_contents('gd_cache_time.txt',$s);
        }
    }


    public function post_url($url,$data){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT,15);   //只需要设置一个秒的数量就可以
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // post数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $output = curl_exec($ch);
        curl_close($ch);
        //打印获得的数据
        //print_r($output);
        return $output;
    }
    public function get_openid()
    {
        $userdao=new UsersModel();

        $code = I('post.code');
        $login_data = $this->test_weixin($code);
        if ($login_data['code'] != 400) {
            $openid = $login_data['openid'];
            $user = $userdao->findByOpenid($openid);
            if(empty($user)){
                $data['openid']= $openid;
                $data['login_time']=time();
                $user_id = $userdao->add($data);
                $game["uid"]=$user_id;
                M('user_game')->add($game);
                $uid=$user_id;
            }
            $uid=$user['id'];
            $session_k=session_id();
            session('user_id',$uid,3600);
            $session_key=$login_data['session_key'];
            $arr=array("code"=>200,"msg"=>"success","data"=>array("openId"=>$openid,"wx_session_key"=>$session_key,"server_key"=>$session_k,"user_id"=>$uid));
            $this->ajaxReturn($arr);
        }
        else{
            $this->ajaxReturn($login_data);

        }
    }
}