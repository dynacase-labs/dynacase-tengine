#!/bin/bash

function te_ooo_server {
    local PID

    case "$1" in

	start)
	    if [ "${TE_OOO_SERVER_ENABLED}" = "yes" ] && [ ! -f "${TE_OOO_PID}" ]; then
		if check_tcp_port "${TE_OOO_SERVER_PORT}"; then
		    echo "(te-ooo-server) Error: OpenOffice's TCP port ${TE_OOO_SERVER_PORT} is already in use!"
		    return 1
		fi
		_service start "(te-ooo-server) OOO server (${TE_OOO_PRODUCTKEY})" "te-ooo-server" "${TE_OOO_PID}"
		return $?
	    fi
	    ;;

	stop)
	    if [ "${TE_OOO_SERVER_ENABLED}" = "yes" ]; then
		if [ -f "${TE_OOO_PID}" ]; then
		    _service stop "(te-ooo-server) OOO server (${TE_OOO_PRODUCTKEY})" "te-ooo-server" "${TE_OOO_PID}"
		    return $?
		else
		    echo "(te-ooo-server) OOO server not running"
		fi
	    fi
	    ;;

	status)
	    if [ "${TE_OOO_SERVER_ENABLED}" != "yes" ]; then
		echo "(te-ooo-server) OOO server not enabled"
		return 0
	    fi
	    if [ -f "${TE_OOO_PID}" ]; then
		PID=$(_service status "${TE_OOO_PID}")
		if [ $? -eq 0 ]; then
		    echo "(te-ooo-server) OOO server (${TE_OOO_PRODUCTKEY}) running (${PID})"
		    return 0
		fi
	    fi
	    echo "(te-ooo-server) OOO server (${TE_OOO_PRODUCTKEY}) is down"
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
