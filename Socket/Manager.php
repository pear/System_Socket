<?php
// +----------------------------------------------------------------------+
// | PEAR :: System :: Socket :: Manager                                  |
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
* System::Socket::Manager
* 
* @author       Michael Wallner <mike@php.net>
* @package      System_Socket
* @category     System
*/

if (!defined('SYSTEM_SOCKET_ROOT')) {
    define('SYSTEM_SOCKET_ROOT', dirname(__FILE__) . '/..');
}

require_once 'PEAR.php';
require_once SYSTEM_SOCKET_ROOT . '/Socket.php';


/** 
* System_Socket_Manager
* 
* Parent class for System_Socket_Connection and Systrem_Socket_Listener which
* performs some basic tasks, like handling observers, attaching a debugger
* automagically and controlling the base socket resource handle and object.
*
* @author   Michael Wallner <mike@php.net>
* @version  $Revision$
* @access   public
*/
class System_Socket_Manager extends PEAR
{
    /**
    * System_Socket object
    * @access protected
    */
    var $Socket = null;
    
    /**
    * Whether we hold a System_Socket object
    * @access proteceted
    */
    var $hasSocket = false;
    
    /**
    * Observer objects
    * @access protected
    */
    var $observers = array();
    
    /**
    * Constructor
    * 
    * @access   protected
    * @return   object  System_Socket_Manager
    * @param    objcet  System_Socket
    */
    function System_Socket_Manager(&$socket)
    {
        System_Socket_Manager::__construct($socket);
    }
    
    /**
    * ZE2 Constructor
    * @ignore
    */
    function __construct(&$socket)
    {
        if ($this->hasSocket = (is_a($socket, 'System_Socket') &&
            $socket->hasResource())) {
            $this->Socket = &$socket;
        }
        
        if (defined('SYSTEM_SOCKET_DEBUG') && SYSTEM_SOCKET_DEBUG) {
            include_once SYSTEM_SOCKET_ROOT . '/Socket/Debugger.php';
            $this->attach(new System_Socket_Debugger(SYSTEM_SOCKET_DEBUG));
        }
    }
    
    /**
    * Destructor
    * @ignore
    */
    function _System_Socket_Manager()
    {
        $this->__destruct();
    }
    
    /**
    * ZE2 Destructor
    * @ignore
    */
    function __destruct()
    {
        $this->close();
        $this->notify('__destruct', true);
    }
    
    /**
    * Close Socket
    * 
    * Close the underlying socket resource
    * 
    * @access   public
    * @return   void
    */
    function close()
    {
        $this->hasSocket = false;
        $this->Socket->close();
        $this->notify('close', true);
    }
    
    /** 
    * Whether we have a socket
    * 
    * @access   public
    * @return   bool
    */
    function hasSocket()
    {
        return $this->hasSocket;
    }
    
    /**
    * Close Socket
    * 
    * Close a socket resource or a System_Socket or System_Socket_Manager object
    * 
    * @access   public
    * @return   void
    * @param    mixed   $socket
    */
    function closeSocket(&$socket)
    {
        if (System_Socket::isResource($socket)) {
            socket_close($socket);
            $this->notify(__FUNCTION__, true, get_resource_type($socket));
        } elseif (is_object($socket) && method_exists($socket, 'close')) {
            $socket->close();
            $this->notify(__FUNCTION__, true, get_class($socket));
        } else {
            $this->notify(__FUNCTION__, false, $socket);
        }
        $socket = null;
    }

    /**
    * Attach observer
    * 
    * @access   public
    * @return   bool
    * @param    object  System_Socket_Observer  $observer
    */
    function attach(&$observer)
    {
        if (is_a($observer, 'System_Socket_Observer')) {
            $this->observers[$observer->getHashCode()] = &$observer;
            return true;
        }
        return false;
    }
    
    /**
    * Detach observer
    * 
    * @access   public
    * @return   void
    * @param    object  System_Socket_Observer $observer
    */
    function detach(&$observer)
    {
        unset($this->observers[$observer->getHashCode()]);
    }
    
    /** 
    * Check if a specific observer is already attached
    * 
    * @access   public
    * @return   bool
    * @param    object  System_Socket_Observer  $observer
    */
    function attached(&$observer)
    {
        return isset($this->observers[$observer->getHashCode()]);
    }
    
    /**
    * Notify observers
    * 
    * @access   protected
    * @return   mixed   the supplied result
    * @param    string  $event
    * @param    mixed   $result
    * @param    mixed   $arg [, $arg [, ...]]
    */
    function notify($event, $result)
    {
        static $class;
        
        if (!isset($class)) {
            $class = get_class($this);
        }
        
        if ($c = count($this->observers)) {
            $args = func_get_args();
            $note = array(
                0       => $event,
                'event' => $event,
                1       => $class,
                'class' => $class,
                2       => $result,
                'result'=> $result,
                3       => $args = array_splice($args, 2),
                'args'  => $args,
            );

            reset($this->observers);
            while (list($hashCode) = each($this->observers)) {
                $this->observers[$hashCode]->notify($note);
            }
        }

        return $result;
    }
}
?>
