<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/

require_once "Class.PgObj.php";
require_once "Class.Histo.php";
require_once "Lib.TE.php";

Class Task extends PgObj
{
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
        if (file_exists($this->infile)) {
            unlink($this->infile);
        }
        if (file_exists($this->outfile)) {
            unlink($this->outfile);
        };
        if (file_exists($this->outfile . '.err')) {
            unlink($this->outfile . '.err');
        }
        return '';
    }
    /**
     * @param $args array() request arguments (ex.: array("orderby" => "column1", "sort" => "desc", "start" => 20, "length" => 10))
     * @return array|bool|resource|string
     */
    public function getTasks($args)
    {
        include_once ("Class.QueryPg.php");
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
        /* FILTER */
        if (isset($args['filter']) && is_array($args['filter'])) {
            foreach ($args['filter'] as $column => $value) {
                if (!in_array($column, $fields)) {
                    return array();
                }
                $condition = sprintf("%s::text ~* '%s'", $column, pg_escape_string($value));
                $q->AddQuery($condition);
            }
        } else {
            $q->AddQuery("true");
        }
        return $q->Query($start, $length, "TABLE");
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
        $q->Query(0, 0, "TABLE", "BEGIN; LOCK TABLE task IN ACCESS EXCLUSIVE MODE NOWAIT;");
        $sql = sprintf("DELETE FROM task %s RETURNING infile, outfile, status, pid", $where);
        $tasks = $q->Query(0, 0, "TABLE", $sql);
        if (!is_array($tasks)) {
            $tasks = array();
        }
        foreach ($tasks as $task) {
            if ($task['status'] == 'P' && ((int)$task['pid'] > 0)) {
                posix_kill($task['pid'], SIGTERM);
            }
            if (file_exists($task['infile'])) {
                unlink($task['infile']);
            }
            if (file_exists($task['outfile'])) {
                unlink($task['outfile']);
            }
            if (file_exists($task['outfile'] . '.err')) {
                unlink($task['outfile'] . '.err');
            }
        }
        $q->Query(0, 0, "TABLE", "COMMIT;");
        $histo = new Histo($this->dbaccess);
        $histo->purgeUnreferencedLog();
        return true;
    }
}
