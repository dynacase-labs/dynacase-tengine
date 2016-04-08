<?php
/*
 * @author Anakeen
 * @package FDL
 */

function te_microtime_diff($a, $b)
{
    list($a_micro, $a_int) = explode(' ', $a);
    list($b_micro, $b_int) = explode(' ', $b);
    if ($a_int > $b_int) {
        return ($a_int - $b_int) + ($a_micro - $b_micro);
    } elseif ($a_int == $b_int) {
        if ($a_micro > $b_micro) {
            return ($a_int - $b_int) + ($a_micro - $b_micro);
        } elseif ($a_micro < $b_micro) {
            return ($b_int - $a_int) + ($b_micro - $a_micro);
        } else {
            return 0;
        }
    } else { // $a_int<$b_int
        return ($b_int - $a_int) + ($b_micro - $a_micro);
    }
}
/**
 * If the argument is of the form --NAME=VALUE it will be represented in the array as an element with the key NAME and the value VALUE. I the argument is a flag of the form -NAME it will be represented as a boolean with the name NAME with a value of true (the string 'true') in the associative array.
 * @param array $argv global argv
 * @return array
 */
function getArgv(Array $argv)
{
    $_ARG = array();
    foreach ($argv as $arg) {
        if (preg_match('/--([^=]+)=(.*)/', $arg, $reg)) {
            $_ARG[$reg[1]] = $reg[2];
        } elseif (preg_match('/-([a-zA-Z0-9])/', $arg, $reg)) {
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
 * @return bool|string
 */
function te_getSysMimeFile($f, $fn = "")
{
    if (!file_exists($f)) {
        return false;
    }
    clearstatcache(); // to reset filesize
    $ret = te_getMimeFile($f, 'sys');
    if ($ret !== false) {
        return $ret;
    }
    
    $sys = trim(`file -bi "$f"`);
    $txt = te_getTextMimeFile($f);
    if ($fn == "") {
        $fn = basename($f);
    }
    // correct errors of file function
    if (preg_match('/Makefile/', $fn)) {
        return 'text/x-makefile';
    }
    if (preg_match('/ASCII C\+\+/', $txt)) {
        if (preg_match('/\.h$/', $fn)) {
            return 'text/x-c-header';
        }
        return 'text/x-c++';
    }
    if (preg_match('/PHP script/', $txt)) {
        return 'text/x-php';
    }
    // open office archive
    if (preg_match('/zip$/', $sys) || (preg_match('/octet-stream$/', $sys))) {
        
        if (preg_match('/OpenOffice/', $txt)) {
            if (preg_match('/\.sxw$/', $fn)) {
                return 'application/vnd.sun.xml.writer';
            }
            if (preg_match('/\.sxc$/', $fn)) {
                return 'application/vnd.sun.xml.calc';
            }
            if (preg_match('/\.sxi$/', $fn)) {
                return 'application/vnd.sun.xml.impress';
            }
            if (preg_match('/\.sxd$/', $fn)) {
                return 'application/vnd.sun.xml.draw';
            }
            if (preg_match('/\.sxg$/', $fn)) {
                return 'application/vnd.sun.xml.writer.global';
            }
            return 'application/vnd.sun.xml.writer';
        }
        if (preg_match('/OpenDocument/', $txt)) {
            if (preg_match('/\.odp$/', $fn)) {
                return 'application/vnd.oasis.opendocument.presentation';
            }
            if (preg_match('/\.odt$/', $fn)) {
                return 'application/vnd.oasis.opendocument.text';
            }
            if (preg_match('/\.ods$/', $fn)) {
                return 'application/vnd.oasis.opendocument.spreadsheet';
            }
            if (preg_match('/\.odg$/', $fn)) {
                return 'application/vnd.oasis.opendocument.graphics';
            }
            return 'application/vnd.oasis.opendocument.text';
        }
        
        if (preg_match('/\.sxw$/', $fn)) {
            return 'application/vnd.sun.xml.writer';
        }
        if (preg_match('/\.sxc$/', $fn)) {
            return 'application/vnd.sun.xml.calc';
        }
        if (preg_match('/\.sxi$/', $fn)) {
            return 'application/vnd.sun.xml.impress';
        }
        if (preg_match('/\.sxd$/', $fn)) {
            return 'application/vnd.sun.xml.draw';
        }
        if (preg_match('/\.sxg$/', $fn)) {
            return 'application/vnd.sun.xml.writer.global';
        }
        if (preg_match('/\.odp$/', $fn)) {
            return 'application/vnd.oasis.opendocument.presentation';
        }
        if (preg_match('/\.odt$/', $fn)) {
            return 'application/vnd.oasis.opendocument.text';
        }
        if (preg_match('/\.ods$/', $fn)) {
            return 'application/vnd.oasis.opendocument.spreadsheet';
        }
        if (preg_match('/\.odg$/', $fn)) {
            return 'application/vnd.oasis.opendocument.graphics';
        }
    }
    if (!$sys) {
        // try with text only
        if ($txt) {
            if (preg_match('/^XML/', $txt)) {
                return 'text/xml';
            }
        }
        
        if (preg_match('/\.xls$/', $fn)) {
            return 'application/vnd.ms-excel';
        }
        if (preg_match('/\.doc$/', $fn)) {
            return 'application/msword';
        }
        if (preg_match('/\.ppt$/', $fn)) {
            return 'application/vnd.ms-powerpoint';
        }
    }
    if ($sys == 'application/msword') { // for old sys mime info
        if (preg_match('/\.xls$/', $fn)) {
            return 'application/vnd.ms-excel';
        }
        if (preg_match('/\.ppt$/', $fn)) {
            return 'application/vnd.ms-powerpoint';
        }
    }
    
    $sys = strtok($sys, " \n\t");
    if ($sys == "") {
        $sys = "application/unknown";
    }
    return $sys;
}

function te_getTextMimeFile($f)
{
    $ret = te_getMimeFile($f, 'text');
    if ($ret !== false) {
        return $ret;
    }
    
    $txt = trim(`file -b "$f"`);
    
    if (!$txt) {
        return " ";
    }
    return $txt;
}
/**
 * transform php postgresql connexion syntax for psql syntax connection
 * @param string $dbcoord postgresql string connection (like : dbname=anakeen user=admin)
 * @return string like --username admin --dbname anakeen
 */
function php2DbCreateSql($dbcoord)
{
    $dbname = '';
    $dbhost = '';
    $dbport = '';
    $dbuser = '';
    
    if (preg_match('/dbname=[ ]*([a-z_0-9\'"][^ ]*)/i', $dbcoord, $reg)) {
        $dbname = $reg[1];
    }
    if (preg_match('/host=[ ]*([a-z_0-9\'"][^ ]*)/i', $dbcoord, $reg)) {
        $dbhost = $reg[1];
    }
    if (preg_match('/port=[ ]*([0-9\'"]*)/i', $dbcoord, $reg)) {
        $dbport = $reg[1];
    }
    if (preg_match('/user=[ ]*([a-z_0-9\'"][^ ]*)/i', $dbcoord, $reg)) {
        $dbuser = $reg[1];
    }
    $dbpsql = "";
    if ($dbhost != "") $dbpsql.= "--host $dbhost ";
    if ($dbport != "") $dbpsql.= "--port $dbport ";
    if ($dbuser != "") $dbpsql.= "--username $dbuser ";
    $dbpsql.= " $dbname ";
    return $dbpsql;
}

function te_fileextension($filename, $ext = "")
{
    $te = explode(".", basename($filename));
    if (count($te) > 1) $ext = $te[count($te) - 1];
    return $ext;
}
/**
 * get MIME type/text from mime.conf and mime-user.conf files
 */
function te_getMimeFile($filename, $type = 'sys')
{
    $conf_user = te_loadUserMimeConf();
    $conf_global = te_loadMimeConf();
    
    $conf = array_merge($conf_user, $conf_global);
    
    foreach ($conf as $rule) {
        $ext = $rule['ext'];
        if (preg_match("/\.\Q$ext\E$/i", $filename)) {
            return $rule[$type];
        }
    }
    
    return false;
}
/**
 * load mime-user.conf XML file into PHP array
 */
function te_loadUserMimeConf()
{
    $rules = array();
    
    $te_home = getenv('TE_HOME');
    if ($te_home === false) {
        error_log(__FUNCTION__ . " " . sprintf("Could not get TE_HOME env var."));
        return $rules;
    }
    
    $conf_file = sprintf("%s%etc%ssmime-user.conf", $te_home, DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR);
    if (!file_exists($conf_file) || !is_readable($conf_file)) {
        return $rules;
    }
    
    $xml = simplexml_load_file($conf_file);
    if ($xml === false) {
        error_log(__FUNCTION__ . " " . sprintf("Could not load user MIME config '%s'.", $conf_file));
        return $rules;
    }
    
    foreach ($xml->mime as $mimeNode) {
        $rule = array();
        foreach (array(
            'ext',
            'sys',
            'text'
        ) as $attrName) {
            $rule[$attrName] = (string)$mimeNode[$attrName];
        }
        array_push($rules, $rule);
    }
    
    return $rules;
}
/**
 * load mime.conf XML file into PHP array
 */
function te_loadMimeConf()
{
    $rules = array();
    
    $te_home = getenv('TE_HOME');
    if ($te_home === false) {
        error_log(__FUNCTION__ . " " . sprintf("Could not get TE_HOME env var."));
        return $rules;
    }
    
    $conf_file = sprintf("%s%setc%smime.conf", $te_home, DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR);
    $xml = simplexml_load_file($conf_file);
    if ($xml === false) {
        error_log(__FUNCTION__ . " " . sprintf("Could not load MIME config '%s'.", $conf_file));
        return $rules;
    }
    
    foreach ($xml->mime as $mimeNode) {
        $rule = array();
        foreach (array(
            'ext',
            'sys',
            'text'
        ) as $attrName) {
            $rule[$attrName] = (string)$mimeNode[$attrName];
        }
        array_push($rules, $rule);
    }
    
    return $rules;
}
?>
