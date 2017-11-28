<?php 


//----------------------------------
// User部分错误code以10000开始
//  用户注册：
//      验证码错误：1001
//      手机号格式错误：1002
//      验证码发送失败：1003
//      手机号已注册：1004
//      密码格式错误：1005
//      用户注册失败：1006
//  用户登录：
//      手机号格式错误：1101
//      手机号未注册：1102
//      密码错误：1103
//      手机号码未修改：1104
//      手机号码不一致：1105
//----------------------------------

class UserController extends BaseController
{
    // 数据库添加token字段
	public function index()
	{

	}

	/**
	 * 个人中心
	 * @AuthorHTL
	 * @DateTime  2017-11-23T21:06:12+0800
	 * Typecho Blog Platform
	 * @param
	 * @return    [type]                   [description]
	 */
    public function center()
    {

    }

    /**
	 * 个人中心
	 * @AuthorHTL
	 * @DateTime  2017-11-23T21:06:12+0800
	 * Typecho Blog Platform
	 * @param string 	mobile
	 * @param string 	password
	 * @return    [type]                   [description]
	 */
    // mobile 手机号
    // password 密码
    // 
    public function login()
    {
    	$mobile = $this->data['mobile'];
    	$password = md5($this->data['password']);

    	if(preg_match("/^13[0-9]{1}[0-9]{8}$|15[0189]{1}[0-9]{8}$|189[0-9]{8}$/", $mobile)){
    		return $this->ajaxReturn(array('code'=> 1101, 'msg'=> '手机号格式错误'));
    	}

    	$user = User::where("Mobile='%s'", $mobile)
    		->find();

    	if(!$user) {
    		return $this->ajaxReturn(array('code'=> 1102, 'msg'=> '手机号未注册'));
    	}

    	if($user['Password'] != $password) {
    		return $this->ajaxReturn(array('code'=> 1102, 'msg'=> '密码错误'));
    	}

    	$session_id = session_id();
    	User::where("UserId", $user['id'])
    		->update("token='%s'", $session_id);
        $user['token'] = $session_id;

    	return $this->ajaxReturn(array('code'=> 200, 'msg'=> '登录成功', 'data'=> $user));
    }

    /**
     * 退出账号
     * @AuthorHTL
     * @DateTime  2017-11-23T21:06:34+0800
     * Typecho Blog Platform
     * @copyright [copyright]
     * @license   [license]
     * @version   [version]
     * @return    [type]                   [description]
     */
    public function logout()
    {

    }

    /**
     * 注册
     * @AuthorHTL
     * @DateTime  2017-11-23T21:06:37+0800
     * Typecho Blog Platform
     * @copyright [copyright]
     * @license   [license]
     * @version   [version]
     * @return    [type]                   [description]
     */
    public function checkin()
    {
    	$mobile = $this->data['mobile'];
    	$code = $this->data['code'];
    	$password = $this->data['password'];
        $invite = isset($this->data['invite']) ? $this->data['invite'] : '';

    	if(!$password) {
    		return $this->ajaxReturn(array('code'=> 1005, 'msg'=> '密码格式错误'));
    	}

    	if(!preg_match("/^1(3|4|5|7|8)\d{9}$/", $mobile)){
    		return $this->ajaxReturn(array('code'=> 1002, 'msg'=> '手机号格式错误'));
    	}

    	$ary = session('verify_code');
    	if ($ary[$mobile] != $code) {
    		return $this->ajaxReturn(array('code'=> 1001, 'msg'=> '验证码错误'));
    	}

    	$have_user = User::where("Mobile='%s'", $mobile)
    		->count();
    	if($have_user) {
    		return $this->ajaxReturn(array('code'=> 1004, 'msg'=> '手机已被注册'));
    	}

    	try {
            $code = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

            $rand = $code[rand(0,25)]
                .strtoupper(dechex(date('m')))
                .date('d').substr(time(),-5)
                .substr(microtime(),2,5)
                .sprintf('%02d',rand(0,99));
            $a = md5( $rand, true ),
            $s = '0123456789ABCDEFGHIJKLMNOPQRSTUV',
            $d = '',
            for ($i = 0; $i < 8; $i++) {
                $g = ord( $a[ $i ] );
                $d .= $s[ ( $g ^ ord( $a[ $i + 8 ] ) ) - $g & 0x1F ];
            }
    		$username = '用户_' . $d;

            /* 邀请人开始 */
            $inviter_id = isset($this->data['inviter_id']) ? $this->data['inviter_id'] : 0;
            $inviter_user = User::where("UserId", $inviter_id)->first();
            $first = $second = 0;
            if ($inviter_user) {
                $first = $inviter_user->id;
                $second = $inviter_user->InviterOne;
            }
            /* 邀请人结束 */

    		$userId = User::insertGetId(array(
	    		'Mobile' 	=> $mobile,
	    		'Password'  => md5($password),
	    		'Username'  => $username,
                'Status'    => 1,
                'AddTime'   => time(),
                'InviterId' => $inviter_id,
                'InviterOne'=> $first,
                'InviterTwo'=> $second
	    	));

	    	if ($userId) {
	    		return $this->ajaxReturn(array('code'=> 200, 'msg'=> '用户注册成功'));
	    	}
    		$this->ajaxReturn(array('code'=> 1006, 'msg'=> '用户注册失败'));
    	} catch (Exception $e) {
    		return $this->ajaxReturn(array('code'=> $e->getCode(), 'msg'=> $e->getMessage()));
    	}
    }

    /**
     * 发送验证码
     * @AuthorHTL
     * @DateTime  2017-11-23T21:06:42+0800
     * Typecho Blog Platform
     * @copyright [copyright]
     * @license   [license]
     * @version   [version]
     * @return    [type]                   [description]
     */
    public function getVerify()
    {
    	$mobile = $this->data['mobile'];

    	if(!preg_match("/^1(3|4|5|7|8)\d{9}$/", $mobile)){
    		return $this->ajaxReturn(array('code'=> 1002, 'msg'=> '手机号格式错误'));
    	}

    	try {
    		$code = rand(100000,999999);
    		if ($this->sendSMS($mobile, $code)) {
    			session(array('verify_code'=> compact("mobile", "code"), 'expire'=> 300));
    			return $this->ajaxReturn(array('code'=> 200, 'msg'=> '验证码发送成功'));
    		}
    		return $this->ajaxReturn(array('code'=> 1003, 'msg'=> '验证码发送失败'));
    	} catch (Exception $e) {
    		return $this->ajaxReturn(array('code'=> $e->getCode(), 'msg'=> $e->getMessage()));
    	}
    }

    /**
     * 发送验证码
     * @AuthorHTL
     * @DateTime  2017-11-23T21:06:46+0800
     * Typecho Blog Platform
     * @copyright [copyright]
     * @license   [license]
     * @version   [version]
     * @param     [type]                   $mobile [description]
     * @param     [type]                   $code   [description]
     * @return    [type]                           [description]
     */
    public function sendSMS($mobile, $code)
    {
    	return true;
    }

    /**
     * 修改个人信息
     * @AuthorHTL
     * @DateTime  2017-11-27T21:23:57+0800
     * Typecho Blog Platform
     * @copyright [copyright]
     * @license   [license]
     * @version   [version]
     * @return    [type]                   [description]
     */
    public function editInfo()
    {
        $type = $this->data['type'];
        $id = $this->data['id'];
        $name = $this->data['name'];
        $mobile = $this->data['mobile'];
        $code = $this->data['code'];
        $password = $this->data['password'];
        $pay_password = $this->data['pay_password'];

        $user = User::where("UserId", $id)->find();

        switch ($type) {
            case 'name':
                
                break;
            case 'mobile':
                if ($mobile != $user->Mobile) {
                    if(!preg_match("/^1(3|4|5|7|8)\d{9}$/", $mobile)){
                        return $this->ajaxReturn(array('code'=> 1002, 'msg'=> '手机号格式错误'));
                    }

                    $ary = session('verify_code');
                    if ($ary[$mobile] != $code) {
                        return $this->ajaxReturn(array('code'=> 1001, 'msg'=> '验证码错误'));
                    }
                } else {
                    return $this->ajaxReturn(array('code'=> 1104, 'msg'=> '手机号码未修改'));
                }
                break;
            case 'password':
                if ($mobile != $user->Mobile) {
                    return $this->ajaxReturn(array('code'=> 1105, 'msg'=> '手机号码不一致'));
                }

                $ary = session('verify_code');
                if ($ary[$mobile] != $code) {
                    return $this->ajaxReturn(array('code'=> 1001, 'msg'=> '验证码错误'));
                }
                $password = md5($password);
                break;
            case 'pay_password':
                if ($mobile != $user->Mobile) {
                    return $this->ajaxReturn(array('code'=> 1105, 'msg'=> '手机号码不一致'));
                }

                $ary = session('verify_code');
                if ($ary[$mobile] != $code) {
                    return $this->ajaxReturn(array('code'=> 1001, 'msg'=> '验证码错误'));
                }
                $pay_password = md5($pay_password);
                break;
            default:
                return $this->ajaxReturn(array('code'=> 200, 'msg'=> '信息修改成功'));
                break;
        }

        $user->update(array(
            'UserName'      => $name,
            'Mobile'        => $mobile,
            'Password'      => $password,
            'PayPassword'   => $pay_password
        ));
        return $this->ajaxReturn(array('code'=> 200, 'msg'=> '信息修改成功'));
    }

    public function idCard()
    {

    }
}