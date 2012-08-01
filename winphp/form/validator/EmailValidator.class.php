<?php

class EmailValidator extends RegExValidator
{
	const SCRIPT="\nValidation.register('%s', '%s', 'email');";
	const REG_EXP = '/^([a-zA-Z0-9\._-])+@([a-zA-Z0-9\._-])+(.[a-zA-Z0-9_-])+/';
	
    public function EmailValidator()
    {
       parent::__construct(self::REG_EXP);
    }
    
    public function genValidatorScript()
    {
    	return self::SCRIPT;
    }
    
}

?>