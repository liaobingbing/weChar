<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/5/15
 * Time: 14:15
 */

namespace app\armyhand\model;


use think\Model;

class Game extends Model
{
    public function get_question()
    {

        $question =cache("army_hand_question");
        if (empty($question))
        {
            $question= db("answer")->select();
            foreach ($question as $k => $v) {
                $num = $k + 1;
                $question[$num] = $v;
            }
            array_shift($question);
            //print_r($question);die;
            cache("army_hand_question", $question, 7200);
        }
        shuffle($question);
        $question = array_slice($question, 0, 5);
        //print_r($question) ;die;
        return $question;
    }
}