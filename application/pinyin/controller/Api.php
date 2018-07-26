<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/6/12
 * Time: 10:28
 */

namespace app\pinyin\controller;


use app\pinyin\model\Game;
use app\pinyin\model\User;
use common\controller\ApiLogin;
use think\Cache;
use think\Db;

class Api extends ApiLogin
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
            $data['user_name']=$userName;
            $data['user_img']=$userImg;
           if(Db::name("users")->where("openid",$openId)->update($data)){
               $arr=resCode(200,"ok",null);
               return $arr;
           }else{
               $arr=resCode(400,"error",null);
               return $arr;
           }
        }else{
            $arr=resCode(400,"无此人或已经更新头像",null);
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
                $user_id=db("users")->insertGetId($data);
            }
            $arr=array("code"=>200,"msg"=>"success","data"=>array("openId"=>$openid,"wx_session_key"=>$session_key,"user_id"=>$user_id,"status"=>1));
            return $arr;
        }
        else{
            return $login_data;

        }
    }
    //首页数据
    public function index()
    {
        $res=Cache::init();
        $redis=$res->handler();
        $openId=input("post.openId");
        $chance=$redis->zscore("AllChancePinYin",$openId);
        // print_r($chance);die;
        if(!is_numeric($chance)){
            $chance=Db::name("users")->where("openid",$openId)->value("chance_num");
        }
        $res=Db::name("users")->where("openid",$openId)->find();
        $count=Db::name("answer")->count();
        $res['count']=$count;
        $res['chance_num']=$chance;
        $arr=resCode(200,"ok",$res);
        return $arr;

    }

    //获取题目
    public function get_question()
    {
        $openId=input("post.openId");
        $layer=input("post.layer",1);
        $game=new Game();
        $res=$game->get_question($openId,$layer);
        if($res){
            $arr=resCode(200,"查询成功",$res);
        }else{
            $arr=resCode(400,"查询失败",null);
        }
        return  $arr;
    }
    //更新是否通关
    public function is_over()
    {
        $openId=input("post.openId");
        $status=input("post.status");//0没有通关1通关了
        Db::name("users")->where("openid",$openId)->setField("is_over",$status);
        $status=Db::name("users")->where("openid",$openId)->value("is_over");
        $arr=resCode(200,"ok",$status);
        return $arr;
    }
    //开始挑战
    public function begin()
    {
        $res=Cache::init();
        $redis=$res->handler();
        $openId=input("post.openId");
        $chance_num=$redis->zscore("AllChancePinYin",$openId);
        if(!is_numeric($chance_num)){
            $chance_num= Db::name("users")->where("openid",$openId)->value("chance_num");
            $redis->zadd('AllChancePinYin', $chance_num, $openId);
            $redis->zincrby('AllChancePinYin', -1, $openId);
        }else{
            $chance_num=$redis->zscore("AllChancePinYin",$openId);
            if($chance_num>0){
                $redis->zincrby('AllChancePinYin', -1, $openId);
            }else{
                $arr=resCode(400,"机会不足",null);
                return $arr;
            }

        }
        $arr=resCode(200,"开始成功",null);
        return $arr;
    }
    //检查机会
    public function check()
    {
        $openId=input("post.openId");
        $status=Db::name("users")->where("openid",$openId)->value("status");
        $arr=resCode(200,"检查状态",$status);
        return $arr;
    }
    //改变状态
    public function update_status()
    {
        $openId=input("post.openId");
        $status=input("status");
        Db::name("users")->where("openid",$openId)->setField("status",$status);
        $arr=resCode(200,"检查状态",$status);
        return $arr;
    }
    //定时更新数据库
    public function put_cache(){
        $res=Cache::init();
        $redis=$res->handler();
        $chance_num=$redis->zrange('AllChancePinYin', 0, -1,true);

        foreach($chance_num as $k=>$v){
            Db::name("users")->where("openid",$k)->setField("chance_num",$v);
        }
        $redis->ZREMRANGEBYRANK ('AllChancePinYin',0,100000);
    }
    //判断是否有挑战机会
    public function check_chance()
    {
        $res=Cache::init();
        $redis=$res->handler();
        $openId=input("post.openId");
        $chance=$redis->zscore("AllChancePinYin",$openId);
        // print_r($chance);die;
        if(!is_numeric($chance)){
            $chance=Db::name("users")->where("openid",$openId)->value("chance_num");
        }
        if(empty($chance)||$chance<=0){
            $arr=resCode(400,"没机会了",null);
            return $arr;
        }else{
            $arr=resCode(200,"有机会",$chance);
            return $arr;
        }
    }
//保存关卡
    public function put_layer()
    {
        $openId=input("post.openId");
        $layer=input("post.layer");
        $time=time();
        $s=Db::name("users")->where("openid",$openId)->update(["layer"=>$layer,'update_layer'=>$time]);
        if($s){
            $arr=resCode(200,"保存成功",null);
            return $arr;
        }else{
            $arr=resCode(400,"保存失败",null);
            return $arr;
        }
    }
    //排行榜 依照获取的现金数
    public function cash_top()
    {
        $openId=input("post.openId");
        $res=Db::name("users")->field('user_name,user_img,total_cash')->whereNotNull("user_name")->order('total_cash desc')->limit(100)->select();
        foreach($res as $k=>$v) {
            $res[$k]['ranking'] = $k + 1;
        }
        $my=$this->my_rank(1,$openId);
        $arr=resCode(200,"查询成功",array("data"=>$res,"my"=>$my));
        return $arr;
    }
    //毅力邦 依照获取的现金数
    public function changellge_top()
    {
        $openId=input("post.openId");
        $res=Db::name("users")->field('user_name,user_img,layer')->whereNotNull("user_name")->order('layer desc')->limit(100)->select();
        foreach($res as $k=>$v) {
            $res[$k]['ranking'] = $k + 1;
        }
        $my=$this->my_rank(2,$openId);
        $arr=resCode(200,"查询成功",array("data"=>$res,"my"=>$my));
        return $arr;
    }

    //缓存我的排名
    public function my_rank($type=1,$openId="")
    {
        $my="";
        if($type==1) {
            $info=Db::name("users")->field('openid,user_name,user_img,total_cash')->whereNotNull("user_name")->order('total_cash desc')->select();
        }
        else {
            $info=Db::name("users")->field('openid,user_name,user_img,layer')->whereNotNull("user_name")->order('layer desc')->select();
        }
        $s=array_search($openId,$info);
        echo $s;
        foreach($info as $k=>$v) {
            $info[$k]['ranking'] = $k + 1;
            if ($v['openid'] == $openId) {
                $my = $info[$k];
            }
        }
        if(empty($my)){
            $my=db("users")->where("openid",$openId)->find();
        }
        return $my;
    }

    //个人中心用户信息
    public function user_info()
    {
        $res=Cache::init();
        $redis=$res->handler();
        $openId=input("post.openId");
        $chance=$redis->zscore("AllChancePinYin",$openId);
        // print_r($chance);die;
        if(!is_numeric($chance)){
            $chance=Db::name("users")->where("openid",$openId)->value("chance_num");
        }
        $res=Db::name('users')->field('user_name,user_img,chance_num,total_cash,cash,pass_num')->where('openid',$openId)->find();
        $res['chance_num']=$chance;
        $arr=resCode(200,"查询成功",$res);
        return $arr;
    }
    //领奖记录
    public function record()
    {
        $openId=input("post.openId");
        $res=Db::name('prize')->where('openid',$openId)->select();
        $arr=resCode(200,"查询成功",$res);
        return $arr;
    }

    //领取奖励
    public function push_cash()
    {
        $openId=input("post.openId");
        $cash=input("post.cash");
        $time=date('Y-m-d H:i:s');
        $data['openid']=$openId;
        $data['cash']=$cash;
        $data['login_time']=$time;
        $res=Db::name("users")->where('openid',$openId)->find();
        if(Db::name("prize")->insert($data)){
            Db::name('users')->where('openid',$openId)->setField(['total_cash'=>$res['total_cash']+$cash,"cash"=>$res['cash']+$cash]);
            $arr=resCode(200,"更新成功",null);
            return $arr;
        }else{
            $arr=resCode(400,"更新失败",null);
            return $arr;
        }
    }
    //提现接口
    public function  put_forward(){
        $openId=input("post.openId");
        $cash=input("post.cash");
        $time=date('Y-m-d H:i:s');
        $data['openid']=$openId;
        $data['cash']=$cash;
        $data['time']=$time;
        $data['cash_number']=time();
        $res=Db::name("users")->where('openid',$openId)->find();
        if($res['cash']<$cash||$cash<5){
            $arr=resCode(400,"要满5元才能提现哦，继续游戏赚钱",null);
            return $arr;
        }
        if(Db::name("aply")->insert($data)){
            Db::name('users')->where('openid',$openId)->setField(["cash"=>$res['cash']-$cash]);
            $arr=resCode(200,"更新成功",$data['cash_number']);
            return $arr;
        }else{
            $arr=resCode(400,"更新失败",null);
            return $arr;
        }
    }
    //提现记录
    public function forward()
    {
        $openId=input("post.openId");
        $res=Db::name('aply')->where('openid',$openId)->order("time desc")->select();
        $arr=resCode(200,"查询成功",$res);
        return $arr;
    }
    //分享群
    public function share_group(){
        $openId=input("post.openId");
        $encryptedData = input("post.encryptedData");
        $iv = input("post.iv");
        $session_key=input("session_key");
       // echo '$openId:'.$openId.'$encryptedData='.$encryptedData.'$iv='.$iv.'$session_key='.$session_key;die;
        if(!$encryptedData||!$iv||!$session_key){
            $arr=resCode(400,"参数为空",null);
            return  $arr;
        }
        $res=Cache::init();
        $redis=$res->handler();
        $chance_num=$redis->zscore("AllChancePinYin",$openId);
        vendor("wxaes.wxBizDataCrypt");
        $wxBizDataCrypt = new \WXBizDataCrypt(config("WECHAT_APPID"), $session_key);
        $data_arr = array();
        $errCode = $wxBizDataCrypt->decryptData($encryptedData, $iv, $data_arr);
        //print_r($errCode);die;
        if($errCode==0) {
            $json_data = json_decode($data_arr, true);
            $openGid=$json_data['openGId'];
            $res=Db::name("share_group")->where("openGid",$openGid)->where("openid",$openId)->find();
            if(!empty($res)&&$res['share_time']<strtotime(date("Y-m-d"))){
                Db::name("share_group")->where("openGid",$openGid)->where("openid",$openId)->setField("share_time",time());
                if(!is_numeric($chance_num)){
                    Db::name("users")->where("openid",$openId)->setInc("chance_num");
                }else{
                    $redis->zincrby('AllChancePinYin', 1, $openId);
                }
                $arr=resCode(200,"ok",null);
                return $arr;
            }else if(empty($res)){
                    $data['openid']=$openId;
                    $data['share_time']=time();
                    $data['openGid']=$openGid;
                    Db::name("share_group")->insert($data);
                if(!is_numeric($chance_num)){
                    Db::name("users")->where("openid",$openId)->setInc("chance_num");
                }else{
                    $redis->zincrby('AllChancePinYin', 1, $openId);
                }
                $arr=resCode(200,"ok",null);
                return $arr;
            }else{
                $arr=resCode(400,"已经分享过",null);
                return $arr;
            }
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
        $data=cache("pinyin_formid");
        if(empty($data)){
            $data[]=$arr;
            cache("pinyin_formid",$data);
        }else if(count($data)<5000){
            array_push($data,$arr);
            cache("pinyin_formid",$data);
        }else{
            Db::name('xcx_formid')->insertAll($data);
            cache("pinyin_formid",null);
        }
    }

    public function cache_formid()
    {
        $data=cache("pinyin_formid");
        if(!empty($data)){
            Db::name('xcx_formid')->insertAll($data);
            cache("pinyin_formid",null);
        }
    }
}