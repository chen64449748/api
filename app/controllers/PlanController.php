<?php 

/**
* 
*/
class PlanController extends BaseController
{
	protected $user;
	function getAdd()
	{

		try {
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
			$this->data['start_time'] = '2017-11-28 10:00:00';
			$this->data['end_time'] = '2017-11-29 10:00:00';
			$this->user->UserId = 1;
			// 生成还款计划 次数
			$plan_time = ceil(100 / $this->data['ratio']) + 1;
			// 套现分两次
			$tao_time = 2;

			// 金额 头尾
			$header_money = round($this->data['cash_deposit'], 2);
			$o_time = $plan_time - 1;
			$foot_money = round(($this->data['cash_deposit'] * $o_time) / $plan_time);
			$total_money = round($this->data['cash_deposit'] * $o_time, 2);
			// 生成计划

			$plan_id = Plan::insertGetId(array(
				'StartTime' => $this->data['start_time'],
				'EndTime'   => $this->data['end_time'],
				'status' => 0,
				'UserId' => $this->user->UserId,
				'BankId' => $bank_card->Id,
				'created_at' => date('Y-m-d H:i:s'),
				'TotalMoney' => $total_money,
			));

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


				// 套现计划
				$tao_item_total_money = $huan_money;

				$tao_item_add = $tao_time + 1;
				$tao_header_money =  round($tao_item_total_money / $tao_time, 2);
				$tao_foot_money = round($tao_item_total_money / $tao_item_add, 2);

				PlanDetail::insert($huan_item);
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

			Plan::where('Id', $plan_id)->update(array('status'=> 1));

			return Response::json(array('code'=> '200', 'msg'=> '添加成功'));
		} catch (Exception $e) {
			return Response::json(array('code'=> $e->getCode(), 'msg'=> $e->getMessage()));
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