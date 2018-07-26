<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/5/18
 * Time: 17:01
 */

namespace app\mind\controller;


use think\Controller;

class WxPay extends Controller
{
    public function doPay()
    {
        $openId =input('post.openId');
        $free=input("post.free");
        $wxPayService = new WxPayService();

        $jsApiParameters = $wxPayService->wxpay($openId,$free);
        // 返回支付详情的页面,并把从【统一下单】接口中得到json串串给页面
        // 这个页面描述了买的啥,多少钱,支付按钮之类的
        // 在这个页面点击支付的时候可能出现找不到appId的错误.建议你按照文档上的写法发起支付.
        // 例子我在支付页面里的js给出了demo
        return $jsApiParameters;
    }

}