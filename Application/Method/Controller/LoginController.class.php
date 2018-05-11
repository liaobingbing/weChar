<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/4/14
 * Time: 10:47
 */

namespace Method\Controller;


use Common\Controller\ApiLoginController;
use Method\Model\AnswerModel;
use Method\Model\UsersModel;

class LoginController extends  ApiLoginController
{

    private  $key="kuaiyu666666";


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
                //$user_data['unionid'] = $login_data['unionId'];
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
    //获取题目
    public function get_question(){
                $layer=I('post.layer',1);
                $openId=I('post.openId');
                if($layer<=30){
                    $answerdao=new AnswerModel();
                    $question=$answerdao->get_question($layer,$openId);
                    if($question){
                        $data['code']=200;
                        $data['msg']='获取成功';
                        $data['data']['layer']=$layer;
                        $data['data']['nex_layer']=$layer+1;
                        $data['data']['subject1']=$question['subject1'];
                        $data['data']['subject2']=$question['subject2'];
                        if($layer>23){
                            $odds=($layer-23)*100;
                        }else{
                            $odds=0;
                        }
                        $rand=rand(0,500);
                        if($rand>$odds){
                            $data['data']['answer']=$question['answer'];
                        }else{
                            $data['data']['answer']=2;
                        }
                    }else{
                        $data['code']=400;
                        $data['msg']='题库出错';
                    }

                }else{
                    $data['code']=400;
                    $data['msg']='没有此等级';
                }
        $this->ajaxReturn($data,'JSON');
    }
    public function get_openid()
    {
        $code = I('post.code');
        $login_data = $this->test_weixin($code);
        if ($login_data['code'] != 400) {
            $openid = $login_data['openid'];
            $arr=array("code"=>200,"msg"=>"success","data"=>array("openId"=>$openid));
            $this->ajaxReturn($arr);
        }
        else{
            $this->ajaxReturn($login_data);

        }
    }
    //分享群
    public function share_group(){
        $data=array("code"=>200,"msg"=>"success","data"=>null);
        $this->ajaxReturn($data,'JSON');
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
           /* $sql1="SELECT avatar_url as avatarUrl,gt_number,nickname FROM method_user_game order by gt_number desc limit 3";
            $data1=M()->query($sql1);*/
            $sql2="SELECT avatar_url as avatarUrl ,get_number as gt_number ,nickname FROM method_user_game   order by  gt_number desc LIMIT 0,8";
            $user_info2=M()->query($sql2);
           // $user_info2=array_merge($data1,$data2);
            foreach($user_info2 as $k=>$v){
                $user_info2[$k]['ranking']=$k+1;
            }
            S("m_num_top",$user_info);//缓存
            S("m_intelligence_top",$user_info2);
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
        $data['data']=$count_challenge;
        $this->ajaxReturn($data);
    }
    //智力榜
    public function intelligence_top()
    {
        $user_info = S('m_intelligence_top');
        if(!$user_info){
            //SELECT avatarUrl,gt_number as number,nickname FROM method_user_game WHERE id >= ((SELECT MAX(id) FROM method_user_game)-(SELECT MIN(id) FROM method_user_game)) * RAND() + (SELECT MIN(id) FROM method_user_game)  order by  number desc LIMIT 5;
           /* $sql1="SELECT avatar_url as avatarUrl,gt_number,nickname FROM method_user_game order by gt_number desc limit 3";
            $data1=M()->query($sql1);*/
            $sql2="SELECT avatar_url as avatarUrl ,get_number as gt_number ,nickname FROM method_user_game   order by  gt_number desc LIMIT 0,8";
            $user_info=M()->query($sql2);
           // $user_info=array_merge($data1,$data2);
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
        $user_info = S('m_num_top');
        if(!$user_info){
            $user_info=M('user_game')->field('challenge_num,avatar_url,nickname')->order('challenge_num desc')->limit(8)->select();
            foreach($user_info as $k=>$v){
                $user_info[$k]['ranking']=$k+1;
            }
            S("m_num_top",$user_info);
        }
        $arr=array('code'=>200,'msg'=>'success','data'=>$user_info);
        $this->ajaxReturn($arr);
    }
    //设置session
    public function set_session(){
        session('user_id',7535);
    }
}