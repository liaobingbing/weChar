<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/5/15
 * Time: 9:59
 */

namespace app\guesong\model;


use think\Model;

class User extends Model
{
    public function findByOpenid($openid){
        $user=db('users')->where("openid='{$openid}'")->find();
        return $user;
    }
}