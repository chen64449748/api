<?php

class BaseController extends Controller {

	/**
	 * Setup the layout used by the controller.
	 *
	 * @return void
	 */
	
	protected $data = '';
    protected $key = 'xinyongka';

    protected $privateKey = "1234567812345678";  
	protected $iv = "1234567812345678"; 

	function __construct()
	{

		// jm4in9on4p4lfpn4rshj9r28v1
		// echo Session::getId(); 
		// Session::put('user', 12);

		// echo Session::get('user');exit;
		try {
			$data = Input::get('data');

			if (!$data) {
				throw new Exception("data参数必传", 9999);
			}

			$data = $this->cbc_decode($data);

			$data = json_decode($data, 1);

			if (isset($data['token'])) {
				session_start($data['token']);
			} else {
				session_start();
			}
			
			
			$this->data = $data;

		} catch (Exception $e) {
			return json_encode(array('code'=> $e->getCode(), 'msg'=> $e->getMessage()));
		}

	}

	protected function cbc_encode($data)
	{
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
