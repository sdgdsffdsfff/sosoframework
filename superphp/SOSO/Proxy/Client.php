<?php
/**
 * @author moonzhang (zyfunny@gmail.com)
 * @version 0.0.0.1 2008-05-11 21:30
 * 
 */
/**
 * web service -> client ¿‡
 */
require_once("Tools/HessianPHP/HessianClient.php");
class SOSO_Proxy_Client extends HessianClient {
	public function __construct($pURL,$pOptions=array()){
		parent::__construct($pURL,$pOptions);
	}
}