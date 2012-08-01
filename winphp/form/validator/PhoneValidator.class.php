<?php

class PhoneValidator extends RegExValidator
{
	const SCRIPT="\nValidation.register('%s','%s','phone');";
	const REG_EXP = "/^[0-9\(\)\-\s]+\$/";
	
    public function PhoneValidator()
    {
       parent::__construct(self::REG_EXP);
    }
    
    public function genValidatorScript()
    {
    	return self::SCRIPT;
    }
    
}

?>