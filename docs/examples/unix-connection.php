<?php

/**
* Simple unix socket connction
* $Id$
*/
require_once 'System/Socket.php';

/**
* Create the unix domain socket with the specified parameters passed through
* to the underlying System_Socket.  Most of the used options are typical for 
* such a unix domain socket connection.
* 
* Note that unix domain sockets are not available on Win32.
* 
*/
$sock = &System_Socket::createConnection(
    array(  'proto'     => SOL_SOCKET,
            'domain'    => AF_UNIX,
            'type'      => SOCK_STREAM,
            'address'   => '/tmp/pear.sock',
            'port'      => 0,
    )
);
/**
* Alternatively:
* <code>
*   $sock = &System_Socket::createUnixConnection('/tmp/pear.sock');
* </code>
*/

/**
* Loop while we're connected
*/
while($sock->hasSocket) {
    // echo kinda prompt
    echo "\n>";
    // read a line from STDIN
    $line = trim(fgets(STDIN));
    // write the string through the socket connection
    $sock->writeLine($line);
    // if we catched an "exit" close our connection
    if ($line == 'exit') {
        $sock->close();
        break;
    }
}
?>