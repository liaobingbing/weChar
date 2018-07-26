<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/5/19
 * Time: 9:47
 */

namespace app\fortune\model;


use think\Model;

class User extends Model
{
    public function get_result($data = array())
    {
        $wechat_id=$data['wechat_id'];
        $name=$data['user_name'];
        $year=$data['year'];
        $month_day=$data['month'];
        $day=$data['day'];
        $hour_time=$data['hour'];
        $birthhourText=$data['birthhourText'];
        // 默认子时
        if($hour_time == 0){
            $hour_time=1;
        }
        // $hour_time=trim($hour_time);
        $sex=$data['sex'];
        if(empty($year)||empty($month_day)||empty($day)||empty($hour_time)){
            $arr=resCode(400,"参数不全",null);
            return $arr;
        }
        $bzi=db('year')->where('new_calendar',$year)->whereOr("lunar_calendar",$year)->find();//年测试 性格，财运健康
        $minl=db('day')->where('day',$day)->whereOr("new_day",$day)->find();//八字命理
        // $hour=db('hour')->where('hour',$hour_time)->find();//时间 婚姻职业避凶
        $hour=db('hour')->where('id',$hour_time)->find();//时间 婚姻职业避凶
        $month=db('month')->where("month",$month_day)->whereOr("new_month",$month_day)->find();//月事业成就
        $arr=[array("type"=>"1、八字命盘","score"=>get_rand(),"content"=>$minl['chart']),
            array("type"=>"2、事业成就","score"=>get_rand(),"content"=>$month['achievement']),
            array("type"=>"3、婚姻家庭","score"=>get_rand(),"content"=>$hour['marriage']),
            array("type"=>"4、适合职业","score"=>get_rand(),"content"=>$hour['occupation']),
            array("type"=>"5、避凶之年","score"=>get_rand(),"content"=>$hour['avoid']),
            array("type"=>"6、性格分析","score"=>get_rand(),"content"=>$bzi['features']),
            array("type"=>"7、财运荣富","score"=>get_rand(),"content"=>$bzi['wealth']),
            array("type"=>"8、健康养生","score"=>get_rand(),"content"=>$bzi['health']),
        ];
        $data=["wechat_id"=>$wechat_id,"result"=>json_encode($arr),"user_name"=>$name,"year"=>$year,"day"=>$day,"month"=>$month_day,"hour"=>$birthhourText,"sex"=>$sex];//修改
        $id=db("game")->insertGetId($data);
        $arr=resCode(200,array("id"=>$id),$arr); //测试
        return $arr;
    }
}