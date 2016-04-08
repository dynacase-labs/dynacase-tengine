<?php
/*
 * @author Anakeen
 * @package FDL
 */
/**
 * This class is a generic DB Class that can be used to create objects
 * based on the description of a DB Table. More Complex Objects will
 * inherit from this basic Class.
 *
 */
Class PgObj
{
    /**
     * the database connection resource
     * @public resource
     */
    public $dbid = - 1;
    /**
     * coordinates to access to database
     * @public string
     */
    public $dbaccess = '';
    /**
     * array of SQL fields use for the object
     * @public array
     */
    public $fields = array(
        '*'
    );
    
    public $id_fields = array();
    /**
     * name of the SQL table
     * @public string
     */
    public $dbtable = '';
    
    public $criterias = array();
    /**
     * array of other SQL fields, not in attribute of object
     * @public array
     */
    public $sup_fields = array();
    public $sup_where = array();
    public $sup_tables = array();
    public $fulltextfields = array();
    /**
     * sql field to order
     * @public string
     */
    public $order_by = "";
    /**
     * indicates if fields has been affected
     * @public string
     * @see Affect()
     */
    public $isset = false; // indicate if fields has been affected (call affect methods)
    public $sqlcreate;
    public $sqlinit;
    /**
     * @var resource
     */
    public $res = '';
    protected $msg_err = '';
    //----------------------------------------------------------------------------
    
    /**
     * Database Object constructor
     *
     * @param string $dbaccess database specification
     * @param int|string $id identificator of the object
     * @param array|string $res array of result issue to QueryDb {@link QueryDb::Query()}
     * @param int|resource $dbid the database connection resource
     * @return \PgObj false if error occured
     */
    function __construct($dbaccess = '', $id = '', $res = '', $dbid = 0)
    {
        
        $this->dbaccess = $dbaccess;
        $this->init_dbid();
        
        if ($this->dbid == 0) {
            $this->dbid = - 1;
        }
        
        $this->selectstring = "";
        // SELECTED FIELDS
        reset($this->fields);
        while (list($k, $v) = each($this->fields)) {
            $this->selectstring = $this->selectstring . $this->dbtable . "." . $v . ",";
            $this->$v = "";
        }
        
        reset($this->sup_fields);
        while (list($k, $v) = each($this->sup_fields)) {
            $this->selectstring = $this->selectstring . "" . $v . ",";
            $this->$v = "";
        }
        $this->selectstring = substr($this->selectstring, 0, strlen($this->selectstring) - 1);
        // select with the id
        if (($id != '') || (is_array($id)) || (!isset($this->id_fields[0]))) {
            $ret = $this->Select($id);
            
            return ($ret);
        }
        // affect with a query result
        if (is_array($res)) {
            $this->Affect($res);
        }
        
        return true;
    }
    
    function Select($id)
    {
        if ($this->dbid == - 1) return FALSE;
        
        $msg = $this->PreSelect($id);
        if ($msg != '') return $msg;
        
        if ($this->dbtable == '') {
            return ("error : No Tables");
        }
        $fromstr = "{$this->dbtable}";
        if (is_array($this->sup_tables)) {
            reset($this->sup_tables);
            while (list($k, $v) = each($this->sup_tables)) {
                $fromstr.= "," . $v;
            }
        }
        $sql = "select {$this->selectstring} from {$fromstr} ";
        
        $count = 0;
        if (is_array($id)) {
            $count = 0;
            $wherestr = " where ";
            reset($this->id_fields);
            while (list($k, $v) = each($this->id_fields)) {
                if ($count > 0) {
                    $wherestr = $wherestr . " AND ";
                }
                $wherestr = $wherestr . "( " . $this->dbtable . "." . $v . "='" . pg_escape_string($id[$k]) . "' )";
                $count = $count + 1;
                //$this->$v = $id[$k];
                
            }
        } else {
            if (isset($this->id_fields[0])) {
                $wherestr = "where " . $this->dbtable . "." . $this->id_fields[0] . "='" . pg_escape_string($id) . "'";
            } else {
                $wherestr = "";
            }
        }
        if (is_array($this->sup_where)) {
            reset($this->sup_where);
            while (list($k, $v) = each($this->sup_where)) {
                $wherestr = $wherestr . " AND ";
                $wherestr = $wherestr . "( " . $v . " )";
                $count = $count + 1;
            }
        }
        
        $sql = $sql . " " . $wherestr;
        
        $this->exec_query($sql);
        
        if ($this->numrows() > 0) {
            $res = $this->fetch_array(0);
            $this->Affect($res);
        } else {
            return false;
        }
        $msg = $this->PostSelect($id);
        if ($msg != '') return $msg;
        return true;
    }
    
    function Affect($array)
    {
        reset($array);
        while (list($k, $v) = each($array)) {
            if (!is_integer($k)) {
                $this->$k = $v;
            }
        }
        $this->Complete();
        $this->isset = true;
    }
    /**
     * verify that the object exists
     *
     * if true values of the object has been set
     * @return bool
     */
    function isAffected()
    {
        return $this->isset;
    }
    
    function Complete()
    {
        // This function should be replaced by the Child Class
        return '';
    }
    /**
     * Method use before Add method
     * This method should be replaced by the Child Class
     *
     * @return string error message, if no error empty string
     * @see Add()
     */
    function PreInsert()
    {
        // This function should be replaced by the Child Class
        return '';
    }
    /**
     * Method use after Add method
     * This method should be replaced by the Child Class
     *
     * @return string error message, if no error empty string, if message
     * error not empty the Add method is not completed
     * @see Add()
     */
    function PostInsert()
    {
        // This function should be replaced by the Child Class
        return '';
    }
    /**
     * Method use before Modify method
     * This method should be replaced by the Child Class
     *
     * @return string error message, if no error empty string
     * @see Modify()
     */
    function PreUpdate()
    {
        // This function should be replaced by the Child Class
        return '';
    }
    /**
     * Method use after Modify method
     * This method should be replaced by the Child Class
     *
     * @return string error message, if no error empty string, if message
     * error not empty the Modify method is not completed
     * @see Modify()
     */
    function PostUpdate()
    {
        // This function should be replaced by the Child Class
        return '';
    }
    
    function PreDelete()
    {
        // This function should be replaced by the Child Class
        return '';
    }
    
    function PostDelete()
    {
        // This function should be replaced by the Child Class
        return '';
    }
    
    function PreSelect($id)
    {
        // This function should be replaced by the Child Class
        return '';
    }
    
    function PostSelect($id)
    {
        // This function should be replaced by the Child Class
        return '';
    }
    
    function PostInit()
    {
        // This function should be replaced by the Child Class
        return '';
    }
    /**
     * Add the object to the database
     * @param bool $nopost PostInsert method not apply if true
     * @return string error message, if no error empty string
     * @see PreInsert()
     * @see PostInsert()
     */
    function Add($nopost = false)
    {
        if ($this->dbid == - 1) return false;
        
        $msg = $this->PreInsert();
        if ($msg) {
            return $msg;
        }
        
        $sfields = implode(",", $this->fields);
        $sql = "insert into " . $this->dbtable . "($sfields) values (";
        
        $valstring = "";
        reset($this->fields);
        while (list($k, $v) = each($this->fields)) {
            $valstring = $valstring . $this->lw($this->$v) . ",";
        }
        $valstring = substr($valstring, 0, strlen($valstring) - 1);
        $sql = $sql . $valstring . ")";
        // requery execution
        $msg = $this->exec_query($sql);
        
        if ($msg) {
            return $msg;
        }
        
        $this->isset = true;
        if (!$nopost) {
            $msg = $this->PostInsert();
        }
        
        return $msg;
    }
    /**
     * update the object in database
     * @param bool $nopost PostUpdate() and method not apply if true
     * @param string $sfields only this column will ne updated if empty all fields
     * @param bool $nopre PreUpdate() method not apply if true
     * @return string error message, if no error empty string
     * @see PreUpdate()
     * @see PostUpdate()
     */
    function Modify($nopost = false, $sfields = "", $nopre = false)
    {
        if ($this->dbid == - 1) {
            return false;
        }
        
        if (!$nopre) {
            $msg = $this->PreUpdate();
            if ($msg) {
                return $msg;
            }
        }
        
        $sql = "update " . $this->dbtable . " set ";
        
        $nb_keys = 0;
        foreach ($this->id_fields as $v) {
            $notset[$v] = "Y";
            $nb_keys++;
        }
        
        if (!is_array($sfields)) {
            $fields = $this->fields;
        } else {
            $fields = $sfields;
            foreach ($this->id_fields as $v) {
                $fields[] = $v;
            }
        }
        
        $setstr = "";
        $wstr = "";
        foreach ($fields as $v) {
            if (!isset($notset[$v])) {
                $setstr = $setstr . " " . $v . "=" . $this->lw($this->$v) . ",";
            } else {
                $val = pg_escape_string($this->$v);
                $wstr = $wstr . " " . $v . "='" . $val . "' AND";
            }
        }
        $setstr = substr($setstr, 0, strlen($setstr) - 1);
        $wstr = substr($wstr, 0, strlen($wstr) - 3);
        $sql.= $setstr;
        if ($nb_keys > 0) {
            $sql.= " where " . $wstr . ";";
        }
        
        $msg = $this->exec_query($sql);
        if ($msg) {
            return $msg;
        }
        
        if (!$nopost) {
            $msg = $this->PostUpdate();
        }
        
        return $msg;
    }
    
    function Delete($nopost = false)
    {
        $msg = $this->PreDelete();
        if ($msg) {
            return $msg;
        }
        $wherestr = "";
        $count = 0;
        
        reset($this->id_fields);
        while (list($k, $v) = each($this->id_fields)) {
            if ($count > 0) {
                $wherestr = $wherestr . " AND ";
            }
            $wherestr = $wherestr . "( " . $v . "='" . AddSlashes($this->$v) . "' )";
            $count++;
        }
        // suppression de l'enregistrement
        $sql = "delete from " . $this->dbtable . " where " . $wherestr . ";";
        
        $msg = $this->exec_query($sql);
        if ($msg) {
            return $msg;
        }
        
        if (!$nopost) {
            $msg = $this->PostDelete();
        }
        
        return $msg;
    }
    /**
     * Add several objects to the database
     * no post neither preInsert are called
     * @param $tcopy
     * @param bool $nopost PostInsert method not apply if true
     * @return string error message, if no error empty string
     * @see PreInsert()
     * @see PostInsert()
     */
    function Adds(&$tcopy, $nopost = false)
    {
        $msg = '';
        
        if ($this->dbid == - 1) {
            return false;
        }
        if (!is_array($tcopy)) {
            return false;
        }
        
        $trow = array();
        foreach ($tcopy as $kc => $vc) {
            $row = "";
            foreach ($this->fields as $field) {
                if (isset($vc[$field])) {
                    $row.= $vc[$field];
                } elseif ($this->$field != '') {
                    $row.= $this->$field;
                }
                $row.= "\t";
            }
            $trow[$kc] = substr($row, 0, -1);
        }
        // query execution
        if (pg_copy_from($this->dbid, $this->dbtable, $trow, "\t")) {
            return sprintf(_("Pgobj::Adds error in multiple insertion"));
        }
        
        if (!$nopost) {
            $msg = $this->PostInsert();
        }
        
        return $msg;
    }
    
    function lw($prop)
    {
        $result = ($prop == '' ? "null" : "'" . pg_escape_string($prop) . "'");
        return $result;
    }
    
    function CloseConnect()
    {
        return pg_close($this->dbid);
    }
    
    function Create($nopost = false)
    {
        $msg = "";
        
        if (isset($this->sqlcreate)) {
            // step by step
            if (is_array($this->sqlcreate)) {
                while (list($k, $sqlquery) = each($this->sqlcreate)) {
                    $msg.= $this->exec_query($sqlquery, 1);
                }
            } else {
                $sqlcmds = explode(";", $this->sqlcreate);
                while (list($k, $sqlquery) = each($sqlcmds)) {
                    $msg.= $this->exec_query($sqlquery, 1);
                }
            }
        }
        if (isset($this->sqlinit)) {
            $msg = $this->exec_query($this->sqlinit, 1);
        }
        if ($msg) {
            return $msg;
        }
        
        if (!$nopost) {
            $msg = $this->PostInit();
        }
        
        return ($msg);
    }
    
    static function close_my_pg_connections()
    {
        global $_DBID;
        
        $pid = getmypid();
        
        if (!isset($_DBID[$pid])) {
            return;
        }
        foreach ($_DBID[$pid] as $conn) {
            @pg_close($conn);
        }
        unset($_DBID[$pid]);
    }
    
    function init_dbid()
    {
        global $_DBID;
        
        $pid = getmypid();
        
        if (isset($_DBID[$pid]) && isset($_DBID[$pid][$this->dbaccess]) && is_resource($_DBID[$pid][$this->dbaccess])) {
            $status = pg_connection_status($_DBID[$pid][$this->dbaccess]);
            if ($status !== PGSQL_CONNECTION_OK) {
                pg_connection_reset($_DBID[$pid][$this->dbaccess]);
            }
        } else {
            $_DBID[$pid][$this->dbaccess] = pg_connect($this->dbaccess, PGSQL_CONNECT_FORCE_NEW);
        }
        $this->dbid = $_DBID[$pid][$this->dbaccess];
        
        return $this->dbid;
    }
    
    function exec_query($sql, $lvl = 0)
    {
        global $SQLDELAY, $SQLDEBUG;
        
        if (!$sql) {
            return '';
        }
        
        if ($SQLDEBUG) {
            $sqlt1 = microtime();
        }
        
        $this->init_dbid();
        
        $this->res = @pg_query($this->dbid, $sql);
        
        $pgmess = pg_last_error($this->dbid);
        
        $this->msg_err = chop(preg_replace("/ERROR:  /", "", $pgmess));
        // Use Postgresql error codes instead of localized text messages
        $action_needed = "";
        if ($lvl == 0) { // to avoid recursivity
            if ($this->msg_err != "") {
                if ((preg_match("/Relation ['\"]([a-zA-Z_]*)['\"] does not exist/i", $this->msg_err) || preg_match("/Relation (.*) n'existe pas/i", $this->msg_err) || preg_match("/class \"([a-zA-Z_]*)\" not found/i", $this->msg_err))) {
                    $action_needed = "create";
                } else if ((preg_match("/No such attribute or function '([a-zA-Z_0-9]*)'/i", $this->msg_err)) || (preg_match("/Attribute ['\"]([a-zA-Z_0-9]*)['\"] not found/i", $this->msg_err))) {
                    $action_needed = "update";
                } else if (preg_match("/relation ['\"](.*)['\"] already exists/i", $this->msg_err) || preg_match("/relation (.*) existe d/i", $this->msg_err)) {
                    $action_needed = "none";
                }
            }
        }
        
        switch ($action_needed) {
            case "create":
                $st = $this->Create();
                if ($st == "") {
                    $this->msg_err = $this->exec_query($sql);
                } else {
                    return "Table {$this->dbtable} doesn't exist and can't be created";
                }
                break;

            case "update":
                return "Table {$this->dbtable} cannot be updated";
                break;

            case "none":
                $this->msg_err = "";
                break;

            default:
                break;
        }
        
        if ($SQLDEBUG) {
            global $TSQLDELAY;
            /** @noinspection PhpUndefinedVariableInspection */
            $SQLDELAY+= te_microtime_diff(microtime() , $sqlt1); // to test delay of request
            $TSQLDELAY[] = array(
                "t" => sprintf("%.04f", te_microtime_diff(microtime() , $sqlt1)) ,
                "s" => str_replace("from", "<br/>from", $sql)
            );
        }
        return ($this->msg_err);
    }
    
    function numrows()
    {
        if ($this->msg_err == "") {
            return (pg_num_rows($this->res));
        } else {
            return (0);
        }
    }
    
    function fetch_array($c, $type = PGSQL_ASSOC)
    {
        return (pg_fetch_array($this->res, $c, $type));
    }
}
?>
