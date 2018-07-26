<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/6/26
 * Time: 14:12
 */

namespace app\sort\model;


use think\Model;

class Users extends Model
{
	public function findByOpenid($openid){
        $user=db('users')->where("openid='{$openid}'")->find();
        if($user['nickname']) {
            $user['nickname'] = unicode2emoji($user['nickname']);   
//            $user['avatarUrl'] = str_replace('/0','/96',$user['avatarUrl'] );
        }
        return $user;
    }
    public function findByuid($uid){
        $user=db('users')->where("id",$uid)->find();
        if($user['nickname']) {
            $user['nickname'] = unicode2emoji($user['nickname']);
        }

        return $user;
    }

    public function findGame($uid){
        $user=db('user_game')->where("uid",$uid)->find();
        if($user['nickname']) {
            $user['nickname'] = unicode2emoji($user['nickname']);
        }

        return $user;
    }
}