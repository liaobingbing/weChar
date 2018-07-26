<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/5/19
 * Time: 10:01
 */

namespace app\fortune\controller;


use app\idiom\model\Game;
use app\idiom\model\User;
use common\controller\ApiLogin;
use think\Controller;

class Login extends ApiLogin
{
    public function index()
    {
        var_dump('tes');
    }
}