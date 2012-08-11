<?php
/*
 *  目的：封装最常用的socket操作
*/
class SK
{
	static $reciveLen = "65535";
	public static function q( $request, $conf, $headLen="" )
	{
		if(($fd = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false)
			return false;
		
		socket_set_option($fd, SOL_SOCKET, SO_RCVTIMEO, $conf['timeout']['recv']);
		socket_set_option($fd, SOL_SOCKET, SO_SNDTIMEO, $conf['timeout']['send']);
		
		if(@socket_connect($fd, $conf['host'], $conf['port']) === false)
			return false; 

		if(@socket_send($fd, $request, strlen($request), 0x100) === false)
		{
			socket_close($fd);
			return false;
		}
		if( !empty($headLen) )  
		{
			if(@socket_recv($fd, $len, 6, 0x100) === false)
			{
				socket_close($fd);
				return false;
			}
		
			$len = (int)$len;
			if($len <= 4)
			{
				socket_close($fd);
				return false;			
			}
			if(@socket_recv($fd, $result, $len, 0x100) === false)
			{
				socket_close($fd);
				return false;
			}
		}else{
			if(@socket_recv($fd, $result, self::$reciveLen, 0x100) === false)
			{
				socket_close($fd);
				return false;
			}			
		}
		socket_close($fd);
		return $result;
	}
	
	public static function curl( $request_url, $timeout="1", $header="false", $return="true" )
	{
	   $curl = curl_init();
	   curl_setopt($curl, CURLOPT_URL, $request_url);
	   curl_setopt($curl, CURLOPT_RETURNTRANSFER, $return);
	   curl_setopt($curl, CURLOPT_HEADER, $header);
	   curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
	   
	    ob_start(); 
		  $resFlag = curl_exec($curl); 
		  curl_close($curl); 
		  $result = ob_get_contents(); 
		ob_end_clean();
	   if($resFlag)
	   {
	   		return $result;
	   }
	   	else{
	   		return false;
	   	}
	}
}

?>