<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
return [
    //配置
    'WECHAT_APPID'      =>  '',
    'WECHAT_APPSECRET'  =>  '',
    'template'               => [    // 模板引擎类型 支持 php think 支持扩展
	   'type'         => 'Think',    // 模板路径
	   'view_path'    => '',    // 模板后缀
	   'view_suffix'  => 'html',    // 模板文件名分隔符
	   'view_depr'    => DS,    // 模板引擎普通标签开始标记
	   'tpl_begin'    => '{',    // 模板引擎普通标签结束标记
	   'tpl_end'      => '}',    // 标签库标签开始标记
	   'taglib_begin' => '{',    // 标签库标签结束标记
	   'taglib_end'   => '}',
	],
	// 'APPID'=>'wx66250f97659f8290',
	// 'APPSECRET'=>'f3d718cfefd699422c98546ba9dd5045',
	'APPID'=>'wxd66b4ab854b524b5',
	'APPSECRET'=>'151c1102bc249f7ad71553163c668bf8',
	'__PUBLIC__'     =>'/static/fortune/',
    //微信支付
    'aply_appid'=>"wxc6ef70525489d95e",
    // 'mch_id'=>"1502168761",
    'mch_id'=>"1508324821",
    "key"=>"aRgzGh476ITcC2Cu6afn6FC2vHYIzO6O",
];
