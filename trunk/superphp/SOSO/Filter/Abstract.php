<?php
/**
 * SOSO Framework
 * @category   SOSO
 * @package    SOSO_Controller
 * @copyright  Copyright (c) 2007-2008 Soso.com
 * @author moonzhang
 * @version 1.0
 * @created 15-кдтб-2008 16:59:19
 */
abstract class SOSO_Filter_Abstract {
	abstract function doPreProcessing(SOSO_Frameworks_Context $context);
	abstract function doPostProcessing(SOSO_Frameworks_Context $context);
}