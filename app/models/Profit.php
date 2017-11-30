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
	public static function profit($user_id, $money)
	{
		$persent1 = 0.1;
		$persent2 = 0.01;
		$first_profit = number_format($persent1 * $money, 2, '.', '');
		$second_profit = number_format($persent2 * $money, 2, '.', '');;

		User::where("UserId", $user_id)->increment('Account', $money);
		$user = User::where("UserId", $user_id)->frist();
		User::where("UserId", $user->InviteOne)->increment('Account', $first_profit);
		Profit::insert(array(
			"user_id"=> $user->InviteOne,
			"money"=> $first_profit,
			"time"=> time(),
			"content"=> "一级分销"
		));

		User::where("UserId", $user->InvitTwo)->increment('Account', $second_profit);
		Profit::insert(array(
			"user_id"=> $user->InviteTwo,
			"money"=> $first_profit,
			"time"=> time(),
			"content"=> "二级分销"
		));

		Profit::insert(array(
			"user_id"=> $user->UserId,
			"first_user_id"=> $user->InviteOne,
			"second_user_id"=> $user->InviteTwo,
			"money"=> $first_profit,
			"time"=> time(),
			"content"=> "余额充值"
		));

	}
}