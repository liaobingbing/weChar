<?php
/**
 * Created by PhpStorm.
 * User: HZM
 * Date: 2018/4/18
 * Time: 18:47
 */

namespace app\findcolortwo\model;

use think\Model;

class ShareGroup extends Model
{
    /**
     * 添加 分享群记录
     * @param $uid
     * @param $open_gid
     * @return bool
     */
    public function add_share_group($uid,$open_gid){
        $Model = db('share_group');
        $result = false;

        $share_group = $Model->where(array('uid'=>$uid,'open_gid'=>$open_gid))->find();

        if(!$share_group){
            $data = array(
                'uid'   =>  $uid,
                'open_gid' => $open_gid,
                'share_time' => time()
            );

            if($Model->insert($data)){
                $result = true;
            }
        }

        return $result;
    }

    /**
     * 更新分享群数据
     * @param $uid
     * @param $open_gid
     * @return bool
     */
    public function update_share_group($uid,$open_gid){
        $Model = db('share_group');
        $result = false;

        $share_group = $Model->where(array('uid'=>$uid,'open_gid'=>$open_gid))->find();

        $today_0 = strtotime(date('Y-m-d',time()));
        if($share_group['share_time'] < $today_0){
            $share_group['share_time'] = time();
            if($Model->update($share_group)){
                $result = true;
            }
        }

        return $result;
    }

    /**
     * 验证分享群
     * @param $uid
     * @param $open_gid
     * @return bool
     */
    public function check_share_group($uid,$open_gid){
        $result = false;
        // 是否是新群
        if($this->add_share_group($uid,$open_gid)){
            $result = true;
        }else{
            // 是否是今日已分享过的
            if($this->update_share_group($uid,$open_gid)){
                $result = true;
            }else{
                $result = false;
            }
        }

        return $result;
    }
}