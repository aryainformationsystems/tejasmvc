<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/util/router-functions.php');
$inputJSON = file_get_contents('php://input');
$_REQUEST['requestBody'] = json_decode($inputJSON, TRUE);
$_GLOBALS['schema'] = array();

include_once($_SERVER['DOCUMENT_ROOT'] . '/schema/employee.php');

include_once($_SERVER['DOCUMENT_ROOT'] . '/route/employee.php');
?>
