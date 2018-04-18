<?php
/**
 * Created by PhpStorm.
 * User: iqny-vkb
 * Date: 16/5/23
 * Time: 下午6:51
 */
namespace Common\Util;

class Yunpian
{

    public function show()
    {
        echo 'yunpian';
    }

    /**
     * @param $apikey 修改为您的apikey(https://www.yunpian.com)登录官网后获取
     * @param $mobile   请用自己的手机号代替
     * @param $text 您的验证码是1234
     * @param $type 发送类型 1普通短信,2模板短信((默认),3语音短信
     */
    public function sendSns($apikey, $mobile, $text, $type = 2)
    {
        header("Content-Type:text/html;charset=utf-8");
        $ch = curl_init();

        /* 设置验证方式 */

        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept:text/plain;charset=utf-8', 'Content-Type:application/x-www-form-urlencoded', 'charset=utf-8'));

        /* 设置返回结果为流 */
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        /* 设置超时时间*/
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        /* 设置通信方式 */
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        switch ($type) {
            case 1:
                // 发送短信
                $data = array('text' => $text['content'], 'apikey' => $apikey, 'mobile' => $mobile);
                $json_data = $this->send($ch, $data);
                $array = json_decode($json_data, true);
                curl_close($ch);
                break;
            case 2:
                // 发送模板短信
                // 需要对value进行编码
                $data = array('tpl_id' => $text['tpl_id'],
                    'tpl_value' => ('#code#') . '=' . urlencode($text['code']) . '&' . ('#app#') . '=' . urlencode($text['content']) ,
                    'apikey' => $apikey,
                    'mobile' => $mobile);
                $json_data = $this->tpl_send($ch, $data);
                $array = json_decode($json_data, true);
                break;
            case 3;
                // 发送语音验证码
                $data = array('code' => $text['code'], 'apikey' => $apikey, 'mobile' => $mobile);
                $json_data = $this->voice_send($ch, $data);
                $array = json_decode($json_data, true);
                break;
            default:
                break;

        }

        return $array;

    }

    //获得账户
    private function get_user($ch, $apikey)
    {
        curl_setopt($ch, CURLOPT_URL, 'https://sms.yunpian.com/v2/user/get.json');
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('apikey' => $apikey)));
        return curl_exec($ch);
    }

    /**
     * 普通短信
     * @param $ch
     * @param $data
     * @return mixed
     */
    private function send($ch, $data)
    {
        curl_setopt($ch, CURLOPT_URL, 'https://sms.yunpian.com/v2/sms/single_send.json');
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        return curl_exec($ch);
    }

    /**
     * 模板短信
     * @param $ch
     * @param $data
     * @return mixed
     */
    private function tpl_send($ch,$data){
        curl_setopt ($ch, CURLOPT_URL, 'https://sms.yunpian.com/v2/sms/tpl_single_send.json');
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        return curl_exec($ch);
    }

    /**
     * 语音短信
     * @param $ch
     * @param $data
     * @return mixed
     */
    private function voice_send($ch, $data)
    {
        curl_setopt($ch, CURLOPT_URL, 'http://voice.yunpian.com/v2/voice/send.json');
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        return curl_exec($ch);
    }


}