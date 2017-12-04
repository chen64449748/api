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
		$plan = Plan::where("status", 1)
			->where("EndTime", ">", date('Y-m-d H:i:s'))
			->orderBy("StartTime", "desc")
			->first();
		$detail = PlanDetail::where("status", 1)
			->where("PayTime", "<", date("Y-m-d H:i:s"))
			->where("status", 0)
			->orderBy("PayTime", "asc")
			->first();
		if (!$detail) {
			Plan::where("Id", $plan->Id)->update(array('status' => 2));
			return $this->info('Display this on the screen');
		}
		if ($detail->Type == 1) {
			
		} else {

		}
		$this->info('Display this on the screen');
	}

	public function curlGet($url)
	{
		//初始化
	　　$ch = curl_init();
	　　//设置选项，包括URL
	　　curl_setopt($ch, CURLOPT_URL, $url);
	　　curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	　　curl_setopt($ch, CURLOPT_HEADER, 0);
	　　//执行并获取HTML文档内容
	　　$output = curl_exec($ch);
	　　//释放curl句柄
	　　curl_close($ch);
	　　//打印获得的数据
	　　return $output;
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
