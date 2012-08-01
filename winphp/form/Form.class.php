<?php

class Form
{
	const VALIDATOR = "validator";
	const ONLY_AVAILIABLE = "onlyAvailable";
	const ERROR_MSG = "errorMsg";
	const ERROR_MSG_STYLE = "&nbsp;&nbsp;%s&nbsp;&nbsp;";
	
    private $fieldValidators;
	private $validateResults;
	private $fieldValues;
	private $formHasRun;
	private $errorMsgs;
	private $validateFlag;
	
	public function Form()
	{
		$this->fieldValidators = array();
		$this->validateResults = array();
		$this->fieldValues = array();
		$this->errorMsgs = array();
	}
	
	public function registerField($fieldName)
	{
		$this->registerFieldValidator($fieldName, new EmptyValidator(), "");
	}
	
	public function registerFieldValidator($fieldName, $validatorObject, $errorMsg, $onlyAvailable = false)
	{
		$this->fieldValidators[]["$fieldName"] = array(self::VALIDATOR=>$validatorObject, self::ERROR_MSG=>$errorMsg, self::ONLY_AVAILIABLE=>$onlyAvailable);
	}
	
	public function validate()
	{
		$validateStatus = true;
		if(empty($this->fieldValidators) == false && is_array($this->fieldValidators))
		{
			foreach ($this->fieldValidators as $validators) 
			{
				$validateTmp = $this->doValidate($validators);
				if($validateTmp == false)
					$validateStatus = false;
			}
		}
		return $validateStatus;
	}
	
	private function doValidate($validators)
	{
		$resultFlag = true;
		foreach($validators as $fieldName => $validateInfos) 
		{
			if($this->hasErrorMsg($fieldName) == false)
			{
				$validatorObject = $validateInfos[self::VALIDATOR];
				$onlyAvailable = $validateInfos[self::ONLY_AVAILIABLE];
				$fieldValue = WinRequest::getValue($fieldName);
				if($fieldValue == "" && $onlyAvailable == true)
				{
					$validateResult = true;
				}
				else
				{
					$validateResult = $validatorObject->validate($fieldValue);					
				}
				if($validateResult == false)
				{
					$resultFlag = false;
					$this->setErrorMsg($fieldName, $validateInfos[self::ERROR_MSG]);
				}
				$this->validateResults["$fieldName"] = $validateResult;
				$this->fieldValues["$fieldName"] = $fieldValue;
			}
		}
		return $resultFlag;
	}

	public function getValidateResults()
	{
		return $this->validateResults;
	}
	
	public function getFieldValues()
	{
		return $this->fieldValues;
	}
	
	public function getFiledValue($filedName)
	{
		return $this->fieldValues["$filedName"];
	}
	
	public function getErrorMsg($fieldName)
	{
		if($this->errorMsgs["$fieldName"] != null && $this->errorMsgs["$fieldName"] != "")
		{
			$adminSiteUrl = Config::getConfig("adminsite_url");
			return sprintf(self::ERROR_MSG_STYLE,  $this->errorMsgs["$fieldName"]);
		}
		return "";
	}
	
	public function setErrorMsg($fieldName, $errorMsg)
	{
		$this->errorMsgs["$fieldName"] = $errorMsg;
	}
	
	public function hasErrorMsg($fieldName)
	{
		return ($this->errorMsgs["$fieldName"] != null && $this->errorMsgs["$fieldName"] != "");
	}
	
	public function getValidatorScripts()
	{
		$validatorScript = "";
		if(is_array($this->fieldValidators) && empty($this->fieldValidators) == false)
		{
			$validatorScript .= '<script language="JavaScript" type="text/javascript"><!--// ';
			foreach ($this->fieldValidators as $validators) 
			{
				foreach($validators as $fieldName => $validateInfos) 
				{
					$validatorObject = $validateInfos[self::VALIDATOR];
					$errorMsg = $validateInfos[self::ERROR_MSG];
					$validatorScript .= sprintf($validatorObject->genValidatorScript(), $fieldName, $errorMsg);
				}
			}
			$validatorScript .= ' //--></script>';
			return $validatorScript;
		}
		else
		{
			return "";
		}
	}

}

?>