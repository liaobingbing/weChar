<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/4/14
 * Time: 10:43
 */

namespace Confuse\Model;


use Think\Model;

class UsersModel extends  Model
{
    public function findByOpenid($openid){
        $user=M('users')->where("openid='{$openid}'")->find();
        if($user) {
            $user['name'] = unicode2emoji($user['name']);
//            $user['avatarUrl'] = str_replace('/0','/96',$user['avatarUrl'] );
        }
        return $user;
    }

    public function findGame($user_id){
        $user=M('user_game')->where("uid='{$user_id}'")->find();
        if($user) {
            $user['nickname'] = unicode2emoji($user['nickname']);
//            $user['avatarUrl'] = str_replace('/0','/96',$user['avatarUrl'] );
        }
        return $user;
    }
}