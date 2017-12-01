<?php 

/**
* 
*/
class PayController extends BaseController
{
	function __construct()
	{
		header('Content-type:text/html;charset=utf-8');
	}

	function getBankcode()
	{	
		// $user_id = $this->data['user_id'];
		// $bank_number = $this->data['bank_number'];
		// $user_phone = $this->data['user_phone'];
	
		try {

			// $params = array(
			// 	'user_id' => $this->user->UserId,
			// 	'bank_number'=> $this->data['bank_number'],
			// 	'user_phone' => $this->user->Moblie,
			// );

			$params = array(
				'user_id' => '82',
				'bank_number'=> '6225768758046880',
				'user_phone'=> '18329042977',
			);

			$pay = new Pay('HLBPay');
			$pay->getBankValideteCode(); //  绑卡短信
			$pay->setParams($params);

			$pay->sendRequest();
			
			$result = $pay->getResult();
		print_r($result);exit;	
			if ($result['action'] != 1) { throw new Exception($result['msg'], $result['code']);}

			return $this->cbc_encode(json_encode(array('code'=> 1, 'msg'=> '发送成功!')));

		} catch (Exception $e) {
			
			return $this->cbc_encode(json_encode(array('code'=> 0, 'msg'=> '失败！错误代码：'.$e->getCode().','.$e->getMessage())));
		}
		


	}

	function getBankbind()
	{
		/*
		需要参数

			bank_number
			bank_year 选填 信用卡必填
			bank_month 选填 信用卡必填
			cvv2 选填 信用卡必填
			user_phone
			validateCode

			quota 选填
			account_date 选填
			repayment_date 选填
		*/

		try {
			// $params = array(
			// 	'user_id' => $this->user->UserId,
			// 	'user_name' => $this->user->Username,
			// 	'id_card_number' => $this->IDCard,
			// 	'bank_number' => $this->data['bank_number'],
			// 	'user_phone' => $this->user->Moblie,
			// 	'validateCode' => $this->data['validateCode'],
			// 	'account_date' => date('Y-m-d 00:00:00', strtotime($this->data['account_date'])),
			// 	'repayment_date' => date('Y-m-d 00:00:00', strtotime($this->data['repayment_date'])),
			// );

			$params = array(
				'user_id' => '82',
				'user_name' => '陈文越',
				'id_card_number' => '330327199312022158',
				'bank_number' => '6225768758046880',
				'user_phone'=> '18329042977',
				'validateCode' => '952313',
				'account_date' => '2017-11-28 10:00:00',
				'repayment_date' => '2017-11-29 23:59:59',
			);
			$this->data['bank_year'] = '20';
			$this->data['bank_month'] = '11';
			$this->data['cvv2'] = '449';
			$this->data['quota'] = '15000';


			isset($this->data['bank_year']) && $params['bank_year'] = $this->data['bank_year'];
			isset($this->data['bank_month']) && $params['bank_month'] = $this->data['bank_month'];
			isset($this->data['cvv2']) && $params['cvv2'] = $this->data['cvv2'];
			isset($this->data['quota']) && $params['quota'] = $this->data['quota'];
			isset($this->data['account_date']) && $params['account_date'] = $this->data['account_date'];
			isset($this->data['repayment_date']) && $params['repayment_date'] = $this->data['repayment_date'];

			$pay = new Pay('HLBPay');
			$pay->bankBind(); // 绑卡
			$pay->setParams($params);
			$pay->sendRequest();
			
			$result = $pay->getResult();

			if ($result['action'] != 1) { throw new Exception($result['msg'], $result['code']);}
print_r($result);
			if ($result['rt7_bindStatus'] == 'FAIL') {
				throw new Exception('绑卡失败，请重试', 8999);
			}

			if ($result['rt7_bindStatus'] == 'SUCCESS') {
				try {
					if (isset($this->data['bank_year'])) {
						// 贷记卡 信用卡
						$type = 2;
					} else {
						// 借记卡 银行卡
						$type = 1;
					}

					$bank_card_m = new BankdCard();

					$card_data = array(
						'bindId' => $result['rt10_bindId'],
						'bank_number' => $params['bank_number'],
						'cvv2' => $params['cvv2'],
						'quota' => $this->data['quota'],
						'type' => $type,
					);

					isset($this->data['account_date']) && $card_data['account_date'] = $this->data['account_date'];
					isset($this->data['repayment_date']) && $card_data['repayment_date'] = $this->data['repayment_date'];

					$bank_card_m->addUserCard($params['user_id'], $card_data);

					return json_encode(array('code'=> 200, 'msg'=> '添加成功'));

				} catch (Exception $e) {
					throw new Exception("数据库添加失败，请重试", 8997);
				}
			}	

		} catch (Exception $e) {
			return json_encode(array('code'=> $e->getCode(), 'msg'=> '失败：错误代码：'.$e->getCode().','.$e->getMessage()));
		}
		
	}

	function getPay()
	{
		try {

			$bank_card = BankdCard::where('UserId', $this->user->UserId)->where('Id', $this->data['bank_id'])->first();

			if (!$bank_card) {
				throw new Exception("没有找到该卡", 8996);
			}

			if (is_numeric($this->data['money'])) {
				throw new Exception("金额必须为数字", 8995);
			}

			$params = array(
				'hlb_bindId' => $bank_card->CreditId,
				'user_id' => $this->user->UserId,
				'money' => $this->data['money'],
				'goods_name' => $this->data['goods_name'],
				'goods_desc' => $this->data['goods_desc'],
				'server_mac' => $this->data['server_mac'],
				'server_ip'	 => $_SERVER['SERVER_ADDR'],
				'callback_url' => $_SERVER['HTTP_HOST']. '/', // backurl
				'validateCode' => $this->data['validateCode'],
			);

			$pay = new Pay('HLBPay');
			$pay->pay(); // 支付
			$pay->setParams($params);
			$pay->sendRequest();
			
			$result = $pay->getResult();

			if ($result['action'] != 1) { throw new Exception($result['msg'], $result['code']);}



		} catch (Exception $e) {
			return $this->cbc_encode(json_encode(array('code'=> 0, 'msg'=> '错误代码：'.$e->getCode().','.$e->getMessage())));
		}
		
	}

	function getDopay()
	{

	}

	function getPaycode()
	{
		try {

			// $bank_card = BankdCard::where('UserId', $this->user->UserId)->where('Id', $this->data['bank_id'])->first();

			// if (!$bank_card) {
			// 	throw new Exception("没有找到该卡", 8996);
			// }

			// $params = array(
			// 	'hlb_bindId' => $bank_card->CreditId,
			// 	'user_id'	=> $this->user->UserId,
			// 	'money'	=> $this->data['money'],
			// 	'user_phone' => $this->user->Moblie,
			// );

			$params = array(
				'hlb_bindId' => '12345',
				'user_id'	=> '132',
				'money'	=> '123.00',
				'user_phone' => '18320904848',
			);

			$pay = new Pay('HLBPay');
			$pay->payCode(); // 支付短信
			$pay->setParams($params);
			$pay->sendRequest();
			
			$result = $pay->getResult();
print_r($result);exit;
			if ($result['action'] != 1) { throw new Exception($result['msg'], $result['code']);}
		
			return $this->cbc_encode(json_encode(array('code'=> '200', 'msg'=> '发送成功!')));
		} catch (Exception $e) {
			return $this->cbc_encode(json_encode(array('code'=> $e->getCode(), 'msg'=> '错误代码：'.$e->getCode().','.$e->getMessage())));
		}
		
	}

	function getRepay()
	{
		try {

			$bank_card = BankdCard::where('UserId', $this->user->UserId)->where('CreditId', $this->data['bindId'])->first();

			if (!$bank_card) {
				throw new Exception("没有找到该卡", 8996);
			}

			$repay_id = Repay::insertGetId(array(
				'OrderNum' => $result['result']['rt6_orderId'],
				'Money' => $params['money'],
				'UserId' => $params['user_id'],
				'SerialNum' => $result['result']['rt7_serialNumber'],
				'BankId' => $bank_card->Id,
				'FeeType' => $params['feeType'],
				'created_at' => date('Y-m-d H:i:s'),
			));

			$params = array(
				'user_id' => 1,
				'hlb_bindId' => '12324',
				'money' => '1.00',
				'feeType' => 'RECEIVER', // RECEIVER 收款方 自己      PAYER 付款方  用户
				'remark' => '',
			);

			$pay = new Pay('HLBPay');
			$pay->repay();
			$pay->setParams($params);
			$pay->sendRequest();

			$result = $pay->getResult();

			if ($result['action'] != 1) { throw new Exception($result['msg'], $result['code']);}
	

			// 还款成功  生成套现计划
			Repay::where('Id', $repay_id)->update(array(
				'status' => 1,
			));

			

		} catch (Exception $e) {
			return json_encode(array('code'=> $e->getCode(), 'msg'=> '错误代码：'.$e->getCode().','.$e->getMessage()));		
		}
		

	}

	function getDeletebank()
	{

		try {
			// $bank_card = BankdCard::where('UserId', $this->user->UserId)->where('CreditId', $this->data['bindId'])->first();

			// if (!$bank_card) {
			// 	throw new Exception("没有找到该卡", 8996);
			// }
			
			$params = array(
				'user_id' => '1',
				'hlb_bindId' => '1234',
			);

			$pay = new Pay('HLBPay');
			$pay->unBindBank();
			$pay->setParams($params);
			$pay->sendRequest();

			$result = $pay->getResult();

			if ($result['action'] != 1) { throw new Exception($result['msg'], $result['code']);}

			BankdCard::where('Id', $bank_card->Id)->update(array(
				'status' => 2,
			));

			return Response::json(array('code'=> '200', 'msg'=> '解绑成功'));

		} catch (Exception $e) {
			return Response::json(array('code'=> $e->getCode(), 'msg'=> '错误代码：'.$e->getCode().','.$e->getMessage()));
		}

	}
}