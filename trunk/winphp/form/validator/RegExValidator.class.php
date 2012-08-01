<?php

abstract class RegExValidator
{
	private $regExp;
	
	public function RegExValidator($regExp)
	{
		$this->regExp = $regExp;
	}
	
	public function validate($value)
	{
		if(preg_match($this->regExp, $value))
			return true;
		else
			return false;
	}
	
	abstract public function genValidatorScript();
	
}

?>