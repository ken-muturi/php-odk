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

$realm = 'PHP ODK Aggregate';
$users = array('admin' => 'mypass', 'guest' => 'guest');

function check_user_pass( $data = null , $users = array('admin' => 'mypass', 'guest' => 'guest') )
{
    global $realm;
    $data = $data ? $data : http_digest_parse($_SERVER['PHP_AUTH_DIGEST']);

    $A1 = md5($data['username'] . ':' . $realm . ':' . $users[$data['username']]);
    $A2 = md5($_SERVER['REQUEST_METHOD'].':'.$data['uri']);
    $valid_response = md5($A1.':'.$data['nonce'].':'.$data['nc'].':'.$data['cnonce'].':'.$data['qop'].':'.$A2);

    if ($data['response'] != $valid_response)
        return false;

    return true;
}

// function to parse the http auth header
function http_digest_parse($txt)
{
    // protect against missing data
    $needed_parts = array('nonce'=>1, 'nc'=>1, 'cnonce'=>1, 'qop'=>1, 'username'=>1, 'uri'=>1, 'response'=>1);
    $data = array();
    $keys = implode('|', array_keys($needed_parts));

    preg_match_all('@(' . $keys . ')=(?:([\'"])([^\2]+?)\2|([^\s,]+))@', $txt, $matches, PREG_SET_ORDER);

    foreach ($matches as $m) {
        $data[$m[1]] = $m[3] ? $m[3] : $m[4];
        unset($needed_parts[$m[1]]);
    }

    return $needed_parts ? false : $data;
}

function formList() 
{
    $forms = glob(FS_PATH.'uploads/odk/*');
    $_forms = [];
    $_forms [] = '<xforms xmlns="http://openrosa.org/xforms/xformsList">';
    foreach ($forms as $form)
    {
        $details =  pathinfo($form);
        $url =  site_url('uploads/odk/'.$details['basename']);
        $_forms [] = "<xform>";
            $_forms [] = "<formID>{$details['filename']}</formID>";
            $_forms [] = "<name>{$details['filename']}</name>";
            $_forms [] = "<version>1.1</version>";
            $_forms [] = "<hash>md5:". md5_file( $form ) ."</hash>";
            $_forms [] = "<downloadUrl>{$url}</downloadUrl>";
        $_forms [] = "</xform>";
    }
    $_forms [] = '</xforms>';
   return $_forms;
}

function site_url($var)
{
    return BASE_URL.$var;
}

function printr($var)
{
    if ($var === FALSE) 
    {
        var_dump($var);
    }
    else 
    {
        echo "<pre>" . print_r($var, 1) . "</pre>";
    }
}
