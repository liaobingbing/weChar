<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/6/29
 * Time: 11:30
 */

namespace app\chengyu\model;

use think\Db;
use think\Model;

class Users extends Model
{
    public function findByOpenid($openid){
        $user=Db::name('users')->where("openid='{$openid}'")->find();
        return $user;
    }
    public function findByuid($uid){

        $user=Db::name('users')->where("id",$uid)->find();
        return $user;
    }
    public function get_user_id($openId)
    {
        $id = Db::name('users')->where('openid',$openId)->value("id");
        return $id;
    }
    public function findGame($uid){

        $user=Db::name('user_game')->where("uid",$uid)->find();
        return $user;
    }

    public function share_gold($uid){
        $user_game=$this->findGame($uid);
        if($user_game){
            if($user_game['share_num']<10){
                $user_game['share_num']+=1;
                $user_game['gold_num']+=config('SHARE_GOLD');
                Db::name('user_game')->update($user_game);
               /* $data2['uid']=$uid;
                $data2['gold_change']=config('SHARE_GOLD');
                $data2['change_type']='分享成功';
                $data2['add_time']=time();
                Db::name('user_income')->insert($data2);*/
                $data['code']=200;
                $data['gold_num']=$user_game['gold_num'];
                $data['add_gold_num']=config('SHARE_GOLD');
            }else{
                $data['code']=400;
            }
            return $data;
        }else{
            return false;
        }
    }

}