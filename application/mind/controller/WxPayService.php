<?php
/**
 * Created by PhpStorm.
 * User: mayn
 * Date: 2018/5/17
 * Time: 15:57
 */

namespace app\mind\controller;
class WxPayService
{
    /**
     * @param $openId
     * @return String js支付参数
     */
    public function wxpay($openId,$free)
    {
        vendor("wxpay.Api");
        //统一下单
        $input = new \WxPayUnifiedOrder();
        $input->SetBody("快鱼商城");
        $input->SetAttach("快鱼商城支付");
        $input->SetOut_trade_no(\WxPayConfig::MCHID.date("YmdHis"));
        $input->SetTotal_fee($free);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag("快鱼");
       // $input->SetNotify_url("http://wxpay.foo.cn/weixin/test/notify");
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openId);
        $order = \WxPayApi::unifiedOrder($input);


        $jsApiParameters = $this->getJsApiParameters($order);

        return $jsApiParameters;
    }

    /**
     *
     * 获取jsapi支付的参数
     * @param  $UnifiedOrderResult array 统一支付接口返回的数据
     * @throws \WxPayException
     *
     * @return  array ，可直接填入js函数作为参数
     */
    private function getJsApiParameters($UnifiedOrderResult)
    {
        if(!array_key_exists("appid", $UnifiedOrderResult)
            || !array_key_exists("prepay_id", $UnifiedOrderResult)
            || $UnifiedOrderResult['prepay_id'] == "")
        {
            throw new \WxPayException("参数错误");
        }
        $jsapi = new \WxPayJsApiPay();
        $jsapi->SetAppid($UnifiedOrderResult["appid"]);
        $timeStamp = time();
        $jsapi->SetTimeStamp("$timeStamp");
        $jsapi->SetNonceStr(\WxPayApi::getNonceStr());
        $jsapi->SetPackage("prepay_id=" . $UnifiedOrderResult['prepay_id']);
        $jsapi->SetSignType("MD5");
        $jsapi->SetPaySign($jsapi->MakeSign());
        $parameters = $jsapi->GetValues();
        return $parameters;
    }
}