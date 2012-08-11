<?php
/**
 * @author zhangyong
 * @version 1.0 2006/12/21
 * @package Util
 *
 */
/**
 * 签名生成程序
 *
 */
class SOSO_Util_Signature {
	/**
	 * 公钥地址
	 *
	 * @var unknown_type
	 */
	private $public = 'key/pub.pem';
	/**
	 * 私钥地址
	 *
	 * @var unknown_type
	 */
	private $private = 'key/key.pem';
	private $passphrase;

	private static $instances = array();
	protected $jsoned = false;
	
	private function __construct($path,$pass='sosounion'){
		$this->public = $path .'/'. $this->public;
		$this->private = $path .'/'. $this->private;
		$this->passphrase = $pass;
	}
	
	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $keypath
	 * @return SOSO_Util_Signature
	 */
	public static function getInstance($keypath=null,$pass='sosounion'){
		$keypath = $keypath ? $keypath : dirname(__FILE__);
		if (isset(self::$instances[$keypath])){
			return self::$instances[$keypath];
		}
		if (!extension_loaded('openssl')){
			throw new RuntimeException('Extension openssl must be loaded', 1024, null);
		}
		$instance = new self($keypath,$pass);
		self::$instances[$keypath] = $instance;
		return $instance;
	} 

	/**
	 * 从文件中获得私钥。
	 * $data为需要进行签名的数据
	 */
	function generateSignature($data){
		$priv_key = $this->getpriv();
		$pkeyid = openssl_get_privatekey(array($priv_key,$this->passphrase));
		/* 计算签名*/
		openssl_private_encrypt($data,$signature,$pkeyid);
		/*释放内存*/
		openssl_free_key($pkeyid);
		return $signature;
	}

	public function encode($data){
		$str = $data;
		if (!is_string($data)){
			$this->jsoned = true;
			$str = json_encode($data);
		}
		$sig = $this->generateSignature($str);
		return base64_encode($sig);
	}
	
	public function decode($signature){
		$pub = $this->getpub();
		$pubkeyid = openssl_get_publickey($pub);
		// 试用公钥验证签名
		$ok = openssl_public_decrypt(base64_decode($signature), $new, $pubkeyid);
		// free the key from memory
		openssl_free_key($pubkeyid);
		return $this->jsoned ? json_decode($new) : $new;
	}

	public function verify($data,$signature){
		$new = $this->decode($signature);
        return $new == $data;
	}
	
	protected function getpub(){
		$fp = fopen($this->public, "r");
		$pub = fread($fp,8192);
		fclose($fp);
	//	var_dump('pkey=>'.$pub);
		return $pub;
	}
	
	protected function getpriv(){
		$fp = fopen($this->private, "r");
		$pkey = fread($fp,8192);
		fclose($fp);
	//	var_dump('pkey=>'.$pkey);
		return $pkey;
	}
}

