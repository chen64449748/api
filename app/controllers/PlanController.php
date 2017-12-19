<?php 

/**
* 
*/
class PlanController extends BaseController
{
	protected $user;
	// d_time 同一天存在次数
	private function getRandTime($times, $s_time, $e_time, &$arr = array(), $end_h = 21)
	{
		$use_time = $e_time - $s_time;

		$tmp_s_time = 1;

		$pay_time_tmp = mt_rand($tmp_s_time, $use_time);

		$pay_time = $s_time + $pay_time_tmp;

		if ((int)date('H', $pay_time) >= 7 && (int)date('H', $pay_time) <= $end_h) {
			
			$arr[] = $pay_time;
			if (count($arr) == $times * 100) {
				return $pay_time;
			} else {
				$this->getRandTime($times, $s_time, $e_time, $arr);
			}
			
		} else {
			$this->getRandTime($times, $s_time, $e_time, $arr);
		}
	} 

	private function getRandTimeDay($arr, $d_time = 2, $times, $t_time = 1200)
	{
		$tmp_arr = array();
		sort($arr);
		foreach ($arr as $key => $value) {
			$tmp_arr[date('d', $value)][] = $value;
		}

		$r_arr = array();
		$tmp_time = 0;
		foreach ($tmp_arr as $tk => $tv) {
			
			$tmp_count = count($tv) - 1;
						
			for ($i=0; $i < $d_time; $i++) { 	
				if ($tmp_time == $times) {
					break;
				}

				$dk = mt_rand(0, $tmp_count);
				$flag_t = true;
				// 判断间隔时间是否小于半小时
				// foreach ($r_arr as $rval) {
				// 	$tmp_t_time = $tv[$dk] - $rval;
				// 	if ($tmp_t_time < $t_time) {
				// 		$flag_t = false;
				// 		break;
				// 	}
				// }

				if ($flag_t) {
					$r_arr[] = $tv[$dk];
					$tmp_time++;
				}
					
			}
		}

		return $r_arr;
	}

	function getRandMoney($foot_money, $header_money)
	{
		$money = mt_rand($foot_money, $header_money);

		if ($money < 1) {
			return $this->getRandMoney($foot_money, $header_money);
		} else {
			return $money;
		}
	}

	function postAdd()
	{

		try {

			DB::beginTransaction();

			// 测试数据
			// $bank_card = new stdClass();
			// $bank_card->Id = 1;

			// $user = new stdClass();
			// $user->UserId = 82;
			// $this->user = $user;
			// $this->data['total_money'] = 10000;
			// $this->data['cash_deposit'] = 2500;
			// $this->data['ratio'] = 50;
			// $this->data['bank_id'] = 1;
			// $this->data['over'] = 0;
			if (!isset($this->data['total_money'])) {
				throw new Exception("还款总额必填", 0);
			}

			if (!isset($this->data['cash_deposit'])) {
				throw new Exception("保证金必填", 0);
			}

			if (!isset($this->data['ratio'])) {
				throw new Exception("保证金比例必填", 0);
			}

			$pay_bank_id = 0;
			isset($this->data['pay_bank_id']) && $pay_bank_id = $this->data['pay_bank_id'];
			

			$bank_card = BankdCard::where('UserId', $this->user->UserId)->where('Id', $this->data['bank_id'])->first();

			if (!$bank_card) {
				throw new Exception("没有找到交易卡", 8996);
			}

			if ($bank_card->Type != 2) {
				throw new Exception("做计划的卡必须是信用卡", 8891);
			}

			if ($pay_bank_id) {
				// 如果有传 保证金卡
				$pay_bank_card = BankdCard::where('UserId', $this->user->UserId)->where('Id', $this->data['pay_bank_id'])->first();

				if (!$pay_bank_card) {
					throw new Exception("没有找到交易卡", 8996);
				}

				if ($pay_bank_card->Type != 1) {
					throw new Exception("用来收取保证金的卡必须是 借记卡（银行卡 非信用卡）", 8890);	
				}
			}
			

			// 获取手续费
			$fee = DB::table('xyk_fee')->first();
			$plan_sys = DB::table('xyk_plan_sys')->first();
			if (!$fee) {
				throw new Exception("等待商家设置手续费", 3001);
			}
			
			if (!$plan_sys) {
				throw new Exception("等待商家设置计划配置", 3005);
			}

			if (!$bank_card->AccountDate) {
				throw new Exception("该卡未设置账单日", 8980);
			}

			if (!$bank_card->RepaymentDate) {
				throw new Exception("该卡未设置还款日", 8980);
			}

			$ddate = $bank_card->RepaymentDate - $bank_card->AccountDate;
			
			$s_month = date('m');
			$e_month = intval(date('m'));
			

			if ($e_month < 10) {
				$e_month = '0'.$e_month;
			}

			if ($bank_card->AccountDate < 10) {
				$bank_card->AccountDate = '0'.$bank_card->AccountDate;
			}

			if ($bank_card->RepaymentDate < 10) {
				$bank_card->RepaymentDate = '0'.$bank_card->RepaymentDate;
			}

			$plan_start_date = date('Y-m').'-'.$bank_card->AccountDate.' 00:00:00';

			if ( $ddate < 20 ) {
				$plan_end_date = date('Y-m', strtotime('+1 month', strtotime($plan_start_date))). '-'. $bank_card->RepaymentDate.' 23:59:59';
			} else {
				$plan_end_date = date('Y-m').'-'.$bank_card->RepaymentDate.' 00:00:00';
			}


			// $plan_start_date = $bank_card->AccountDate;
			// $plan_end_date = $bank_card->RepaymentDate;
			$d_time = $plan_sys->PlanDayTimes; // 后台获取


			if (date('H') >= 15) {
				throw new Exception("请在15点以前生成计划", 3003);
			}

			$plan_s_time = strtotime($plan_start_date);
			$plan_e_time = strtotime($plan_end_date);

			if (time() >= $plan_e_time) {
				$plan_s_time = strtotime('+1 month', $plan_s_time);
				$plan_e_time = strtotime('+1 month', $plan_e_time);
			}
			
			if ($this->data['over']) {
				$plan_e_time = strtotime('+'.$this->data['over']. ' day', $plan_e_time);
			}

			if ($plan_s_time < time() && $plan_s_time > time()) {
				// 如果再还款中间
				$plan_s_time = time();
			}
			// echo date('Y-m-d', $plan_s_time);exit;
			// $plan_s_time = strtotime($plan_start_date);	# 测
			// 生成还款计划 次数
			$plan_time = floor(100 / $this->data['ratio']) + 1;
			// 套现分两次 数据库获取
			$tao_time = $plan_sys->TaoTimes;

			$plan_fee_one = $fee->PlanFee / 100 * $this->data['cash_deposit'];

			if ($plan_fee_one * $plan_time > $this->data['cash_deposit']) {
				throw new Exception("保证金不能低于手续费", 3002);
			}

			// 时间间隔
			$plan_jiange_time = ceil(($plan_e_time - $plan_s_time) / $plan_time);

			// 金额 头尾
			$header_money = round($this->data['cash_deposit'], 2);
			$o_time = $plan_time - 1;
			$foot_money = round(($this->data['cash_deposit'] * $o_time) / $plan_time, 2);
			$total_money = round($this->data['total_money'], 2);
			// 生成计划

			// 保证金费率
			$cash_deposit_fee = 0;
			// 如果有打开计划 系统支付费用
			if ($plan_sys->OpenPlanFeePay) {
				$cash_deposit_fee = $header_money * $fee->PayFee / 100;
				$cash_deposit_fee = round($cash_deposit_fee, 2);
			}
			
			$plan_id = Plan::insertGetId(array(
				'StartDate' => $plan_start_date,
				'EndDate'   => $plan_end_date,
				'status' => 6,
				'UserId' => $this->user->UserId,
				'BankId' => $bank_card->Id,
				'created_at' => date('Y-m-d H:i:s'),
				'TotalMoney' => $total_money, // 不含手续费 还款总额
				'CashDeposit' => $header_money, // 保证金
				'fee' => $plan_fee_one * $plan_time,
				'PayBankId' => $pay_bank_id,
				'SysFee' => $cash_deposit_fee, // 先设置 计划保证金支付费率
				'times' => $plan_time, // 还款次数
			));

			// 获取时间计时用
			// $x = 1;

			// 生成时间
			$arr = array();
			$this->getRandTime($plan_time, $plan_s_time, $plan_e_time, $arr);
			$pay_t_arr = $this->getRandTimeDay($arr, $d_time, $plan_time);
			sort($pay_t_arr);
			

			$plan_pay_fee_total = 0; # 接口调用费率
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
					$huan_money = $this->getRandMoney($foot_money, $header_money);
				}
				$t_sort++;

				$plan_repay_fee = 0;
				// 如果还款费率打开
				if ($plan_sys->OpenPlanFeeRepay) {
					$plan_repay_fee = $huan_money * $fee->RepayFee / 100;
					$plan_repay_fee = round($plan_repay_fee, 2);
					$plan_pay_fee_total += $plan_repay_fee;
				}

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
					'SysFee' => $plan_repay_fee,
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
						$tao_money = $this->getRandMoney($tao_foot_money, $tao_header_money);
					}
					$tao_item_total_money = $tao_item_total_money - $tao_money;

					$plan_pay_fee = 0;
					// 如果还款消费费率打开
					if ($plan_sys->OpenPlanFeeRepay) {
						$plan_pay_fee = $tao_money * $fee->PayFee / 100;
						$plan_pay_fee = round($plan_pay_fee, 2);
						$plan_pay_fee_total += $plan_pay_fee;
					}

					$tao_item = array(
						'PlanId' => $plan_id,
						'created_at' => date('Y-m-d H:i:s'),
						'Money' => $tao_money,
						'Type' => 2,
						'status' => 0,
						'BankId' => $bank_card->Id,
						'Batch' => $batch,
						'sort' => $t_sort,
						'SysFee'=> $plan_pay_fee,
					);

					PlanDetail::insert($tao_item);
				}
			}
			// 费率添加
			Plan::where('Id', $plan_id)->increment('SysFee', $plan_pay_fee_total);
			$this->getHuanpaytime($plan_id, $tao_time, $pay_t_arr, $plan_e_time, $d_time);
			$re_data = PlanDetail::where('PlanId', $plan_id)->get();
			$re_plan = Plan::where('Id', $plan_id)->first();
			DB::commit();
			return $this->cbc_encode(json_encode(array('code'=> '200', 'msg'=> '计划添加成功!', 'data'=> $re_data, 'plan'=> $re_plan, 'plan_id'=> $plan_id)));
		} catch (Exception $e) {
			DB::rollback();
			return $this->cbc_encode(json_encode(array('code'=> $e->getCode(), 'msg'=> $e->getMessage())));
		}
			

	}
	
	private function getHuanpaytime($plan_id, $tao_time, $pay_t_arr, $plan_e_time, $d_time)
	{
	
		$plan_detail = PlanDetail::where('PlanId', $plan_id)->where('Type', 1)->orderBy('sort', 'asc')->get();

		foreach ($plan_detail as $key => $value) {

			$arr = array();
			$addkey = $key + 1;

			if ($addkey == count($pay_t_arr)) {
				// 最后一笔还款 时间随意
				$this->getRandTime($tao_time, $pay_t_arr[$key], $plan_e_time, $arr, 24, 600);
				$huan_t_arr = $this->getRandTimeDay($arr, $d_time, $tao_time);
			} else {

				if (date('d', $pay_t_arr[$key]) != date('d', $pay_t_arr[$addkey])) {
					$tmp_e_time = strtotime(date('Y-m-d 23:59:59', $pay_t_arr[$key]));
				} else {
					$tmp_e_time = $pay_t_arr[$addkey];
				}
				$tmp_e_time = $pay_t_arr[$addkey];
				$this->getRandTime($tao_time, $pay_t_arr[$key], $tmp_e_time, $arr, 24, 600);
				$huan_t_arr = $this->getRandTimeDay($arr, $d_time, $tao_time);
			}
			
			sort($huan_t_arr);

			for ($j=0; $j < $tao_time; $j++) { 
				PlanDetail::where('PlanId', $value->PlanId)->where('Batch', $value->Batch)->take(1)->whereNull('PayTime')->update(array(
					'PayTime' => date('Y-m-d H:i:s', $huan_t_arr[$j]),
				));
			}
			
		}
		
	}

	// 计划确认
	public function postConfirm()
	{
		$plan_id = $this->data['plan_id'];
		try {
			Plan::where('Id', $plan_id)->update(array('status'=> 0));
			return $this->cbc_encode(json_encode(array('code'=> '200', 'msg'=> '确认成功')));
		} catch (Exception $e) {
			return $this->cbc_encode(json_encode(array('code'=> '0', 'msg'=> '确认失败')));
		}
	}


	// 终止计划
	public function postStop()
	{
		// $this->data['plan_id'] = 392;
		$plan_id = $this->data['plan_id'];
		try {
			$plan_sys = DB::table('xyk_plan_sys')->first();
			if (!$plan_sys) {
				throw new Exception("等待商家设置计划配置", 3005);
			}
			// 找到 一批结束的
			$plan_details_count = PlanDetail::where('PlanId', $plan_id)->where('status', 1)->groupBy('Batch')->select(DB::raw('count(Id) as count, Batch'))->get();
	
			// $plan_details = PlanDetail::where('PlanId', $plan_id)->where('status', 0)->get();
			$t_count = $plan_sys->TaoTimes + 1;
			$flag = true;

			// foreach ($plan_details as $tk => $tval) {
			// 	# 如果时间接近待支付20分钟 不可取消
			// }

			foreach ($plan_details_count as $key => $value) {
				if ($value->count != $t_count) {
					$flag = false;
				}
			}

			if ($flag) {
				// 计划终止
				Plan::where('Id', $plan_id)->update(array('status'=> 6));
			} else {
				// 计划正在执行中
				throw new Exception("计划正在执行中", 0);
			}
			return $this->cbc_encode(json_encode(array('code'=> '200', 'msg'=> '计划终止成功')));
		} catch (Exception $e) {
			return $this->cbc_encode(json_encode(array('code'=> '0', 'msg' => $e->getMessage())));
		}
		
	}
	// 获取可选百分比
	public function postRatio()
	{
		try {
			// $this->data['bank_id'] = 1;
			// $this->user = new stdClass();
			// $this->user->UserId = 82;

			$over = $this->data['over']; // 延后天数

			if ($over == '') {
				$over = 0;
			}

			if (!isset($this->data['bank_id'])) {
				throw new Exception("bank_id必传", 0);
			}
			$plan_sys = DB::table('xyk_plan_sys')->first();
			if (!$plan_sys) {
				throw new Exception("等待商家设置计划配置", 3005);
			}

			$fee = DB::table('xyk_fee')->first();
			if (!$fee) {
				throw new Exception("等待商家设置手续费", 3001);
			}
			$bank_card = BankdCard::where('UserId', $this->user->UserId)->where('Id', $this->data['bank_id'])->where('Type', 2)->first();

			if (!$bank_card) {
				throw new Exception("没有找到交易卡", 8996);
			}

			$ddate = $bank_card->RepaymentDate - $bank_card->AccountDate;

			$s_month = date('m');
			$e_month = intval(date('m'));
			

			if ($e_month < 10) {
				$e_month = '0'.$e_month;
			}

			if ($bank_card->AccountDate < 10) {
				$bank_card->AccountDate = '0'.$bank_card->AccountDate;
			}

			if ($bank_card->RepaymentDate < 10) {
				$bank_card->RepaymentDate = '0'.$bank_card->RepaymentDate;
			}

			$plan_start_date = date('Y-m').'-'.$bank_card->AccountDate.' 00:00:00';
			$plan_end_date = '';
			if ( $ddate < 20 ) {
				$plan_end_date = date('Y-m', strtotime('+1 month', strtotime($plan_start_date))). '-'. $bank_card->RepaymentDate.' 23:59:59';
			} else {
				$plan_end_date = date('Y-m').'-'.$bank_card->RepaymentDate.' 00:00:00';
			}

			$d_time = $plan_sys->PlanDayTimes; // 后台获取

			if (date('H') >= 15) {
				// throw new Exception("请在15点以前生成计划", 3003);
			}

			$plan_s_time = strtotime($plan_start_date);
			$plan_e_time = strtotime($plan_end_date);

			if (time() >= $plan_e_time) {
				$plan_s_time = strtotime('+1 month', $plan_s_time);
				$plan_e_time = strtotime('+1 month', $plan_e_time);
			}

			if ($over) {
				$plan_e_time = strtotime('+'.$over. ' day', $plan_e_time);
			}

			// echo date('Y-m-d H:i:s', $plan_e_time);exit;
			if ($plan_s_time < time() && $plan_s_time > time()) {
				// 如果再还款中间
				$plan_s_time = time();
			}

			// $day_diff = (int)date('d', $plan_e_time) - (int)date('d', $plan_s_time);
			$day_diff = ceil(($plan_e_time - $plan_s_time) / 86400);
			// 计算次数 如一天两次 计算结果 加1 因为同一天 差距diff为0
			$times = ($day_diff + 1) * $d_time;
			
			$top = 50;
			$ratio_arr = array();

			
			$di_rate = 0;
			if ($times <= 2) {
				$di_rate = 100;
			} else {
				$di_rate = intval(100 / ($times - 1));

				if ($di_rate % 5) {
					$di_rate = ($di_rate % 5) * 10;
				} 	
			}
			
			if ($times >= 21) {
				$ratio_arr[] = 5;
			}

			$now = $di_rate;

			// echo $now;exit;
			if ($di_rate <= 50) {

				while ($now <= $top) {
					$ratio_arr[] = $now;
					$now = $now + 10;
				}

			} else {
				$ratio_arr[] = $di_rate;
			}
			
			return $this->cbc_encode(json_encode(array('code'=> '200', 'fee'=> $fee, 'ratio'=> $ratio_arr)));

		} catch (Exception $e) {
			return $this->cbc_encode(json_encode(array('code'=> '0', 'msg'=> $e->getMessage(), 'fee'=> '', 'ratio'=> '')));
		}
		
	}

	/**
	 * 还款列表
	 */
    public function postBankplanlist(){
	    $offset = $this->data['offset'] ? $this->data['offset'] : '0';
	    $limit = $this->data['limit'] ? $this->data['limit'] : '20';
	    $bankplanList = BankdCard::select('xyk_userbinddcard.*','xyk_users.Username')
	                ->leftJoin('xyk_users','xyk_users.UserId','=','xyk_userbinddcard.UserId')
	                ->where('xyk_userbinddcard.UserId',$this->user['UserId'])
	                ->where('Type','2')
            	    ->skip($offset)
            	    ->take($limit)
            	    ->get();
	    
	    if(!$bankplanList->isEmpty()){
	        foreach ($bankplanList as $key => &$val){
	            $planInfo = array();
	            $val->havePlan = '0';
	            $planInfo = Plan::where('BankId',$val->Id)->where('status','1')->get();
	            if(!$planInfo->isEmpty()){
	                $val->havePlan = '1';
	            }
	        }
	    }
	    
	    return json_encode(array('code'=> '200', 'bankplanList'=> $bankplanList));
	    
	}
	
	/**
	 * 还款计划列表
	 */
	public function postPlanlist(){
	    $offset = $this->data['offset'] ? $this->data['offset'] : '0';
	    $limit = $this->data['limit'] ? $this->data['limit'] : '20';
	    $bankId = $this->data['bankId'];
	    
	    $planList = Plan::where('BankId',$bankId)->where('UserId', $this->user->UserId)->where('status', '<>', 6)->skip($offset)->take($limit)->get();
	    
	    return json_encode(array('code'=> '200', 'planList'=> $planList));
	}
	
	/**
	 * 计划详情
	 */
	public function postPlandetail(){
	    $planId = $this->data['planId'] ? $this->data['planId'] : '1';
	    $planDetail = PlanDetail::where('PlanId',$planId)->orderBy('created_at','desc')->get();
	    
	    if(!$planDetail->isEmpty()){
	        foreach ($planDetail as $key => &$val){
	            $val->CreditInfo = array();
	            //获取信用卡号
	            if($val->BankId != ''){
	                $val->CreditInfo = BankdCard::where('Id',$val['BankId'])->first();
	            }
	             
	        }
	    }
	    
	    return json_encode(array('code'=> '200', 'planDetail'=> $planDetail));
	}

}