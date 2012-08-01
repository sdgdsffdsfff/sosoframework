<?php

class EnglishAndNumberValidator extends Validator
{
	const SCRIPT="\nValidation.register('%s','%s','englishandnumber');";
	
	public function EnglishAndNumberValidator()
	{
		
	}
	
	public function validate($value)
	{	
		if($this->isHasNumber($value) && $this->isHasEnLetter($value))
		{
			return true;
		}
		return false;
	}
	//[0-9]
	private function isHasNumber($value)
	{
		for($i=48; $i<58; $i++)
		{	
			if(strstr($value, chr($i)) !== false)
				return true;
		}
		return false;
	}
	//[A-Za-z]
	private function isHasEnLetter($value)
	{
		for($i=65; $i<123; $i++)
		{			
			if(strstr($value, chr($i)) !== false)
				return true;
		}
		return false;
	}
	
	public function genValidatorScript()
    {
    	return self::SCRIPT;
    }
    
}


?>