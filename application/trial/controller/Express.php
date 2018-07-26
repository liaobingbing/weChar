<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/7/11
 * Time: 10:31
 */

namespace app\trial\controller;


use think\Controller;

class Express extends Controller
{
    public function index()
    {
     $com=input("courier_name","shentong");
    $nu=input("courier_no","3367544650238");
    $host = "https://ali-deliver.showapi.com";
    $path = "/showapi_expInfo";
    $method = "GET";
    $appcode = "acf507f0eb994425a95956891d7ae8e0";
    $headers = array();
    array_push($headers, "Authorization:APPCODE " . $appcode);
    $querys = "com=$com&nu=$nu";
    $bodys = "";
    $url = $host . $path . "?" . $querys;

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_FAILONERROR, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, false);
    if (1 == strpos("$".$host, "https://"))
    {
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    }
    $res=curl_exec($curl);
    print $res;
    }
}