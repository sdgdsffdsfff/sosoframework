<?php
if(basename(dirname(__FILE__)) == 'app')
	define("_BASE_DIR", dirname(dirname(__FILE__)).'/app/');
else
	define("_BASE_DIR", '');
require_once(_BASE_DIR . 'ServiceAPI.php');
require_once(_BASE_DIR . 'RequestBuilder.class.php');
require_once(_BASE_DIR . 'StarConfig.inc.php');

class ServiceAPI_Star extends ServiceAPI
{
	protected static $_host = STAR_SERVER;
	protected static $_port = STAR_PORT;

	// 查询星座定制情况
	public static function getStar($uin)
	{
		// 4字节总长度 + GET + 4字节用户QQ号长度 + 用户QQ号
	    $request = "%04dGET%04d%s";
	    $uinLen = strlen($uin);
	    $total = 4 + 3 + 4 + $uinLen;
	    $request = sprintf($request, $total, $uinLen, $uin);
	    $result = self::_retrieve($request, self::$_host, self::$_port);
	    if(empty($result) || strlen($result) < 2) return false;
	    $code = substr($result, 0, 2);
	    if($code == '00'){
			$result = array(
				'star' => substr($result, 2, 12),
				'notice' => substr($result, 14, 2),
				'status' => substr($result, 16, 1),
				'code' => $code,
			);
	    }
	    else if($code == '01'){
			$result = array(
				'star' => '000000000000',
				'notice' => '01',
				'status' => '0',
				'code' => $code,
			);
	    }
	    return $result;
	}

	// 定制星座Tips通知
	public static function addStar($uin, $nick, $star, $notice, $status)
	{
		// 4字节总长度 + SET + 4字节用户QQ号长度 + 用户QQ号 + 4字节用户昵称长度 + 用户昵称 
		// + 12字节星座 + 2字节发送时间 + 1字节是否有效
		$request = "%04dSET%04d%s%04d%s%s%s%d";
		$uinLen = strlen($uin);
		$nickLen = strlen($nick);
		if(strlen($star) !== 12) die('star format err!');
		if(strlen($notice) !== 2) die('notice format err!');
		if(strlen($status) !== 1) die('status format err!');
		$total = 4 + 3 + 4 + $uinLen + 4 + $nickLen + 12 + 2 + 1;
		$request = sprintf($request, $total, $uinLen, $uin, $nickLen, $nick, $star, $notice, $status);
		$result = self::_retrieve($request, self::$_host, self::$_port);
		if(empty($result)) return false;
		return $result;
	}

	// === 以下为改版新增函数 ===

	// 查询关注星座
	public static function getAttentionStar($uin)
	{
		// 4字节总长度 + GTC + 4字节用户QQ号长度 + 用户QQ号
		$builder = new RequestBuilder();
		$builder->addString('GTC');
		$builder->addInt(strlen($uin), 4);
		$builder->addString($uin);
		$request = $builder->build();

		$ret = self::_retrieve($request, self::$_host, self::$_port);
	    if(empty($ret) || strlen($ret) < 2) return false;
	    $code = substr($ret, 0, 2);
	    if($code == '00'){
			$result = array(
				'star' => substr($ret, 2, 12),
				'status' => substr($ret, 14, 1),
				'code' => $code,
			);
	    }
	    else if($code == '01'){ // 未定制过
			$result = array(
				'star' => '000000000000',
				'status' => '0',
				'code' => $code,
			);
	    }
	    return $result;
	}

	// 定制关注星座
	public static function addAttentionStar($uin, $nick, $star, $status)
	{
		// 4字节总长度 + STC + 4字节用户QQ号长度 + 用户QQ号 + 4字节用户昵称长度 + 用户昵称 + 12字节星座  + 1字节状态
		$builder = new RequestBuilder();
		$builder->addString('STC');
		$builder->addInt(strlen($uin), 4);
		$builder->addString($uin);
		$builder->addInt(strlen($nick), 4);
		$builder->addString($nick);
		$builder->addString($star);
		$builder->addString($status);
		$request = $builder->build();

		$ret = self::_retrieve($request, self::$_host, self::$_port);
		if(empty($ret)) return false;
		return $ret === '00';
	}

	// 添加关注好友
	public static function addAttentionFriend($uin, $fuin, $fstar, $fface, $fnick)
	{
		// 4字节总长度 + SCF + 4字节用户QQ号长度 + 用户QQ号 + 10字节好友QQ号 + 2字节星座 + 5字节头像ID + 4字节用户昵称长度 + 用户昵称
		$builder = new RequestBuilder();
		$builder->addString('SCF');
		$builder->addInt(strlen($uin), 4);
		$builder->addString($uin);
		$builder->addString(str_pad($fuin, 10, '0', STR_PAD_LEFT));
		$builder->addInt($fstar, 2);
		$builder->addInt($fface, 5);
		$builder->addInt(strlen($fnick), 4);
		$builder->addString($fuin);
		$request = $builder->build();

		$ret = self::_retrieve($request, self::$_host, self::$_port);
		if(empty($ret)) return false;
		return $ret === '00';
	}

	// 删除关注好友
	public static function removeAttentionFriend($uin, $fuin)
	{
		// 4字节总长度 + DCF + 4字节用户QQ号长度 + 用户QQ号 + 10字节好友QQ号
		$builder = new RequestBuilder();
		$builder->addString('DCF');
		$builder->addInt(strlen($uin), 4);
		$builder->addString($uin);
		$builder->addString(str_pad($fuin, 10, '0', STR_PAD_LEFT));
		$request = $builder->build();

	    $ret = self::_retrieve($request, self::$_host, self::$_port);
		if(empty($ret)) return false;
		return $ret === '00';
	}

	// 查询关注好友
	public static function getAttentionFriends($uin){
		// 4字节总长度 + GCF + 4字节用户QQ号长度 + 用户QQ号
		$builder = new RequestBuilder();
		$builder->addString('GCF');
		$builder->addInt(strlen($uin), 4);
		$builder->addString($uin);
		$request = $builder->build();

		$ret = self::_retrieve($request, self::$_host, self::$_port);
	    if(empty($ret) || strlen($ret) < 2) return false;
	    $code = substr($ret, 0, 2);
	    if($code == '00'){
			$pos = 2;
			$fcount = intval(substr($ret, $pos, 4));
			$pos += 4;
			$result = array();
			for($i = 0; $i < $fcount; $i++){
				$fuin = ltrim(substr($ret, $pos, 10), '0');
				$pos += 10;
				$fstar = intval(substr($ret, $pos, 2));
				$pos += 2;
				$fface = intval(substr($ret, $pos, 5));
				$pos += 5;
				$fnickLen = intval(substr($ret, $pos, 4));
				$pos += 4;
				$fnick = substr($ret, $pos, $fnickLen);
				$pos += $fnickLen;
				$result[] = array('uin' => $fuin, 'star' => $fstar, 'face' => $fface, 'nick' => $fnick);
			}
	    }
		else{
			$result = false;
		}
	    return $result;
	}
}
?>
