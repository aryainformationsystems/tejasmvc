<?php
function handleError($err) {

}

function notImplemented() {
  header('Content-Type: application/json');
  echo json_encode(array(
    "data"=> null,
    "message"=> "Method Not Implemented",
    "error"=> null,
    "count"=> -1)
  );
  http_response_code(501);
}

function sendResponse($data, $count = -1) {
  header('Content-Type: application/json');
  echo json_encode(array(
    "data"=> $data,
    "message"=> "success",
    "error"=> null,
    "count"=> $count)
  );
}
?>
