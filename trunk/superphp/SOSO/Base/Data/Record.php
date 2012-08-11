<?php
/**
 * @author moonzhang (zyfunny@gmail.com)
 * @version 0.0.1 2009-03-28
 * @created 01-六月-2009 17:43:08
 * 
 * 记录类,每个记录对象表示一条数据
 */
class SOSO_Base_Data_Record {
	
	public $dirty = false;
	public $error;
	public $filtering = false;
	public $modified;
	/**
	 * SOSO_Base_Data_Store
	 *
	 * @var SOSO_Base_Data_Store
	 */
	public $store;
	public $data;
	/**
	 * Enter description here...
	 *
	 * @var SOSO_Base_Data_Collection
	 */
	public $fields;
	public $id;
	public static $AUTO_ID=1000;
	
	private function __construct($data=null,$id=null){
		if (!is_null($data)){
			$this->id = (!is_null($id) && is_numeric($id)) ? $id : ++SOSO_Base_Data_Record::$AUTO_ID;
			$this->data = $data;
		}
	}
	public function __destruct(){
		$this->store = null;
		$this->fields = null;
		$this->data = null;
	}
	public function getData(){
		return $this->data;	
	}
	
	/**
	 * Enter description here...
	 *
	 * @param mixed $data
	 * @param int $id
	 * @return SOSO_Base_Data_Record
	 */
	public function instance($data,$id=null){
		$dest = clone($this);
		$dest->data = $data;
		$dest->id = (!is_null($id) && is_numeric($id)) ? $id : ++SOSO_Base_Data_Record::$AUTO_ID;
		// modified @ 2009-6-7 2:17 
		//返回对象副本，解决所有id都相同的问题。。。。
		return $dest;
	}
	public static function getKeyFn($v){
		return $v->name;
	}
	/**
	 * 
	 * @param opt
	 * @return SOSO_Base_Data_Collection
	 */
	public static function create($opt){
		$instance = new self();
		$instance->fields = new SOSO_Base_Data_Collection(array('SOSO_Base_Data_Record','getKeyFn'));
		for($i=0,$len=count($opt);$i<$len;$i++){
			$instance->fields->add(new SOSO_Base_Data_DataField($opt[$i]));
		}
		return $instance;
	}
	
	public function getField($name){
		if (isset($this->fields) && $this->fields instanceof Base_Data_Collection){
			return $this->fields->get($name);
		}
		return null;	
	}
	
	/**
	 * 
	 * @param store
	 */
	public function join($store){
		$this->store = $store;
	}

	/**
	 * 
	 * @param key
	 * @param value
	 */
	public function set($key, $value){
		//$this-
	}

	/**
	 * 
	 * @param key
	 */
	public function get($key){
		return isset($this->data[$key]) ? $this->data[$key] : null;
	}

	/**
	 * 
	 * @param silent
	 */
	public function commit(){
		if ($this->store instanceof SOSO_Base_Data_Store) {
			$this->store->commit($this->data,$this->id,$this);	
		}
	}
}
?>