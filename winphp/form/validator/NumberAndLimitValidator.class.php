<?php

class NumberAndLimitValidator extends Validator
{
	private $script;
	private $max;
	private $min;
	
	public function NumberAndLimitValidator($min, $max)
	{
		$this->max = $max;
		$this->min = $min;
		$this->script = "\nValidation.register('%s', '%s', 'number_cn',{max:$max,min:$min});";
	}
	
	public function validate($value)
	{
		$value = Utility::SBC2DBCNubmer($value);
		if(is_numeric($value) && $value <= $this->max && $value >= $this->min)
		{
			return true;
		}
		return false;
	}
	
	public function genValidatorScript()
    {
    	return $this->script;
    }
	
}

?>