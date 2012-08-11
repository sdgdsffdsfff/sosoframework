<?php
/**
 * SOSO Framework
 * 
 * @category   SOSO
 * @package    SOSO
 * @desc 代理类
 * @copyright  Copyright (c) 2007-2008 Soso.com
 * @author moonzhang
 * @version 1.0
 * @created 15-四月-2008 16:59:22
 */
class SOSO_Proxy{

	public $mObject = null;
	public $mCurrentUser = null;

	/**
	 * 
	 * @param Object Instance
	 * @param Object User
	 */
	public function __construct($pClassInstance, $pUser){
		$this->mObject = $pClassInstance;
		$this->mCurrentUser = $pUser;
	}
	
	public function __call($method,$args=array()){
		if (extension_loaded('Reflection')) {
			$tReflection = new ReflectionObject($this->mObject);
			try{
				$tMethod = $tReflection->getMethod($method);
			}catch (Exception $e){
				return false;
			}
			if ($tMethod->isPublic()) {
				try{
					return $tMethod->invokeArgs($this->mObject,$args);
				}catch (Exception $e){
					return call_user_func_array(array($this->mObject,$method),$args);
				}
			}
		}else{
			return call_user_func_array(array($this->mObject,$method),$args);
		}
		return false;
	}
	
	/**
	 * 
	 * @param method
	 * @param parameters
	 */
	public function Invoke($method, $parameters=array()){
		return call_user_func_array(array($this->mObject,$method),$parameters);
	}

	/**
	 * 
	 * @param member
	 * @param value
	 */
	public function __set($member, $value){
		$this->mObject->$member = $value;
	}

	/**
	 * 
	 * @param member
	 */
	public function __get($member){
		return $this->mObject->_get($member);
	}
}
?>