<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/6/26
 * Time: 14:10
 */

namespace app\sort\controller;

use app\sort\model\Users;
use app\sort\model\UserGame;
use common\controller\ApiLogin;

class Login extends ApiLogin
{
	private  $key="kuaiyu666666";

    public function login(){
        $userdao=new Users();
        $code=input('post.code');;
        $userInfo=input('post.userInfo');//获取前台传送的用户信息
        $userInfo=str_replace("&quot;","\"",$userInfo);
        $userInfo=json_decode($userInfo,true);
        $openid=input('post.openId');//获取opendId
        $wx_key=input('post.session_key');
        if($openid&&$userInfo){
            // session('wx_session_key',$wx_key);
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
                $uid = db('users')->insertGetId($user_data);
                $user_game['uid']=$uid;
                $user_game['nickname']=$userInfo['nickName'];
                $user_game['login_time'] = time();
                $user_game['avatar_url']=str_replace('/0','/132',$userInfo['avatarUrl'] );
				db('user_game')->insertGetId($user_game);
            }else{
                if($user['status']==0) {
                    $data['code']=403;//已经被拉黑
                    $data['msg']='已经被拉黑';
                    return $data;
                    // $this->ajaxReturn($data,'JSON');
                }
                $user_data['id'] = $user['id'];
                $user_data['openid'] = $openid;
                //$user_data['unionid'] = $login_data['unionId'];
                $user_data['gender'] = $userInfo['gender'];
                $user_data['city'] = $userInfo['city'];

                $user_data['province'] = $userInfo['province'];
                $user_data['country'] = $userInfo['country'];
                $user_data['avatar_url'] =  str_replace('/0','/132',$userInfo['avatarUrl'] );
                $user_data['nickname'] = $userInfo['nickName'];
                $user_data['login_time'] = time();
                db('users')->where('openid',$openid)->update($user_data);
                $user_game['uid']=$user['id'];
                $user_game['nickname']=$userInfo['nickName'];
                $user_game['login_time'] = time();
                $user_game['avatar_url']=str_replace('/0','/132',$userInfo['avatarUrl'] );
                db('user_game')->where('uid',$user['id'])->update($user_game);
                $uid=$user['id'];
            }
            cache('user_id',$uid,3600);
            cache("openid",$openid);
            $data['code']=200;
            $data['msg']=$userInfo;
            $data['data']=array('session_key'=>$wx_key);
            return $data;
            // $this->ajaxReturn($data,'JSON');

        }
        else{
            $login_data=array("code"=>400,"msg"=>"error","data"=>null);
            return $data;
            // $this->ajaxReturn($login_data);
        }

    }

	//缓存挑战次数
    public function cache_num()
    {
        $key=input('get.key');
        if($key==$this->key){
            $user_info=db('user_game')->field('challenge_num,avatar_url,nickname')->where("avatar_url is not null")->order('challenge_num desc')->limit(8)->select();
            foreach($user_info as $k=>$v){
                $user_info[$k]['ranking']=$k+1;
            }
            $sql2="SELECT avatar_url  ,get_number as gt_number ,nickname FROM sort_user_game   where(avatar_url is not null) order by  gt_number desc LIMIT 0,8";
            $user_info2=db()->query($sql2);
            foreach($user_info2 as $k=>$v){
                $user_info2[$k]['ranking']=$k+1;
            }
            cache("sort_num_top",$user_info);
            cache("sort_intelligence_top",$user_info2);
        }
    }
    //统计挑战的次数
    public function count_challenge()
    {
        $count_challenge=db('user_game')->sum('challenge_num');
        if($count_challenge<=5000){
            $count_challenge=$count_challenge+5000;
        }
        $data['code']=200;
        $data['msg']='success';
        $data['data']['count_challenge']=$count_challenge;
        return $data;
        // $this->ajaxReturn($data);
    }
    //智力榜
    public function intelligence_top()
    {
        $user_info = cache('sort_intelligence_top');
        if(!$user_info){

            $sql2="SELECT avatar_url  ,get_number as gt_number ,nickname FROM sort_user_game  where(avatar_url is not null)  order by  gt_number desc LIMIT 0,8";
            $user_info=db()->query($sql2);
            foreach($user_info as $k=>$v){
                $user_info[$k]['ranking']=$k+1;
            }
            cache("m_intelligence_top",$user_info);
        }
        // $user_info=M('user_game')->field('get_number,avatar_url,nickname')->order('get_number desc')->limit(5)->select();
        $arr=array('code'=>200,'msg'=>'success','data'=>$user_info);
        return $arr;
        // $this->ajaxReturn($arr);
    }
    //毅力榜
    public function num_top()
    {
        $user_info = cache('sort_num_top');
        if(!$user_info){
            $user_info=db('user_game')->field('challenge_num,avatar_url,nickname')->where("avatar_url is not null")->order('challenge_num desc')->limit(8)->select();
            foreach($user_info as $k=>$v){
                $user_info[$k]['ranking']=$k+1;
            }
            cache("sort_num_top",$user_info);
        }
        $arr=array('code'=>200,'msg'=>'success','data'=>$user_info);
        return $arr;
        // $this->ajaxReturn($arr);
    }
//获取题目
    public function get_question(){
            $layer=input('post.layer',1);
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
            return $data;
        // $this->ajaxReturn($data,'JSON');
    }
    //分享群
    public function share_group(){
        $data=array("code"=>200,"msg"=>"success","data"=>null);
        return $data;
        // $this->ajaxReturn($data,'JSON');
    }
    //设置session
    public function set_session(){
        // echo  session_id();;
        cache('user_id',1);
    }
    public function get_openid()
    {
        $code = input('post.code');
        $login_data = $this->test_weixin($code);
        if (empty($login_data['code'])&&$login_data['openid']) {
            $openid = $login_data['openid'];
            $session_key=$login_data['session_key'];
            $userdao=new Users();
            $user = $userdao->findByOpenid($openid);
            $user_id = $user['id'];
            if(empty($user)){
                $data['openid']=$openid;
                $uid = db("users")->insertGetId($data);
                $user_id = $uid;
                $game['uid']=$uid;
                $game['login_time'] = time();
                db("user_game")->insertGetId($game);

            }
            $arr=array("code"=>200,"msg"=>"success","data"=>array("openId"=>$openid,"wx_session_key"=>$session_key,'user_id'=>$user_id));
            return $arr;
            // $this->ajaxReturn($arr);
        }
        else{
        	return $login_data;
            // $this->ajaxReturn($login_data);

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
            db('UserGame')->where(array('uid' => $user_id))->setInc('challenge_num');
            $result['code'] =   200;
            $result['msg']  =   '开始挑战成功';
            $result['data'] = null;

        }else{
            $result['code'] =400;
            $result['msg']  = '用户不存在';
            $result['data'] = null;
        }
        return $result;
        // $this->ajaxReturn($result);

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
        $data=cache("sort_formid");
        if(empty($data)){
            $data[]=$arr;
            cache("sort_formid",$data);
            $arr=resCode(400,"error");
            return $arr;
        }else if(count($data)<5000){
            array_push($data,$arr);
            cache("sort_formid",$data);
            $arr=resCode(400,"error");
            return $arr;
        }else{
            Db::name('xcx_formid')->insertAll($data);
            cache("sort_formid",null);
            $arr=resCode(200,"SUCCESS");
            return $arr;
        }
    }

    //从缓存中取
    public function cache_formid()
    {
        $data=cache("sort_formid");
        if(!empty($data)){
            Db::name('xcx_formid')->insertAll($data);
            cache("sort_formid",null);
        }
    }

    //查询缓存的长度
    public function cache_long(){
        $data=cache("sort_formid");
        return count($data);
    }
}