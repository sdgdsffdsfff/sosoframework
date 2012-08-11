<?php

interface SOSO_Logger_IFormatter{
    
    function format(SOSO_Logger_Message $message);
	function setFormat($format);
}
