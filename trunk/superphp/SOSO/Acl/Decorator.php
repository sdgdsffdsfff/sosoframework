<?php
/**
 * @author moonzhang (zyfunny@gmail.com)
 * @version 1.0.0.1 2010-05-04 alpha
 */
class SOSO_Acl_Decorator extends SOSO_Proxy implements SOSO_Acl_Interface {
	
	/**
	 * Enter description here...
	 *
	 * @var SOSO_Acl_Interface
	 */
	protected $acl;
	
	public function __construct(SOSO_Acl_Interface $auth,$pUsername=''){
		$this->acl = $auth;	
		parent::__construct($auth,$pUsername);
	}
	
	/**
	 * 根据用户名得到权限身份
	 *
	 * @param string $pUsername
	 * @throws ErrorException
	 */
	function setUser($pUsername){
		return $this->acl->setUser($pUsername);
	}
	
	/**
	 * 获得指定用户的所有角色
	 *
	 * @param int $pUserID
	 */
	function getRoles($pUserID){
		return $this->acl->getRoles($pUserID);
	}
	
	function getParentRoles($pRoleList){
		return $this->acl->getParentRoles($pRoleList);
	}
	
	/**
	 * 获得数组列表的所有权限
	 *
	 * @param mixed $pRoleIDList
	 */
	function getRoleRights($pRoleIDList){
		return $this->acl->getRoleRights($pRoleIDList);
	}
	
	/**
	 * 获得当前用户的所有权限
	 *
	 */
	function getRights(){
		return $this->acl->getRights();
	}
	
	function getImplyingRights(){
		return $this->acl->getImplyingRights();
	}
	
	function getImpliedRights(){
		return $this->acl->getImpliedRights();
	}
	
	/**
	 * 如果用户是管理员，获得其负责的所有模块
	 *
	 * @param int $pUserID
	 */
	function getDutiedModules($pUserID){
		return $this->acl->getDutiedModules($pUserID);
	}
	/**
	 * Enter description here...
	 *
	 * @return Acl_Concrete
	 */
	public function getAcl(){
		return $this->acl;
	}
	
	 public function __get($member){
		return $this->acl->$member;
	}
}