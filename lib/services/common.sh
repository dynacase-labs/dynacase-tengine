#!/bin/bash

function check_environment {
    local CMD
    CMD=$1
    if [ ! -d "$TE_HOME" ]; then
	echo "Error: TE_HOME directory '$TE_HOME' not found."
	exit 1
    fi
    if [ ! -d "$TE_HOME/etc" ]; then
	echo "Error: directory '$TE_HOME/etc' not found."
	exit 1
    fi
    if [ ! -f "$TE_HOME/etc/te.conf" ]; then
	echo "Error: config file '$TE_HOME/etc/te.conf' not found."
	echo "Hint: you might need to create 'te.conf' by copying '$TE_HOME/etc/te.conf.sample'."
	exit 1
    fi
    if [ ! -d "$PID_DIR" -a ! -w "$PID_DIR" ]; then
	echo "Error: pid dir '$PID_DIR' not found or not writable."
	exit 1
    fi
    if [ ! -d "$TE_WORK_DIR" ]; then
	echo "Error: TE_WORK_DIR '$TE_WORK_DIR' is not a valid directory."
	exit 1
    fi
    if [ ! -w "$TE_WORK_DIR" ]; then
	echo "Error: TE_WORK_DIR '$TE_WORK_DIR' is not writable."
	exit 1
    fi
    if [ "$TE_OOO_SERVER_ENABLED" = "yes" ]; then
	if [ ! -x "$TE_OOO_SERVER_SOFFICE" ]; then
	    echo "Error: TE_OOO_SERVER_SOFFICE '$TE_OOO_SERVER_SOFFICE' (in '$TE_HOME/etc/te.conf') is not executable!"
	    exit 1
	fi
	if [ -z "$(which java)" ]; then
	    echo "Error: java interpreter not found in PATH!"
	    exit 1
	fi
	if [ -z "$TE_OOO_PRODUCTKEY" ]; then
	    TE_OOO_PRODUCTKEY=$(te_ooo_productkey)
	fi
    fi
    if [ "$TE_TIKA_SERVER_ENABLED" = "yes" ]; then
	if [ ! -f "$TE_TIKA_SERVER_JAR" ]; then
	    echo "Error: TE_TIKA_SERVER_JAR '$TE_TIKA_SERVER_JAR' (in '$TE_HOME/etc/te.conf') does not exists!"
	    exit 1
	fi
	if [ -z "$(which java)" ]; then
	    echo "Error: java interpreter not found in PATH!"
	    exit 1
	fi
    fi
    check_php
    if [ "$CMD" != "stop" ]; then
	check_te_pg_service
    fi
}

function check_te_pg_service {
    local OUTPUT
    if [ -z "$TE_PG_SERVICE" ]; then
	echo "Error: TE_PG_SERVICE must not be empty in '$TE_HOME/etc/te.conf'."
	exit 1
    fi
    OUTPUT=$(php -- "service=$TE_PG_SERVICE" <<'EOF'
<?php
ini_set("error_log", null);
ini_set("track_errors", true);
if (($r = @pg_connect($argv[1])) === false) {
    printf("%s\n", $php_errormsg);
    exit(1);
}
pg_close($r);
exit(0);
EOF
    )
    if [ $? -ne 0 ]; then
	echo "Error: could not connect to postgresql service '$TE_PG_SERVICE': $OUTPUT"
	exit 1
    fi
}

function php_function_exists {
    php -r 'exit(function_exists($argv[1])?0:1);' "$1"
}

function exit_if_not_php_function_exists {
    php_function_exists "$1"
    if [ $? -ne 0 ]; then
	echo "$2"
	exit 1
    fi
}
	
function check_php {
    if [ -z "$(which php)" ]; then
	echo "Error: php interpreter not found in PATH!"
	exit 1
    fi
    exit_if_not_php_function_exists pg_connect "Error: missing 'pgsql' PHP extension!"
    exit_if_not_php_function_exists json_encode "Error: missing 'json' PHP extension!"
    exit_if_not_php_function_exists simplexml_load_file "Error: missing 'SimpleXML' PHP extension!"
    exit_if_not_php_function_exists gettext "Error: missing 'gettext' PHP extension!"
    exit_if_not_php_function_exists pcntl_fork "Error: missing 'pcntl' PHP extension!"
    exit_if_not_php_function_exists posix_kill "Error: missing 'posix' PHP extension!"
    exit_if_not_php_function_exists curl_init "Error: missing 'curl' PHP extension!"
}

function check_tcp_port {
    local P=$1
    if which netstat > /dev/null 2>&1; then
	netstat -tnl | grep ":$P[[:space:]]" > /dev/null 2>&1
    elif which ss > /dev/null 2>&1; then
	ss -tnl | grep ":$P[[:space:]]" > /dev/null 2>&1
    else
	# The port cannot be checked so assume it is not already opened...
	false
    fi
}

function check_ports {
    check_tcp_port "$PORT"
    if [ $? -eq 0 ]; then
	echo "Error: te_request_server's TCP port $PORT is already in use!"
	exit 1
    fi
    if [ "$TE_OOO_SERVER_ENABLED" = "yes" ]; then
	check_tcp_port "$TE_OOO_SERVER_PORT"
	if [ $? -eq 0 ]; then
	    echo "Error: OpenOffice's TCP port $TE_OOO_SERVER_PORT is already in use!"
	    exit 1
	fi
    fi
    if [ "$TE_TIKA_SERVER_ENABLED" = "yes" ]; then
	check_tcp_port "$TE_TIKA_SERVER_PORT"
	if [ $? -eq 0 ]; then
	    echo "Error: Tika's TCP port $TE_TIKA_SERVER_PORT is already in use!"
	    exit 1
	fi
    fi
}

function te_ooo_productkey {
    local SOFFICE_DIR
    SOFFICE_DIR=$(dirname "$TE_OOO_SERVER_SOFFICE")
    local BOOTSTRAPRC
    BOOTSTRAPRC="$SOFFICE_DIR/bootstraprc"
    if [ -f "$BOOTSTRAPRC" ]; then
	sed -n -e 's/^[[:space:]]*ProductKey[[:space:]]*=[[:space:]]*//p' "$BOOTSTRAPRC" 2> /dev/null | head -1
    else
	echo ""
    fi
}

function init {
    TE_INIT=$"Initializing ${NAME} service: "
    if  [ ! -d "$TE_HOME/" ]
	then
	echo "Transformation Engine not installed in $TE_HOME"
	log_failure_msg "$TE_INIT" "FAILED"
	exit 1;
    fi
    "$TE_HOME/bin/te_server_init"  --db="$DB" 
    ret=$?
    if [ $ret -eq 0 ]; then
	log_success_msg "$TE_INIT""OK"
	echo
    else
	if [ $ret -eq 1 ]; then
	    echo
	    echo -n  "Database already created"
	    log_warning_msg "$TE_INIT" "WARNING"
	    echo
	else
	    log_failure_msg "$TE_INIT" "FAILED"
	    echo
	    return 1
	fi
    fi
}

function check {
    "${TE_HOME}/bin/runas.php" "$TE_SERVER_USER" "${TE_HOME}/lib/engines/engines-check.sh"
}

function cleantmpfiles {
    local DEADLINE="7"
    if [[ -n $1 && $1 =~ ^[0-9][0-9]*$ ]]; then
	DEADLINE=$1
    fi
    if [ -d "$TE_WORK_DIR" ]; then
	find "$TE_WORK_DIR" -maxdepth 1 -type d -name "te-task-*" -mtime "+$DEADLINE" -print0 | xargs -0 --no-run-if-empty rm -r
    fi
}

# vim: tabstop=8 softtabstop=4 shiftwidth=4 noexpandtab
