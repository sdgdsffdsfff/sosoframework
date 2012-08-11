<?php
class SOSO_Logger_JSONFormatter implements SOSO_Logger_IFormatter{
	
 	public function format(SOSO_Logger_Message $message){
        return json_encode($message->getArrayCopy());
    }
    
    public function setFormat($format){
    	
    }
}