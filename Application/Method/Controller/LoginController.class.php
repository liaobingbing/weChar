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
use Method\Model\UserGameModel;

class LoginController extends  ApiLoginController
{

    private  $key="kuaiyu666666";


    public function login(){
        $userdao=new UsersModel();
        $code=I('post.code');
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
            $user_info=M('user_game')->field('challenge_num,avatar_url,nickname')->where('avatar_url is not null')->order('challenge_num desc')->limit(8)->select();
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
            $user_info=M('user_game')->field('challenge_num,avatar_url,nickname')->where('avatar_url is not null')->order('challenge_num desc')->limit(8)->select();
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

    /**
     * 小程序formid写入
     * @param form_id 小程序formid
     * @param open_id 微信openid
     */
    public function addXcxFormId() {
        $form_id = I('form_id');
        $open_id = I('open_id');

        if (empty($form_id) || empty($open_id) || $form_id == 'the formId is a mock one' || $form_id == 'undefined') {
            $arr=array("code"=>200,"msg"=>"SUCCESS");
            $this->ajaxReturn($arr);
        }
        if (M('xcx_formid')->add(array(
            'form_id' => $form_id,
            'open_id' => $open_id,
            'add_time' => time()
        ))) {
            $arr=array("code"=>200,"msg"=>"SUCCESS");
        }else{
            $arr=array("code"=>400,"msg"=>"网络错误");
        }
        $this->ajaxReturn($arr);
    }
}