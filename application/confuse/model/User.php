<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/5/15
 * Time: 9:59
 */

namespace app\confuse\model;

use think\Model;

class User extends Model
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
        if($user) {
            $user['nickname'] = unicode2emoji($user['nickname']);
//            $user['avatarUrl'] = str_replace('/0','/96',$user['avatarUrl'] );
        }
        return $user;
    }
}