<?php

class EmptyValidator extends Validator
{
	const SCRIPT="";
	
	public function EmptyValidator()
	{
		
	}
	
	public function validate($value)
	{
		return true;
	}
	
	public function genValidatorScript()
    {
    	return self::SCRIPT;
    }
    
}
?>