<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/5/26
 * Time: 11:51
 */

namespace app\handgk\controller;


use app\handgk\model\User;
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
        $gkTime=input("date_time", '2018-06-07 09:00:00');//高考时间
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
            $arr=array("code"=>200,"msg"=>"success","data"=>array("openId"=>$openid,"wx_session_key"=>$session_key));
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
        $result=cache("handgk_distence");
        $type=input("type",1);
        if(empty($result)){
            $result=db("distance")->where("type",$type)->column('distance');
            //dump($result);die;
            cache("handgk_distence",$result);
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
        $arr=resCode(200,"可以",$totle);
        return $arr;
    }


    //发布的接口
    public function discuss()
    {
        $user_name=input("user_name");//昵称
        $img_url=input("img_url");//头像
        $user_img=input("user_img");//头像
        $content=input("content");//内容
        $openId=input("openId");
        $up_time=time();//上传时间
        $data['user_name']=$user_name;
        $data['img_url']=$img_url;
        $data['content']=$content;
        $data['up_time']=$up_time;
        $data['user_img']=$user_img;
        $data['openid']=$openId;
        $res=Db::table("gkhand_comment")->insert($data);
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
        $toName=input('to_name');
        $replyTime=time();//上传时间
        $data['comment_id']=$replyId;
        $data['from_name']=$fromName;
        $data['from_content']=$fromContent;
        $data['to_name']=$toName;
        $data['reply_time']=$replyTime;
        $data['from_img']=$fromImg;
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
        $type=input("type");//1.为点赞2.为取消点赞
        $openId=input("openId");
        if($type==1){
            $data["openid"]=$openId;
            $data['comment_id']=$id;
            $data['status']=1;
            $res= Db::table('gkhand_star')->where('comment_id', $id)->where('openid',$openId)->select();
            if($res==0||empty($res)){
                Db::table('gkhand_star')->insert($data);
                Db::table('gkhand_comment')->where('id', $id)->setInc('star');
            }else{
                Db::table('gkhand_star')->where('comment_id', $id)->where('openid',$openId)->update(['status'=>1]);
                Db::table('gkhand_comment')->where('id', $id)->setInc('star');
            }
        }else{
            Db::table('gkhand_star')->where('comment_id', $id)->where('openid',$openId)->update(['status'=>0]);
            Db::table('gkhand_comment')->where('id', $id)->setDec('star');
        }
        $arr=resCode(200,"ok",null);
        return $arr;
    }
    //获取每20条所有评论
    public function allDiscuss()
    {
       // $ids=array();
        $page=input("page",1);//页面
        $pageSize=input('pageSize',20);
        $openId=input("post.openId");
        $res=Db::table("gkhand_comment")->order('up_time desc')->page($page,$pageSize)->select();
        foreach ($res as $k=>$v){
            $reply=Db::table("gkhand_reply")->where('comment_id',$v['id'])->order('reply_time desc')->limit(2)->select();
            $count=Db::table("gkhand_reply")->where('comment_id',$v['id'])->count();
            $status= Db::table('gkhand_star')->where('comment_id', $v['id'])->where('openid',$openId)->value('status');
            if(empty($status)){
                $status=0;
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
       $res=Db::table("gkhand_reply")->where("comment_id",$id )->order('reply_time desc')->page($page,$pageSize)->select();
        $status= Db::table('gkhand_star')->where('comment_id', $id)->where('openid',$openId)->value('status');
        $count=Db::table("gkhand_reply")->where('comment_id',$id)->count();
        if(empty($status)){
            $status=0;
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