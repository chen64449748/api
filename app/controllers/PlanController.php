<?php 

/**
* 
*/
class PlanController extends BaseController
{
	protected $user;
	// 分段获取 除了 0 - 8 点的随机时间
	private function getRandTime($times, $s_time, $e_time, &$i = 1)
	{
		$total_time = $e_time - $s_time;

		$day_diff = (int)date('d', $e_time) - (int)date('d', $s_time);
		
		if ($day_diff == 0) {
			$sub_time = 0;
		} else if ($day_diff >= 1) {

			if ((int)date('H', $e_time) < 8) {
				$sub_time = ($day_diff - 1) * 8 * 3600;

				$sub_time = $sub_time + $e_time - strtotime(date('Y-m-d 00:00:00', $e_time));

			} else {
				$sub_time = $day_diff * 8 * 3600; //  去掉夜间的
			}

		}

		// 可用分配时间
		$use_time = $total_time - $sub_time;

		$jiange_time = floor($use_time / $times);

		$tmp_s_time = ($i - 1) * $jiange_time;
		$tmp_e_time = $i * $jiange_time;

		$tmp_pay_time = mt_rand($tmp_s_time, $tmp_e_time);
		$pay_time = $s_time + $tmp_pay_time;

		$i++;
		if ((int)date('H', $pay_time) >= 8) {
			return date('Y-m-d H:i:s', $pay_time);
		} else {
			return $this->getRandTime($times, $s_time, $e_time, $i);
		}

		
	}

	

	function getAdd()
	{

		try {

			DB::beginTransaction();
			// $bank_card = BankdCard::where('UserId', $this->user->UserId)->where('Id', $this->data['bank_id'])->first();

			// if (!$bank_card) {
			// 	throw new Exception("没有找到该卡", 8996);
			// }

			// 测试数据
			$bank_card = new stdClass();
			$bank_card->Id = 1;

			$user = new stdClass();
			$user->UserId = 1;
			$this->user = $user;

			$this->data['cash_deposit'] = 2500;
			$this->data['ratio'] = 25;
			$plan_start_date = '2017-11-28 10:00:00';
			$plan_end_date = '2017-11-29 10:00:00';

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
				'TotalMoney' => $total_money,
			));

			// 获取时间计时用
			$x = 1;

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
				// 还款计划
				$huan_item = array(
					'PlanId' => $plan_id,
					'created_at' => date('Y-m-d H:i:s'),
					'Money' => $huan_money,
					'Type' => 1,
					'status' => 0,
					'BankId' => $bank_card->Id,
					'Batch' => $batch,
				);
				// 减去 计划还的金额
				$total_money = $total_money - $huan_money;

				$huan_item['PayTime'] = $this->getRandTime($plan_time, $plan_s_time, $plan_e_time, $x);

				// 还款计划
				PlanDetail::insert($huan_item);

				// 套现计划
				$tao_item_total_money = $huan_money;

				$tao_item_add = $tao_time + 1;
				$tao_header_money =  round($tao_item_total_money / $tao_time, 2);
				$tao_foot_money = round($tao_item_total_money / $tao_item_add, 2);

				
				// 生成两笔套现计划
				for ($j = 0; $j < $tao_time; $j++) { 

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
					);

					PlanDetail::insert($tao_item);
				}
			}

			$plan_detail = PlanDetail::where('PlanId', $plan_id)->orderBy('PayTime', 'asc')->get();
			// 给套现分配计划 时间
			$tao_s_time = 0;
			$tao_e_time = 0;
			// 第一次循环为了获取第一次时间区间
			foreach ($plan_detail as $key => $value) {
				if ($value->PayTime) {
					
					if (!$tao_s_time && !$tao_e_time) {
						$tao_s_time = strtotime($value->PayTime);	
					} else if ($tao_s_time && !$tao_e_time) {
						$tao_e_time = strtotime($value->PayTime);
					} else if ($tao_s_time && $tao_e_time) {
						break;
					}
				}
			}

			// 套现随机获取时间 计数
			$y = 1;
			// 第二次  分配时间
			foreach ($plan_detail as $k_t => $v_t) {

				if ($v_t->PayTime) {
					if (strtotime($v_t->PayTime) > $tao_e_time) {
						$tao_s_time = $tao_e_time;
						$tao_e_time = strtotime($v_t->PayTime);
					}
				} else {

					// 分配套现时间
					$pay_time = $this->getRandTime($tao_time, $tao_s_time, $tao_e_time, $y);
					PlanDetail::where('Id', $v_t->Id)->update(array('PayTime'=> $pay_time));

					$tao_s_time = strtotime($pay_time);
				}
			}


			Plan::where('Id', $plan_id)->update(array('status'=> 1));

			DB::commit();
			return Response::json(array('code'=> '200', 'msg'=> '添加成功'));
		} catch (Exception $e) {
			DB::rollback();
			return Response::json(array('code'=> $e->getCode(), 'msg'=> $e->getMessage()));
		}
		

	}

}