<?php
// +----------------------------------------------------------------------+
// | PEAR :: System :: Socket                                             |
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
* System::Socket
* 
* @author       Michael Wallner <mike@php.net>
* @package      System_Socket
* @category     System
*/

if (!defined('SYSTEM_SOCKET_ROOT')) {
    define('SYSTEM_SOCKET_ROOT', dirname(__FILE__));
}

require_once 'PEAR.php';

/** 
* System_Socket
*
* The System_Socket class represents a socket resource and aims to provide a
* thight ad robust API for handling those.  Beside that it implements a creator
* for System_Socket_Connection and System_Socket_Listener.  You'd most probably
* never use this class or an object of it directly, but the creator pattern.
* However this doesn't mean that you do not need to know about its socket 
* configuration options.
* 
* Example: Simple HTTP request to localhost
* <code>
*   require_once 'System/Socket.php';
* 
*   $conn = &System_Socket::createConnection(
*       array('address' => '127.0.0.1')
*   );
*   if (PEAR::isError($conn)) {
*       die($conn->getMessage());
*   }
* 
*   $conn->writeLine('GET / HTTP/1.0');
*   $conn->writeLine('Connection: close');
*   $conn->writeLine();
* 
*   while ($data = $conn->read()) {
*       echo $data;
*   }
* </code>
* 
* @author   Michael Wallner <mike@php.net>
* @version  $Revision$
* @access   public
*/
class System_Socket
{
    /**
    * Socket resource
    * @access private
    */
    var $_rsrc = null;
    
    /**
    * Blocking mode
    * @access private
    */
    var $_blocking = true;
    
    /**
    * Socket domain/family
    * 
    * AF_INET | AF_UNIX
    * 
    * @access   public
    * @var      int
    */
    var $domain = AF_INET;
    
    /**
    * Socket type
    * 
    * SOCK_DGRAM | SOCK_RAW | SOCK_RDM | SOCK_SEQPACKET | SOCK_STREAM
    * 
    * @access   public
    * @var      int
    */
    var $type = SOCK_STREAM;
    
    /**
    * Socket layer
    * 
    * SOL_SOCKET | SOL_TCP | SOL_UDP
    * 
    * @access   public
    * @var      int
    */
    var $proto = SOL_TCP;
    
    /**
    * Network address (IP) or file path for unix sockets
    * 
    * @access   public
    * @var      string
    */
    var $address = '127.0.0.1';
    
    /**
    * Port
    * 
    * @access   public
    * @var      int
    */
    var $port = 80;
    
    /**
    * Queuelength
    * 
    * @access   public
    * @var      int
    */
    var $queueLength = SOMAXCONN;
    
    /**
    * Constructor
    * 
    * @access   public
    * @return   object  System_Socket
    * @param    int     $resource       socket resource handle
    */
    function System_Socket($resource = null)
    {
        System_Socket::__construct($resource);
    }
    
    /**
    * ZE2 Constructor
    * @ignore
    */
    function __construct($resource = null)
    {
        if (System_Socket::isResource($resource)) {
            $this->_rsrc = $resource;
            socket_getSockname($resource, $this->address, $this->port);
        }
    }
    
    /**
    * Retrieve last socket error
    * 
    * See include/WinSock.h (Win32) and include/sys/errno.h (Un*x) for the 
    * values of the error codes.  See also (win32|unix)_socket_constants.h 
    * located in ext/sockets for all available SOCKET_E* constants.
    *
    * @static
    * @access   public
    * @return   object  PEAR_Error
    */
    function &lastError()
    {
        $error_code = socket_last_error();
        return PEAR::raiseError(socket_strerror($error_code), $error_code);
    }
    
    /**
    * Get socket resource handle
    *
    * @access public
    * @return int        resource handle
    */
    function getResource()
    {
        return $this->_rsrc;
    }

    /**
    * Create a socket resource
    *
    * Create the socket resource with the objects properties as parameters.
    *
    * @access public
    * @return bool
    */
    function create()
    {
        if (!System_Socket::isResource($this->_rsrc)) {
            if (!System_Socket::isResource(   
                $this->_rsrc = @socket_create(
                    $this->domain, $this->type, $this->proto))) {
                //
                return false;
            }
        }
        return true;
    }
    
    /**
    * Bind the socket resource
    *
    * Bind the socket resource to System_Socket::address at System_Socket::port.
    *
    * @access public
    * @return bool
    */
    function bind()
    {
        return (bool) @socket_bind($this->_rsrc, $this->address, $this->port);
    }
    
    /**
    * Close the socket
    *
    * Close the socket resource handle.
    *
    * @access public
    * @return void
    */
    function close()
    {
        @socket_close($this->_rsrc);
        $this->_rsrc = null;
    }
    
    /**
    * @access public
    */
    function listen()
    {
        return @socket_listen($this->_rsrc, $this->queueLength);
    }
    
    /**
    * Accept a socket connection
    *
    * Accept a socket connection from a prior created socket resource handle.
    *
    * @access public
    * @return mixed     new socket resource handle or false
    */
    function accept()
    {
        return @socket_accept($this->_rsrc);
    }
    
    /**
    * Connect the socket
    *
    * Connect the socket resource handle to System_Socket::address 
    * at System_Socket::port.
    *
    * @access public
    * @return bool
    */
    function connect()
    {
        return (bool) @socket_connect($this->_rsrc, $this->address, $this->port);
    }
    
    /**
    * Set socket option
    *
    * Set a socket option after you have created a socket resource handle.
    *
    * @access public
    * @return bool
    * @param  string $option
    * @param  mixed  $value
    * @param  int    $level
    */
    function setOption($option, $value, $level = SOL_SOCKET)
    {
        return socket_set_option($this->_rsrc, $level, $option, $value);
    }
    
    /**
    * Get socket option
    *
    * Retrieve a socket option after you have created the socket resource handle.
    * 
    * @access public
    * @return mixed
    * @param  string  $option
    * @param  int     $level
    */
    function getOption($option, $level = SOL_SOCKET)
    {
        return socket_get_option($this->_rsrc, $level, $option);
    }
    
    /**
    * Get socket status
    * 
    * @access   public
    * @return   mixed
    * @param    string  $part
    */
    function getStatus($part = null)
    {
        $status = socket_get_status($this->_rsrc);
        return isset($part) ? @$status[$part] : $status;
    }
    
    /**
    * Set socket blocking
    * 
    * @access   public
    * @return   bool
    * @param    bool    $blocking
    */
    function setBlocking($blocking)
    {
        if ($blocking) {
            $rs = socket_set_block($this->_rsrc);
            $this->_blocking = $rs;
        } else {
            $rs = socket_set_nonblock($this->_rsrc);
            $this->_blocking = !$rs;
        }
        return $rs;
    }
    
    /**
    * Get socket blocking
    * 
    * @access public
    * @return bool
    */
    function getBlocking()
    {
        return $this->_blocking;
    }
    
    /**
    * Check if a resource is still valid
    * 
    * Due to a nasty bug in PHP < 4.3.6 and 5.0.0 is_resource() would return
    * true for a already closed socket, too.  So we check the resource type
    * additionally to be really sure we have still a valid socket resource.
    *
    * @static
    * @access   public
    * @return   bool
    * @param    resource    $rsrc
    */
    function isResource($rsrc)
    {
        return (is_resource($rsrc) && get_resource_type($rsrc) == 'Socket');
    }
    
    /**
    * Check if the we have still a valid socket resource
    *
    * @see      System_Socket::isResource()
    * @access   public
    * @return   bool
    */
    function hasResource()
    {
        return System_Socket::isResource($this->_rsrc);
    }

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