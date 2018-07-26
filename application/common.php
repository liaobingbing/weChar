<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
/**
 * 调用第三方接口
 * @param $url
 * @param $parameter
 * @return mixed
 */
function post_url($url,$parameter)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT,15);   //只需要设置一个秒的数量就可以
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    // post数据
    curl_setopt($ch, CURLOPT_POST, 1);
    // post的变量
    curl_setopt($ch, CURLOPT_POSTFIELDS, $parameter);
    $output = curl_exec($ch);
    curl_close($ch);
    $output = json_decode($output,true);
    return $output;
}
/**
 * 加密解密
 * @param $text
 * @param $key
 * @param string $type encode:加密 decode:解密
 * @return bool|string
 */
function encode_div( $text, $key, $type = 'encode')
{
    $result = false;
    $chr_arr = array(
        'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o',
        'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O',
        'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
        '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'
    );

    // 解密
    if ( $type == 'decode') {

        if ( strlen($text) >= 14 ) {

            $verity_str = substr($text,0,8);
            $text = substr($text,8);

            // 密文完整性验证
            if ( $verity_str == substr(md5($text),0,8)){
                $key_b = substr($text, 0, 6);
                $rand_key = $key_b.$key;
                $rand_key = md5($rand_key);
                $text = base64_decode(substr($text, 6));

                $result = '';
                for ($i = 0; $i < strlen($text); $i++) {
                    $result .= $text{$i} ^ $rand_key{$i % 32};
                }
            }

        }
    // 加密
    }else{

        $key_b = $chr_arr[rand() % 62] . $chr_arr[rand() % 62] . $chr_arr[rand() % 62] . $chr_arr[rand() % 62] . $chr_arr[rand() % 62] . $chr_arr[rand() % 62];
        $rand_key = $key_b.$key;
        $rand_key = md5($rand_key);

        $result = '';
        for ($i = 0; $i < strlen($text); $i++) {
            $result .= $text{$i} ^ $rand_key{$i % 32};
        }

        $result = trim($key_b.base64_encode($result), "==");
        $result = substr(md5($result), 0, 8) . $result;
    }

    return $result;
}
//暴露出unicode
function emoji2unicode($emoji){
    $tmpStr = json_encode($emoji); //暴露出unicode
    $tmpStr=addslashes(substr($tmpStr,1,strlen($tmpStr)-2));
    return $tmpStr;
}
function unicode2emoji($unicode){
    $tmpStr = json_decode("\"".stripslashes($unicode)."\""); //暴露出unicode
    return $tmpStr;
}

function resCode($code=400,$msg="",$data=null){
    $arr=array("code"=>$code,"msg"=>$msg,"data"=>$data);
    return $arr;
}

 function get_rand(){
    $num=rand(60,98);//随机数
    return $num;
}

/**
 * 随机数
 * @param int $len
 * @param string $type
 * @param string $addChars
 * @return bool|string
 */
function rand_string($len = 5, $type = '2', $addChars = '')
{

    $str = '';

    switch ($type) {

        case '0':

            $chars = "ABCDEFGHIJKLMNPQRSTUVWXYZabcdefghijklmnpqrstuvwxyz" . $addChars;
            break;

        case '1':

            $chars = "0123456789";
            break;

        case '2':

            $chars = "abcdefghijklmnpqrstuvwxyz123456789";
            break;

        default :

            $chars = "ABCDEFGHIJKLMNPQRSTUVWXYZabcdefghijklmnpqrstuvwxyz123456789" . $addChars;
            break;

    }

    $chars = str_shuffle($chars);

    $str = substr($chars, 1, $len);

    return $str;

}

/**
 * 同步POST请求，需要打开curl扩展
 * @param url 请求地址
 * @param data 请求数据
 */
function PHPPOST($url, $data = array()) {
    $curl = curl_init(); // 启动一个CURL会话
    curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 对认证证书来源的检查
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在
    curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
    if (!empty($data)) {
        curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
    }
    curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
    curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
    $info = curl_exec($curl); // 执行操作

    if (curl_errno($curl)) {
        echo 'ErrnoCode:' . curl_errno($curl) . "\n";
        echo 'ErrnoLog:' . curl_error($curl) . "\n";
        echo 'ErrorData:' . var_export(curl_getinfo($curl), true);
    }
    return $info;
}

/**
 * api返回
 * @param error 错误码
 * @param msg 返回信息
 * @param data 返回数据组
 * @param count 数据量
 */
function api_return($error, $msg, $data = array(), $count = '') {
    // return true;
    header('Content-Type:application/json; charset=utf-8');
    header("Access-Control-Allow-Origin:*");

    /**
     * 全局转换为字符串
     */
    function toNullStr($data = array()) {
        foreach ($data as &$v) {
            if (is_array($v)) {
                $v = toNullStr($v);
            } elseif (is_bool($v)) {

            } else {
                $v .= '';
            }
        }
        return $data;
    }
    $data = toNullStr($data);
    if ($error == 'SUCCESS') {
        // if (empty($data)) {
        //     $data['success'] = true;
        // }
        if (empty($msg)) {
            $msg = 'ok';
        }
    } else {
        // if (I('app_type') != 'android' && empty($data)) {
        //     $data['success'] = false;
        // }
    }

    $returnArr = array('error' => e($error), 'msg' => $msg, 'data' => $data);

    if ($count !== '') {
        $returnArr['count'] = intval($count);
    }

    echo $r = json_encode($returnArr);

    if (APP_DEBUG) {
        $r = array(
            '返回数据信息' => json_decode($r, true),
            'POST请求数据' => $_POST,
            'GET请求数据' => $_GET,
        );
        
    }
    exit;
}

function e($e) {
    $code = array(
        //不影响
        'LOGIN_OUT' => -1, //登录超时
        'SUCCESS' => 0, //正常
        'NO_CHANGE' => 1, //数据无变动
        'HINT_ERR' => 2, //提示错误
        'SHOP_END' => 3, //店铺过期
        'EXPAND_LACK' => 4, //推广号余额不足
        'BALANCE_LACK' => 5, //用户号余额不足
        'INTEGRAL_LACK' => 6, //积分不足
        'USER_RANK_LACK' => 7, //等级不足
        'NEED_FOCUS_WECHAT' => 8, //需要先关注微信公众号
        'LIMIT_POS_LACK' => 9, //额度不足
        //
        'CHECK_ERR' => 20, //校验失败、信息不匹配
        'LINK_ERR' => 22, //连接错误
        //
        //中断操作(验证错误)
        'VAL_NULL' => 40, //参数为空
        'VAL_INVALID' => 41, //参数无效不存在
        'FORMAT_ERR' => 42, //格式错误(系统提交数据的验证)
        'CODE_EXCEPTION' => 43, //代码异常
    );
    return $code[$e];
}

/**
 * emoji表情处理
 * @param $str 字符串
 * @param $rep 替换字符
 * @return mixed
 */
function emoji($str, $rep = '') {
    $regex = '/(\\\u[ed][0-9a-f]{3})/i';
    $str = json_encode($str);
    $str = preg_replace($regex, $rep, $str);
    return json_decode($str);
}

/**
 * 微信授权
 *  @param string appid
 *  @param string appsecret
 *  @return array()
 */
function getUserInfo($appid, $appsecret)
{
    global $_SESSION, $_GET;
    $redirect_uri = urlencode((is_ssl() ? 'https' : 'http') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
    $code         = input('code');
    // unset(input('code'));
    if ($code == '') {
        redirect("https://open.weixin.qq.com/connect/oauth2/authorize?appid=$appid&redirect_uri=$redirect_uri&response_type=code&scope=snsapi_userinfo&state=1#wechat_redirect");
    }
    $access_token_url     = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=$appid&secret=$appsecret&code=$code&grant_type=authorization_code";
    $access_token_json    = PHPPOST($access_token_url); //获取openid
    $access_token_array   = json_decode($access_token_json, true);
    $access_token         = $access_token_array['access_token'];
    $_SESSION['wecha_id'] = $openid = $access_token_array['openid'];
    $userinfo_url         = "https://api.weixin.qq.com/sns/userinfo?access_token=$access_token&openid=$openid&lang=zh_CN";
    $userinfo_json        = PHPPOST($userinfo_url); //获取用户详情星系

    $userinfo_array = json_decode($userinfo_json, true);
    /**
     * @return array
     *唯一标识 openid
     *微信昵称 nickname
     *头像 headimgurl
     *国家 country
     *省份 province
     *城市 city
     *男 sex==1
     *女 sex==2
     */
    return $userinfo_array;
}

/**
 * 判断是否SSL协议
 * @return boolean
 */
function is_ssl() {
    if(isset($_SERVER['HTTPS']) && ('1' == $_SERVER['HTTPS'] || 'on' == strtolower($_SERVER['HTTPS']))){
        return true;
    }elseif(isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'] )) {
        return true;
    }
    return false;
}