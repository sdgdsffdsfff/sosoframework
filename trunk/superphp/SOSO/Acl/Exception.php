<?php
/**
 * SOSO Framework
 * 
 * @category   SOSO
 * @package    SOSO
 * ACL вьГЃРр
 * @copyright  Copyright (c) 2007-2008 Soso.com
 * @author moonzhang
 * @version 1.0
 */
class SOSO_Acl_Exception extends SOSO_Exception {
	public function __construct($error){
		parent::__construct($error,120);
	}
}