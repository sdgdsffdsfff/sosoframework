<?php

class NonEmptyValidator extends Validator
{
	const SCRIPT="\nValidation.register('%s', '%s', 'noblank');";
	
	public function NonEmptyValidator()
	{
		
	}
	
	public function validate($value)
	{	
		if($value == null || trim($value) == "" || str_replace("กก","",$value) == "")
		{
		    return false;
		}
		return true;
	}
	
	public function genValidatorScript()
    {
    	return self::SCRIPT;
    }
	
}

?>