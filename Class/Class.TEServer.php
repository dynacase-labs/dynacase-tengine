<?php
/**
 * Transformation server engine
 *
 * @author Anakeen 2007
 * @version $Id: Class.TEServer.php,v 1.13 2007/06/13 13:53:06 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package TE
 */
/**
 */

include_once("TE/Lib.TEUtil.php");
include_once("TE/Class.Task.php");
include_once("TE/Class.Engine.php");

// for signal handler function
declare (ticks = 1);

Class TEServer {
  public $cur_client=0;
  public $max_client=15;
  public $address = '0.0.0.0';
  public $port = 10000;
  public $dbaccess="dbname=te user=postgres";
  public $tmppath="/var/tmp";

  private $good=true; // main loop condition
  function decrease_child($sig) {
    $this->cur_client--;
    //    echo "One Less [$sig]  ".$this->cur_client."\n";
    pcntl_wait($status); // to suppress zombies
  
  }
  function closesockets($sig) {
    print "\nCLOSE SOCKET ".$this->msgsock."\n";
    @fclose($this->msgsock);
    if (isset($this->task)) {
      $this->task->status='I'; // interrupted
      $this->task->Modify();
    }
    $this->good=false;
  }

  /**
   * main loop to listen socket
   */
  function listenLoop() {
   
    
    error_reporting(E_ALL);

    /* Autorise l'exécution infinie du script, en attente de connexion. */
    set_time_limit(0);

    /* Active le vidage implicite des buffers de sortie, pour que nous
     * puissions voir ce que nous lisons au fur et à mesure. */
    ob_implicit_flush();


    pcntl_signal(SIGCHLD, array(&$this,"decrease_child"));
    pcntl_signal(SIGPIPE, array(&$this,"decrease_child"));
    pcntl_signal(SIGINT,  array(&$this,"closesockets"));



    $this->sock = stream_socket_server("tcp://".$this->address.":".$this->port, $errno, $errstr);


    echo "Listen on :"."tcp://".$this->address.":".$this->port."\n";

    while ($this->good) {
      $this->msgsock = @stream_socket_accept($this->sock,3,$peername);
      if ($this->msgsock === false) {
	if ($errno==0) echo "Accept : ".$this->cur_client." childs in work\n";
	else       echo "accept : $errstr ($errno)<br />\n";    
      } else {
	echo "Accept [".$this->cur_client."]\n";


	if ($this->cur_client> $this->max_client) {

	  $talkback = "Too many child [".$this->cur_client."] Reject\n";
	  //$childpid=pcntl_wait($wstatus); 
	  if (@fputs($this->msgsock, $talkback, strlen($talkback))=== false) {
	    echo "$errstr ($errno)<br />\n";
	  }
	  fclose($this->msgsock);
	} else {
	  $this->cur_client++;
	  $pid = pcntl_fork();
            
	  if ( $pid == -1 ) {       
	    // Fork failed           
	    exit(1);
	  } else if ( $pid ) {
	    // We are the parent
    
	    echo "Parent Waiting Accept:".$this->cur_client."\n";
    

	  } else {
	    // We are the child
	    // Do something with the inherited connection here
	    // It will get closed upon exit
	    /* Send instructions. */
	    $talkback = "Continue\n";
	    //$childpid=pcntl_wait($wstatus); 
	    if (@fputs($this->msgsock, $talkback, strlen($talkback))=== false) {
	      echo "fputs $errstr ($errno)<br />\n";

	    }
   
	    if (false === ($command = @fgets($this->msgsock))) {
	      echo "fget $errstr ($errno)<br />\n";
	      break;
	    }
	    $command=trim($command);
	    switch ($command) {
	    case "CONVERT":
	      $msg=$this->transfertFile();
	      if (@fputs($this->msgsock, $msg,strlen($msg))=== false) {
		 echo "fputs $errstr ($errno)<br />\n";		 
	       }
	      break;
	    case "INFO":
	      $msg=$this->getInfo();
	      if (@fputs($this->msgsock, $msg,strlen($msg))=== false) {
		 echo "fputs $errstr ($errno)<br />\n";		 
	       }
	      break;
	    case "GET":
	      $msg=$this->retrieveFile();
	      if (@fputs($this->msgsock, $msg,strlen($msg))=== false) {
		 echo "fputs $errstr ($errno)<br />\n";		 
	       }
	      break;
	    case "ABORT":
	      $msg=$this->Abort();
	      if (@fputs($this->msgsock, $msg,strlen($msg))=== false) {
		 echo "fputs $errstr ($errno)<br />\n";		 
	       }
	      break;
	    }
	    echo "COMMAND:$command\n";
	    fclose($this->msgsock);
	    exit(0);
	  }
	}
      }
    } 

    @fclose($sock);
  }
  
  /**
   * read file transmition request header + content file
   * header like : <TE name="latin" fkey="134" size="2022123" />
   * followed by file content
   * 
   * @return string  message to return 
   */
  function transfertFile() {
   if (false === ($buf = @fgets($this->msgsock))) {
     echo "fget $errstr ($errno)<br />\n";
     break;
   }
   $tename=false;
   if (preg_match("/name=[ ]*\"([^\"]*)\"/i",$buf,$match)) {
     $tename=$match[1];
   }
   if (preg_match("/fkey=[ ]*\"([^\"]*)\"/i",$buf,$match)) {
     $fkey=$match[1];
   }
   if (preg_match("/size=[ ]*\"([^\"]*)\"/i",$buf,$match)) {
     $size=intval($match[1]);
   }
   if (preg_match("/callback=[ ]*\"([^\"]*)\"/i",$buf,$match)) {
     $callback=$match[1];

   }
    // normal case : now the file	  

    $filename=$this->tmppath."/tes-".posix_getpid();
    $this->task=new Task($this->dbaccess);
    $this->task->engine=$tename;
    $this->task->infile=$filename;
    $this->task->fkey=$fkey;
    $this->task->callback=$callback;
    $this->task->status='T'; // transferring
    $peername=stream_socket_get_name($this->msgsock,true);
    if  ($peername) {
      $this->task->log(sprintf(_("transferring from %s"),$peername));
    }

    $err=$this->task->Add();
    if ($err=="") {
      $mb=microtime();
      $handle=false;
      $trbytes=0;
      if (!is_file($filename)) $handle = @fopen($filename, "w"); // only if not
      if ($handle) {
	do {
	  if ($size >= 2048) {
	    $rsize=2048;
	  } else {
	    $rsize=$size;
	  }	   
	  $out = @fread($this->msgsock, $rsize);
	  $l=strlen($out);
	  $trbytes+=$l;	     
	  $size-=$l;
	  fwrite($handle,$out);
	     
	  //echo "file:$l []";
	} while ($size>0);
	fclose($handle);
	//sleep(3);
	$this->task->log(sprintf("%d bytes read in %.03f sec",$trbytes,
				 microtime_diff(microtime(),$mb)));
	$this->task->status='W'; // waiting
      } else {
	$this->task->comment=sprintf(_("cannot create temporary file [%s]"),$filename);
	$this->task->status='K'; // KO
      }
      $this->task->Modify();
    }
    echo "\nEND FILE $trbytes bytes\n";

    if ($err=="") $talkback = "<response status=\"OK\">";    		       
    else $talkback = "<response status=\"KO\">";    
	

    $talkback.=sprintf("<task id=\"%s\" status=\"%s\"><comment>%s</comment></task>",
		       $this->task->tid,$this->task->status,str_replace("\n","; ",$this->task->comment));

    $talkback.="</response>\n";
    return $talkback;
  }

 /**
   * read file transmition request header + content file
   * header like : <TASK id="134"  />
   * 
   * @return string  message to return 
   */
  function getInfo() {
    $err="";
    if (false === ($buf = @fgets($this->msgsock))) {
      $err= "getInfo::fget $errstr ($errno)";     
    }
    if ($err=="") {
      if (preg_match("/ id=[ ]*\"([^\"]*)\"/i",$buf,$match)) {
	$tid=$match[1];
      }
      $this->task=new Task($this->dbaccess,$tid);
   
      if ($this->task->isAffected()) {     
	$message="<response status=\"OK\">";
	$message.="<TASK>";
	foreach ($this->task->fields as $k=>$v) {
	  $message .= "<$v>".str_replace("\n","; ",$this->task->$v)."</$v>";
	}
	$message.="</TASK></response>\n";
      } else {
	$err=sprintf(_("unknow task [%s]"),$tid);
	$message="<response status=\"KO\">$err</response>\n";
      }
    } else {
      $message="<response status=\"KO\">$err</response>\n";
    }
    return $message;
  }


  /**
   * delete files and reference to the task
   * try kill process if is in processing
   * header like : <TASK id="134"  />
   * 
   * @return string  message to return 
   */
  function Abort() {
    $err="";
    if (false === ($buf = @fgets($this->msgsock))) {
      $err= "Abord::fget $errstr ($errno)";     
    }
    if ($err=="") {
      if (preg_match("/ id=[ ]*\"([^\"]*)\"/i",$buf,$match)) {
	$tid=$match[1];
      }
      $this->task=new Task($this->dbaccess,$tid);
   
      if ($this->task->isAffected()) {     
	if ($this->task->status=='P') {
	}
	$outfile=$this->task->outfile;
	if ($outfile) {
	  @unlink($outfile);
	  @unlink($outfile.".err");
	}
	$infile=$this->task->infile;
	if ($infile) @unlink($infile);
	$err=$this->task->delete();
	if ($err!="") $message="<response status=\"KO\">$err";
	else $message="<response status=\"OK\">";
	$message.="</response>\n";
      } else {
	$err=sprintf(_("unknow task [%s]"),$tid);
	$message="<response status=\"KO\">$err</response>\n";
      }
    } else {
      $message="<response status=\"KO\">$err</response>\n";
    }
    return $message;
  }
  /**
   * return  file content in
   * header like : <Task id="134" />
   * 
   * 
   * @return string  message to return 
   */
  function retrieveFile() {
    $err="";
    if (false === ($buf = @fgets($this->msgsock))) {
      echo "fget $errstr ($errno)<br />\n";
      break;
    }
    if (preg_match("/ id=[ ]*\"([^\"]*)\"/i",$buf,$match)) {
      $tid=$match[1];
    } else {
      $err= sprintf(_("header [%s] : syntax error"),$buf);     
    }
    if ($err=="") {
      $this->task=new Task($this->dbaccess,$tid);
      if ($this->task->isAffected()) {
	// normal case : now the file	  
	$filename=$this->task->outfile;
	if ($this->task->status != 'D') $err=sprintf("status is not Done [%s] for task %s",
						     $this->task->status,
						     $this->task->tid);
	else if ($this->task->outfile == '') $err=sprintf("empty generated file for task %s",
							  $this->task->tid);
	else if (!file_exists($this->task->outfile)) $err=sprintf("Generated file [%s] not found for task %s",
								  $this->task->outfile,
								  $this->task->tid);
	if ($err=="") {		
	  $peername=stream_socket_get_name($this->msgsock,true);
	  if  ($peername) {
	    $this->task->log(sprintf(_("transferring to %s"),$peername));
	  }
	  if ($err=="") {
	    $mb=microtime();
	    $trbytes=0;
	    $handle = @fopen($this->task->outfile, "r");
	    if ($handle) {
	      $size=filesize($this->task->outfile);
	      
	      $buffer=sprintf("<response status=\"OK\"><task id=\"%s\" size=\"%d\"></response>\n",$this->task->tid,$size);
	      fputs($this->msgsock,$buffer,strlen($buffer));
	      while (!feof($handle)) {
		$buffer = fread($handle, 2048);
		fputs($this->msgsock,$buffer,strlen($buffer));
	      }	
	      fclose($handle);
	    }

	    fflush($this->msgsock);
	    //sleep(3);
	    $this->task->log(sprintf("%d bytes wroted in %.03f sec",$size,
				     microtime_diff(microtime(),$mb)));
	  }
	} else {
	  $this->task->log($err);
	}
	echo "\nEND FILE $trbytes bytes\n";   		       		  
	$this->task->Modify();	
      } 
    } else {
      $err = sprintf(_("task [%s] not exist",$tid));
    }
    print "RECIEVE ERROOR :$err\n";
    if ($err!="") {
      $err=str_replace("\n","; ",$err);
      return "<response status=\"KO\">$err</response>";
    }
    return "<response status=\"OK\"></response>";
  }
}



?>