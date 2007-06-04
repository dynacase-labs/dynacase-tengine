#!/usr/bin/php
<?php
/**
 * Listen request to do file transformation (conversion)
 *
 * @author Anakeen 2007
 * @version $Id: te_request_server.php,v 1.6 2007/06/04 12:13:27 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM-TE
 */
/**
 */


include_once("TE/Class.TEServer.php");

$targ=getArgv($argv);
$pidfile=$targ["fpid"];
if ($pidfile && (! file_exists($pidfile))) {
  file_put_contents($pidfile,posix_getpid());

  $laddr=$targ["laddr"];
  $port=$targ["port"];
  $db=$targ["db"];
  $maxclient=$targ["maxclient"];
  $tmppath=$targ["directory"];

  $s=new TEServer();
  if ($laddr) $s->address=$laddr;
  if ($port) $s->port=$port;
  if ($db) $s->dbaccess=$db;
  if ($maxclient) $s->max_client=$maxclient;
  if ($tmppath) $s->tmppath=$tmppath;
  print_r($s);
  $s->listenloop();
  @unlink($pidfile);
 } else {
  exit(1); 
 }

?>