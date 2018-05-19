<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/4/14
 * Time: 10:47
 */

namespace Sort\Controller;


use Common\Controller\ApiLoginController;
use Sort\Model\UsersModel;
use Sort\Model\UserGameModel;

class LoginController extends  ApiLoginController
{
    private  $key="kuaiyu666666";

    public function login(){
        $userdao=new UsersModel();
        $code=I('post.code');;
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
                //$user_data['unionid'] = $login_data['unionId'];
                $user_data['gender'] = $userInfo['gender'];
                $user_data['city'] = $userInfo['city'];

                $user_data['province'] = $userInfo['province'];
                $user_data['country'] = $userInfo['country'];
                $user_data['avatar_url'] =  str_replace('/0','/132',$userInfo['avatarUrl'] );
                $user_data['nickname'] = $userInfo['nickName'];
                $user_data['login_time'] = time();
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
                    $this->ajaxReturn($data,'JSON');
                }
                $uid=$user['id'];
            }
            $session_k=session_id();
            session('user_id',$uid,3600);
            session("openid",$openid);
            $data['code']=200;
            $data['msg']=$userInfo;
            $data['data']=array('session_key'=>$session_k);

            $this->ajaxReturn($data,'JSON');

        }
        else{
            $login_data=array("code"=>400,"msg"=>"error","data"=>null);
            $this->ajaxReturn($login_data);
        }

    }

//缓存挑战次数
    public function cache_num()
    {
        $key=I('get.key');
        if($key==$this->key){
            $user_info=M('user_game')->field('challenge_num,avatar_url,nickname')->order('challenge_num desc')->limit(8)->select();
            foreach($user_info as $k=>$v){
                $user_info[$k]['ranking']=$k+1;
            }
            $sql2="SELECT avatar_url  ,get_number as gt_number ,nickname FROM sort_user_game   order by  gt_number desc LIMIT 0,8";
            $user_info2=M()->query($sql2);
            foreach($user_info2 as $k=>$v){
                $user_info2[$k]['ranking']=$k+1;
            }
            S("sort_num_top",$user_info);
            S("sort_intelligence_top",$user_info2);
        }
    }
    //统计挑战的次数
    public function count_challenge()
    {
        $count_challenge=M('user_game')->sum('challenge_num');
        if($count_challenge<=5000){
            $count_challenge=$count_challenge+5000;
        }
        $data['code']=200;
        $data['msg']='success';
        $data['data']['count_challenge']=$count_challenge;
        $this->ajaxReturn($data);
    }
    //智力榜
    public function intelligence_top()
    {
        $user_info = S('sort_intelligence_top');
        if(!$user_info){

            $sql2="SELECT avatar_url  ,get_number as gt_number ,nickname FROM sort_user_game   order by  gt_number desc LIMIT 0,8";
            $user_info=M()->query($sql2);
            foreach($user_info as $k=>$v){
                $user_info[$k]['ranking']=$k+1;
            }
            S("m_intelligence_top",$user_info);
        }
        // $user_info=M('user_game')->field('get_number,avatar_url,nickname')->order('get_number desc')->limit(5)->select();
        $arr=array('code'=>200,'msg'=>'success','data'=>$user_info);
        $this->ajaxReturn($arr);
    }
    //毅力榜
    public function num_top()
    {
        $user_info = S('sort_num_top');
        if(!$user_info){
            $user_info=M('user_game')->field('challenge_num,avatar_url,nickname')->order('challenge_num desc')->limit(8)->select();
            foreach($user_info as $k=>$v){
                $user_info[$k]['ranking']=$k+1;
            }
            S("sort_num_top",$user_info);
        }
        $arr=array('code'=>200,'msg'=>'success','data'=>$user_info);
        $this->ajaxReturn($arr);
    }
//获取题目
    public function get_question(){
            $layer=I('post.layer',1);
            if($layer<=5){
                $arr_num=($layer+2)*($layer+2);
                for($i=1;$i<=$arr_num;$i++){
                    $arr['num']=$i;
                    $arr['status']=false;
                    $question[]=$arr;
                }
                shuffle($question);
                $next_layer=$layer+1;
                $data['code']=200;
                $data['msg']='获取成功';
                $data['data']['question']=$question;
                $data['data']['layer']=$layer;
                $data['data']['next_layer']=$next_layer;
            }else{
                $data['code']=400;
                $data['msg']='没有此等级';
            }
        $this->ajaxReturn($data,'JSON');
    }
    //分享群
    public function share_group(){
        $data=array("code"=>200,"msg"=>"success","data"=>null);
        $this->ajaxReturn($data,'JSON');
    }
    //设置session
    public function set_session(){
        echo  session_id();;
        session('user_id',1);
    }
    public function get_openid()
    {
        $code = I('post.code');
        $login_data = $this->test_weixin($code);
        if ($login_data['code'] != 400) {
            $openid = $login_data['openid'];
            $session_key=$login_data['session_key'];
            $arr=array("code"=>200,"msg"=>"success","data"=>array("openId"=>$openid,"wx_session_key"=>$session_key));
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