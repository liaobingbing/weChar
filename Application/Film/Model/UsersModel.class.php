<?php
namespace Film\Model;
use \Think\Model;
class UsersModel extends Model
{
    private $cache_key='wx_apps_user_';
    public function findByOpenid($openid){
        $user=M('users')->where("openid='{$openid}'")->find();
        if($user) {
            $user['nickname'] = unicode2emoji($user['nickname']);
//            $user['avatarUrl'] = str_replace('/0','/96',$user['avatarUrl'] );
        }
        return $user;
    }
    public function findByuid($uid){

        $user=M('users')->where("id=%d",$uid)->find();
        if($user) {
            $user['nickname'] = unicode2emoji($user['nickname']);
        }

        return $user;
    }

    public function findGame($uid){

        $user=M('user_game')->where("uid=%d",$uid)->find();
        if($user) {
            $user['nickname'] = unicode2emoji($user['nickname']);
        }

        return $user;
    }

    public function share_gold($uid){
        $user_game=$this->findGame($uid);
        if($user_game){
            if($user_game['share_num']<10){
                $user_game['share_num']+=1;
                $user_game['gold_num']+=C('SHARE_GOLD');
                M('user_game')->save($user_game);
                $data['code']=200;
                $data['gold_num']=$user_game['gold_num'];
                $data['add_gold_num']=C('SHARE_GOLD');
            }else{
                $data['code']=400;
            }
            return $data;
        }else{
            return false;
        }
    }

}