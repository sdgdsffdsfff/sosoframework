<?php

class AmountGreaterValidator extends Validator
{
	private $script;
	private $amount;
	
	public function AmountGreaterValidator($amountField)
	{
		$this->amount = WinRequest::getValue("$amountField");
		$this->script = "\nValidation.register('%s','%s','greater_cn','$amountField');";
	}
	
	public function validate($value)
	{
		if($value >= $this->amount)
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