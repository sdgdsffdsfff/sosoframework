<?php
/**
 * @author moonzhang (zyfunny@gmail.com)
 * @version 0.0.1 2009-03-30 
 *
 */
require_once(dirname(__FILE__).'/../../ObjectStorage.php');
require_once(dirname(__FILE__).'/Observable.php');
class SOSO_Base_Util_Observer extends SOSO_Base_Util_Observable {
	public $subjects = array();
	public $observers = array();
	protected $storage = null;
	protected $proxy;
	private static $mStorage=null;
	
	public function __construct($object=null,$pUser='moonzhang'){
		//$this->storage = new SOSO_ObjectStorage();
		if (is_object($object) && !self::getStorage()->contains($object)) {
			self::getStorage()->attach($object);
			$this->proxy = $object;
			$tMethods = get_class_methods(get_class($object));
			$this->events = $tMethods;
		}
	}
	
	public static function getStorage(){
		if (is_null(self::$mStorage)) {
			self::$mStorage = new SOSO_ObjectStorage();
		}
		return self::$mStorage;
	}
	
	public function observe($object){
		return new self($object);
	}
	
	public function __call($method,$params=array()){
		if (($res=$this->fireEvent($method,$params)) !== false) {
			return call_user_func_array(array($this->proxy,$method),$params);	
		}
		$this->fireEvent('RunTimeException',$this->proxy,$method,$res,new Exception(sprintf('method access denied(%s::%s)',get_class($this->proxy),$method),1));
		return false;
		//return $this->proxy->Invoke($method,$params);
	}
	
	public function getProxy(){
		return $this->proxy;
	}
	
//	/**
//	 * 获得事件名称
//	 *
//	 * @param string $method
//	 * @param string $pPos before | after 
//	 */
//	private function genEventName($method,$pPos='before'){
//		return false;
//	}
//	public function afterMethod($method,$fn,$scope){
//		$tEventName = 'after'.$method;
//		if (!array_key_exists($tEventName,$this->events)) {
//			$this->addEvents($tEventName);	
//		}
//		
//		$this->on($tEventName,$fn,$scope);
//	}
//	
//	public function removeMethodListener($method,$fn,$scope){
//		
//	}
}