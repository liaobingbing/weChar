<?php

namespace app\film\model;

use think\Db;
use Think\Model;

class Answer extends Model
{

    public function answer_success($uid, $layer)
    {
        $userdao = new User();
        $user_game = $userdao->findGame($uid);
        if ($user_game) {
            if ($layer) {
                $user_game['gold_num'] += config('SUCCESS_GOLD');
                $user_game['success_num'] += 1;
                $user_game['layer'] = $layer;
                $info = db('user_game')->update($user_game);
                if ($info) {
                    $level_arr = db('level')->where('status=1')->select();
                    $data['up_status'] = 0;
                    $data['up_layer'] = '';
                    foreach ($level_arr as $k => $v) {
                        if ($layer == $v['layer_max']) {
                            $data['up_status'] = 1;
                            $kk = $k + 1;
                            $data['up_layer'] = $level_arr[$kk]['layer_max'];
                            break;
                        }
                    }
                    $data['code'] = 200;
                    $data['add_gold_num'] = config('SUCCESS_GOLD');
                    $data['gold_num'] = $user_game['gold_num'];
                } else {
                    $data['code'] = 400;
                }
            } else {
                $data['code'] = 400;
            }

        } else {
            $data['code'] = 400;
        }
        return $data;
    }


    public function prompt($uid)
    {
        $userdao = new User();
        $user_game = $userdao->findGame($uid);
        $user_game['gold_num'] -= config('PROMPT_GOLD');
        $info = db('user_game')->update($user_game);
        if ($info) {
            $data['code'] = 200;
            $data['gold_num'] = $user_game['gold_num'];
        } else {
            $data['code'] = 400;
        }
        return $data;
    }

    public function get_all_level()
    {
        $level_arr = cache('gm_all_level');
        if (!$level_arr) {
            $level_arr = db('level')->where('status=1')->field('id as level_id,level_name,layer_min,layer_max')->order('level')->select();
            if ($level_arr) {
                cache('gm_all_level', $level_arr);
            }
        }
        return $level_arr;
    }

    public function get_all_layer()
    {
        $layer_arr = cache('gm_all_layer',null);
        if (!$layer_arr) {
            $layer_arr = db('answer')->where('status=1')->field('layer')->order('layer')->select();
            if ($layer_arr) {
                $a = array(0);
                $layer_arr = array_merge($a, $layer_arr);

                cache('gm_all_layer', $layer_arr);
            }
        }
        return $layer_arr;
    }

    public function get_one_friend($user_id)
    {
        /*$friend_detail=session('friend_detail');
        if(!$friend_detail){*/
        $friend_arr1 = db('user_friend')->where('uid', $user_id)->select();
        $where_arr = array($user_id);
        foreach ($friend_arr1 as $k => $v) {
            $where_arr[] = $v['recommend_user_id'];
        }
        $where['uid'] = array('in', $where_arr);
        $friend_arr2 = db('user_game')->where($where)->field('uid,avatarUrl,nickname,gold_num,success_num')->order('success_num desc')->select();

        if ($friend_arr2) {
            foreach ($friend_arr2 as $k => $v) {
                if ($v['uid'] == $user_id) {
                    $friend_detail['my_ranking'] = $k + 1;
                    $friend_detail['my_success_num'] = $v['success_num'];
                }
                $friend_arr2[$k]['ranking'] = $k + 1;
            }
            $friend_detail['data'] = $friend_arr2;
            //session('friend_detail',$friend_detail);
        }
        /*}*/
        return $friend_detail;

    }

    public function get_world_ranking($user_id)
    {
        $gm_world_ranking = cache('gm_world_ranking');
        if (!is_array($gm_world_ranking) || empty($gm_world_ranking)) {
            $world_arr = array();
            $ranking_arr = db('user_game')->field('uid,avatarUrl,nickname,gold_num,success_num')->where("avatarUrl is not null")->order('success_num desc')->select();
            foreach ($ranking_arr as $k => $v) {
                $ranking = $k + 1;
                $world_arr[$ranking] = $v;
                $world_arr[$ranking]['ranking'] = $ranking;
            }
            cache('gm_world_ranking', $world_arr);
            $gm_world_ranking = $world_arr;
        }
        $ranking_detail['data'] = !empty($gm_world_ranking) ? $gm_world_ranking : [];
        $sql = "SELECT * FROM (SELECT (@rownum:=@rownum+1) AS ranking, a.* FROM `gm_user_game` a, (SELECT @rownum:= 0 ) r  ORDER BY a.`success_num` DESC ) AS b  WHERE uid = " . $user_id;
        $user = Db::query($sql);
        $ranking_detail['my_ranking'] = $user[0]['ranking'];
        return $ranking_detail;
    }


    //获取求组答案
    public function get_help_answer($uid, $layer)
    {
        $info = db('user_help')->where("uid='{$uid}' and layer='{$layer}'")->field('help_answer,user_avatarUrl')->select();
        if ($info) {
            $top = 0;
            foreach ($info as $k => $v) {
                $info[$k]['top'] = $top;
                $top += 50;
                if ($k % 2 == 0) {
                    $info[$k]['right'] = 0;
                } else {
                    $info[$k]['right'] = 50;
                }
            }
        }
        return $info;
    }

    //用户分享群
    public function user_share_group($uid, $gid)
    {
        $info = '';
        if ($uid && $gid) {
            $userdao = new User();
            $user_game = $userdao->findGame($uid);
            if ($user_game) {
                $has = db('share_group')->where('uid=' . $uid . ' and openGid like "' . $gid . '"')->find();
                if ($has) {
                    if ($has['share_time'] < strtotime(date("Y-m-d"), time())) {
                        $user_game['gold_num'] += config('SHARE_Group_GOLD');
                        db('user_game')->update($user_game);
                        $has['share_time'] = time();
                        db('share_group')->update($has);
                        $info['add_status'] = 1;
                        $info['add_gold_num'] = config('SHARE_Group_GOLD');
                        $info['user_gold_num'] = $user_game['gold_num'];
                    } else {
                        $info['add_status'] = 0;
                        $info['add_gold_num'] = 0;
                        $info['user_gold_num'] = $user_game['gold_num'];
                    }
                } else {
                    $user_game['gold_num'] += config('SHARE_Group_GOLD');
                    db('user_game')->update($user_game);
                    $group['uid'] = $uid;
                    $group['openGid'] = $gid;
                    $group['share_time'] = time();
                    db('share_group')->insert($group);
                    $info['add_status'] = 1;
                    $info['add_gold_num'] = config('SHARE_Group_GOLD');
                    $info['user_gold_num'] = $user_game['gold_num'];
                }

            }
        }
        return $info;
    }
}
