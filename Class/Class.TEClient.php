<?php
/**
 * Function to dialog with transformation server engine
 *
 * @author Anakeen 2007
 * @version $Id: Class.TEClient.php,v 1.2 2007/05/30 15:54:22 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM-TE
 */
/**
 */


Class TransformationEngine {

  public $host='localhost';
  public $port='10000';

  /**
   * send a request to do a transformation
   * @param string $te_name Engine name
   * @param string $fkey foreign key
   * @param string $filename the path where is the original file
   * @param string $callback url to activate after transformation is done
   * @param array &$info transformation task info return "tid"=> ,"status"=> ,"comment=>
   * 
   * @return string error message, if no error empty string
   */
  function sendTransformation($te_name,$fkey,$filename,$callback,&$info) {

  
    $err="";

    /* Lit l'adresse IP du serveur de destination */
    $address = gethostbyname($this->host);
    $service_port = $this->port;

    /* Cree une socket TCP/IP. */
    echo "Essai de connexion à '$address' sur le port '$service_port'...\n";
    //    $result = socket_connect($socket, $address, $service_port);

    $fp = stream_socket_client("tcp://$address:$service_port", $errno, $errstr, 30);

    if (!$fp) {
      $err=_("socket creation error")." : $errstr ($errno)\n";
    } 

    
    if ($err=="") {
      $in = "CONVERT\n";
      echo "Envoi de la commande $in ...";    
      fputs($fp,$in);


      $out = trim(fgets($fp, 2048));
      echo "[$out].\n";
      if ($out=="Continue") {

	$size=filesize ($filename);
	$in = "<TE name=\"$te_name\" fkey=\"$fkey\" size=\"$size\" callback=\"$callback\"/>\n";
	echo "Envoi du header $in ...";    
	fputs($fp,$in);
	echo "Envoi du fichier $filename ...";


	if (file_exists($filename)) {
	  $handle = @fopen($filename, "r");
	  if ($handle) {
	    while (!feof($handle)) {
	      $buffer = fread($handle, 2048);
	      fputs($fp,$buffer,strlen($buffer));
	    }	
	    fclose($handle);
	  }

     
	  fflush($fp);
	  echo "OK.\n";
     

	  // echo "Lire la réponse : \n\n";
	  $out = trim(fgets($fp));
	  if (preg_match("/status=[ ]*\"([^\"]*)\"/i",$out,$match)) {
	    $status=$match[1];
	  }
	  if (preg_match("/<response[^>]*>(.*)<\/response>/i",$out,$match)) {
	    $outmsg=$match[1];
	  }
	  echo "Response [$status]\n";
	  echo "Message [$outmsg]\n";
	  if ($status == "OK") {
	    if (preg_match("/ id=[ ]*\"([^\"]*)\"/i",$outmsg,$match)) {
	      $tid=$match[1];
	    }
	    if (preg_match("/status=[ ]*\"([^\"]*)\"/i",$outmsg,$match)) {
	      $status=$match[1];
	    }
	    if (preg_match("/<comment>(.*)<\/comment>/i",$outmsg,$match)) {
	      $comment=$match[1];
	    }
	    $info=array("tid"=>$tid,
			"status"=>$status,
			"comment"=>$comment);
	  } else {
	    $err= $outcode." [$outmsg]";
	  }      
      
	}
      }
      echo "Fermeture de la socket...";
      fclose($fp);
    }
    return $err;
  }
  /**
   * send a request for get information about a task
   * @param int $tid_task identificator
   * @param array &$info transformation task info return "tid"=> ,"status"=> ,"comment=>
   * 
   * @return string error message, if no error empty string
   */
  function getInfo($tid,&$info) {

    error_reporting(E_ALL);
  
    $err="";

    /* Lit l'adresse IP du serveur de destination */
    $address = gethostbyname($this->host);
    $service_port = $this->port;

    /* Cree une socket TCP/IP. */
    echo "Essai de connexion à '$address' sur le port '$service_port'...\n";
    //    $result = socket_connect($socket, $address, $service_port);

    $fp = stream_socket_client("tcp://$address:$service_port", $errno, $errstr, 30);

    if (!$fp) { 
      $err=_("socket creation error")." : $errstr ($errno)\n";

    } 

    if ($err=="") {

      $in = "INFO\n";
      echo "Envoi de la commande $in ...";    
      fputs($fp,$in);

      $out = trim(fgets($fp, 2048));
      echo "[$out].\n";
      if ($out=="Continue") {
    
	$in = "<TASK id=\"$tid\" />\n";
	echo "Envoi du header $in ...";    
	fputs($fp,$in);
     

	$out = trim(fgets($fp, 2048));
	if (preg_match("/status=[ ]*\"([^\"]*)\"/i",$out,$match)) {
	  $status=$match[1];
	}
	echo "Response [$out]\n";
	if ($status == "OK") {


	  if (preg_match("/<task[^>]*>(.*)<\/task>/i",$out,$match)) {
	    $body=$match[1];
	    print "BODY:$body\n";
	    if (preg_match_all("|<[^>]+>(.*)</([^>]+)>|U",
			       $body,
			       $reg,
			       PREG_SET_ORDER) ) {	
	      foreach ($reg as $v) {
		$info[$v[2]]=$v[1];
	      }
	    }
	  }
	      

	} else {
	  $msg="";
	  if (preg_match("/<response[^>]*>(.*)<\/response>/i",$out,$match)) {
	    $msg=$match[1];
	  }
	  $err= $status." [$msg]";
	}            
      }
      
    
    
      echo "Fermeture de la socket...";
      fclose($fp);
    }

    return $err;
  }

  /**
   * send a request for retrieve a transformation
   * the status must be D (Done).
   * @param string $tid Task identification
   * @param string $filename the path where put the file (must be writeable)
   * @param array &$info transformation task info return "tid"=> ,"status"=> ,"comment=>
   * 
   * @return string error message, if no error empty string
   */
  function getTransformation($tid,$filename,&$info) {

    error_reporting(E_ALL);
  
    $err="";

    $handle = @fopen($filename, "w");
    if (!$handle) {
      $err=sprintf("cannot open file <%s> in write mode",$filename);
      return $err;
    }

    /* Lit l'adresse IP du serveur de destination */
    $address = gethostbyname($this->host);
    $service_port = $this->port;

    /* Cree une socket TCP/IP. */
    echo "Essai de connexion à '$address' sur le port '$service_port'...\n";
    //    $result = socket_connect($socket, $address, $service_port);

    $fp = stream_socket_client("tcp://$address:$service_port", $errno, $errstr, 30);

    if (!$fp) {
      $err=_("socket creation error")." : $errstr ($errno)\n";
    } 

    
    if ($err=="") {
      $in = "GET\n";
      echo "Envoi de la commande $in ...";    
      fputs($fp,$in);


      $out = trim(fgets($fp, 2048));
      echo "[$out].\n";
      if ($out=="Continue") {

	$size=filesize ($filename);
	$in = "<task id=\"$tid\" />\n";
	echo "Envoi du header $in ...";    
	fputs($fp,$in);
	echo "Recept du file size ...";
	$out = trim(fgets($fp, 2048));
	
	echo "[$out]\n";
	if (preg_match("/status=[ ]*\"([^\"]*)\"/i",$out,$match)) {
	  $status=$match[1];
	}
	if ($status=="OK") {

	  if (preg_match("/size=[ ]*\"([^\"]*)\"/i",$out,$match)) {
	    $size=$match[1];
	  }
	
	  echo "$size bytes\n";
	  echo "Recept du fichier $filename ...";


	  if ($handle) {
	    $trbytes=0;
	    do {
	      if ($size >= 2048) {
		$rsize=2048;
		$size-=2048;
	      } else {
		$rsize=$size;
		$size=0;
	      }

	   
	      $out = @fread($fp, $rsize);
	      $l=strlen($out);
	      $trbytes+=$l;
	     
	      fwrite($handle,$out);
	     
	      //echo "file:$l []";
	    } while ($size>0);


	    fclose($handle);
	  }

	  echo "Wroted  $filename\n.";
    
	  // echo "Lire la réponse : \n\n";
	  $out = trim(fgets($fp, 2048));
	  if (preg_match("/status=[ ]*\"([^\"]*)\"/i",$out,$match)) {
	    $status=$match[1];
	  }
	  if ($status!="OK") {
	    if (preg_match("/<response[^>]*>(.*)<\/response>/i",$out,$match)) {
	      $msg=$match[1];
	    }
	    $err="$status:$msg";
	  }
	} else {
	  // status not OK 
	  $msg="";
	  if (preg_match("/<response[^>]*>(.*)<\/response>/i",$out,$match)) {
	    $msg=$match[1];
	  }
	  $err="$status:$msg";
	}
      }
    }
    echo "Fermeture de la socket...";
    fclose($fp);
    return $err;
  }
  
}
?>