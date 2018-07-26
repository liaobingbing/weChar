<?php
/**
 * 错误码表
 */
namespace Api\Org;

class ErrorCode {

	public function e($e) {
		$code = array(
			//不影响
			'LOGIN_OUT' => -1, //登录超时
			'SUCCESS' => 0, //正常
			'NO_CHANGE' => 1, //数据无变动
			'HINT_ERR' => 2, //提示错误
			'SHOP_END' => 3, //店铺过期
			'EXPAND_LACK' => 4, //推广号余额不足
			'BALANCE_LACK' => 5, //用户号余额不足
			'INTEGRAL_LACK' => 6, //积分不足
			'USER_RANK_LACK' => 7, //等级不足
			'NEED_FOCUS_WECHAT' => 8, //需要先关注微信公众号
			'LIMIT_POS_LACK' => 9, //额度不足
			//
			'CHECK_ERR' => 20, //校验失败、信息不匹配
			'LINK_ERR' => 22, //连接错误
			//
			//中断操作(验证错误)
			'VAL_NULL' => 40, //参数为空
			'VAL_INVALID' => 41, //参数无效不存在
			'FORMAT_ERR' => 42, //格式错误(系统提交数据的验证)
			'CODE_EXCEPTION' => 43, //代码异常
		);
		return $code[$e];
	}

}
