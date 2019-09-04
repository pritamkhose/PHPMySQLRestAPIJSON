<?php
// https://www.w3schools.com/php/php_file_upload.asp

include "HttpResponse.php";

$target_dir = "uploads/";
$fileSizeLimit = 8 * 1000 * 1000; // 8 MB

$queries = array();
parse_str($_SERVER['QUERY_STRING'], $queries);
try {
    $action = $_SERVER['REQUEST_METHOD'];
    if (!empty($queries['action'])) {
        $action = $queries['action'];
    }
    switch ($action) {
        case 'GET':
            getFileList();
            break;
        case 'DELETE':
            deleteFile();
            break;
        case 'POST':
            updateFile();
            break;
        default:
            httpresponse(405, null, 'Method Not Allowed');
            break;
    }
} catch (exception $e) {
    httpresponse(500, null, $e->getMessage());
}

function getFileList()
{
    global $queries, $target_dir;

    if (!empty($queries['path'])) {
        $target_dir = $target_dir . '\/' . $queries['path'];
    }

    $files = scandir($target_dir);
    httpresponse(200, $files, null);
}

function deleteFile()
{
    global $queries, $target_dir;
    if (empty($queries['path'])) {
        httpresponse(404, empty($queries['file']), 'file name is empty');
    } else {
        $file = $target_dir . '\/' . $queries['path'];
        // Check if file already exists
        if (is_dir($file)) {
            delete_dir($file);
            httpresponse(200, array('status' => 'ok', 'msg' => "$file folder deleted!"), null);
        } else if (file_exists($file)) {
            if (!unlink($file)) {
                httpresponse(500, "path = $file", "Error in deleting");
            } else {
                httpresponse(200, array('status' => 'ok', 'msg' => "$file deleted!"), null);
            }
        } else {
            httpresponse(404, "path = $file", "Not Found");
        }
    }
}

function delete_dir($directory)
{
    foreach (glob("{$directory}/*") as $file) {
        if (is_dir($file)) {
            delete_dir($file);
        } else {
            unlink($file);
        }
    }
    rmdir($directory);
}

function updateFile()
{
    global $queries, $target_dir, $fileSizeLimit;

    if ($_FILES == null) {
        httpresponse(500, null, 'empty file not accepted.');
    } else {
        // Check file size
        if ($_FILES["file"]["size"] > $fileSizeLimit) {
            httpresponse(404, array(
                'filename' => ($_FILES["file"]["name"]),
                'filesize' => ($_FILES["file"]["size"]),
                'filetype' => ($_FILES["file"]["type"]),
                'fileerror' => ($_FILES["file"]["error"])),
                'Sorry, your file is too large and keep it below 8 MB.');
        } else if ($_FILES["file"]["size"] == 0) {
            httpresponse(404, array(
                'filename' => ($_FILES["file"]["name"]),
                'filesize' => ($_FILES["file"]["size"]),
                'filetype' => ($_FILES["file"]["type"]),
                'fileerror' => ($_FILES["file"]["error"])),
                'Invalid file size.');
        } else {
            // map file within specfic folder if path is given
            if (!empty($queries['path'])) {
                $target_dir = $target_dir . '\\' . $queries['path'] . '\\';
            }
            // check target_dir folder exist or not and if not then create it.
            if (!file_exists($target_dir)) {
                mkdir($target_dir);
            }
            // Add dyanmic name to file
            $target_file = $target_dir . date('ymd-His') . '-' . basename($_FILES["file"]["name"]);

            if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
                httpresponse(201, array('status' => 'ok',
                    'target_file' => $target_file,
                    // 'target_dir' => $target_dir,
                    'datetime' => date('Y-m-d h:i:sa'),
                    'filename' => ($_FILES["file"]["name"]),
                    'filesize' => ($_FILES["file"]["size"]),
                    'filetype' => ($_FILES["file"]["type"]),
                    'fileerror' => ($_FILES["file"]["error"]),
                    // 'tmp_name' => basename($_FILES["file"]["tmp_name"]),
                ), null);
            } else {
                httpresponse(500, 'No such file or directory', 'error uploading your file.');
            }
        }
    }
}
