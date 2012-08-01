<?php
class UrlValidator extends RegExValidator
{
	const SCRIPT="\nValidation.register('%s', '%s','homepage');";
	const REG_EXP = "/^http:\/\/([\w-]+\.)+[\w-]+(\/[\w- .\/?%&=]*)?/";
	
    public function UrlValidator()
    {
       parent::__construct(self::REG_EXP);
    }
    
    public function genValidatorScript()
    {
    	return self::SCRIPT;
    }
    
}

?>