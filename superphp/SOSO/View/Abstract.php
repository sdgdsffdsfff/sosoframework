<?php
/**
 * SOSO Framework
 * 
 * @category   SOSO
 * @package    SOSO_View
 * @author moonzhang
 * @version 1.0 15-04-2008 16:59:24
 * Templates' Factory
 */
abstract class SOSO_View_Abstract /*extends SOSO_Object*/ implements SOSO_Interface_Runnable{
	//abstract function run();
	abstract function showMessage($pMsg = '',$pButtons=array(array('name' => 'È·¶¨', 'url' => '')), $pFile = 'tpl.msg.html');
	abstract function initTemplate();
}