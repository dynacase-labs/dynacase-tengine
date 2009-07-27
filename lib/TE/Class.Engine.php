<?php
/**
 * Tranformation Engine Definition
 *
 * @author Anakeen 2005
 * @version $Id: Class.Engine.php,v 1.6 2007/06/18 12:27:44 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package TE
 */
/**
 */


include_once("Class.PgObj.php");
Class Engine extends PgObj {
  public $fields = array ( "name", 
                           "mime",
			   "command",
			   "comment"// comment text
			   );

  /**
   * transformation name
   * @public string
   */
  public $name;  
  /**
   * compatible system mime type with the command (like text/html)
   * @public string
   */
  public $mime;  		  
  /**
   * complete path of the program to use for transformation
   * @public string
   */
  public $command;		  
  /**
   * description of the command
   * @public string
   */
  public $comment;
  


  public $id_fields = array ("name","mime");

  public $dbtable = "engine";


  public $sqlcreate = "
create table engine ( name text not null,   
                   mime text not null, 
                   command text not null,
                   comment text ,
                   constraint engine_pkey primary key(name,mime))";

  function getNearEngine($engine,$mime) {
      if (! $this->isAffected()) {
	$eng=new Engine($this->dbaccess,array($engine,$mime));
      }
      if (! $this->isAffected()) {
	$mime=strtok($mime,";");
	$eng=new Engine($this->dbaccess,array($engine,($mime)));
      }
      if (! $eng->isAffected()) {
	$mime=strtok($mime,"/");
	$eng=new Engine($this->dbaccess,array($engine,($mime)));
      }
      if (! $eng->isAffected()) {
	$eng=new Engine($this->dbaccess,array($engine,($mime).'/*'));
      }
      if (! $eng->isAffected()) {
	$eng=new Engine($this->dbaccess,array($engine,'*'));
      }
      if ( $eng->isAffected()) return $eng;
      return false;
  }  
  function existsEngine($engine) {
    include_once("Class.QueryPg.php");
    $q=new QueryPg($this->dbaccess,"Engine");
    $q->AddQuery("name='".pg_escape_string($engine)."'");
    return ($q->Count() > 0);
  }
		 
}
?>