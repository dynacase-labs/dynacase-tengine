#!/bin/bash

function te_rendering_server {
    local PID

    case "$1" in

	start)
	    if [ ! -f "${TE_RENDERING_PID}" ]; then
		_service start "(te-rendering-server) TE Rendering server" "te-rendering-server" "${TE_RENDERING_PID}"
		return $?
	    fi
	    ;;

	stop)
	    if [ -f "${TE_RENDERING_PID}" ]; then
		_service stop "(te-rendering-server) TE Rendering server" "te-rendering-server" "${TE_RENDERING_PID}"
		return $?
	    else
		echo "(te-rendering-server) TE Rendering server not running"
	    fi
	    ;;

	status)
	    if [ -f "${TE_RENDERING_PID}" ]; then
		PID=$(_service status "${TE_RENDERING_PID}")
		if [ $? -eq 0 ]; then
		    echo "(te-rendering-server) TE Rendering server running (${PID})"
		    return 0
		fi
	    fi
	    echo "(te-rendering-server) TE Rendering server is down"
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
