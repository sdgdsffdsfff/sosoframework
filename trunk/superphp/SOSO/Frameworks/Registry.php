<?php
/**
 * SOSO Framework
 * 
 * @category   SOSO
 * @package    SOSO_Frameworks
 * @copyright  Copyright (c) 2007-2008 Soso.com
 * @author moonzhang
 * @version 1.0
 * @created 15-����-2008 16:59:21
 */
class SOSO_Frameworks_Registry extends ArrayObject {
	private static $instance = null;
	
	private function __clone(){
	}
	/**
	 * @param string $key ��ö�Ӧ$key��ֵ
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
	 * @param string $key ��
	 * @param mixed $value ֵ
	 * @return boolean
	 * 
	 */
	public function set($key,$value){
		return self::$instance->offsetSet($key,$value);
	}

	/**
	 * ���Registryʵ��
	 * @return SOSO_Frameworks_Registry
	 */
	public static function &getInstance(){
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
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
?>