#!/usr/bin/php
<?php
/**
 * Read te database to do file transformation (conversion) in waiting
 *
 * @author Anakeen 2007
 * @version $Id: te_rendering_server.php,v 1.2 2007/06/04 08:45:32 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM-TE
 */
/**
 */
include_once("TE/Class.TERendering.php");



$pidfile=$argv[1];
if ($pidfile && (! file_exists($pidfile))) {
  file_put_contents($pidfile,posix_getpid());
  $s=new TERendering();
  $s->listenloop();
  @unlink($pidfile);
 } else {
  exit(1); 
 }
?>