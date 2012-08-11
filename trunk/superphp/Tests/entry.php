<?PHP
//ÏîÄ¿Ãû
define('APP_NAME','mytest');
require_once dirname(dirname(__FILE__))."/SOSO/SOSO.php";
function main(){
$soso = new SOSO();
$soso->Serve();
}
main();
?>
