<?php
// +----------------------------------------------------------------------+
// | PEAR :: System :: Socket :: Listener                                 |
// +----------------------------------------------------------------------+
// | This source file is subject to version 3.0 of the PHP license,       |
// | that is available at http://www.php.net/license/3_0.txt              |
// | If you did not receive a copy of the PHP license and are unable      |
// | to obtain it through the world-wide-web, please send a note to       |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Copyright (c) 2004 Michael Wallner <mike@iworks.at>                  |
// +----------------------------------------------------------------------+
//
// $Id$

/**
* System::Socket::Listener
* 
* @author       Michael Wallner <mike@php.net>
* @package      System_Socket
* @category     System
* @example      unix-listener.php   Simple unix socket listener example
* @example      echo-server.php     Simple TCP echo server example
* @example      access-rights.php   Introduction to IP access rights
*/

if (!defined('SYSTEM_SOCKET_ROOT')) {
    define('SYSTEM_SOCKET_ROOT', dirname(__FILE__) . '/..');
}

require_once SYSTEM_SOCKET_ROOT . '/Socket/Manager.php';
require_once SYSTEM_SOCKET_ROOT . '/Socket/Connection.php';

/** 
* System_Socket_Listener
* 
* Example:
* <code>
*   require_once 'System/Socket.php';
*   $Listener = &System_Socket::createListener(array(
*       'address'   => '/tmp/pear.sock',
*       'domain'    => AF_UNIX,
*       'proto'     => SOL_SOCKET,
*   ));
*   while ($Listener->hasSocket) {
*       $Pool = &$Listener->getReadableClients();
*       while ($Connection = &$Pool->shift()) {
*           if (trim($line = $Connection->readLine()) == 'exit') {
*               $Connection->close();
*               $Pool->close();
*               $Listener->close();
*           }
*           echo $line;
*       }
*       usleep(10);
*   }
* </code>
*
* @author   Michael Wallner <mike@php.net>
* @version  $Revision$
* @access   public
*/
class System_Socket_Listener extends System_Socket_Manager
{
    /**
    * Clients
    * 
    * @access public
    * @readonly
    */
    var $Clients = array();
    
    /**
    * hostsAllow/hostsDeny
    * 
    * 'allow' | 'deny' | empty
    * 
    * @access   public
    * @var      string
    */
    var $allowDeny = null;
    
    /**
    * Classname of the ConnectionPool object
    * 
    * @access   private
    * @var      string
    */
    var $_poolClass = 'System_Socket_ConnectionPool';
    
    /**
    * allowed/denied hosts
    * 
    * @access   private
    * @var      array
    */
    var $_hosts = array();
    
    /**
    * Constructor
    *
    * @access   public
    * @return   object  System_Socket_Listener
    * @param    object  SystemSocket $socket
    */
    function System_Socket_Listener(&$socket)
    {
        System_Socket_Listener::__construct($socket);
    }
    
    /**
    * ZE2 Constructor
    * @ignore
    */
    function __construct(&$socket)
    {
        parent::__construct($socket);
        if ($this->hasSocket) {
            $this->Socket->setOption(SO_REUSEADDR, true);
        }
    }
    
    /**
    * Destructor
    * @ignore
    */
    function _System_Socket_Listener()
    {
        $this->__destruct();
    }
    
    /**
    * ZE2 Destructor
    * @ignore
    */
    function __destruct()
    {
        foreach ($this->Clients as $clientSock) {
            $this->closeSocket($clientSock);
        }
        parent::__destruct();
        if (    $this->Socket->domain == AF_UNIX && 
                is_file($this->Socket->address)) {
            @unlink($this->Socket->address);
        }
        $this->notify(__FUNCTION__, true);
    }
    
    /**
    * Get clients
    *
    * @access   private
    * @return   array
    * @param    bool    $withBaseSocket
    */
    function _getClients($withBaseSocket = false)
    {
        $clients = $withBaseSocket ? array($this->Socket->getResource()) : array();
        foreach($this->Clients as $key => $client){
            if (System_Socket::isResource($client)) {
                $clients[] = $client;
            } else {
                unset($this->Clients[$key]);
            }
        }
        return $this->notify(__FUNCTION__, $clients, $withBaseSocket);
    }
    
    /** 
    * Set ConnectionPool class
    * 
    * Set the classname of a (custom) ConnectionPool.
    * 
    * @access   public
    * @return   bool
    * @param    string  $poolClass  the name of an existing class
    */
    function setPoolClass($poolClass = 'System_Socket_ConnectionPool')
    {
        if (class_exists($poolClass)) {
            $this->_poolClass = $poolClass;
            return true;
        }
        return false;
    }
    
    /**
    * Get connection pool
    *
    * @access   private
    * @return   object  System_Socket_ConnectionPool
    * @param    array   array of socket resources to check the clients against
    */
    function &_getConnPool($check)
    {
        $PoolClass = $this->_poolClass;
        return new $PoolClass($s = $this->notify(__FUNCTION__,
            array_intersect($this->Clients, $check), $check));
    }
    
    /**
    * Select readable sockets
    *
    * @access   private
    * @return   bool
    * @param    array   $into
    */
    function _selectReadable(&$into)
    {
        return $this->notify(__FUNCTION__,
            @socket_select($into, $w = null, $e = null, 0), $into);
    }
    
    /**
    * Select writable sockets
    *
    * @access   private
    * @return   bool
    * @param    array   $into
    */
    function _selectWritable(&$into)
    {
        return $this->notify(__FUNCTION__,
            @socket_select($r = null, $into, $e = null, 0), $into);
    }
    
    /**
    * Select exceptional sockets
    *
    * @access   private
    * @return   bool
    * @param    array   $into
    */
    function _selectExceptional(&$into)
    {
        return $this->notify(__FUNCTION__,
            @socket_select($r = null, $w = null, $into, 0), $into);
    }
    
    /**
    * Accepts a socket connection
    *
    * @access   private
    * @return   void
    * @param    array   $pendingSockets
    */
    function _acceptSocket(&$pendingSockets)
    {
        $key = array_search($this->Socket->getResource(), $pendingSockets);
        if (isset($key) && $key !== false) {
            if ($this->hasAccessRights($socket = $this->Socket->accept())) {
                $this->Clients[] = $socket;
            } else {
                $this->closeSocket($socket);
            }
            unset($pendingSockets[$key]);
            $this->notify(__FUNCTION__, $socket, $pendingSockets);
        } else {
            $this->notify(__FUNCTION__, false, $pendingSockets);
        }
    }
    
    /**
    * Get readable clients
    * 
    * @access   public
    * @return   object
    */
    function &getReadableClients()
    {
        $this->_selectReadable($pending = $this->_getClients(true));
        $this->_acceptSocket($pending);
        return $this->_getConnPool($pending);
    }
    
    /**
    * Get writable clients
    *
    * @access   public
    * @return   object
    */
    function &getWritableClients()
    {
        $this->_selectWritable($pending = $this->_getClients(true));
        return $this->_getConnPool($pending);
    }
    
    /**
    * Get exceptional clients
    *
    * @access   public
    * @return   object
    */
    function &getExceptionalClients()
    {
        $this->_selectExceptional($pending = $this->_getClients(true));
        return $this->_getConnPool($pending);
    }
    
    /**
    * Get all Clients
    * 
    * @access   public
    * @return   object  System_Socket_ConnectionPool
    */
    function &getClients()
    {
        $PoolClass = $this->_poolClass;
        return new $PoolClass($socks = $this->_getClients());
    }
    
    /**
    * hostsAllow
    * 
    * @access   public
    * @return   mixed
    * @param    string  $mask   network (address) to allow
    */
    function hostsAllow($mask)
    {
        return $this->hostsAllowDeny($mask, true);
    }
    
    /**
    * hostsDeny
    * 
    * @access   public
    * @return   mixed
    * @param    string  $mask   network (address) to deny
    */
    function hostsDeny($mask)
    {
        return $this->hostsAllowDeny($mask, false);
    }
    
    /**
    * hostsReset
    * 
    * @access   public
    * @return   void
    * @param    string  $allowDeny  will become System_Socket_Listener::allowDeny
    */
    function hostsReset($allowDeny = null)
    {
        $this->allowDeny = $allowDeny;
        $this->_hosts = array();
    }
    
    /**
    * hostsAllowDeny
    * 
    * @access protected
    * @return mixed
    */
    function hostsAllowDeny($mask, $allow)
    {
        if (!@include_once 'Net/IPv4.php') {
            return $this->raiseError(
                'Net_IPv4 is needed to provide this functionality');
        }
        // !yuk
        if (!isset($GLOBALS['Net_IPv4_Netmask_Map'])) {
            $GLOBALS['Net_IPv4_Netmask_Map'] = $Net_IPv4_Netmask_Map;
        }
        if (!strstr($mask, '/')) {
            $mask .= '/32';
        }
        if (PEAR::isError($ip = Net_IPv4::parseAddress($mask))) {
            return $ip;
        }
        $this->_hosts[$ip->bitmask][$ip->network] = $allow;
        ksort($this->_hosts);
        return true;
    }
    
    /**
    * hasAccessRights
    * 
    * Check whether a certain socket resource has access rights.
    * 
    * @access public
    * @return bool
    * @param  int   socket resource
    */
    function hasAccessRights($socket)
    {
        if (!$this->allowDeny || $this->Socket->domain != AF_INET) {
            return true;
        }
        if (!System_Socket::isResource($socket) || 
            !@socket_getPeername($socket, $ip)) {
            return false;
        }
        $maskList = array_keys($this->_hosts);
        while ($bitmask = array_pop($maskList)) {
            $networks = $this->_hosts[$bitmask];
            $acrights = Net_IPv4::parseAddress($ip . '/' . $bitmask);
            if (isset($networks[$acrights->network])) {
                return $networks[$acrights->network];
            }
        }
        return ($this->allowDeny != 'allow');
    }
}
?>
