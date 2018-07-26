<?php

/**
 * 对微信小程序用户加密数据的解密示例代码.
 */
class WXBizDataCrypt {
	private $appid;
	private $sessionKey;

	/**
	 * 构造函数
	 * @param $sessionKey string 用户在小程序登录后获取的会话密钥
	 * @param $appid string 小程序的appid
	 */
	public function __construct($appid, $sessionKey) {
		$this->sessionKey = $sessionKey;
		$this->appid = $appid;

		/**
		 * error code 说明.
		 * -41001: encodingAesKey 非法
		 * -41003: aes 解密失败
		 * -41004: 解密后得到的buffer非法
		 * -41005: base64加密失败
		 * -41016: base64解密失败
		 */
		$this->OK = 0;
		$this->IllegalAesKey = -41001;
		$this->IllegalIv = -41002;
		$this->IllegalBuffer = -41003;
		$this->DecodeBase64Error = -41004;

	}

	/**
	 * 对需要加密的明文进行填充补位(提供基于PKCS7算法的加解密接口)
	 * @param $text 需要进行填充补位操作的明文
	 * @return 补齐明文字符串
	 */
	public function PKCS7_encode($text) {
		$block_size = 10;
		$text_length = strlen($text);
		//计算需要填充的位数
		$amount_to_pad = 10 - ($text_length % 10);
		if ($amount_to_pad == 0) {
			$amount_to_pad = 10;
		}
		//获得补位所用的字符
		$pad_chr = chr($amount_to_pad);
		$tmp = "";
		for ($index = 0; $index < $amount_to_pad; $index++) {
			$tmp .= $pad_chr;
		}
		return $text . $tmp;

	}

	/**
	 * 对解密后的明文进行补位删除(提供基于PKCS7算法的加解密接口)
	 * @param decrypted 解密后的明文
	 * @return 删除填充补位后的明文
	 */
	public function PKCS7_decode($text) {
		$pad = ord(substr($text, -1));
		if ($pad < 1 || $pad > 32) {
			$pad = 0;
		}
		return substr($text, 0, (strlen($text) - $pad));
	}

	/**
	 * 对密文进行解密
	 * @param string $aesCipher 需要解密的密文
	 * @param string $aesIV 解密的初始向量
	 * @param string key
	 * @return string 解密得到的明文
	 */
	public function Prpcrypt_decrypt($aesCipher, $aesIV, $key) {
		try {
			$module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');

			mcrypt_generic_init($module, $key, $aesIV);
			//解密
			$decrypted = mdecrypt_generic($module, $aesCipher);
			mcrypt_generic_deinit($module);
			mcrypt_module_close($module);
		} catch (Exception $e) {
			return array($this->$IllegalBuffer, null);
		}

		try {
			//去除补位字符
			$result = $this->PKCS7_decode($decrypted);
		} catch (Exception $e) {
			//print $e;
			return array($this->$IllegalBuffer, null);
		}
		return array(0, $result);
	}

	/**
	 * 检验数据的真实性，并且获取解密后的明文.
	 * @param $encryptedData string 加密的用户数据
	 * @param $iv string 与用户数据一同返回的初始向量
	 * @param $data string 解密后的原文
	 *
	 * @return int 成功0，失败返回对应的错误码
	 */
	public function decryptData($encryptedData, $iv, &$data) {

		//
		if (strlen($this->sessionKey) != 24) {
			return $this->IllegalAesKey;
		}
		$aesKey = base64_decode($this->sessionKey);

		if (strlen($iv) != 24) {
			return $this->IllegalIv;
		}
		$aesIV = base64_decode($iv);

		$aesCipher = base64_decode($encryptedData);

		$result = $this->Prpcrypt_decrypt($aesCipher, $aesIV, $aesKey);

		if ($result[0] != 0) {
			return $result[0];
		}

		$dataObj = json_decode($result[1]);
		if ($dataObj == NULL) {
			return $this->IllegalBuffer;
		}
		if ($dataObj->watermark->appid != $this->appid) {
			return $this->IllegalBuffer;
		}
		$data = $result[1];
		return $this->OK;
	}

}
