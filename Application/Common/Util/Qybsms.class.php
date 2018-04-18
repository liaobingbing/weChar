<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/15
 * Time: 18:22
 */

namespace Common\Util;

/**
php 环境须装有curl模块和json模块才能正常使用该程序, 本页面使用utf-8编码，短信内容为utf-8编码
 **/
class Qybsms{
    var $username = "dichan"; //在这里配置你们的发送帐号
    var $passwd = "123456";    //在这里配置你们的发送密

    /**
     **发送短信函数
     **$phone 为手机号码，可以一个或多个号码，多个号码以“,”号隔开 例：13800000000,13800000000
     **$msg 为短信内容，请发正规的验证码或行业通知类的内容和带签名,测试用例请发："你好，你的验证码为：666888.【微软科技】"
     **$sendtime 为发送时间，为空立即发送，时间格式为："2016-12-12 12:12:12"
     **$port 为端口号，默认为空
     **$needstatus 是否需要状态回推,值为true或false,回推地址在后台设置
     **所有线上公开促发短信发送的接入，比如公开的验证码、注册等必须加以判断每个号码的发送限制、ip发送短信限制
     **/
    function sendMessage($phone,$msg,$sendtime='',$port='',$needstatus=''){
        $ch = curl_init();
        $post_data = "username=".$this->username."&passwd=".$this->passwd."&phone=".$phone."&msg=".urlencode($msg)."&needstatus=true&port=".$port."&sendtime=".$sendtime;
        /**
        php5.4或php6 curl版本的curl数据格式为数组   你们接入时要注意
        $post_data = array(
        "username"="账号",
        "passwd"="密码",
        "mobile"="手机号码1,号码2,号码3".
        "msg"="您好,你的验证码:8888【企业宝】",
        "needstatus"="true",
        "port"='',
        "sendtime"=''
        );
         **/
        curl_setopt ($ch, CURLOPT_URL,"http://www.qybor.com:8500/shortMessage");
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT,30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $file_contents = curl_exec($ch);
        curl_close($ch);
        return json_decode($file_contents);
    }
}


?>