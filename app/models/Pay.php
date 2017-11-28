<?php 

class Pay
{
	private $pay_obj;

	function __construct($class_name)
	{
		$reflection = new ReflectionClass($class_name);
		$obj = $reflection->newInstance();
		if (!$obj) {
			throw new Exception("no class");
		}

		$this->pay_obj = $obj;
	}

	function setParams($params)
	{
		$this->pay_obj->setParams($params);
	}

	function sendRequest()
	{
		$this->pay_obj->sendRequest();
	}

	function getResult()
	{
		return $this->pay_obj->getResult();
	}

	// 绑卡
	function bankBind()
	{
		$this->pay_obj->setType('bankBind');
	}

	// 绑卡短信
	function getBankValideteCode()
	{
		$this->pay_obj->setType('bankBindCode');
	}

	// 支付
	function pay()
	{
		$this->pay_obj->setType('pay');
	}

	// 支付短信
	function payCode()
	{
		$this->pay_obj->setType('payCode');
	}

	// 还款
	function repay()
	{
		$this->pay_obj->setType('repay');
	}

	// 解绑
	function unBindBank()
	{
		$this->pay_obj->setType('bankUnbind');
	}
}