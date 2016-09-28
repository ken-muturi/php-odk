<?php
require("../config.php");

if ( ! empty($_FILES) )
{
	header('Server: Apache-Coyote/1.1');
	header('Content-Type: application/xml;charset=UTF-8');
	header( "Date: ".date('r'));
	header('Content-Length: 1000000');
	header('X-OpenRosa-Version: 1.0');
	header('X-OpenRosa-Accept-Content-Length: 1048576000');

	foreach( $_FILES as $file)
	{
		$file_name = 'uploads/uploaded_files/'. $file['name'];
		move_uploaded_file( $file['tmp_name'],  $file_name);

		if(substr(strrchr($file_name, '.'), 1) == 'xml')
		{
			$postFields['xml_submission_file'] ='@'.$file_name;
		}
		else
		{
			$postFields['datafile'.$i]='@'.$file_name.';type='. $file['type']; 
		}				
	}
	file_put_contents('uploads/uploaded_files/submission.json', json_encode($_POST) );

	http_response_code(201);
    echo '<OpenRosaResponse xmlns="http://openrosa.org/http/response">
    	<message nature="submit_success">Thanks</message>
    </OpenRosaResponse> ';
    exit();
}
else
{
	// header('Content-Type: text/xml; charset=utf-8');
	// header('X-OpenRosa-Version:1.0');
	
	if ( empty( $_SERVER['PHP_AUTH_DIGEST']) )
    { 
    	// before credentials
        http_response_code( 401 );
        header("HTTP/1.1 401 Unauthorized");
        header('WWW-Authenticate: Digest realm="'. $realm .'",qop="auth",nonce="'.uniqid().'",opaque="'. md5($realm) .'"');
        header('"HTTP_X_OPENROSA_VERSION":"1.0"');
        header('X-OpenRosa-Accept-Content-Length: 2000000');
        header('Content-Length: 0');
    } 
    else 
    { // after credentials
    	header("Server: Apache-Coyote/1.1");
		header("X-OpenRosa-Version: 1.0");
		header("X-OpenRosa-Accept-Content-Length: 1048576000");
		header( "Date: ".date('r'));
		
		$data = http_digest_parse($_SERVER['PHP_AUTH_DIGEST']);
		if (! $data || ! isset($users[ $data['username'] ]))
			die('Wrong Credentials!');

		if ( ! check_user_pass( $data, array('admin' => 'mypass', 'guest' => 'guest')) )
		    http_response_code( 401 );

		// header("Location:". site_url('submission/index.php'), true, 204);
		http_response_code(201);
		echo '<OpenRosaResponse xmlns="http://openrosa.org/http/response">
    		<message nature="submit_success">Thanks</message>
    	</OpenRosaResponse> ';

        // if( check_user_pass( $data, array('admin' => 'mypass', 'guest' => 'guest'))  )
        // {
        // 	header("Location:". site_url('submission/index.php'), true, 204);
        // }
        // else
        // {
        // 	http_response_code(401);
        // }
    }
}