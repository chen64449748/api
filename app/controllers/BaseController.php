<?php

class BaseController extends Controller {

	/**
	 * Setup the layout used by the controller.
	 *
	 * @return void
	 */
	
	protected $data = '';
    protected $key = 'xinyongka';
    protected $user;
    protected $id_card;

    protected $privateKey = "1234567812345678";  
	protected $iv = "1234567812345678"; 

	function __construct()
	{
		$url_ary = array("/user/login", "/user/checkin", "/user/verify");

		// try {
			$this->data = Input::all();
			$token = $this->data['token'];
			if (!in_array($_SERVER['REDIRECT_URL'], $url_ary)) {
				exit();
			}
        	$this->user = User::where('token', $token)->first();
        	if ($this->user) {
        		$this->IdCard = UserContact::where("UserId", $this->user->UserId)
        			->where("CertType", 1)
        			->where("Isvalid", 1)
        			->where("IsActivated", 1)
        			->first();
        	}

		// 	if (!$data) {
		// 		throw new Exception("data参数必传", 9999);
		// 	}

		// 	$data = $this->cbc_decode($data);

		// 	$data = json_decode($data, 1);

		// 	if (isset($data['token'])) {
		// 		session_start($data['token']);
		// 	} else {
		// 		session_start();
		// 	}
			
			
		// 	$this->data = $data;

		// } catch (Exception $e) {
		// 	return json_encode(array('code'=> $e->getCode(), 'msg'=> $e->getMessage()));
		// }

	}

	protected function cbc_encode($data)
	{
		return $data;
		//加密  
		$encrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $this->privateKey, $data, MCRYPT_MODE_CBC, $this->iv);  
		return base64_encode($encrypted);  
	}

	protected function cbc_decode($str)
	{
		//解密  
		$encryptedData = base64_decode($str);  
		$decrypted = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $this->privateKey, $encryptedData, MCRYPT_MODE_CBC, $this->iv);  
		return $decrypted; 
	}

}
