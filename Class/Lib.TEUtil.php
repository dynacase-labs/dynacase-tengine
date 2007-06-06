<?php
/**
 * Common util functions
 *
 * @author Anakeen 200T
 * @version $Id: Lib.TEUtil.php,v 1.5 2007/06/06 18:12:01 eric Exp $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package TE
 */
/**
 */

function microtime_diff($a,$b) {
    list($a_micro, $a_int)=explode(' ',$a);
     list($b_micro, $b_int)=explode(' ',$b);
     if ($a_int>$b_int) {
        return ($a_int-$b_int)+($a_micro-$b_micro);
     } elseif ($a_int==$b_int) {
        if ($a_micro>$b_micro) {
          return ($a_int-$b_int)+($a_micro-$b_micro);
        } elseif ($a_micro<$b_micro) {
           return ($b_int-$a_int)+($b_micro-$a_micro);
        } else {
          return 0;
        }
     } else { // $a_int<$b_int
        return ($b_int-$a_int)+($b_micro-$a_micro);
     }
}
/**
 * If the argument is of the form %Gâ%@NAME=VALUE it will be represented in the array as an element with the key NAME and the value VALUE. I the argument is a flag of the form -NAME it will be represented as a boolean with the name NAME with a value of true in the associative array.
 * @param string argv global argv 
 * @return array
 */
function getArgv($argv) {
    $_ARG = array();
    foreach ($argv as $arg) {
      if (ereg('--([^=]+)=(.*)',$arg,$reg)) {
	    $_ARG[$reg[1]] = $reg[2];
      } elseif(ereg('-([a-zA-Z0-9])',$arg,$reg)) {
            $_ARG[$reg[1]] = 'true';
        }
   
    }
    return $_ARG;
}
/**
 * return system file mime
 * @param string $f filename
 * @param string $fn basename of file (can be different of real path)
 * return string mime like text/html
 */
function getSysMimeFile($f,$fn="") {
  if (! file_exists($f)) return false;
  $sys = trim(`file -bi "$f"`);
  $txt=getTextMimeFile($f);
  error_log("MIME:.file -bi [$f] [$sys]");
  // correct errors of file function
  if (preg_match('/Makefile/',$fn)) return 'text/x-makefile';
  if (preg_match('/ASCII C\+\+/',$txt)) {
     if (preg_match('/\.h$/',$fn))    return 'text/x-c-header';
     return 'text/x-c++';
  } 
  if (preg_match('/PHP script/',$txt))  return 'text/x-php';
  
  // open office archive
  if (preg_match('/zip$/',$sys)) {
  
    if (preg_match('/OpenOffice/',$txt)) {
      if (preg_match('/\.sxw$/',$fn))    return 'application/vnd.sun.xml.writer';
      if (preg_match('/\.sxc$/',$fn))    return 'application/vnd.sun.xml.calc';
      if (preg_match('/\.sxi$/',$fn))    return 'application/vnd.sun.xml.impress';
      if (preg_match('/\.sxd$/',$fn))    return 'application/vnd.sun.xml.draw';
      if (preg_match('/\.sxg$/',$fn))    return 'application/vnd.sun.xml.writer.global';
      return 'application/vnd.sun.xml.writer';
      
    }
    if (preg_match('/OpenDocument/',$txt)) {
      if (preg_match('/\.odp$/',$fn))    return 'application/vnd.oasis.opendocument.presentation';
      if (preg_match('/\.odt$/',$fn))    return 'application/vnd.oasis.opendocument.text';
      if (preg_match('/\.ods$/',$fn))    return 'application/vnd.oasis.opendocument.spreadsheet';
      if (preg_match('/\.odg$/',$fn))    return 'application/vnd.oasis.opendocument.graphics';
      return 'application/vnd.oasis.opendocument.text';
    
    }

      if (preg_match('/\.sxw$/',$fn))    return 'application/vnd.sun.xml.writer';
      if (preg_match('/\.sxc$/',$fn))    return 'application/vnd.sun.xml.calc';
      if (preg_match('/\.sxi$/',$fn))    return 'application/vnd.sun.xml.impress';
      if (preg_match('/\.sxd$/',$fn))    return 'application/vnd.sun.xml.draw';
      if (preg_match('/\.sxg$/',$fn))    return 'application/vnd.sun.xml.writer.global';
      if (preg_match('/\.odp$/',$fn))    return 'application/vnd.oasis.opendocument.presentation';
      if (preg_match('/\.odt$/',$fn))    return 'application/vnd.oasis.opendocument.text';
      if (preg_match('/\.ods$/',$fn))    return 'application/vnd.oasis.opendocument.spreadsheet';
      if (preg_match('/\.odg$/',$fn))    return 'application/vnd.oasis.opendocument.graphics';
    
  }
  if (!$sys ) {
    // try with text only
    if ($txt) {
      if (preg_match('/^XML/',$txt))  return 'text/xml';
    }
    
    if (preg_match('/\.xls$/',$fn))    return 'application/vnd.ms-excel';
    if (preg_match('/\.doc$/',$fn))    return 'application/msword';
    if (preg_match('/\.ppt$/',$fn))    return 'application/vnd.ms-powerpoint';
  }
  if ($sys=='application/msword') { // for old sys mime info
    if (preg_match('/\.xls$/',$fn))    return 'application/vnd.ms-excel';
    if (preg_match('/\.ppt$/',$fn))    return 'application/vnd.ms-powerpoint';
  }

  return $sys;
}

function getTextMimeFile($f) {
  $txt = trim(`file -b "$f"`);

  if (! $txt) return " ";
  return $txt;
}

/**
 * transform php postgresql connexion syntax for psql syntax connection
 * @param string postgresql string connection (like : dbname=anakeen user=admin)
 * @return string like --username admin --dbname anakeen
 */
function php2DbCreateSql($dbcoord) {
    if (eregi('dbname=[ ]*([a-z_0-9\'"][^ ]*)',$dbcoord,$reg)) {  
      $dbname=$reg[1];
    }
    if (eregi('host=[ ]*([a-z_0-9\'"][^ ]*)',$dbcoord,$reg)) {  
      $dbhost=$reg[1];
    }
    if (eregi('port=[ ]*([0-9\'"]*)',$dbcoord,$reg)) {  
      $dbport=$reg[1];
    }
    if (eregi('user=[ ]*([a-z_0-9\'"][^ ]*)',$dbcoord,$reg)) {  
      $dbuser=$reg[1];
    }
    $dbpsql="";
    if ($dbhost != "")  $dbpsql.= "--host $dbhost ";
    if ($dbport != "")  $dbpsql.= "--port $dbport ";
    if ($dbuser != "")  $dbpsql.= "--username $dbuser ";
    $dbpsql.= " $dbname ";
    return $dbpsql;  
}
?>