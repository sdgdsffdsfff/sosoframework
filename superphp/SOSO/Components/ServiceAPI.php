<?php
class ServiceAPI
{
    const MAX_RECV_LEN = 100;
    
    const HEADER_LEN = 4;

    protected static $_timeout = array(
	    'send' => array('s' => 1, 'us' => 0),
	    'recv' => array('s' => 1, 'us' => 0),
	    );

    protected static $_host = '172.24.19.154';

    protected static $_port = 20007;

    public static function get($uin, $type)
    {
	$request = "%0".self::HEADER_LEN."dGET%0".self::HEADER_LEN."d%s%s";
	$uinLen = strlen($uin);
	$total = self::HEADER_LEN + 3 + self::HEADER_LEN + $uinLen + 1;
	$request = sprintf($request, $total, $uinLen, $uin, $type);
	$result = self::_retrieve($request, self::$_host, self::$_port);
	if(empty($result))
	    return false;
	$ret = substr($result, 0, 2);
	if($ret != '00')
	    return false;
	$data = (int)substr($result, 2);
	return $data;
    }

    public static function set($uin, $type)
    {
	$request = "%0".self::HEADER_LEN."dSET%0".self::HEADER_LEN."d%s%s";
	$uinLen = strlen($uin);
	$total = self::HEADER_LEN + 3 + self::HEADER_LEN + $uinLen + 1;
	$request = sprintf($request, $total, $uinLen, $uin, $type);
	$result = self::_retrieve($request, self::$_host, self::$_port);
	if(empty($result))
	    return false;
	$ret = substr($result, 0, 2);
	if($ret != '00')
	    return false;
	$data = (int)substr($result, 2);
	return $data;
    }

    public static function query($uin)
    {
	$request = "%0".self::HEADER_LEN."dALL%0".self::HEADER_LEN."d%s";
	$uinLen = strlen($uin);
	$total = self::HEADER_LEN + 3 + self::HEADER_LEN + $uinLen;
	$request = sprintf($request, $total, $uinLen, $uin);
	$result = self::_retrieve($request, self::$_host, self::$_port);
	if(empty($result))
	    return false;
	$ret = substr($result, 0, 2);
	if($ret != '00')
	    return false;
	$data = substr($result, 2);
	return $data;
    }

    protected static function _retrieve($request, $host = '127.0.0.1', $port = 10000)
    {
	if(($fd = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false)		
	    return false;		

	socket_set_option($fd, SOL_SOCKET, SO_RCVTIMEO, array('sec'=>self::$_timeout['recv']['s'],'usec'=>self::$_timeout['recv']['us']));
	socket_set_option($fd, SOL_SOCKET, SO_SNDTIMEO, array('sec'=>self::$_timeout['send']['s'],'usec'=>self::$_timeout['send']['us']));
	if(@socket_connect($fd, $host, $port) === false)		
	    return false;

	if(@socket_send($fd, $request, strlen($request), 0x100) === false)
	{
	    socket_close($fd);
	    return false;
	}
	if(@socket_recv($fd, $len, self::HEADER_LEN , 0x100) === false)
	{
	    socket_close($fd);
	    return false;
	}
	$len = (int)$len;
	if(empty($len))
	{
	    socket_close($fd);
	    return false;
	}
	if(@socket_recv($fd, $result, $len - self::HEADER_LEN , 0x100) === false)
	{
	    socket_close($fd);
	    return false;
	}
	socket_close($fd);
	return $result;
    }

}
?>
