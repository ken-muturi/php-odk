<?php
class Util
{
    public static $realm = 'PHP ODK Aggregate';
    public static $users = [
        'admin' => 'mypass',
        'guest' => 'guest'
    ];

    // function to parse the http auth header
    public static function http_digest_parse( $txt )
    {
        // protect against missing data
        $needed_parts = array('nonce'=>1, 'nc'=>1, 'cnonce'=>1, 'qop'=>1, 'username'=>1, 'uri'=>1, 'response'=>1);
        $data = array();

        preg_match_all('@(\w+)=(?:(?:\'([^\']+)\'|"([^"]+)")|([^\s,]+))@', $txt, $matches, PREG_SET_ORDER);

        foreach ($matches as $m)
        {
        $data[$m[1]] = $m[2] ? $m[2] : ($m[3] ? $m[3] : $m[4]);
        unset($needed_parts[$m[1]]);
        }

        return $needed_parts ? false : $data;
    }

    public static function check_user_pass()
    {
        $data = self::http_digest_parse( $_SERVER['PHP_AUTH_DIGEST'] );
        $name = $data['username'];

        $path = FS_PATH.'/uploads/uploaded_files/data.json';
        file_put_contents($path, json_encode($data) );

        $A1 = md5($data['username'] . ':' . self::$realm . ':' . self::$users[$data['username']]);
        $A2 = md5($_SERVER['REQUEST_METHOD'].':'.$data['uri']);
        $valid_response = md5($A1.':'.$data['nonce'].':'.$data['nc'].':'.$data['cnonce'].':'.$data['qop'].':'.$A2);

        if ($data['response'] != $valid_response)
            return false;

        return true;
    }

    public static function formList()
    {
        $forms = glob(FS_PATH.'/uploads/odk/*');

        $_forms = ["<?xml version='1.0' encoding='UTF-8' ?>"];
        $_forms [] = '<xforms xmlns="http://openrosa.org/xforms/xformsList">';
        foreach ($forms as $form)
        {
            $details =  pathinfo($form);
            $url =  self::site_url('uploads/odk/'.$details['basename']);
            $_forms [] = "<xform>";
                $_forms [] = "<formID>{$details['filename']}</formID>";
                $_forms [] = "<name>{$details['filename']}</name>";
                $_forms [] = "<version>1.1</version>";
                $_forms [] = "<hash>md5:". md5_file( $form ) ."</hash>";
                $_forms [] = "<downloadUrl>{$url}</downloadUrl>";
            $_forms [] = "</xform>";
        }
        $_forms [] = '</xforms>';
       return join(' ', $_forms);
    }

    /**
     * Wrap output of the print_r() function in <PRE> html tags to enable easier debug selects either the last posted value or the db value (selected value)
     *
     * @author          imss team
     * */
    public static function printr($var)
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

    public static function printResponse($msg = '', $httpcode="200 OK")
    {
        header('HTTP/1.1 '.$httpcode);
        header('Content-Type: text/xml');
        echo "<OpenRosaResponse xmlns=\"http://openrosa.org/http/response items=\"1\">";
        echo "<message>$msg</message>";
        echo "</OpenRosaResponse>";
        die();
    }

    public static function printError($msg = '', $httpcode="400 Not found")
    {
        header('HTTP/1.1 '. $httpcode);
        header('Content-Type: text/html');
        echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\"\"http://www.w3.org/TR/1999/REC-html401-19991224/loose.dtd\">
            <html>
            <head>
            <title>Error</title>
            </head>
            <body><h1>$httpcode</h1>
            <p>$msg</p></body>
            </html>
            ";

        die();
    }

    /**
     * Create a teaser from string.
     *
     * This function will eventually be deleted, please use word_limiter()
     *
     * @param           string      $string     The
     * @param           int         $length     The character length of the teaser
     * @return          string
     * @author          imss team
     * */
    public static function teaser($string, $length)
    {
        return substr($string, 0, $length) . (($length < strlen($string)) ? " .." : null);
    }

    public static function site_url($var)
    {
        return BASE_URL.$var;
    }
}
