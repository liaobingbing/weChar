<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/6/28
 * Time: 9:40
 */

namespace app\fortune\controller;


use think\Controller;

class Notify extends Controller
{
    public function index()
    {
        header('Content-type:text/html; Charset=utf-8');
        $mchid = '1508324821';          //微信支付商户号 PartnerID 通过微信支付商户资料审核后邮件发送
        $appid = 'wxc6ef70525489d95e';  //公众号APPID 通过微信支付商户资料审核后邮件发送
        $apiKey = '61e2b62b1aeb521b5047e5803a1cf06c';   //https://pay.weixin.qq.com 帐户设置-安全设置-API安全-API密钥-设置API密钥
        $wxPay = new WxpayService($mchid,$appid,$apiKey);
        $result = $wxPay->notify();
        if($result){
            $res=resCode(200,"ok",null);
        }else{
            $res=resCode(400,"支付失败",null);
        }
        return $res;
    }
}