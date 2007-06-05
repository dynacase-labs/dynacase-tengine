#!/usr/bin/php
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