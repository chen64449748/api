<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class PayRepay extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'PayRepay';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = '还款计划执行任务';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		$pay = new Pay('HLBPay');
		Plan::where('status', 1)->chunk(200, function($plans)
		{
			if (!$plans->count()) {
				$this->info('data empty');
				exit();
			}

		    foreach ($plans as $plan)
		    {	
		    	// 读取计划详情
		    	$plan_details = PlanDetail::where('PlanId', $plan->Id)->orderBy('sort', 'asc')->get();
		    	$bank_card = BankdCard::where('UserId', $plan->UserId)->where('Id', $plan->BankId)->where('status', 0)->first();
		    	if (!$bank_card) {
		    		Plan::where('Id', $plan->Id)->update(array('status'=> 5, 'res'=> '没有找到银行卡'));
		    		continue;
		    	}
		    	$continue_batch = 0;
		    	foreach ($plan_details as $key => $value) {
		    		if ($value->status == 2) {
		    			break; # 处理中状态
		    		}

		    		// 时间未到 不做处理
		    		if (strtotime($value->PayTime) > time()) {
		    			continue;
		    		}

		    		if ($value->Type == 1) {
		    			// 还款
		    			try {
		    				$repay_params = array(
					    		'user_id' => $plan->UserId,
								'hlb_bindId' => $bank_card->CreditId,
								'money' => (float)$value->Money,
								'feeType' => 'RECEIVER', // RECEIVER 收款方 自己      PAYER 付款方  用户
								'remark' => '',
					    	);

					    	$pay->repay()
					    	$pay->setParams($repay_params);
					    	// 生成账单 默认失败
					    	$bill_id = Bill::createBill(array(
								'CreditId' => $bank_card->Id,
								'UserId' => $repay_params['user_id'],
								'money' => $repay_params['money'],
								'Type' => 3, // 还款
								'bank_number' => $bank_card->CreditNumber,
								'OrderNum' => $pay->getOrderId(),
								'feeType' => $repay_params['feeType'],
								'TableId' => $plan->Id,
							));

					    	$pay->sendRequest();

					    	$result = $pay->getResult();

							if ($result['action'] != 1) { throw new Exception($result['msg'], $result['code']);}

							// 还款成功	
							Bill::billUpdate($bill_id, 'SUCCESS');
							PlanDetail::where('Id', $value->Id)->update(array('status'=> 1));
							$this->info('计划ID:'. $plan->Id.', 批次号：'.$value->Batch.'还款成功');
							$continue_batch = 0; # 用来做判断 批次号跟跳过批次号相同的 不处理
		    			} catch (Exception $e) {
		    				$this->info('计划ID:'. $plan->Id.', 批次号：'.$value->Batch.'还款失败，'.$e->getMessage());
		    				$continue_batch = $value->Batch;
		    			}
		    		} else if ($value->Type == 2) {
		    			// 消费 如果还款为成功 一直跳过
		    			if ($continue_batch == $value->Batch) {
		    				continue;
		    			}
		    			try {
		    				$sys = DB::table('xyk_sys')->first();

							if (!$sys) {
								throw new Exception("商户未配置mac地址", 3003);
							}
							// 消费
			    			$pay_params = array(
			    				'hlb_bindId' => $bank_card->CreditId,
								'user_id' => $plan->UserId,
								'money' => (float)$value->Money,
								'goods_name' => '消费',
								'goods_desc' => '消费',
								'server_mac' => $sys->mac,
								'server_ip'	 => $sys->ip,
								'callback_url' => '', // backurl
								// 'validateCode' => $this->data['validateCode'],
			    			);
			    			$pay->pay();
							$pay->setParams($pay_params);
							// 生成账单 默认失败
					    	$bill_id = Bill::createBill(array(
								'CreditId' => $bank_card->Id,
								'UserId' => $pay_params['user_id'],
								'money' => $pay_params['money'],
								'Type' => 4, // 还款消费
								'bank_number' => $bank_card->CreditNumber,
								'OrderNum' => $pay->getOrderId(),
								'feeType' => '',
								'TableId' => $plan->Id,
							));

					    	$pay->sendRequest();
							if ($result['action'] != 1) { throw new Exception($result['msg'], $result['code']);}

							if ($result['result']['rt9_orderStatus'] == 'DOING' || $result['result']['rt9_orderStatus'] == 'INIT') {
								Bill::billUpdate($bill_id, 'DOING'); //  账单修改为处理中
								// 计划修改为处理中
								PlanDetail::where('Id', $value->Id)->update(array('status'=> 2));
								$this->info('计划ID:'. $plan->Id.', 批次号：'.$value->Batch.'还款消费处理中');
							} else {
								Bill::billUpdate($bill_id, 'SUCCESS'); // 成功 
								PlanDetail::where('Id', $value->Id)->update(array('status'=> 1));
								$this->info('计划ID:'. $plan->Id.', 批次号：'.$value->Batch.'还款消费成功');
							}

		    			} catch (Exception $e) {
		    				$this->info('计划ID:'. $plan->Id.', 批次号：'.$value->Batch.'还款消费失败,'.$e->getMessage());
		    			}
		    		}

			       

		    	}

		    	// 计划详情执行完  检查是否全部是 1 是 修改计划为完成
		    	$pld = PlanDetail::where('status', '<>', 1)->where('PlanId', $plan->id)->first();
		    	
		    	if (!$pld) {
		    		Plan::where('Id', $plan->Id)->update(array('status'=> 2));
		    	}

		    }
		    
		});


		$this->info('Display this on the screen');
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			// array('example', InputArgument::REQUIRED, 'An example argument.'),
		);
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			// array('example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null),
		);
	}

}
