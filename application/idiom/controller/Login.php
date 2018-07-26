<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/5/19
 * Time: 10:01
 */

namespace app\idiom\controller;


use app\idiom\model\Game;
use app\idiom\model\User;
use common\controller\ApiLogin;
use think\Cache;
use think\Controller;
use think\Db;

class Login extends ApiLogin
{
    public function login()
    {
        $userdao =new User();
        $userInfo=input('post.userInfo');//获取前台传送的用户信息
        $userInfo=str_replace("&quot;","\"",$userInfo);
        $userInfo=json_decode($userInfo,true);
        //dump($userInfo);die;
        //  $login_data=$this->test_weixin($code);
        $userName=$userInfo['nickName'];
        $str=mb_strlen($userName,'UTF-8');
        if($str>6){
            $userName=mb_substr($str,0,6,'UTF-8');
        }
        $openid=input('post.openId');//获取opendId
        $wx_key=input('post.session_key');
        if ($openid&&!empty($userInfo)) {
            session('wx_session_key',$wx_key);
            $user = $userdao->findByOpenid($openid);
            $season=$userdao->getCurrentSeason();
            if($user['status']==0) {
                    $arr=resCode(403,"已经被拉黑",array("user_id"=>$user['id']));
                    return $arr;
                }
                $uid=$user["id"];
                $user_data['gender'] = $userInfo['gender'];
                $user_data['city'] = $userInfo['city'];
                $user_data['province'] = $userInfo['province'];
                $user_data['country'] = $userInfo['country'];
                $user_data['avatar_url'] =  str_replace('/0','/132',$userInfo['avatarUrl'] );
                $user_data['login_time'] =time();
                $user_data['nickname'] = $userName;
                db('users')->where('id',$uid)->update($user_data);
                $game_data["avatar_url"]=str_replace('/0','/132',$userInfo['avatarUrl'] );
                $game_data['nickname']=$userName;

                db("game")->where("openid",$openid)->where("season",$season)->update($game_data);
            $session_k=session_id();
            session('user_id',$uid);
            $arr=resCode(200,$userInfo,array('session_key'=>$session_k));
            return $arr;
        }else {
            $arr=resCode(400,"用户不存在",null);
            return $arr;
        }
    }


    //获取openId
    public function get_openid()
    {
        $userdao =new User();
        $code = input('post.code');
        $season=$userdao->getCurrentSeason();
        $login_data = $this->test_weixin($code);
        if (empty($login_data['code'])) {
            $openid = $login_data['openid'];
            $session_key=$login_data['session_key'];
            $user = $userdao->findByOpenid($openid);
            $game=$userdao->getGame($openid,$season);
            // print_r($season);die;
            if(empty($user)&&empty($game)){
                $data['openid']=$openid;
                $data["login_time"]=time();
                $game['openid']=$openid;
                db("users")->insert($data);
                db("game")->insert($game);
            }
            if(empty($game)){
                $game['season']=$season;
                $game['openid']=$openid;
                db("game")->insert($game);
            }
            $sql=db("users")->where('openid',$openid)->fetchSql(true)->value("is_sign,revive");
            $data=db()->query($sql);
           // print_r($data);die;
            $arr=array("code"=>200,"msg"=>"success","data"=>array("openId"=>$openid,"wx_session_key"=>$session_key,"is_sign"=>$data[0]['is_sign'],"revive"=>$data[0]['revive']));
            return $arr;
        }
        else{
            return $login_data;

        }
    }

    // 获取用户信息
    public function get_user_info()
    {
        $res=Cache::init();
        $redis=$res->handler();
        $openId=input('post.openId');//获取opendId
        $chance_num=$redis->zscore("AllopenIdIdiom",$openId);
        if(!is_numeric($chance_num)){
            $chance_num=Db::name("users")->where("openid",$openId)->value("chance_num");
            $redis->zadd('AllopenIdIdiom',$chance_num, $openId);
            $chance_num=$redis->zscore("AllopenIdIdiom",$openId);
        }
        $data=db("users")->where('openid',$openId)->find();
        $data['chance_num']=$chance_num;
        $res=resCode(200,"ok",$data);
        return $res;
    }

    //获取题目
    public function get_question()
    {
        $game=new Game();
        $layer=input("post.layer",1);
        $openId=input("post.openId");
        $count=Db::name("answer")->count();
        if($layer>$count){
            $layer=$layer%$count;
        }
        $question=$game->get_question($layer,$openId);
        if($question){
            $arr=resCode(200,"获取成功",array("layer"=>(int)$layer,"nex_layer"=>$layer+1,"question"=>$question));
        }else{
            $arr=resCode(400,"题库出错");
        }
        return $arr;
    }
    /*//分享群
    public function share_group(){
        $openId=input("post.openId");//获取openId
        db("users")->where("openid",$openId)->setInc('revive');
        $data=array("code"=>200,"msg"=>"success","data"=>null);
        return $data;
    }*/
    //分享群
    public function share_group(){
        $openId=input("post.openId");
        $encryptedData = input("post.encryptedData");
        $iv = input("post.iv");
        $session_key=input("session_key");
        $type=input("type");//1.增加挑战机会 2.复活
        if(!$encryptedData||!$iv||!$session_key){
            $arr=resCode(400,"参数为空",null);
            return  $arr;
        }
        vendor("wxaes.wxBizDataCrypt");
        $res=Cache::init();
        $redis=$res->handler();
        $chance_num=$redis->zscore("AllopenIdIdiom",$openId);
        if(!is_numeric($chance_num)){
            $chance_num=Db::name("users")->where("openid",$openId)->value("chance_num");
            $redis->zadd('AllopenIdIdiom',$chance_num, $openId);
            //$chance_num=$redis->zscore("AllopenIdIdiom",$openId);
        }
        $wxBizDataCrypt = new \WXBizDataCrypt(config("WECHAT_APPID"), $session_key);
        $data_arr = array();
        $errCode = $wxBizDataCrypt->decryptData($encryptedData, $iv, $data_arr);
        if($errCode==0) {
            $json_data = json_decode($data_arr, true);
            $openGid=$json_data['openGId'];
                $where=array("openGid"=>$openGid,"openid"=>$openId,"type"=>$type);
                $res=Db::name("share_group")->where($where)->find();
                if(!empty($res)&&$res['share_time']<strtotime(date("Y-m-d"))){
                    Db::name("share_group")->where($where)->setField("share_time",time());
                   if($type==1){
                       //Db::name("users")->where("openid",$openId)->setInc("chance_num");
                       $redis->zincrby('AllopenIdIdiom', 1, $openId);
                   }else{
                       Db::name("users")->where("openid",$openId)->setInc("revive");
                   }
                    $arr=resCode(200,"ok",null);
                    return $arr;
                }else if(empty($res)){
                    $data['openid']=$openId;
                    $data['share_time']=time();
                    $data['openGid']=$openGid;
                    $data['type']=$type;
                    Db::name("share_group")->insert($data);
                    if($type==1){
                       // Db::name("users")->where("openid",$openId)->setInc("chance_num");
                        $redis->zincrby('AllopenIdIdiom', 1, $openId);
                    }else{
                        Db::name("users")->where("openid",$openId)->setInc("revive");
                    }
                    $arr=resCode(200,"ok",null);
                    return $arr;
                }else{
                    $arr=resCode(400,"运气不好没得到机会，换个群试试",null);
                    return $arr;
                }
        }else{
            $arr=resCode(400,"参数错误",null);
            return $arr;
        }

    }
    //结束挑战
    public function end_challenge()
    {
        $userdao=new User();
        $openId=input("post.openId");//获取openId
        $score=input("post.score");
        $season=$userdao->getCurrentSeason();
        $sql=db("game")->where("openid",$openId)->where("season",$season)->fetchSql(true)->value("score,chanllege_num");
        $history=db()->query($sql);
        // print_r($history);die;
        if($history[0]['score']<=$score){
            $data['fail_time']=date('Y-m-d H:i:s');
            $data["score"]=$score;
            $data["chanllege_num"]=$history[0]['chanllege_num']+1;
        }else{
            $data["chanllege_num"]=$history[0]['chanllege_num']+1;
        }
        $info=db("game")->where("openid",$openId)->where("season",$season)->update($data);
        $count_people=db("game")->whereTime("fail_time","w")->count();
        $arr=resCode(200,"结束成功",array("people"=>$count_people));
        return $arr;
    }
    //开始挑战
    public function begain_challenge()
    {
        $openId=input("post.openId");//获取openId
        $res=Cache::init();
        $redis=$res->handler();
        $chance_num=$redis->zscore("AllopenIdIdiom",$openId);
        if(!is_numeric($chance_num)){
            $chance_num=Db::name("users")->where("openid",$openId)->value("chance_num");
            $redis->zadd('AllopenIdIdiom',$chance_num, $openId);
            $chance_num=$redis->zscore("AllopenIdIdiom",$openId);
        }
        //$chance_num=Db::name("users")->where("openid",$openId)->value("chance_num");
        if($chance_num>0){
           // Db::name("users")->where("openid",$openId)->setDec("chance_num");
            $redis->zincrby('AllopenIdIdiom', -1, $openId);
            $arr=resCode(200,"开始成功",null);
            return $arr;
        }else{
            $arr=resCode(400,"机会不足,请分享",null);
            return $arr;
        }

    }
    //定时更新数据库
    public function put_cache()
    {
        $res = Cache::init();
        $redis = $res->handler();
        $gloal_num = $redis->zrange('AllopenIdIdiom', 0, -1, true);
        foreach($gloal_num as $k=>$v){
            Db::name("users")->where("openid",$k)->setField("chance_num",$v);
        }
        $redis->ZREMRANGEBYRANK ('AllopenIdIdiom',0,100000);

    }


    public function sign()
    {
        $openId=input("post.openId");
        $sql=db("users")->where("openid",$openId)->fetchSql(true)->value("is_sign,revive");
        $data=db()->query($sql);
        //print_r($data);die;
        if($data[0]['is_sign']==1){
            $arr=resCode(400,"已经签到",null);
        }else{
            db("users")->where("openid",$openId)->update(['revive'=>$data[0]['revive']+2,"is_sign"=>1]);
            $arr=resCode(200,"签到成功",null);
        }
        return $arr;
    }
    //个人本周最佳
    public function best_week()
    {
        $userdao=new User();
        $season=$userdao->getCurrentSeason();
        $openId=input("post.openId");
        $result=db("game")->where("openid",$openId)->where("season",$season)->whereTime("fail_time",'w')->order("score","desc")->limit(1)->value("score");
       if($result){
           $arr=resCode(200,"查询成功",$result);
       }else{
           $arr=resCode(400,"查询失败",null);
       }
       return $arr;

    }
    
    //申请领奖
    public function prize()
    {
        $openId=input("post.openId");//用户身份标志
        $userName=input("post.user_name");//用户名字
        $cartNum=input("post.cart_num");//银行卡号
        $bankName=input("post.bank_name");//银行名字
        $brunchName=input("post.brunch_name");//支行名字
        $cashNum=input("post.case_num");//金钱
        $putTime=date("Y-m-d H:i:s");
        $phoneName=input('phone_number');
        if($cashNum==0){
            $arr=resCode(400,"不能提现",null);
            return $arr;
        }
           $data['openid']=$openId;
           $data['user_name']=$userName;
           $data['cart_num']=$cartNum;
           $data['bank_name']=$bankName;
           $data['brunch_name']=$brunchName;
           $data['case_num']=$cashNum;
           $data['put_time']=$putTime;
           $data['phone_number']=$phoneName;
           $info=db("cart")->insert($data);
           if($info){
               db("users")->where("openid",$openId)->update(["total_cash"=>0,"is_cash"=>1]);
               $arr=resCode(200,"信息插入成功",null);
           }else{
               $arr=resCode(400,"信息插入失败",null);
           }
        return $arr;
    }
    //提现记录
    public function put_forward()
    {
        $openId=input("post.openId");
        $data=db("cart")->where("openid",$openId)->select();
        if($data){
            $arr=resCode(200,"查询成功",$data);
        }else{
            $arr=resCode(200,"查询为空",null);
        }
        return $arr;
    }

    //排名缓存
    public function cache_rank(){
        $info2=db("game")->whereTime("fail_time","last week")->field("openid,nickname,avatar_url,chanllege_num,score,season,fail_time")->order("score desc")->limit(100)->select();
        foreach($info2 as $k=>$v){
            $info2[$k]['ranking']=$k+1;
        }
        //print ($info2);die;
        cache("idiom_work_rank",$info2);
    }
    //设置缓存
    public function set_session()
    {
        session("user_id",1);
    }

    public function get_time()
    {
        $current=time();
        $date=$this->getNextMonday();
        $time=strtotime($date);
        $next_time=$time-$current;
        $arr=resCode(200,"success",$next_time);
        return $arr;
    }

    /**
     * 取得下个周一
     * @internal param $time
     */
    private function getNextMonday()
    {
        return date('Y-m-d',strtotime('+1 week last monday'));
    }

    /**
     * 小程序formid写入
     * @param form_id 小程序formid
     * @param open_id 微信openid
     */
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
        $data=cache("idiom_formid");
        if(empty($data)){
            $data[]=$arr;
            cache("idiom_formid",$data);
        }else if(count($data)<5000){
            array_push($data,$arr);
            cache("idiom_formid",$data);
        }else{
            Db::name('xcx_formid')->insertAll($data);
            cache("idiom_formid",null);
        }
    }

    //从缓存中取
    public function cache_formid()
    {
        $data=cache("idiom_formid");
        if(!empty($data)){
            Db::name('xcx_formid')->insertAll($data);
            cache("idiom_formid",null);
        }
    }
    //每周一定时刷新挑战季度
    public function update_season()
    {
        $season=db("season")->order('season desc')->limit(1)->value("season");
        if(empty($season)){
            $season=0;
        }
        $data['season']=$season+1;
        $data['begain_time']=date("Y-m-d");
        db("season")->insert($data);

    }

    //立即领取
    public function get_status()
    {

        $openId=input("post.openId");
        $season=input("season");
        $data=db("apply")->where("openid",$openId)->where("season",$season)->value("status");
        if(empty($data)){
            $data=0;
        }
        $arr=resCode(200,"查询成功",$data);
        return $arr;
    }
    //申请领取
    public function apply()
    {
        $openId=input("post.openId");
        $season=input("season");
        $cash=input("cash");
        $data["openid"]=$openId;
        $data['cash']=$cash;
        $data['season']=$season;
        $data['status']=1;
        $data['apply_time']=date("Y-m-d");
       $info=db("apply")->where("openid",$openId)->where("season",$season)->select();
       if(empty($info)){
           /*$id=db("apply")->insertGetId($data);
          if($id){
              db("users")->where("openid",$openId)->setInc("total_cash",$cash);
          }*/
           // 启动事务
             db("apply")->insert($data);
             db("users")->where("openid",$openId)->setInc("total_cash",$cash);
            // 提交事务

               $arr=resCode(200,"插入成功",null);
       }
        return $arr;
    }
    //总奖金
    public function put_cash()
    {
        $openId=input("post.openId");
       // $cash=input("post.cash");
       // db("users")->where("openid",$openId)->setInc("total_cash",$cash);
        $data=db("users")->where("openid",$openId)->value("total_cash");
        $arr=resCode(200,"更新成功",$data);
        return $arr;
    }
    //小程序跳转
    public function jump()
    {
        $box_app_id="wx038a63af17ad0c8e";
        $target_app_id="wx2bd2b34cbbebf754";
        $img_url="http://img.ky121.com/jieri/ad/2.gif";
        $rob_app_id="wxdd815c0d7c274238";
        $data["box_app_id"]=$box_app_id;
        $data['target_app_id']=$target_app_id;
        $data['img_url']=$img_url;
        $data['rob_app_id']=$rob_app_id;
        $arr=resCode(200,"ok",$data);
        return $arr;
    }

    //使用复活卡
    public function use_revive()
    {
        $openid=input("post.openId");
        db("users")->where("openid",$openid)->setDec("revive");
        $arr=resCode(200,"成功",null);
        return $arr;
    }
    //查询缓存的长度
    public function cache_long(){
        $data=cache("idiom_formid");
        return count($data);
    }
}