<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/6/29
 * Time: 9:20
 */

namespace app\guessong\controller;

use common\controller\ApiLogin;
use think\Db;
use app\guessong\model\GsUsers;
use think\Controller;

class Member extends ApiLogin
{
    public function index(){
       /* $openId=input("openId");
        $user_id=$user->get_user_id($openId);*/
        $user_id=input("post.id");
        $user=new GsUsers();
        $user_data =$user->get_user_info($user_id);
        $user_data['layer']++;
        $result['data'] = $user_data;
        $result['code'] = 200;
        $result['msg']  = '获取成功';

        return $result;

    }

    /*
     * 用户登录验证
     * */
    public function login(){
        // 用户临时登录凭证
        $userInfo=input('post.userInfo');//获取前台传送的用户信息
        $userInfo=str_replace("&quot;","\"",$userInfo);
        $userInfo=json_decode($userInfo,true);
        $openid=input('post.openId');//获取opendId
        $result =array(
            'code'  =>   400,
            'msg'   =>   '参数错误'
        );
        if($openid&&$userInfo){

            $User=new GsUsers();

            // 查询数据库是否拥有该用户
            $user_id = Db::name('users')->where(array('openid'=>$openid))->value('id');

            if(empty($user_id)){
                $user_data = array();
                $user_data['openid'] = $openid;
                $user_data['name']   = $userInfo['nickName'];
                $user_data['gender'] = $userInfo['gender'];
                $user_data['city']   = $userInfo['city'];
                $user_data['country'] = $userInfo['country'];
                $user_data['province'] = $userInfo['province'];
                $user_data['avatarUrl'] = $userInfo['avatarUrl'];
                $user_data['created_at'] = time();
                $user_data['login_at'] = time();

                $user_id = Db::name('users')->insertGetId($user_data);
            }else{
                // 更新用户信息
                $update_data = array();
                $update_data['id']   = $user_id;
                $update_data['name']   = $userInfo['nickName'];
                $update_data['gender'] = $userInfo['gender'];
                $update_data['city']   = $userInfo['city'];
                $update_data['country'] = $userInfo['country'];
                $update_data['province'] = $userInfo['province'];
                $update_data['avatarUrl'] = $userInfo['avatarUrl'];
                $update_data['login_at'] = time();
                Db::name('users')->where("id",$user_id)->update($update_data);
            }
            $result['code'] =   200;
            $result['msg']  =   '登录成功';
            $date['data']=$user_id;

        }

        return $result;

    }

    function post_url($url,$parameter)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT,15);   //只需要设置一个秒的数量就可以
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // post数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, $parameter);
        $output = curl_exec($ch);
        curl_close($ch);
        $output = json_decode($output,true);
        return $output;
    }
    /**
     * 获取用户id
     */
    public function get_user_id(){
        //  $this->is_login();
       // $user_id = session('user_info.id');
        $openId=input("openId");
        $user=new GsUsers();
        $user_id=$user->get_user_id($openId);
        if(empty($user_id)){
            $result = array(
                'code'  => 401,
                'msg'   => '请先登录'
            );
        }else{
            $result = array(
                'code'    => 200,
                'msg'     => '获取成功',
                'user_id' => $user_id
            );
        }

       return $result;
    }

    /**
     * 签到处理
     */
    public function sign(){
        // $this->is_login();
        $openid=input("openId");
        $user=new GsUsers();
        $user_id=$user->get_user_id($openid);
        $key = $openid.'_sign_status';

        $resutl = array(
            'code' => 400,
            'msg'  => '签到失败'
        );
        $sign = Db::name("users")->where(array('id'=>$user_id))->field('sign_day,sign_time,fraction')->find();
        //  print_r("1234545".$sign);
        // 今天 0 点时间
        $today_0 = strtotime(date('Y-m-d',time()));
        //昨天 0点时间
        $yesterday_0 = $today_0 - 60*60*24;
        // 现在距 今天 24 点的时间
        $expire = $today_0 + 60*60*24 -time();

        if($sign['sign_time'] > $today_0 ) {

            $resutl['msg'] = '今天已签到';
            cache($key,1,$expire);

        }else if($sign['sign_time'] < $yesterday_0){
            $sign_day = 2;
            $fraction = $sign['fraction'] + 30;

            $data = array(
                'sign_day' => $sign_day,
                'sign_time' => time(),
                'fraction'  => $fraction
            );

            if(Db::name("users")->where('id',$user_id)->update($data)){

                cache($key,1,$expire);

                $resutl['msg']  = '签到成功';
                $resutl['code'] =200;
            }

        }else{

            switch($sign['sign_day']){
                case 1:
                    $sign_day = 2;
                    $fraction = $sign['fraction'] + 30;
                    break;
                case 2:
                    $sign_day = 3;
                    $fraction = $sign['fraction'] + 40;
                    break;
                case 3:
                    $sign_day = 4;
                    $fraction = $sign['fraction'] + 50;
                    break;
                case 4:
                    $sign_day = 5;
                    $fraction = $sign['fraction'] + 60;
                    break;
                case 5:
                    $sign_day = 6;
                    $fraction = $sign['fraction'] + 70;
                    break;
                case 6:
                    $sign_day = 7;
                    $fraction = $sign['fraction'] + 80;
                    break;
                case 7:
                    $sign_day = 1;
                    $fraction = $sign['fraction'] + 100;
                    break;
            }

            $data = array(
                'sign_day' => $sign_day,
                'sign_time' => time(),
                'fraction'  => $fraction
            );

            if(Db::name("users")->where('id',$user_id)->update($data)){

                cache($key,1,$expire);

                $resutl['msg']  = '签到成功';
                $resutl['code'] =200;
            }

        }

        return $resutl;

    }

    /*
     * 签到判断
     * */
    public function is_sign(){
        // $this->is_login();
        $openid=input("openId");
        $user=new GsUsers();
        $user_id=$user->get_user_id($openid);
        $key = $openid.'_sign_status';

        $result = array(
            'code' => 400,
            'msg'  => '获取失败'
        );

        $is_sign = cache($key);

        if(empty($is_sign)){

            $sign = Db::name('users')->where(array('id'=>$user_id))->field('sign_time,sign_day')->find();

            $result['code'] =   '200';
            $result['sign_status'] = 0;
            $result['msg']  = '未签到';

            $yesterday_0 = strtotime(date('Y-m-d',time())) - 60*60*24;
            if($sign['sign_time'] < $yesterday_0){
                $result['sign_day'] = 1;
            }else{
                $result['sign_day'] = $sign['sign_day'];
            }

        }else{
            $result['code'] =   '200';
            $result['sign_status'] = 1;
            $result['msg']  = '已签到';
        }

        return $result;
    }

    /*
     * 判断是否好友
     */
    public function is_friend(){
        //$this->is_login();
        //$user_id = session('user_info.id');
        $openid=input("openId");
        $user=new GsUsers();
        $user_id=$user->get_user_id($openid);
        $recommend_user_id = input('recommend_id');

        $result = array();


        if($recommend_user_id){
            if($recommend_user_id != $user_id){
                $friend = Db::name('friend')->where("(uid = $user_id And recommend_user_id = $recommend_user_id) OR (uid = $recommend_user_id And recommend_user_id = $user_id)")->find();
                if(empty($friend)){
                    $data = array(
                        'uid' => $user_id,
                        'recommend_user_id' => $recommend_user_id
                    );
                    Db::name('friend')->insert($data);
                }

                $result['code'] = 200;
                $result['msg']  = '添加好友成功';
            }

        }else{
            $result['code'] = 400;
            $result['msg']  = '没有recommend_id,无法添加';
        }


       return $result;
    }

    private $key='kuaiyu666666';

    //更新世界排行
    public function gs_world_ranking(){
        $key=input('get.key');
        if($key==$this->key){
            $world_arr=array();
            $ranking_arr=Db::name('users')->where("avatarUrl",'<>',"")->field('id,avatarUrl,name,fraction,layer')->order('layer desc')->limit(100)->fetchSql(false)->select();
            foreach($ranking_arr as $k=>$v){
                $ranking_arr[$k]['ranking']=$k+1;
            }
            cache('gs_world_rankings',$ranking_arr);

        }
    }


    //获取用户opendId
    public function get_openid()
    {
        $code = input('code');
        $login_data = $this->test_weixin($code);
        if (!empty($login_data['openid'])) {
            $openid = $login_data['openid'];
            $session_key=$login_data['session_key'];
            $user_id = Db::name("users")->where(array('openid'=>$openid))->value('id');
            if(!$user_id){
                $data['openid']= $openid;
                $data['login_at']=time();
                $user_id = Db::name("users")->insertGetId($data);
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
        if (db('xcx_formid')->insert(array(
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