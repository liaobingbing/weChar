<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/6/12
 * Time: 17:24
 */

namespace app\mind\controller;


use app\mind\model\User;
use common\controller\ApiLogin;
use think\Db;
class Api extends ApiLogin
{
//获取小程序的openid
    public function get_openid()
    {
        $code = input('post.code');
        $img=input("post.userImg");
        $name=input("post.userName");
        $login_data = $this->test_weixin($code);
        if (empty($login_data['400'])&&$login_data['openid']) {
            $openid = $login_data['openid'];
            $session_key=$login_data['session_key'];
            $userdao=new User();
            $user = $userdao->findByOpenid($openid);
            $user_id=$user['id'];
            if(empty($user)){
                $data['openid']=$openid;
                $data['user_img']=$img;
                $data['user_name']=$name;
                $user_id=db("users")->insertGetId($data);
                db("game")->insert($data);
            }
            $arr=array("code"=>200,"msg"=>"success","data"=>array("openId"=>$openid,"wx_session_key"=>$session_key,"user_id"=>$user_id,"status"=>2));
            return $arr;
        }
        else{
            return $login_data;

        }
    }
//授权接口
    /*public function login()
    {
        $openId=input("openId");
        $img=input("post.userImg");
        $name=input("post.userName");
        $data['user_img']=$img;
        $data['user_name']=$name;
        Db::name("users")->where("openid",$openId)->update($data);
        $arr=resCode(200,"ok",null);
        return $arr;
    }*/
    //添加关卡结束时间
    public function chanllge_num()
    {
        $openId=input("openId");
        $layer=input("post.layer");
        $time=date('Y-m-d H:i:s');
        $data['layer']=$layer;
        $data['paly_time']=$time;
        Db::name("users")->where("openid",$openId)->update($data);
        $arr=resCode(200,"ok",null);
        return $arr;
    }
    //获取关卡
    public function get_layer()
    {
        $openId=input("openId");
        $res=Db::name("users")->where("openid",$openId)->value("layer");
        $arr=resCode(200,"ok",$res);
        return $arr;
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
    //混淆字体
    public function confuse()
    {
        $str="要采油松树下的花土外公说油松树下的土养花最好啦于是我和外公沿着山路往下走来到一个山坳里转身向上看去漫坡上草丛间长满了秋天开的各种野花有黄的有红的有白的还有紫的伴随着鸟儿的鸣叫声淡淡的花香随风飘来沁人心脾山坳里长满了粗壮的油松花土很松软外公忙着采而我却四处乱跑无意中我的左脚踩到了一块软乎乎的东西我抬起脚拨开落叶和草丛哇好大一块红蘑唉我大有所望地找起红蘑来外公采完花土就和我一起采红蘑时间过得真快我和外公也采了好多红蘑这就是将军北沟美丽的将军北沟听说这里要搞大开发建造适宜人们居住的豪华洋楼和别墅到那时红于绿树之间学校幼儿园座落在弯弯的潺潺溪水边这里将是一派盎然生机";
        $big=mb_strlen($str,'UTF-8');
        $input=input("answerlist");
        $input=json_decode($input,true);
        //print_r($input);die;
        $count=count($input);
        $arr=[];
        for($i=0;$i<$count;$i++){
            $string="";
            $len=mb_strlen($input[$i],'UTF-8');
            for($j=1;$j<10-$len;$j++){
                $rand=rand(0,$big-1);
                $string.=mb_substr($str,$rand,1,'UTF-8');
                // print_r($input[$i]);
            }
            $array=$input[$i].$string;
            $arr[]=$array;

        }
        $res=resCode(200,"ok",$arr);
        return $res;

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
        $result=Db::name('users')->whereIn("openid",$where_arr)->order('layer desc')->order('layer desc')->limit(50)->select();
        foreach($result as $k=>$v){
            $result[$k]['ranking']=$k+1;
            $result[$k]=$v;
        }
        $arr=resCode(200,"ok",$result);
        return $arr;
    }
    //本周排行
    public function week_rank()
    {
        $res=Db::name("users")->whereTime("paly_time","w")->order("layer desc")->limit(200)->select();
        $arr=resCode(200,"ok",$res);
        return $arr;
    }

//分享群
    public function share_group(){
        $openId=input("post.openId");
        $encryptedData = input("post.encryptedData");
        $iv = input("post.iv");
        $session_key=input("session_key");
        if(!$encryptedData||!$iv||!$session_key){
            $arr=resCode(400,"参数为空",null);
            return  $arr;
        }
        vendor("wxaes.wxBizDataCrypt");
        $wxBizDataCrypt = new \WXBizDataCrypt(config("WECHAT_APPID"), $session_key);
        $data_arr = array();
        $errCode = $wxBizDataCrypt->decryptData($encryptedData, $iv, $data_arr);
        if($errCode==0) {
            $json_data = json_decode($data_arr, true);
            $openGid=$json_data['openGId'];
            $res=Db::name("share_group")->where("openGid",$openGid)->where("openid",$openId)->find();
            if(!empty($res)&&$res['share_time']<strtotime(date("Y-m-d"))){
                Db::name("share_group")->where("openGid",$openGid)->where("openid",$openId)->setField("share_time",time());
                $arr=resCode(200,"ok",null);
                return $arr;
            }else if(empty($res)){
                $data['openid']=$openId;
                $data['share_time']=time();
                $data['openGid']=$openGid;
                Db::name("share_group")->insert($data);
                $arr=resCode(200,"ok",null);
                return $arr;
            }else{
                $arr=resCode(400,"已经分享过",null);
                return $arr;
            }
        }

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
        $data=cache("mind_formid");
        if(empty($data)){
            $data[]=$arr;
            cache("mind_formid",$data);
        }else if(count($data)<5000){
            array_push($data,$arr);
            cache("mind_formid",$data);
        }else{
            Db::name('xcx_formid')->insertAll($data);
            cache("mind_formid",null);
        }
    }

    public function cache_formid()
    {
        $data=cache("mind_formid");
        if(!empty($data)){
            Db::name('xcx_formid')->insertAll($data);
            cache("mind_formid",null);
        }
    }
}