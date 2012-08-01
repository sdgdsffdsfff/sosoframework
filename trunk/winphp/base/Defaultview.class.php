<?php

class DefaultView
{
	private $templateFile;
	private $local;
	private $data = array();
	
	public function DefaultView($templateFile)
	{
		$this->templateFile = $templateFile;
	}
	
	public function render()
	{
		print $this->getRenderOutput();
	}
	
    private function getRenderOutput()
    {
		$template = DefaultViewSetting::getTemplate();
		DefaultViewSetting::setTemplateSetting($template);
       	$template->assign($this->data);
		return $template->fetch($this->templateFile);
    }
	
	public function setValue($key, $value)
	{
		assert('' != $key);
		$this->data[$key] = $value;
	}
	
	public function getValue($key)
	{
		return (isset($this->data[$key])) ? $this->data[$key] : '';
	}
	
	public function getValues()
	{
		return $this->data;
	}
	
	public function setValues($values)
	{
		$this->data = $values;
	}
	
}



?>