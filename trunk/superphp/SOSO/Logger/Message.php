<?php
class SOSO_Logger_Message {

	protected $_message,$_formatted,
		$_context,$_level,$_level_name,$_category,
		$_datetime,$_extra=array(),$_pairs=array();
		
	public function __construct($category,$info,$level,$context){
		$message = is_scalar($info) ? (string)$info : json_encode($info);
		$this->setMessage($message)
			->setContext($context)
			->setLevel($level)
			->setCategory($category)
			->setDatetime(new DateTime());
	}
	
	public function getArrayCopy(){
		return array(
            'message' => trim($this->_message),
            'context' => $this->_context,
            'level' => $this->_level,
            'level_name' => $this->_level_name,
            'category' => $this->_category,
            'datetime' => $this->_datetime,
            'extra' => $this->_extra,
        ) + $this->_pairs;
	}
	
	public function setPairs($key,$value){
		$this->_pairs[$key] = $value;
		return $this;
	}

	public function setMessage($message){
		$this->_message = $message;
		return $this;
	}
	public function setContext($context){
		if(isset($context['extra'])){
			$extra = $context['extra'];
			if (!is_array($extra)) $extra = array($extra);
			$this->setExtra($extra);
			unset($context['extra']);
		}
		$this->_context = $context;
		return $this;
	}
	public function setLevel($level){
		$this->_level = $level;
		$this->_level_name = SOSO_Log::getLevelName($level);
		return $this;
	}
	public function setCategory($category){
		$this->_category = $category;
		return $this;
	}
	public function setDatetime(DateTime $dt){
		$this->_datetime = $dt;
		return $this;
	}
	
	public function setExtra(array $extra,$mergable=true){
		$this->_extra = $mergable ? array_merge($this->_extra,$extra) : $extra; 
		return $this;
	}
	
	public function setFormatted($message){
		$this->_formatted = $message;
		return $this;
	}
	public function getDatetimeString(){
		return $this->_datetimeString;
	}
	public function getFormatted(){
		return $this->_formatted ? $this->_formatted : $this->_message;
	}

	public function getMessage(){
		return $this->_message;
	}
	public function getLevel(){
		return $this->_level;
	}

	public function getContext(){
		return $this->_context;
	}
	
	public function getCategory(){
		$this->_category;
	}
	public function getDatetime(){
		return $this->_datetime;
	}
	public function getExtra(){
		return $this->_extra;
	}
	public function getLevelName(){
		return $this->_level_name;
	}
	
}