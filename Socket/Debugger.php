<?php
// +----------------------------------------------------------------------+
// | PEAR :: System :: Socket :: Debugger                                 |
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
* System::Socket::Debugger
* 
* @TODO!        XXX add extended support for PEAR::Log XXX
* 
* @author       Michael Wallner <mike@php.net>
* @package      System_Socket
* @category     System
* @example      simple-debugger.php Simple debugging (echo) example
*/

if (!defined('SYSTEM_SOCKET_ROOT')) {
    define('SYSTEM_SOCKET_ROOT', dirname(__FILE__) . '/..');
}

require_once SYSTEM_SOCKET_ROOT . '/Socket/Observer.php';

define('SYSTEM_SOCKET_DEBUG_NONE',          0);
define('SYSTEM_SOCKET_DEBUG_PEAR_LOG',      1);
define('SYSTEM_SOCKET_DEBUG_PEAR_ERROR',    2);
define('SYSTEM_SOCKET_DEBUG_ECHO',          3);
define('SYSTEM_SOCKET_DEBUG_ERRORLOG',      4);
define('SYSTEM_SOCKET_DEBUG_TRIGGERERROR',  5);

/** 
* System_Socket_Debugger
* 
* The System_Socket_Debugger class aims to provide a simple and versatile
* option to debug socket implementations in various ways.
* 
* Example 1:
* <code>
* require_once 'System/Socket.php';
* require_once 'System/Socket/Debugger.php';
* $conn = &System_Socket::createConnection(
*   array('address'=>'pear.php.net', 'port'=>80));
* $conn->attach(new System_Socket_Debugger(SYSTEM_SOCKET_DEBUG_ECHO));
* </code>
* 
* Example 2:
* <code>
* // no need to explicitly create a System_Socket_Debugger object
* require_once 'System/Socket.php';
* require_once 'System/Socket/Debugger.php';
* define('SYSTEM_SOCKET_DEBUG', SYSTEM_SOCKET_DEBUG_ECHO);
* $conn = &System_Socket::createConnection(
*   array('address'=>'pear.php.net', 'port'=>80));
* // a debugger object gets attached automagically
* </code>
*
* @author   Michael Wallner <mike@php.net>
* @version  $Revision$
* @access   public
*/
class System_Socket_Debugger extends System_Socket_Observer
{
    /**
    * Linefeed character(s)
    * 
    * @access   public
    * @var      string
    */
    var $LF = "\n";
    
    /**
    * Type
    * 
    * @access   public
    * @var      int
    */
    var $type = SYSTEM_SOCKET_DEBUG_NONE;

    /**
    * Whether to log all method calls
    * 
    * @access   public
    * @var      bool
    */
    var $verboseCalls = true;
    
    /**
    * Whether to log methods arguments
    * 
    * @access   public
    * @var      bool
    */
    var $verboseArgs = true;
    
    /**
    * Whether to log methods results
    * 
    * @access   public
    * @var      bool
    */
    var $verboseResults = true;
    
    /**
    * Whether to echo a date
    * 
    * @access   public
    * @var      bool
    */
    var $echoDate = true;
    
    /**
    * Echoed dates format
    * 
    * @access   public
    * @var      string
    */
    var $dateFormat = 'Y-m-d H:i:s';
    
    /**
    * PEAR::Log object
    * 
    * @readonly
    * @access   public
    * @var      object
    */
    var $PearLog = null;
    
    /**
    * Whether a PEAR::Log object is attached
    * 
    * @readonly
    * @access   public
    * @var      bool
    */
    var $hasPearLog = false;
    
    /**
    * Note stack
    * 
    * @access   protected
    * @var      array
    */
    var $stack = array();
    
    /**
    * Constructor
    * 
    * @access   public
    * @return   object  System_Socket_Debugger
    */
    function System_Socket_Debugger($type = null)
    {
        System_Socket_Debugger::__construct($type);
    }

    /**
    * ZE2 Constructor
    * @ignore
    */
    function __construct($type = null)
    {
        parent::__construct();
        
        if (isset($type)) {
            $this->type = $type;
        } elseif (defined('SYSTEM_SOCKET_DEBUG')) {
            $this->type = SYSTEM_SOCKET_DEBUG;
        }
    }
    
    /**
    * Set a configured PEAR_Log object
    * 
    * @access   public
    * @return   bool
    * @param    object Log  $log
    */
    function setPearLog(&$log)
    {
        if ($this->hasPearLog = is_a($log, 'Log')) {
            $this->PearLog = &$log;
            return true;
        }
        return false;
    }
    
    /**
    * Notify
    * 
    * @access   public
    * @return   void
    * @param    array   $note
    */
    function notify($note)
    {
        static $lastVerboseCall;
        
        $this->stack[] = $note;
        list($event, $class, $result, $args) = $note;
        
        if (!$this->verboseCalls) {
            if (isset($lastVerboseCall) && 
                preg_match('/^'. $lastVerboseCall .'.+/', $event)) {
                return;
            }
            $lastVerboseCall = $event;
        }
        
        $message = $class . '::' . $event;
        
        if ($this->verboseArgs) {
            $message .= '(';
            if (is_array($args)) {
                foreach ($args as $arg) {
                    $message .= $this->arg2string($arg) . ', ';
                }
                $message = substr($message, 0, -2);
            } else {
                $message .= $this->arg2string($arg);
            }
            $message .= ')';
        }
        
        if ($this->verboseResults) {
            $message .= ' Result: ' . $this->arg2string($result);
        }
        
        switch ($this->type) {
            
            case SYSTEM_SOCKET_DEBUG_PEAR_LOG:
                if ($this->hasPearLog) {
                    $this->PearLog->log($message);
                }
            break;
            
            case SYSTEM_SOCKET_DEBUG_PEAR_ERROR:
                PEAR::raiseError($message);
            break;
            
            case SYSTEM_SOCKET_DEBUG_ERRORLOG:
                error_log($message);
            break;
            
            case SYSTEM_SOCKET_DEBUG_TRIGGERERROR;
                trigger_error($message, E_USER_NOTICE);
            break;

            case SYSTEM_SOCKET_DEBUG_ECHO: 
                if ($this->echoDate) {
                    echo date($this->dateFormat) . ' ';
                }
                echo $message, $this->LF;
            break;
        }
    }
    
    /**
    * Get (part of) note stack
    *
    * Each received note gets stored in the notes stack of the debugger.  With
    * this method you can get the whole stack or the <var>$num</var>th part.
    * 
    * @access   public
    * @return   array
    * @param    int     $num
    */
    function getStack($num = null)
    {
        return isset($num, $this->stack[$num]) ? 
            $this->stack[$num] : $this->stack;
    }
    
    /**
    * arg2string
    *
    * Attempts to convert any argument to a representive string.
    * 
    * @access   public
    * @return   string
    * @param    mixed   $arg
    */
    function arg2string($arg)
    {
        switch (strToLower($type = getType($arg)))
        {
            case 'null':
            {
                return 'NULL';
            }
            
            case 'resource':
            {
                return get_resource_type($arg) . ' (' . (string) $arg . ')';
            }
            
            case 'double':
            case 'integer':
            {
                return (string) $arg;
            }
            
            case 'object':
            {
                return get_class($arg) . ' (' . (method_exists($arg, 'toString')
                    ? $arg->toString() : ('Object' == ($string = (string) $arg)
                    ? '(' . serialize($arg) . ')' : $string));
            }
            
            case 'array':
            {
                return 'Array (' . serialize($arg) .')';
            }
            
            case 'string':
            {
                return '"' . addCSlashes($arg, "\n\t\r\"\0") . '"';
            }
            
            default:
            {
                return "($type) " . (string) $arg;
            }
        }
    }
}
?>