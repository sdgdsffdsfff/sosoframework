<?php
if (!defined('APP_NAME')){
	define('APP_NAME','mytest');
}
$path = get_include_path();
define("ROOT",dirname(dirname(__FILE__)));
require_once ROOT."/SOSO/SOSO.php";

$soso = new SOSO(dirname(__FILE__).'/web.xml');
$_SERVER['argc'] = 2;
$_SERVER['argv'] = array(basename(__FILE__),'Bootstrap');

class Bootstrap extends SOSO_Page{
	public function run(){
		echo "system init";
	}
}
set_include_path(get_include_path().PATH_SEPARATOR.$path);
//$soso->serve();

require_once ROOT.'/SOSO/ORM/Criteria.php';
require_once ROOT.'/SOSO/ORM/Join.php';
require_once ROOT.'/SOSO/DB/PDOSQLCommand.php';
require_once ROOT.'/SOSO/DB/SQLCommand.php';
require_once ROOT.'/SOSO/DB/Driver/PDOMySQL.php';
require_once ROOT.'/SOSO/DB/Driver/MySQL.php';
require_once ROOT.'/SOSO/Util/Pagination.php';
require_once ROOT.'/SOSO/Cache.php';
require_once ROOT.'/SOSO/Exception.php';
require_once ROOT.'/SOSO/Filter.php';
