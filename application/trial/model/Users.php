<?php
/**
 * Created by sub.
 * User: wei
 * Date: 2018/7/10
 * Time: 14:10
 */

namespace app\trial\model;


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

}