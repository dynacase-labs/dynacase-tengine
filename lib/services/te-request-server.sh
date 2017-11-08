#!/bin/bash

function te_request_server {
    local PID

    case "$1" in

	start)
	    if [ ! -f "${TE_REQUEST_PID}" ]; then
		if check_tcp_port "${PORT}"; then
		    echo "(te-request-server) Error: TE Request server's TCP port $PORT is already in use!"
		    return 1
		fi
		_service start "(te-request-server) TE Request server" "te-request-server" "${TE_REQUEST_PID}"
		return $?
	    fi
	    ;;

	stop)
	    if [ -f "${TE_REQUEST_PID}" ]; then
		_service stop "(te-request-server) TE Request server" "te-request-server" "${TE_REQUEST_PID}"
		return $?
	    else
		echo "(te-request-server) TE Request server not running"
	    fi
	    ;;

	status)
	    if [ -f "${TE_REQUEST_PID}" ]; then
		PID=$(_service status "${TE_REQUEST_PID}")
		if [ $? -eq 0 ]; then
		    echo "(te-request-server) TE Request server running (${PID})"
		    return 0
		fi
	    fi
	    echo "(te-request-server) TE Request server is down"
	    return 1
	    ;;

	*)
	    echo "Unknown operation '$1'!" 1>&2
	    return 1
	    ;;

    esac
    return 0
}

# vim: tabstop=8 softtabstop=4 shiftwidth=4 noexpandtab
