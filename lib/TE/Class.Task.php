<?php
/*
 * @author Anakeen
 * @package FDL
*/

require_once "Class.PgObj.php";
require_once "Class.Histo.php";
require_once "Lib.TE.php";

Class Task extends PgObj
{
    const TASK_WORK_DIR_PREFIX = 'te-task-';
    const STATE_BEGINNING = 'B'; // C/S start of transaction
    const STATE_TRANSFERRING = 'T'; // Data (file) transfer is in progress
    const STATE_ERROR = 'K'; // Job ends with error
    const STATE_SUCCESS = 'D'; // Job ends successfully
    const STATE_PROCESSING = 'P'; // Engine is running
    const STATE_WAITING = 'W'; // Job registered, waiting to start engine
    const STATE_INTERRUPTED = 'I'; // Job was interrupted
    public $fields = array(
        "tid",
        "infile",
        "inmime", // mime of infile
        "outfile",
        "engine",
        "status",
        "callback",
        "callreturn",
        "fkey",
        "pid",
        "comment",
        // comment text
        
    );
    public $sup_fields = array(
        "cdate"
    );
    
    public $id_fields = array(
        "tid"
    );
    
    public $dbtable = "task";
    
    public $sqlcreate = <<< SQL
CREATE TABLE task (
    tid TEXT PRIMARY KEY,
    infile TEXT NOT NULL,
    inmime TEXT,
    outfile TEXT,
    engine TEXT NOT NULL,
    status CHAR NOT NULL,
    fkey TEXT,
    callback TEXT,
    callreturn TEXT,
    cdate TIMESTAMP DEFAULT NOW(),
    pid INT,
    comment TEXT
);
SQL;
    public $tid;
    public $infile;
    public $inmime;
    public $outfile;
    /**
     * transformation name
     * @public string
     */
    public $engine;
    public $status;
    public $fkey;
    public $callback;
    public $callreturn;
    public $cdate;
    /**
     * unix process id of processing
     * @public int
     */
    public $pid;
    /**
     * description of the command
     * @public string
     */
    public $comment;
    
    public static function newTaskWorkDir($workDir)
    {
        $taskDir = tempnam($workDir, self::TASK_WORK_DIR_PREFIX);
        if ($taskDir === false) {
            return false;
        }
        unlink($taskDir);
        if (mkdir($taskDir) === false) {
            return false;
        }
        return $taskDir;
    }
    
    function preInsert()
    {
        if (empty($this->tid)) {
            $this->tid = uniqid("", true);
        }
        if (!empty($this->infile)) {
            $this->inmime = te_getSysMimeFile($this->infile);
        }
    }
    
    function preUpdate()
    {
        if (($this->infile != '') && ($this->inmime == '')) {
            $this->inmime = te_getSysMimeFile($this->infile);
        }
    }
    
    function log($s)
    {
        static $oh = false;
        
        if (!$oh) $oh = new Histo($this->dbaccess);
        if ($s) {
            $this->comment = $s;
            $oh->comment = $s;
            $oh->tid = $this->tid;
            $oh->Add();
        }
    }
    
    function preDelete()
    {
        self::deleteTaskWorkDir($this->getTaskWorkDir());
        return '';
    }
    /**
     * @param $args array() request arguments (ex.: array("orderby" => "column1", "sort" => "desc", "start" => 20, "length" => 10))
     * @return array|bool|resource|string
     */
    public function getTasks($args)
    {
        include_once ("Class.QueryPg.php");
        $response = array(
            'count_all' => 0,
            'count_filter' => 0,
            'tasks' => array()
        );
        $fields = array_merge($this->fields, $this->sup_fields);
        $q = new QueryPg($this->dbaccess, $this->dbtable);
        /* ORDERBY */
        if (isset($args['orderby']) && is_scalar($args['orderby']) && in_array($args['orderby'], $fields)) {
            $orderBy = pg_escape_string($args['orderby']);
            /* SORT */
            if (isset($args['sort']) && ($args['sort'] == 'asc' || $args['sort'] == 'desc')) {
                $orderBy.= ' ' . $args['sort'];
            }
            $q->order_by = $orderBy;
        }
        /* START */
        $start = 0;
        if (isset($args['start']) && is_numeric($args['start'])) {
            $start = $args['start'];
        }
        /* LENGTH */
        $length = 0;
        if (isset($args['length']) && is_numeric($args['length'])) {
            $length = $args['length'];
        }
        
        $q->AddQuery("true");
        $response['count_all'] = $q->Count();
        /* FILTER */
        $qFilter = "";
        if (!empty($args['filter']) && is_array($args['filter'])) {
            foreach ($args['filter'] as $column => $value) {
                if (in_array($column, $fields)) {
                    $qFilter.= ($qFilter != "" ? " OR " : "");
                    $qFilter.= sprintf("(%s::text ~* '%s')", $column, pg_escape_string($value));
                }
            }
            $q->AddQuery($qFilter);
        }
        $response['count_filter'] = $q->Count();
        $response['tasks'] = $q->Query($start, $length, "TABLE");
        return $response;
    }
    public function getStatusBreakdown()
    {
        include_once ("Class.QueryPg.php");
        $q = new QueryPg($this->dbaccess, $this->dbtable);
        $sql = 'SELECT status, count(status) FROM task GROUP BY status ORDER BY status';
        $res = $q->Query(0, 0, "TABLE", $sql);
        if (!is_array($res)) {
            return array();
        }
        $statusBreakdown = array();
        foreach ($res as $tuple) {
            $statusBreakdown[$tuple['status']] = $tuple['count'];
        }
        return $statusBreakdown;
    }
    /**
     * Delete tasks older than $maxDays days
     * @param int|float $maxDays task's max age (in days)
     * @param string $status delete tasks with given statuses (ex. "D", "DKW", etc.)
     * @param string $tid delete task with given identifier
     * @return bool
     */
    public function purgeTasks($maxDays = 0, $status = '')
    {
        include_once ("Class.QueryPg.php");
        $q = new QueryPg($this->dbaccess, $this->dbtable);
        $cond = array();
        if ($maxDays > 0) {
            $cond[] = sprintf("cdate < now() - INTERVAL '%f days'", pg_escape_string($maxDays));
        }
        if ($status != '') {
            $in = array();
            for ($i = 0; $i < strlen($status); $i++) {
                $in[] = sprintf("'%s'", pg_escape_string($status[$i]));
            }
            if (count($in) > 0) {
                $cond[] = sprintf("status IN (%s)", join(', ', $in));
            }
        }
        $where = '';
        if (count($cond) > 0) {
            $where = sprintf("WHERE %s", join(' AND ', $cond));
        }
        /*
         * Lock task table in exclusive mode to prevent te_rendering_server
         * from spawning new renderers during purge.
        */
        $q->Query(0, 0, "TABLE", "BEGIN; LOCK TABLE task IN ACCESS EXCLUSIVE MODE;");
        $sql = sprintf("DELETE FROM task %s RETURNING infile, outfile, status, pid", $where);
        $tasks = $q->Query(0, 0, "TABLE", $sql);
        if (!is_array($tasks)) {
            $tasks = array();
        }
        foreach ($tasks as $task) {
            if ($task['status'] == 'P' && ((int)$task['pid'] > 0)) {
                /* Kill process group */
                posix_kill(-$task['pid'], SIGKILL);
            }
            if (file_exists($task['infile'])) {
                self::deleteTaskWorkDir(dirname($task['infile']));
            }
            if (file_exists($task['outfile'])) {
                self::deleteTaskWorkDir(dirname($task['outfile']));
            }
        }
        $q->Query(0, 0, "TABLE", "COMMIT;");
        $histo = new Histo($this->dbaccess);
        $histo->purgeUnreferencedLog();
        return true;
    }
    /**
     * Interrupt a task which is not in a final state (i.e. status not in {K, D, I})
     *
     * If pid is set, the the corresponding process is killed with a SIGKILL, and
     * the task's work dir is removed.
     */
    public function interrupt()
    {
        switch ($this->status) {
            case self::STATE_BEGINNING:
            case self::STATE_TRANSFERRING:
            case self::STATE_WAITING:
            case self::STATE_PROCESSING:
                /* Interrupt only work with these states */
                break;

            default:
                /* Other states are not subject to interruption */
                return;
        }
        if ((int)$this->pid > 0) {
            /* Kill process group */
            posix_kill(-$this->pid, SIGKILL);
        }
        $this->pid = '';
        $this->status = Task::STATE_INTERRUPTED;
        $this->log(sprintf("Abort requested"));
        $this->Modify();
        $this->runCallback();
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
    /**
     * Run the callback if a callback is declared.
     * Return successfully if no callback is declared.
     * @return string error message on failure or empty string on success
     */
    public function runCallback()
    {
        $callback = $this->callback;
        if (!$callback) {
            return '';
        }
        $turl = parse_url($callback);
        $turl["query"].= "&tid=" . $this->tid;
        $this->log(_("call : ") . $turl["host"] . '://' . $turl["query"]);
        $url = $this->implode_url($turl);
        $response = @file_get_contents($url);
        if ($response === false) {
            if (function_exists("error_get_last")) {
                $terr = error_get_last();
                $this->callreturn = "ERROR:" . $terr["message"];
            } else {
                $this->callreturn = "ERROR:$php_errormsg";
            }
        } else {
            
            $this->callreturn = str_replace('<', '', $response);
        }
        $this->log(_("return call : ") . $this->callreturn);
        $this->Modify();
        /*
         * Return error message or empty string on success
        */
        if ($response === false) {
            return $this->callreturn;
        }
        return '';
    }
    /**
     * Recursively delete a directory or a file
     * @param $path
     * @return bool true on success or false if an error occurred (i.e. a file/dir could not be remove)
     */
    public static function rm_rf($path)
    {
        $filetype = filetype($path);
        if ($filetype === false) {
            return false;
        }
        if ($filetype == 'dir') {
            /* Recursively delete content */
            $ret = true;
            foreach (scandir($path) as $file) {
                if ($file == "." || $file == "..") {
                    continue;
                };
                $ret = ($ret && self::rm_rf(sprintf("%s%s%s", $path, DIRECTORY_SEPARATOR, $file)));
            }
            /* The main directory should now be empty, so we can delete it. */
            $ret = ($ret && rmdir($path));
            if ($ret === false) {
                return false;
            }
        } else {
            /* Delete a single file */
            $ret = unlink($path);
        }
        return $ret;
    }
    /**
     * Get the task's work dir from the 'infile' filename.
     * @return bool|string false if infile is empty
     */
    public function getTaskWorkDir()
    {
        if ($this->infile == '') {
            return false;
        }
        $taskWorkDir = dirname($this->infile);
        if (!self::isATaskWorkDir($taskWorkDir)) {
            return false;
        }
        
        return $taskWorkDir;
    }
    /**
     * Recursively delete the given task's work dir if
     * it is a valid task's work dir.
     * @param string $taskWorkDir Path to task's work dir
     */
    protected static function deleteTaskWorkDir($taskWorkDir)
    {
        if (!is_string($taskWorkDir)) {
            return;
        }
        if (self::isATaskWorkDir($taskWorkDir)) {
            self::rm_rf($taskWorkDir);
        }
    }
    /**
     * Check if a given directory has the task's work dir prefix and is
     * hence a valid task's work dir.
     * @param $path
     * @return int
     */
    protected static function isATaskWorkDir($path)
    {
        return (preg_match(sprintf('/^%s/', preg_quote(self::TASK_WORK_DIR_PREFIX, '/')) , basename($path)) === 1);
    }
}
