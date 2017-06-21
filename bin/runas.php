#!/usr/bin/env php
<?php
/**
 * Helper script to uniformize su/runuser functionality across Linux distributions
 */

function usage()
{
    print <<<EOF
Usage
-----

	runas <uid|login> <command> [<args>]


EOF;
    
    
}

function main(&$argv)
{
    $myself = array_shift($argv);
    if ($myself === false) {
        usage();
        throw new \Exception(sprintf("Error: missing arguments."));
    }
    $runAs = array_shift($argv);
    if ($runAs === false) {
        usage();
        throw new \Exception(sprintf("Error: missing uid or login."));
    }
    if (count($argv) <= 0) {
        usage();
        throw new \Exception(sprintf("Error missing command."));
    }
    
    if (is_numeric($runAs)) {
        $user_pw = posix_getpwuid($runAs);
    } else {
        $user_pw = posix_getpwnam($runAs);
    }
    if ($user_pw === false) {
        throw new \Exception(sprintf("Error: could not get information for user '%s'.", $runAs));
    }
    
    $user_name = $user_pw['name'];
    $user_uid = $user_pw['uid'];
    $user_gid = $user_pw['gid'];
    $user_home = $user_pw['dir'];
    
    if (posix_setgid($user_gid) === false) {
        throw new \Exception(sprintf("Error: could not change gid to '%s'.", $user_gid));
    }
    if (posix_setuid($user_uid) === false) {
        throw new \Exception(sprintf("Error: could not change uid to '%s'.", $user_uid));
    }
    
    $envs = array();
    if ($user_name != '') {
        $envs['USER'] = $user_name;
    } else {
        fwrite(STDERR, sprintf("Warning: empty USER.\n"));
    }
    if ($user_home != '') {
        $envs['HOME'] = $user_home;
    } else {
        fwrite(STDERR, sprintf("Warning: empty HOME.\n"));
    }
    foreach (array(
        'PATH',
        'TERM',
        'TE_HOME'
    ) as $envVar) {
        if (($value = getenv($envVar)) !== false) {
            $envs[$envVar] = $value;
        }
    }
    
    $args = array(
        '-c',
        join(' ', array_map('escapeshellarg', $argv))
    );
    if (pcntl_exec('/bin/bash', $args, $envs) === false) {
        throw new \Exception(sprintf("Error: could not execute '%s'.", join(' ', $argv)));
    }
}

main($argv);
