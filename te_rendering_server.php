#!/usr/bin/php
<?php
/**
 * Read te database to do file transformation (conversion) in waiting
 *
 * @author Anakeen 2007
 * @version $Id: te_rendering_server.php,v 1.3 2007/06/04 12:13:27 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM-TE
 */
/**
 */
include_once("TE/Class.TERendering.php");



$targ=getArgv($argv);
$pidfile=$targ["fpid"];
if ($pidfile && (! file_exists($pidfile))) {
  file_put_contents($pidfile,posix_getpid());

  $db=$targ["db"];
  $maxclient=$targ["maxclient"];
  $tmppath=$targ["directory"];
  $login=$targ["clogin"];
  $pwd=$targ["cpassword"];

  $s=new TERendering();
  if ($db) $s->dbaccess=$db;
  if ($maxclient) $s->max_client=$maxclient;
  if ($tmppath) $s->tmppath=$tmppath;
  if ($login) $s->login=$login;
  if ($pwd) $s->password=$pwd;

  print_r($s);
  $s->listenloop();
  @unlink($pidfile);
 } else {
  exit(1); 
 }
?>