<?php

/**
* Simple debugger example
* $Id$
*/

require_once 'System/Socket/Creator.php';
require_once 'System/Socket/Debugger.php';

/**
* If we define SYSTEM_SOCKET_DEBUG, a System_Socket_Debugger object
* gets automagically attached {@link System_Socket_Manager::attach()}
* to every System_Socket_Manager (Connection/Listener) we create.
* 
* So be carefull with this option!
*/
define('SYSTEM_SOCKET_DEBUG', SYSTEM_SOCKET_DEBUG_ECHO);

/**
* Here we create a new System_Socket_Connection object with the
* specified parameters passed to the underlying System_Socket.  
* 
* Note that our debugger will be automatically attached!
*/
$conn = &System_Socket_Creator::createTcpConnection('pear.php.net', 80);

// just check if we actually got a System_Socket_Connection returned
if (PEAR::isError($conn)) {
    die($conn->getMessage());
}

/**
* We now send a simple HTTP GET request through the socket connection and our
* System_Socket_Debugger will comment each action taken. (watch the output)
*/
$conn->writeLine('HEAD / HTTP/1.1');
$conn->writeLine('Host: pear.php.net');
$conn->writeLine('Connection: close');
$conn->writeLine('User-Agent: PEAR::System::Socket');
$conn->writeLine();
while ($conn->read());

?>