<?php
class SOSO_Filter_Exception extends SOSO_Exception {
	public function __construct($message){
		parent::__construct($message,111);
	}
}