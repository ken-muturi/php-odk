<?php
preg_match_all("/\/([^\/]+)\//i", $_SERVER['REQUEST_URI'], $match);

define('REQUEST_URI', $_SERVER['REQUEST_URI']);

define('BASE_DIR', $match[1][0] . '/');

define ('FS_PATH', str_replace('//', '/', $_SERVER['DOCUMENT_ROOT'] . '/' . BASE_DIR));

define ('BASE_URL', 'http://' . str_replace('//', '/', $_SERVER['SERVER_NAME'] . '/' . BASE_DIR));

/* Log stuff */
error_reporting(E_ALL);
ini_set('log_errors', 'On');
ini_set('error_log', BASE_DIR . 'logs/'.date('d-m-Y') . '.log');
ini_set('log_level', 4);

set_error_handler(function($errno, $errstr, $errfile, $errline )
{
    error_log( "$errno, $errstr, $errfile, $errline");
    echo "<pre>";
        echo("Error : " . $errstr);
        echo "FILE : $errfile, $errline";
    echo "</pre>";
    exit();
});

