<?php
class IpService
{
	const REQUEST_COMMAND = "%06dFIND\r\n%s";
	

	public static function query($ip,$host,$port)
	{
		if(empty($host) || empty($port)) return false;
		if(ip2long($ip) === false)
			return false;
		$ipLen = strlen($ip);
		$request = sprintf(self::REQUEST_COMMAND, $ipLen + 6, $ip);
		
		if(($fd = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false)
			return false;
			
		socket_set_option($fd, SOL_SOCKET, SO_SNDTIMEO, array('sec'=>0,'usec'=>30000));
		socket_set_option($fd, SOL_SOCKET, SO_RCVTIMEO, array('sec'=>0,'usec'=>30000));
		
		if(@socket_connect($fd, $host, $port) === false)
			return false;
		if(@socket_send($fd, $request, strlen($request), 0x100) === false)
		{
			socket_close($fd);
			return false;
		}
		if(@socket_recv($fd, $len, 6, 0x100) === false)
		{
			socket_close($fd);
			return false;
		}
			
		if((int)$len < 0)
			return false;
		if(@socket_recv($fd, $result, $len, 0x100) === false)
		{
			socket_close($fd);
			return false;
		}					
		socket_close($fd);
		if(substr($result, 0, 4) != "OK\r\n")
			return false;
		$result = substr($result, 4);
		return $result;
	}
}
?>
