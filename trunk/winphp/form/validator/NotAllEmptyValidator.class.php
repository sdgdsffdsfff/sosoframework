<?php
class NotAllEmptyValidator
{
	private $fieldIds;
	private $script;
	
	public function NotAllEmptyValidator($fieldIds)
	{
		$fieldIdStr = implode("','", $fieldIds);
		$fieldIdStr = "'".$fieldIdStr."'";
		$this->script = "\nvar fieldArray = new Array($fieldIdStr);\nValidation.register('%s','%s','notallempty', fieldArray);";
		$this->fieldIds = $fieldIds;
	}
	
	public function validate($value)
	{	
		foreach($this->fieldIds as $fieldId)
		{
			if(trim(WinRequest::getValue($fieldId)) != "")
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