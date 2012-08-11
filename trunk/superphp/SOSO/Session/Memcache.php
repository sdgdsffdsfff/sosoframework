<?php
/**
 * SOSO Framework
 * 
 * @category   SOSO_Util
 * @package    SOSO_Util
 * @desc       工具类::memcache封装类
 * @copyright  Copyright (c) 2007-2008 Soso.com
 * @author moonzhang
 * @created 15-四月-2008 16:59:23
 */
/**
 * todo : multi-server surport
 *
 */
class SOSO_Session_Memcache extends SOSO_Session_Storage {

	public static $memcache_obj;
	protected static $instance;
	protected $mOptions = array('table'=>'','dataField'=>'','idField'=>'','timeField'=>'','dbIndex'=>'');
	private $mServers = array();

	public function setOptions($pOptions=array()){
		parent::setOptions(array_merge($this->mOptions,$pOptions));
		$this->mOptions = array_filter($this->mOptions);
	
		if (!isset($this->mOptions['server'])) {
			trigger_error("memcached相关配置错误,需要指定memcache的ip(server)",E_USER_WARNING);
			exit;
		}
		if (!isset($this->mOptions['port'])) {
			trigger_error("必须指定memcache server的端口(port)",E_USER_WARNING);
			exit;
		}
	}

	public function open()	{
		if (!self::$memcache_obj) {
			self::$memcache_obj = new Memcached;
			self::$memcache_obj->connect($this->mOptions['server'],$this->mOptions['port']) or die ("Could not connect to memcache");
		}
		return self::$memcache_obj;
	}

	public function close(){
		self::$memcache_obj->close();
	}

	/**
	 * 
	 * @param id
	 */
	public function read($id){
		return self::$memcache_obj->get($id);
	}

	/**
	 * 
	 * @param id
	 * @param data
	 */
	public function write($id, $data){
		if (isset($this->mOptions['lifetime'])) {
			return self::$memcache_obj->set($id,$data,false,$this->mOptions['lifetime']);
		}
		return self::$memcache_obj->set($id,$data);
	}

	/**
	 * 
	 * @param id
	 */
	public function destroy($id){
		return self::$memcache_obj->delete($id);
	}

	/**
	 * 
	 * @param lifetime
	 */
	public function gc($lifetime=null){
		return true;
	}

	public function flush(){
		return self::$memcache_obj->flush();
	}
}
?>