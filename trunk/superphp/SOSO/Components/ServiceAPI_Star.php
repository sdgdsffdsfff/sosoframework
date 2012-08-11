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

	// ��ѯ�����������
	public static function getStar($uin)
	{
		// 4�ֽ��ܳ��� + GET + 4�ֽ��û�QQ�ų��� + �û�QQ��
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

	// ��������Tips֪ͨ
	public static function addStar($uin, $nick, $star, $notice, $status)
	{
		// 4�ֽ��ܳ��� + SET + 4�ֽ��û�QQ�ų��� + �û�QQ�� + 4�ֽ��û��ǳƳ��� + �û��ǳ� 
		// + 12�ֽ����� + 2�ֽڷ���ʱ�� + 1�ֽ��Ƿ���Ч
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

	// === ����Ϊ�İ��������� ===

	// ��ѯ��ע����
	public static function getAttentionStar($uin)
	{
		// 4�ֽ��ܳ��� + GTC + 4�ֽ��û�QQ�ų��� + �û�QQ��
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
	    else if($code == '01'){ // δ���ƹ�
			$result = array(
				'star' => '000000000000',
				'status' => '0',
				'code' => $code,
			);
	    }
	    return $result;
	}

	// ���ƹ�ע����
	public static function addAttentionStar($uin, $nick, $star, $status)
	{
		// 4�ֽ��ܳ��� + STC + 4�ֽ��û�QQ�ų��� + �û�QQ�� + 4�ֽ��û��ǳƳ��� + �û��ǳ� + 12�ֽ�����  + 1�ֽ�״̬
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

	// ��ӹ�ע����
	public static function addAttentionFriend($uin, $fuin, $fstar, $fface, $fnick)
	{
		// 4�ֽ��ܳ��� + SCF + 4�ֽ��û�QQ�ų��� + �û�QQ�� + 10�ֽں���QQ�� + 2�ֽ����� + 5�ֽ�ͷ��ID + 4�ֽ��û��ǳƳ��� + �û��ǳ�
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

	// ɾ����ע����
	public static function removeAttentionFriend($uin, $fuin)
	{
		// 4�ֽ��ܳ��� + DCF + 4�ֽ��û�QQ�ų��� + �û�QQ�� + 10�ֽں���QQ��
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

	// ��ѯ��ע����
	public static function getAttentionFriends($uin){
		// 4�ֽ��ܳ��� + GCF + 4�ֽ��û�QQ�ų��� + �û�QQ��
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
