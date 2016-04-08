<?php
/*
 * @author Anakeen
 * @package FDL
*/

require_once "Class.PgObj.php";

Class Histo extends PgObj
{
    public $fields = array(
        "tid",
        "comment"
        // comment text
        
    );
    public $sup_fields = array(
        "date"
    );
    /**
     * task identificator
     * @public string
     */
    public $tid;
    /**
     * description of the action
     * @public string
     */
    public $comment;
    
    public $id_fields = array(
        "tid",
        "date"
    );
    
    public $dbtable = "histo";
    
    public $sqlcreate = <<< 'SQL'
CREATE TABLE histo (
    tid TEXT NOT NULL,
    date TIMESTAMP DEFAULT now(),
    comment TEXT
);
SQL;
    
    public function getTaskHisto($tid)
    {
        include_once ("Class.QueryPg.php");
        $q = new QueryPg($this->dbaccess, $this->dbtable);
        $q->order_by = "date ASC";
        $q->addQuery(sprintf("tid = '%s'", pg_escape_string($tid)));
        $q->AddQuery("true");
        return $q->Query(0, 0, "TABLE");
    }
    /**
     * Delete histo entries of tasks that do not exists anymore
     * @return bool
     */
    public function purgeUnreferencedLog()
    {
        include_once ("Class.QueryPg.php");
        $q = new QueryPg($this->dbaccess, $this->dbtable);
        $sql = sprintf("DELETE FROM histo WHERE NOT EXISTS (SELECT 1 FROM task WHERE task.tid = histo.tid LIMIT 1)");
        $q->Query(0, 0, "TABLE", $sql);
        return true;
    }
}
