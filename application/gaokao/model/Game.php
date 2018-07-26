<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/5/24
 * Time: 10:34
 */

namespace app\gaokao\model;


use think\Model;

class Game extends Model
{
    /**
     * @param $tpye 1代表理科生 2.代表文科生
     */
    public function get_question($tpye,$layer,$openId)
    {
        //理科生题目
        if($tpye==1){
            if($layer==1){
                cache("scence".$openId,null);
            }
            $question=cache("scence".$openId);
            if(empty($question)){
                $question1=db("science")->where('type',1)->select();
                shuffle($question1);

                $arr1=array_slice($question1,0,3);
                //$type=array("语文"=>array("1/3","2/3","3/3"));
               // $arr1=array_push($arr1,$type);
               // print_r($arr1);die;
                $question2=db("science")->where('type',2)->select();
                shuffle($question2);

                $arr2=array_slice($question2,0,3);

                $question3=db("science")->where('type',3)->select();
                shuffle($question3);

                $arr3=array_slice($question3,0,3);

                $question4=db("science")->where('type',4)->select();
                shuffle($question4);

                $arr4=array_slice($question4,0,2);


                $question5=db("science")->where('type',5)->select();
                shuffle($question5);

                $arr5=array_slice($question5,0,2);

                $question6=db("science")->where('type',6)->select();
                shuffle($question6);

                $arr6=array_slice($question6,0,2);
                $arr=array_merge($arr1,$arr2,$arr3,$arr4,$arr5,$arr6);
                // print_r($arr);die;
                foreach($arr as $k=>$v){
                    $num=$k+1;
                    $question[$num]=$v;
                }
                cache("scence".$openId,$question,1800);
            }
            return  $question[$layer];
        }else{
            if($layer==1){
                cache("arts".$openId,null);
            }
            $question=cache("arts".$openId);
            if(empty($question)){
                $question1=db("arts")->where('type',1)->select();
                shuffle($question1);
                $arr1=array_slice($question1,0,3);
                //print_r($question1);die;
                $question2=db("arts")->where('type',2)->select();
                shuffle($question2);
                $arr2=array_slice($question2,0,3);
                $question3=db("arts")->where('type',3)->select();
                shuffle($question3);
                $arr3=array_slice($question3,0,3);
                $question4=db("arts")->where('type',4)->select();
                shuffle($question4);
                $arr4=array_slice($question4,0,2);
                $question5=db("arts")->where('type',5)->select();
                shuffle($question5);

                $arr5=array_slice($question5,0,2);
                $question6=db("arts")->where('type',6)->select();
                shuffle($question6);
                $arr6=array_slice($question6,0,2);
                $arr=array_merge($arr1,$arr2,$arr3,$arr4,$arr5,$arr6);
                foreach($arr as $k=>$v){
                    $num=$k+1;
                    $question[$num]=$v;
                }
                cache("arts".$openId,$question,1800);
            }
            return  $question[$layer];
        }
    }
}