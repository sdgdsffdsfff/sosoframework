<?php
/**
 * @author moonzhang
 * @verion 0.1 2012-07-05
 */
abstract class SOSO_Logger_Rotating extends SOSO_Logger_Stream {
	
	protected function log(SOSO_Logger_Message $message){
		if ($this->canRotate($message)){
			$this->doRotate();
		}
		parent::log($message);
	}
	
	abstract function canRotate(SOSO_Logger_Message $message);
	abstract function doRotate();
}