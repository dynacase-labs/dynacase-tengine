<?php
/*
 * @author Anakeen
 * @package FDL
*/

require_once "TE/Lib.TE.php";
require_once "TE/Class.Task.php";
require_once "TE/Class.Engine.php";
require_once "TE/Class.Selftest.php";
// for signal handler function
declare(ticks = 1);

Class TEServer
{
    public $cur_client = 0;
    public $max_client = 15;
    public $address = '0.0.0.0';
    public $port = 51968;
    public $dbaccess = "dbname=te user=postgres";
    public $workDir = "/var/tmp";
    
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
            $this->task->pid = '';
            $this->task->status = Task::STATE_INTERRUPTED; // interrupted
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

                            case "INFO:ENGINES":
                                $msg = $this->getEngines();
                                if (@fputs($this->msgsock, $msg, strlen($msg)) === false) {
                                    echo "fputs $errstr ($errno)<br />\n";
                                }
                                break;

                            case "INFO:TASKS":
                                $msg = $this->getTasks();
                                if (@fputs($this->msgsock, $msg, strlen($msg)) === false) {
                                    echo "fputs $errstr ($errno)<br />\n";
                                }
                                break;

                            case "INFO:HISTO":
                                $msg = $this->getHisto();
                                if (@fputs($this->msgsock, $msg, strlen($msg)) === false) {
                                    echo "fputs $errstr ($errno)<br />\n";
                                }
                                break;

                            case "INFO:SERVER":
                                $msg = $this->serverInfo();
                                if (@fputs($this->msgsock, $msg, strlen($msg)) === false) {
                                    echo "fputs $errstr ($errno)<br />\n";
                                }
                                break;

                            case "INFO:SERVER:EXTENDED":
                                $msg = $this->serverExtendedInfo();
                                if (@fputs($this->msgsock, $msg, strlen($msg)) === false) {
                                    echo "fputs $errstr ($errno)<br />\n";
                                }
                                break;

                            case "INFO:SELFTESTS":
                                $msg = $this->getSelftests();
                                if (@fputs($this->msgsock, $msg, strlen($msg)) === false) {
                                    echo "fputs $errstr ($errno)<br />\n";
                                }
                                break;

                            case "SELFTEST":
                                $msg = $this->executeSelftest();
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

                            case "PURGE":
                                $msg = $this->purgeTasks();
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
        try {
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
            $taskDir = Task::newTaskWorkDir($this->workDir);
            if ($taskDir === false) {
                throw new Exception(sprintf("Error creating new task's work directory in '%s'.", $this->workDir));
            }
            $filename = tempnam($taskDir, "tes-");
            if ($filename === false) {
                throw new Exception(sprintf("Error creating new file in task's work directory '%s'.", $taskDir));
            }
            $filename_ext = $filename . $ext;
            if (rename($filename, $filename_ext) !== false) {
                $filename = $filename_ext;
            }
            
            $this->task = new Task($this->dbaccess);
            $this->task->engine = $tename;
            $this->task->infile = $filename;
            $this->task->fkey = $fkey;
            $this->task->callback = $callback;
            $this->task->status = Task::STATE_BEGINNING; // Initializing
            $err = $this->task->Add();
            if ($err != '') {
                throw new Exception("Error adding new task in database: %s", $err);
            }
            // find first a compatible engine
            $eng = new Engine($this->dbaccess);
            if (!$eng->existsEngine($this->task->engine)) {
                throw new Exception(sprintf(_("Engine %s not found") , $this->task->engine));
            }
            if ($cmime) {
                if (!$eng->isAffected()) {
                    $eng = $eng->GetNearEngine($this->task->engine, $cmime);
                }
                if (!$eng || !$eng->isAffected()) {
                    $this->task->log("Incompatible mime [$cmime]");
                    $this->task->Modify();
                    $err = sprintf(_("No compatible engine %s found for %s") , $tename, $fname);
                    throw new Exception($err);
                }
                $talkback = "<response status=\"OK\">";
                $talkback.= sprintf("<task id=\"%s\" status=\"%s\"><comment>%s</comment></task>", $this->task->tid, $this->task->status, str_replace("\n", "; ", $this->task->comment));
                $talkback.= "</response>\n";
                fputs($this->msgsock, $talkback, strlen($talkback));
            }
            $mb = microtime();
            $handle = false;
            $trbytes = 0;
            if ($filename !== false) {
                $handle = @fopen($filename, "w");
            }
            if ($handle === false) {
                throw new Exception(sprintf(_("Error opening task's file '%s' for writing.") , $filename));
            }
            $peername = stream_socket_get_name($this->msgsock, true);
            if ($peername) {
                $this->task->log(sprintf(_("transferring from %s") , $peername));
            }
            $this->task->status = Task::STATE_TRANSFERRING; // transferring
            $this->task->Modify();
            $orig_size = $size;
            do {
                if ($size >= 2048) {
                    $rsize = 2048;
                } else {
                    $rsize = $size;
                }
                $out = @fread($this->msgsock, $rsize);
                if ($out === false || $out === "") {
                    fclose($handle);
                    throw new Exception(sprintf("error reading from msgsock (%s/%s bytes transferred))", $trbytes, $orig_size));
                }
                $l = strlen($out);
                $trbytes+= $l;
                $size-= $l;
                if (fwrite($handle, $out) === false) {
                    fclose($handle);
                    throw new Exception(sprintf("Error writing to file '%s'.", $filename));
                }
            } while ($size > 0);
            fclose($handle);
            $this->task->log(sprintf("%d bytes read in %.03f sec", $trbytes, te_microtime_diff(microtime() , $mb)));
            $this->task->status = Task::STATE_WAITING; // waiting
            $this->task->inmime = ""; // reset mime type
            $this->task->Modify();
        }
        catch(Exception $e) {
            $this->task->comment = $e->getMessage();
            $this->task->log($e->getMessage());
            $this->task->status = Task::STATE_ERROR; // KO
            $this->task->Modify();
            return $this->formatErrorReturn($e->getMessage());
        }
        
        $talkback = "<response status=\"OK\">";
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
            if ($this->task->status != Task::STATE_INTERRUPTED) {
                $this->task->interrupt();
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
                throw new Exception(sprintf(_("task [%s] not exist") , $tid));
            }
            // normal case : now the file
            $filename = $this->task->outfile;
            if ($this->task->status != Task::STATE_SUCCESS) {
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
            $this->task->delete();
            return "<response status=\"OK\"></response>";
        }
        catch(Exception $e) {
            return $this->formatErrorReturn($e->getMessage());
        }
    }
    /**
     * @param $fp
     * @param $string
     * @return int
     */
    private function fwrite_stream($fp, $string)
    {
        for ($written = 0; $written < strlen($string); $written+= $fwrite) {
            $fwrite = fwrite($fp, substr($string, $written));
            if ($fwrite === false) {
                return $written;
            }
        }
        return $written;
    }
    /**
     * Read a specific number of bytes from the given socket file descriptor.
     * @param $fp
     * @param $size
     * @return bool|string the data or bool(false) on error
     */
    private function read_size($fp, $size)
    {
        $buf = '';
        while ($size > 0) {
            if ($size >= 2048) {
                $rsize = 2048;
            } else {
                $rsize = $size;
            }
            $data = fread($fp, $rsize);
            if ($data === false || $data === "") {
                return false;
            }
            $size-= strlen($data);
            $buf.= $data;
        }
        return $buf;
    }
    /** @noinspection PhpUnusedPrivateMethodInspection
     * Read all data till end-of-file from the given socket file descriptor.
     * @param $fp
     * @return bool|string the data or bool(false) on error
     */
    private function read_eof($fp)
    {
        $buf = '';
        while (!feof($fp)) {
            if (($data = fread($fp, 2048)) === false) {
                return false;
            }
            $buf.= $data;
        }
        return $buf;
    }
    public function getEngines()
    {
        try {
            $engine = new Engine($this->dbaccess);
            $response = $engine->getAllEngines();
            if (!is_array($response)) {
                throw new Exception("Found no engines");
            }
            $json = json_encode($response);
            $buffer = sprintf("<response status=\"OK\" size=\"%d\" type=\"application/json\"/>\n%s", strlen($json) , $json);
            $ret = $this->fwrite_stream($this->msgsock, $buffer);
            if ($ret != strlen($buffer)) {
                throw new Exception("Error writing content to socket");
            }
            fflush($this->msgsock);
            return '';
        }
        catch(Exception $e) {
            return $this->formatErrorReturn($e->getMessage());
        }
    }
    public function getTasks()
    {
        try {
            if (false === ($buf = @fgets($this->msgsock))) {
                throw new Exception("fgets error");
            }
            if (!preg_match('/^<args\s+/', $buf)) {
                throw new Exception("Missing args");
            }
            $size = 0;
            if (preg_match('/\bsize\s*=\s*"(?P<size>\d+)"/', $buf, $m)) {
                $size = $m['size'];
            }
            if ($size <= 0) {
                throw new Exception("Missing or empty args size");
            }
            $type = '';
            if (preg_match('/\btype\s*=\s*"(?P<type>[^"]+)"/', $buf, $m)) {
                $type = $m['type'];
            }
            if ($type != 'application/json') {
                throw new Exception(sprintf("Missing or unsupported args type ('%s')", $type));
            }
            $buf = $this->read_size($this->msgsock, $size);
            if ($buf === false) {
                throw new Exception(sprintf("Error reading args data from client"));
            }
            $args = json_decode($buf, true);
            if (!is_array($args)) {
                throw new Exception("Malformed args data");
            }
            $task = new Task($this->dbaccess);
            $response = $task->getTasks($args);
            if (!is_array($response)) {
                $response = array();
            }
            $json = json_encode($response);
            $buf = sprintf("<response status=\"OK\" size=\"%d\" type=\"application/json\"/>\n%s", strlen($json) , $json);
            $ret = $this->fwrite_stream($this->msgsock, $buf);
            if ($ret != strlen($buf)) {
                throw new Exception("Error writing content to socket");
            }
            fflush($this->msgsock);
            return '';
        }
        catch(Exception $e) {
            return $this->formatErrorReturn($e->getMessage());
        }
    }
    /**
     * Get histo log for a single task tid
     * @return string
     */
    public function getHisto()
    {
        try {
            if (false === ($buf = @fgets($this->msgsock))) {
                throw new Exception("fgets error");
            }
            if (!preg_match('/^<task\s+/', $buf)) {
                throw new Exception("Missing task argument");
            }
            $tid = '';
            if (preg_match('/\bid\s*=\s*"(?P<tid>[^"]+)"/', $buf, $m)) {
                $tid = $m['tid'];
            }
            if ($tid === '') {
                throw new Exception("Missing or empty tid");
            }
            $histo = new Histo($this->dbaccess);
            $response = $histo->getTaskHisto($tid);
            if (!is_array($response)) {
                $response = array();
            }
            $json = json_encode($response);
            $buf = sprintf("<response status=\"OK\" size=\"%d\" type=\"application/json\"/>\n%s", strlen($json) , $json);
            $ret = $this->fwrite_stream($this->msgsock, $buf);
            if ($ret != strlen($buf)) {
                throw new Exception("Error writing content to socket");
            }
            fflush($this->msgsock);
            return '';
        }
        catch(Exception $e) {
            return $this->formatErrorReturn($e->getMessage());
        }
    }
    private function version()
    {
        return trim(file_get_contents(getenv('TE_HOME') . DIRECTORY_SEPARATOR . 'VERSION'));
    }
    private function release()
    {
        return trim(file_get_contents(getenv('TE_HOME') . DIRECTORY_SEPARATOR . 'RELEASE'));
    }
    private function getServerInfo()
    {
        return array(
            "version" => $this->version() ,
            "release" => $this->release() ,
            "load" => sys_getloadavg() ,
            "cur_client" => $this->cur_client,
            "max_client" => $this->max_client
        );
    }
    private function getServerExtendedInfo()
    {
        $task = new Task($this->dbaccess);
        $statusBreakdown = $task->getStatusBreakdown();
        return array_merge($this->getServerInfo() , array(
            "status_breakdown" => $statusBreakdown
        ));
    }
    public function serverInfo()
    {
        try {
            $serverInfo = json_encode($this->getServerInfo());
            $msg = sprintf("<response status=\"OK\" size=\"%d\" type=\"application/json\"/>\n%s", strlen($serverInfo) , $serverInfo);
            $ret = $this->fwrite_stream($this->msgsock, $msg);
            if ($ret != strlen($msg)) {
                throw new Exception("Error writing content to socket");
            }
        }
        catch(Exception $e) {
            return $this->formatErrorReturn($e->getMessage());
        }
        return '';
    }
    public function serverExtendedInfo()
    {
        try {
            $serverInfo = json_encode($this->getServerExtendedInfo());
            $msg = sprintf("<response status=\"OK\" size=\"%d\" type=\"application/json\"/>\n%s", strlen($serverInfo) , $serverInfo);
            $ret = $this->fwrite_stream($this->msgsock, $msg);
            if ($ret != strlen($msg)) {
                throw new Exception("Error writing content to socket");
            }
        }
        catch(Exception $e) {
            return $this->formatErrorReturn($e->getMessage());
        }
        return '';
    }
    public function getSelftests()
    {
        try {
            $test = new Selftest($this->workDir);
            $selftests = json_encode($test->getSelftests());
            $msg = sprintf("<response status=\"OK\" size=\"%d\" type=\"application/json\"/>\n%s", strlen($selftests) , $selftests);
            $ret = $this->fwrite_stream($this->msgsock, $msg);
            if ($ret != strlen($msg)) {
                throw new Exception("Error writing content to socket");
            }
        }
        catch(Exception $e) {
            return $this->formatErrorReturn($e->getMessage());
        }
        return '';
    }
    public function executeSelftest()
    {
        try {
            if (false === ($buf = @fgets($this->msgsock))) {
                throw new Exception("fgets error");
            }
            if (!preg_match('/^<selftest\s+/', $buf)) {
                throw new Exception("Missing task argument");
            }
            $selftestId = '';
            if (preg_match('/\bid\s*=\s*"(?P<selftestId>[^"]+)"/', $buf, $m)) {
                $selftestId = $m['selftestId'];
            }
            if ($selftestId === '') {
                throw new Exception("Missing or empty selftest id");
            }
            $test = new Selftest($this->workDir);
            $status = $test->executeSelftest($selftestId, $output);
            $result = json_encode(array(
                "status" => $status,
                "output" => $output
            ));
            $msg = sprintf("<response status=\"OK\" size=\"%d\" type=\"application/json\"/>\n%s", strlen($result) , $result);
            $ret = $this->fwrite_stream($this->msgsock, $msg);
            if ($ret != strlen($msg)) {
                throw new Exception("Error writing content to socket");
            }
        }
        catch(Exception $e) {
            return $this->formatErrorReturn($e->getMessage());
        }
        return '';
    }
    public function purgeTasks()
    {
        try {
            if (false === ($buf = @fgets($this->msgsock))) {
                throw new Exception("fgets error");
            }
            if (!preg_match('/^<tasks\s+/', $buf)) {
                throw new Exception("Missing tasks argument");
            }
            $maxdays = 0;
            if (preg_match('/\bmaxdays\s*=\s*"(?P<maxdays>[0-9.]+)"/', $buf, $m)) {
                $maxdays = $m['maxdays'];
            }
            if (!is_numeric($maxdays)) {
                throw new Exception("Invalid maxdays '%s'", $maxdays);
            }
            $status = '';
            if (preg_match('/\bstatus\s*=\s*"(?P<status>[A-Z]+)"/', $buf, $m)) {
                $status = $m['status'];
            }
            $tid = '';
            if (preg_match('/\btid\s*=\s*"(?P<tid>[^"]+)"/', $buf, $m)) {
                $tid = $m['tid'];
            }
            if ($tid != '') {
                $task = new Task($this->dbaccess, $tid);
                $err = $task->delete();
                if ($err != '') {
                    throw new Exception($err);
                }
            } else {
                $task = new Task($this->dbaccess);
                $task->purgeTasks($maxdays, $status);
            }
            $msg = sprintf("<response status=\"OK\"></response>");
            $ret = $this->fwrite_stream($this->msgsock, $msg);
            if ($ret != strlen($msg)) {
                throw new Exception("Error writing content to socket");
            }
        }
        catch(Exception $e) {
            return $this->formatErrorReturn($e->getMessage());
        }
        return '';
    }
}
