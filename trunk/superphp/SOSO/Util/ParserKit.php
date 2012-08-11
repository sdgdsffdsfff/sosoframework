<?php
class SOSO_Util_ParserKit
{
	public static function between($s, $bflag, $eflag){
		$result = "";
		$bpos = stripos($s, $bflag);
		if($bpos !== false){
			$bpos += strlen($bflag);
			$epos = stripos($s, $eflag, $bpos);
			if($epos !== false){
				$result = substr($s, $bpos, $epos - $bpos);
			}
		}
		return $result;
	}
	
	public static function before($s, $flag){
		$result = "";
		$pos = stripos($s, $flag);
		if($pos !== false){
			$result = substr($s, 0, $pos);
		}
		return $result;
	}
	
	public static function beforeUntil($s, $eflag, $bflag){
		$result = "";
		$pos = stripos($s, $eflag);
		if($pos !== false){
			$newString = substr($s, 0, $pos);
			$bpos = strripos($newString, $bflag, 0);
			if($bpos !== false){
				$delta = strlen($bflag);
				$result = substr($newString, $bpos+$delta, $pos - $bpos - $delta);
			}
		}
		return $result;
	}

	public static function after($s, $flag){
		$result = "";
		$pos = stripos($s, $flag);
		if($pos !== false){
			$pos += strlen($flag);
			$result = substr($s, $pos);
		}
		return $result;
	}
	
	public static function startsWith($s, $needle){
		$pos = stripos($s, $needle);
		return $pos === 0;
	}
	
	public static function endsWith($s, $needle){
		$pos = strripos($s, $needle);
		if($pos === false) return false;
		return $pos == strlen($s) - strlen($needle);
	}
	
	public static function contain($s, $needle){
		$pos = strripos($s, $needle);
		return ($pos === false) ? false : true;
	}
}
?>