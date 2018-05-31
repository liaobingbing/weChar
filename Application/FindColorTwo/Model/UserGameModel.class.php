<?php
/**
 * Created by PhpStorm.
 * User: HZM
 * Date: 2018/4/19
 * Time: 11:07
 */

namespace FindColorTwo\Model;


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
        $chance_num = M('UserGame')->where(array('uid'=>$user_id))->getField('chance_num');

        if($chance_num > 0){
            $resutl = true;
        }else{
            $resutl = false;
        }

        return $resutl;
    }

    /**
     * 获取排名
     * @param $field '字段'
     * @param $len    '长度'
     * @param int $expire '时间'
     * @return mixed
     */
    public function get_rankings($field,$len,$expire=300){
        $rankings = M('UserGame')->field("{$field},avatar_url,nickname")->where('avatar_url is not null')->order("{$field} desc")->limit($len)->fetchSql(false)->select();
        foreach ($rankings as $k => $v){
            $rankings[$k]['ranking'] = $k + 1;
        }

        return $rankings;
    }

    /**
     * 签到操作
     * @param $user_id
     */
    public function do_sign($user_id){
        $UserGame = M('UserGame');
        $sign_time = $UserGame->where("uid=$user_id")->getField('sign_time');
        $today_0 = strtotime(date('Y-m-d',time()));

        if($sign_time < $today_0){
            $user_game['chance_num'] =5;
            $user_game['sign_time']  = time();
            $UserGame->where("uid=$user_id")->save($user_game);
        }
    }


}