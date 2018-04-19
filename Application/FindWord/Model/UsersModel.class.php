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


    /**
     * 添加新用户
     * @param $data
     * @return mixed
     */
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

    /**
     * 更新用户数据
     * @param $data
     * @return mixed
     */
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

    /**
     * 根据openid 获取用户信息
     * @param $openid
     * @param null $field
     * @return mixed
     */
    public function find_by_openid($openid,$field=null){

        if($field){
            $user = M('Users')->field($field)->where(array('openid'=>$openid))->find();

        }else{
            $user = M('Users')->where(array('openid'=>$openid))->find();

        }

        return $user;
    }
}