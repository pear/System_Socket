<?php

/**
* Simple unix domain socket listener
* $Id$
*/

require_once 'System/Socket.php';

PEAR::setErrorHandling(PEAR_ERROR_DIE, "Fatal PEAR Error: %s\n");

/**
* Create a System_Socket_Listener object with the specified options passed
* through to the underlying System_Socket.  Most of the used parameters
* are typical for a unix domain socket listener.
* 
* Note that unix domain sockets are not available on Win32.
*/
$sock = &System_Socket_Creator::createListener(
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
*   $sock = &System_Socket::createUnixListener('/tmp/pear.sock');
* </code>
*/

/**
* Loop while we have a socket resource
*/
while ($sock->hasSocket) {
    /**
    * Get a System_Socket_ConnectionPool object holding our connected clients.
    */
    $pool = &$sock->getReadableClients();
    /**
    * Walk through all connections and display the data the clients sent.
    * If we catch a "exit" stop the socket server.
    */
    while ($conn = &$pool->shift()) {
        $line = $conn->readLine();
        if (trim($line) == 'exit') {
            echo "EXITING!\n";
            $sock->close();
            break;
        }
        echo "CLIENT SAYS: $line";
    }
    flush(); ob_flush();
    sleep(1);
}

?>