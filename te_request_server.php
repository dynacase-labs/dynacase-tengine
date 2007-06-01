<?php
/**
 * Listen request to do file transformation (conversion)
 *
 * @author Anakeen 2007
 * @version $Id: te_request_server.php,v 1.4 2007/06/01 15:38:49 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM-TE
 */
/**
 */


include_once("Class/Class.TEServer.php");

$pidfile="/var/run/te_request.pid";
file_put_contents($pidfile,posix_getpid());
$s=new TEServer();
$s->listenloop();
@unlink($pidfile);

?>