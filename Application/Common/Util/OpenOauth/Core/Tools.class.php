<?php

namespace Common\Util\OpenOauth\Core;
class Tools
{
    /**
     * 记录日志
     *
     * @param $title
     * @param $data
     * @param $path
     *
     * @return int
     */
    public static function dataRecodes($title, $data, $path)
    {
        $handler = fopen(RUNTIME_PATH.'logs/' . $path . '/oauth' . date('Y-m-d', time()) . '.log', 'a+');
        $content = "================" . $title . "===================\n";
        if (is_string($data) === true) {
            $content .= $data . "\n";
        }
        if (is_array($data) === true) {
            forEach ($data as $k => $v) {
                if (is_array($v)) {
                    $v = json_encode($v);
                }
                $content .= "key: " . $k . " value: " . $v . "\n";
            }
        }
        if (is_bool($data) === true) {
            if ($data) {
                $content .= "true\n";
            } else {
                $content .= "false\n";
            }
        }
        $flag = fwrite($handler, $content);
        fclose($handler);

        return $flag;
    }

    /**
     * 验证签名 成功 true 失败 false
     *
     * @param $token
     *
     * @return bool
     */
    public static function verifySignature($token)
    {
        $signature = $_GET['signature'];
        $timestamp = $_GET['timestamp'];
        $nonce     = $_GET['nonce'];

        if (!is_string($signature) || !is_numeric($timestamp) || $timestamp <= 0 || !is_string($nonce) || $nonce == '') {
            return false;
        }

        $tmpArr = [$token, $timestamp, $nonce];
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);

        if ($tmpStr == $signature && $signature != null) {
            return true;
        } else {
            return false;
        }
    }
}