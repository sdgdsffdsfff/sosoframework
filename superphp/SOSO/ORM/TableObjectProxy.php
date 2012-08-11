<?php
/**
 * SOSO Framework
 * 
 * @category   SOSO
 * @package    SOSO_ORM
 * @desc Table tableObject的代理
 * @copyright  Copyright (c) 2007-2008 Soso.com
 * @author moonzhang
 * @version 1.0
 * @created 15-四月-2008 16:59:22
 */
class SOSO_ORM_TableObjectProxy extends SOSO_Proxy implements IteratorAggregate {

	public $mMapHash;

	/**
	 * 
	 * @param pClassInstance
	 * @param pUser
	 */
	public function __construct($pClassInstance, $pUser=''){
		if (!($pClassInstance instanceof SOSO_ORM_TableObject || $pClassInstance instanceof SOSO_ORM_Table)){
			throw new RuntimeException('class instance must be and instance of SOSO_ORM_Table or SOSO_ORM_TableObject', 8192,null);
		}
		parent::__construct($pClassInstance,$pUser);
		$this->mMapHash = $this->getMapHash();
	}

	public function getIterator(){
		return $this->mObject->getIterator();
	}
	
	public function __isset($k){
		return array_key_exists($k,$this->mObject->getMapHash());
	}
}
?>