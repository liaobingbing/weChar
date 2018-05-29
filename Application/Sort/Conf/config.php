<?php
return array(
	//'配置项'=>'配置值'

    //数据库配置信息
    'DB_TYPE'   => 'mysql', // 数据库类型

    'DB_HOST'   => '47.106.98.161', // 线上服务器地址

    'DB_NAME'   => 'kuaiyu_sort', // 数据库名

    'DB_USER'   => 'root', // 用户名

    'DB_PWD'    => 'root123', // 密码

    'DB_PORT'   => 3306, // 端口

    'DB_PREFIX' => 'sort_', // 数据库表前缀

    'DB_CHARSET'=> 'utf8', // 字符集

    'DB_DEBUG'  =>  TRUE, // 数据库调试模式 开启后可以记录SQL日志

    // 小程序APP
    'WECHAT_APPID'      =>  'wx243d834e1b24c74c',
    'WECHAT_APPSECRET'  =>  '034041a08c3d9522c4df12b8da142260',

    'LOG_RECORD' => true, // 开启日志记录
    'LOG_LEVEL'  =>'EMERG,ALERT,CRIT,ERR', // 只记录EMERG ALERT CRIT ERR 错误

);