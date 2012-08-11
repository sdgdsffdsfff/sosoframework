<?php

/**
 * SOSO Framework
 * 
 * @category   SOSO
 * @package    SOSO
 * @description 异常类
 * @copyright  Copyright (c) 2007-2008 Soso.com
 * @author moonzhang
 * @version 1.0
 * @created 15-四月-2008 16:59:20
 */
class SOSO_Exception extends Exception {

    /**
     * exception message
     */
    public $message = 'Unknown exception';
    /**
     * user defined exception code
     */
    public $code = 0;
    /**
     * source filename of exception
     */
    public $file;
    /**
     * source line of exception
     */
    public $line;
    /**
     * backtrace of exception
     */
    public $trace;
    /**
     * internal only!!
     */
    public $string;

    /**
     * 
     * @param message
     * @param code
     */
    public function __construct($message = NULL, $code=0, $line='') {
        parent::__construct($message, $code);
    }

    public function __toString() {
        return $this->message . "[{$this->getCode()}];";
    }

    /**
     * 
     * @param exception
     */
    public static function StringFormat($exception) {
        
    }

    /**
     * 
     * @param exception
     * @return mixed trace
     */
    public static function TraceFormat($exception) {
        return $this->trace;
    }

}