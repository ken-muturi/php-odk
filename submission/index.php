<?php
require("../config.php");
require("../util.php");

$mediaStoreFolder = FS_PATH."/uploads/uploaded_files/";

$deviceid = isset($_GET['deviceID']) ? $_GET['deviceID'] : false;
$deviceid or Util::printError("Device ID is missing", "400 Device id is missing");

header('Server: Apache-Coyote/1.1');
header('Content-Type: application/xml;charset=UTF-8');
header( "Date: ".date('r'));
header('Content-Length: 1000000');
header('X-OpenRosa-Version: 1.0');
header('X-OpenRosa-Accept-Content-Length: 1048576000');

$filearray = array();
if( $_SERVER['REQUEST_METHOD'] === "HEAD" )
{
    http_response_code(204);
}
elseif( $_SERVER['REQUEST_METHOD'] === "POST" )
{
    http_response_code(201);
    $tmpname = $_FILES['xml_submission_file']['tmp_name'];
    $name = $_FILES['xml_submission_file']['name'];

    file_put_contents($mediaStoreFolder .'headers.txt', "Temp name : ".$tmpname."\n", FILE_APPEND);
    file_put_contents($mediaStoreFolder .'headers.txt', "Name : ".$name."\n", FILE_APPEND);
    libxml_use_internal_errors(true);

    if( ! $file_exists($tmpname) )
        Util::printError("XML file not found");

    $xml = simplexml_load_file($tmpname);
    if($xml === false)
    {
        $errormsgs= "";
        foreach(libxml_get_errors() as $error)
        {
            $errormsgs.="\t".$error->message;
        }

        file_put_contents($mediaStoreFolder. 'headers.txt', "XML parse errors: {$errormsgs}.\n", FILE_APPEND);
        Util::printError("Unable to parse ODK XML file.\n {$errormsgs}", "400 XML error");
    }

    $instanceName = $xml->meta->instanceName or Util::printError("Unable to parse ODK XML file", "400 XML parse failed");
    file_put_contents($mediaStoreFolder. 'headers.txt', "\n\nInstance name: {$instanceName} \n", FILE_APPEND);
    file_put_contents($mediaStoreFolder. 'headers.txt', "\n".$xml->asXML()."\n", FILE_APPEND);

    foreach( $_FILES as $file)
    {
        $arraycontents = print_r($file, true);
        file_put_contents($mediaStoreFolder. 'headers.txt', "$arraycontents\n", FILE_APPEND);
        file_put_contents($mediaStoreFolder. 'headers.txt', "Copying file: ".$file['name']." to temp folder\n", FILE_APPEND);

        if(endsWith($file['name'], "xml"))
            continue;

        switch($file['error'])
        {
            case UPLOAD_ERR_OK:
                    // upload is fine
                    printError("The uploaded the file", "hooollah !");
                break;

            case UPLOAD_ERR_INI_SIZE:
                    printError("The uploaded file exceeds the upload_max_filesize directive in php.ini", "413 Media file too large");
                break;

            case UPLOAD_ERR_FORM_SIZE:
                $message = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
                    printError($message, "413 Media file too large");
                break;

            case UPLOAD_ERR_PARTIAL:
                $message = "The uploaded file was only partially uploaded";
                printError($message, "400 Media file upload failure");
                break;

            case UPLOAD_ERR_NO_FILE:
                $message = "No file was uploaded";
                printError($message, "400 Media file upload failure");
                break;

            case UPLOAD_ERR_NO_TMP_DIR:
                $message = "Missing a temporary folder";
                printError($message, "500".$message);
                break;

            case UPLOAD_ERR_CANT_WRITE:
                $message = "Failed to write file to disk";
                printError($message, "500".$message);
                break;

            case UPLOAD_ERR_EXTENSION:
                $message = "File upload stopped by extension";
                printError($message, "500".$message);
                break;

            default:
                $message = "Unknown upload error";
                printError($message, "500". $message);
                break;
        }

        move_uploaded_file( $file['tmp_name'], $mediastorefolder.$file['name']) or printError("Failed to copy media file", "400 Bad request");
        $currentname = $file['tmp_name'];
        $storedname = $mediaStoreFolder.$file['name'];
        file_put_contents($mediaStoreFolder.'headers.txt', "Current filename : ".$currentname."\n", FILE_APPEND);
        file_put_contents($mediaStoreFolder.'headers.txt', "Stored filename : ".$storedname."\n", FILE_APPEND);

        // Fail if media file already exists
        if (file_exists($storedname))
        {
            printError("Failed to copy media file", "500 Media file already exists");
        }
        move_uploaded_file($currentname, $storedname) or printError("Failed to copy media file", "500 Media file copy fail");
        array_push($filearray, $storedname);

        file_put_contents("$mediaStoreFolder.submission.json", $xml->asXML());
    }
    echo util::printResponse('Thanks');
    exit();
    // writeInstanceToDB($instanceName, $xml->asXML(), $filearray);
}

