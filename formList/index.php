<?php
require("../config.php");
require("../util.php");

header('Content-Type: text/xml; charset=utf-8');
header('"HTTP_X_OPENROSA_VERSION": "1.0"');
header('X-OpenRosa-Version:1.0');

$realm = 'PHP ODK Aggregate';
$users = [
    'admin' => 'mypass',
    'guest' => 'guest'
];

if( ! isset( $_SERVER['PHP_AUTH_DIGEST'] ) || empty($_SERVER['PHP_AUTH_DIGEST']) )
{
	http_response_code(401);
	header("HTTP/1.1 401 Unauthorized");
	header('WWW-Authenticate: Digest realm="'. $realm .'",qop="auth",nonce="'.uniqid().'",opaque="'. md5($realm) .'"');

	die('Canceled !');
}

$data = util::http_digest_parse($_SERVER['PHP_AUTH_DIGEST']);
if (! $data || ! isset($users[ $data['username'] ]))
	die('Wrong Credentials!');


// generate the valid response

if ( ! util::check_user_pass( ) )
    http_response_code( 401 );

header("Server: Apache-Coyote/1.1");
header("X-OpenRosa-Version: 1.0");
header("X-OpenRosa-Accept-Content-Length: 1048576000");

echo  util::formList();
