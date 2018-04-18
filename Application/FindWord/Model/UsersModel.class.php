<?php
/**
 * Created by PhpStorm.
 * User: HZM
 * Date: 2018/4/18
 * Time: 18:47
 */

namespace FindWord\Model;


use Think\Model;

class UsersModel extends Model
{


    public function do_login($data){

    }

    public function add_user($data){
        $user['openid'] = $data['openId'];
        $user['unionid'] = $data['unionId'];
        $user['gender'] = $data['gender'];
        $user['city'] = $data['city'];
        $user['update_time'] = time();
        $user['province'] = $data['province'];
        $user['country'] = $data['country'];
        $user['avatar_url'] =  str_replace('/0','/132',$data['avatarUrl'] );
        $user['name'] = $data['nickName'];

        $uid = M('Users')->data($user)->add();

        $user_game['uid']=$uid;
        $user_game['nickname']=$data['nickName'];
        $user_game['login_time'] = time();
        $user_game['avatar_url']=str_replace('/0','/132',$data['avatarUrl'] );
        M('UserGame')->add($user_game);

        return $user;
    }

    public function update_user($data){
        $user = $this->find_by_openid($data['openId']);
        $user['gender'] = $data['gender'];
        $user['city'] = $data['city'];
        $user['update_time'] = time();
        $user['province'] = $data['province'];
        $user['country'] = $data['country'];
        $user['avatar_url'] =  str_replace('/0','/132',$data['avatarUrl'] );
        $user['name'] = $data['nickName'];

        $uid = M('Users')->save($user);

        $user_game['nickname']=$data['nickName'];
        $user_game['avatar_url']=str_replace('/0','/132',$data['avatarUrl'] );
        M('UserGame')->where(array('uid'=>$uid))->save($user_game);

        return $user;
    }

    public function find_by_openid($openid){
        $user = M('Users')->where(array('openid'=>$openid))->find();

        return $user;
    }
}