<?php
// +----------------------------------------------------------------------+
// | PEAR :: System :: Socket :: Creator                                  |
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
* System::Socket::Creator
* 
* @author       Michael Wallner <mike@php.net>
* @package      System_Socket
* @category     System
*/

if (!defined('SYSTEM_SOCKET_ROOT')) {
    define('SYSTEM_SOCKET_ROOT', dirname(__FILE__) . '/..');
}

require_once SYSTEM_SOCKET_ROOT . '/Socket.php';

/** 
* System_Socket_Creator
*
* @author   Michael Wallner <mike@php.net>
* @version  $Revision$
* @access   public
*/
class System_Socket_Creator
{
    /**
    * Create a System_Socket_Connection object
    *
    * @access   public
    * @return   object  System_Socket_Connection or PEAR_Error on failure
    * @param    array   $socketParams   associative array of System_Socket options
    */
    function &createConnection($socketParams = array())
    {
        require_once SYSTEM_SOCKET_ROOT . '/Socket/Connection.php';

        $sock = &new System_Socket;
        foreach ($socketParams as $property => $value) {
            $sock->$property = $value;
        }

        if ($sock->create() && $sock->connect()) {
            return new System_Socket_Connection($sock);
        }

        return System_Socket::lastError();
    }

    /**
    * Create a System_Socket_Listener object
    *
    * @access   public
    * @return   object  System_Socket_Listener or PEAR_Error on failure
    * @param    array   $socketParams   associative array of System_Socket options
    */
    function &createListener($socketParams = array())
    {
        require_once SYSTEM_SOCKET_ROOT . '/Socket/Listener.php';
        
        $sock = &new System_Socket;
        foreach ($socketParams as $property => $value) {
            $sock->$property = $value;
        }
        
        if ($sock->create() && $sock->bind() && $sock->listen()) {
            return new System_Socket_Listener($sock);
        }
        
        return System_Socket::lastError();
    }
    
    /** 
    * Create a unix socket listener
    * 
    * @access   public
    * @return   object
    * @param    string  path to unix socket
    */
    function &createUnixListener($path)
    {
        require_once SYSTEM_SOCKET_ROOT . '/Socket/Listener.php';
        
        $sock = &new System_Socket;
        $sock->domain  = AF_UNIX;
        $sock->proto   = SOL_SOCKET;
        $sock->port    = 0;
        $sock->address = $path;
        
        if ($sock->create() && $sock->bind() && $sock->listen()) {
            return new System_Socket_Listener($sock);
        }
        
        return System_Socket::lastError();
    }
    
    /** 
    * Create a unix socket connection
    * 
    * @access   public
    * @return   object
    * @param    string  $path   path to unix socket
    */
    function &createUnixConnection($path)
    {
        require_once SYSTEM_SOCKET_ROOT . '/Socket/Connection.php';

        $sock = &new System_Socket;
        $sock->domain  = AF_UNIX;
        $sock->proto   = SOL_SOCKET;
        $sock->port    = 0;
        $sock->address = $path;

        if ($sock->create() && $sock->connect()) {
            return new System_Socket_Connection($sock);
        }

        return System_Socket::lastError();
    }
    
    /** 
    * Create a TCP listener
    * 
    * @access   public
    * @return   object
    * @param    string  $address
    * @param    int     $port
    */
    function &createTcpListener($address, $port)
    {
        require_once SYSTEM_SOCKET_ROOT . '/Socket/Listener.php';
        
        $sock = &new System_Socket;
        $sock->address = $address;
        $sock->port    = $port;
        
        if ($sock->create() && $sock->bind() && $sock->listen()) {
            return new System_Socket_Listener($sock);
        }
        
        return System_Socket::lastError();
    }
    
    /** 
    * Create a TCP connection
    * 
    * @access   public
    * @return   object
    * @param    string  $address
    * @param    int     $port
    */
    function &createTcpConnection($address, $port)
    {
        require_once SYSTEM_SOCKET_ROOT . '/Socket/Connection.php';

        $sock = &new System_Socket;
        $sock->address = $address;
        $sock->port    = $port;

        if ($sock->create() && $sock->connect()) {
            return new System_Socket_Connection($sock);
        }

        return System_Socket::lastError();
    }
    
    /** 
    * Create an UDP listener
    * 
    * @access   public
    * @return   object
    * @param    string  $address
    * @param    int     $port
    */
    function &createUdpListener($address, $port)
    {
        require_once SYSTEM_SOCKET_ROOT . '/Socket/Listener.php';
        
        $sock = &new System_Socket;
        $sock->proto   = SOL_UDP;
        $sock->type    = SOCK_DGRAM;
        $sock->address = $address;
        $sock->port    = $port;
        
        if ($sock->create() && $sock->bind() && $sock->listen()) {
            return new System_Socket_Listener($sock);
        }
        
        return System_Socket::lastError();
    }
    
    /** 
    * Create an UDP connection
    * 
    * @access   public
    * @return   object
    * @param    string  $address
    * @param    int     $port
    */
    function &createUdpConnection($address, $port)
    {
        require_once SYSTEM_SOCKET_ROOT . '/Socket/Connection.php';

        $sock = &new System_Socket;
        $sock->type    = SOCK_DGRAM;
        $sock->proto   = SOL_UDP;
        $sock->address = $address;
        $sock->port    = $port;

        if ($sock->create() && $sock->connect()) {
            return new System_Socket_Connection($sock);
        }

        return System_Socket::lastError();
    }
}
?>