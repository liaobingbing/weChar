<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/5/26
 * Time: 11:51
 */

namespace app\gkhand\controller;


use app\gkhand\model\User;
use common\controller\ApiLogin;
use think\Cache;
use think\Db;
use think\Request;

class Api extends ApiLogin
{
    //距离2018年高考的时间
    public function distence_time()
    {
        $currt=time();//当前时间
        $gkTime=input("date_time", '2018-06-08 17:00:00');//高考时间
        /* echo  $gkTime."\n";
         $time2=strtotime($gkTime);
         echo $time2."\n";
         echo date('Y-m-d H:i:s',$time2);
         die;*/
        $distence=abs(strtotime($gkTime)-$currt);
        //$str=floor($distence%(24*3600)/3600);
        $arr=resCode(200,"计算成功",$distence);
        return $arr;
    }
    //获取小程序的openid
    public function get_openid()
    {
        $code = input('post.code');
        $login_data = $this->test_weixin($code);
        if (empty($login_data['400'])&&$login_data['openid']) {
            $openid = $login_data['openid'];
            $session_key=$login_data['session_key'];
            $arr=array("code"=>200,"msg"=>"success","data"=>array("openId"=>$openid,"wx_session_key"=>$session_key,'status'=>2));
            $userdao=new User();
            $user = $userdao->findByOpenid($openid);
            if(empty($user)){
                $data['openid']=$openid;
                db("users")->insert($data);
            }
            return $arr;
        }
        else{
            return $login_data;

        }
    }
    //授权获取头像，昵称
    public function login()
    {
        $userdao=new User();
        $openid=input("post.openId");
        $nickname=input("post.nickName");
        $url=input("post.imgUrl");
        $user = $userdao->findByOpenid($openid);
        if(empty($user)){
            $arr=resCode(400,"用户不存在",null);
        }else {
            $data['img_url'] = $url;
            $data['nickname'] = $nickname;
            db("users")->where("openid", $openid)->update($data);
            $arr=resCode(200,"更新成功",null);
        }
        return $arr;
    }
    //支持一下
    public function support()
    {
        $result=cache("gkhand_support");
        if(empty($result)){
            $result=db("support")->column('support');
            cache("gkhand_support",$result);
        }
        shuffle($result);
        $arr=resCode(200,"查询成功",$result[0]);
        return  $arr;
    }
    //距离高考
    public function distence()
    {
        $result=cache("gkhand_distence");
        if(empty($result)){
            $result=db("distance")->column('distance');
            cache("gkhand_distence",$result);
        }
        shuffle($result);
        $arr=resCode(200,"查询成功",$result[0]);
        return  $arr;
    }
    //分享群
    public function share()
    {
        $arr=resCode(200,"分享成功",null);
        return $arr;
    }
    //统计多人人
    public function count_people()
    {
        $totle=Cache::get("gkhand_totle");
        if(empty($totle)){
            $count=db("users")->count();
            $totle=226876+$count;
            Cache::set("gkhand_totle",$totle);
        }else{
            Cache::inc('gkhand_totle');
            $totle=Cache::get("gkhand_totle");
        }
        $arr=["code"=>200,"msg"=>"可以","data"=>$totle,"status"=>1];
        return $arr;
    }


    //发布的接口
    public function discuss()
    {
        $user_name=input("user_name");//昵称
        $img_url=input("img_url");//头像
        $user_img=input("user_img");//头像
        $content=input("content");//内容
        $s=$this->check_msg($content);
       if($s){
           $arr=resCode(400,"含有敏感词汇",null);
           return $arr;
       }
        $content=json_encode($content);
        $openId=input("openId");
        $up_time=time();//上传时间
        $data['user_name']=$user_name;
        $data['img_url']=$img_url;
        $data['content']=$content;
        $data['up_time']=$up_time;
        $data['user_img']=$user_img;
        $data['openid']=$openId;
        $res=Db::table("gkhand_comment")->insertGetId($data);
        $cache=cache("gkhand_result");
        if(empty($cache)){
            $data=Db::table("gkhand_comment")->order('up_time desc')->limit(100)->select();
            cache("gkhand_result",$data,7200);
        }else{
            $data['id']=$res;
            $data['star']=0;
            $cache=cache("gkhand_result");
            if(count($cache)<100){
                array_unshift($cache,$data);
                cache("gkhand_result",$cache);
            }else{
                array_pop($cache);
                array_unshift($cache,$data);
                cache("gkhand_result",$cache);
            }
        }
        if($res){
            $arr=resCode(200,"发布成功",null);
        }else{
            $arr=resCode(400,"发布失败",null);
        }
        return $arr;
    }
    //评论的接口
    public function reply()
    {
        $replyId=input("comment_id");//评论的Id
        $fromName=input("from_name");//评论人的姓名
        $fromImg=input("from_img");
        $fromContent=input('from_content');//内容
        $s=$this->check_msg($fromContent);
        if($s){
            $arr=resCode(400,"含有敏感词汇",null);
            return $arr;
        }
        $fromOpenid=input("from_openid");
        $toOpenid=input("to_openid");
        $fromContent=json_encode($fromContent);
        $toName=input('to_name');
        $replyTime=time();//上传时间
        $data['comment_id']=$replyId;
        $data['from_name']=$fromName;
        $data['from_content']=$fromContent;
        $data['to_name']=$toName;
        $data['reply_time']=$replyTime;
        $data['from_img']=$fromImg;
        $data['from_openid']=$fromOpenid;
        $data['to_openid']=$toOpenid;
        $res=Db::table("gkhand_reply")->insert($data);
        if($res){
            $arr=resCode(200,"评论成功",null);
        }else{
            $arr=resCode(400,"评论失败",null);
        }
        return $arr;
    }
    //点赞的功能
    public function star()
    {
        $id=input('id');//评论表id
        $openId=input("openId");
        $data['openid']=$openId;
        $data['comment_id']=$id;
        if(empty(Db::table('gkhand_star')->where('comment_id', $id)->where('openid',$openId)->count())){
            //点赞
            if(Db::table('gkhand_star')->insertGetId($data)){
                Db::table('gkhand_comment')->where('id', $id)->setInc('star');
                $arr=resCode(200,array('agree_state'=>true));
                $res=cache("gkhand_result");
                $k=array_search($id, array_column($res, 'id'));//这里可以优化
                if($k!==""){
                   $res[$k]['star']=$res[$k]['star']+1;
                }
                /*foreach ($res as $k=>$v){
                    if($v['id']==$id){
                       $res[$k]['star']=$res[$k]['star']+1;
                    }
                }*/
                cache("gkhand_result",$res);
            }else{
                $arr=resCode(400,"error",null);
            }

        }else{
            //取消点赞
            $rslt =Db::table('gkhand_star')->where('comment_id', $id)->where('openid',$openId)->delete();
            if($rslt!==false){
                Db::table('gkhand_comment')->where('id', $id)->setDec('star');
                $arr=resCode(200,array('agree_state'=>false));
                $res=cache("gkhand_result");
                $k=array_search($id, array_column($res, 'id'));//这里可以优化
               if($k!==""){
                   $res[$k]['star']=$res[$k]['star']-1;
               }
                /*foreach ($res as $k=>$v){
                    if($v['id']==$id){
                        $res[$k]['star']=$res[$k]['star']-1;
                    }
                }*/
                cache("gkhand_result",$res);
            }else{
                $arr=resCode(400,"error",null);
            }

        }

        return $arr;
    }
    //获取每20条所有评论 //
    public function allDiscuss()
    {
        // $ids=array();
        $page=input("page",1);//页面
        $pageSize=input('pageSize',20);
        $openId=input("post.openId");
        $start=($page-1)*$pageSize;
        $type=input("post.type",1);//动态类型1，所有的动态 2.自己发表的动态
        if($type==1){
            $data=cache("gkhand_result");
            if(empty($data)) {
                $data = Db::table("gkhand_comment")->order('up_time desc')->page($page, $pageSize)->select();//所有的评论
            }
            $res=array_slice($data,$start,$pageSize);
        }else{
                $res=Db::table("gkhand_comment")->where("openid",$openId)->order('up_time desc')->page($page,$pageSize)->select();//我自己发表的
        }
        foreach ($res as $k=>$v){
            $reply=Db::table("gkhand_reply")->where('comment_id',$v['id'])->order('reply_time')->limit(2)->select();
            $count=Db::table("gkhand_reply")->where('comment_id',$v['id'])->count();
            $status= Db::table('gkhand_star')->where('comment_id', $v['id'])->where('openid',$openId)->count();
            // $res[$k]['content']=json_decode($res[$k]['content']);
            if(empty($status)){
                $status=0;
            }else{
                $status=1;
            }
            $res[$k]['reply']=$reply;
            $res[$k]["total"]=$count;
            $res[$k]['status']=$status;
        }
        $arr=resCode(200,"查询成功",$res);

        return $arr;
    }
    //获取单挑评论
    public function oneDiscuss()
    {
        $id=input("id");
        $openId=input("post.openId");
        $page=input("page",1);//页面
        $pageSize=input('pageSize',20);//页面大小
        $comment=Db::table("gkhand_comment")->where("id",$id)->find();
        $res=Db::table("gkhand_reply")->where("comment_id",$id )->order('reply_time')->page($page,$pageSize)->select();
        $status= Db::table('gkhand_star')->where('comment_id', $id)->where('openid',$openId)->count();
        $count=Db::table("gkhand_reply")->where('comment_id',$id)->count();
        if(empty($status)){
            $status=0;
        }else{
            $status=1;
        }
        $comment['status']=$status;
        $comment['total']=$count;
        if($comment){
            $arr=resCode(200,"查询成功",array("comment"=>$comment,"replay"=>$res));
        }else{
            $arr=resCode(400,"查询失败",null);
        }
        return $arr;
    }
    //我的回复
    public function replyList()
    {
        $page=input("page",1);//页面
        $pageSize=input('pageSize',20);//页面大小
        $openId=input("openId");
        $res=Db::table("gkhand_reply")->where("to_openid",$openId )->order('reply_time')->page($page,$pageSize)->fetchSql(false)->select();
      //  echo $res;die;
        $arr=resCode(200,"查询成功",$res);
        return $arr;
    }
    //获取access_token;
    public function accessToken()
    {
        $admin=model('Admin');
        $appId=config('WECHAT_APPID');
        $secrt=config('WECHAT_APPSECRET');
        $result=$admin->where('app_id', $appId)->find();
        if(empty($result)){
            $url="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appId&secret=$secrt";
            $res=$this->post_url($url);
            $access_token=$res['access_token'];
            if(!empty($access_token)){
                $admin->app_id = $appId;
                $admin->expires_at = time()+7200;
                $admin->access_token = $access_token;
                $admin->save();
            }
        }
        else{
            if($result['expires_at']>time()){
                $access_token=$result['access_token'];
            }else{
                $url="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appId&secret=$secrt";
                $res=$this->post_url($url);
                //$res=json_decode($res,true);
                $access_token=$res['access_token'];
                $admin->save([
                    'expires_at' => time()+7200,
                    'access_token' => $access_token,
                ],['app_id' => $appId]);
            }
        }
        return $access_token;
    }
    function post_url($url,$parameter=null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT,15);   //只需要设置一个秒的数量就可以
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        if(!empty($parameter)){
            // post数据
            curl_setopt($ch, CURLOPT_POST, 1);
            // post的变量
            curl_setopt($ch, CURLOPT_POSTFIELDS, $parameter);
        }
        $output = curl_exec($ch);
        curl_close($ch);
        $output = json_decode($output,true);
        return $output;
    }
    //判断文字是否违规
    public function check_msg($content)
    {
        $arr=array("Funck You","FunckYou","funckyou","funck you","操","妈的",
            "操你妈","牛逼","智障","挂靠","打工","办身份证","假结婚","避税","傻逼",
            "付CASH","私活","兼职","移民","绿卡","月子中心","庇护","大麻","黑客","狗日",
            "枪","恐怖袭击","爆炸","银行卡","暴力","毒品","金额","办文凭","办证","金额",
            "煞笔","我操你","撕逼","贱人","王八","王八蛋","婊子","废物","白痴","天杀的",
            "马屁精","神经病","禽兽");
        foreach($arr as $v){
            $s=strstr($content,$v);
            //echo $s;
            if(!empty($s)){
               return true;
            }

        }

    }
    public function check_img($img){
        $access=$this->accessToken();
        $url="https://api.weixin.qq.com/wxa/img_sec_check?access_token=$access";//&media=$img
        $data='media=@img';
        $res=$this->post_url($url,$data);
        return $res;
    }
    //删除动态，评论，点赞
    public function del_discuss()
    {
        $id=input("post.id");//动态Id
        $openId=input("openId");//删除动态
            // 启动事务
            Db::startTrans();
            try{
                Db::table('gkhand_comment')->where('id', $id)->where('openid',$openId)->delete();
                Db::table('gkhand_reply')->where('comment_id', $id)->delete();
                Db::table('gkhand_star')->where('comment_id', $id)->delete();
                // 提交事务
                Db::commit();
                $arr=resCode(200,"删除成功",null);
                $res=cache("gkhand_result");
                /*$k=array_search($id, array_column($res, 'id'));//这里可以优化
                if($k!==""){
                    unset($res[$k]);
                }*/
                foreach ($res as $k=>$v){
                    if($v['id']==$id){
                        unset($res[$k]);
                    }
                }
                cache("gkhand_result",$res);

            } catch (\Exception $e) {
            // 回滚事务
                Db::rollback();
                $arr=resCode(400,"删除失败",null);
            }
        return $arr;
    }
    //未读消息提醒
    public function tip()
    {
        $openId=input("post.openId");//openId
        $res=Db::table('gkhand_reply')->where('to_openid', $openId)->where("status",0)->select();
        $count=count($res);
        if($res){
            $arr=resCode(200,"查询成功",array("data"=>$res,"count"=>$count));
        }else{
            $arr=resCode(200,"查询为空",array("count"=>0));
        }
        return $arr;

    }
    //更新未读消息
    public function update_tip()
    {
        $openId=input("post.openId");//更新id，数组的形式
        $res=Db::table('gkhand_reply')->where('to_openid', $openId)->where("status",0)->setField("status",1);
        if($res){
            $arr=resCode(200,"更新成功",null);
        }else{
            $arr=resCode(400,"更新失败",null);
        }
        return $arr;
    }
//清除缓存
    public function rm_cache()
    {
        cache("gkhand_result",null);
    }
    /**
     * 小程序formid写入
     * @param form_id 小程序formid
     * @param open_id 微信openid
     */
    //不知道为什么没有同部
    public function addXcxFormId() {
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
    }
}