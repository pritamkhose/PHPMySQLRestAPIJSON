<?php
// https://www.codeofaninja.com/2017/02/create-simple-rest-api-in-php.html
// https://stackoverflow.com/questions/359047/detecting-request-type-in-php-get-post-put-or-delete
// https://www.php.net/manual/en/reserved.variables.server.php

   try{
    $queries = array();
    parse_str($_SERVER['QUERY_STRING'], $queries);
    if(sizeof($queries) == 0){
        $date = new DateTime();
        httpresponse(200, array('status' => 'ok','msg' => 'Server Running @ '.date('Y-m-d h:i:sa') .' '.date_default_timezone_get()), null);
    } else {
    // if(empty($queries['dbname'])){
    //     httpresponse(404, null, 'Database name not provided in query parameter');
    // } else {
            if(empty($queries['tablename'])){
                httpresponse(404, null, 'Tablename name not provided in query parameter');
            } else {
                $dbname = $queries['dbname'];
                $tablename = $queries['tablename'];
                switch ($_SERVER['REQUEST_METHOD']) {
                    case 'GET':
                        doGet($dbname, $tablename);  
                    break;
                    case 'DELETE':
                        doDelete($dbname, $tablename); 
                    break;
                    // case 'POST':
                    //     do_post($request);  
                    // break;
                    // case 'PUT':
                    //     do_put($request);  
                    // break;
                    // case 'OPTIONS':
                    //     do_option($request);  
                    // break;
                    // case 'PATCH':
                    //     do_patch($request);  
                    // break;
                    default:
                        httpresponse(405, null, 'Method Not Allowed');
                    break;
                }
            }
        // }
    }
   } catch (exception $e) {
      httpresponse(500, null, $e->getMessage());
   }

   function doGet($dbname, $tablename){
    $dbhost = 'remotemysql.com:3306';
    $dbuser = 'tLbLJtFP5W';
    $dbpass = 'cR5j2r0Pag';
    $db     = 'tLbLJtFP5W';
    // $dbhost = 'localhost:3306';
    // $dbuser = 'root';
    // $dbpass = 'root';
    // $db     = 'phpdb';
    $sql = 'SELECT * FROM '.$tablename.';';
    $conn = mysqli_connect($dbhost, $dbuser, $dbpass, $db);
    $retval = mysqli_query($conn, $sql);
    if(! $retval ) {
       httpresponse(500, null, mysqli_error($conn));
    } else {
       // https://www.php.net/manual/en/mysqli-result.fetch-array.php
       // https://stackoverflow.com/questions/18577774/differences-in-mysqli-fetch-functions
       // mysqli_fetch_array, mysqli_fetch_row, mysqli_fetch_assoc, mysqli_fetch_object
       // https://stackoverflow.com/questions/5323146/mysql-integer-field-is-returned-as-string-in-php
       $rows=array();
       while($row =  mysqli_fetch_object($retval)) { 
          $rows[] = $row;
       }
       httpresponse(200, $rows, null);
    }
   }

   function doDelete($dbname, $tablename){
       
   } 

   function httpresponse($status, $result, $error ){
       // https://www.php.net/manual/en/function.http-response-code.php
      header('Access-Control-Allow-Origin: *');
      header('Content-type: application/json');
      $requestStatus= _requestStatus($status);
      header("HTTP/1.1 " . $status . " " . $requestStatus);
      if($status == 200){
         echo json_encode($result, JSON_PRETTY_PRINT);
      } else if(!empty($requestStatus)){
        echo json_encode(array('status' => 'error','msg' => $requestStatus." - ".$error), JSON_PRETTY_PRINT);
      }
      else {
         // die('Could not connect: ' . mysql_error());
         echo json_encode(array('status' => 'error','msg' => $error), JSON_PRETTY_PRINT);
      }
  }

  function _requestStatus($code) {
    $status = array(  
        200 => 'OK',
        404 => 'Not Found',  
        401 => 'Unauthorized' ,
        405 => 'Method Not Allowed',
        500 => 'Internal Server Error',
    ); 
    return ($status[$code]) ? $status[$code] : $status[500]; 
    }
  
?>
