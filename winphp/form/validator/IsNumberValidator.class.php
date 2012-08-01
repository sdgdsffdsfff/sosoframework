<?php

class IsNumberValidator extends RegExValidator
{
	const SCRIPT="\nValidation.register('%s','%s','number_cn');";
	const REG_EXP = "/^\d+\$/";
	
    public function IsNumberValidator()
    {
       parent::__construct(self::REG_EXP);
    }
    
    public function genValidatorScript()
    {
    	return self::SCRIPT;
    }
    
    
}

?>