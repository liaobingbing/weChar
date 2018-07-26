<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/5/16
 * Time: 14:31
 */

namespace app\word\controller;

use common\controller\ApiBase;
use think\Controller;

class Api extends Controller
{
    public function get_result()
    {
        $openId=input("post.openId");
        $name=input("post.username");
        $year=input('post.year');
        $month_day=input('post.month');
        $day=input('post.day');
        $hour_time=input('post.hour');
        $hour_time=trim($hour_time);
        $sex=input("post.sex");
        if(empty($year)||empty($month_day)||empty($day)||empty($hour_time)){
            $arr=resCode(400,"参数不全",null);
            return $arr;
        }
        $bzi=db('year')->where('new_calendar',$year)->whereOr("lunar_calendar",$year)->find();//年测试 性格，财运健康
        $minl=db('day')->where('day',$day)->whereOr("new_day",$day)->find();//八字命理
        $hour=db('hour')->where('hour',$hour_time)->find();//时间 婚姻职业避凶
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
        $data=["openId"=>$openId,"result"=>json_encode($arr),"user_name"=>$name,"year"=>$year,"day"=>$day,"month"=>$month_day,"hour"=>$hour_time,"sex"=>$sex];//修改
        $id=db("game")->insertGetId($data);
        $arr=resCode(200,array("id"=>$id),$arr); //测试
        return $arr;
    }

    public function get_history()
    {
        $openId=input("post.openId");
        $sql=db("game")->where("openId",$openId)->fetchSql(true)->column('user_name,id');
        $result=db()->query($sql);
        $arr=resCode(200,"查询成功",$result);
        return $arr;
    }
//滚动
    public function describe()
    {
        $resutlt=db("test")->select();
        $arr=resCode(200,"查询成功",$resutlt);
        return $arr;
    }

    public function get_one_history(){
        $id=$openId=input("post.id");
        $result=db("game")->where("id",$id)->find();
        $result['result']=json_decode($result['result'],true);
        $arr=resCode(200,"查询成功",$result);
        return $arr;
    }
    //卡片查询
    public function  get_car()
    {
        $day=input("post.day");
        $minl=db('day')->where('day',$day)->whereOr("new_day",$day)->value('chart');//八字命理
        $arr=resCode(200,"查询成功",$minl);
        return $arr;
    }




}