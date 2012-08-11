<?PHP
/**
 * SOSO Framework
 * 
 * @category   SOSO
 * @package    SOSO_View
 * @description Templates' Factory
 * @copyright  Copyright (c) 2007-2008 Soso.com
 * @author moonzhang
 * @version 1.0
 * @created 15-кдтб-2008 16:59:24
 */
class SOSO_View_Exception extends SOSO_Exception {
	public function __construct($message){
		parent::__construct($message,110);
	}
}