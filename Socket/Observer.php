<?php
// +----------------------------------------------------------------------+
// | PEAR :: System :: Socket :: Observer                                 |
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
* System::Socket::Observer
* 
* @author       Michael Wallner <mike@php.net>
* @package      System_Socket
* @category     System
*/

/** 
* System_Socket_Observer
*
* Base class for observers which handles the unique hash code
* generation and defines a thight base API.  A class implementing/extending 
* System_Socket_Observer must provide an uniqe hash code retrievable by a
* getHashCode() method and must also provide a notify() method.  Observer
* objects may be chained because System_Socket_Observer are also observable.
* 
* @author   Michael Wallner <mike@php.net>
* @version  $Revision$
* @access   public
*/
class System_Socket_Observer
{
    /**
    * @access   protected
    */
    var $hashCode;
    
    /**
    * @access   protected
    */
    var $observers = array();
    
    /**
    * Constructor
    * 
    * @access   protected
    * @return   object  System_Socket_Observer
    */
    function System_Socket_Observer()
    {
        $this->__construct();
    }
    
    /**
    * ZE2 Constructor
    * @ignore
    */
    function __construct()
    {
        $this->generateHashCode();
    }
    
    /**
    * Generate hash code
    * 
    * @access   protected
    * @return   void
    */
    function generateHashCode()
    {
        $this->hashCode = md5(uniqid(get_class($this), true));
    }

    /**
    * Notify
    * 
    * @abstract
    * @access   protected
    * @param    array       $note
    */
    function notify($note)
    {
        reset($this->observers);
        while (list($hashCode) = each($this->observers)){
            $this->observers[$hashCode]->notify($note);
        }
    }
    
    /**
    * Get hash code
    * 
    * @access   public
    * @return   string
    */
    function getHashCode()
    {
        return $this->hashCode;
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
    * @param    object  System_Socket_Observer $observer
    */
    function attached(&$observer)
    {
        return isset($this->observers[$observer->getHashCode()]);
    }
    
}
?>