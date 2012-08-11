<?php
interface SOSO_Logger_IProcessor{
	
	function process(SOSO_Logger_Message $record);
	
}