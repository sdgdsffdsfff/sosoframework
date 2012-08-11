<?php
/**
 * @author moonzhang
 * @version 1.0 2008-05-08
 *
 */
 /**
  * 登录认证接口
  */
interface SOSO_Auth_Interface {

    /**
    * 得到当前请求用户的唯一标识
    * @return   void
    */
    function getIdentify($key='currentUser');

    /**
    * 返回当前请求用户的名称
    * @return   void
    */
    function getName($key='currentUser');

    /**
    * 检查当前请求的用户是否是合法用户
    * @return   void
    */
    function isAuthorized($key='currentUser');

    /**
    * 进行用户认证,认证成功返回true，否则返回false
    * @param    string $pUserName
    * @param    string $pPassword
    * @return   boolean
    */
    function login($pUserName, $pPassword, $pBackUrl='/');

    /**
    * 登录退出
    * @return   boolean
    */
    function logout($pBackUrl=null);
}