<?php
/**
 * Created by PhpStorm.
 * User: HZM
 * Date: 2018/4/18
 * Time: 14:39
 */

namespace Admin\Model;


use Think\Model;

class AppStatisticsModel extends Model
{
    /**
     * 更新统计总数据
     * @param $app_id
     * @param $list
     * @return bool
     */
    public function update_data($app_id,$list){
        $AppStatistics = M('AppStatistics');
        $re = $AppStatistics->where(array('app_id'=>$app_id))->find();
        $result = false;
        if( !$re ){
            $data = $list;
            $data['app_id'] = $app_id;
            $result = $AppStatistics->add($data);
        }else{
            if( $list['ref_date'] > $re['ref_date'] ){
                $re['visit_total'] = $list['vist_total'];
                $re['share_pv'] += $list['share_pv'];
                $re['share_uv'] += $list['share_uv'];
                $re['ref_date'] =  $list['ref_date'];
                $result = $AppStatistics->save($re);
            }
        }
        return $result;
    }
}