<?php

class SameValidator extends Validator
{
	private $script;
	private $otherValue;
	
	public function SameValidator($otherFieldName)
	{
		$this->otherValue = WinRequest::getValue("$otherFieldName");
		$this->script = "\nValidation.register('%s','%s','sameas','$otherFieldName');";
	}
	
	public function validate($value)
	{
		if($value != $this->otherValue)
		{
		    return false;
		}
		return true;
	}
	
	public function genValidatorScript()
    {
    	return $this->script;
    }
	
}

?>