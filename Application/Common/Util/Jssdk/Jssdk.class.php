<?php
namespace Common\Util\Jssdk;

class JSSDK {
    private $appId;
    private $appSecret;
    private $access_token;
    private $jsapi_ticket;


    public function __construct($appId, $appSecret) {
    $this->appId = $appId;
    $this->appSecret = $appSecret;
  }

  public function getSignPackage() {
      $this->log("===============================");
    $jsapiTicket = $this->getJsApiTicket();
      $this->log("===============================".$jsapiTicket);


      // 注意 URL 一定要动态获取，不能 hardcode.
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

    $timestamp = time();
    $nonceStr = $this->createNonceStr();

    // 这里参数的顺序要按照 key 值 ASCII 码升序排序
    $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

    $signature = sha1($string);

    $signPackage = array(
      "appId"     => $this->appId,
      "nonceStr"  => $nonceStr,
      "timestamp" => $timestamp,
      "url"       => $url,
      "signature" => $signature,
      "rawString" => $string
    );
    return $signPackage; 
  }

  private function createNonceStr($length = 16) {
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $str = "";
    for ($i = 0; $i < $length; $i++) {
      $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    return $str;
  }

  private function getJsApiTicket() {
    // jsapi_ticket 应该全局存储与更新，以下代码以写入到文件中做示例
      $authname = 'wechat_jsapi_ticket'.$this->appId;
      $this->log($authname);

      if ($rs = $this->getCache($authname))  {
          $this->jsapi_ticket = $rs;
          return $rs;
      }
      $this->log($rs);

      $accessToken = $this->getAccessToken();
      $this->log($accessToken);

      $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
      $result = $this->httpGet($url);
      $this->log($result);

      if ($result)
      {
          $json = json_decode($result,true);
          $this->log($json);

          if (!$json || !empty($json['errcode'])) {
              if($json['errcode'] == '40001'){
                  $json_res = $this->resetgetJsApiTicket();
                  return $json_res;
              }
              $this->errCode = $json['errcode'];
              $this->errMsg = $json['errmsg'];
              return false;
          }
          $this->jsapi_ticket = $json['ticket'];
          $expire = $json['expires_in'] ? intval($json['expires_in'])-100 : 3600;
          $this->setCache($authname,$this->jsapi_ticket,$expire);
          $this->log($this->jsapi_ticket);

          return $this->jsapi_ticket;
      }

    return false;
  }

    public function resetgetJsApiTicket(){
        $this->resetAuth($this->appId);
        $authname = 'wechat_jsapi_ticket'.$this->appId;
        $this->log($authname);

        if ($rs = $this->getCache($authname))  {
            $this->jsapi_ticket = $rs;
            return $rs;
        }
        $this->log($rs);

        $accessToken = $this->getAccessToken();
        $this->log($accessToken);

        $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
        $result = $this->httpGet($url);
        $this->log($result);

        if ($result)
        {
            $json = json_decode($result,true);
            $this->log($json);

            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }
            $this->jsapi_ticket = $json['ticket'];
            $expire = $json['expires_in'] ? intval($json['expires_in'])-100 : 3600;
            $this->setCache($authname,$this->jsapi_ticket,$expire);
            $this->log($this->jsapi_ticket);

            return $this->jsapi_ticket;
        }

        return false;
    }

  public  function getAccessToken() {
    // access_token 应该全局存储与更新，以下代码以写入到文件中做示例
      $authname = 'wechat_access_token'.$this->appId;
      $this->log($authname);

      if ($rs = $this->getCache($authname))  {
          $this->access_token = $rs;
          return $rs;
      }
      $this->log($rs);
      $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret";
      $result = $this->httpGet($url);
      $this->log($result);

      if ($result)
      {
          $json = json_decode($result,true);
          if (!$json || isset($json['errcode'])) {
              $this->errCode = $json['errcode'];
              $this->errMsg = $json['errmsg'];
              return false;
          }
          $this->access_token = $json['access_token'];
          $expire = $json['expires_in'] ? intval($json['expires_in'])-100 : 3600;
          $this->setCache($authname,$this->access_token,$expire);
          return $this->access_token;
      }
      return false;
  }

  private function httpGet($url) {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, 500);
    // 为保证第三方服务器与微信服务器之间数据传输的安全性，所有微信接口采用https方式调用，必须使用下面2行代码打开ssl安全校验。
    // 如果在部署过程中代码在此处验证失败，请到 http://curl.haxx.se/ca/cacert.pem 下载新的证书判别文件。
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, true);
    curl_setopt($curl, CURLOPT_URL, $url);

    $res = curl_exec($curl);
    curl_close($curl);

    return $res;
  }

  private function get_php_file($filename) {
      return trim(substr(file_get_contents($filename), 15));
  }
  private function set_php_file($filename, $content) {
      $fp = fopen($filename, "w");
      fwrite($fp, "<?php exit();?>" . $content);
      fclose($fp);
  }

    /**
     * 设置缓存，按需重载
     * @param string $cachename
     * @param mixed $value
     * @param int $expired
     * @return boolean
     */
    protected function setCache($cachename,$value,$expired){
        //TODO: set cache implementation
        S($cachename,$value,$expired);
        return $value;
    }
    /**
     * 获取缓存，按需重载
     * @param string $cachename
     * @return mixed
     */
    protected function getCache($cachename){
        //TODO: get cache implementation

        return S($cachename);
    }

    /**
     * 删除验证数据
     * @param string $appid
     */
    public function resetAuth($appid=''){
        if (!$appid) $appid = $this->appId;
        $this->access_token = '';
        $authname = 'wechat_access_token'.$appid;
        $this->log($authname);
        $this->removeCache($authname);
        return true;
    }
    /**
     * 清除缓存，按需重载
     * @param string $cachename
     * @return boolean
     */
    protected function removeCache($cachename){
        //TODO: remove cache implementation

        return S($cachename,null);
    }

    public function log($data)
    {
        if (is_string($data)) {
            file_put_contents(RUNTIME_PATH.'Logs/debug_log.txt', $data . PHP_EOL, FILE_APPEND);
        } else {
            file_put_contents(RUNTIME_PATH.'Logs/debug_log.txt', var_export($data, true), FILE_APPEND);
        }
    }
}

