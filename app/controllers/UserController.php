<?php 

class UserController extends BaseController
{
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

    	$user = M("xyk_users")
    		->where("Mobile='%s'", $mobile)
    		->find();

    	if(!$user) {
    		return $this->ajaxReturn(array('code'=> 1102, 'msg'=> '手机号未注册'));
    	}

    	if($user['Password'] != $password) {
    		return $this->ajaxReturn(array('code'=> 1102, 'msg'=> '密码错误'));
    	}

    	$session_id = session_id();
    	M("xyk_users")
    		->where("id=%d", $user['id'])
    		->save("token='%s'", $session_id);
    	$user['token'] = $session_id;
    	session(array('user'=> $user, 'expire'=> 30*24*3600));
    	return $this->ajaxReturn(array('code'=> 200, 'msg'=> '登录成功'));
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

    	$have_user = M("xyk_users")
    		->where("Mobile='%s'", $mobile)
    		->count();
    	if($have_user) {
    		return $this->ajaxReturn(array('code'=> 1004, 'msg'=> '手机已被注册'));
    	}

    	try {
    		$username = 'user_' . date('YmdHis') . rand(1000,9999);
    		$user = M("xyk_users")->add(array(
	    		'Mobile' 	=> $mobile,
	    		'Password'  => md5($password),
	    		'Username'  => $username
	    	));
	    	if($user) {
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
}