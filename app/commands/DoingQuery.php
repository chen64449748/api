<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class DoingQuery extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'DoingQuery';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = '待查询订单查询';

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
		$page = 1;
		$pay = new Pay('HLBPay');
		$pay->payQuery();

		$fee = DB::table('xyk_fee')->first();

		$plan_sys = DB::table('xyk_plan_sys')->first();

		if (!$plan_sys) {
			$this->info("plan_sys no");exit();
		}
		if (!$fee) {
			$this->info('no fee');
			exit();
		}
		while (true) {
			$limit = ($page - 1) * 100;
			$take = 100;
			$bills = Bill::where('status', 2)->skip($limit)->take($take)->get();

			if (!$bills->count()) {
				$this->info('empty data');
				exit;
			}
			$page ++;
			foreach ($bills as $bill) {
				try {
					
					$bill_detail = BillDetail::where('BillId', $bill->Id)->first();

					$params = array(
						'out_order_id' => $bill_detail->OrderNum,
					);
					$pay->setParams($params);
					// $pay->sendRequest();
					// $result = $pay->getResult();

					//测试
					$result = array(
						'result' => array(
							'rt2_retCode' => '0000',
							'rt9_orderStatus' => 'SUCCESS',
						),
					);
					if (!$result['result']) {
						continue;
					}

					if ($result['result']['rt2_retCode'] == '8004') {
						$this->info("billid: $bill->Id , no order");
						$this->noOrder($bill, $bill_detail); // 没有找到该订单
						continue;
					}

					
					if ($result['result']['rt9_orderStatus'] == 'DOING' || $result['result']['rt9_orderStatus'] == 'INIT') {
						continue;
					} else if ($result['result']['rt9_orderStatus'] == 'SUCCESS') {
						// 成功
						$this->info("billid: $bill->Id , SUCCESS");
						$this->orderSuccess($bill, $bill_detail, $fee);
					} else if ($result['result']['rt9_orderStatus'] == 'FAILED') {
						$this->info("billid: $bill->Id , FAILED");
						$this->noOrder($bill, $bill_detail);
					}
					
					



				} catch (Exception $e) {
					
				}


			}

		}
	}

	private function noOrder($bill, $bill_detail)
	{
		// 没找到订单 或者失败
		// 修改状态
		try {
			DB::beginTransaction();
			Bill::where('Id', $bill->Id)->update(array('status', 0)); # 失败

			switch ($bill->Type) {
				case '1':
					// 充值 
					// 没动作
					break;
				case '4':
					// 还款消费 
					// 将计划详情表修改回来 0 
					PlanDetail::where('PlanId', $bill->TableId)->where('OrderNum', $bill_detail->OrderNum)->update(array('status'=> 0));
					break;
				case '5':
					// 保证金收取
					// 将计划表 状态改为  2 计划完成，等待退保证金
					Plan::where('Id', $bill->TableId)->update(array('status'=> 2));
					break;
				default:
					# code...
					break;
			}

		} catch (Exception $e) {
			
		}
	}

	private function orderSuccess($bill, $bill_detail, $fee)
	{
		// 订单成功
		try {
			DB::beginTransaction();
		
			Bill::where('Id', $bill->Id)->update(array('status'=> 1)); # 成功
				
			switch ($bill->Type) {
				case '1':
					// 充值 
					// 余额增加 要扣除手续废
					$money = $bill->Amount;
					// fenxiao
					Profit::doProfit($this->user->UserId, $money);

					$pay_fee = $money * $fee->PayFee / 100;
					$pay_fee = round($pay_fee, 2);
					$money = $money - $pay_fee;

					User::where('Id', $bill->UserId)->increment('Account', (float)$money);
					break;
				case '4':
					// 还款消费
					// 修改计划详情为成功
					// 检查计划是否完成

					// 修改
					PlanDetail::where('PlanId', $bill->TableId)->where('OrderNum', $bill_detail->OrderNum)->update(array('status'=> 1));

					// 判断 分销
		    		if ($plan_sys->OpenPlanProfit) {
						Profit::doProfit($bill->UserId, $bill->Amount);
					}

					// 检查
					$pld = PlanDetail::where('PlanId', $bill->TableId)->where('status', '<>', 1)->first();
					if (!$pld) {
						//  如果全等于1的 完成 修改为 2 计划完成 等待还保证金
						Plan::where('Id', $bill->TableId)->update(array('status'=> 2));
					}

					break;
				case '5':
					// 保证金收取
					// 修改计划为 1 保证金收取完成 计划准备
					// 将保证金不添加到余额
					// 判断 分销
		    		if ($plan_sys->OpenPlanProfit) {
						Profit::doProfit($bill->UserId, $bill->Amount);
					}
					Plan::where('Id', $bill->TableId)->update(array('status'=> 1));
					break;
				default:
					# code...
					break;
			}
			DB::commit();
		} catch (Exception $e) {
			$this->info("billid : $bill->Id, Exception : ".$e->getMessage());
			DB::rollback();
		}
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
