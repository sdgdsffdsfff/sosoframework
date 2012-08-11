<?php
/**
 * @author moonzhang (zyfunny@gmail.com)
 * @version 1.0 2010-05-04
 * 
 */
class SOSO_Acl extends SOSO_Base_Util_Observer {
	/**
	 * 构造函数
	 *
	 * @param SOSO_Acl_Interface $sai 实体类
	 */
	public function __construct(SOSO_Acl_Interface $sai,$pUsername=NULL){
		parent::__construct($sai);
		if (strlen($pUsername)) {
			$this->setUser($pUsername);
		}
	}
	
	/**
	 * 判断用户是否有某个操作的权限
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
	 * 取得某个用户的所有权限
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

	//请求
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

	//处理结果
	private function parseResponse($res){
		if($this->format =='json'){
			return json_decode($res);
		}
		//处理xml
		$xml = @simplexml_load_string($res);
		if(!$xml){
			trigger_error('error result:'.$xml);
		}
		return $xml;
	}

	//检查参数
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