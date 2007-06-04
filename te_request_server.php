#!/usr/bin/php
<?php
/**
 * Listen request to do file transformation (conversion)
 *
 * @author Anakeen 2007
 * @version $Id: te_request_server.php,v 1.5 2007/06/04 08:45:32 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM-TE
 */
/**
 */


include_once("TE/Class.TEServer.php");


$pidfile=$argv[1];
if ($pidfile && (! file_exists($pidfile))) {
  file_put_contents($pidfile,posix_getpid());
  $s=new TEServer();
  $s->listenloop();
  @unlink($pidfile);
 } else {
  exit(1); 
 }

?>