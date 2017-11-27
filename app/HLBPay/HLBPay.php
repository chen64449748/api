<?php 

class HLBPay
{
	private $http_client;
	private $crypt_rsa;

	// 测试环境
	private $pay_gateway = 'http://test.trx.helipay.com/trx/';
	private $huan_gateway = 'http://test.trx.helipay.com/trx/';
	//生产环境
	// private $pay_gateway = 'http://pay.trx.helipay.com/trx/';
	// private $huan_gateway = 'http://transfer.trx.helipay.com/trx/';

	// 私钥 测试
	private $signkey = 't7rjXvj5yW3qyRa0Y2HOqcz830Bp3bM3'; 
	// rsa 私钥
	private $rsa_signkey = 'MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBAK5GQSOPqzt7o4xcgygdikqN1uY13J7Uu0nJdm/BtxPH1Y1qolPUw/lSCd83f7KnS/xS/THCVEwvUm2iOtQKIDj2A/SC7Jy+bZbbbrJqkx+61pgjuIFsKo7Wf/2OX59Nj1qQlWa99J3ZH/kEFxKd5V1moV9cCNpBZVoEYyhmBbajAgMBAAECgYAIDqt4T24lQ+Qd2zEdK7B3HfOvlRHsLf2yvaPCKvyh531SGnoC0jV1U3utXE2FHwL+WX/nSwrGsvFmrDd4EjfHFsqRvHm+TJfXoHtmkfvbVGI7bFl/3NbYdi76tqbth6W8k0gkPUsACs2ix8a4K7zxOO+UpOeUBIXrchDxFmj9sQJBAOgSHQAI5hr/3+rSQXlq2lET87Ew9Ib72Lwqri3vsHO/sysVTLAznuA+V8s+a4tUeA839a/tGLp1SaJhvma9/30CQQDAPoNFw4rYTm9vbQnrCb6Mm0l9GNpCD1c4ShTxHJyt8Gql0e1Sl3vc28AxyqHLq66abYDzOpnPGJ8AIpri4qifAkArJsMRsJXoy089gJ8ADqhNjyIu/mVZfBbO1jjQ/dKXkzujdTBvSwnttGnqts6Ud75jRgp/Dd0dPpXUhcw7mnSZAkEAmYeTKP0Afr0tS6ymNhojHoHJz+kwLX+45VBspx51loghc+pSgRpPplOti1ZLnq+uktAPIrDTM0xzdxUr4zSm+wJAV54yRQsZBkRmhPibmLeoe3lM6hcAwLqS+E1H09X92fBLqCtBAybDnf++hT2ATgtW+xgI+tFVbOqbi1QbLyw1kA==';
	
	// 测试
	private $customer_number = 'C1800001107';
	private $credit_number = 'C1800001108';

	private $type; // 业务类型
	public $send_url; // 发送接口
	public $send_data; // 发送的参数
	private $sign; // 签名
	private $out_order_id; // 生成单号

	public $response; // 返回
	public $result; // 返回结果

	function setType($type)
	{
		$this->type = $type;
		$url_t = '';
		// TransferQuery  CreditCardRepayment 需要RSA
		if ($type == 'repay' || $type == 'repayQuery') {
			$this->setRSA(new Crypt_RSA);
			$this->send_url = $this->huan_gateway . 'transfer/interface.action';
		} else {
			$this->send_url = $this->pay_gateway . 'quickPayApi/interface.action';
		}
	}

	function setParams($params)
	{
		// 接口列表
		// key 参数type， value api地址
		// $type_list = array(
		// 	'QuickPayBankCardPay' 			=> 'quickPayApi', // 银行卡支付下单
		// 	'QuickPayBindPay'				=> 'quickPayApi', // 绑卡支付
		// 	'QuickPayBindPayValidateCode'	=> 'quickPayApi', // 绑卡支付短信

		// 	'QuickPayBindCard' 				=> 'quickPayApi', // 绑卡
		// 	'QuickPayBindCardValidateCode'  => 'quickPayApi', // 绑卡短信

		// 	'QuickPayQuery'					=> 'quickPayApi', // 订单查询

		// 	'CreditCardRepayment'			=> 'transfer', 	  // 信用卡还款	
		// 	'TransferQuery'					=> 'transfer',	  // 信用卡还款查询
		// 	'AccountQuery'					=> 'quickPayApi',    // 用户余额查询
		// 	'BankCardUnbind'				=> 'quickPayApi',	  // 解绑银行卡
		// 	'BankCardbindList'				=> 'quickPayApi',    // 用户绑定银行卡信息查询（仅限于交易卡）
		// );

		$type_list = array(
			'bankBind' 			=> 'QuickPayBindCard', // 绑卡
			'bankBindCode'  	=> 'QuickPayBindCardValidateCode', // 绑卡短信
			'bankUnbind'		=> 'BankCardUnbind', // 解绑银行卡
			'bankList'			=> 'BankCardbindList', // 用户绑定银行卡信息查询（仅限于交易卡）

			'pay'				=> 'QuickPayBindPay', // 绑卡支付
			'payCode'			=> 'QuickPayBindPayValidateCode', // 绑卡支付短信

			'payQuery'			=> 'QuickPayQuery', // 订单查询
			
			'repay'				=> 'CreditCardRepayment', // 信用卡还款	
			'repayQuery'		=> 'TransferQuery', // 信用卡还款查询

			'account'			=> 'AccountQuery', // 用户余额查询

		);

		$params['P1_bizType'] = $type_list[$this->type];

		call_user_func(array($this, $type_list[$this->type]), $params);
	}

	function sendRequest()
	{
		if ($this->type == 'repayQuery' || $this->type == 'repay') {
			$this->ras_sign();
		} else {
			$this->md5_sign();
		}

		$pageContents = HttpClient::quickPost($this->send_url, $this->send_data);
		
		if ($this->type == 'repay') {
			$pageContents = iconv('UTF-8','GBK//IGNORE', $pageContents);
		}

		$this->response = $pageContents;
		$result = json_decode($pageContents);
		print_r($result);exit;
		$this->result = $result;
	}

	function getResult()
	{

		// 验证签名
		$tmp_result = $this->result;
		$rt_sign = $tmp_result['sign'];
		unset($tmp_result['sign'], $tmp_result['rt3_retMsg']);
		ksort($tmp_result);

		$sign_str = '';

		foreach ($tmp_result as $key => $value) {
			$sign_str .= '&'.$value;
		}
		$sign_str .= '&'.$this->signkey;

		if (md5($sign_str) != $rt_sign) {
			return array('action'=> 0, 'code'=> 'error', 'msg'=> '返回数据签名失败，请注意您所在的网络环境是否安全', 'result'=> array());
		}

		if ($this->result['rt2_retCode'] == '0000') {

			return array('action'=> 1, 'code'=> '0000', 'msg'=> '成功', 'result'=> $this->result);

		} else {
			switch ($this->result['rt2_retCode']) {
				case '8000':
					$msg = '失败';
					break;
				case '8001': // 输入参数错误
					$msg = '输入参数错误';
					break;

				case '8002': // 订单号不唯一
					$msg = '订单号不唯一';
					break;

				case '8003': // 订单金额不正确
					$msg = '订单金额不正确';
					break;

				case '8004': // 订单不存在
					$msg = '订单不存在';
					break;

				case '8005': // 订单状态异常
					$msg = '订单状态异常';
					break;

				case '8006': // 订单对应的渠道未在系统中配置
					$msg = '订单对应的渠道未在系统中配置';
					break;

				case '8007': // 退款金额超过了订单实付金额
					$msg = '退款金额超过了订单实付金额';
					break;

				case '8008': // 渠道请求交互验签错误
					$msg = '渠道请求交互验签错误';
					break;

				case '8009': // 订单已过期
					$msg = '订单已过期';
					break;

				case '8010': // 订单已存在,请更换订单号重新下单
					$msg = '订单已存在,请更换订单号重新下单';
					break;

				case '8011': // 商户未开通此银行
					$msg = '商户未开通此银行';
					break;

				case '8012': // 绑定号不存在
					$msg = '绑定号不存在';
					break;

				case '8013': // 银行卡绑卡信息不存在
					$msg = '银行卡绑卡信息不存在';
					break;

				case '8014': // 商户不存在
					$msg = '商户不存在';
					break;

				case '8015': // 短信验证码错误或已过期
					$msg = '短信验证码错误或已过期';
					break;

				case '8016': // 手机号码与下单时手机号码不一致
					$msg = '手机号码与下单时手机号码不一致';
					break;

				case '8017': // 当前银行卡不支持
					$msg = '当前银行卡不支持';
					break;

				case '8018': // 卡号与支付卡种不符
					$msg = '卡号与支付卡种不符';
					break;
				case '8019': // 产品未开通或已关闭
					$msg = '产品未开通或已关闭';
					break;

				case '8028': // 手机号码与绑定号对应的手机号码不一致
					$msg = '手机号码与绑定号对应的手机号码不一致';
					break;

				case '8030': // 只支持信用卡还款
					$msg = '只支持信用卡还款';
					break;

				case '8031': // 用户ID已绑定其他身份证号码
					$msg = '用户ID已绑定其他身份证号码';
					break;

				case '8032': // 用户ID和绑定ID已有成功绑定的记录，请核对
					$msg = '用户ID和绑定ID已有成功绑定的记录，请核对';
					break;

				case '8999': // 系统异常，请联系管理员
					$msg = '系统异常，请联系管理员';
					break;

			}

			return array('action'=> 0, 'code'=> $this->result['rt2_retCode'], 'msg'=> $msg, 'result'=> $this->result);
		}	
	}

	function getOrderId()
	{
		$this->out_order_id = date('YmdHis').mt_rand(100000, 999999);
		return $this->out_order_id;
	}

	function md5_sign()
	{
		$sign_str = '&'.implode('&', array_values($this->send_data)).'&'.$this->signkey;
		$sign = md5($sign_str);

		$this->send_data['sign'] = $sign;
		$this->sign = $sign;
	}

	// 导入RSA类
	function setRSA($crypt_rsa)
	{
		$this->crypt_rsa = $crypt_rsa;	
	}

	// rsa 签名
	function ras_sign()
	{
		$sign_str = '&'.implode('&', array_values($this->send_data));

		$this->crypt_rsa->setHash('md5');
		$this->crypt_rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
		$this->crypt_rsa->loadKey($this->rsa_signkey);

		$sign = base64_encode($this->crypt_rsa->sign($sign_str));

		$this->send_data['sign'] = $sign;
		$this->sign = $sign;
	}

	/*
	银行卡下单
	必要参数 user_id, user_name, card_number
	*/
	function QuickPayBankCardPay($params)
	{
		$this->send_data = array(
			'P1_bizType' 			=> $params['P1_bizType'],
			'P2_customerNumber' 	=> $this->customer_number,
			'P3_userId' 			=> $params['user_id'],
			'P4_orderId'  			=> $this->getOrderId(),
			'P5_timestamp' 			=> date('YmdHis'),
			'P6_payerName' 			=> $params['user_name'],
			'P7_idCardType'			=> 'IDCARD',
			'P8_idCardNo'			=> $params['id_card_number'],
			'P9_cardNo'				=> $params['bank_number'],
			'P10_year'				=> $params['bank_year'],
			'P11_month'				=> $params['bank_month'],
			'P12_cvv2'				=> $params['cvv2'],
			'P13_phone'				=> $params['user_phone'],
			'P14_currency'			=> 'CNY',
			'P15_orderAmount' 		=> round($params['money'], 2),
			'P16_goodsName'			=> $params['goods_name'],
			'P17_goodsDesc'     	=> '',
			'P18_terminalType'  	=> 'MAC',
			'P19_terminalId'		=> $params['server_mac'], // 手机序列号
			'P20_orderIp'       	=> $params['server_ip'],
			'P21_period'			=> '1',
			'P22_periodUnit'		=> 'Hour', //有效时间一小时
			'P23_serverCallbackUrl'	=> $params['callback_url'],
		);
	}

	/*
	鉴权绑卡
	params 下标参数必填
	*/
	function QuickPayBindCard($params)
	{
		$this->send_data = array(
			'P1_bizType'		=> $params['P1_bizType'],
			'P2_customerNumber'	=> $this->customer_number,
			'P3_userId'			=> $params['user_id'],
			'P4_orderId'		=> $this->getOrderId(),
			'P5_timestamp'		=> date('YmdHis'),
			'P6_payerName'		=> $params['user_name'],
			'P7_idCardType'		=> 'IDCARD',
			'P8_idCardNo'		=> $params['id_card_number'],
			'P9_cardNo'			=> $params['bank_number'],
			'P10_year'			=> $params['bank_year'],
			'P11_month'			=> $params['bank_month'],
			'P12_cvv2'			=> $params['cvv2'],
			'P13_phone'			=> $params['user_phone'],
			'P14_validateCode'  => $params['validateCode'], // 短信验证码
		);
	}

	/*
	鉴权绑卡短信
	*/
	function QuickPayBindCardValidateCode($params)
	{
		$this->send_data = array(
			'P1_bizType' 		=> $params['P1_bizType'],
			'P2_customerNumber' => $this->customer_number,
			'P3_userId' 		=> $params['user_id'],
			'P4_orderId'		=> $this->getOrderId(),
			'P5_timestamp'		=> date('YmdHis'),
			'P6_cardNo'			=> $params['bank_number'],
			'P7_phone'  		=> $params['user_phone'],
		);
	}


	/*绑卡支付短信*/
	function QuickPayBindPayValidateCode($params)
	{
		$this->send_data = array(
			'P1_bizType'		=> $params['P1_bizType'],
			'P2_customerNumber' => $this->customer_number,
			'P3_bindId'			=> $params['hlb_bindId'], // 合利宝 绑卡生成唯一ID
			'P4_userId'			=> $params['user_id'],
			'P5_orderId'		=> $this->getOrderId(),
			'P6_timestamp'		=> date('YmdHis'),
			'P7_currency'		=> 'CNY',
			'P8_orderAmount' 	=> round($params['money'], 2),
			'P9_phone'			=> $params['user_phone'],
		);
	}

	/*绑卡支付*/
	function QuickPayBindPay($params)
	{
		$this->send_data = array(
			'P1_bizType' 			=> $params['P1_bizType'],
			'P2_customerNumber' 	=> $this->customer_number,
			'P3_bindId'				=> $params['hlb_bindId'],
			'P4_userId'				=> $params['user_id'],
			'P5_orderId'			=> $this->getOrderId(),
			'P6_timestamp'			=> date('YmdHis'),
			'P7_currenc'			=> 'CNY',
			'P8_orderAmount'		=> round($params['money'], 2),
			'P9_goodsName'      	=> $params['goods_name'],
			'P10_goodsDesc'			=> $params['goods_desc'],
			'P11_terminalType'		=> 'MAC',
			'P12_terminalId'		=> $params['server_mac'],
			'P13_orderIp'			=> $params['server_ip'],
			'P14_period'			=> '1',
			'P15_periodUnit'		=> 'Hour',
			'P16_serverCallbackUrl' => $params['callback_url'],
			'P17_validateCode'		=> $params['validateCode'],
		);
	}

	/*订单查询*/
	function QuickPayQuery($params)
	{
		$this->send_data = array(
			'P1_bizType'			=> $params['P1_bizType'],
			'P2_orderId'			=> $params['out_order_id'],
			'P3_customerNumber'		=> $this->customer_number,
		);
		$this->md5_sign();
	}

	/*信用卡还款*/
	function CreditCardRepayment($params)
	{
		$this->send_data = array(
			'P1_bizType'			=> $params['P1_bizType'],
			'P2_customerNumber'		=> $this->credit_number,
			'P3_userId'				=> $params['user_id'],
			'P4_bindId'				=> $params['hlb_bindId'],
			'P5_orderId'			=> $this->getOrderId(),
			'P6_timestamp'			=> date('YmdHis'),
			'P7_currency'			=> 'CNY',
			'P8_orderAmount'		=> round($params['money'], 2),
			'P9_feeType'			=> $params['feeType'],
			'P10_summary'			=> $params['remark'],
		);

	}

	/*信用卡还款查询*/
	function TransferQuery($params)
	{
		$this->send_data = array(
			'P1_bizType'			=> $params['P1_bizType'],
			'P2_orderId'			=> $params['out_order_id'],
			'P3_customerNumber'		=> $this->customer_number,
		);
	}

	/*用户余额查询*/
	function AccountQuery($params)
	{
		$this->send_data = array(
			'P1_bizType'			=> $params['P1_bizType'],
			'P2_customerNumber'		=> $this->customer_number,
			'P3_userId'				=> $params['user_id'],
			'P4_timestamp'			=> date('YmdHis'),
		);
	}

	/*银行卡解绑*/
	function BankCardUnbind($params)
	{
		$this->send_data = array(
			'P1_bizType'			=> $params['P1_bizType'],
			'P2_customerNumber'		=> $this->customer_number,
			'P3_userId'				=> $params['user_id'],
			'P4_bindId'				=> $params['hlb_bindId'],
			'P5_orderId'			=> $this->getOrderId(),
			'P6_timestamp'			=> date('YmdHis'),
		);
	}

	function BankCardbindList($params)
	{
		$this->send_data = array(
			'P1_bizType'			=> $params['P1_bizType'],
			'P2_customerNumber'		=> $this->customer_number,
			'P3_userId'				=> $params['user_id'],
			'P4_bindId'				=> $params['hlb_bindId'],
			'P5_timestamp'			=> date('YmdHis'),
		);
	}
}	