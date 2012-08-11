<?php
class SOSO_Auth extends SOSO_Auth_Decorator {
	public function __construct($pType='isoso'){
		if ($pType instanceof SOSO_Auth_Interface) {
			$tAuth = $pType;
		}else if (class_exists($pType)){
			$tAuth = new $pType();
		}else{
			$tClass = "SOSO_Auth_".$pType;
			if (class_exists($tClass)) {
				$tAuth = new $tClass();
			}else{
				trigger_error('No auth type :'.$pType,E_USER_ERROR);
			}
		}
		parent::__construct($tAuth);
	}
	/**
	 * HMAC-MD5ÊµÏÖ
	 *
	 * @param string $key
	 * @param string $data
	 * @return string
	 */
	public function _HMAC_MD5($key, $data) {
        if (strlen($key) > 64) {
            $key = pack('H32', md5($key));
        }

        if (strlen($key) < 64) {
            $key = str_pad($key, 64, chr(0));
        }

        $k_ipad = substr($key, 0, 64) ^ str_repeat(chr(0x36), 64);
        $k_opad = substr($key, 0, 64) ^ str_repeat(chr(0x5C), 64);

        $inner  = pack('H32', md5($k_ipad . $data));
        $digest = md5($k_opad . $inner);

        return $digest;
    }
}