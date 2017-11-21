<?php
namespace Home\Controller;
use Think\Controller;

class CommonController extends Controller {
    
    protected $data = '';
    protected $key = 'xinyongka';

    protected $privateKey = "1234567812345678";  
	protected $iv = "1234567812345678"; 

	function __construct()
	{

		try {
			$data = I('post.data');



			$data = json_decode($data, 1);

			if (!$data) {
				throw new \Exception("data参数必传", 9999);
			}

		} catch (\Exception $e) {
			
			return $this->ajaxReturn(array('code'=> $e->getCode(), 'msg'=> $e->getMessage()));

		}
		

		

	}


	function encode($data)
	{
		//加密  
		$encrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $this->privateKey, $data, MCRYPT_MODE_CBC, $this->iv);  
		return base64_encode($encrypted);  
	}

	function decode($str)
	{
		//解密  
		$encryptedData = base64_decode($str);  
		$decrypted = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $this->privateKey, $encryptedData, MCRYPT_MODE_CBC, $this->iv);  
		return $decrypted; 
	}
 
}

