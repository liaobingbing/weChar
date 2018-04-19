<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/4/14
 * Time: 10:43
 */

namespace Sort\Model;


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
}