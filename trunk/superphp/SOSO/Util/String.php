<?php
/**
 * SOSO Framework
 * 
 * @category   SOSO_Util
 * @package    SOSO_Util
 * @description 工具类::字符串工具
 * @copyright  Copyright (c) 2007-2008 Soso.com
 * @author : 张勇 <zhanghe525@hotmail.com>
 * @version $Revision 1.1
 * @created 15-四月-2008 16:59:24
 * @date : $Date 2008-08-04 11:34
 */
class SOSO_Util_String {

	/**
	 * 检测字符串是否是UTF-8编码,效果与mb_detect_
	 * // From http://w3.org/International/questions/qa-forms-utf-8.html
	 * 
	 * @param string $string
	 * @return boolean
	 */
	public function detectUTF8($string){
	    return preg_match('%^(?:
	          [\x09\x0A\x0D\x20-\x7E]            # ASCII
	        | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
	        |  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
	        | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
	        |  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
	        |  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
	        | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
	        |  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
	    )*$%xs', $string);
	    //[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}
	}
	
 /**
	* Unicode-safe version of htmlspecialchars()
	*
	* @param	string	Text to be made html-safe
	*
	* @return	string
	*/
	public static function htmlspecialchars_uni($text, $entities = true){
		return str_replace(
			// replace special html characters
			array('<', '>', '"'),
			array('&lt;', '&gt;', '&quot;'),
			preg_replace(
				// translates all non-unicode entities
				'/&(?!' . ($entities ? '#[0-9]+|shy' : '(#[0-9]+|[a-z]+)') . ';)/si',
				'&amp;',
				$text
			)
		);
	}
	
		/**
	 * 将URL参数字符串转化为数组
	 *
	 * @param string $pString
	 * @return array
	 */
	public static function parseQuery($pString=''){
		if (strlen($pString)) {
			parse_str($pString,$return);
		}elseif (strlen($_SERVER['QUERY_STRING'])){
			parse_str($_SERVER['QUERY_STRING'],$return);
		}
		return $return;
	}
	/**
	 * 过滤query_string参数
	 *
	 * @param string $pString
	 * @param string[] $pOffset
	 * @param boolean $pEncode 控制是否进行urlencode
	 * @return unknown
	 */
	public static function filterParam($pString='',$pOffset=array(),$pEncode=true){
		$pString = strlen($pString)?$pString:$_SERVER['QUERY_STRING'];
		if (is_string($pOffset)) {
			if (!strlen($pOffset)) {
				return $pString;
			}
			$tArray = array($pOffset);
		}elseif (is_array($pOffset) && !empty($pOffset)){
			$tArray = $pOffset;
		}else {
			return $pString;
		}
		$tReturn = self::parseQuery($pString);
		foreach ($tArray as $value) {		
			if (isset($tReturn[$value])) {
				unset($tReturn[$value]);
			}
		}			
		$tReturn = $pEncode?array_map(create_function('$v','return urlencode(urldecode($v));'),$tReturn):$tReturn;
		return http_build_query($tReturn);
	}
	
	public static function getDomain($pString=''){
		$pString = strlen($pString)?$pString:$_SERVER['HTTP_HOST'];
		$arr = explode('.',$pString);
		$arr = array_splice($arr,floor(count($arr)/2));
		return implode('.',$arr);
	}
	public static function strip($pString){
    	return preg_replace("/^\s+/", '' ,$pString);
	}
	
	public static function remove($pString,$pStart,$pEnd){
		$s='';
		if ($pStart>0) 
			$s=substr($pString,0,$pStart-1);
		if ($pEnd < strlen($pString)) 
			$s.= substr($pString,$pEnd);
		return $s;
	}
	
	public static function chop ($pString) {
		return substr($pString, 0, strlen($pString)-1);
	}
	
	public static function left($pTitle, $pLength, $sign = false) {
		if (strlen($pTitle) <= $pLength) {
			return $pTitle;
		}
		$tmpstr = "";
		for($i = 0;$i < $pLength;$i++) {
			if (ord(substr($pTitle, $i, 1)) > 0x80) {
				if ($i+1 == $pLength) {
					break;
				}
				$tmpstr .= substr($pTitle, $i, 2);
				$i++;
			} else {
				$tmpstr .= substr($pTitle, $i, 1);
			}
		}
		return $tmpstr . (string)$sign;
	}
	
	public static function randString($pLength,$pUserDefine="dstS"){
        $all["d"] = array(0,1,2,3,4,5,6,7,8,9);
        $all["s"] = array("a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z");
        $all["S"] = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z");
        $all['t'] = array('~','!','@','#','$','%','^','&','*','(',')',',','<','>','[',']','{','}','?','.',',','=','-','_');
        $all['u'] = array();
        for($i=0;$i<strlen($pUserDefine);$i++){
        	$all['u'] = array_merge($all['u'],$all[$pUserDefine[$i]]);
        }
        shuffle($all['u']);
        return implode(array_slice($all['u'],0,$pLength));
    }
    
    /**
     * 零钱时，去掉"整"
     * Enter description here ...
     * @param unknown_type $data
     */
	public static function money2cn($data) {
        $capnum = array("零","壹","贰","叁","肆","伍","陆","柒","捌","玖");
        $capdigit = array("","拾","佰","仟");
        $subdata = explode(".",$data);
        $yuan = $subdata[0];
        $j = 0;
        $nonzero = 0;
        for($i=0;$i<strlen($subdata[0]);$i++) {
            if(0==$i) { //确定个位
                if(isset($subdata[1])) {
                    $cncap=(substr($subdata[0],-1,1)!=0)?"元":"元零";
                }
                else{
                    $cncap="元";
                }
            }
            if(4==$i){ $j=0;  $nonzero=0; $cncap="万".$cncap; } //确定万位
            if(8==$i){ $j=0;  $nonzero=0; $cncap="亿".$cncap; } //确定亿位
            $numb=substr($yuan,-1,1); //截取尾数
            $cncap=($numb)?$capnum[$numb].$capdigit[$j].$cncap:(($nonzero)?"零".$cncap:$cncap);
            $nonzero=($numb)?1:$nonzero;
            $yuan=substr($yuan,0,strlen($yuan)-1); //截去尾数
            $j++;
        }
		$cent = '';
		$chiao = '';
        if(isset($subdata[1])) {
            $chiao=(substr($subdata[1],0,1))?$capnum[substr($subdata[1],0,1)]."角":"零";
            $cent=(substr($subdata[1],1,1))?$capnum[substr($subdata[1],1,1)]."分":"零分";
        }
        $cncap .= $chiao.$cent."整";
        $cncap=preg_replace("/(零)+/","\\1",$cncap); //合并连续“零”
        return $cncap;
    }
    
	public static function truncate($pString,$length=0,$truncation=null) {
		$length = $length ? 30 : $length;
    	$truncation = $truncation ? '...' : $truncation;
		return strlen($pString) > $length ?substr($pString,0,$length- strlen($truncation)) + $truncation : $pString;
	}
	
	/**
	 * 解析出文本中的URL及链接文字,适用于各种a标签,包括有轻程度语法错误的
	 *
	 * @param string $pContent
	 * @param string $pFlag PREG_SET_ORDER | PREG_PATTERN_ORDER 
	 * @return array();
	 */
	public static function pageUrls($pContent,$pFlag=PREG_SET_ORDER){
        $pattern = "/<\s*a\s+.*href\s*=\s*['\"]?([^\"\'\s>]+?).*>(.*)<[\s\/]*a\s*>/Uis";
        preg_match_all($pattern,$pContent,$match,$pFlag);
        return $match; 
	}
	
	// 多字节版 substr
	// 增加一个 option 参数组，提供如下选项
	// add_dot 是否需要在截取后添加 ...(会计入总长度)，默认为 true，添加
	// charset 字符集 utf-8 or gb2312，默认为 utf-8
	// char_len $length 是 ascii 长度还是字符长度，默认为 false，ascii长度
	public static function substr($string, $length, $option = array()) {
		$strLength = 0;
		$i_option = array('add_dot'=>true, 'charset'=>'utf-8', 'char_len'=>false);
		$option = array_merge($i_option, $option);
		if(strlen($string) > $length) {
			//将$length换算成实际UTF8格式编码下字符串的长度
			for($i = 0; $i < ($length-($option['add_dot']?3:0)); $i++) {
				if ( $strLength >= strlen($string) )
				break;
				//当检测到一个中文字符时
				if( ord($string[$strLength]) > 127 ) {
					if ($option['char_len'] || ++$i < ($length-($option['add_dot']?3:0))) {
						$strLength += (($option['charset'] == 'utf-8')?3:2);
					}
				}
				else
				$strLength += 1;
			}
			return substr($string, 0, $strLength).($option['add_dot']!==false?$option['add_dot']:'');
		} else {
			return $string;
		}
	}

	// utf-8 版 strlen
	public static function strlen($str) {
		$i = 0;
		$count = 0;
		$len = strlen ($str);
		while ($i < $len) {
			$chr = ord ($str[$i]);
			$count++;
			$i++;
			if ($i >= $len)
			break;

			if ($chr & 0x80) {
				$chr <<= 1;
				while ($chr & 0x80) {
					$i++;
					$chr <<= 1;
				}
			}
		}
		return $count;
	}
	
	public static function encode($pStr){
		$return = '';
		$pStr = base64_encode($pStr);
		for($i=0;$i<strlen($pStr);$i++){
			$return .= dechex(ord($pStr{$i}));
		}
		return base64_encode($return);
	}

	public static function decode($pStr){
		$return = '';
		$pArr = str_split(base64_decode($pStr),2);
		for($i=0;$i<count($pArr);$i++){
			$return .= chr(hexdec($pArr[$i]));
		}
		return base64_decode($return);
	}
	
	/**
	 * change unicode to any encoding
	 * @desc unicode2any('20195;'); //&#20195;
	 * @author moonzhang @ 2008-04-18
	 * @param string $str
	 * @param string $pEncoding
	 * @return string
	 */
	public static function unicode2any($str,$pEncoding='gbk'){
		return iconv('ucs-2',$pEncoding,pack('n',$str));
	}
	
	/**
	 * 将字符串转换成html实体类型
	 *
	 * @param string $str
	 * @param string $from
	 * @return string
	 */
	public static function any2unicode($str,$from='gbk'){
		if (function_exists('mb_convert_encoding')) {
			$tRes = unpack("n*",mb_convert_encoding($str,"ucs-2",$from));
		}elseif(strtolower(PHP_OS)=='linux' && function_exists('iconv')) {
			$str = iconv($from,'utf-8',$str);
			$tRes = (unpack("n*",iconv('utf-8','ucs-2',$str)));
		}else{
			return $str;
		}
		$return = array();
		foreach ($tRes as $v){
			$return[] = sprintf("&#%s;",$v);
		}
		return join('',$return);
	}
	
	/**
	 * Function converts an Javascript escaped string back into a string with specified charset (default is UTF-8).
	 * Modified function from http://pure-essence.net/stuff/code/utf8RawUrlDecode.phps
	 * example:unescape('%u4EE3 &#20195; ipcn+%E6%8C%91%E6%88%98%E6%9D%AF,%CC%F4 %D5%BD','gb2312');
	 * 
	 * @param string $source escaped with Javascript's escape() function
	 * @param string $iconv_to destination character set will be used as second paramether in the iconv function. Default is UTF-8.
	 * @return string
	 */
	public static function unescape($source, $iconv_to = 'UTF-8') {
	    $decodedStr = '';
	    $pos = 0;
	    $oneWords=0;

	    $len = strlen ($source);
	    while ($pos < $len) {
	        $charAt = substr ($source, $pos, 1);
	        if ($charAt == '%') {
	            $pos++;
	            $charAt = substr ($source, $pos, 1);
	            if ($charAt == 'u') {
	                // we got a unicode character
	                $pos++;
	                $unicodeHexVal = substr ($source, $pos, 4);
	                $unicode = hexdec ($unicodeHexVal);
	                $decodedStr .= self::code2utf($unicode);
	                $pos += 4;
	            }else {
	            	$oneWords += 1;
	                // we have an escaped ascii character
	                $hexVal = substr ($source, $pos, 2);
	                $decodedStr .= chr (hexdec ($hexVal));
	                $pos += 2;
	            }
	        }elseif ($charAt == '&'){
	        	$pos++;
	        	$charAt = substr ($source, $pos, 1);
	        	if ($charAt == '#') {
	        		$pos2 = strpos(substr($source,$pos),';');
	        		$str = substr($source,$pos+1,$pos2);
	        		$decodedStr .= self::uni2any($str,'utf-8');
	        		$pos += $pos2+1;
	        	}
	        }
	        else {
	            $decodedStr .= $charAt;
	            $pos++;
	        }
	    }
	    if ($iconv_to != "UTF-8") {
	        $decodedStr = iconv("UTF-8", $iconv_to, $decodedStr);
	    }
	   
	    return $decodedStr;
	}
	
	/**
	 * Function coverts number of utf char into that character.
	 * Function taken from: http://sk2.php.net/manual/en/function.utf8-encode.php#49336
	 * @modified by phper: equal to iconv('ucs-2','utf-8',$num);
	 * @param int $num
	 * @return utf8char
	 */
	public static function code2utf($num){
	    if($num<128)return chr($num);
	    if($num<2048)return chr(($num>>6)+192).chr(($num&63)+128);
	    if($num<65536)return chr(($num>>12)+224).chr((($num>>6)&63)+128).chr(($num&63)+128);
	    if($num<2097152)return chr(($num>>18)+240).chr((($num>>12)&63)+128).chr((($num>>6)&63)+128) .chr(($num&63)+128);
	    return '';
	}
	
	public static function isUTF8($pString){
		$pString = urldecode($pString);
		if ($str = @iconv('utf-8','gbk//IGNORE',$pString)) {
			if ($pString == @iconv("gb18030","utf-8//IGNORE",$str)) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * change escapted utf-8 string to unicode ,example : %u4EE3 => &#20195;
	 *
	 * @param string $source
	 * @return string
	 */
	function utf8RawUrlDecode ($source) { 
	    $decodedStr = ""; 
	    $pos = 0; 
	    $len = strlen ($source); 
	    while ($pos < $len) { 
	        $charAt = substr ($source, $pos, 1); 
	        if ($charAt == '%') { 
	            $pos++; 
	            $charAt = substr ($source, $pos, 1); 
	            if ($charAt == 'u') { 
	                // we got a unicode character 
	                $pos++; 
	                $unicodeHexVal = substr ($source, $pos, 4); 
	                $unicode = hexdec ($unicodeHexVal); 
	                $entity = "&#". $unicode . ';'; 
	                $decodedStr .= utf8_encode ($entity); 
	                $pos += 4; 
	            } 
	            else { 
	                // we have an escaped ascii character 
	                $hexVal = substr ($source, $pos, 2); 
	                $decodedStr .= chr (hexdec ($hexVal)); 
	                $pos += 2; 
	            } 
	        } else { 
	            $decodedStr .= $charAt; 
	            $pos++; 
	        } 
	    } 
	    return $decodedStr; 
	} 
}
?>