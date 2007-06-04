<?php
/**
 * Tranformation Engine Definition
 *
 * @author Anakeen 2005
 * @version $Id: Class.Histo.php,v 1.1 2007/06/04 16:23:26 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package FREEDOM-TE
 */
/**
 */


include_once("Class.PgObj.php");
Class Histo extends PgObj {
  public $fields = array ( "tid", 
			   "comment"// comment text
			   );
  public $sup_fields = array ("date");
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
  


  public $id_fields = array ("tid","date");

  public $dbtable = "histo";


  public $sqlcreate = "
create table histo ( tid int not null,   
                   date timestamp default now(),
                   comment text  );";


}
?>