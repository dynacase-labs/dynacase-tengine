<?php
/**
 * Function to dialog with transformation server engine
 *
 * @author Anakeen 2002
 * @version $Id: te_client.php,v 1.1 2007/05/25 12:27:43 eric Exp $
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

    echo "<h2>Connexion TCP/IP</h2>\n";

  

    /* Lit l'adresse IP du serveur de destination */
    $address = gethostbyname($this->host);
    $service_port = $this->port;

    /* Cree une socket TCP/IP. */
    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    if ($socket === false) {
      echo "socket_create() a échoué : raison :  " . socket_strerror(socket_last_error()) . "\n";
    } else {
      echo "OK.\n";
    }

    echo "Essai de connexion à '$address' sur le port '$service_port'...";
    $result = socket_connect($socket, $address, $service_port);
    if ($socket === false) {
      echo "socket_connect() a échoué : raison : ($result) " . socket_strerror(socket_last_error($socket)) . "\n";
    } else {
      echo "OK.\n";
    }

    $in = "<TE name=\"$te_name\" fkey=\"$fkey\">\n";


    echo "Envoi de la requête $in ...";
    socket_write($socket, $in, strlen($in));
    //socket_send($socket, $in, strlen($in),0x8);
    //    socket_send($socket, "\0", 1,0x8);
    echo "OK.\n";
    echo "Envoi du fichier ...";


    if (file_exists($filename)) {
      $handle = @fopen($filename, "r");
      if ($handle) {
	while (!feof($handle)) {
	  $buffer = fread($handle, 2048);
	   socket_write($socket, $buffer,strlen($buffer));
	   //$lue=socket_send($socket, $buffer, strlen($buffer),0x8);
	  //if ($lue < 2048)  socket_send($socket, "\0", 1,0x8);
	  //print "send $lue ";
	}
	fclose($handle);
      }
      echo "SEND END\n";
      //socket_write($socket, "\n\0", 2);
      sleep(1);
      //socket_send($socket, "==\0", 3,0x8);
      echo "OK.\n";
      $in = "<\TE> \r\n";
      socket_write($socket, $in, strlen($in));
      //socket_send($socket, $in, strlen($in),0x8);
      //      socket_send($socket, "\0", 1,0x8);

      echo "Lire la réponse : \n\n";
       $out = socket_read($socket, 2048, PHP_NORMAL_READ);
       echo "response [$out]\n";
       /*while ($out = socket_read($socket, 2048, PHP_NORMAL_READ)) {
	 echo $out;
	 }*/
    }
    echo "Fermeture de la socket...\n";
    socket_close($socket);
    echo "OK.\n\n";
  }

}
?>