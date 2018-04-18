<?php
/**
 * Created by PhpStorm.
 * User: HZM
 * Date: 2018/4/18
 * Time: 15:02
 */

namespace Admin\Model;


use Think\Model;

class AppDailyRecordModel extends Model
{
    /**
     * 对某天的数据进行记录
     * @param $app_id
     * @param $list
     * @return bool
     */
    public function update_data($app_id,$list){
        $AppDailyRecord = M('AppDailyRecord');
        $re = $AppDailyRecord->where(array('app_id'=>$app_id,'ref_date'=>$list['ref_date']))->find();
        $result = false;

        if( !$re ){
            $data = $list;
            $data['app_id'] = $app_id;
            $result = $AppDailyRecord->add($data);
        }

        return $result;
    }

    public function get_daily_record($app_id,$time='month'){

        $time = date('Ym',time());

        $AppDailyRecord = M('AppDailyRecord');

    }
}