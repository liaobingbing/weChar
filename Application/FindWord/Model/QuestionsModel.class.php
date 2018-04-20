<?php
/**
 * Created by PhpStorm.
 * User: HZM
 * Date: 2018/4/19
 * Time: 9:56
 */

namespace FindWord\Model;


use Think\Model;

class QuestionsModel extends Model
{
    /**
     * 获取题库
     * @param int $expire 缓存时间
     * @return mixed
     */
    public function get_questions($expire = 86200){
        $key = 'find_word_questions';
        $question = S($key);

        if( !$question ){
            $question = M('Questions')->field('option_1,option_2')->select();
            S($key,$question,$expire);
        }

        return $question;
    }

    /**
     * 随机获取指定长度的题库
     * @param int $len
     * @return array
     */
    public function get_rand_questions($len=10){
        $questions = $this->get_questions();
        shuffle($questions);
        $questions = array_slice($questions,0,$len);
        $result = array();
        foreach($questions as $k => $v){
            $rand = rand(0,1);
            $result[$k] = $v;
            $key = array_keys($v);
            $result[$k]['answer'] = $key[$rand];
        }

        return $result;
    }
}