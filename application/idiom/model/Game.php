<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/5/19
 * Time: 11:02
 */

namespace app\idiom\model;


use think\Model;

class Game extends Model
{
    public function get_question($layer,$openId)
    {
        /*if($layer==1){
            cache($openId."1",null);
            cache($openId."2",null);
            cache($openId."3",null);
            cache($openId."4",null);
            cache($openId."5",null);
        }die;*/
        $count1=cache("idiom_count1");
        if(empty($count1)){
            $count1=db("answer")->where("discript",1)->count();
            cache("idiom_count1",$count1);
        }
        $count2=cache("idiom_count2");
        if(empty($count2)){
            $count2=db("answer")->where("discript",2)->count();
            cache("idiom_count2",$count2);
        }
        $count3=cache("idiom_count3");
        if(empty($count3)){
            $count3=db("answer")->where("discript",3)->count();
            cache("idiom_count3",$count3);
        }
        $count4=cache("idiom_count4");
        if(empty($count4)){
            $count4=db("answer")->where("discript",4)->count();
            cache("idiom_count4",$count4);
        }
        $count5=cache("idiom_count5");
        if(empty($count5)){
            $count5=db("answer")->where("discript",5)->count();
            cache("idiom_count5",$count5);
        }
        if($layer<=$count1) {
            $question=cache("idiom_question1");
            if(empty($question)){
                $question = db("answer")->where("discript", 1)->select();
                foreach ($question as $k => $v) {
                    $num = $k + 1;
                    $question[$num] = $v;
                }
                cache("idiom_question1",$question,3600);
            }
            shuffle($question);
            return $question[$layer];

        }else if($layer<=($count1+$count2)){
            //cache("idiom_question1",null);
            $question=cache("idiom_question2");
            if(empty($question)){
                $question = db("answer")->where("discript", 2)->select();
                foreach ($question as $k => $v) {
                    $num = $k + 1;
                    $question[$num] = $v;

                }
                //print_r($question);die;
                cache("idiom_question2",$question,3600);
            }
            shuffle($question);
            return $question[$layer-$count1];

        }else if($layer<=($count1+$count2+$count3)){
            //cache("idiom_question2",null);
            $question=cache("idiom_question3");
            if(empty($question)){
                $question = db("answer")->where("discript", 3)->select();
                foreach ($question as $k => $v) {
                    $num = $k + 1;
                    $question[$num] = $v;
                }
                cache("idiom_question3",$question,3600);
            }
            shuffle($question);
            return $question[$layer-$count1-$count2];
        }else if($layer<=($count1+$count2+$count3+$count4)){
//            $question=cache("idiom_question4");
            if(empty($question)){
                $question = db("answer")->where("discript", 4)->select();
                foreach ($question as $k => $v) {
                    $num = $k + 1;
                    $question[$num] = $v;
                }
                cache("idiom_question4",$question,3600);
            }
            shuffle($question);
            return $question[$layer-$count1-$count2-$count3];
        }else if($layer<=($count1+$count2+$count3+$count4+$count5)){
           // cache("idiom_question4",null);
            $question=cache("idiom_question5");
            if(empty($question)){
                $question = db("answer")->where("discript", 5)->select();
                foreach ($question as $k => $v) {
                    $num = $k + 1;
                    $question[$num] = $v;
                }
                // print_r($question);die;
                cache("idiom_question5",$question,3600);
            }
            shuffle($question);
            return $question[$layer-$count1-$count2-$count3-$count4];
        }
    }
}