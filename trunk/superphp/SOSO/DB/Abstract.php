<?php
interface SOSO_DB_Abstract {
	
	function getTableFields($pTable);

	function getTablePK($pTable);

	function db_insert_id();

	function db_affected_rows();

	function db_num_rows($pResult) ;

	function db_fetch_array($pResult ,$pResultType = 'both');

	function db_free_result($pResult);

	function db_query($pQuery);

	function db_connect();

	function db_close();

	function db_data_seek($result_identifier, $row_number);
	
	function limit($sql, $pPageNo=0, $pPageSize = 10);
	
	function getLastQuery();
	
	function select_db($pDB);
	
	function setCharset($pCharset='gbk');
}