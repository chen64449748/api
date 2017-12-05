<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class PlanPay extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'PlanPay';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = '接收保证金';

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
		$now_date = date('Y-m-d H:i:s');
		Plan::where('status', 0)->where('StartDate', '<=', $now_date)->where('EndDate', '>=', $now_date)->chunk(200, function($plans) 
		{

			if (!$plans->count()) {
				$this->info('data empty');
				exit();
			}

			foreach ($plans as $plan) {
				// 保证金卡
				// 取余额
				try {
					DB::beginTransaction();
					$user = User::where('Id', $plan->UserId)->get();
					
					// 余额不足
					if ($user->Account < $plan->CashDeposit) {
						Plan::where('Id', $plan->Id)->update(array('status'=> 5, 'res'=> '用户余额不足，不够扣除保证金，计划执行失败'));
						DB::commit();
						continue;
					}

					//扣除余额 ， 修改计划准备中
					User::where('Id', $plan->UserId)->decrement('Account', (float)$plan->CashDeposit);
					Plan::where('Id', $plan->Id)->update(array('status'=> 1));

					DB::commit();
				} catch (Exception $e) {
					DB::rollback();
					Plan::where('Id', $plan->Id)->update(array('status'=> 5, 'res'=> $e->getMessage()));
				}

			}

		});
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
