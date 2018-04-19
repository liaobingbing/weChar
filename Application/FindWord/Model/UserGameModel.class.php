<?php
/**
 * Created by PhpStorm.
 * User: HZM
 * Date: 2018/4/19
 * Time: 11:07
 */

namespace FindWord\Model;


use Think\Model;

class UserGameModel extends Model
{

    /**
     * 由 user_id 获取用户游戏数据
     * @param $user_id
     * @param null $field
     * @return mixed
     */
    public function find_by_user_id($user_id,$field=null){

        if($field){
            $user = M('UserGame')->field($field)->where(array('uid'=>$user_id))->find();

        }else{
            $user = M('user_game')->where(array('uid'=>$user_id))->find();

        }

        return $user;
    }

    /**
     * 验证挑战机会
     * @param $user_id
     * @return bool
     */
    public function check_chance_num($user_id){
        $chance_num = M('UserGame')->getField('chance_num');

        if($chance_num > 0){
            $resutl = true;
        }else{
            $resutl = false;
        }

        return $resutl;
    }
}