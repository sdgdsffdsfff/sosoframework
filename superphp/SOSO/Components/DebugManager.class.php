<?php
class DM
{
	private static $marker;
	public static function dump( $target="" )
	{
		//error_log(date("Ymd H:i:s	").$target."\n",3,"/data/bingo/".date("Ymd").".log");
		$isdebug = WinRequest::getValue("isdebug");
		if( ( $isdebug == true) && !empty($target) )
		{
			echo "<pre>";
			var_dump( $target );
		}else if( $isdebug == true )
		{
			echo "Time:".date("Y-m-d H:i:s")."<br/>\n";
		}else 
		{
			return true;
		}
	}
	
	public static function  mark($name)
	{
		self::$marker[$name] = gettimeofday(true);
	}
	
	public static function elapsed_time($point1 = '', $point2 = '', $decimals = 4)
	{
		if ($point1 == '')
		{
			return '{elapsed_time}';
		}
		if ( ! isset(self::$marker[$point1]))
		{
			return '';
		}
		if ( ! isset(self::$marker[$point2]))
		{
			self::$marker[$point2] = gettimeofday(true);
		}
		return number_format((self::$marker[$point2] - self::$marker[$point1]), $decimals);
	}
}
?>