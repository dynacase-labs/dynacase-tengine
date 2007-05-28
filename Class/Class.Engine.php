<?php
/**
 * Tranformation Engine Definition
 *
 * @author Anakeen 2005
 * @version $Id: Class.Engine.php,v 1.1 2007/05/28 14:45:42 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM-TE
 */
/**
 */


include_once("Class.DbObj.php");
Class Engine extends DbObj {
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
                   comment text  );
";

  function getNearEngine($engine,$mime) {

      if (! $this->isAffected()) {
	$mime=strtok($mime,";");
	$eng=new Engine($this->dbaccess,array($engine,$mime));
      }
      if (! $eng->isAffected()) {
	$mime=strtok($mime,"/");
	$eng=new Engine($this->dbaccess,array($engine,$mime));
      }
      if (! $eng->isAffected()) {
	$eng=new Engine($this->dbaccess,array($engine,$mime.'/*'));
      }
      if (! $eng->isAffected()) {
	$eng=new Engine($this->dbaccess,array($engine,'*'));
      }
      if ( $eng->isAffected()) return $eng;
      return false;
  }
}
?>