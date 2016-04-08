<?php
/*
 * @author Anakeen
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
    public $workDir = "/var/tmp";
    public $purge_days = 7;
    public $purge_interval = 100;
    private $purge_count = 0;
    
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
            $this->task->status = Task::STATE_WAITING; // waiting
            $this->task->log('Interrupted');
            $this->task->Modify();
        }
        exit(0);
    }
    
    function childTermReq()
    {
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
        
        $this->setMainSignals();
        
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
                        if ($this->purgeTrigger()) {
                            $this->purgeTasks();
                        }
                        echo "Parent Waiting Accept:" . $this->cur_client . "\n";
                        sleep(1); // need to wait rewaiting signal
                        
                    } else {
                        $this->processOneTask();
                        exit(0);
                    }
                } else {
                    sleep(10); // to not load CPU
                    
                }
            }
        }
    }
    private function setMainSignals()
    {
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
    }
    private function setChildSignals()
    {
        pcntl_signal(SIGCHLD, SIG_DFL);
        pcntl_signal(SIGPIPE, SIG_DFL);
        pcntl_signal(SIGINT, array(&$this,
            "rewaiting"
        ));
        pcntl_signal(SIGTERM, array(&$this,
            "childTermReq"
        ));
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
     * Get a waiting task and process it
     */
    function processOneTask()
    {
        /*
         * Become a session leader so we can kill the whole process group
         * (the renderer + the engine's sub-process) with a single kill.
        */
        posix_setsid();
        try {
            // We are the child
            // Do something with the inherited connection here
            // It will get closed upon exit
            /* Send instructions. */
            $this->setChildSignals();
            $this->task = $this->getNextTask();
            if (!$this->task) {
                /* No tasks to process */
                return;
            }
            echo "Processing :" . $this->task->tid . "\n";
            $eng = new Engine($this->dbaccess, array(
                $this->task->engine,
                $this->task->inmime
            ));
            if (!$eng->isAffected()) {
                $eng = $eng->GetNearEngine($this->task->engine, $this->task->inmime);
            }
            if (!$eng || !$eng->isAffected()) {
                throw new Exception(_("no compatible engine found"));
            }
            if (!$eng->command) {
                throw new Exception(_("empty command"));
            }
            $TE_HOME = getenv('TE_HOME');
            if ($TE_HOME !== false) {
                $eng->command = preg_replace('/@TE_HOME@/', $TE_HOME, $eng->command);
            }
            /*
             * Setup outputfile and errfile in task's work dir
            */
            $orifile = $this->task->infile;
            $taskWorkDir = $this->task->getTaskWorkDir();
            if (!is_dir($taskWorkDir)) {
                throw new Exception(sprintf(_("Invalid task directory from task's input file '%s'.") , $this->task->infile));
            }
            $outfile = tempnam($taskWorkDir, 'ter-');
            if ($outfile === false) {
                throw new Exception(sprintf(_("cannot create out file [%s]") , $outfile));
            }
            unlink($outfile);
            $outfile = $outfile . "." . $eng->name;
            $errfile = $outfile . ".err";
            if (is_file($outfile)) {
                throw new Exception(sprintf(_("output file '%s' already exists.") , $outfile));
            }
            if (is_file($errfile)) {
                throw new Exception(sprintf(_("error file '%s' already exists.") , $errfile));
            }
            $tc = sprintf("%s %s %s > %s 2>&1", $eng->command, escapeshellarg($orifile) , escapeshellarg($outfile) , escapeshellarg($errfile));
            $this->task->log(sprintf(_("execute [%s] command") , $tc));
            /* Save original TMPDIR */
            $TMPDIR = $this->setTmpDir($taskWorkDir);
            system($tc, $retval);
            /* Restore original TMPDIR */
            $this->setTmpDir($TMPDIR);
            if (!file_exists($outfile)) $retval = - 1;
            if ($retval != 0) {
                //error mode
                $err = file_get_contents($errfile);
                throw new Exception($err);
            }
            $warcontent = str_replace('<', '', file_get_contents($errfile));
            $this->task->outfile = $outfile;
            $this->task->status = Task::STATE_SUCCESS;
            $this->task->log(sprintf(_("generated by [%s] command") , $eng->command) . "\n$warcontent");
            $this->task->pid = null;
            $this->task->modify();
        }
        catch(Exception $e) {
            $this->task->log($e->getMessage());
            $this->task->comment = $e->getMessage();
            $this->task->status = Task::STATE_ERROR; // KO
            $this->task->pid = null;
            $this->task->modify();
        }
        $this->task->runCallback();
    }
    
    function flushProcessingTasks()
    {
        $tasks = new Task($this->dbaccess);
        $tasks->exec_query(sprintf("DELETE FROM task WHERE status = 'P'"));
        return true;
    }
    
    public function purgeTrigger()
    {
        if ($this->purge_interval <= 0) {
            return false;
        }
        $this->purge_count++;
        $this->purge_count = $this->purge_count % $this->purge_interval;
        return ($this->purge_count === 0);
    }
    public function purgeTasks()
    {
        $task = new Task($this->dbaccess);
        $task->purgeTasks($this->purge_days);
    }
    public function setTmpDir($tmpDir)
    {
        $TMPDIR = getenv('TMPDIR');
        if ($tmpDir === false) {
            putenv('TMPDIR');
        } else {
            putenv(sprintf('TMPDIR=%s', $tmpDir));
        }
        return $TMPDIR;
    }
}
