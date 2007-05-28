<?php
/**
 * Function to dialog with transformation server engine
 *
 * @author Anakeen 2002
 * @version $Id: te_client.php,v 1.3 2007/05/28 15:37:47 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM-TE
 */
/**
 */


Class TransformationEngine {

  public $host='localhost';
  public $port='10000';

  /**
   * send a request for do a transformation
   * @param string $te_name Engine name
   * @param string $fkey foreign key
   * @param string $filename the path where is the original file
   * @param int &$tid transformation task id return
   * 
   * @return string error message, if no error empty string
   */
  function sendTransformation($te_name,$fkey,$filename,&$tid) {

    error_reporting(E_ALL);

   

  

    /* Lit l'adresse IP du serveur de destination */
    $address = gethostbyname($this->host);
    $service_port = $this->port;

    /* Cree une socket TCP/IP. */
    /* $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    if ($socket === false) {
      echo "socket_create() a échoué : raison :  " . socket_strerror(socket_last_error()) . "\n";
    } else {
      echo "OK.\n";
    }
    */
    echo "Essai de connexion à '$address' sur le port '$service_port'...\n";
    //    $result = socket_connect($socket, $address, $service_port);

    $fp = stream_socket_client("tcp://$address:$service_port", $errno, $errstr, 30);

    if (!$fp) {
      echo "$errstr ($errno)<br />\n";//echo "socket_connect() a échoué : raison : ($result) " . socket_strerror(socket_last_error($socket)) . "\n";
    } else {
      echo "OK.\n";
    }

    

    $in = "CONVERT\n";
    echo "Envoi de la commande $in ...";    
    fputs($fp,$in);


    $out = trim(fgets($fp, 2048));

    echo "[$out].\n";
    if ($out=="Continue") {

    $size=filesize ($filename);
    $in = "<TE name=\"$te_name\" fkey=\"$fkey\" size=\"$size\" />\n";
    echo "Envoi du header $in ...";    
    fputs($fp,$in);
    echo "Envoi du fichier $filename ...";


    if (file_exists($filename)) {
      $handle = @fopen($filename, "r");
      if ($handle) {
	while (!feof($handle)) {
	  $buffer = fread($handle, 2048);
	  //socket_write($socket, $buffer,strlen($buffer));
	   fputs($fp,$buffer,strlen($buffer));
	   //$lue=socket_send($socket, $buffer, strlen($buffer),0x8);
	  //if ($lue < 2048)  socket_send($socket, "\0", 1,0x8);
	  //print "send $lue ";
	}	
	fclose($handle);
      }

     
      //socket_write($socket, "\n\0", 2);
      fflush($fp);
      // sleep(2);
      //socket_send($socket, "==\0", 3,0x8);
      echo "OK.\n";
     

      // echo "Lire la réponse : \n\n";
      $outcode = trim(fgets($fp, 2048));
      echo "Response [$outcode]\n";
      $outmsg = trim(fgets($fp, 2048));
      echo "Message [$outmsg]\n";
      
      
      
    }
    }
    echo "Fermeture de la socket...";
    fclose($fp);
    echo "OK.\n\n";
  }

}
?>