<?php

/**
* Simple TCP echo server (localhost:9876)
* $Id$
*/

require_once 'System/Socket/Creator.php';

set_time_limit(0);

/**
* Create a System_Socket_Listener object with the spcified options passed
* through to the underlying System_Socket.  Used options are typical for
* a TCP/IP listener.
*/
$srv = &System_Socket_Creator::createListener(
    array(  'port'      => 9876,
            'proto'     => SOL_TCP,
            'domain'    => AF_INET,
            'type'      => SOCK_STREAM,
    )
);

// just check if we actually got a System_Socket_Listener
if (PEAR::isError($srv)) {
    die($srv->getMessage());
}

// this array will hold already seen connection IDs
$seen = array();

/**
* We leave the loop when the listener gets closed
*/
while ($srv->hasSocket) {

    /**
    * Fetch exceptional clients - this should rather never happen, though.
    */
    $excp = &$srv->getExceptionalClients();
    while ($conn = &$excp->shift()) {
        echo "\nExceptional Client: " . $conn->getID() . "\n";
        $conn->close();
    }

    /**
    * We first select writable clients to show a short greeting and usage 
    * message.  System_Socket_Listener::getWritableClients() returns a
    * System_Socket_ConnectionPool object like its counterparts for
    * readable and exceptional clients.
    */
    $write = &$srv->getWritableClients();

    /**
    * Shift one client connection after another and check if we have already
    * seen it before; otherwise display a short usage message.
    */
    while ($conn = &$write->shift()) {
        if (!isset($seen[$conn->getID()])) {
            $conn->writeLine();
            $conn->writeLine('Type "exit" to quit or "stop" to stop the server');
            $conn->writeLine();
            $seen[$conn->getID()] = true;
        }
    }
    
    /**
    * Now read a line from the clients and write received data back.  If we get
    * a "exit" disconnect the client and if we get a "stop" shutdown the server.
    */
    $read = &$srv->getReadableClients();
    while ($conn = &$read->shift()) {
        $line = $conn->readLine();
        switch (trim($line))
        {
            case 'exit':
                $conn->close();
            break;
            
            case 'stop':
                $srv->close();
            break;
            
            default:
                $conn->write($line);
        }
    }
    usleep(10);
}
?>