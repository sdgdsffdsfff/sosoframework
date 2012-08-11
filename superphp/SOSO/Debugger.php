<?php
/**
 * Debugger
 * 
 * @author moonzhang
 */

class SOSO_Debugger {
	
	protected static $s;
	private $data = array();
	
	private function __construct(){
	}
	
	public static function instance(){
		if (is_null(self::$s)) self::$s = new self(); 
		return self::$s;
	}
	
	public function __destruct(){
		$this->export();
	}
	
	public function log($line,$type){
		if(!isset($this->data[$type])) $this->data[$type] = array();

		array_push($this->data[$type],$line);
	}
	
	public function export(){
		$this->data && print_r($this->data);
		//$r = var_export($this->data,true);
		//file_put_contents("/tmp/error.log", $_SERVER['REQUEST_URI'].$r."\n\n",FILE_APPEND);
	}
}