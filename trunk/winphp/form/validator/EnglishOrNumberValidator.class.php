<?php

class EnglishOrNumberValidator extends RegExValidator
{
	const SCRIPT="\nValidation.register('%s','%s','englishornumber');";
	const REG_EXP = "/^[a-zA-Z0-9]+\$/";
	
    public function EnglishOrNumberValidator()
    {
       parent::__construct(self::REG_EXP);
    }
    
    public function genValidatorScript()
    {
    	return self::SCRIPT;
    }
    
}

?>