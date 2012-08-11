<?php
/**
 * SOSO Framework
 * @category   SOSO
 * @package    SOSO_Controller
 * @copyright  Copyright (c) 2007-2008 Soso.com
 * @author moonzhang
 * @version 1.0
 * @created 17-кдтб-2008 23:32:12
 */
class SOSO_Controller_Exception extends SOSO_Exception {
	public function __construct($pMessage=''){
		parent::__construct($pMessage,112);
	}
}
