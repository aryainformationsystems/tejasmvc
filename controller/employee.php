<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/util/api-functions.php');

$employeeSchema = $_GLOBALS['schema']['employee'];

function getAll($params) {
  global $employeeSchema;
  $offset = 0;
  $limit = 10;
  $sort = "createdAt";
  $order = "DESC";
  if (isset($_REQUEST["size"])) {
    $limit = $_REQUEST["size"];
  }
  if (isset($_REQUEST["page"])) {
    $offset = ($_REQUEST["page"] - 1) * $limit;
  }
  if (isset($_REQUEST["sort"])) {
    $sort = $_REQUEST["sort"];
  }
  if (isset($_REQUEST["order"])) {
    $order = $_REQUEST["order"] == "-1" ? "DESC": "ASC";
  }
  try {
    $result = $employeeSchema->findAll($offset, $limit, $sort, $order);
    sendResponse($result, count($result));
  }
  catch(Exception $ex) {
    handleError($ex);
  }
}

function getById($params) {
  global $employeeSchema;
  try {
    sendResponse($employeeSchema->findById($params["id"]));
  }
  catch(Exception $ex) {
    handleError($ex);
  }
}

function create($params) {
  global $employeeSchema;
  try {
    sendResponse($employeeSchema->insert($_REQUEST["requestBody"]));
  }
  catch(Exception $ex) {
    handleError($ex);
  }
}

function deleteById($params) {
  global $employeeSchema;
  try {
    sendResponse($employeeSchema->deleteById($params["id"]));
  }
  catch(Exception $ex) {
    handleError($ex);
  }
}

function update($params) {
  global $employeeSchema;
  try {
    sendResponse($employeeSchema->update($params["id"], $_REQUEST["requestBody"]));
  }
  catch(Exception $ex) {
    handleError($ex);
  }
}

function search($params) {
  global $employeeSchema;
  notImplemented();
}

function deleteMany($params) {
  global $employeeSchema;
  notImplemented();
}

function ddl($params) {
  global $employeeSchema;
  try {
    echo $employeeSchema->ddl();
  }
  catch(Exception $ex) {
    handleError($ex);
  }
}

?>
