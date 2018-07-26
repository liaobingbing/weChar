<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/6/12
 * Time: 10:28
 */

namespace app\gmethod\controller;
use app\gmethod\model\Game;
use app\gmethod\model\User;
use common\controller\ApiLogin;
use think\Db;
use think\Cache;

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
                $data["medal"]=Db::name("type")->where("type",1)->value("name");
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
    //银行领奖
    public function bank()
    {
        $res=Cache::init();
        $redis=$res->handler();
        $openId=input("post.openId");
        $gloal_num=$redis->zscore("AllopenIdGmethod",$openId);
        if(!is_numeric($gloal_num)){
            $gloal_num=Db::name("users")->where("openid",$openId)->value("gloal_num");
            $redis->zadd('AllopenIdGmethod',$gloal_num, $openId);
            $redis->zincrby('AllopenIdGmethod', 2000, $openId);
            $gloal_num=$redis->zscore("AllopenIdGmethod",$openId);
        }else{
            $redis->zincrby('AllopenIdGmethod', 2000, $openId);
        }
       // Db::name("users")->where("openid",$openId)->setInc("gloal_num",2000);
        //$golbal=Db::name("users")->where("openid",$openId)->value("gloal_num");
        $arr=resCode(200,"ok",$gloal_num);
        return $arr;
    }
    //

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

    //分享群
    public function share_group(){
        $openId=input("post.openId");
        $res=Cache::init();
        $redis=$res->handler();
        $gloal_num=$redis->zscore("AllopenIdGmethod",$openId);
        if(!is_numeric($gloal_num)){
            $gloal_num=Db::name("users")->where("openid",$openId)->value("gloal_num");
            $redis->zadd('AllopenIdGmethod',$gloal_num, $openId);
            $gloal_num=$redis->zscore("AllopenIdGmethod",$openId);
        }
        $type=input("post.type");//1个人2群
        if($type==1){
            $res=Db::name("users")->where("openid",$openId)->find();
            if($res['share_time']<=2){
                Db::name("users")->where("openid",$openId)->update(['share_time'=>$res['share_time']+1,'gloal_num'=>$gloal_num+5000]);
                $redis->zincrby('AllopenIdGmethod', 5000, $openId);
                $arr=resCode(200,"ok",null);
            }else{
                $arr=resCode(400,"个人分享已达上限",null);
            }
            return $arr;
        }else{
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
                   // Db::name("users")->where("openid",$openId)->setInc("gloal_num",30000);
                    $redis->zincrby('AllopenIdGmethod', 30000, $openId);
                    $arr=resCode(200,"ok",null);
                    return $arr;
                }else if(empty($res)){
                    $data['openid']=$openId;
                    $data['share_time']=time();
                    $data['openGid']=$openGid;
                    Db::name("share_group")->insert($data);
                    //Db::name("users")->where("openid",$openId)->setInc("gloal_num",30000);
                    $redis->zincrby('AllopenIdGmethod', 30000, $openId);
                    $arr=resCode(200,"ok",null);
                    return $arr;
                }else{
                    $arr=resCode(400,"已经分享过",null);
                    return $arr;
                }
            }else{
                $arr=resCode(400,"解密参数报错",$errCode);
                return $arr;
            }
        }

    }
    //获取用户信息
    public function user_info()
    {
        $openId=input("post.openId");
        $res=Cache::init();
        $redis=$res->handler();
        $gloal_num=$redis->zscore("AllopenIdGmethod",$openId);
        // print_r($chance);die;
        if(!is_numeric($gloal_num)){
            $gloal_num=Db::name("users")->where("openid",$openId)->value("gloal_num");
        }
        $res=Db::name("users")->where("openid",$openId)->find();
        $name=Db::name("type")->where("type",$res['rank'])->value("name");
        $res['gloal_num']=$gloal_num;
        $res['medal']=$name;
        $res['cut_gold']=10000;
        $res['next_name']=Db::name("type")->where("type",$res['rank']+1)->value("name");
        $arr=resCode(200,"查询成功",$res);
        return $arr;

    }
    //答案正确 或者晋级
    public function answer_true()
    {
        $res=Cache::init();
        $redis=$res->handler();
        $openId=input("post.openId");
        //Db::name("users")->where("openid",$openId)->setInc("gloal_num",1000);
        $redis->zincrby('AllopenIdGmethod', 1000, $openId);
        $arr=resCode(200,"ok",1000);
        return $arr;
    }
    //开始挑战
    public function begain()
    {
        $res=Cache::init();
        $redis=$res->handler();
        $openId=input("post.openId");
        $gloal_num=$redis->zscore("AllopenIdGmethod",$openId);
        if(!is_numeric($gloal_num)){
            $gloal_num=Db::name("users")->where("openid",$openId)->value("gloal_num");
            $redis->zadd('AllopenIdGmethod',$gloal_num, $openId);
            $gloal_num=$redis->zscore("AllopenIdGmethod",$openId);
        }
        if($gloal_num>=10000){
            //Db::name("users")->where("openid",$openId)->setDec("gloal_num",10000);
            $redis->zincrby('AllopenIdGmethod', -10000, $openId);
            $arr=resCode(200,"ok",null);
        }else{
            $arr=resCode(400,"金币不足",null);
        }
        return $arr;
    }
    //定时更新数据库
    public function put_cache()
    {
        $res = Cache::init();
        $redis = $res->handler();
        $gloal_num = $redis->zrange('AllopenIdGmethod', 0, -1, true);
        foreach($gloal_num as $k=>$v){
            Db::name("users")->where("openid",$k)->setField("gloal_num",$v);
        }
        $redis->ZREMRANGEBYRANK ('AllopenIdGmethod',0,100000);

    }
    //更新称号和等级
    public function update_medal()
    {
        $openId=input("post.openId");
        $type=input("post.type");
        $name=Db::name("type")->where("type",$type)->value("name");
        Db::name("users")->where("openid",$openId)->update(["medal"=>$name,"rank"=>$type]);
        $arr=resCode(200,"ok",null);
        return $arr;
    }

    //获取所有的称号
    public function get_medal()
    {
        $res=Db::name("type")->select();
        $arr=resCode(200,"ok",$res);
        return $arr;
    }
    //世界排行
    public function cash_rank()
    {

        $page=input("page",1);
        $pageSize=input("pageSize",10);
         $res=Db::name("users")->order("gloal_num desc")->page($page,$pageSize)->select();
        $arr=resCode(200,"ok",$res);
        return $arr;
    }
    //缓存排行
    public function cache_rank()
    {
        $global=Db::name("users")->order("gloal_num desc")->limit("100")->select();
        cache("cash_rank",$global,3600);
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
        $res=Db::name("user_friend")->where("to_openid",$openId)->select();
        $res2=Db::name("user_friend")->where("from_openid",$openId)->select();
        $where_arr=array($openId);
        foreach($res as $k=>$v){
            $where_arr[]=$v['from_openid'];
        }
        foreach($res2 as $k=>$v){
            $where_arr[]=$v['to_openid'];
        }
        $result=Db::name('users')->whereIn("openid",$where_arr)->order('gloal_num desc')->limit(20)->select();
        foreach($result as $k=>$v){
            $result[$k]['ranking']=$k+1;
            $result[$k]=$v;
        }
        $arr=resCode(200,"ok",$result);
        return $arr;
    }
    //检查签到
    public function check_sign()
    {
        $openId=input("openId");
        $status=Db::name("users")->where("openid",$openId)->value("sign");
        $arr=resCode(200,"ok",$status);
        return $arr;
    }
    //执行签到
    public function sign()
    {
        $res = Cache::init();
        $redis = $res->handler();
        $openId=input("openId");
        $status=1;
        $day=Db::name("users")->where("openid",$openId)->value("sign_day");
        $gloal_num=$redis->zscore("AllopenIdGmethod",$openId);
        if(!is_numeric($gloal_num)){
            $gloal_num=Db::name("users")->where("openid",$openId)->value("gloal_num");
            $redis->zadd('AllopenIdGmethod',$gloal_num, $openId);
            $gloal_num=$redis->zscore("AllopenIdGmethod",$openId);
        }
        if($day===0){
            $day=1;
            $global=10000;
        }else if($day==1){
            $day=2;
            $global=11000;
        }else if($day==2){
            $day=3;
            $global=12000;
        }else if($day==3){
            $day=4;
            $global=13000;
        }else if($day==4){
            $day=5;
            $global=14000;
        }else if($day==5){
            $day=6;
            $global=15000;
        }else if($day==6){
            $day=7;
            $global=16000;
        }else{
            $day=1;
            $global=10000;
        }
       $res= Db::name("users")->where("openid",$openId)->update(["sign"=>$status,"sign_day"=>$day,"gloal_num"=>$gloal_num+$global]);
        if($res){
            $arr=resCode(200,"ok",array("golbal"=>$global,"day"=>$day));
        }else{
            $arr=resCode(400,"error",null);
        }
        return $arr;
    }
    //抽奖首页数据
    public function index()
    {
        $res = Cache::init();
        $redis = $res->handler();
        $openId=input("openId");
        $gloal_num=$redis->zscore("AllopenIdGmethod",$openId);
        if(!is_numeric($gloal_num)){
            $gloal_num=Db::name("users")->where("openid",$openId)->value("gloal_num");
            $redis->zadd('AllopenIdGmethod',$gloal_num, $openId);
            $gloal_num=$redis->zscore("AllopenIdGmethod",$openId);
        }
        $data['global_num']=$gloal_num;
        $data["people"]=rand(64421,69999);
        $data['free']=0;
        $arr=resCode(200,"ok",$data);
        return $arr;
    }
    //抽奖减金币
    public function desc_global()
    {
        $res = Cache::init();
        $redis = $res->handler();
        $openId=input("openId");
        $gloal_num=$redis->zscore("AllopenIdGmethod",$openId);
        if(!is_numeric($gloal_num)){
            $gloal_num=Db::name("users")->where("openid",$openId)->value("gloal_num");
            $redis->zadd('AllopenIdGmethod',$gloal_num, $openId);
            $gloal_num=$redis->zscore("AllopenIdGmethod",$openId);
        }
        if($gloal_num<20000){
            $arr=resCode(400,"金币不足",$gloal_num);
        }else{
            $redis->zincrby('AllopenIdGmethod', -20000, $openId);
            $gloal_num=$redis->zscore("AllopenIdGmethod",$openId);
            //Db::name("users")->where("openid",$openId)->setDec("gloal_num",20000);
            //$global=Db::name("users")->where("openid",$openId)->value("gloal_num");
            $arr=resCode(200,"ok",$gloal_num);
        }
       return $arr;
    }
    //随机抽奖
    public function rank_golbal()
    {
        $res = Cache::init();
        $redis = $res->handler();
        $openId=input("post.openId");
        $game["openid"]=$openId;
        $game['time']=time();
        $game['receve_time']=date("Y-m-d H:i:s");
        $rand=rand(1,500);
        if($rand<=120){
            $data['golbal']=0;
            $data['level']=0;
        }else if($rand<=280){
            $data['golbal']=1;
            $data['level']=1;
            $game["golbal"]=10000;
           // Db::name("users")->where("openid",$openId)->setInc("gloal_num",$game["golbal"]);
            $redis->zincrby('AllopenIdGmethod', $game["golbal"], $openId);
            Db::name("prize")->insert($game);
        }else if($rand<=415){
            $data['golbal']=2;
            $data['level']=2;
            $game["golbal"]=20000;
           // Db::name("users")->where("openid",$openId)->setInc("gloal_num",$game["golbal"]);
            $redis->zincrby('AllopenIdGmethod', $game["golbal"], $openId);
            Db::name("prize")->insert($game);
        }else if($rand<=455){
            $data['golbal']=3;
            $data['level']=3;
            $game["golbal"]=30000;
           // Db::name("users")->where("openid",$openId)->setInc("gloal_num",$game["golbal"]);
            $redis->zincrby('AllopenIdGmethod', $game["golbal"], $openId);
            Db::name("prize")->insert($game);
        }else if($rand<=480){
            $data['golbal']=4;
            $data['level']=4;
            $game["golbal"]=50000;
           // Db::name("users")->where("openid",$openId)->setInc("gloal_num",$game["golbal"]);
            $redis->zincrby('AllopenIdGmethod', $game["golbal"], $openId);
            Db::name("prize")->insert($game);
        }else{
            $data['golbal']=5;
            $data['level']=5;
            $game["golbal"]=80000;
           // Db::name("users")->where("openid",$openId)->setInc("gloal_num",$game["golbal"]);
            $redis->zincrby('AllopenIdGmethod', $game["golbal"], $openId);
            Db::name("prize")->insert($game);
        }
        $arr=resCode(200,"ok",$data);
        return $arr;
    }
    //查询今天获奖记录
    public function today_prize()
    {
        $res=Db::name("prize")->whereTime("receve_time","today")->order("golbal desc")->select();
        foreach($res as $k=>$v){
            $result=Db::name("users")->where('openid',$v['openid'])->field('user_name,user_img')->find();
            $res[$k]['user_name']=$result['user_name']?$result['user_name']:"游客";
            $res[$k]['user_img']=$result['user_img']?$result['user_img']:"http://img.ky121.com/nihao.png";
        }
        $arr=resCode(200,"ok",$res);
        return $arr;
    }
    //我的中奖记录
    public function my_prize()
    {
        $openId=input("post.openId");
        $res=Db::name("prize")->whereTime("receve_time","today")->where("openid",$openId)->select();
        $total=Db::name("prize")->where("openid",$openId)->sum("golbal");
        $today=Db::name("prize")->whereTime("receve_time","today")->where("openid",$openId)->sum("golbal");
       /* $data['total']=$total;
        $data['today_total']=$today;*/
        $arr=resCode(200,"ok",array("list"=>$res,"total"=>$total,'today_total'=>$today));
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
        $data=cache("gmethod_formid");
        if(empty($data)){
            $data[]=$arr;
            cache("gmethod_formid",$data);
        }else if(count($data)<5000){
            array_push($data,$arr);
            cache("gmethod_formid",$data);
        }else{
            Db::name('xcx_formid')->insertAll($data);
            cache("gmethod_formid",null);
        }
    }

    public function cache_formid()
    {
        $data=cache("gmethod_formid");
        if(!empty($data)){
            Db::name('xcx_formid')->insertAll($data);
            cache("gmethod_formid",null);
        }
    }
}