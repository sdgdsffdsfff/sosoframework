<?php
/**
 * Permission Ȩ�޹������
 * @author moonzhang
 * @version 1.0
 * @created 15-����-2008 16:59:23
 */
class SOSO_Util_Permission {

	public $mPermissions = array (
		1 => '1',
		2 => '2',
		4 => '4',
		8 => '8',
		16 => '16',
		);

	public function __construct(){
	}



	public function getPermissions(){
	}

	/**
	 * 
	 * @param pPermissions
	 */
	public function mergePermissions($pPermissions){
	}

	/**
	 * 
	 * @param pPermission
	 * @param pUserPermission
	 */
	public function authorization($pPermission, $pUserPermission){
	}

	/**
	 * 
	 * @param pUser
	 * @param pObject
	 * @param pMethod
	 */
	public static function InvokePermissions($pUser, $pObject, $pMethod){
	}

}
?>