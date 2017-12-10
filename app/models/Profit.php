<?php 

/**
* 
*/
class Profit extends Eloquent
{
	protected $table = 'xyk_profit';
	public $timestamps = false;

	/**
	 * 记录收益情况
	 * @AuthorHTL
	 * @DateTime  2017-11-30T23:55:12+0800
	 * Typecho Blog Platform
	 * @copyright [copyright]
	 * @license   [license]
	 * @version   [version]
	 * @param     int                   $user_id 	充值用户
	 * @param     float                 $money   	充值金额
	 */
	public static function doProfit($user_id, $money, $content)
	{
		if (!$user_id || !$money) {
			return false;
		}

		$user = User::where("UserId", $user_id)->first();
		if (!$user) {
			return ;
		}
		User::where("UserId", $user_id)->increment('Account', $money);
		Profit::insert(array(
			"user_id"=> $user->UserId,
			"first_user_id"=> 0,
			"second_user_id"=> 0,
			"money"=> $money,
			"time"=> time(),
			"content"=> "余额充值"
		));

		$persent = Persent::first();
		if (!$persent) {
			return ;
		}

		$persent1 = $persent->persent1;
		$persent2 = $persent->persent2;
		if (!$persent1 || !$persent2) {
			return ;
		}

		$first_profit = number_format($persent1 * $money, 2, '.', '');
		$second_profit = number_format($persent2 * $money, 2, '.', '');;

		if (!User::where("UserId", $user->InviteOne)->first()) {
			return ;
		}
		User::where("UserId", $user->InviteOne)->increment('Account', $first_profit);
		Profit::insert(array(
			"user_id"=> $user->InviteOne,
			"first_user_id"=> $user_id,
			"money"=> $first_profit,
			"time"=> time(),
			"content"=> "一级分销"
		));

		if (!User::where("UserId", $user->InviteTwo)->first()) {
			return ;
		}
		User::where("UserId", $user->InviteTwo)->increment('Account', $second_profit);
		Profit::insert(array(
			"user_id"=> $user->InviteTwo,
			"first_user_id"=> $user->InviteOne,
			"second_user_id"=> $user_id,
			"money"=> $first_profit,
			"time"=> time(),
			"content"=> "二级分销"
		));
		return 1;
	}
}