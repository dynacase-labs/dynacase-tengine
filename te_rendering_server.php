<?php
/**
 * Read te database to do file transformation (conversion) in waiting
 *
 * @author Anakeen 2007
 * @version $Id: te_rendering_server.php,v 1.1 2007/06/01 15:38:49 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM-TE
 */
/**
 */
include_once("Class/Class.TERendering.php");


$pidfile="/var/run/te_rendering.pid";
file_put_contents($pidfile,posix_getpid());
$s=new TERendering();
$s->listenloop();
@unlink($pidfile);
?>