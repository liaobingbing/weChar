<?php
/**
 * 未登陆时的操作
 * Created by PhpStorm.
 * User: HZM
 * Date: 2018/4/14
 * Time: 9:02
 */

namespace Common\Controller;

use Think\Controller;

class ApiLoginController extends Controller
{
    private function login($code,$encryptedData,$iv){
        $url = 'https://api.weixin.qq.com/sns/jscode2session';

    }
}