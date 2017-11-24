<?php 

/**
* 
*/
class PayController extends BaseController
{
	function getBankcode()
	{	
		// $user_id = $this->data['user_id'];
		// $bank_number = $this->data['bank_number'];
		// $user_phone = $this->data['user_phone'];
	
		try {
			$params = array(
				'user_id' => '10000001',
				'bank_number'=> '315251115151561',
				'user_phone' => '18329042977'
			);

			$pay = new Pay('HLBPay');
			$pay->getBankValideteCode(); //  绑卡短信
			$pay->setParams($params);

			$pay->sendRequest();
			
			$result = $pay->getResult();
			
			if ($result['action'] != 1) { throw new Exception($result['msg'], $result['code']);}

		} catch (Exception $e) {
			
			return Response::json(array('code'=> 1, 'msg'=> '错误代码：'.$e->getCode().','.$e->getMessage()));
		}
		


	}

	function getBankbind()
	{

		try {
			$params = array(
				'user_id' => '1',
				'user_name' => '陈文越',
				'id_card_number' => '330327199312022158',
				'bank_number' => '1564674656',
				'bank_year'	=> '2017',
				'bank_month' => '09',
				'cvv2'	=> '1545',
				'user_phone' => '18329042977',
				'validateCode' => '1242',
			);

			$pay = new Pay('HLBPay');
			$pay->bankBind(); // 绑卡
			$pay->setParams($params);
			$pay->sendRequest();
			
			$result = $pay->getResult();

			if ($result['action'] != 1) { throw new Exception($result['msg'], $result['code']);}

		} catch (Exception $e) {
			return Response::json(array('code'=> 1, 'msg'=> '错误代码：'.$e->getCode().','.$e->getMessage()));
		}
		
	}

	function getPay()
	{
		try {
			$params = array(

			);

			$pay = new Pay('HLBPay');
			$pay->pay(); // 支付
			$pay->setParams($params);
			$pay->sendRequest();
			
			$result = $pay->getResult();

			if ($result['action'] != 1) { throw new Exception($result['msg'], $result['code']);}
		} catch (Exception $e) {
			return Response::json(array('code'=> 1, 'msg'=> '错误代码：'.$e->getCode().','.$e->getMessage()));
		}
		
	}

	function getPaycode()
	{
		try {
			$params = array(
				'hlb_bindId' => '123123',
				'user_id'	=> '1',
				'money'	=> '1.00',
				'user_phone' => '18329042977',
			);

			$pay = new Pay('HLBPay');
			$pay->payCode(); // 支付短信
			$pay->setParams($params);
			$pay->sendRequest();
			
			$result = $pay->getResult();

			if ($result['action'] != 1) { throw new Exception($result['msg'], $result['code']);}
		} catch (Exception $e) {
			return Response::json(array('code'=> 1, 'msg'=> '错误代码：'.$e->getCode().','.$e->getMessage()));
		}
		
	}
}