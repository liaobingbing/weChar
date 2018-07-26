<?php
namespace app\fortune\model;
use think\Model;
class Common extends Model
{
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
     * 获取微信TOKEN
     *  @author TowBen
     *  @param 微信appid 微信appsecret
     *  @return token
     *  @requires OTA() PHPPOST()
     */
    function get_token($appid, $secret)
    {
        // $ACCESS_TOKEN = cache($appid . 'ACCESS_TOKEN');
        // if (empty($ACCESS_TOKEN)) {
            $url          = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $appid . '&secret=' . $secret;
            $ACCESS_TOKEN = PHPPOST($url, '');
            // cache($appid . 'ACCESS_TOKEN', $ACCESS_TOKEN, 7000);
        // }
        $arr = $this->OTA(json_decode($ACCESS_TOKEN));
        return $arr['access_token'];
    }

    /**
     *  生成sha1签名
     *  @author TowBen
     *  @param 微信appid 微信appsecret
     *  @return 接口推送结果
     *  @requires getJsApiTicket() RANGDSTR()
     */
    function getSignPackage($appid, $appsecret)
    {
        $jsapiTicket = $this->getJsApiTicket($appid, $appsecret);
        // $url         = (is_ssl() ? 'https' : 'http') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $url         = 'https' . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $timestamp   = time();
        $nonceStr    = $this->RANGDSTR(16);
        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

        $signature = sha1($string);

        $signPackage = array(
            "appId"     => $appid,
            "nonceStr"  => $nonceStr,
            "timestamp" => $timestamp,
            "url"       => $url,
            "signature" => $signature,
            "rawString" => $string,
        );
        return $signPackage;
    }

    /**
     *  微信获取jsapi_ticket
     *  @author TowBen
     *  @param 微信appid 微信appsecret
     *  @return 接口推送结果
     *  @requires get_token() PHPPOST() OTA()
     */
    function getJsApiTicket($appid, $appsecret)
    {
        $jsapi_ticket = cache($appid . 'jsapi_ticket');
        if ($jsapi_ticket == '') {
            $obj = $this->OTA(json_decode(PHPPOST('https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=' . $this->get_token($appid, $appsecret) . '&type=jsapi')));
            cache($appid . 'jsapi_ticket', $obj['ticket'], 7000);
            $jsapi_ticket = cache($appid . 'jsapi_ticket');
        }
        return $jsapi_ticket;
    }

    //随机生成字符串
    function RANGDSTR($length)
    {
        $str    = null;
        $strPol = "ABCDEFGHIJKLMNPQRSTUVWXYZ123456789abcdefghijklmnpqrstuvwxyz";
        $max    = strlen($strPol) - 1;
        for ($i = 0; $i < $length; $i++) {
            $str .= $strPol[rand(0, $max)];
        }
        return $str;
    }

    //对象转数组
    function OTA($e)
    {
        $e = (array) $e;
        foreach ($e as $k => $v) {
            if (gettype($v) == 'resource') {
                return;
            }

            if (gettype($v) == 'object' || gettype($v) == 'array') {
                $e[$k] = (array) OTA($v);
            }

        }
        return $e;
    }
}