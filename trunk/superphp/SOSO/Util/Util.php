<?php
/**
 * SOSO Framework
 * 
 * @category   SOSO_Util
 * @package    SOSO_Util
 * @author moonzhang
 * @version 1.0 15-四月-2008 16:59:24
 * 
 * 静态工具类
 * $Id: Util.php 348 2012-05-03 04:13:43Z moonzhang $
 */
class SOSO_Util_Util{

	/**
	 * 获取IP列表
	 * @return mixed
	 */
	public static function getIP($ignoreInternal=true)	{
		$alt_ip = $_SERVER['REMOTE_ADDR'];

		if (isset($_SERVER['HTTP_CLIENT_IP'])){
			$alt_ip = $_SERVER['HTTP_CLIENT_IP'];
		}
		else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) &&
				preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches)){
			// make sure we dont pick up an internal IP defined by RFC1918
			foreach ($matches[0] AS $ip){
				if (!preg_match("#^(10|172\.16|192\.168)\.#", $ip)){
					$alt_ip = $ip;
					break;
				}
			}
		}
		else if (isset($_SERVER['HTTP_FROM'])){
			$alt_ip = $_SERVER['HTTP_FROM'];
		}

		return $alt_ip;
	}
	
	public static function getTempDir(){
		$cache_path = '/tmp';
		if (function_exists('sys_get_temp_dir')) {
			$cache_path = sys_get_temp_dir();
		}elseif (('WINNT' == PHP_OS) && getenv('TEMP')) {
			$cache_path = getenv('TEMP');
		}
		return $cache_path;
	}

	/**
	 * 返回当前host名.
	 * 
	 * @since V1.1 2008-09-06
	 */
	public static function getHost() {
		$pathArray = $_SERVER;
		return isset($pathArray['HTTP_X_FORWARDED_HOST']) ? $pathArray['HTTP_X_FORWARDED_HOST'] : (isset($pathArray['HTTP_HOST']) ? $pathArray['HTTP_HOST'] : '');
	}

	public static function getScriptName(){
		$pathArray = $_SERVER;
		return isset($pathArray['SCRIPT_NAME']) ? $pathArray['SCRIPT_NAME'] : (isset($pathArray['ORIG_SCRIPT_NAME']) ? $pathArray['ORIG_SCRIPT_NAME'] : '');
	}
	/**
	 * 
	 * 获得时区偏移值
	 */
	public static function getTimeZoneOffset(){
		$tNow = new DateTime();
		$tMins = $tNow->getOffset() / 60;
		$sgn = ($tMins < 0 ? -1 : 1);
		$tMins = abs($tMins);
		$tHours = floor($tMins / 60);
		$tMins -= $tHours * 60;
		return sprintf("%+d:%02d", $tHours*$sgn, $tMins);
	}

	/**
	 * @param string $pUrl 重定向目标地址
	 * @param integer $delay 延迟时间
	 * @return boolean
	 * @param delay
	 * @param pUrl
	 * 
	 * 重定向URL
	 */
	public static function redirect($pUrl='/',$delay=0,$permanent=false)	{
		if (headers_sent($file,$line)) {
			echo "<meta http-equiv=\"refresh\" content=\"{$delay};URL={$pUrl}\" />";
			exit;
		}
		if ($delay > 0) {
			header("Refresh:{$delay}; url={$pUrl}");
			exit;
		}
		
		$status = $permanent ? 301 : 302;
		$statusHeader = sprintf("HTTP/1.1 %s %s",$status,SOSO_Util_Util::get_status_header_desc($status));
		header($statusHeader); 
		header("Location:{$pUrl}");
	}

	/**
	 * @param integer $code code值
	 * @param string $backUrl 返回页地址
	 * 
	 * @param backUrl
	 * @param code
	 */
	public static function go2info($backUrl, $code,$file='prompt.php')	{
		$tParam = "?code=$code";
		if (strlen($backUrl)) {
			$tParam.= "&backUrl=".urlencode($backUrl);
		}
		$file = strlen($file) > 4 ? $file : 'prompt.php';
		self::redirect($file.$tParam);
		exit;
	}

	/**
	 * 按权重随机命中函数 
	 * 
	 * @param array $pArray 二维数组
	 * @param string $pOffset 键值
	 * @return string (返回命中的某个元素对应的key值)
	 */
	public static function random_weighted($pArray,$pOffset = NULL) {
		$innerArray = $pArray;
		if (!is_null($pOffset)) {
			$innerArray = array();
			foreach ($pArray as $key => $value) {
				$innerArray[$key] = $value[$pOffset];
			}
		}
		$rand = rand(0,array_sum($innerArray)-1);
		foreach ($innerArray as $key => $value) {
			if($rand >= $value) {
				$rand -= $value;
			}
			else {
				return $key;
			}
		}
	}

	/**
	 * 
	 * 数字转字节函数
	 * @param float $input
	 * @param int $precision 根据指定精度 precision （十进制小数点后数字的数目）进行四舍五入
	 */
	public static function byte_format($input, $precision =0){
		$prefix_arr = array("B", "K", "M", "G", "T",'P');
		$value = round($input, $precision);
		$i=0;
		while ($value>=1024) {
			$value /= 1024;
			$i++;
		}
		$return_str = round($value, $precision).$prefix_arr[$i];
		return $return_str;
	}

	/**
	 * Strip slashes recursively from array
	 *
	 * @param  array $value  the value to strip
	 *
	 * @return array clean value with slashes stripped
	 */
	public static function stripslashesDeep($value){
		return is_array($value) ? array_map(array('SOSO_Util_Util', 'stripslashesDeep'), $value) : stripslashes($value);
	}
	// code from php at moechofe dot com (array_merge comment on php.net)
	/*
	 * array arrayDeepMerge ( array array1 [, array array2 [, array ...]] )
	 *
	 * Like array_merge
	 *
	 *  arrayDeepMerge() merges the elements of one or more arrays together so
	 * that the values of one are appended to the end of the previous one. It
	 * returns the resulting array.
	 *  If the input arrays have the same string keys, then the later value for
	 * that key will overwrite the previous one. If, however, the arrays contain
	 * numeric keys, the later value will not overwrite the original value, but
	 * will be appended.
	 *  If only one array is given and the array is numerically indexed, the keys
	 * get reindexed in a continuous way.
	 *
	 * Different from array_merge
	 *  If string keys have arrays for values, these arrays will merge recursively.
	 */
	public static function arrayDeepMerge(){
		switch (func_num_args()){
			case 0:
				return false;
			case 1:
				return func_get_arg(0);
			case 2:
				$args = func_get_args();
				$args[2] = array();
				if (is_array($args[0]) && is_array($args[1])){
					foreach (array_unique(array_merge(array_keys($args[0]),array_keys($args[1]))) as $key)	{
						$isKey0 = array_key_exists($key, $args[0]);
						$isKey1 = array_key_exists($key, $args[1]);
						if ($isKey0 && $isKey1 && is_array($args[0][$key]) && is_array($args[1][$key]))	{
							$args[2][$key] = self::arrayDeepMerge($args[0][$key], $args[1][$key]);
						}else if ($isKey0 && $isKey1){
							$args[2][$key] = $args[1][$key];
						}else if (!$isKey1)	{
							$args[2][$key] = $args[0][$key];
						}else if (!$isKey0)	{
							$args[2][$key] = $args[1][$key];
						}
					}
					return $args[2];
				}
				else{
					return $args[1];
				}
			default :
				$args = func_get_args();
				$args[1] = self::arrayDeepMerge($args[0], $args[1]);
				array_shift($args);
				return call_user_func_array(array('SOSO_Util_Util', 'arrayDeepMerge'), $args);
				break;
		}
	}

	public static function arrayFlatten($pArray){
		$ret = array();
		foreach ($pArray as $v){
			if(is_array($v)) {
				$ret = array_merge($ret,self::arrayFlatten($v));
				continue;
			}
			$ret[] = $v;
		}
		return $ret;
	}

	/**
	 * 
	 * 过滤数组
	 * @param array $input
	 * @param mixed $val
	 * @param unknown_type $pKey
	 */
	public static function arrayFilter($input,$val,$pKey=null){
		$ret = array();
		if(is_callable($val)){
			return array_filter($input,$val);
		}
		foreach ($input as $k=>$v){
			if(is_null($pKey) && $v !== $val) $ret[] = $v;
			elseif(strlen($pKey) && $v[$pKey] != $val) $ret[] = $v;
		}
		return $ret;
	}

	/**
	 * <pre>
	 *   
	 * 将二维数组中指定key的值用指定符号连接在一起
	 * 
	 * $arr = array(array('id'=>1),array('id'=>2'),array('id'=>5));
	 * $ret = SOSO_Util_Util::Array2String($arr,id);
	 * var_dump($ret); => string(11) "'1','2','5'"
	 * </pre>
	 * 
	 * @param array[] $pArray
	 * @param string $pOffset
	 * @param string $pDelim
	 * @return array
	 */
	public static function Array2String($pArray,$pOffset,$pGlue="','"){
		$return = array();
		foreach ($pArray as $k=>$v){
			if (array_key_exists($pOffset,$v)) {
				$return[] = $v[$pOffset];
			}
		}

		return sprintf("'%s'",implode($pGlue,$return));
	}

	public static function Array2Array ($pArray,$pOffset='') {
		$return = Array();
		if (empty($pOffset)) {
			for($i=0; $i<count($pArray); $i++) {
				$keys = array_keys($pArray[$i]);
				for($j=0; $j<count($pArray[$i]); $j++) {
					$return[$keys[$j]][$i] = $pArray[$i][$keys[$j]];
				}
			}
		}else{
			for($i=0,$len=count($pArray);$i<$len;$i++){
				$return[$pOffset][] = isset($pArray[$i][$pOffset]) ? $pArray[$i][$pOffset] : '';
			}
		}
		return $return;
	}

	/**
	 * 以指定key的值做为第一维的键
	 * 如果第二维只有二个字段，即以指定key元素的值为键，另一个元素值为value
	 *
	 * @param array() $pArray  典型二维数组，第一维为数字索引，第二维是hash数组
	 * @param string $pKey   指定第二维元素的key
	 * @return array()
	 */
	public static function Array2Hash($pArray,$pKey=''){
		$return = Array();
		for($i=0;$i<count($pArray);$i++) {
			if(count($pArray[$i])==2) {
				$keys = array_keys($pArray[$i]);
				$index = array_search($pKey,$keys);
				$return[$pArray[$i][$pKey]] = $pArray[$i][$keys[1-intval($index)]];
			}
			else {
				$return[$pArray[$i][$pKey]] = $pArray[$i];
			}
		}
		return $return;
	}

	public function Array2Group($pArray,$pColumn){
		$return = Array();
		for($i=0;$i<count($pArray);$i++) {
			if (!isset($return[$pArray[$i][$pColumn]])) {
				$return[$pArray[$i][$pColumn]] = array();
			}
			array_push($return[$pArray[$i][$pColumn]],$pArray[$i]);
		}
		return $return;
	}
	/**
	 * 数组合并 - 类似于mysql的left join
	 *
	 * @param array $pList1 数组一
	 * @param array $pList2 数组二
	 * @param mixed $pColumn 字段名
	 * @param boolean $pForceMerge 如同名字段时，是否强制覆盖
	 * 
	 */
	public static function JOIN($pList1,$pList2,$pColumns,$pForceMerge=false){
		if (!$pList1) {
			return false;
		}
		if (is_array($pColumns) && count($pColumns) === 2) {
			$pColumnLeft = $pColumns[0];
			$pColumnRight = $pColumns[1];
		}elseif (is_string($pColumns)){
			if (strpos($pColumns,',') === false) {
				$pColumnLeft = $pColumnRight = $pColumns;
			}else{
				$pColumns = explode(",",$pColumns);
				$pColumnLeft = $pColumns[0];
				$pColumnRight = $pColumns[1];
			}
		}
		if (!$pList2 || !array_key_exists($pColumnLeft,$pList1[0]) 
				|| !array_key_exists($pColumnRight,$pList2[0]) ) {
			return $pList1;
		}

		$tHash = array();
		foreach ($pList2 as $k=>$v){
			$tHash[$v[$pColumnRight]] = $v;
		}
		foreach ($pList1 as $key=>$item){
			$val = $item[$pColumnLeft];
			if (array_key_exists($val,$tHash)) {
				$pList1[$key] = $pForceMerge ? array_merge($item,$tHash[$val]) : $item+$tHash[$val];
			}
		}
		return $pList1;
	}

	/**
	 * 获得指定类定义的常量
	 *
	 * @param mixed $pClass
	 * @param string $pNamed
	 */
	public static function getClassConstant($pClass,$pNamed='FILTERS'){
		if (class_exists($pClass) && strlen($pNamed) && defined("{$pClass}::{$pNamed}")) {
			return constant("{$pClass}::{$pNamed}");
		}
		return false;
	}
	public static function magicName($k,$pSeperator='_'){
		return 'm'.implode('',array_map('ucfirst',explode($pSeperator,$k)));
	}

	/**
	 * 
	 * 输出无浏览器缓存的HTTP头
	 */
	public static function nocache_headers() {
		@header( 'Expires: Wed, 11 Jan 1984 05:00:00 GMT' );
		@header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );
		@header( 'Cache-Control: no-cache, must-revalidate, max-age=0' );
		@header( 'Pragma: no-cache' );
	}

	/**
	 * apache_mod_loaded() - 检测是否加载了指定的apache模块?
	 *
	 * @param string $mod e.g. mod_rewrite
	 * @param boolean $default 默认返回值
	 * @return boolean
	 */
	public static function apache_mod_loaded($mod, $default = false) {
		if ( function_exists('apache_get_modules') ) {
			$mods = apache_get_modules();
			if ( in_array($mod, $mods) )
				return true;
		} elseif ( function_exists('phpinfo') ) {
			ob_start();
			phpinfo(8);
			$phpinfo = ob_get_clean();
			if ( false !== strpos($phpinfo, $mod) )
				return true;
		}
		return $default;
	}

	/**
	 * 检测指定url是否可以使用https访问
	 * @param string $url 待请求的URL地址
	 * @return bool 
	 */
	public static function url_is_accessable_via_ssl($url){
		//if (in_array('curl', get_loaded_extensions())) {
		if (extension_loaded('curl')){
			$ssl = preg_replace( '/^http:\/\//', 'https://',  $url );

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $ssl);
			curl_setopt($ch, CURLOPT_FAILONERROR, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

			curl_exec($ch);

			$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close ($ch);

			if ($status == 200 || $status == 401) {
				return true;
			}
		}
		return false;
	}

	/**
	 * 
	 * For PHP 5.2, make sure all output buffers are flushed
	 * before our singletons our destroyed.
	 *
	 */
	public static function ob_end_flush_all() {
		while (ob_get_level() > 0) {
			ob_end_flush();
		}
	}

	public static function cache_javascript_headers($charset='gbk') {
		$expiresOffset = 864000; // 10 days
		header( "Content-Type: text/javascript; charset=" . $charset );
		header( "Vary: Accept-Encoding" ); //处理代理
		header( "Expires: " . gmdate( "D, d M Y H:i:s", time() + $expiresOffset ) . " GMT" );
	}

	public static function get_status_header_desc($code) {
		$header_to_desc = array(
				100 => 'Continue',
				101 => 'Switching Protocols',

				200 => 'OK',
				201 => 'Created',
				202 => 'Accepted',
				203 => 'Non-Authoritative Information',
				204 => 'No Content',
				205 => 'Reset Content',
				206 => 'Partial Content',

				300 => 'Multiple Choices',
				301 => 'Moved Permanently',
				302 => 'Found',
				303 => 'See Other',
				304 => 'Not Modified',
				305 => 'Use Proxy',
				307 => 'Temporary Redirect',

				400 => 'Bad Request',
				401 => 'Unauthorized',
				403 => 'Forbidden',
				404 => 'Not Found',
				405 => 'Method Not Allowed',
				406 => 'Not Acceptable',
				407 => 'Proxy Authentication Required',
				408 => 'Request Timeout',
				409 => 'Conflict',
				410 => 'Gone',
				411 => 'Length Required',
				412 => 'Precondition Failed',
				413 => 'Request Entity Too Large',
				414 => 'Request-URI Too Long',
				415 => 'Unsupported Media Type',
				416 => 'Requested Range Not Satisfiable',
				417 => 'Expectation Failed',

				500 => 'Internal Server Error',
				501 => 'Not Implemented',
				502 => 'Bad Gateway',
				503 => 'Service Unavailable',
				504 => 'Gateway Timeout',
				505 => 'HTTP Version Not Supported'
					);

		if ( isset( $header_to_desc[$code] ) ){
			return $header_to_desc[$code];
		}
		return '';
	}

	public static function remote_fopen( $uri ) {
		$timeout = 10;
		$parsed_url = @parse_url( $uri );

		if ( !$parsed_url || !is_array( $parsed_url ) ){
			return false;
		}
		if ( !isset( $parsed_url['scheme'] ) || !in_array( $parsed_url['scheme'], array( 'http','https' ) ) ){
			$uri = 'http://' . $uri;
		}
		if ( ini_get( 'allow_url_fopen' ) ) {
			$fp = @fopen( $uri, 'r' );
			if ( !$fp ){
				return false;
			}
			stream_set_timeout($fp, $timeout); // Requires php 4.3
			$linea = '';
			while ( $remote_read = fread( $fp, 4096 ) ){
				$linea .= $remote_read;
			}
			fclose( $fp );
			return $linea;
		} elseif ( function_exists( 'curl_init' ) ) {
			$handle = curl_init();
			curl_setopt( $handle, CURLOPT_URL, $uri);
			curl_setopt( $handle, CURLOPT_CONNECTTIMEOUT, 1 );
			curl_setopt( $handle, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $handle, CURLOPT_TIMEOUT, $timeout );
			$buffer = curl_exec( $handle );
			curl_close( $handle );
			return $buffer;
		} else {
			return false;
		}
	}

	/**
	 * 
	 * 根据状态码输出相应的HTTP头
	 * @param int $header
	 */
	public static function status_header( $header ) {
		$text = self::get_status_header_desc( $header );

		if ( empty( $text ) ){
			return false;
		}

		$protocol = $_SERVER["SERVER_PROTOCOL"];
		if ( 'HTTP/1.1' != $protocol && 'HTTP/1.0' != $protocol ){
			$protocol = 'HTTP/1.0';
		}
		$status_header = "$protocol $header $text";

		if ( version_compare( phpversion(), '4.3.0', '>=' ) ){
			return @header( $status_header, true, $header );
		}else{
			return @header( $status_header );
		}
	}
	}
