<?php
/**
 * @author moonzhang
 * @version 1.0
 * @created 01-ม๙ิย-2009 17:43:07
 */
class SOSO_Base_Data_DataField {
	public $dateFormat;
	public $defaultValue = '';
	public $mapping;
	public $type='auto'; //string int date?
	
	function __construct($config){
		if (is_string($config)){
			$config = array("name"=>$config);
		}
		$this->apply($config);
		if (!isset($this->type) || !$this->type){
			$this->type = 'auto';
		}
		
	}
//
//	function __destruct(){
//		global $xx;
//		$xx = is_null($xx) ? 0 : $xx;
//		echo __CLASS__." @ mapping= ".$this->mapping ." ".($xx++)." destructred\n";
//	}

	protected function apply($options){
		foreach($options as $k=>$v){
			$this->$k = $v;
		}
		return $this;
	}


}
?>