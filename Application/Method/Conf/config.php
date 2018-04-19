<?php
return array(
	//'配置项'=>'配置值'

    //数据库配置信息
    'DB_TYPE'   => 'mysql', // 数据库类型

    'DB_HOST'   => 'www.ky311.com', // 线上服务器地址

    'DB_NAME'   => 'kuaiyu_method', // 数据库名

    'DB_USER'   => 'root', // 用户名

    'DB_PWD'    => 'root123', // 密码

    'DB_PORT'   => 3306, // 端口

    'DB_PREFIX' => 'method_', // 数据库表前缀

    'DB_CHARSET'=> 'utf8', // 字符集

    'DB_DEBUG'  =>  TRUE, // 数据库调试模式 开启后可以记录SQL日志

    // 小程序APP
    'WECHAT_APPID'      =>  'wxb15bd36a0179edf2',
    'WECHAT_APPSECRET'  =>  'ee3adf23be67e07780a4e1794126bb0e',

    'LOG_RECORD' => true, // 开启日志记录
    'LOG_LEVEL'  =>'EMERG,ALERT,CRIT,ERR', // 只记录EMERG ALERT CRIT ERR 错误

);