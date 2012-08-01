<?php

class NonChineseValidator extends Validator
{
	const SCRIPT="\nValidation.register('%s','%s','nochinese');";
	
	public function NonChineseValidator()
	{
		
	}
	
	public function validate($value)
	{		
		if(mb_strlen($value, "UTF-8") != strlen($value))
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