<?php
/**
 * @author moonzhang
 * @version 1.0
 *
 * 邮件报警
 */
class SOSO_Base_Alarm_Email {
	private $mailAction = "http://ws.tof.oa.com/MessageService.svc?wsdl";
	private $appKey = "8ba933be65074686aaf7c80785e4bd81";
	private static $instance = null;
	private $mClient;

	public function __construct(){
		$this->mClient = new SoapClient($this->mailAction);
		$this->_setSoapHeader();
	}
	public static function getInstance(){
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * changed by goodnepei, add default encoding
	 * @param string/array $pUser
	 * @param string $pSubject
	 * @param string $pBody
	 * @param string $pEncoding
	 * @param array $pAttachments,format: array($filename1=>$filepath1,$filename2=>$filepath2)
	 */
	public function sendMail($pUser,$pSubject="Alarm",$pBody="",$pEncoding="gbk", $pAttachments=array()){
		$config = new stdClass();
		if (is_array($pUser)) {
			$config->From = $pUser[0];
			$tUsers = join(";",$pUser);
		}else {
			$tArray = explode(",",$pUser);
			$config->From = array_shift($tArray);
			$tUsers = join(";",$tArray);
		}
		if (!strlen($tUsers)) {
			return;
		}
		$config->Sender = strlen(trim($config->From)) ? $config->From : "sosocom@tencent.com";
		$config->To = $tUsers;

		if("gbk"==strtolower($pEncoding)){
			$config->Title = iconv("gbk",'utf-8',$pSubject);
			$config->Content = iconv("gbk","utf-8",$pBody);
		}else{
			$config->Title = $pSubject;
			$config->Content = $pBody;
		}
		$config->Priority="Normal";
		$config->BodyFormat="Html";
		if(count($pAttachments)>0){
			$config->Attachments=$this->_getAttachmentObject($pAttachments);
		}		
        
		$tRet = $this->mClient->SendMail(array('mail'=>$config));
		return $tRet->SendMailResult;
	}
	
	/**
	 * 根据路径返回附件对象
	 * @param array $pAttachments
	 */
	private function _getAttachmentObject($pAttachments){
		$tObjects = array();		
		foreach($pAttachments as $name=>$path){			
			if(!file_exists($path)){
				continue;
			}
			if(is_int($name)){
				$name = basename($path);
			}
			$attach = new stdClass();
			$attach->FileContent=file_get_contents($path);
			$attach->FileName=$name;
			$tObjects[] = $attach;			
		}
		return $tObjects;
	}
	
	private function _setSoapHeader(){		
		$ns = "http://www.w3.org/2001/XMLSchema-instance";
		$nsnode = "http://schemas.datacontract.org/2004/07/Tencent.OA.Framework.Context";		
		$appkeyvar = new SoapVar("<Application_Context xmlns:i=\"{$ns}\"><AppKey xmlns=\"{$nsnode}\">{$this->appKey}</AppKey></Application_Context>",XSD_ANYXML);
		$header = new SoapHeader($ns, 'Application_Context',$appkeyvar);
		$this->mClient->__setSoapHeaders(array($header));
	}
}
