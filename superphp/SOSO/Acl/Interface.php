<?php
interface SOSO_Acl_Interface {
	/**
	 * 根据用户名得到权限身份
	 *
	 * @param string $pUsername
	 * @throws ErrorException
	 */
	function setUser($pUsername);
	
	/**
	 * 获得指定用户的所有组
	 *
	 * @param int $pUserID
	 */
	function getRoles($pUserID);
	
	function getParentRoles($pRoleList);
	
	/**
	 * 获得数组列表的所有权限
	 *
	 * @param mixed $pRoleIDList
	 */
	function getRoleRights($pRoleIDList);
	
	/**
	 * 获得当前用户的所有权限
	 *
	 */
	function getRights();
	
	function getImplyingRights();
	
	function getImpliedRights();
	
	/**
	 * 如果用户是管理员，获得其负责的所有模块
	 *
	 * @param int $pUserID
	 */
	function getDutiedModules($pUserID);

}