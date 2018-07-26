<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/4/14
 * Time: 10:43
 */

namespace app\method\model;


use think\Model;

class Users extends  Model
{
    public function findByOpenid($openid){
        $user=db('users')->where("openid='{$openid}'")->find();
        if($user) {
            $user['name'] = unicode2emoji($user['name']);
//            $user['avatarUrl'] = str_replace('/0','/96',$user['avatarUrl'] );
        }
        return $user;
    }


    public function findGame($user_id){
        $user=db('user_game')->where("uid='{$user_id}'")->find();
        return $user;
    }
}