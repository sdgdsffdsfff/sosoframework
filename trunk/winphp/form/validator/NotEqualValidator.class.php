<?php

class NotEqualValidator extends Validator
{
	private $script;
	private $srcValue;
	
	public function NotEqualValidator($srcValue)
	{
		$this->srcValue = $srcValue;
		$this->script = "\nValidation.register('%s', '%s', 'SelectValue');";
	}
	
	public function validate($value)
	{
		if($value == $this->srcValue)
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