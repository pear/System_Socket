<?php
// +----------------------------------------------------------------------+
// | PEAR :: System :: Socket :: Connection                               |
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
* System::Socket::Connection
* 
* @author       Michael Wallner <mike@php.net>
* @package      System_Socket
* @category     System
* @example      simple-debugger.php     Simple example for a HTTP connection
*/

if (!defined('SYSTEM_SOCKET_ROOT')) {
    define('SYSTEM_SOCKET_ROOT', dirname(__FILE__) . '/..');
}

require_once SYSTEM_SOCKET_ROOT . '/Socket/Manager.php';

/** 
* System_Socket_Connection
*
* @author   Michael Wallner <mike@php.net>
* @version  $Revision$
* @access   public
*/
class System_Socket_Connection extends System_Socket_Manager
{
    /**
    * Connection ID
    * @access   protected
    * @var      string
    */
    var $ID = null;
    
    /**
    * Peer host
    * @access public
    * @readonly
    */
    var $peerHost = '';
    
    /**
    * Peer port
    * @access public
    * @readonly
    */
    var $peerPort = 0;
    
    /**
    * Constructor
    * 
    * Instantiate a new System_Socket_Connection object with the underlying
    * System_Socket object $socket.
    *
    * @access public
    * @return object  System_Socket_Connection
    * @param  object  $socket System_Socekt object
    */
    function System_Socket_Connection(&$socket)
    {
        System_Socket_Connection::__construct($socket);
    }
    
    /**
    * ZE2 Constructor
    * @ignore
    */
    function __construct(&$socket)
    {
        parent::__construct($socket);
        
        if ($this->hasSocket) {
            @socket_getPeername(
                $this->Socket->getResource(), 
                $this->peerHost, 
                $this->peerPort
            );
            
            $this->ID = md5($this->peerHost . $this->peerPort . $this->Socket->getResource());
        }
    }
    
    /**
    * Get connection ID
    * 
    * Each connection is represented by a unique id.
    *
    * @access   public
    * @return   string
    */
    function getID()
    {
        return $this->ID;
    }
    
    /**
    * Check if wer're connected
    * 
    * Check if the System_Socket_Connection is still connected.  Actually we
    * check if the underlying socket resource handle is still valid.
    *
    * @access   public
    * @return   bool
    */
    function isConnected()
    {
        return ($this->hasSocket && $this->Socket->hasResource());
    }
    
    /**
    * Set socket blocking
    * 
    * Set the blocking mode of the underlying System_Socket.
    * 
    * @access   public
    * @return   bool
    */
    function setBlocking($blocking)
    {
        return $this->notify(__FUNCTION__, 
            $this->Socket->setBlocking($blocking), $blocking);
    }
    
    /**
    * Get socket blocking
    * 
    * Get the blocking mode of the underlying System_Socket.
    * 
    * @access   public
    * @return   bool
    */
    function getBlocking()
    {
        return $this->notify(__FUNCTION__, $this->Socket->getBlocking());
    }
    
    /**
    * Write through connection
    *
    * Write data through the socket connection.
    *
    * @access public
    * @return mixed  int bytes written or false
    * @param  string $data
    */
    function write($data)
    {
        return $this->notify(__FUNCTION__, 
            @socket_write($this->Socket->getResource(), $data), $data);
    }
    
    /**
    * Write char (chr(byte))
    * 
    * @access   public
    * @return   mixed
    * @param    string  $char
    */
    function writeChar($char)
    {
        $this->notify(__FUNCTION__, $this->write($char), $char);
    }
    
    /**
    * Write byte
    * 
    * @access   public
    * @return   mixed
    * @param    int     $byte
    */
    function writeByte($byte)
    {
        return $this->notify(__FUNCTION__, $this->write(chr($byte)), $byte);
    }
    
    /**
    * Write data with ending delimter
    * 
    * Write a string terminated by the specified delimiter
    * 
    * @access   public
    * @return   mixed
    * @oaram    string  $data   data to write
    * @param    string  $delim  delimiter to append
    * @param    bool    $escape whether to escape all delimiter characters
    *                           ($delim) conatained in $data
    */
    function writeDelim($data, $delim, $escape = true)
    {
        if ($escape) {
            $data = addCSlashes($data, $delim);
        }
        return $this->notify(__FUNCTION__, 
            $this->write($data . $delim), $data, $delim, $escape);
    }
    
    /**
    * Write a line
    * 
    * Write a line of data terminated with the specified linefeed character.
    * 
    * @access   public
    * @return   mixed
    * @param    string  $line   line of data
    * @param    string  $LF     linefeed character
    * @param    bool    $escape whether to escape all linefeed
    *                           characters ($LF) contained in $line
    */
    function writeLine($line = '', $LF = "\n", $escape = true)
    {
        return $this->notify(__FUNCTION__, 
            $this->writeDelim($line, $LF, $escape), $line, $LF, $escape);
    }
    
    /**
    * Write integer
    * 
    * Write an 32 bit network byte order integer (4 bytes).
    * 
    * @access   public
    * @return   mixed
    * @param    int     $int
    */
    function writeInt($int)
    {
        return $this->notify(__FUNCTION__, 
            $this->write(pack('V', $int)), $int);
    }
    
    /**
    * Write string terminated by NUL
    * 
    * Write a string which will be terminated by a NUL byte.
    * 
    * @access   public
    * @return   mixed
    * @param    string  $string the string to write
    * @param    bool    $escape wheter to escape all NULs in $string
    */
    function writeString($string, $escape = true)
    {
        return $this->notify(__FUNCTION__, 
            $this->writeDelim($string, "\0", $escape), $string, $escape);
    }
    
    /**
    * Read through connection
    *
    * Attempts to read $length length of data through the socket connection.
    * 
    * @access public
    * @return mixed read data or false
    * @param  int   bytes to read
    */
    function read($length = 4096)
    {
        return $this->notify(__FUNCTION__, 
            @socket_read($this->Socket->getResource(), abs($length)), $length);
    }
    
    /**
    * Read char
    * 
    * Reads a single character from the socket resource.
    * 
    * @access   public
    * @return   string
    */
    function readChar()
    {
        return $this->notify(__FUNCTION__, $this->read(1));
    }
    
    /**
    * Read byte
    * 
    * Reads a byte from the socket and returns its integer representation.
    * 
    * @access   public
    * @return   int
    */
    function readByte()
    {
        return $this->notify(__FUNCTION__, ord($this->readChar()));
    }
    
    /**
    * Read until delimiter
    * 
    * Reads from the socket until the first occurence of the delimiter $delim.
    * Be aware that the delimiter is part of the returned string.
    * 
    * @access   public
    * @return   string
    * @param    string  $delim  1 character string
    * @param    string  $escape escape character
    */
    function readDelim($delim, $escape = '\\')
    {
        $escape = $escape{0};
        $delim  = $delim{0};
        $buffer = '';
        $char   = null;

        do {
            $last = $char;
            $char = $this->readChar();
            $buffer .= $char;
        } while ($delim !== $char && $last !== $escape);
        
        return $this->notify(__FUNCTION__, $buffer, $delim);
    }
    
    /**
    * Read line
    * 
    * Be aware, that the linefeed character doesn't get truncated.
    * 
    * @access   public
    * @return   string
    * @param    string  $LF
    */
    function readLine($LF = "\n")
    {
        return $this->notify(__FUNCTION__, $this->readDelim($LF));
    }
    
    /**
    * Read integer
    * 
    * Read a 32 bit network byte order integer (4 bytes).
    * 
    * @access   public
    * @return   int
    */
    function readInt()
    {
        return $this->notify(__FUNCTION__, 
            array_shift(unpack('V', $this->read(4))));
    }
    
    /**
    * Read NUL terminated string
    * 
    * Reads a string terminated wit a NUL byte from the socket resource.
    * Be aware, that the NUL byte will *NOT* be truncated.
    * 
    * @access   public
    * @return   string
    */
    function readString()
    {
        return $this->notify(__FUNCTION__, $this->readDelim("\0"));
    }
}
?>