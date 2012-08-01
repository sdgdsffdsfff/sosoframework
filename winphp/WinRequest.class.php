<?php
class WinRequest
{	
	public static function getRequest()
	{
		return $_REQUEST;
	}
	
	public static function getValue($key)
	{
		$request = self::getRequest();
		if (isset($request[$key]))
			return(trim($request[$key]));
		return null;
	}
	
	public static function hasKey($key)
	{
		$request = self::getRequest();
		return array_key_exists($key, $request);
	}
	
	public static function setRequest($requestArray)
	{
		foreach($requestArray as $key => $value)
		{
			$_REQUEST["$key"] = $value;
		}
		return true;
	}
    
	public static function setValue($key, $value)
	{
		$request = self::getRequest();
		$request["$key"] = $value;
		self::setRequest( $request );
		return true;
	}
	

}
?>