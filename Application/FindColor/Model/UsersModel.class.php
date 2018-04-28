<?php
/**
 * Created by PhpStorm.
 * User: HZM
 * Date: 2018/4/18
 * Time: 18:47
 */

namespace FindColor\Model;


use Think\Model;

class UsersModel extends Model
{


    /**
     * 添加新用户
     * @param $data
     * @return mixed
     */
    public function add_user($data){
        $user['openid']  = $data['openId'];
        $user['unionid'] = $data['unionId'];
        $user['gender']  = $data['gender'];
        $user['city']    = $data['city'];
        $user['update_time'] = time();
        $user['province'] = $data['province'];
        $user['country']  = $data['country'];
        $user['avatar_url'] =  str_replace('/0','/132',$data['avatarUrl'] );
        $user['nickname']   = $data['nickName'];

        $result = false;

        $uid = M('Users')->data($user)->add();

       if($uid){
           $user_game['uid']    = $uid;
           $user_game['nickname'] = $data['nickName'];
           $user_game['login_time'] = time();
           $user_game['avatar_url'] =str_replace('/0','/132',$data['avatarUrl'] );
           M('UserGame')->add($user_game);
           $result = $user;
       }

        return $result;
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
        $user['nickname'] = $data['nickName'];

        $result = false;

        $uid = M('Users')->save($user);

        if($uid){
            $user_game['nickname']     =   $data['nickName'];
            $user_game['avatar_url']   =   str_replace('/0','/132',$data['avatarUrl'] );
            M('UserGame')->where(array('uid'=>$uid))->save($user_game);
            $result = $user;
        }

        return $result;
    }

    /**
     * 登录操作 将用户数据保存到数据库 保存到session 并返回session_id
     * @param $data
     * @return string
     */
    public function do_login($data){

        $user = $this->find_by_openid($data['openId']);
        $result = false;

        if($user){
            if ($user['status'] != 0){
                $re = $this->update_user($data);

                if($re){
                    session(null);
                    session(array('name'=>'session_id','expire'=>3600));
                    session('user_id',$user['id']);
                    session('openid',$user['openid']);
                    session('session_key',$data['session_key']);
                    $session_id = session_id();

                    $result['session_id']  = $session_id;
                    $result['session_key'] = $data['session_key'];
                    $result['nickname'] = $user['nickname'];
                    $result['avatar_url'] = $user['avatar_url'];
                }

            }else{
                $result = false;
            }

        }else{
            $re = $this->add_user($data);
            if($re){
                session(null);
                session(array('name'=>'session_id','expire'=>3600));
                session('user_id',$re['id']);
                session('openid',$re['openid']);
                session('session_key',$data['session_key']);
                $session_id = session_id();

                $result['session_id']  = $session_id;
                $result['session_key'] = $data['session_key'];
                $result['nickname']    = $user['nickname'];
                $result['avatar_url']  = $user['avatar_url'];
            }
        }

        return $result;
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

    /**
     * 根据 user_id 获取用户信息
     * @param $user_id
     * @param null $field
     * @return mixed
     */
    public function find_by_user_id($user_id,$field=null){

        if($field){
            $user = M('Users')->field($field)->where(array('id'=>$user_id))->find();
        }else{
            $user = M('Users')->where(array('id'=>$user_id))->find();
        }

        return $user;
    }


}