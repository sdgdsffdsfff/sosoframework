<?php

class MobileValidator extends RegExValidator
{
	const SCRIPT="\nValidation.register('%s','%s','mobile');";
	const REG_EXP = "/^1[35]\d{9}\$/";
	
    public function MobileValidator()
    {
       parent::__construct(self::REG_EXP);
    }
    
    public function genValidatorScript()
    {
    	return self::SCRIPT;
    }
    
}

?>