<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/5/15
 * Time: 9:59
 */

namespace app\box\model;


use think\Model;

class User extends Model
{
    public function findByOpenid($openid){
        $user=db('users')->where("openid='{$openid}'")->find();
        if($user) {
            $user['nickname'] = unicode2emoji($user['nickname']);
//            $user['avatarUrl'] = str_replace('/0','/96',$user['avatarUrl'] );
        }
        return $user;
    }
}