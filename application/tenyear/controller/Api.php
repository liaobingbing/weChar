<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/6/6
 * Time: 9:17
 */

namespace app\tenyear\controller;


use common\controller\ApiLogin;
use think\Db;


class Api extends ApiLogin
{

    //获取小程序的openid
    public function get_openid()
    {
        $code = input('post.code');
        $login_data = $this->test_weixin($code);
        if (empty($login_data['400']) && $login_data['openid']) {
            $openid = $login_data['openid'];
            $session_key = $login_data['session_key'];
            $arr = array("code" => 200, "msg" => "success", "data" => array("openId" => $openid, "wx_session_key" => $session_key,"status"=>1));
            return $arr;
        }
    }

    //获取题目
    public function get_question()
    {
        $res=cache("tenyear_question");
        if(empty($question)){
            $res=Db::name("answer")->select();
            cache("tenyear_question",$res);
        }
        shuffle($res);
        $question=array_slice($res,0,7);
        $arr=resCode(200,"ok",$question);
        return $arr;

    }
    /**
     * 小程序formid写入
     * @param form_id 小程序formid
     * @param open_id 微信openid
     */
    //不知道为什么没有同部
    /*public function addXcxFormId() {
        $form_id = input('form_id');
        $open_id = input('open_id');

        if (empty($form_id) || empty($open_id) || $form_id == 'the formId is a mock one' || $form_id == 'undefined') {
            $arr=resCode(200,"SUCCESS");
            return $arr;
        }
        if (db('xcx_formid')->insert(array(
            'form_id' => $form_id,
            'open_id' => $open_id,
            'add_time' => time()
        ))) {
            $arr=resCode(200,"SUCCESS");
        }else{
            $arr=resCode(400,"网络错误");
        }
        return $arr;
    }*/
    public function addXcxFormId()
    {
        $form_id = input('form_id');
        $open_id = input('open_id');

        if (empty($form_id) || empty($open_id) || $form_id == 'the formId is a mock one' || $form_id == 'undefined') {
            $arr=resCode(200,"SUCCESS");
            return $arr;
        }
        $arr=['form_id' => $form_id,
            'open_id' => $open_id,
            'add_time' => time()
        ];
        $data=cache("tenyear_formid");
        if(empty($data)){
            $data[]=$arr;
            cache("tenyear_formid",$data);
        }else if(count($data)<5000){
            array_push($data,$arr);
            cache("tenyear_formid",$data);
        }else{
            Db::name('xcx_formid')->insertAll($data);
            cache("tenyear_formid",null);
        }
    }

    public function cache_formid()
    {
        $data=cache("tenyear_formid");
        if(!empty($data)){
            Db::name('xcx_formid')->insertAll($data);
            cache("tenyear_formid",null);
        }
    }

    //查询缓存的长度
    public function cache_long(){
        $data=cache("tenyear_formid");
        return count($data);
    }
}