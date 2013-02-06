<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
 */

require_once "TE/Lib.TE.php";
require_once "TE/Class.Task.php";
require_once "TE/Class.QueryPg.php";
require_once "TE/Class.Engine.php";
// for signal handler function
declare(ticks = 1);

Class TERendering
{
    public $cur_client = 0;
    public $max_client = 10;
    public $dbaccess = "dbname=te user=postgres";
    public $password;
    public $login = false;
    public $tmppath = "/var/tmp";
    
    private $good = true;
    /** @var Task $task */
    public $task;
    public $status;
    // main loop condition
    function decrease_child($sig)
    {
        while (($child = pcntl_waitpid(-1, $status, WNOHANG)) > 0) {
            $this->cur_client--;
            echo "One Less (pid = $child / sig = $sig)  " . $this->cur_client . "\n";
            // pcntl_wait($status); // to suppress zombies
            
        }
    }
    
    function rewaiting()
    {
        if ($this->task) {
            $this->task->status = 'W'; // waiting
            $this->task->log('Interrupted');
            $this->task->Modify();
        }
        exit(0);
    }
    
    function breakloop()
    {
        $this->good = false;
    }
    /**
     * main loop to listen socket
     */
    function listenLoop()
    {
        /* unlimit execution time. */
        set_time_limit(0);
        
        pcntl_signal(SIGCHLD, array(&$this,
            "decrease_child"
        ));
        pcntl_signal(SIGPIPE, array(&$this,
            "decrease_child"
        ));
        pcntl_signal(SIGINT, array(&$this,
            "breakloop"
        ));
        pcntl_signal(SIGTERM, array(&$this,
            "breakloop"
        ));
        
        while ($this->good) {
            
            if ($this->cur_client >= $this->max_client) {
                echo "Too many [" . $this->cur_client . "]\n";
                sleep(10);
            } else {
                echo "Wait [" . $this->cur_client . "]\n";
                if ($this->HasWaitingTask()) {
                    
                    echo "Accept [" . $this->cur_client . "]\n";
                    $this->cur_client++;
                    $pid = pcntl_fork();
                    
                    PgObj::close_my_pg_connections();
                    
                    if ($pid == - 1) {
                        // Fork failed
                        exit(1);
                    } else if ($pid) {
                        // We are the parent
                        echo "Parent Waiting Accept:" . $this->cur_client . "\n";
                        sleep(1); // need to wait rewaiting signal
                        
                    } else {
                        // We are the child
                        // Do something with the inherited connection here
                        // It will get closed upon exit
                        /* Send instructions. */
                        
                        pcntl_signal(SIGINT, array(&$this,
                            "rewaiting"
                        ));
                        $this->task = $this->getNextTask();
                        if ($this->task) {
                            echo "Processing :" . $this->task->tid . "\n";
                            #sleep(3);
                            $eng = new Engine($this->dbaccess, array(
                                $this->task->engine,
                                $this->task->inmime
                            ));
                            if (!$eng->isAffected()) {
                                $eng = $eng->GetNearEngine($this->task->engine, $this->task->inmime);
                            }
                            if ($eng && $eng->isAffected()) {
                                if ($eng->command) {
                                    $TE_HOME = getenv('TE_HOME');
                                    if ($TE_HOME !== false) {
                                        $eng->command = preg_replace('/@TE_HOME@/', $TE_HOME, $eng->command);
                                    }
                                    $orifile = $this->task->infile;
                                    $outfile = tempnam($this->tmppath, "ter-");
                                    if ($outfile !== false) {
                                        unlink($outfile);
                                        $outfile = $outfile . "." . $eng->name;
                                    }
                                    $errfile = $outfile . ".err";
                                    if ((!is_file($outfile)) && (!is_file($errfile))) {
                                        $tc = sprintf("%s %s %s 2>%s", $eng->command, escapeshellarg($orifile) , escapeshellarg($outfile) , escapeshellarg($errfile));
                                        $this->task->log(sprintf(_("execute [%s] command") , $tc));
                                        system($tc, $retval);
                                        if (!file_exists($outfile)) $retval = - 1;
                                        if ($retval != 0) {
                                            //error mode
                                            $err = file_get_contents($errfile);
                                            $this->task->log(str_replace('<', '', $err));
                                            $this->task->status = 'K';
                                        } else {
                                            $warcontent = str_replace('<', '', file_get_contents($errfile));
                                            $this->task->outfile = $outfile;
                                            $this->task->status = 'D';
                                            $this->task->log(sprintf(_("generated by [%s] command") , $eng->command) . "\n$warcontent");
                                        }
                                        
                                        $this->task->modify();
                                        
                                        $callback = $this->task->callback;
                                        if ($callback) {
                                            $turl = parse_url($callback);
                                            if ($this->login) {
                                                $turl["pass"] = $this->password;
                                                $turl["user"] = $this->login;
                                            }
                                            $turl["query"].= "&tid=" . $this->task->tid;
                                            $this->task->log(_("call : ") . $turl["host"] . '://' . $turl["query"]);
                                            $url = $this->implode_url($turl);
                                            $response = @file_get_contents($url);
                                            if ($response === false) {
                                                if (function_exists("error_get_last")) {
                                                    $terr = error_get_last();
                                                    $this->task->callreturn = "ERROR:" . $terr["message"];
                                                } else {
                                                    $this->task->callreturn = "ERROR:$php_errormsg";
                                                }
                                            } else {
                                                
                                                $this->task->callreturn = utf8_encode(str_replace('<', '', $response));
                                            }
                                            $this->task->log(_("return call : ") . $this->task->callreturn);
                                            $this->task->modify();
                                        }
                                    } else {
                                        $this->task->comment = sprintf(_("cannot create out file [%s]") , $outfile);
                                        $this->task->status = 'K'; // KO
                                        $this->task->modify();
                                    }
                                } else {
                                    $this->task->log(_("empty command"));
                                    $this->task->status = 'K'; // KO
                                    $this->task->modify();
                                }
                            } else {
                                $this->task->log(_("no compatible engine found"));
                                $this->task->status = 'K'; // KO
                                $this->task->modify();
                            }
                        }
                        if ($this->status == 'P') {
                            $this->task->log(_("hou hou"));
                            $this->task->status = 'Z'; // KO ??
                            $this->task->modify();
                        }
                        exit(0);
                    }
                } else {
                    sleep(10); // to not load CPU
                    
                }
            }
        }
    }
    /**
     * verify if has a task winting
     * @return bool
     */
    function HasWaitingTask()
    {
        $q = new QueryPg($this->dbaccess, "Task");
        $q->AddQuery("status='W'");
        $q->Query(0, 1);
        if ($q->nb > 0) return true;
        return false;
    }
    /**
     * return next task to process
     * the new status ogf task is 'P' and yje pid is set to current process
     * @return Task
     */
    function getNextTask()
    {
        $wt = new Task($this->dbaccess);
        $wt->exec_query(sprintf("update task set pid=%d, status='P' where tid = (select tid from task where status='W' limit 1)", posix_getpid())); // no need lock table
        $q = new QueryPg($this->dbaccess, "Task");
        $q->AddQuery("status='P'");
        $q->AddQuery("pid=" . posix_getpid());
        $l = $q->Query(0, 1);
        if ($q->nb > 0) return $l[0];
        return false;
    }
    /**
     * rewrite URL from parse_url array
     * @param array $turl the url array
     * @return string
     */
    function implode_url($turl)
    {
        
        if (isset($turl["scheme"])) $url = $turl["scheme"] . "://";
        else $url = "http://";
        if (isset($turl["user"]) && isset($turl["pass"])) $url.= $turl["user"] . ':' . $turl["pass"] . '@';
        if (isset($turl["host"])) $url.= $turl["host"];
        else $url.= "localhost";
        if (isset($turl["port"])) $url.= ':' . $turl["port"];
        if (isset($turl["path"]) && ($turl["path"][0] == '&')) {
            $turl["query"] = $turl["path"] . $turl["query"];
            $turl["path"] = '';
        }
        if (isset($turl["path"])) $url.= $turl["path"];
        if (isset($turl["query"])) $url.= '?' . $turl["query"];
        if (isset($turl["fragment"])) $url.= '#' . $turl["fragment"];
        
        return $url;
    }
    
    function flushProcessingTasks()
    {
        $tasks = new Task($this->dbaccess);
        $tasks->exec_query(sprintf("DELETE FROM task WHERE status = 'P'"));
        return true;
    }
}
?>
