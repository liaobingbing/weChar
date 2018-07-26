<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/5/19
 * Time: 10:01
 */

namespace app\idiom\controller;

use think\Controller;
use think\Request;

class Api extends Controller
{
    //本周排名
    public function week_rank()
    {
        $type=input("post.type");
        $my=array();
        $page=input("post.page",1);
        $pagesize=input("post.pageSize",10);
        $openId=input("post.openId");
        $info = db("game")->whereTime("fail_time", "w")->field("openid,nickname,avatar_url,chanllege_num,score,season,fail_time")->whereNotNull("avatar_url")->order("score desc")->limit(100)->select();
        foreach($info as $k=>$v){
            $info[$k]['ranking']=$k+1;
            if($info[$k]['ranking']==1){
                $info[$k]['cashNum']=500;
            }elseif($info[$k]['ranking']==2){
                $info[$k]['cashNum']=200;
            }elseif($info[$k]['ranking']==3){
                $info[$k]['cashNum']=100;
            }else{
                $info[$k]['cashNum']=0;
            }
            if($v['openid']==$openId){
                $my=$info[$k];
                // $my=$info[$k];
            }
        }
        if(empty($my)){
            $my=$this->my_rank(1,$openId);
        }
        $start=($page-1)*$pagesize; #计算每次分页的开始位置
       $info=array_slice($info,$start,$pagesize);
        if($type==1) {
            $arr=resCode(200,"查询成功",array("my_rank"=>$my));
        }else{
            $arr=resCode(200,"查询成功",array("info"=>$info,"my_rank"=>$my));
        }
        return $arr;
    }
    //上周排名、
    public function last_rank()
    {
        $my=array();
        $openId=input("post.openId");
        $page=input("post.page",1);
        $pagesize=input("post.pageSize",10);
        $info=cache("idiom_work_rank");
        // print_r($info);die;
        if(empty($info)){
            $info=db("game")->whereTime("fail_time","last week")->field("openid,nickname,avatar_url,chanllege_num,score,season,fail_time")->whereNotNull("avatar_url")->order("score desc")->limit(100)->select();
            cache("idiom_work_rank",$info);

        }
        //print_r($info);die;
        foreach($info as $k=>$v){
            $info[$k]['ranking']=$k+1;
            if($info[$k]['ranking']==1){
                $info[$k]['cashNum']=500;
            }elseif($info[$k]['ranking']==2){
                $info[$k]['cashNum']=200;
            }elseif($info[$k]['ranking']==3){
                $info[$k]['cashNum']=100;
            }else{
                $info[$k]['cashNum']=0;
            }
            if($v['openid']==$openId){
                $my=$info[$k];
                // $my=$info[$k];
            }
        }
        if(empty($my)){
            $my=$this->my_rank(2,$openId);
        }
        $start=($page-1)*$pagesize; #计算每次分页的开始位置
         $info=array_slice($info,$start,$pagesize);
        //  print_r($info);die;
        $arr=resCode(200,"查询成功",array("info"=>$info,"my_rank"=>$my));
        return $arr;
    }
/*
 * @param type=1我的本周排行
 * @param type=2 上周排行
 */
    public function my_rank($type=1,$openId="")
    {
        $my="";
        if($type==1) {
            $info = db("game")->whereTime("fail_time", "w")->field("openid,nickname,avatar_url,chanllege_num,score,season,fail_time")->order("score desc")->select();
        }
        else {
            $info=db("game")->whereTime("fail_time","last week")->field("openid,nickname,avatar_url,chanllege_num,score,season,fail_time")->order("score desc")->select();
        }
            foreach($info as $k=>$v) {
                $info[$k]['ranking'] = $k + 1;
                if ($info[$k]['ranking'] == 1) {
                    $info[$k]['cashNum'] = 500;
                } elseif ($info[$k]['ranking'] == 2) {
                    $info[$k]['cashNum'] = 200;
                } elseif ($info[$k]['ranking'] == 3) {
                    $info[$k]['cashNum'] = 100;
                } else {
                    $info[$k]['cashNum'] = 0;
                }
                if ($v['openid'] == $openId) {
                    $my = $info[$k];
                }
            }
            if(empty($my)){
                $my=db("users")->where("openid",$openId)->find();
            }
            return $my;
        }

}