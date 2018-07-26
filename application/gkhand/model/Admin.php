<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/6/6
 * Time: 17:40
 */

namespace app\gkhand\model;


use think\Model;

class Admin extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'admin_wx_token';
// 设置当前模型的数据库连接
    protected $connection = [
// 数据库类型
        'type' => 'mysql',
// 服务器地址
        'hostname' => '120.79.97.48',
// 数据库名
        'database' => 'kuaiyu_admin',
// 数据库用户名
        'username' => 'root',
// 数据库密码
        'password' => 'qianTU123456',
// 数据库编码默认采用utf8
        'charset' => 'utf8',
// 数据库表前缀
        'prefix' => 'admin_',
// 数据库调试模式
        'debug' => false,
    ];
}