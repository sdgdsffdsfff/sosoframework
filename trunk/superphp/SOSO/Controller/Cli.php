<?php

/**
 * SOSO Framework
 * @category   SOSO
 * @package    SOSO_Controller
 * @copyright  Copyright (c) 2007-2008 Soso.com
 * @author moonzhang
 * @version 1.0
 * @created 15-四月-2008 16:59:19
 */
class SOSO_Controller_Cli extends SOSO_Object implements SOSO_Controller_Abstract {

    private $mClass = '';

    public function __construct() {
        parent::__construct();
        $_SERVER['argc'] > 1 || $this->error("需要指定类名") || exit();

        array_shift($_SERVER['argv']);

        $class_name = array_shift($_SERVER['argv']);
        if (!class_exists($class_name)) {
            $this->error('要执行的类' . $class_name . "不存在");
            exit;
        }

        $parameters = array();
        if (!empty($_SERVER['argv'])) {
            parse_str(implode('&', $_SERVER['argv']), $parameters);
            $_GET = $_REQUEST = $parameters;
        }
        $this->mClass = $class_name;
    }

    public function dispatch($pClass=null) {
        $class = $this->getClass();
        try{
            $instance = new $class();
            $instance->run();
        }catch(Exception $e){
            echo $e->getMessage();
            echo $e->getTraceAsString();
            
        }
    }

    private function error($msg) {
        echo $msg . "\r\n";
        return false;
    }

    public function getClass() {
        return $this->mClass;
    }

}
