<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/6/29
 * Time: 9:09
 */

namespace app\guessong\model;

use think\Db;
use think\Model;

class GsUsers extends Model
{
    /**
     * 获取用户常用信息
     * @param $user_id
     * @return mixed
     */
    public function get_user_info($user_id){
        $user_info = Db::name('users')->where(array('id'=>$user_id))->field('openid,id,name,avatarUrl,fraction,layer')->find();
        return $user_info;
    }

    public function get_user_id($openId)
    {
        $id = Db::name('users')->where('openid',$openId)->value("id");
        return $id;
    }

    /**
     * 获取世界排行
     * @param $user_id
     * @return mixed
     */
    public function get_world_ranking($user_id){
        $key = 'gs_world_rankings';
        $ranking_detail['data']=cache($key);
        if(!$ranking_detail['data']){
            $ranking_arr=Db::name('users')->field('id,avatarUrl,name,fraction,layer')->whereNotNull("avatarUrl")->order('layer desc')->limit(100)->select();
            if($ranking_arr){
                foreach($ranking_arr as $k=>$v){
                    $ranking_arr[$k]['ranking']=$k+1;
                }
                $ranking_detail['data']=$ranking_arr;
                cache($key,$ranking_arr,300);
            }
        }
        $user =Db::name('users')->field('name,layer,fraction')->find($user_id);
        $ranking_detail['my_ranking']   =   '未上榜';
        $ranking_detail['my_layer']     =   $user['layer'];
        $ranking_detail['my_fraction']  =   $user['fraction'];
        $ranking_detail['my_name']      =   $user['name'];

        foreach($ranking_detail['data'] as $kk=>$vv){
            if($vv['id']==$user_id){
                $ranking_detail['my_ranking']=$kk+1;
                $ranking_detail['my_layer']=$vv['layer'];
                $ranking_detail['my_fraction']=$vv['fraction'];
                $ranking_detail['my_name']=$vv['name'];
            }
        }


        return $ranking_detail;
    }

    /**
     * 获取好友排行
     */
    public function get_friend_rankings($user_id){

        $friends =Db::name('friend')->where("uid = $user_id OR recommend_user_id = $user_id")->select();

        // 所有好友与自己的 id
        $fridends_id[] = $user_id;
        foreach($friends as $k => $v){
            if($v['uid'] == $user_id){
                $fridends_id[] = $v['recommend_user_id'];
            }else{
                $fridends_id[] = $v['uid'];
            }

        }

        $users_info = Db::name('users')->where(array('id'=>array('in',$fridends_id)))->field('id,avatarUrl,name,fraction,layer')->order('layer desc')->select();

        if($users_info) {
            foreach($users_info as $k=>$v){
                $users_info[$k]['ranking']=$k+1;
            }
            $ranking_detail['data']=$users_info;
        }

        foreach($ranking_detail['data'] as $kk=>$vv){
            if($vv['id']==$user_id){
                $ranking_detail['my_ranking']=$kk+1;
                $ranking_detail['my_layer']=$vv['layer'];
                $ranking_detail['my_fraction']=$vv['fraction'];
                $ranking_detail['my_name']=$vv['name'];

            }
        }

        return $ranking_detail;
    }

    /**
     * 分享群
     */
    public function user_share_group($uid,$gid){
        $info = '';

        if($uid && $gid){
            $user = $this->get_user_info($uid);
            if($user){
                $has=DB::name('share_group')->where('uid='.$uid.' and openGId like "'.$gid.'"')->find();
                if($has){
                    if($has['share_time']<strtotime(date("Y-m-d"),time())){
                        $user['fraction']+=config('SHARE_FRACTION');
                        Db::name('users')->update($user);

                        $has['share_time']=time();
                        Db::name('share_group')->update($has);
                        $info['add_status']=1;
                        $info['add_fraction']=config('SHARE_FRACTION');
                        $info['fraction']=$user['fraction'];
                    }else{
                        $info['add_status']=0;
                        $info['add_fraction']=0;
                        $info['fraction']=$user['fraction'];
                    }
                }else{
                    $user['fraction']+=config('SHARE_FRACTION');
                    DB::name('users')->where('id',$uid)->update($user);

                    $group['uid']=$uid;
                    $group['openGId']=$gid;
                    $group['share_time']=time();
                    Db::name('share_group')->insert($group);
                    $info['add_status']=1;
                    $info['add_fraction']=config('SHARE_FRACTION');
                    $info['fraction']=$user['fraction'];
                }

            }

        }

        return $info;

    }
}