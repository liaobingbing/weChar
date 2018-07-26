<?php
/**
 * 微信模板消息发送
 *  @author 
 *  @version 1.0
 */
namespace app\congratulate\model;
use think\Model;

class WechatTmpMsg extends Model {
	private $appid;
	private $appsecret;

	/**
	 * 初始化
	 */
	public function __construct() {
		/*读取微信配置*/
		$this->appid = config('WECHAT_APPID');
		$this->appsecret = config('WECHAT_APPSECRET');
	}

	/**
	 * 获取微信TOKEN
	 *  @param 微信appid 微信appsecret
	 *  @return token
	 *  @requires PHPPOST()
	 */
	function get_token($appid, $secret) {
		// $ACCESS_TOKEN = session('appid',$appid . 'ACCESS_TOKEN');

		// if (empty($ACCESS_TOKEN)) {
			$ACCESS_TOKEN = $this->new_get_token($appid, $secret);

		// }
		return $ACCESS_TOKEN['access_token'];
	}

	/**
	 * 获取新微信TOKEN
	 *  @param 微信appid 微信appsecret
	 *  @return array(access_token,expires_in)
	 *  @requires PHPPOST()
	 */
	function new_get_token($appid, $secret) {
		$url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $appid . '&secret=' . $secret;
		$ACCESS_TOKEN = json_decode(PHPPOST($url, ''), true);
		$ACCESS_TOKEN['expires_in'] = intval($ACCESS_TOKEN['expires_in']) - 10;
		// session('appid',$appid . 'ACCESS_TOKEN', $ACCESS_TOKEN, $ACCESS_TOKEN['expires_in']);
		return $ACCESS_TOKEN;
	}

	/**
	 * 获取小程序用户的form_id
	 * @param open_id 微信openid
	 */
	public function xcx_get_form_id($open_id) {
		if (!$open_id) {
			return '';
		}
		$this->xcx_delete_form_id(); //删除小程序过期formid
		$formInfo = db('xcx_formid')->where(array(
			'open_id' => $open_id,
		))
			->order('add_time ASC')
			->field('form_id')->find();
		// echo M()->_sql();exit;
		return $formInfo['form_id'];
	}

	/**
	 * 删除小程序过期formid
	 */
	public function xcx_delete_form_id() {
		db('xcx_formid')->where(array('add_time' => array('LT', time() - 7 * 24 * 3600 - 30)))->whereOr(array('form_id' => 'the formId is a mock one'))->delete();
	}

	/**
	 * 发送模版消息
	 * @param data 数据
	 * @param app_type
	 */
	public function xcxSend($data) {
		$access_token = $this->get_token($this->appid, $this->appsecret);
		is_array($data) && $data = json_encode($data);
		$r = json_decode(PHPPOST('https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=' . $access_token, $data), 1);
		// var_dump($r, json_decode($data, 1));exit;
		if ($r['errcode'] == 0) {
			//核销form_id
			$data = json_decode($data, 1);
			if ($formInfo = db('xcx_formid')->where(array('form_id' => $data['form_id'], 'open_id' => $data['touser']))->field('id')->find()) {
				db('xcx_formid')->where(array('id' => $formInfo['id']))->delete();
			}
		}
		// var_dump($r,$this->appid_ds, $this->appsecret_ds,$data, $app_type);
		return $r;
	}

	/**
	 * 模板消息推送
	 *
	 * @param open_id 要发送信息的用户的wecha_id
	 * @param data array(
		测试名称{{keyword1.DATA}}
		测试报告{{keyword2.DATA}}
		备注{{keyword3.DATA}}
	)
	 * @param url 消息点击后跳转的连接
	 */
	public function send_template($openid, $data = array(), $url = '') {
		$template_id = 'gHkNpvPrTzUFPvHSVi_3KSSnT1XOnNXdnVBvOc8xszo';
		$send_data = json_encode(array(
			'touser' => $openid,
			'template_id' => $template_id,
			'page' => $url, //点击模板卡片后的跳转页面
			'form_id' => $this->xcx_get_form_id($openid),
			'data' => array(
				//任务名称
				'keyword1' => array(
					"value" => $data[0],
					"color" => "#173177",
				),
				//上传时间
				'keyword2' => array(
					"value" => $data[1],
					"color" => "#173177",
				),
				//日期
				'keyword3' => array(
					"value" => $data[2],
					"color" => "#173177",
				),
			),
			'emphasis_keyword' => 'keyword1.DATA', //模板需要放大的关键词，不填则默认无放大
		));
		// var_dump($send_data);
		return $this->xcxSend($send_data);
	}

}
