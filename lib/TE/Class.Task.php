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
}
