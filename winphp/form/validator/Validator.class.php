<?php

abstract class Validator
{
	abstract public function validate($value);
	
	abstract public function genValidatorScript();
	
}

?>