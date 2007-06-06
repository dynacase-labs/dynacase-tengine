#!/usr/bin/php
/**
 * Initialization of databse transformation server engine
 *
 * @author Anakeen 2007
 * @version $Id: te_server_init.php,v 1.3 2007/06/06 18:12:01 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package TE
 */
/**
 */
<?php

include_once("TE/Class.TEServer.php");

$targ=getArgv($argv);
$dbaccess=$targ["db"];
$dbid=@pg_connect($dbaccess);
if ($dbid) {
  exit(1); // already create
 } else {


  $dbcreate=php2DbCreateSql($dbaccess);
  $cmd=sprintf("createdb %s",$dbcreate);
  system($cmd,$retval);	
  if ($retval!=0) {
    exit($retval);
  }
  $dbid=@pg_connect($dbaccess);
  if ($dbid) {
    $o=new Task($dbaccess);
    pg_query($dbid,$o->sqlcreate);
    $o=new Engine($dbaccess);
    pg_query($dbid,$o->sqlcreate);
    $sqlinit=file_get_contents("TE/engine_init.sql",true);
    pg_query($dbid,$sqlinit);
    
  }
  exit(0);
 }

?>