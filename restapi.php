<?php
// https://www.codeofaninja.com/2017/02/create-simple-rest-api-in-php.html
// https://stackoverflow.com/questions/359047/detecting-request-type-in-php-get-post-put-or-delete
// https://www.php.net/manual/en/reserved.variables.server.php


   try{
    $headers = apache_request_headers();
    $queries = array();
    parse_str($_SERVER['QUERY_STRING'], $queries);
    $entityBody = file_get_contents('php://input');
     httpresponse(true, 
     array('REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'] ,
     'REQUEST_SCHEME' => $_SERVER['REQUEST_SCHEME'] ,
     'SERVER_ADDR'=> $_SERVER['SERVER_ADDR'] ,
     'REQUEST_TIME' => $_SERVER['REQUEST_TIME'] ,
     'HTTP_ACCEPT_ENCODING'=> $_SERVER['HTTP_ACCEPT_ENCODING'] ,
     'REQUEST_URI'=> $_SERVER['REQUEST_URI'] ,
    //  'AUTH_TYPE'=> $_SERVER['AUTH_TYPE'] ,
    //  'PATH_INFO'=> $_SERVER['PATH_INFO'] ,
     'headers'=> $headers,
     'queries'=> $queries,
     'body'=> json_decode($entityBody, TRUE) // json_decode(file_get_contents('php://input'), TRUE)
        ), null);
     /*switch ($method) {
        case 'GET':
            do_get($request);  
        break;
        case 'POST':
            do_post($request);  
        break;
        case 'PUT':
            do_put($request);  
        break;
        case 'DELETE':
            do_delete($request);  
        break;
        case 'OPTIONS':
            do_option($request);  
        break;
        default:
          handle_error($request);  
        break;
     }*/
   } catch (exception $e) {
      httpresponse(false, null, $e->getMessage());
   }

   function httpresponse($resultok, $result, $error ) {
      header('Access-Control-Allow-Origin: *');
      header('Content-type: application/json');
      if($resultok){
         echo json_encode($result, JSON_PRETTY_PRINT);
      } else if($resultcode == 404){
        header('HTTP/1.1 404 Not Found');
        echo json_encode(array('status' => 'error','msg' => $error), JSON_PRETTY_PRINT);
      } else if($resultcode == 405){
        header('HTTP/1.1 405 Method Not Allowed');
        echo json_encode(array('status' => 'error','msg' => 'Method Not Allowed'), JSON_PRETTY_PRINT);
      } else if($resultcode == 401){
        header('HTTP/1.1 401 Unauthorized');
        echo json_encode(array('status' => 'error','msg' => 'Unauthorized'), JSON_PRETTY_PRINT);
      } else {
         // die('Could not connect: ' . mysql_error());
         // header('HTTP/1.0 401 Unauthorized');
         // echo json_encode(array('status' => 'error','error' => mysqli_error($conn)));
         header('HTTP/1.1 500 Internal Server Error');
         echo json_encode(array('status' => 'error','error' => $error), JSON_PRETTY_PRINT);
      }
  }
  
?>
