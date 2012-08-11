<?php
abstract class SOSO_Session_Storage {
	protected $mOptions = array();
	public function __construct($options = array()){
		$this->setOptions($options);
	}
	public function setOptions($options=array()){
		if (empty($options)) {
			return $this->mOptions;
		}
		return $this->mOptions = array_merge($this->mOptions,$options);
	}
	
	public function getOptions(){
		return $this->mOptions;
	}
	public function getOption($pName){
		return $this->mOptions[$pName];
	}
	
	abstract function open();
    abstract function close();
    abstract function read($id);
    abstract function write($id,$data);
    abstract function destroy($id);
    abstract function gc($lifetime=null);
}