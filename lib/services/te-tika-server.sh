#!/bin/bash

function te_tika_server {
    local PID

    case "$1" in

	start)
	    if [ "$TE_TIKA_SERVER_ENABLED" = "yes" ] && [ ! -f "$TE_TIKA_PID" ]; then
		if check_tcp_port "${TE_TIKA_SERVER_PORT}"; then
		    echo "(te-tika-server) Error: Tika's TCP port $TE_TIKA_SERVER_PORT is already in use!"
		    return 1
		fi
		_service start "(te-tika-server) Tika server" "te-tika-server" "$TE_TIKA_PID"
		return $?
	    fi
	    ;;

	stop)
	    if [ "$TE_TIKA_SERVER_ENABLED" = "yes" ]; then
		if [ -f $TE_TIKA_PID ]; then
		    _service stop "(te-tika-server) Tika server" "te-tika-server" "$TE_TIKA_PID"
		    return $?
		else
		    echo "(te-tika-server) Tika server not running"
		fi
	    fi
	    ;;

	status)
	    if [ "${TE_TIKA_SERVER_ENABLED}" != "yes" ]; then
		echo "(te-tika-server) Tika server not enabled"
		return 0
	    fi
	    if [ -f "${TE_TIKA_PID}" ]; then
		PID=$(_service status "${TE_TIKA_PID}")
		if [ $? -eq 0 ]; then
		    echo "(te-tika-server) Tika server running (${PID})"
		    return 0
		fi
	    fi
	    echo "(te-tika-server) Tika server is down"
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
