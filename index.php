<?php
// https://www.codeofaninja.com/2017/02/create-simple-rest-api-in-php.html
// https://stackoverflow.com/questions/359047/detecting-request-type-in-php-get-post-put-or-delete
// https://www.php.net/manual/en/reserved.variables.server.php
// https://stackoverflow.com/questions/5060465/get-variables-from-the-outside-inside-a-function-in-php

include "config.php";

try {
    $queries = array();
    parse_str($_SERVER['QUERY_STRING'], $queries);
    if (sizeof($queries) == 0) {
        $date = new DateTime();
        httpresponse(200, array('status' => 'ok', 'msg' => 'Server Running @ ' . date('Y-m-d h:i:sa') . ' ' . date_default_timezone_get()), null);
    } else {
        if (empty($queries['dbname'])) {
            httpresponse(404, null, 'Database name not provided in query parameter');
        } else {
            if (empty($queries['tablename'])) {
                httpresponse(404, null, 'Tablename name not provided in query parameter');
            } else {
                $dbname = $db; //$queries['dbname'];
                $tablename = $queries['tablename'];
                $action = $_SERVER['REQUEST_METHOD'];
                if (!empty($queries['action'])) {
                    $action = $queries['action'];
                }
                switch ($action) {
                    case 'GET':
                        doGet();
                        break;
                    case 'DELETE':
                        doDelete();
                        break;
                    case 'POST':
                        doUpdate();
                        break;
                    // case 'PUT':
                    //     doUpdate();
                    //     break;
                    case 'OPTIONS':
                        doOption();
                        break;
                    // case 'PATCH':
                    //     do_patch($request);
                    // break;
                    default:
                        httpresponse(405, null, 'Method Not Allowed');
                        break;
                }
            }
        }
    }
} catch (exception $e) {
    httpresponse(500, null, $e->getMessage());
}

function doGet()
{
    global $dbhost, $dbuser, $dbpass, $dbname, $tablename, $queries;
    $sql = 'SELECT * FROM ' . $dbname . '.' . $tablename . ';';
    if (!empty($queries['info'])) {
        switch ($queries['info']) {
            case 'showtable':
                $sql = 'SHOW TABLES;';
                break;
            case 'tabledetails':
                $sql = 'DESCRIBE ' . $dbname . '.' . $tablename . ';';
                break;
            case 'search':
                $sql = goSearch();
                break;
            default:
                break;
        }
    }
    if (!empty($queries['id'])) {
        $sql = 'SELECT * FROM ' . $dbname . '.' . $tablename . ' where `id` = ' . $queries['id'] . ';';
    }
    $conn = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);
    $retval = mysqli_query($conn, $sql);
    if (!$retval) {
        httpresponse(500, null, mysqli_error($conn));
    } else {
        // https://www.php.net/manual/en/mysqli-result.fetch-array.php
        // https://stackoverflow.com/questions/18577774/differences-in-mysqli-fetch-functions
        // mysqli_fetch_array, mysqli_fetch_row, mysqli_fetch_assoc, mysqli_fetch_object
        // https://stackoverflow.com/questions/5323146/mysql-integer-field-is-returned-as-string-in-php
        if (!empty($queries['id'])) {
            httpresponse(200, mysqli_fetch_object($retval), null);
        } else {
            $rows = array();
            while ($row = mysqli_fetch_object($retval)) {
                $rows[] = $row;
            }
            httpresponse(200, $rows, null);
        }
    }
}

function goSearch()
{
    global $dbhost, $dbuser, $dbpass, $dbname, $tablename, $queries;
    $sqldetail = 'DESCRIBE ' . $dbname . '.' . $tablename . ';';
    $conn = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);
    $retval = mysqli_query($conn, $sqldetail);
    $colArr = array();
    while ($row = mysqli_fetch_array($retval)) {
        $colArr[] = $row[0];
    }
    $order = ' DESC';
    if (!empty($queries['order'])) {
        $order = 'ASC ';
    }
    $search = '';
    if (!empty($queries['where'])) {
        $search = ' WHERE ' . $queries['where'];
    } else if (!empty($queries['key']) && !empty($queries['search'])) {
        $search = ' WHERE `' . $queries['key'] . '` = \'' . $queries['search'] . '\' ';
    } else if (!empty($queries['search'])) {
        $search = ' WHERE ';
        foreach ($colArr as $value) {
            $search = $search . '`' . $value . '` LIKE \'' . $queries['search'] . '%\' or ';
        }
        $search = substr($search, 0, (strlen($search) - 3));
    }
    $limitoffset = '';
    $offset = '';
    if (!empty($queries['limit'])) {
        $limitoffset = ' LIMIT ' . $queries['limit'];
        if (!empty($queries['offset'])) {
            $limitoffset = $limitoffset . ' OFFSET ' . $queries['offset'];
        }
    }
    $sql = 'SELECT * FROM ' . $dbname . '.' . $tablename . $search . ' ORDER BY ' . $colArr[0] . ' ' . $order . $limitoffset . ';';
    // httpresponse(200, $sql, null);
    return $sql;
}

function doDelete()
{
    global $dbhost, $dbuser, $dbpass, $dbname, $tablename, $queries;
    if (empty($queries['id'])) {
        httpresponse(404, null, 'delete id is not provided in query parameter');
    } else {
        $sql = 'DELETE FROM ' . $dbname . '.' . $tablename . ' where `id` = ' . $queries['id'] . ';';
        $conn = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);
        $retval = mysqli_query($conn, $sql);
        if (mysqli_affected_rows($conn) == 0) {
            httpresponse(404, null, 'Invalid ID');
        } else {
            httpresponse(200, array('status' => 'ok', 'msg' => 'ID ' . $queries['id'] . ' is deleted'), null);
        }
    }
}

function doUpdate()
{
    global $dbhost, $dbuser, $dbpass, $dbname, $tablename, $queries;
    $body = json_decode(file_get_contents('php://input'), true);
    $sql = 'DESCRIBE ' . $dbname . '.' . $tablename . ';';
    $conn = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);
    $retval = mysqli_query($conn, $sql);
    $colArr = array();
    $colTypeArr = array();
    while ($row = mysqli_fetch_array($retval)) {
        $colArr[] = $row[0];
        $colTypeArr[] = $row[1];
    }
    if (empty($queries['id'])) { // Create or Insert
        doCreate($colArr, $colTypeArr, $body);
    } else { // Update
        $colIDName = $colArr[0];
        unset($colArr[0]); // remove first element ID
        $colName = implode(", ", $colArr);
        $colValue = '';
        $i = 1;
        foreach ($colArr as $value) {
            if (array_key_exists($value, $body)) {
                $colty = $colTypeArr[$i];
                if ((strpos($colty, 'int') !== false) || ((strpos($colty, 'decimal') !== false)) ||
                    (strpos($colty, 'double') !== false) || ((strpos($colty, 'float') !== false))) {
                    $colty = $body[$value];
                } else {
                    $colty = '\'' . $body[$value] . '\'';
                }
                $colValue = $colValue . '`' . $value . '` = ' . $colty . ', ';
            }
            $i++;
        }
        $colValue = substr($colValue, 0, (strlen($colValue) - 2));

        $sql = 'UPDATE ' . $dbname . '.' . $tablename . ' SET ' . $colValue . ' WHERE `' . $colIDName . '` = ' . $queries['id'] . ';';
        // httpresponse(200, $sql, null);
        $conn = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);
        if (mysqli_query($conn, $sql)) {
            $HttpStausCode = 204;
            if (mysqli_affected_rows($conn) == 1) {
                $HttpStausCode = 200;
            }
            $sql = 'SELECT * FROM ' . $dbname . '.' . $tablename . ' where `' . $colIDName . '` = ' . $queries['id'] . ';';
            $retval = mysqli_query($conn, $sql);
            httpresponse($HttpStausCode, mysqli_fetch_object($retval), null);
        } else {
            httpresponse(404, null, mysqli_error($conn) . '\n Invalid SQL Query = ' . $sql);
        }
    }
}

function doCreate($colArr, $colTypeArr, $body)
{
    global $dbhost, $dbuser, $dbpass, $dbname, $tablename, $queries;
    $colIDName = $colArr[0];
    unset($colArr[0]); // remove first element ID
    $colName = implode(", ", $colArr);
    $colValue = '';
    $i = 1;
    foreach ($colArr as $value) {
        $colty = $colTypeArr[$i];
        if ((strpos($colty, 'int') !== false) || ((strpos($colty, 'decimal') !== false)) ||
            (strpos($colty, 'double') !== false) || ((strpos($colty, 'float') !== false))) {
            if (array_key_exists($value, $body)) {
                $colty = $body[$value];
            } else {
                $colty = 0;
            }
        } else {
            if (array_key_exists($value, $body)) {
                $colty = '\'' . $body[$value] . '\'';
            } else {
                $colty = '\'\'';
            }
        }
        $colValue = $colValue . $colty . ', ';
        $i++;
    }
    $colValue = substr($colValue, 0, (strlen($colValue) - 2));

    $sql = 'Insert INTO ' . $dbname . '.' . $tablename . ' (' . $colName . ') VALUES (' . $colValue . ');';
    // httpresponse(201, $sql, null);
    $conn = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);
    $retval = mysqli_query($conn, $sql);
    if (mysqli_affected_rows($conn) < 0) {
        httpresponse(201, array('status' => 'error', 'msg' => 'Data not Created', 'sql' => $sql), null);
    } else {
        $sql = 'SELECT * FROM ' . $dbname . '.' . $tablename . ' where `' . $colIDName . '` = ' . mysqli_insert_id($conn) . ';';
        $retval = mysqli_query($conn, $sql);
        httpresponse(201, mysqli_fetch_object($retval), null);
    }
}

function doOption()
{
    global $dbhost, $dbuser, $dbpass, $dbname, $tablename, $queries;
    $body = json_decode(file_get_contents('php://input'), true);
    if (!empty($body['sql'])) {
        $sql = $body['sql'];
        $conn = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);
        $retval = mysqli_query($conn, $sql);
        if (!$retval) {
            httpresponse(404, 'sql = ' . $sql, mysqli_error($conn));
        } else {
            $rows = array();
            while ($row = mysqli_fetch_object($retval)) {
                $rows[] = $row;
            }
            httpresponse(200, $rows, null);
        }
    } else {
        httpresponse(404, null, 'Invalid SQL Query');
    }
}

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
