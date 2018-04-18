<?php

namespace Common\Util;
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
        $handler = fopen(RUNTIME_PATH.'Logs/' . $path . '/' . date('Y-m-d', time()) . '.log', 'a+');
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

}