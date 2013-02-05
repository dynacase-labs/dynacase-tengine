<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
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
    tid INT NOT NULL,
    date TIMESTAMP DEFAULT now(),
    comment TEXT
);
SQL;
    
}
?>
