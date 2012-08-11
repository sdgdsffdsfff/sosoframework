<?php
/**
 * @author moonzhang (zyfunny@gmail.com)
 * @version 0.0.0.1 2008-05-11 12:06
 * 
 */
/**
 * 上下文类，基本HaspMap实现
 * 提供上下文环境，用于共享数据
 * 
 */
class SOSO_Frameworks_Context extends ArrayObject {
	private static $instance;
	/**
	 * Enter description here...
	 *
	 * @var User
	 */
	public $mCurrentUser;
	
	/**
	 * Enter description here...
	 *
	 * @return SOSO_Frameworks_Context
	 */
	public static function getInstance(){
		if (!self::$instance instanceof SOSO_Frameworks_Context) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	public function __toString(){
		return serialize($this->getIterator());
	}
	
	public function reConfigure($pHash){
		self::getInstance();
		self::$instance->__construct($pHash);
	}
	
	/**
	 * @param string $key 获得对应$key的值
	 * @return mixed
	 * 
	 */
	public function get($key){
		if ($this->offsetExists($key)) {
			return self::$instance->offsetGet($key);
		}
		return null;
	}

	/**
	 * @param string $key 键
	 * @param mixed $value 值
	 * @return boolean
	 * 
	 */
	public function set($key,$value){
		return self::$instance->offsetSet($key,$value);
	}
	
	/**
	 * @param string $key
	 * @returns boolean
	 * 
	 */
	public function isRegistered($key){
		return self::$instance->offsetExists($key);
	}

	/**
	 * @param string $key
	 * @returns mixed
	 * 
	 * Workaround for http://bugs.php.net/bug.php?id=40442
	 * 
	 */
	public function offsetExists($key) {
		return array_key_exists($key,$this);
	}
}