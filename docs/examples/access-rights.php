<?php

/**
* Simple access rights example
* $Id$
*/

require_once 'System/Socket.php';

/**
* Create a new TCP/IP System_Socket_Listener object.
* 
* Note that access rights only apply to AF_INET connections.
*/
$srv = &System_Socket::createListener('localhost', 9876);

/**
* Accept connections from localhost only
*/
$srv->allowDeny = 'allow';
$srv->hostsAllow('127.0.0.1');

/**
* Accept only connections from within a certain network
*/
$srv->allowDeny = 'allow';
$srv->hostsAllow('10.0.1.0/24');

/**
* Allow from class C network 10.0.1.0/24 but deny from the single 10.0.1.254
*/
$srv->allowDeny = 'allow';
$srv->hostsAllow('10.0.1.0/24');
$srv->hostsDeny('10.0.1.254');

/**
* Allow all connections except from a certain IP
*/
$srv->allowDeny = 'deny';
$srv->hostsDeny('111.111.111.111');

/**
* Clear allow/deny list
*/
$srv->hostsReset(null);

/**
* Do some nice stuff right here... 
* What about implementing a SMTP server?  
* There are already some HTTP server out there :-)
* Uhm... perhaps I should rather have a look at these blog thingies...
*/

// ...

?>