<?php

function httpresponse($status, $result, $error)
{
    // https://www.php.net/manual/en/function.http-response-code.php
    header('Access-Control-Allow-Origin: *');
    header('Content-type: application/json');
    $requestStatus = _requestStatus($status);
    header("HTTP/1.1 " . $status . " " . $requestStatus);
    if ($status == 200 || $status == 201 || $status == 204) {
        echo json_encode($result, JSON_PRETTY_PRINT);
    } else {
        // die('Could not connect: ' . mysql_error());
        echo json_encode(array('status' => 'error', 'msg' => $error, 'details' => $result), JSON_PRETTY_PRINT);
    }
}

function _requestStatus($code)
{
    $status = array(
        200 => 'OK',
        201 => 'Created',
        204 => 'No Content',
        404 => 'Not Found',
        401 => 'Unauthorized',
        405 => 'Method Not Allowed',
        500 => 'Internal Server Error',
    );
    return ($status[$code]) ? $status[$code] : $status[500];
}
