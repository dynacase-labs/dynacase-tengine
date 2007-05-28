<?php
/**
 * Function to dialog with transformation server engine
 *
 * @author Anakeen 2002
 * @version $Id: te_request_server.php,v 1.3 2007/05/28 14:45:42 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM-TE
 */
/**
 */


include_once("Class/Class.TEServer.php");


$s=new TEServer();
$s->listenloop();


?>