<?php

class CaptchaValidator extends Validator
{
	const SCRIPT="";
	
	public function __construct()
	{
		
	}
	
	public function validate($value)
	{
		if ($value == null || $value == '')
			return false;	
		return (B2BCaptcha::verify($value) !== false);
	}
	
	public function genValidatorScript()
    {
    	return self::SCRIPT;
    }
	
}

?>