<?php
// +----------------------------------------------------------------------+
// | PEAR :: System :: Socket :: ConnectionPool                           |
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
* System::Socket::ConnectionPool
* 
* @author       Michael Wallner <mike@php.net>
* @package      System_Socket
* @category     System
*/

if (!defined('SYSTEM_SOCKET_ROOT')) {
    define('SYSTEM_SCKET_ROOT', dirname(__FILE__) . '/..');
}
/**
* System_Socket_ConnectionPool
*
* @author   Michael Wallner <mike@php.net>
* @version  $Revision$
* @access   public
*/
class System_Socket_ConnectionPool
{
    /**
    * Socket pool
    * 
    * @access   protected
    * @var      array
    */
    var $pool = array();
    
    /**
    * Constructor
    * 
    * Instantiate a new System_Socket_ConnectionPool object and add specified
    * array of socket connections to the pool.  Actually the method 
    * System_Socket_ConnectionPool::push() gets called for each array element.
    *
    * @access   public
    * @return   object  System_Socket_ConnectionPool
    * @param    array   $sockets    reference to an array of socket connections
    */
    function System_Socket_ConnectionPool(&$sockets)
    {
        System_Socket_ConnectionPool::__construct($sockets);
    }
    
    /**
    * ZE2 Constructor
    * @ignore
    */
    function __construct(&$sockets)
    {
        foreach (array_keys($sockets) as $key){
            $this->push($sockets[$key]);
        }
    }
    
    /**
    * Push a socket connection
    *
    * Add a System_Socket_Connection object to the pool.
    * You can push one of the following values:
    *   * a valid socket resource handle
    *   * a System_Socket_Connection object
    *   * a System_Socket object
    * 
    * 
    * @access   public
    * @return   bool
    * @param    mixed   $s  resource handle or System_Socket[_Connection] object
    */
    function push(&$s)
    {
        if (is_object($s)) {
            if (is_a($s, 'System_Socket_Connection')) {
                $this->pool[$s->getID()] = &$s;
            } elseif (is_a($s, 'System_Socket')) {
                $conn = &new System_Socket_Connection($s);
                $this->pool[$conn->getID()] = &$conn;
            } else {
                return false;
            }
        } elseif (System_Socket::isResource($s)) {
            $conn =  &new System_Socket_Connection(new System_Socket($s));
            $this->pool[$conn->getID()] = &$conn;
        } else {
            return false;
        }
        return true;
    }
    
    /**
    * Shift a socket connection
    * 
    * Retrieve a System_Socket_Connection object from the pool.
    * Note that the System_Socket_ConnecionPool object will no 
    * longer contain the shifted connection.
    *
    * @access   public
    * @return   object  System_Socket_Connection
    */
    function &shift()
    {
        return count($this->pool) ? array_shift($this->pool) : null;
    }
    
    /** 
    * Get all connections
    * 
    * This method returns a refernce to the connection pool array.
    * 
    * @access   public
    * @return   array   connection pool
    */
    function &getConnections()
    {
        return $this->pool;
    }
    
    /**
    * Close all connections of this pool
    * 
    * Each socket resource handle will be closed and all 
    * System_Socket_Connection objects of this pool
    * will be unset.
    * 
    * @access   public
    * @return   void
    */
    function close()
    {
        while ($conn = &$this->shift()) {
            $conn->close();
            unset($conn);
        }
    }
    
    /** 
    * Count connections
    * 
    * @access   public
    * @return   int
    */
    function count()
    {
        return count($this->pool);
    }
    
    /** 
    * Write to all connections
    * 
    * @access   public
    * @return   array
    * @param    string  $data
    */
    function write($data)
    {
        $result = array();
        foreach (array_keys($this->pool) as $ID){
            $result[$ID] = $this->pool[$ID]->write($data);
        }
        return $result;
    }
    
    /** 
    * Read from all connections
    * 
    * @access   public
    * @return   array
    * @param    int     $bytes
    */
    function read($bytes = 4096)
    {
        $result = array();
        foreach (array_keys($this->pool) as $ID){
            $result[$ID] = $this->pool[$ID]->read($bytes);
        }
        return $result;
    }
    
    /** 
    * Call a method by name of all connections
    * 
    * @access   public
    * @return   array
    * @param    string  $method
    * @param    mixed   [$arg[, $arg[, ...]]]
    */
    function call($method)
    {
        $params = array_splice(func_get_args(), 1);
        $result = array();
        foreach (array_keys($this->pool) as $ID){
            $result[$ID] = call_user_method_array(
                $method, $this->pool[$ID], $params);
        }
        return $result;
    }
    
}
?>