<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/5/15
 * Time: 9:59
 */

namespace app\box\model;


use think\Model;

class Banner extends Model
{
    protected $connection = [
        // 数据库类型
        'type'            => 'mysql',
        // 服务器地址
        'hostname'        => '47.106.198.229',
        // 数据库名
        'database'        => 'kuaiyu_admin',
        // 用户名
        'username'        => 'root',
        // 密码
        'password'        => '3zprYtPzHrd3AsYa',
        // 数据库连接端口
        'hostport'    => '',
        // 数据库连接参数
        'params'      => [],
        // 数据库编码默认采用utf8
        'charset'     => 'utf8',
        // 数据库表前缀
        'prefix'      => '',
    ];
}