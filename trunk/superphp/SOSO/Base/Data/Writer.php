<?php
/**
 * @author moonzhang (zyfunny@gmail.com)
 * @version 1.0
 * 
 * delegation
 */
class SOSO_Base_Data_Writer implements IteratorAggregate{
	/**
	 * Enter description here...
	 *
	 * @var SOSO_ObjectStorage
	 */
	private $proxy ;
	
	public function __construct($writer=null){
		$this->proxy = new SOSO_ObjectStorage();
		$this->delegate($writer);
	}
	public function __destruct(){
		$this->proxy = null;
	}
	public function delegate($writer,$append=false){
		if (is_object($writer) && method_exists($writer,'save')) {
			//$this->proxy = new SOSO_ObjectStorage();
			if (!$append && $this->proxy->count()) {
				$this->proxy->clear();
			}
			$this->proxy->attach($writer);
		}
	}
	
	public function getIterator(){
		return $this->proxy;
	}
	
	public function getWriter(){
		return $this->proxy;
	}
	/**
	 * ±£´æÊı¾İ
	 *
	 * @param mixed $data
	 * @param mixed $id
	 * @param SOSO_Base_Data_Record $rec
	 */
	public function save($data,$id,$rec){
		if ($this->proxy->count()) {
			foreach ($this->proxy as $writer){
				$writer->save($data,$id,$rec);
			}
		}
	}
}