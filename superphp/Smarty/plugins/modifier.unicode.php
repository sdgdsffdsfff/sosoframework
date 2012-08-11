<?php

function smarty_modifier_unicode($str,$from="gbk"){
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
