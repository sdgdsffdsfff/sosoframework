<?php
class Local
{
	private static $localCache;
	public static function getValue($key)
	{
		if (empty(self::$localCache))
		{
			$lang = Config::getConfig("cp_lang");
			if($lang == "en")
			{
				include_once(ROOT_CLASS_PATH."/src/local/locale_en_UK.php");
			}
			else
			{
				include_once(ROOT_CLASS_PATH."/src/local/locale_zh_CN.php");
			}
			self::$localCache = $local;
		}	
		return self::$localCache[$key];
	}
}
?>
