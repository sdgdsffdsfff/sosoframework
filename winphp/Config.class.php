<?php
class Config
{
	private static $configCache;
	public static function getConfig($key)
	{
		if (empty(self::$configCache))
		{
			include_once(ROOT_PRO_PATH."/config/config.properties.php");
			self::$configCache = $config;
		}
			
		return self::$configCache[$key];
	}
}
?>
