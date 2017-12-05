<?php 

/**
* 
*/
class PlanController extends BaseController
{
	protected $user;

	private function getRandTime($times, $s_time, $e_time, &$arr = array())
	{
		$use_time = $e_time - $s_time;

		$tmp_s_time = 3600;

		$pay_time_tmp = mt_rand($tmp_s_time, $use_time);

		$pay_time = $s_time + $pay_time_tmp;

		if ((int)date('H', $pay_time) >= 7 && (int)date('H', $pay_time) <= 22) {
			$arr[] = $pay_time;
			if (count($arr) == $times * 100) {
				return $arr;
			} else {
				$this->getRandTime($times, $s_time, $e_time, $arr);
			}
		} else {
			$this->getRandTime($times, $s_time, $e_time, $arr);
		}
	} 

	function getAdd()
	{

		try {

			DB::beginTransaction();

			// 测试数据
			$bank_card = new stdClass();
			$bank_card->Id = 1;

			$user = new stdClass();
			$user->UserId = 82;
			$this->user = $user;
			$this->data['cash_deposit'] = 2500;
			$this->data['ratio'] = 50;
			$this->data['bank_id'] = 1;

			$pay_bank_id = 0;
			isset($this->data['pay_bank_id']) && $pay_bank_id = $this->data['pay_bank_id'];
			

			$bank_card = BankdCard::where('UserId', $this->user->UserId)->where('Id', $this->data['bank_id'])->first();

			if (!$bank_card) {
				throw new Exception("没有找到交易卡", 8996);
			}

			if ($pay_bank_id) {
				// 如果有传 保证金卡
				$pay_bank_card = BankdCard::where('UserId', $this->user->UserId)->where('Id', $this->data['pay_bank_id'])->first();

				if (!$bank_card) {
					throw new Exception("没有找到交易卡", 8996);
				}
			}
			

			// 获取手续费
			$fee = DB::table('xyk_fee')->first();

			if (!$fee) {
				throw new Exception("等待商家设置手续费", 3001);
			}
			
			$plan_start_date = $bank_card->AccountDate;
			$plan_end_date = $bank_card->RepaymentDate;

			if ( (int)date('H', strtotime($plan_start_date)) < 8) {
				$plan_s_time = strtotime(date('Y-m-d 09:00:00', strtotime($plan_start_date)));
			} else {
				$plan_s_time = strtotime($plan_start_date);
			}

			
			$plan_e_time = strtotime($plan_end_date);

			$this->user->UserId = 1;
			// 生成还款计划 次数
			$plan_time = ceil(100 / $this->data['ratio']) + 1;
			// 套现分两次
			$tao_time = 2;

			if ($fee->PlanFee * $plan_time > $this->data['cash_deposit']) {
				throw new Exception("保证金不能低于手续费", 3002);
			}

			// 时间间隔
			$plan_jiange_time = ceil(($plan_e_time - $plan_s_time) / $plan_time);

			// 金额 头尾
			$header_money = round($this->data['cash_deposit'], 2);
			$o_time = $plan_time - 1;
			$foot_money = round(($this->data['cash_deposit'] * $o_time) / $plan_time);
			$total_money = round($this->data['cash_deposit'] * $o_time, 2);
			// 生成计划

			$plan_id = Plan::insertGetId(array(
				'StartDate' => $plan_start_date,
				'EndDate'   => $plan_end_date,
				'status' => 0,
				'UserId' => $this->user->UserId,
				'BankId' => $bank_card->Id,
				'created_at' => date('Y-m-d H:i:s'),
				'TotalMoney' => $total_money, // 不含手续费 还款总额
				'CashDeposit' => $header_money, // 保证金
				'fee' => $fee->PlanFee * $plan_time,
				'PayBankId' => $pay_bank_id,
			));

			// 获取时间计时用
			// $x = 1;

			// 生成时间
			$arr = array();
			$this->getRandTime($plan_time, $plan_s_time, $plan_e_time, $arr);
			sort($arr);
			$pay_t_arr = array();

			for ($i = 0; $i < $plan_time; $i++) {
				$pt_i = $i + 1; 
				$ptk = mt_rand(100 * $i, 100 * $pt_i - 1);

				$pay_t_arr[] = $arr[$ptk];
			}

			$t_sort = 0;
			// 生成还款计划
			for ($i = 0; $i < $plan_time; $i++) { 
				// 批次
				$batch = date('YmdHis').mt_rand(100000, 999999).$i;

				if ($plan_time == 1) {
					$huan_money = $total_money;
					} else if ($i == $plan_time - 1) {
					$huan_money = $total_money;
				} else {
					$huan_money = mt_rand($foot_money, $header_money);
				}
				$t_sort++;
				// 还款计划
				$huan_item = array(
					'PlanId' => $plan_id,
					'created_at' => date('Y-m-d H:i:s'),
					'Money' => $huan_money,
					'Type' => 1,
					'status' => 0,
					'BankId' => $bank_card->Id,
					'Batch' => $batch,
					'PayTime' => date('Y-m-d H:i:s', $pay_t_arr[$i]),
					'sort' => $t_sort,
				);
				
				// 减去 计划还的金额
				$total_money = $total_money - $huan_money;

				// 还款计划
				PlanDetail::insert($huan_item);

				// 套现计划
				$tao_item_total_money = $huan_money;

				$tao_item_add = $tao_time + 1;
				$tao_header_money =  round($tao_item_total_money / $tao_time, 2);
				$tao_foot_money = round($tao_item_total_money / $tao_item_add, 2);

				// 生成两笔套现计划
				for ($j = 0; $j < $tao_time; $j++) { 

					$t_sort++;

					if ($tao_time == 1) {
						$tao_money = $tao_item_total_money;
					} else if ($j == $tao_time - 1) {
						$tao_money = $tao_item_total_money;
					} else {
						$tao_money = mt_rand($tao_foot_money, $tao_header_money);
					}
					$tao_item_total_money = $tao_item_total_money - $tao_money;

					$tao_item = array(
						'PlanId' => $plan_id,
						'created_at' => date('Y-m-d H:i:s'),
						'Money' => $tao_money,
						'Type' => 2,
						'status' => 0,
						'BankId' => $bank_card->Id,
						'Batch' => $batch,
						'sort' => $t_sort,
					);

					PlanDetail::insert($tao_item);
				}
			}

			$this->getHuanpaytime($plan_id, $tao_time, $pay_t_arr, $plan_e_time);
		
			DB::commit();
			return json_encode(array('code'=> '200', 'msg'=> '计划添加成功!'));
		} catch (Exception $e) {
			DB::rollback();
		}
			

	}
	
	private function getHuanpaytime($plan_id, $tao_time, $pay_t_arr, $plan_e_time)
	{
	
		$plan_detail = PlanDetail::where('PlanId', $plan_id)->where('Type', 1)->orderBy('sort', 'asc')->get();

		foreach ($plan_detail as $key => $value) {

			$arr = array();
			$addkey = $key + 1;

			if ($addkey == count($pay_t_arr)) {
				$this->getRandTime($tao_time, $pay_t_arr[$key], $plan_e_time, $arr);
			} else {
				$this->getRandTime($tao_time, $pay_t_arr[$key], $pay_t_arr[$addkey], $arr);
			}
			
			sort($arr);
			$huan_t_arr = array();

			for ($i = 0; $i < $tao_time; $i++) {
				$pt_i = $i + 1; 
				$ptk = mt_rand(100 * $i, 100 * $pt_i - 1);

				$huan_t_arr[] = $arr[$ptk];
			}

			for ($j=0; $j < $tao_time; $j++) { 
				PlanDetail::where('PlanId', $value->PlanId)->where('Batch', $value->Batch)->take(1)->whereNull('PayTime')->update(array(
					'PayTime' => date('Y-m-d H:i:s', $huan_t_arr[$j]),
				));
			}
			
		}


		
	}

	/**
	 * 还款计划列表
	 */
	public function getPlanlist(){
	    $offset = $this->data['offset'] ? $this->data['offset'] : '0';
	    $limit = $this->data['limit'] ? $this->data['limit'] : '20';
	    $planList = Plan::where('UserId',$this->data['userId'])->orderBy('created_at','desc')->skip($offset)->take($limit)->get();
	    
	    return array('planList'=>$planList);
	}
	
	/**
	 * 计划详情
	 */
	public function getPlandetail(){
	    $offset = $this->data['offset'] ? $this->data['offset'] : '0';
	    $limit = $this->data['limit'] ? $this->data['limit'] : '20';
	    $planId = $this->data['planId'] ? $this->data['planId'] : '1';
	    $planDetail = PlanDetail::where('PlanId',$planId)->orderBy('created_at','desc')->skip($offset)->take($limit)->get();
	     
	    return array('planDetail'=>$planDetail);
	}

}