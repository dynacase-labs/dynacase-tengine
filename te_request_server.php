<?php
/**
 * Function to dialog with transformation server engine
 *
 * @author Anakeen 2002
 * @version $Id: te_request_server.php,v 1.1 2007/05/25 12:27:43 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM-TE
 */
/**
 */


global $cur_client;
global $msgsock;
$cur_client=0;
declare (ticks = 1);
// signal handler function

function decrease_child($sig) {
  global $cur_client;
  $cur_client--;
  echo "One Less [$sig]  $cur_client\n";
  
}
function closesockets($sig) {
  global $msgsock;
  print "\nCLOSE SOCKET\n";
  socket_close($msgsock);
  
}

error_reporting(E_ALL);

/* Autorise l'exécution infinie du script, en attente de connexion. */
set_time_limit(0);

/* Active le vidage implicite des buffers de sortie, pour que nous
 * puissions voir ce que nous lisons au fur et à mesure. */
ob_implicit_flush();

$address = '127.0.0.1';
$port = 10000;
$max_client=10;
pcntl_signal(SIGCHLD, "decrease_child");
pcntl_signal(SIGPIPE, "decrease_child");
pcntl_signal(SIGTERM, "closesockets");
//pcntl_signal(SIGINT, "closesockets");
//pcntl_signal(SIGSTOP, "closesockets");
if (($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
    echo "socket_create() a échoué : raison : " . socket_strerror(socket_last_error()) . "\n";
    exit;
}

if (socket_bind($sock, $address, $port) === false) {
    echo "socket_bind() a échoué : raison : " . socket_strerror(socket_last_error($sock)) . "\n";
    exit;
}

if (socket_listen($sock, 5) === false) {
    echo "socket_listen() a échoué : raison : " . socket_strerror(socket_last_error($sock)) . "\n";
    exit;
}
/*if (socket_set_nonblock($sock) === false) {
    echo "socket_set_nonblock() a échoué. Raison : ".socket_strerror(socket_last_error($socket));
    }*/
echo "Listen on $address:$port\n";

/*if (pcntl_signal(SIGUSR1, "decrease_child")) {
  
  echo "Catch Signal SIGUSR1 activated\n";
  }*/
/*
socket_set_option(
  $sock,
  SOL_SOCKET,  // socket level
  SO_SNDTIMEO, // timeout option
  array(
    "sec"=>2, // Timeout in seconds
    "usec"=>0  // I assume timeout in microseconds
    )
    );*/
//socket_set_nonblock($sock);
 while (true) {
  if (($msgsock = socket_accept($sock)) === false) {
    echo "socket_accept() a échoué : raison : " . socket_strerror(socket_last_error($sock)) . "\n";
    break;
    }
  echo "Accept\n";

  socket_set_block($sock);
      $cur_client++;
  if ($cur_client> $max_client) {
    echo "Too many child [$cur_client] Waiting\n";
    $childpid=pcntl_wait($wstatus);
  }
  $pid = pcntl_fork();
            
  if ( $pid == -1 ) {       
    // Fork failed           
    exit(1);
  } else if ( $pid ) {
    // We are the parent
    
      echo "Parent Waiting Accept:$cur_client\n";
    

  } else {
    // We are the child
    // Do something with the inherited connection here
    // It will get closed upon exit
    /* Send instructions. */
    $msg = "\Bienvenue sur le serveur de test PHP.\n" .
      "Pour quitter, tapez 'quit'. Pour éteindre le serveur, tapez 'shutdown'.\n";
    // socket_write($msgsock, $msg, strlen($msg));

   
    if (false === ($buf = @socket_read($msgsock, 2048, PHP_NORMAL_READ))) {
      echo "socket_read() a échoué : raison : " . socket_strerror(socket_last_error($msgsock)) . "\n";
      //$msgsock = socket_accept($sock);
      break;
    }
    if (!$buf = trim($buf)) {
      continue;
    }
    if ($buf == 'quit') {
      break;
    }
    if ($buf == 'shutdown') {
      socket_close($msgsock);
      break 2;
    }
    echo "HEAD:$buf\n";
    if (true) {
      // normal case : now the file	  

      $filename="/var/tmp/eric".posix_getpid();
      $handle = @fopen($filename, "w");
      if ($handle) {
	
	$binary_mode=false;
	while ($out = @socket_read($msgsock, 2048, PHP_BINARY_READ)) {
	  $l=strlen($out);
	  if (($l==3)&&($out=="==\0")) break;
	  fwrite($handle,$out);
	  if ($l < 2048) {
	    if ($out[$l-1]!="\n") {
	      $binary_mode=true;
	    }
	    echo "file:$l []";
	    break;
	  }
	  echo "file:$l []";
	}
	fclose($handle);
      }
    }
    echo "\nEND FILE\n";

    /* if ($binary_mode) {
      //ignore last \0
      if (false === ($buf = @socket_read($msgsock, 2048, PHP_NORMAL_READ))) {
	echo "socket_read() a échoué : raison : " . socket_strerror(socket_last_error($msgsock)) . "\n";
	break;
      }
      echo "SKIP [$buf]\n";
      }*/
    if (false === ($buf = @socket_read($msgsock, 2048, PHP_NORMAL_READ))) {
      echo "socket_read() a échoué : raison : " . socket_strerror(socket_last_error($msgsock)) . "\n";
      break;
    }
    echo "FOOT [$buf]\n";
    

    $talkback = "PHP: You said '$buf'.\n";
    if (@socket_write($msgsock, $talkback, strlen($talkback))=== false) {
      echo "socket_write() a échoué : raison : " . socket_strerror(socket_last_error($msgsock)) . "\n";
    }
	

	
    
    //posix_kill(posix_getppid(), SIGUSR1);
    //echo "send signal:".posix_getpid() .",parent:". posix_getppid();
    echo "\nBefore close\n";
    socket_close($msgsock);
    echo "Working child:".posix_getpid() .",parent:". posix_getppid()."\n";
    sleep(1); // store request
    echo "Finish child:".posix_getpid() .",parent:". posix_getppid()."\n";
    exit(0);
  }
    
} 

socket_close($sock);
?>