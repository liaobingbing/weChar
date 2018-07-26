<?php
/**
 * Created by sub.
 * User: wei
 * Date: 2018/7/10
 * Time: 14:10
 */

namespace app\trial\model;


use think\Model;

class Check extends Model
{
	/**
     * 检查用户是否需要重新登录
     * @param user_id 用户id/微信唯一识别号
     */
    public function needLogin($user_id) {
        $user_id = intval($user_id);
        if(!$user_id){
            return array('code'=>401,'msg'=>'user_id不能为空');
        }
        if (!$user = db('users')->where(array('id' => $user_id))->find()) {
            return array('code'=>401,'msg'=>'用户不存在');
        }
        // db('users')->where(array('id' => $user_id))->update(array('login_time' => time())); //更新最后登录时间
    }

}