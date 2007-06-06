<?php
/**
 * Task for Engine 
 *
 * @author Anakeen 2007
 * @version $Id: Class.Task.php,v 1.4 2007/06/06 18:12:01 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package TE
 */
/**
 */


include_once("Class.PgObj.php");
include_once("Class.Histo.php");
include_once("Lib.TEUtil.php");
Class Task extends PgObj {
  public $fields = array ( "tid",
                           "infile",
                           "inmime", // mime of infile
			   "outfile",
			   "engine",
			   "status",
			   "callback",
			   "callreturn",
			   "fkey",
			   "comment"// comment text
			   );
  public $sup_fields = array ("cdate");
  /**
   * transformation name
   * @public string
   */
  public $engine;  
  		  
  /**
   * description of the command
   * @public string
   */
  public $comment;
  


  public $id_fields = array ("tid");

  public $dbtable = "task";


  public $sqlcreate = "
create table task ( tid serial primary key,   
                    infile text not null, 
                    inmime text, 
                   outfile text,
                   engine text not null,
                   status char not null,
                   fkey text,
                   callback text,
                   callreturn text,
                   cdate timestamp default now(),
                   comment text  );
";

  function preInsert() {
    if ($this->tid == '') {
      $res = pg_exec($this->init_dbid(), "select nextval ('task_tid_seq')");
      $arr = pg_fetch_array ($res, 0);
      $this->tid = $arr[0];
    }
    if ($this->infile != '') {
      $this->inmime=getSysMimeFile($this->infile);
    }
  }

  function preUpdate() {
    if (($this->infile != '') &&  ($this->inmime == '')) $this->inmime=getSysMimeFile($this->infile);
  }

  function log($s) {
    static $oh=false;

    if (!$oh) $oh=new Histo($this->dbaccess);
    if ($s) {
      $this->comment=$s;
      $oh->comment=$s;
      $oh->tid=$this->tid;
      $oh->Add();      
    }
  }
}
?>