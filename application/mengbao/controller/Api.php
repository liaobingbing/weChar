<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/7/9
 * Time: 16:49
 */

namespace app\mengbao\controller;

use app\mengbao\model\User;
use common\controller\ApiLogin;
use think\Db;
class Api extends ApiLogin
{
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
                $arr=resCode(400,"已经更新头像",null);
                return $arr;
            }
        }else{
            $arr=resCode(400,"无此人",null);
            return $arr;
        }
    }
    //获取小程序的openid
    public function get_openid()
    {
        $code = input('post.code');
        $login_data = $this->test_weixin($code);
       // print_r($login_data);die;
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
        $openId=input("openId");
        $count=Db::name("photo")->count("openid");
        $count_ticket=Db::name("photo")->sum("ticket_num");
        $id=Db::name("photo")->where("openid",$openId)->value("id");
        $seasonInfo=Db::name("season")->order("season desc")->limit(1)->find();
        $seasonInfo['picture']=config("IMG_URL").$seasonInfo["picture"];
        $time=$seasonInfo['end_time']-time();
        $prize=Db::name("prize")->select();
        foreach($prize as $k=>$v){
            $prize[$k]['image']=config("IMG_URL").$v["image"];
        }
        $arr=resCode(200,"ok",array("people"=>$count,"ticket"=>$count_ticket,"season"=>$seasonInfo,"time"=>$time,"prize"=>$prize,"status"=>1,"my_id"=>$id));
        return $arr;
    }
    //检查是否领取爱心
    public function ck_sign()
    {
        $openId=input("openId");
        $status=Db::name("users")->where("openid",$openId)->value("status");
        $arr=resCode(200,"ok",$status);
        return $arr;
    }
    //领取爱心
    public function put_star()
    {
        $openId=input("openId");
        Db::name("users")->where("openid",$openId)->setField("status",1);
        $arr=resCode(200,"ok",null);
        return $arr;
    }
    //查看所有奖品
    public function prize()
    {
        $prize=Db::name("prize")->select();
        foreach($prize as $k=>$v){
            $prize[$k]['image']=config("IMG_URL").$v["image"];
        }
        $arr=resCode(200,"ok",$prize);
        return $arr;
    }
    //所有萌宝
    public function all_photo()
    {
        // $ids=array();
        $page=input("page",1);//页面
        $pageSize=16;
        $openId=input("post.openId");
        $res=Db::name("photo")->order("up_time desc")->where("status",1)->page($page,$pageSize)->select();
        foreach ($res as $k=>$v){
            $status=Db::name("ticket")->where("openid",$openId)->where("bady_num",$v['id'])->value("status");
            if(empty($status)){
                $status=0;
            }
            $res[$k]['status']=$status;
        }
        $arr=resCode(200,"ok",$res);
        return $arr;
    }
    //搜索萌宝1
    public function search()
    {
        $openId=input("post.openId");
        $name=input("post.name");
        if(empty($name)){
            $arr=resCode(400,"参数为空",null);
            return $arr;
        }
        $res=Db::name("photo")->where("name","like","%$name%")->whereOr("id",$name)->select();
        foreach ($res as $k=>$v){
            $status=Db::name("ticket")->where("openid",$openId)->where("bady_num",$v['id'])->value("status");
            if(empty($status)){
                $status=0;
            }
            $res[$k]['status']=$status;
        }
        $arr=resCode(200,"ok",$res);
        return $arr;
    }
    //萌宝详情
    public function detail_mengbao()
    {
        $openId=input("post.openId");
        $id=input("post.id");//萌宝id
        if(!$openId||!$id){
            $arr=resCode(400,"error",null);
            return  $arr;
        }

        $res=Db::name("photo")->where('id',$id)->find();
        if($res['openid']==$openId){
            $is_my=1;
        }else{
            $is_my=0;
        }
        $photo=Db::name("users")->where("openid",$res['openid'])->find();
        $info=Db::name("ticket")->where("bady_num",$id)->select();
        $status=Db::name("ticket")->where(array("bady_num"=>$id,"openid"=>$openId))->value("status");
        if(empty($status)){
            $status=0;
        }
        $count=count($info);
       /* $sql="SELECT count(*) as rank  from (SELECT * FROM `mengbao_photo` ORDER BY ticket_num DESC) as a where ticket_num >= (SELECT ticket_num from `mengbao_photo` where id ="."$id)";
        $rank=db()->query($sql);*/
       if(!empty($res)){
           $rank=$this->my_rank($res['openid']);
       }else{
           $rank=0;
       }
        $res=resCode(200,"ok",array("status"=>$status,"photoInfo"=>$res,"ticket"=>$info,"count_ticket"=>$count,"rank"=>$rank,"my"=>$photo,"is_my"=>$is_my));//
        return $res;
   }

   //发布萌宝 更新萌宝
    public function push_mengbao()
    {
        $openId=input("openId");
        $name=input("post.name");
        $phone=input("post.phoneNumber");
        $content=input("post.content");
        $image=input("post.img");
        $season=input("post.season");
        $key=input("post.key");
        $time=time();
        $status=1;
        if(!$openId||!$name||!$phone||!$content||!$image||!$season){
            $arr=resCode(400,"error",null);
            return  $arr;
        }

        $res=Db::name("photo")->where(array("openid"=>$openId,"season"=>$season))->find();
        if($res){
            $id=$res['id'];
            Db::name("photo")->where(array("openid"=>$openId,"season"=>$season))->update(["name"=>$name,"phone_number"=>$phone,"content"=>$content,"image"=>$image,"up_time"=>$time,"status"=>$status,"img_key"=>$key]);

        }else{
            $data["name"]=$name;
            $data["phone_number"]=$phone;
            $data["content"]=$content;
            $data["image"]=$image;
            $data["openid"]=$openId;
            $data["season"]=$season;
            $data['up_time']=$time;
            $data["status"]=$status;
            $data['img_key']=$key;
            $id=Db::name("photo")->insertGetId($data);
        }
        $res=resCode(200,"ok",$id);
        return $res;
    }
    //删除萌宝
    public function del_mengbao()
    {
        $id=input("post.id");
        $res=Db::name("photo")->where("id",$id)->delete();
        if($res){
            Db::name("ticket")->where("bady_num",$id)->delete();
        }
        $arr=resCode(200,"ok",null);
        return $arr;
    }
    //投票管理
    public function ticket_mengbao()
    {
        $openId=input("openId");
        $id=input("id");
        $userName=input("userName");
        if(mb_strlen($userName)>3){
            $userName=mb_substr($userName,0,3,'utf-8')."..";
        }
        $userImg=input("userImg");
        $season=input("season");
        $data['openid']=$openId;
        $data['user_img']=$userImg;
        $data['user_name']=$userName;
        $data['bady_num']=$id;
        $data['ticket_num']=1;
        $data['ticket_time']=date('Y-m-d');
        $data['season']=$season;
        $data['status']=1;
        $chance_num=Db::name("users")->where("openid",$openId)->value("chance_num");
        if($chance_num<=0){
            $arr=resCode(400,"今日没有投票机会啦",null);
            return $arr;
        }
        $res=Db::name("ticket")->where(array("openid"=>$openId,"bady_num"=>$id,"season"=>$season))->find();
        if(empty($res)){
            if(Db::name("photo")->where("id",$id)->setInc("ticket_num")) {
                Db::name("ticket")->insert($data);
                Db::name("users")->where("openid",$openId)->setDec("chance_num");
            }
        }else{
            if(Db::name("photo")->where("id",$id)->setInc("ticket_num")){
                Db::name("ticket")->where("id",$res['id'])->update(["status"=>1,"ticket_time"=>date('Y-m-d'),"ticket_num"=>$res['ticket_num']+1]);
                Db::name("users")->where("openid",$openId)->setDec("chance_num");
            }
        }
        $arr=resCode(200,"ok",null);
        return $arr;
    }
    //检查是否签到
    public function check_sign()
    {
        $openId=input("openId");
        $id=input("id");
        $status=Db::name("ticket")->where("openid",$openId)->where("bady_num",$id)->value("status");
        if(empty($status)){
            $status=0;
        }
        $arr=resCode(200,"ok",$status);
        return $arr;
    }
    //排行榜
    public function rank_ticket()
    {
        $openId=input("openId");
        $my="";
            $info=Db::name("photo")->order("ticket_num desc")->select();
            foreach($info as $k=>$v) {
                $info[$k]['ranking'] = $k + 1;
                if($openId==$v["openid"]){
                    $my=$info[$k];
                }
            }
        foreach($info as $k=>$v) {
            if($openId==$v["openid"]){
                $my=$info[$k];
            }
        }
        $arr=resCode(200,"ok",array("info"=>$info,"my"=>$my));
        return $arr;
    }
    //我的排名
    public function my_rank($openId)
    {
        $info = Db::name("photo")->order("ticket_num desc")->select();
        foreach($info as $k=>$v) {
            $info[$k]['ranking'] = $k + 1;
            if($openId==$v["openid"]){
                $my=$info[$k];
            }
        }
            return $my['ranking'];
    }
    //我发布的萌宝
    public function my_mengbao()
    {
        $openId=input("openId");
        $res=Db::name("photo")->where("openid",$openId)->find();
        $arr=resCode(200,"ok",$res);
        return $arr;

    }
    //分享
    public function share_group()
    {
        $id=input("id");
        $res=Db::name("users")->where("id",$id)->find();
        if($res){
            $status=1;//代表本人
        }else{
            $status=0;//代表非本人
        }
        $arr=resCode(200,"ok",$status);
        return $arr;
    }

    //添加formid
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
        $data=cache("mengbao_formid");
        if(empty($data)){
            $data[]=$arr;
            cache("mengbao_formid",$data);
        }else if(count($data)<5000){
            array_push($data,$arr);
            cache("mengbao_formid",$data);
        }else{
            Db::name('xcx_formid')->insertAll($data);
            cache("mengbao_formid",null);
        }
    }

    //从缓存中取
    public function cache_formid()
    {
        $data=cache("mengbao_formid");
        if(!empty($data)){
            Db::name('xcx_formid')->insertAll($data);
            cache("mengbao_formid",null);
        }
    }

}