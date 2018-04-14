<?php
/**
 * Created by PhpStorm.
 * User: HZM
 * Date: 2018/4/13
 * Time: 20:33
 */


/**
 * 删除二维数组重复值
 * @param $array
 * @return array
 */
function two_array_unique($array)
{
    $out = array();

    foreach ( $array as $key => $value ) {
        if( !in_array($value,$out)){
            $out[$key] = $value;
        }
    }

    return $out;
}

/**
 * 手机号合格验证
 * @param $str_data
 * @return bool
 */
function validate_phone($str_data)
{
    $str_rule = "/^1[34578]\d{9}$/";
    $result = false;

    if( preg_match($str_rule,$str_data) == 1){
        $result = true;
    }

    return $result;
}

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

    return $output;
}

/**
 * 判断请求是否来自微信浏览器
 * @return bool
 */
function is_wechat_browser()
{
    $result = false;

    if( strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false ){
        $result = true;
    }

    return $result;
}

/**
 * 判断请求是否来自小程序
 * @return bool
 */
function is_wechat_small_app()
{
    $referer = $_SERVER['HTTP_REFERER'];
    $result = false;

    if( !empty($referer) ){
        $referer =parse_url($referer);

        if( $referer['host'] != 'servicewechat.com' ){
            $result = true;
        }

    }

    return $result;
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


function emoji2unicode($emoji){
    $tmpStr = json_encode($emoji); //暴露出unicode
    $tmpStr=addslashes(substr($tmpStr,1,strlen($tmpStr)-2));
    return $tmpStr;
}
function unicode2emoji($unicode){
    $tmpStr = json_decode("\"".stripslashes($unicode)."\""); //暴露出unicode
    return $tmpStr;
}
function is_base64($str){
//这里多了个纯字母和纯数字的正则判断
    if ($str === base64_encode(base64_decode($str))) {
        return true;
    }else{
        return false;
    }
}
function is($got, $expected, $name){

    $passed = ($got === $expected) ? 1 : 0;

    if ($passed){
        echo "ok # $name\n";
    }else{
        echo "not ok # $name\n";
        echo "# expected : ".byteify($expected)."\n";
        echo "# got      : ".byteify($got)."\n";

        $GLOBALS['failures']++;
    }
}

function byteify($s){
    $out = '';
    for ($i=0; $i<strlen($s); $i++){
        $c = ord(substr($s,$i,1));
        if ($c >= 0x20 && $c <= 0x80){
            $out .= chr($c);
        }else{
            $out .= sprintf('0x%02x ', $c);
        }
    }
    return trim($out);
}

function utf8_bytes($cp){

    if ($cp > 0x10000){
        # 4 bytes
        return	chr(0xF0 | (($cp & 0x1C0000) >> 18)).
            chr(0x80 | (($cp & 0x3F000) >> 12)).
            chr(0x80 | (($cp & 0xFC0) >> 6)).
            chr(0x80 | ($cp & 0x3F));
    }else if ($cp > 0x800){
        # 3 bytes
        return	chr(0xE0 | (($cp & 0xF000) >> 12)).
            chr(0x80 | (($cp & 0xFC0) >> 6)).
            chr(0x80 | ($cp & 0x3F));
    }else if ($cp > 0x80){
        # 2 bytes
        return	chr(0xC0 | (($cp & 0x7C0) >> 6)).
            chr(0x80 | ($cp & 0x3F));
    }else{
        # 1 byte
        return chr($cp);
    }
}

function voiceform($local_url){
    $command="/usr/bin/ffprobe -v quiet -print_format json -show_format ";
    $output="";
    exec($command.$local_url,$output);
    $output=json_decode(implode("",$output),true);
    return $output;
}
function transSilk2Pcm($local_url,$dest=""){
    $dest=str_replace("silk","pcm",$local_url);
    $command1="/home/silk_v3/silk/decoder {$local_url} {$dest} -Fs_API 8000";
    $output="";
    exec($command1,$output);
    $output=json_decode(implode("",$output),true);
    return $output;
}
function transPcm2Amr($local_url){
    $dest=str_replace("pcm","amr",$local_url);
    $command1="/usr/bin/ffmpeg -y -f s16le -ar 8000 -ac 1 -i {$local_url} -ab 12.2k -ar 8000 -ac 1 {$dest}";
    $output="";
    exec($command1,$output);
    $output=json_decode(implode("",$output),true);
    return $output;
}