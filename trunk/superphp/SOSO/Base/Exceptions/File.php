<?php
class SOSO_Base_Exceptions_File extends Exception {
	const FILE_NOT_FOUND = 1001;
	const STATUS_404     = 1002;
	const EMPTY_CONTENT  = 1003;
	const REQUEST_FAILED = 1004;
	
	public function __construct($msg,$code=1001){
		parent::__construct($msg,$code);
	}
}