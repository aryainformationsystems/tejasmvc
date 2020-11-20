<?php

/* Configuration */

$DB_HOST = "127.0.0.1";
$DB_USER = "root";
$DB_PASSWORD = "password@123";
$DB_NAME = "test_db";

/* DB functions */

function connect() {
  global $DB_HOST, $DB_USER, $DB_PASSWORD, $DB_NAME;
  $mysqli = mysqli_connect($DB_HOST, $DB_USER, $DB_PASSWORD, $DB_NAME);
  if ($mysqli->connect_error) {
    die('Connection Failed: ' . $mysqli->connect_error);
  }
  return $mysqli;
}

?>
