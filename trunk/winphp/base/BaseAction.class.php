<?php
class ActionInfo
{
	private $actionName;
	private $successTemplate;
	private $errorTemplate;
	private $actionMethod;
	private $validator;
	public function ActionInfo($actionName, $successTemplate, $errorTemplate, $actionMethod, $validator)
	{
		$this->actionName = $actionName;
		$this->successTemplate = $successTemplate;
		$this->errorTemplate = $errorTemplate;
		$this->actionMethod = $actionMethod;
		$this->validator = $validator;
	}
	public function getActionName()
	{
		return $this->actionName;
	}
	public function getSuccessTemplate()
	{
		return $this->successTemplate;
	}
	public function getErrorTemplate()
	{
		return $this->errorTemplate;
	}
	public function getActionMethod()
	{
		return $this->actionMethod;
	}
	public function getValidator()
	{
		return $this->validator;
	}
}

abstract class BaseAction
{
	const INPUT = "input";
	const SUCCESS = "success";
	const SYSTEM_ERROR = "error";
	const VALIDATE_ERROR = "validateError";
	
	protected $actionInfo;
	protected $form;
	protected $view;
	private $jumpPath;
	private $errorMsg;
	
	public function BaseAction(ActionInfo $actionInfo)
	{
		$this->actionInfo = $actionInfo;
		$this->form = new Form();
		$this->view = new DefaultView($actionInfo->getSuccessTemplate());
		$this->DealExt();
	}
	public function DealExt()
	{
		return true;
	}
	
	public final function execute()
	{
		try
		{
			return $this->doExecute();
		}
		catch(BizException $bizEx)
		{
		   	$this->errorMsg = $bizEx->getMessage();
		    return self::SYSTEM_ERROR;
		}
		catch(Exception $ex)
		{
			return self::SYSTEM_ERROR;
		}
	}
	protected function doExecute()
	{
		$this->setViewCommonValue();
		$validatorStr = $this->actionInfo->getValidator();
		$validator = new $validatorStr($this->form);		
		if($validator->validate())
		{
			$actionMethodStr = $this->actionInfo->getActionMethod();
			return $this->$actionMethodStr();
		}
		else
		{
			$this->validateErrorProcess();
			return self::VALIDATE_ERROR;
		}
	}
	protected function validateErrorProcess()
	{
		$this->setView(new DefaultView($this->actionInfo->getErrorTemplate()));
		$this->setViewCommonValue();
		
		$this->view->setValue("inputs", $this->form->getFieldValues());
		$this->view->setValue("model", $this->form->getFieldValues());
	}
	public function systemErrorProcess()
	{
		$this->setView(new DefaultView($this->actionInfo->getErrorTemplate()));
		$this->setViewCommonValue();
		$this->view->setValue("errorMsg", $this->errorMsg);
	}
	protected function setViewValue($name, $value)
	{
		$this->view->setValue($name, $value);
	}
	protected function getViewValue($name)
	{
		return $this->view->getValue($name);
	}
	public function getView()
	{
		return $this->view;
	}
	public function setView($view)
	{
		$this->view = $view;
	}
	public function getErrorMsg()
	{
		return $this->errorMsg;
	}
	protected function setViewCommonValue()
	{
       	$this->view->setValue("now", date("H:i:s"));		
	}
    public function setJumpPath($jumpPath)
    {
        $this->jumpPath = $jumpPath;
    }
    public function tryJump()
    {
        if($this->jumpPath != null)
        {
            header ("Location: ".$this->jumpPath);
            exit;
        }
    }
    public function setCustomView($customTemplate)
    {
    	$datas = $this->view->getValues();
    	$this->view = new DefaultView($customTemplate);
    	$this->view->setValues($datas);
    }
    public function codeConvert( $str, $to="utf-8", $from="gbk" )
    {
    	return iconv($from ,$to, $str);
    }
}

?>