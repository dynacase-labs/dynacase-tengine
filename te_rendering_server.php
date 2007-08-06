#!/usr/bin/php
<?php
/**
 * Read te database to do file transformation (conversion) in waiting
 *
 * @author Anakeen 2007
 * @version $Id: te_rendering_server.php,v 1.6 2007/08/06 10:45:40 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package TE
 */
/**
 */
include_once("TE/Class.TERendering.php");


//  error_reporting(E_ALL);

$targ=getArgv($argv);
$pidfile=$targ["fpid"];
if ($pidfile && (! file_exists($pidfile))) {
  file_put_contents($pidfile,posix_getpid());

  $db=$targ["db"];
  $maxclient=$targ["maxclient"];
  $tmppath=$targ["directory"];
  $filelogin=$targ["loginfile"];
  if ($filelogin) {
    $logincontent=file_get_contents($filelogin);
    if (preg_match('/URL_CALLBACK_LOGIN=([^ \n\r\t]+)/', $logincontent , $matches)) {
      $login=$matches[1];
    }
    if (preg_match('/URL_CALLBACK_PASSWORD=([^ \n\r\t]+)/', $logincontent , $matches)) {
      $pwd=$matches[1];
    }
  } else {
    $login=$targ["clogin"];
    $pwd=$targ["cpassword"];
  }

  $s=new TERendering();
  if ($db) $s->dbaccess=$db;
  if ($maxclient) $s->max_client=$maxclient;
  if ($tmppath) $s->tmppath=$tmppath;
  if ($login) $s->login=$login;
  if ($pwd) $s->password=$pwd;

  $s->listenloop();
  @unlink($pidfile);
 } else {
  exit(1); 
 }
?>