<?php
/**
 * @author moonzhang (zyfunny@gmail.com)
 * @version 1.0 2010-05-04
 * 
 */
class SOSO_Acl extends SOSO_Base_Util_Observer {
	/**
	 * ���캯��
	 *
	 * @param SOSO_Acl_Interface $sai ʵ����
	 */
	public function __construct(SOSO_Acl_Interface $sai,$pUsername=NULL){
		parent::__construct($sai);
		if (strlen($pUsername)) {
			$this->setUser($pUsername);
		}
	}
	
	/**
	 * �ж��û��Ƿ���ĳ��������Ȩ��
	 *
	 * @return boolean
	 */
	public function isAllowed($mod, $act){
		$this->mod_name = $mod;
		$this->act_value = $act;
		if(!$this->checkParam(array('username','syscode','url', 'mod_name', 'act_value'))){
			return false;
		}
		$param = array(
			'act'	   => 'checkPermission',
			'username' => $this->username,
			'syscode'  => $this->syscode,
			'mod_name' => $this->mod_name,
			'act_value'=> $this->act_value
		);
		$res = $this->request($param);
		if($res == 'allow'){
			return true;
		}
		elseif($res == 'deny'){
			return false;
		}else{
			trigger_error('result error:'.$res);
		}
	}

	/**
	 * ȡ��ĳ���û�������Ȩ��
	 *
	 */
	public function getAllRights(){
		if(!$this->checkParam(array('username','syscode','url'))){
			return false;
		}
		$param = array(
			'act'	   => 'getUserRights',
			'username' => $this->username,
			'syscode'  => $this->syscode,
			'format'   => $this->format
		);
		$res = $this->request($param);
		return $this->parseResponse($res);
	}

	//����
	private function request($param){
		$ch = curl_init($this->url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
		$res = curl_exec($ch);
		if(curl_errno($ch)){
			trigger_error(curl_error($ch));
			return false;
		}
		return $res;
	}

	//������
	private function parseResponse($res){
		if($this->format =='json'){
			return json_decode($res);
		}
		//����xml
		$xml = @simplexml_load_string($res);
		if(!$xml){
			trigger_error('error result:'.$xml);
		}
		return $xml;
	}

	//������
	private function checkParam($arr){
		$bool = true;
		foreach($arr as $v){
			if($this->$v == ''){
				$bool = false;
				trigger_error($v.' can not be empty');
			}
		}
		return $bool;

	}
}