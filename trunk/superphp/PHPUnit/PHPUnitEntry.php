<?php
$path = dirname(__FILE__);
set_include_path($path.PATH_SEPARATOR.dirname($path));
require dirname(__FILE__) . DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR . 'Autoload.php';
require_once(dirname(dirname(__FILE__)) . '/SOSO/SOSO.php');
require_once(dirname(__FILE__) . '/TextUI/Command.php');
PHPUnit_TextUI_Command::main();
?>
