<?php
/**
 * @author moonzhang (zyfunny@gmail.com)
 * @version 0.0.0.1 2008-05-11 21:30
 * 
 */
/**
 * web service -> Server Àà
 */
require_once("Tools/HessianPHP/HessianService.php");
class SOSO_Proxy_Server {
	public $mProxy;
	public function __construct($pClass='',$options=array()){
		$this->mProxy = new HessianService('',$options);
		if (is_object($pClass)) {
			$this->mProxy->registerObject($pClass);	
		}elseif (class_exists($pClass)){
			$this->mProxy->registerObject(new $pClass);
		}
		$this->mProxy->displayInfo = true;
	}
	public function service(){
		//¼æÈÝ£±.£°°æ±¾
		if(method_exists($this->mProxy,'service')) $this->mProxy->service();
		$this->mProxy->handle();
	}
	
	public function register($pClass){
		$this->mProxy->registerObject($pClass);
	}
}