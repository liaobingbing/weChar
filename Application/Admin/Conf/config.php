<?php
return array(

    //session前缀
    'SESSION_PREFIX'        =>  's_admin_',

    'TMPL_PARSE_STRING'     =>array(
        //,自定义路径常量，用于样式加载
        '__ADMIN__'             =>  __ROOT__.'/Public/Admin',
    ),

    // 七牛 AK 与 SK
    'QI_NIU_ACCESS_KEY'     =>  'a4L1s02Tf4adQYm9-VZtlqrX0gR8ugAV8F_d2Fd9',
    'QI_NIU_SECRET_KEY'     =>  'FWcEZVOnlvdm4k_Vmmj2L1veHVXECt6dhsekGNc0',
    'QI_NIU_URL_PREFIX'     =>  'http://img.ky121.com',    // 七牛云文件前缀

    //数据库配置信息
    'DB_TYPE'   => 'mysql', // 数据库类型
    'DB_HOST'   => 'www.ky311.com', // 线上服务器地址
    'DB_NAME'   => 'kuaiyu_xcx', // 数据库名
    'DB_USER'   => 'root', // 用户名
    'DB_PWD'    => 'root123', // 密码
    'DB_PORT'   => 3306, // 端口
    'DB_PREFIX' => '', // 数据库表前缀
    'DB_CHARSET'=> 'utf8', // 字符集
    'DB_DEBUG'  =>  TRUE, // 数据库调试模式 开启后可以记录SQL日志


    'LOG_RECORD' => true, // 开启日志记录
    'LOG_LEVEL'  =>'EMERG,ALERT,CRIT,ERR', // 只记录EMERG ALERT CRIT ERR 错误


    // 猜歌 appid 与 appsecret
    'GUESS_SONG_WECHAT_APPID'      =>  'wx2c35140111330a22',
    'GUESS_SONG_WECHAT_APPSECRET'  =>  '8b4bb7291ccc2a19f302b604dcec7482',


);


