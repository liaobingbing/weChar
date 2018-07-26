<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/5/19
 * Time: 9:47
 */

namespace app\idiom\model;


use think\Model;

class User extends Model
{
    public function findByOpenid($openid){
        $user=db('users')->where("openid='{$openid}'")->find();
        return $user;
    }

    public function getCurrentSeason(){
        $seasion=db("season")->order("season desc")->limit(1)->value("season");
        return $seasion;
    }
    public function getGame($openid,$season){
        $where=array("openid"=>$openid,"season"=>$season);
        $user=db('game')->where($where)->find();
        return $user;
    }
}