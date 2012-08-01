<?php
abstract class BaseController
{
	//const DEFAULT_ACTION_PARAM = "do";
	const DEFAULT_ACTION_NAME = "Default";
	
	const ACTION_CLASS = "class";
    const ACTION_METHOD = "method";
    const VALIDATEOR = "validator";
	const TEMPLATE = "template";
	const NEXT_ACTION = "nextAction";
	const INTERCEPTOR_KEY = "interceptor";
	const DEFAULT_INTERCEPTOR = "defaultinterceptor";
	const DEFAULT_ERROR_PAGE = "defaulterrorpage";
	const DEFAULT_VALIDATOR = "DefaultValidate";
	
	private $actionMaps = array();
	private $actionName;
	private $interceptors = array();
	private $applicationContext;
	
	public function BaseController()
	{
	}
	
	public function process()
	{
		$this->actionMaps = array_change_key_case($this->loadActionMaps(), CASE_LOWER);
		$this->actionName = WinRequest::getValue(Config::getConfig("actionParam"));
//		var_dump($this->actionName);
		$this->actionName = ($this->actionName != null && isset($this->actionMaps[$this->actionName]) ) ? $this->actionName : self::DEFAULT_ACTION_NAME;
		$this->actionName = strtolower($this->actionName);
		$actionClass = $this->actionMaps[$this->actionName][self::ACTION_CLASS];
		if( $actionClass != null && $actionClass != "")
		{
			$this->doProcess();
		}
		else
		{
			//TODO:: not have action mapping add waring
		}
	}
	
	protected abstract function loadActionMaps();
	
	private function doProcess()
	{
		$performed = false;
		while($performed == false)
		{
			$this->loadInteceptorBeforeMethod();
			$actionClass = $this->getActionClassName();
            $actionMethod = $this->getActionMethod();
            $validator = $this->getValidator();
			$successTemplate = $this->actionMaps[$this->actionName][BaseAction::SUCCESS][self::TEMPLATE];
			$errorTemplate = $this->getErrorTemplate();

			$actionInfo = new ActionInfo($this->actionName, $successTemplate, $errorTemplate, $actionMethod, $validator);
			$actionObject = new $actionClass($actionInfo);
			$result = $actionObject->execute($actionMethod);
			if(BaseAction::SUCCESS == $result && $this->hasNextAction($result))
			{
				$this->setNextAction();
			}
			else
			{
				$performed = true;
			}
		
			$this->loadInteceptorAfterMethod();
			if(BaseAction::SYSTEM_ERROR == $result)
			{	
				$actionObject->systemErrorProcess();
				break;
			}
			else
			{
				$actionObject->tryJump();
			}
		}
		if($result != BaseAction::SUCCESS)
			$this->setViewByPerformResult($actionObject, $result);
		$view = $actionObject->getView();
		$view->render();
	}
	private function getActionClassName()
	{
        return $this->actionMaps[$this->actionName][self::ACTION_CLASS];
	}
    private function getActionMethod()
    {
        return $this->actionMaps[$this->actionName][self::ACTION_METHOD]; 
    }
    private function getValidator()
    {
    	$validator = $this->actionMaps[$this->actionName][self::VALIDATEOR];
    	$validator = ($validator != "")?$validator:self::DEFAULT_VALIDATOR;
    	return $validator; 
    }
    private function loadInteceptor()
    {
    	$arrays = array();
    	if(array_key_exists(self::INTERCEPTOR_KEY, $this->actionMaps[$this->actionName]))
    	{
    		$interceptors = $this->actionMaps[$this->actionName][self::INTERCEPTOR_KEY];
    	}
    	else
    	{
    		$interceptors = $this->actionMaps[self::DEFAULT_INTERCEPTOR];
    	}
    	
   		foreach ($interceptors as $interceptorClassName)
    	{
    		$arrays[] = new $interceptorClassName;
    	}
    	return $arrays;
    }
    private function loadInteceptorBeforeMethod()
    {
    	foreach ($this->loadInteceptor() as $inter)
    	{
    		$inter->before();
    		array_push($this->interceptors, $inter);
    	}
    }
    private function loadInteceptorAfterMethod()
    {
    	while ($inter = array_pop($this->interceptors))
    	{
    		$inter->after();
    	}
    }
    private function getErrorTemplate()
    {
    	if(array_key_exists(BaseAction::SYSTEM_ERROR, $this->actionMaps[$this->actionName]))
    	{
    		$errorTemplate = $this->actionMaps[$this->actionName][BaseAction::SYSTEM_ERROR][self::TEMPLATE];
    	}
    	else
    	{
    		$errorTemplate = $this->actionMaps[self::DEFAULT_ERROR_PAGE];
    	}
    	return $errorTemplate;
    }
	private function hasNextAction($result)
	{
		return (empty($this->actionMaps[$this->actionName][$result][self::NEXT_ACTION]) == false);
	}
	
	private function setNextAction()
	{
		$this->actionName = strtolower($this->actionMaps[$this->actionName][BaseAction::SUCCESS][self::NEXT_ACTION]);
	}
	private function setViewByPerformResult($actionObject, $result)
	{
		$performResultSettings = $this->actionMaps[$this->actionName][$result];
		if(is_array($performResultSettings) && empty($performResultSettings[self::TEMPLATE]) == false)
		{
			$actionObject->setCustomView($performResultSettings[self::TEMPLATE]);
		}
	}
	public function getActionName()
	{
		return $this->actionName;
	}
	public function getApplicationContext()
	{
		return $this->applicationContext;
	}
	
	
}
?>
