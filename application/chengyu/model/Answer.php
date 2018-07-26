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

class Answer extends Model
{
    public function get_all_answer(){
        $data=cache('idiom_all');
        if(!$data){
            $level_arr=Db::name('level')->where('status=1')->order('level')->select();
            if($level_arr){
                foreach($level_arr as $kk=>$vv){
                    $layer_arr=Db::name('answer')->where('status=1 and level='.$vv["id"])->order('layer')->select();
                    foreach($layer_arr as $k=>$v){
                        $data[$vv['level']][$v['layer']]['id']=$v['id'];
                        $data[$vv['level']][$v['layer']]['answer']=$v['answer'];
                        $data[$vv['level']][$v['layer']]['img_url']=$v['img_url'];
                        $data[$vv['level']][$v['layer']]['interfere_answer']=$v['interfere_answer'];
                        $data[$vv['level']][$v['layer']]['level']=$vv['level'];
                        $data[$vv['level']][$v['layer']]['level_name']=$vv['level_name'];
                        $data[$vv['level']][$v['layer']]['layer']=$v['layer'];
                        $data[$vv['level']][$v['layer']]['explain']=$v['explain'];
                        $data[$vv['level']][$v['layer']]['provenance']=$v['provenance'];
                    }
                }
            }
            if($data){
                cache('idiom_all',$data,7200);
            }
        }
        return $data;
    }

    public function answer_success($uid,$level,$layer){
        $userdao=new Users();
        $user_game=$userdao->findGame($uid);
        if($user_game){
            if($level&&$layer){
                $user_game['gold_num']+=config('SUCCESS_GOLD');
                $user_game['idiom_num']+=1;
                $user_game['level']=$level;
                $user_game['layer']=$layer;
                $medal_info=$this->check_medal($user_game['idiom_num'],$user_game['medal_num']);
                if($medal_info['code']==200){
                    $user_game['medal_num']=$medal_info['medal_num'];
                    $data['up_medal']=1;
                    $data['medal']=$medal_info['idiom_num'];
                }else{
                    $data['up_medal']=0;
                    $data['medal']=0;
                }
                $info=Db::name('user_game')->update($user_game);
                if($info){
                   /* $data2['uid']=$user_game['uid'];
                    $data2['gold_change']=config('SUCCESS_GOLD');
                    $data2['change_type']='答题成功';
                    $data2['add_time']=time();
                    Db::name('user_income')->insert($data2);*/
                    $data['code']=200;
                    $data['add_gold_num']=config('SUCCESS_GOLD');
                }else{
                    $data['code']=400;
                }
            }else{
                $data['code']=400;
            }

        }else{
            $data['code']=400;
        }
        return $data;

    }

    public function check_medal($idiom_num,$medal_num){
        $medal_arr=$this->get_all_medal();
        $medal_num+=1;
        $data['code']=400;
        foreach($medal_arr as $k=>$v){
            if($medal_num==$v['medal']){
                if($idiom_num<$v['idiom_num']){
                    $data['code']=400;
                }else{
                    $data['code']=200;
                    $data['medal_num']=$v['medal'];
                    $data['idiom_num']=$v['idiom_num'];
                }
                break;
            }
        }
        return $data;
    }

    public function prompt($uid){
        $userdao=new Users();
        $user_game=$userdao->findGame($uid);
        $user_game['gold_num']-=config('PROMPT_GOLD');
        $info=Db::name('user_game')->update($user_game);
        if($info){
            /*$data2['uid']=$user_game['uid'];
            $data2['gold_change']=0-config('PROMPT_GOLD');
            $data2['change_type']='提示答案';
            $data2['add_time']=time();
            Db::name('user_income')->insert($data2);*/
            $data['code']=200;
            $data['gold_num']=$user_game['gold_num'];
        }else{
            $data['code']=400;
        }
        return $data;
    }

    public function get_all_level(){
        $level_arr=cache('all_level');
        if(!$level_arr){
            $level_arr=Db::name('level')->where('status=1')->field('id as level_id,level,level_name')->order('level')->select();
            if($level_arr){
                cache('all_level',$level_arr,86400);
            }
        }
        return $level_arr;
    }

    public function get_all_layer($level_id){

       // $layer_arr=db('answer as a')->join('LEFT JOIN cy_level as l on a.level=l.id')->where('a.status=1 and a.level=$level_id')->field('l.level,a.layer')->order('a.layer')->fetchSql(true)->select();
        $layer_arr=Db::name("answer")->alias("a")->join("cy_level b",'a.level=b.id')->where("a.status=1 and a.level=$level_id")->field('b.level,a.layer')->order('a.layer')->fetchSql(false)->select();
        //echo $layer_arr;die;
        return $layer_arr;
    }

    public function get_one_friend($user_id){
        $friend_arr1=Db::name('user_friend')->where('uid',$user_id)->select();
        $where_arr=array($user_id);
        foreach($friend_arr1 as $k=>$v){
            $where_arr[]=$v['recommend_user_id'];
        }
        $where['uid'] = array('in',$where_arr);
        $friend_arr2=Db::name('user_game')->where($where)->field('uid,avatarUrl,nickname,gold_num,idiom_num')->order('idiom_num desc')->select();

        if($friend_arr2){
            foreach($friend_arr2 as $k=>$v){
                if($v['uid']==$user_id){
                    $friend_detail['my_ranking']=$k+1;
                    $friend_detail['my_idiom']=$v['idiom_num'];
                }
                $friend_arr2[$k]['ranking']=$k+1;
            }
            $friend_detail['data']=$friend_arr2;

        }

        return $friend_detail;

    }

    public function get_world_ranking($user_id){
        $ranking_detail['data']=cache('cy_world_ranking');
            $sql="SELECT * FROM (SELECT (@rownum:=@rownum+1) AS ranking, a.* FROM `cy_user_game` a, (SELECT @rownum:= 0 ) r  ORDER BY a.`idiom_num` DESC ) AS b  WHERE uid = ".$user_id;
            $user=db()->query($sql);
        $ranking_detail['my_ranking']=$user[0]['ranking'];
        return $ranking_detail;
    }

    public function get_all_medal(){
        $medal_arr=cache('all_medal');
        if(!$medal_arr){
            $medal_arr=Db::name('user_medal')->select();
            if($medal_arr){
                cache('all_medal',$medal_arr,86400);
            }
        }
        return $medal_arr;
    }


    //获取求组答案
    public function get_help_answer($uid,$level,$layer){
        $info=Db::name('user_help')->where("uid=$uid and level=$level and layer=$layer")->field('help_answer,user_avatarUrl')->select();
        if($info){
            $top=0;
            foreach($info as $k=>$v){
                $info[$k]['top']=$top;
                $top+=50;
                if($k%2==0){
                    $info[$k]['right']=0;
                }else{
                    $info[$k]['right']=50;
                }
            }
        }

        return $info;
    }

    //用户分享群
    public function user_share_group($uid,$gid){
        $info='';
        if($uid&&$gid){
            $userdao=new Users();
            $user_game=$userdao->findGame($uid);
            if($user_game){
                $has=Db::name('share_group')->where('uid='.$uid.' and openGId like "'.$gid.'"')->find();
                if($has){
                    if($has['share_time']<strtotime(date("Y-m-d"),time())){
                        $user_game['gold_num']+=config('SHARE_GOLD');
                        Db::name('user_game')->update($user_game);
                        /*$data['uid']=$uid;
                        $data['gold_change']=config('SHARE_GOLD');
                        $data['change_type']='分享群成功';
                        $data['add_time']=time();
                        Db::name('user_income')->insert($data);*/
                        $has['share_time']=time();
                        Db::name('share_group')->update($has);
                        $info['add_status']=1;
                        $info['add_gold_num']=config('SHARE_GOLD');
                        $info['user_gold_num']=$user_game['gold_num'];
                    }else{
                        $info['add_status']=0;
                        $info['add_gold_num']=0;
                        $info['user_gold_num']=$user_game['gold_num'];
                    }
                }else{
                    $user_game['gold_num']+=config('SHARE_GOLD');
                    Db::name('user_game')->update($user_game);
                    /*$data['uid']=$uid;
                    $data['gold_change']=config('SHARE_GOLD');
                    $data['change_type']='分享群成功';
                    $data['add_time']=time();
                    Db::name('user_income')->insert($data);*/
                    $group['uid']=$uid;
                    $group['openGId']=$gid;
                    $group['share_time']=time();
                    Db::name('share_group')->insert($group);
                    $info['add_status']=1;
                    $info['add_gold_num']=config('SHARE_GOLD');
                    $info['user_gold_num']=$user_game['gold_num'];
                }

            }

        }
        return $info;
    }
}