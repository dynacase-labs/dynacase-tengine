<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
 */

require_once "TE/Lib.TE.php";
require_once "TE/Class.Task.php";
require_once "TE/Class.Engine.php";
// for signal handler function
declare(ticks = 1);

Class TEServer
{
    public $cur_client = 0;
    public $max_client = 15;
    public $address = '0.0.0.0';
    public $port = 51968;
    public $dbaccess = "dbname=te user=postgres";
    public $tmppath = "/var/tmp";
    
    private $good = true;
    private $msgsock;
    /** @var Task $task */
    private $task;
    private $sock;
    // main loop condition
    function decrease_child()
    {
        while (($child = pcntl_waitpid(-1, $status, WNOHANG)) > 0) {
            $this->cur_client--;
            // pcntl_wait($status); // to suppress zombies
            
        }
    }
    
    private function formatErrorReturn($err)
    {
        return "<response status=\"KO\">$err</response>\n";
    }
    
    function closesockets()
    {
        print "\nCLOSE SOCKET " . $this->msgsock . "\n";
        @fclose($this->msgsock);
        if (isset($this->task)) {
            $this->task->status = 'I'; // interrupted
            $this->task->Modify();
        }
        $this->good = false;
    }
    /**
     * main loop to listen socket
     */
    function listenLoop()
    {
        error_reporting(E_ALL);
        /* Autorise l'exécution infinie du script, en attente de connexion. */
        set_time_limit(0);
        /* Active le vidage implicite des buffers de sortie, pour que nous
         * puissions voir ce que nous lisons au fur et à mesure. */
        ob_implicit_flush();
        
        pcntl_signal(SIGCHLD, array(&$this,
            "decrease_child"
        ));
        pcntl_signal(SIGPIPE, array(&$this,
            "decrease_child"
        ));
        pcntl_signal(SIGINT, array(&$this,
            "closesockets"
        ));
        pcntl_signal(SIGTERM, array(&$this,
            "closesockets"
        ));
        
        $this->sock = stream_socket_server("tcp://" . $this->address . ":" . $this->port, $errno, $errstr);
        if ($this->sock === false) {
            echo sprintf("Error: could not open server socket on 'tcp://%s:%s': (%s) %s", $this->address, $this->port, $errno, $errstr);
            exit(1);
        }
        
        echo "Listen on :" . "tcp://" . $this->address . ":" . $this->port . "\n";
        
        while ($this->good) {
            $this->msgsock = @stream_socket_accept($this->sock, 3, $peername);
            if ($this->msgsock === false) {
                if ($errno == 0) {
                    echo "Accept : " . $this->cur_client . " childs in work\n";
                } else {
                    echo "accept : $errstr ($errno)<br />\n";
                }
            } else {
                echo "Accept [" . $this->cur_client . "]\n";
                
                if ($this->cur_client >= $this->max_client) {
                    
                    $talkback = "Too many child [" . $this->cur_client . "] Reject\n";
                    //$childpid=pcntl_wait($wstatus);
                    if (@fputs($this->msgsock, $talkback, strlen($talkback)) === false) {
                        echo "$errstr ($errno)<br />\n";
                    }
                    fclose($this->msgsock);
                } else {
                    $this->cur_client++;
                    $pid = pcntl_fork();
                    
                    PgObj::close_my_pg_connections();
                    
                    if ($pid == - 1) {
                        // Fork failed
                        exit(1);
                    } else if ($pid) {
                        // We are the parent
                        echo "Parent Waiting Accept:" . $this->cur_client . "\n";
                    } else {
                        // We are the child
                        // Do something with the inherited connection here
                        // It will get closed upon exit
                        /* Send instructions. */
                        $talkback = "Continue\n";
                        //$childpid=pcntl_wait($wstatus);
                        if (@fputs($this->msgsock, $talkback, strlen($talkback)) === false) {
                            echo "fputs $errstr ($errno)<br />\n";
                        }
                        
                        if (false === ($command = @fgets($this->msgsock))) {
                            echo "fget $errstr ($errno)<br />\n";
                            break;
                        }
                        $command = trim($command);
                        switch ($command) {
                            case "CONVERT":
                                $msg = $this->transfertFile();
                                if (@fputs($this->msgsock, $msg, strlen($msg)) === false) {
                                    echo "fputs $errstr ($errno)<br />\n";
                                }
                                break;

                            case "INFO":
                                $msg = $this->getInfo();
                                if (@fputs($this->msgsock, $msg, strlen($msg)) === false) {
                                    echo "fputs $errstr ($errno)<br />\n";
                                }
                                break;

                            case "GET":
                                $msg = $this->retrieveFile();
                                if (@fputs($this->msgsock, $msg, strlen($msg)) === false) {
                                    echo "fputs $errstr ($errno)<br />\n";
                                }
                                break;

                            case "ABORT":
                                $msg = $this->Abort();
                                if (@fputs($this->msgsock, $msg, strlen($msg)) === false) {
                                    echo "fputs $errstr ($errno)<br />\n";
                                }
                                break;
                        }
                        fclose($this->msgsock);
                        exit(0);
                    }
                }
            }
        }
        
        @fclose($this->sock);
    }
    /**
     * read file transmition request header + content file
     * header like : <TE name="latin" fkey="134" size="2022123" />
     * followed by file content
     *
     * @throws Exception
     * @return string  message to return
     */
    function transfertFile()
    {
        if (false === ($buf = @fgets($this->msgsock))) {
            throw new Exception("fgets error");
        }
        $tename = false;
        if (preg_match("/name=[ ]*\"([^\"]*)\"/i", $buf, $match)) {
            $tename = $match[1];
        }
        $fkey = '';
        if (preg_match("/fkey=[ ]*\"([^\"]*)\"/i", $buf, $match)) {
            $fkey = $match[1];
        }
        $size = '';
        if (preg_match("/size=[ ]*\"([^\"]*)\"/i", $buf, $match)) {
            $size = intval($match[1]);
        }
        $callback = '';
        if (preg_match("/callback=[ ]*\"([^\"]*)\"/i", $buf, $match)) {
            $callback = $match[1];
        }
        $ext = "";
        $fname = "";
        if (preg_match("/fname=[ ]*\"([^\"]*)\"/i", $buf, $match)) {
            $fname = $match[1];
            $ext = te_fileextension($fname);
            if ($ext) $ext = '.' . $ext;
        }
        $cmime = "";
        if (preg_match("/mime=[ ]*\"([^\"]*)\"/i", $buf, $match)) {
            $cmime = $match[1];
        }
        // normal case : now the file
        
        $filename = tempnam($this->tmppath, "tes-");
        if ($filename !== false) {
            $filename_ext = $filename . $ext;
            if (rename($filename, $filename_ext) !== false) {
                $filename = $filename_ext;
            }
        }
        $this->task = new Task($this->dbaccess);
        $this->task->engine = $tename;
        $this->task->infile = $filename;
        $this->task->fkey = $fkey;
        $this->task->callback = $callback;
        $this->task->status = 'B'; // Initializing
        $peername = stream_socket_get_name($this->msgsock, true);
        
        $err = $this->task->Add();
        // find first a compatible engine
        $eng = new Engine($this->dbaccess);
        if ($eng->existsEngine($this->task->engine)) {
            if ($cmime) {
                if (!$eng->isAffected()) {
                    $eng = $eng->GetNearEngine($this->task->engine, $cmime);
                }
                if ($eng && $eng->isAffected()) {
                    $talkback = "<response status=\"OK\">";
                    $talkback.= sprintf("<task id=\"%s\" status=\"%s\"><comment>%s</comment></task>", $this->task->tid, $this->task->status, str_replace("\n", "; ", $this->task->comment));
                    
                    $talkback.= "</response>\n";
                    fputs($this->msgsock, $talkback, strlen($talkback));
                } else {
                    $err = sprintf(_("No compatible engine %s found for %s") , $tename, $fname);
                    $this->task->log("Incompatible mime [$cmime]");
                }
            }
        } else {
            $err = sprintf(_("Engine %s not found") , $this->task->engine);
        }
        if ($err == "") {
            $mb = microtime();
            $handle = false;
            $trbytes = 0;
            if ($peername) {
                $this->task->log(sprintf(_("transferring from %s") , $peername));
            }
            if ($filename !== false) {
                $handle = @fopen($filename, "w");
            }
            if ($handle) {
                $this->task->status = 'T'; // transferring
                $this->task->modify();
                $orig_size = $size;
                do {
                    if ($size >= 2048) {
                        $rsize = 2048;
                    } else {
                        $rsize = $size;
                    }
                    $out = @fread($this->msgsock, $rsize);
                    if ($out === false || $out === "") {
                        $err = sprintf("error reading from msgsock (%s/%s bytes transferred))", $trbytes, $orig_size);
                        break;
                    }
                    $l = strlen($out);
                    $trbytes+= $l;
                    $size-= $l;
                    fwrite($handle, $out);
                    //echo "file:$l []";
                    
                } while ($size > 0);
                fclose($handle);
                if ($err == "") {
                    //sleep(3);
                    $this->task->log(sprintf("%d bytes read in %.03f sec", $trbytes, te_microtime_diff(microtime() , $mb)));
                    $this->task->status = 'W'; // waiting
                    $this->task->inmime = ""; // reset mime type
                    $this->task->Modify();
                }
            } else {
                $err = sprintf(_("cannot create temporary file [%s]") , $filename);
            }
        }
        
        if ($err != "") {
            $talkback = "<response status=\"KO\">";
            $this->task->comment = $err;
            $this->task->log($err);
            $this->task->status = 'K'; // KO
            $this->task->Modify();
        } else $talkback = "<response status=\"OK\">";
        
        $talkback.= sprintf("<task id=\"%s\" status=\"%s\"><comment>%s</comment></task>", $this->task->tid, $this->task->status, str_replace("\n", "; ", $this->task->comment));
        
        $talkback.= "</response>\n";
        
        return $talkback;
    }
    /**
     * read file transmition request header + content file
     * header like : <TASK id="134"  />
     *
     * @throws Exception
     * @return string  message to return
     */
    function getInfo()
    {
        try {
            if (false === ($buf = @fgets($this->msgsock))) {
                throw new Exception("getInfo::fget");
            }
            if (preg_match("/ id=[ ]*\"([^\"]*)\"/i", $buf, $match)) {
                $tid = $match[1];
            } else {
                throw new Exception("Syntax error");
            }
            $this->task = new Task($this->dbaccess, $tid);
            
            if (!$this->task->isAffected()) {
                throw new Exception(sprintf(_("unknow task [%s]") , $tid));
            }
            
            $message = "<response status=\"OK\">";
            $message.= "<TASK>";
            foreach ($this->task->fields as $v) {
                $message.= "<$v>" . str_replace("\n", "; ", $this->task->$v) . "</$v>";
            }
            $message.= "</TASK></response>\n";
            
            return $message;
        }
        catch(Exception $e) {
            return $this->formatErrorReturn($e->getMessage());
        }
    }
    /**
     * delete files and reference to the task
     * try kill process if is in processing
     * header like : <TASK id="134"  />
     *
     * @throws Exception
     * @return string  message to return
     */
    function Abort()
    {
        try {
            if (false === ($buf = @fgets($this->msgsock))) {
                throw new Exception("Abort::fget");
            }
            if (preg_match("/ id=[ ]*\"([^\"]*)\"/i", $buf, $match)) {
                $tid = $match[1];
            } else {
                throw new Exception("Syntax error");
            }
            $this->task = new Task($this->dbaccess, $tid);
            
            if (!$this->task->isAffected()) {
                throw new Exception(sprintf(_("unknow task [%s]") , $tid));
            }
            
            $outfile = $this->task->outfile;
            if ($outfile) {
                @unlink($outfile);
                @unlink($outfile . ".err");
            }
            
            $infile = $this->task->infile;
            if ($infile) {
                @unlink($infile);
            }
            
            $err = $this->task->delete();
            if ($err != "") {
                throw new Exception($err);
            }
            
            $message = "<response status=\"OK\"></response>\n";
            return $message;
        }
        catch(Exception $e) {
            return $this->formatErrorReturn($e->getMessage());
        }
    }
    /**
     * return  file content in
     * header like : <Task id="134" />
     *
     * @throws Exception if socket lost
     * @return string  message to return
     */
    function retrieveFile()
    {
        if (false === ($buf = @fgets($this->msgsock))) {
            throw new Exception("retrieveFile::fget");
        }
        
        try {
            if (preg_match("/ id=[ ]*\"([^\"]*)\"/i", $buf, $match)) {
                $tid = $match[1];
            } else {
                throw new Exception(sprintf(_("header [%s] : syntax error") , $buf));
            }
            $this->task = new Task($this->dbaccess, $tid);
            if (!$this->task->isAffected()) {
                throw new Exception(sprintf(_("task [%s] not exist", $tid)));
            }
            // normal case : now the file
            $filename = $this->task->outfile;
            if ($this->task->status != 'D') {
                $err = sprintf("status is not Done [%s] for task %s", $this->task->status, $this->task->tid);
                $this->task->log($err);
                throw new Exception($err);
            }
            if ($filename == '') {
                $err = sprintf("empty generated file for task %s", $this->task->tid);
                $this->task->log($err);
                throw new Exception($err);
            }
            if (!file_exists($filename)) {
                $err = sprintf("Generated file [%s] not found for task %s", $filename, $this->task->tid);
                $this->task->log($err);
                throw new Exception($err);
            }
            $peername = stream_socket_get_name($this->msgsock, true);
            if ($peername) {
                $this->task->log(sprintf(_("transferring to %s") , $peername));
            }
            $mb = microtime();
            $trbytes = 0;
            $size = 0;
            $handle = @fopen($filename, "r");
            if ($handle) {
                $size = filesize($filename);
                
                $buffer = sprintf("<response status=\"OK\"><task id=\"%s\" size=\"%d\"></response>\n", $this->task->tid, $size);
                fputs($this->msgsock, $buffer, strlen($buffer));
                while (!feof($handle)) {
                    $buffer = fread($handle, 2048);
                    fputs($this->msgsock, $buffer, strlen($buffer));
                }
                fclose($handle);
            }
            
            fflush($this->msgsock);
            $this->task->log(sprintf("%d bytes wroted in %.03f sec", $size, te_microtime_diff(microtime() , $mb)));
            $this->task->Modify();
            
            return "<response status=\"OK\"></response>";
        }
        catch(Exception $e) {
            return $this->formatErrorReturn($e->getMessage());
        }
    }
}
?>
