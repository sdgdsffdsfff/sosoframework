<?php
class SOSO_Auth_Oss implements SOSO_Auth_Interface,SOSO_Interface_Runnable {
	const LOGIN_URL = 'http://passport.oa.com/modules/passport/signin.ashx?title=TencentSOC%e7%94%a8%e6%88%b7%e7%99%bb%e5%bd%95&url=';
	const SOAP_SERVER = 'http://passport.oa.com/services/passportservice.asmx?WSDL';
	private $mBackurl = '';

	public function getIdentify($key='currentUser'){
		return $this->getName($key);
	}

	/**
    * 返回当前请求用户的名称
    * @return   void
    */
	public function getName($key='currentUser'){
		return isset($_SESSION[$key]) ? $_SESSION[$key] : isset($_SESSION['currentUser']) ? $_SESSION['currentUser'] : null;
	}

	/**
    * 检查当前请求的用户是否是合法用户
    * @return   void
    */
	public function isAuthorized($key='currentUser'){
		return isset($_SESSION[$key]) && is_object($_SESSION[$key]) && isset($_SESSION[$key]->mUserID);
	}

	/**
    * 进行用户认证,认证成功返回true，否则返回false
    * @param    string $pUserName
    * @param    string $pPassword
    * @return   boolean
    */
	public function login($pUserName, $pPassword, $pBackUrl="/",$session_key='currentUser'){
		$tHost = "http://".$_SERVER['HTTP_HOST'];
		$this->mBackurl = str_ireplace($tHost,"",$pBackUrl);

		$tBackUrl = $tHost."/".__CLASS__.".php?session_key=".$session_key."&backUrl=".base64_encode($this->mBackurl);
		SOSO_Util_Util::redirect(self::LOGIN_URL . rawurlencode($tBackUrl));
		exit;
	}

	public function run(){
		$backUrl = isset($_GET['backUrl']) ? base64_decode($_GET['backUrl']) : '/';
		if (isset($_GET['ticket'])) {
			return $this->login_from_soap($_GET['ticket'],$backUrl,isset($_GET['session_key'])?$_GET['session_key']:'currentUser');
		}elseif (isset($_GET['loginParam'])){
			return $this->login_from_bin($_GET['loginParam'],$backUrl,isset($_GET['session_key'])?$_GET['session_key']:'currentUser');
		}else{
			$this->login('','',base64_decode($backUrl));
		}
	}

	protected function login_from_soap($ticket,$backUrl='/',$session_key='login_user'){
		$et = new stdClass();
		$et->encryptedTicket = $ticket;
		$tSoap = new SoapClient(self::SOAP_SERVER);
		$tResult = $tSoap->DecryptTicket($et);
		if (isset($tResult->DecryptTicketResult)) {
			if (strlen($tResult->DecryptTicketResult->LoginName)) {
				return array($session_key=>$tResult->DecryptTicketResult->LoginName,
				'ChineseName'=>(iconv('utf-8','gbk',$tResult->DecryptTicketResult->ChineseName)),
				'DeptName'=>(iconv('utf-8','gbk',$tResult->DecryptTicketResult->DeptName)));
			}
			$this->login('','',base64_decode($backUrl));
		}
	}

	protected function login_from_bin($loginParam,$backUrl,$session_key='login_user'){
		if (strlen($loginParam)) {
			$tBin = dirname(__FILE__)."/bin/getUsername";
			if (file_exists($tBin) && is_executable($tBin)) {
				$fp = popen($tBin." ".$loginParam,'r');
				$tRes = fread($fp,1024);
				pclose($fp);
				if (strlen($tRes) > 9 && strpos($tRes,'username=') !== false) {
					$_SESSION[$session_key] = trim(substr($tRes,9));
					SOSO_Session::writeClose();
					SOSO_Util_Util::redirect($backUrl);
					exit;
				}
				$this->login('','',$backUrl);
			}else{
				trigger_error("$tBin 不存在或不可调用",E_USER_ERROR);
			}
		}
	}

	/**
    * 登录退出
    * @return   boolean
    */
	public function logout($pBackUrl=null){
		
		if (stripos($pBackUrl,'http') === false) {
			$pBackUrl = preg_replace("#/+#i",'/',$pBackUrl);
			/*if (substr($pBackUrl,0,1) == '/') {
			$pBackUrl = substr($pBackUrl,1);
			}*/
			$tHost = "http://".$_SERVER['HTTP_HOST'];
			$pBackUrl = $tHost."/".get_class($this).".php?backUrl=".base64_encode($pBackUrl);
		}
		SOSO_Util_Util::redirect("http://passport.oa.com/modules/passport/signout.ashx?url=".rawurlencode($pBackUrl));
	}
}