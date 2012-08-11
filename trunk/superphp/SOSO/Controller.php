<?php

/**
 * SOSO Framework
 *
 * @category   SOSO
 * @package    SOSO
 * @description ������
 * @copyright  Copyright (c) 2007-2008 Soso.com
 * @author moonzhang
 * @version 1.0
 * @created 15-����-2008 16:59:19
 */
//require_once 'Controller/Exception.php';
class SOSO_Controller /*extends SOSO_Object*/ implements SOSO_Controller_Abstract {

	/**
	 * ҳ������ʹ�õĹ������б�
	 *
	 * @var SOSO_Filter_Abstract[]
	 */
	private $mFilters = array();
	private $mRequest;
	protected $mAction = 'run';
	/**
	 * ����������
	 *
	 * @var SOSO_Controller_Abstract | SOSO_Controller_Browser | SOSO_Controller_Cli [..]
	 */
	private $innerController;
	/**
	 * SOSO_Filter instance (singleton)
	 *
	 * @var SOSO_Filter
	 */
	private $mFilterInstance;

	public function __construct() {
		if ('cli' === php_sapi_name()) {
			$this->innerController = new SOSO_Controller_Cli();
			$this->getFilters($this->innerController->getClass(), true);
			$context = SOSO_Frameworks_Context::getInstance();
			$context->set('filters', $this->mFilters);
			$context->set('page_class', $this->innerController->getClass());
			$context->set('cacheTime', $this->getConstant($this->innerController->getClass(), 'CACHE_TIME'));
		} else {
			//add some router stuffs

			$this->doSession();
			$app = SOSO_Application::getInstance();
			$info = $app->parseRequest($_SERVER['SCRIPT_NAME']);
			$baseController = $info['controller'];
			$request = $info['request'];
			
			$this->mRequest = $request;
			$this->mAction = $info['action'];
			if (($tFilters = $this->getConstant($request)) !== false && strlen(trim($tFilters))) {
				$this->mFilters = explode(',', $tFilters);
			}
			
			//����ȫ�ֱ���
			$context = SOSO_Frameworks_Context::getInstance();
			$context->set('filters', $this->mFilters);
			$context->set('cacheTime', $this->getConstant($request, 'CACHE_TIME'));
			$context->set('page_class', $request);
			$this->innerController = new $baseController();
		}
		if (!empty($this->mFilters)) {
			$this->mFilterInstance = SOSO_Filter::getInstance();
		}
	}

	public function dispatch($request=null) {
		if (count(array_filter($this->mFilters))) {
			$filter = array_shift($this->mFilters);
			try {
				$this->mFilterInstance->doFilter(new $filter(), $this);
			} catch (Exception $e) {
				echo $e->getTraceAsString();
			}
		} else {
			$this->innerController->dispatch($this->mRequest);
		}
	}


	/**
	 * ���ָ���ඨ��ĳ���
	 *
	 * @param mixed $pClass
	 * @param string $pNamed
	 */
	protected function getConstant($pClass, $pNamed='FILTERS') {
		if (class_exists($pClass) && strlen($pNamed) && defined("{$pClass}::{$pNamed}")) {
			return constant("{$pClass}::{$pNamed}");
		}
		return false;
	}

	/**
	 * ��ù�����
	 *
	 * @param string $class Ҫִ�е�����
	 * @param boolean $notBrowserBased �Ƿ��ǻ���browser��������,true��ʾ����
	 * ��browser��false�෴��
	 * @return mixed
	 */
	protected function getFilters($class, $notBrowserBased=false) {
		$interfaces = class_implements($class);
		if ($notBrowserBased || in_array('SOSO_Interface_Runnable', $interfaces)) {
			if (($tFilters = $this->getConstant($class)) !== false && strlen(trim($tFilters))) {
				$this->mFilters = explode(',', $tFilters);
			}
			return true;
		}
		throw new Exception("ҳ����(<b>$class</b>)����ʵ��SOSO_Interface_Runnable�ӿ�", 011);
	}

	public function doSession() {
		$tProject = SOSO_Frameworks_Registry::getInstance()->get('project');
		if (isset($tProject['session']) && strval($tProject['session']) == 'disable') {
			return false;
		}
		$tConfig = array();
		if ($tProject->session) {
			$tConfig = current((array) $tProject->session);

			if (isset($tConfig['session_id_var'])
			&& strlen($tConfig['session_id_var'])
			&& isset($_REQUEST[$tConfig['session_id_var']])) {

                $tConfig['session_id'] = $_REQUEST[$tConfig['session_id_var']];
            }
        }
        require_once 'Session.php';
        SOSO_Session::start($tConfig);
    }

}
