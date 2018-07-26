<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/6/12
 * Time: 10:28
 */

namespace app\gstar\controller;
use app\gstar\model\Game;
use app\gstar\model\User;
use common\controller\ApiLogin;
use think\Db;
use think\Request;

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
        if($user){
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
            $arr=resCode(400,"查无此人",null);
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
                $data['medal']=Db::name("medal")->where("type",1)->value("name");;
                $data['login_time']=date("Y-m-d H:i:s");
                $user_id=db("users")->insertGetId($data);
            }
            $arr=array("code"=>200,"msg"=>"success","data"=>array("openId"=>$openid,"wx_session_key"=>$session_key,"user_id"=>$user_id,"status"=>1));
            return $arr;
        }
        else{
            return $login_data;

        }
    }

    //获取题目
    public function get_question()
    {
        $openId=input("post.openId");
        $layer=input("post.layer");
        $rank=input("post.rank");
        $game=new Game();
        $res=$game->get_question($openId,$layer,$rank);
        if($res){
            $arr=resCode(200,"查询成功",$res);
        }else{
            $arr=resCode(400,"查询失败",null);
        }
        return  $arr;
    }
    //检查签到
    public function check_sign()
    {
        $openId=input("openId");
        $status=Db::name("users")->where("openid",$openId)->field("sign,sign_time")->find();
        $int=ceil((strtotime(date("Y-m-d"))-strtotime($status['sign_time']))/86400);
        if($int!=1&&$int!=0){
            Db::name("users")->where("openid",$openId)->update(['sign_day'=>0]);
        }
        $arr=resCode(200,"ok",$status['sign']);
        return $arr;
    }
    //执行签到
    public function sign()
    {
        $openId=input("openId");
        $status=1;
        $day=Db::name("users")->where("openid",$openId)->value("sign_day");
        $global_num=Db::name("users")->where("openid",$openId)->value("gloal_num");
        if($day===0){
            $day=1;
            $global=10000;
        }else if($day==1){
            $day=2;
            $global=2000;
        }else if($day==2){
            $day=3;
            $global=3000;
        }else if($day==3){
            $day=4;
            $global=4000;
        }else if($day==4){
            $day=5;
            $global=5000;
        }else if($day==5){
            $day=6;
            $global=6000;
        }else if($day==6){
            $day=7;
            $global=8000;
        }else{
            $day=1;
            $global=1000;
        }
        $res= Db::name("users")->where("openid",$openId)->update(["sign"=>$status,"sign_day"=>$day,"gloal_num"=>$global_num+$global,"sign_time"=>date("Y-m-d")]);
        if($res){
            $arr=resCode(200,"ok",array("golbal"=>$global,"day"=>$day));
        }else{
            $arr=resCode(400,"error",null);
        }
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
                   Db::name("users")->where("openid",$openId)->setInc("gloal_num",2000);
                   $arr=resCode(200,"ok",null);
                   return $arr;
               }else if(empty($res)){
                   $data['openid']=$openId;
                   $data['share_time']=time();
                   $data['openGid']=$openGid;
                   Db::name("share_group")->insert($data);
                   Db::name("users")->where("openid",$openId)->setInc("gloal_num",2000);
                   $arr=resCode(200,"ok",null);
                   return $arr;
               }else{
                   $arr=resCode(400,"已经分享过",null);
                   return $arr;
               }
           }

    }
    //分享给个人
    public function share()
    {
            $openId=input("post.openId");
            Db::name("users")->where("openid",$openId)->setInc("gloal_num",200);
            $arr=resCode(200,"ok",null);
            return $arr;
    }
    //获取用户信息
    public function user_info()
    {
        $openId=input("post.openId");
        $res=Db::name("users")->where("openid",$openId)->find();
        $res['layer']=intval($res['layer']);
        $arr=resCode(200,"查询成功",$res);
        return $arr;

    }
    //验证答案
    public function check_answer()
    {
        $openId=input("post.openId");
        $answer=input("post.answer");
        $id=input("post.id");
        $res=Db::name("answer")->where("id",$id)->where("answer",$answer)->find();
        if(empty($res)){
            $value=Db::name("users")->where("openid",$openId)->value("gloal_num");
            if($value<1000){
                Db::name("users")->where("openid",$openId)->setField("gloal_num",0);
                $arr=resCode(400,"金币不足",null);
                return $arr;
            }
            Db::name("users")->where("openid",$openId)->setDec("gloal_num",1000);
            $arr=resCode(200,"ok",1000);
        }else{
            Db::name("users")->where("openid",$openId)->setInc("gloal_num",200);
            $arr=resCode(200,"ok",200);
        }
        return $arr;
    }
    //更新称号
    public function update_medal()
    {
        $openId=input("post.openId");
       $type=input("post.type");
        $name=Db::name("medal")->where("type",$type)->value("name");
        Db::name("users")->where("openid",$openId)->setField("medal",$name);
        Db::name("users")->where("openid",$openId)->setInc("gloal_num",300);
        $arr=resCode(200,"ok",null);
        return $arr;
    }
    //更新关卡
    public function update_layer()
    {
        $openId=input("post.openId");
        $layer=input("layer");
        Db::name("users")->where("openid",$openId)->setField("layer",$layer);
        $arr=resCode(200,"ok",null);
        return $arr;
        
    }
    //获取所有的称号
    public function get_medal()
    {
        $res=Db::name("medal")->select();
        $count=Db::name("answer")->count();
        $arr=resCode(200,"ok",array('res'=>$res,'count'=>$count));
        return $arr;
    }
    //全球排行
    public function global_rank()
    {

            $res=Db::name("users")->whereNotNull("user_name")->order("layer desc")->limit("10")->select();

        $arr=resCode(200,"ok",$res);
        return $arr;
    }
    //土豪排行
    public function cash_rank()
    {

            $res=Db::name("users")->whereNotNull("user_name")->order("gloal_num desc")->limit("10")->select();
        $arr=resCode(200,"ok",$res);
        return $arr;
    }
    //缓存排行
    public function cache_rank()
    {
        $res=Db::name("users")->order("layer desc")->limit("10")->select();
        $global=Db::name("users")->order("gloal_num desc")->limit("10")->select();
        cache("gstar_glabal_rank",$res,3600);
        cache("cash_rank",$global,3600);
    }

    //我的排行 1世界排行2土豪排行
    public function my_rank()
    {
        $openId=input("post.openId");
        $type=input("type",1);
        if($type==1){
            $res=Db::name("users")->order("layer desc")->select();
        }else{
            $res=Db::name("users")->order("gloal_num desc")->select();
        }
        foreach($res as $k=>$v) {
            $info[$k]['ranking'] = $k + 1;
            if ($v['openid'] == $openId) {
                $my = $info[$k];
            }
        }
        return $my;
    }
    //添加好友
    public function add_friend(){
        $to_openid=input("to_openid");
        if($to_openid){
            $from_openid=input("post.from_openid",0);
            if($from_openid){
                $this->friend_add($to_openid,$from_openid);
                $arr=resCode(200,"ok",null);
            }else{
                $arr=resCode(400,"error",null);
            }
        }else{
            $arr=resCode(400,"error",null);
        }
        return $arr;
    }

    public function friend_add($to_openid,$from_openid){
        if($to_openid&&$from_openid){
            $has=Db::name('user_friend')->where(['to_openid'=>$to_openid,"from_openid"=>$from_openid])->find();
            if(!$has){
                $recommend_arr['to_openid']=$to_openid;
                $recommend_arr['from_openid']=$from_openid;
                Db::name('user_friend')->data($recommend_arr)->insert();
            }
            $has2=Db::name('user_friend')->where(['to_openid'=>$from_openid,"from_openid"=>$to_openid])->find();
            if(!$has2){
                $recommend_arr['to_openid']=$from_openid;
                $recommend_arr['from_openid']=$to_openid;
                Db::name('user_friend')->data($recommend_arr)->insert();
            }
        }
    }
    //好友排行
    public function friend_rand()
    {
        $openId=input("openId");
        $page=input('page',1);
        $pageSize=input("pageSize",10);
        $res=Db::name("user_friend")->where("to_openid",$openId)->select();
        $res2=Db::name("user_friend")->where("from_openid",$openId)->select();
        $where_arr=array($openId);
        foreach($res as $k=>$v){
            $where_arr[]=$v['from_openid'];
        }
        foreach($res2 as $k=>$v){
            $where_arr[]=$v['to_openid'];
        }
       // print_r($where_arr);die;
        $result=Db::name('users')->whereIn("openid",$where_arr)->order('layer desc')->page($page,$pageSize)->select();
        $arr=resCode(200,"ok",$result);
        return $arr;
    }
    /**
     * 小程序formid写入
     * @param form_id 小程序formid
     * @param open_id 微信openid
     */
    //不知道为什么没有同部
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
        $data=cache("gstar_formid");
        if(empty($data)){
            $data[]=$arr;
            cache("gstar_formid",$data);
        }else if(count($data)<5000){
            array_push($data,$arr);
            cache("gstar_formid",$data);
        }else{
            Db::name('xcx_formid')->insertAll($data);
            cache("gstar_formid",null);
        }
    }

    public function cache_formid()
    {
        $data=cache("gstar_formid");
        if(!empty($data)){
            Db::name('xcx_formid')->insertAll($data);
            cache("gstar_formid",null);
        }
    }
}