<?php
/**
 * @author moonzhang
 * @version 2009-12-09
 * 
 * ¶ÌÐÅ±¨¾¯
 */
class SOSO_Base_Alarm_Mobile {
	public $mSOCK,
		   $mRetry = 3,
		   $mHOST = '172.16.69.34',
		   $mPORT = 29907;
	public static $instance;	
	private $appKey = "8ba933be65074686aaf7c80785e4bd81";
	/**
	 * Enter description here...
	 *
	 * @param string $host
	 * @param string $port
	 * @return SOSO_Base_Alarm_Mobile
	 */
	public function &instance($host=null,$port=null){
		if (is_null(self::$instance)) {
			self::$instance = new self($host,$port);
		}
		return self::$instance;
	}
	private function __construct($host=null,$port=null){
		$this->mHOST = is_null($host) ? $this->mHOST : $host;
		$this->mPORT = is_null($port) ? $this->mPORT : $port;
		$this->mSOCK = socket_create(AF_INET,SOCK_DGRAM,0);
	}
	
	public function sms($mobile,$msg){
		$client = new Soapclient('http://ws.tof.oa.com/MessageService.svc?wsdl');
		$client->__setSoapHeaders($this->getSoapHeader());
		$config = new stdClass();
		$config->Sender=$mobile;
		$config->Receiver=$mobile;
		$config->MsgInfo=iconv('gbk','utf-8',$msg);
		$config->Priority='Normal';		
		$ret = $client->SendSMS(array('message'=>$config));	
		return $ret->SendSMSResult;
	}
	
	public function sms_oss($mobile,$msg){
		$buffer = "SEND $mobile $msg";
		$b = socket_sendto($this->mSOCK,$buffer,strlen($buffer),0,$this->mHOST,$this->mPORT);
		if (!$b && $this->mRetry > 0) {
			$this->mRetry--;
			return $this->sms($mobile,$msg);
		}
		return $b;
	}
	
	public function dump(){
		echo socket_strerror(socket_last_error($this->mSOCK));
	}
	
	public function __destruct(){
		if ($this->mSOCK) {
			socket_close($this->mSOCK);
		}
	}
	
	private function getSoapHeader(){		
		$ns = "http://www.w3.org/2001/XMLSchema-instance";
		$nsnode = "http://schemas.datacontract.org/2004/07/Tencent.OA.Framework.Context";		
		$appkeyvar = new SoapVar("<Application_Context xmlns:i=\"{$ns}\"><AppKey xmlns=\"{$nsnode}\">{$this->appKey}</AppKey></Application_Context>",XSD_ANYXML);
		$header = new SoapHeader($ns, 'Application_Context',$appkeyvar);
		return $header;
	}
}