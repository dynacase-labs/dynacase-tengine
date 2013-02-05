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
        "comment"
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
    tid SERIAL PRIMARY KEY,
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
            $res = pg_exec($this->init_dbid() , "select nextval ('task_tid_seq')");
            $arr = pg_fetch_array($res, 0);
            $this->tid = $arr[0];
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
}
?>
