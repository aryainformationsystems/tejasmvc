<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/util/schema.php');

$addressAttributes = array(
  array("name"=> "_id", "type"=> "int"),
  array("name"=> "addressLine1", "type"=> "string"),
  array("name"=> "addressLine2", "type"=> "string"),
  array("name"=> "city", "type"=> "string"),
  array("name"=> "state", "type"=> "string"),
  array("name"=> "zip", "type"=> "string"),
  array("name"=> "addressProof", "type"=>"file")
);

$workExperienceAttributes = array(
  array("name"=> "_id", "type"=> "int"),
  array("name"=> "companyName", "type"=> "string"),
  array("name"=> "startDate", "type"=> "date"),
  array("name"=> "endDate", "type"=> "date"),
  array("name"=> "position", "type"=> "string"),
  array("name"=> "appointmentLetter", "type"=>"file"),
  array("name"=> "relievingLetter", "type"=>"file")
);

$skillAttributes = array(
  array("name"=> "_id", "type"=> "int"),
  array("name"=> "name", "type"=> "string"),
);

$employeeSchema = new Schema('employee');
$employeeSchema
  ->attribute('_id', 'int')
  ->attribute('firstName', 'string')
  ->attribute('lastName', 'string')
  ->attribute('email', 'string')
  ->attribute('phone', 'string')
  ->childObject('address', $addressAttributes)
  ->childObjectArray('workExperience', $workExperienceAttributes)
  ->childObjectArray('skills', $skillAttributes);
  
$_GLOBALS['schema']['employee'] = $employeeSchema;
?>
