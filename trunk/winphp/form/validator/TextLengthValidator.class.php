<?php

class TextLengthValidator extends Validator
{
	private $max;
	private $min;
	private $script;
	
	public function TextLengthValidator($min, $max)
	{
		$this->min = $min;
		$this->max = $max;
		$this->script = "\nValidation.register('%s','%s','text',{max:$max,min:$min});";
	}
	
	public function validate($value)
	{
		if(mb_strlen($value) < $this->min || $this->max < mb_strlen($value))
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