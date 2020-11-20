<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/util/string-functions.php');
include_once($_SERVER['DOCUMENT_ROOT'] . '/util/db-functions.php');

$inputJSON = file_get_contents('php://input');
$_REQUEST['requestBody'] = json_decode($inputJSON, TRUE);

$fileAttributes = array(
  array("name"=> "_id", "type"=>"int"),
  array("name"=> "originalName", "type"=>"string"),
  array("name"=> "encoding", "type"=>"string"),
  array("name"=> "mimetype", "type"=>"string"),
  array("name"=> "filename", "type"=>"string"),
  array("name"=> "path", "type"=>"string"),
  array("name"=> "size", "type"=>"bigint"),
  array("name"=> "status", "type"=>"int")
);

/* Utility Functions */

function isChild($key, $schema) {
  foreach($schema->children as $child) {
    if ($child->name == $key) {
      return true;
    }
  }
  return false;
}

/* CRUD Operations - CREATE */

function insertRecord($schema, $data, $mysqli) {
  $types = array();
  $questions = "";
  $insertables = array();
  foreach($data as $key=>$value) {
    if (!isChild($key, $schema)) {
      if (isset($schema->columns[$key])) {
        $types[$key] = $schema->types[$key];
        $questions = $questions . "?, ";
        $insertables[$key] = $value;
      }
    }
  }
  $questions = substr($questions, 0, -2);
  $columnNames = implode(', ', array_keys($insertables));
  $stmt = $mysqli->prepare("insert into $schema->table ($columnNames, createdAt, updatedAt, isActive) values ($questions, now(), now(), 1)");
  $stmt->bind_param(implode('', array_values($types)), ... array_values($insertables));
  $stmt->execute();
  $id = $mysqli->insert_id;
  $data[$schema->idColumn] = $id;
  foreach($schema->children as $child) {
    if (isset($data[$child->name]) && $data[$child->name] != null) {
      if ($child->relationType == "1:1") {
        $data[$child->name]["$schema->name$schema->idColumn"] = $id;
        insertRecord($child, $data[$child->name], $mysqli);
      }
      elseif ($child->relationType == '1:m') {
        foreach($data[$child->name] as $record) {
          $record["$schema->name$schema->idColumn"] = $id;
          insertRecord($child, $record, $mysqli);
        }
      }
    }
  }
  return $data;
}

/* CRUD Operations - DELETE */

function deleteRecord($schema, $id, $mysqli) {
  foreach($schema->children as $child) {
    $stmt = $mysqli->prepare("delete from $child->table where $schema->name$schema->idColumn = ?");
    $stmt->bind_param($schema->types[$schema->idColumn], $id);
    $stmt->execute();
  }
  $stmt = $mysqli->prepare("delete from $schema->table where $schema->idColumn = ?");
  $stmt->bind_param($schema->types[$schema->idColumn], $id);
  $stmt->execute();
}

/* CRUD Operations - UPDATE */

function updateRecord($schema, $id, $data, $mysqli) {
  // Updating the parent record
  $sql = "UPDATE $schema->table SET";
  $insertables = array();
  $types = array();
  foreach($data as $key=>$value) {
    if (isset($schema->columns[$key]) && $key != $schema->idColumn && $key != 'createdAt' && $key != 'updatedAt') {
      $types[$key] = $schema->types[$key];
      $insertables[$key] = $value;
      $sql = "$sql $key=?, ";
    }
  }
  $sql = substr($sql, 0, -2) . ", updatedAt=now() WHERE $schema->idColumn = ?";
  $types[$schema->idColumn] = $schema->types[$schema->idColumn];
  $insertables[$schema->idColumn] = $data[$schema->idColumn];
  $stmt = $mysqli->prepare($sql);
  $stmt->bind_param(implode('', array_values($types)), ... array_values($insertables));
  $stmt->execute();
  // Deleting related children
  foreach($schema->children as $child) {
    $stmt = $mysqli->prepare("delete from $child->table where $schema->name$schema->idColumn = ?");
    $stmt->bind_param($schema->types[$schema->idColumn], $id);
    $stmt->execute();
  }
  // Inserting related children
  foreach($schema->children as $child) {
    if ($child->relationType == "1:1") {
      $data[$child->name]["$schema->name$schema->idColumn"] = $id;
      unset($data[$child->name]["createdAt"]);
      unset($data[$child->name]["updatedAt"]);
      unset($data[$child->name]["_id"]);
      unset($data[$child->name]["isActive"]);
      $inserted = insertRecord($child, $data[$child->name], $mysqli);
      $data[$child->name] = $inserted;
    }
    elseif ($child->relationType == '1:m') {
      $allInserted = array();
      foreach($data[$child->name] as $record) {
        $record["$schema->name$schema->idColumn"] = $id;
        unset($record["createdAt"]);
        unset($record["updatedAt"]);
        unset($record["_id"]);
        unset($data[$child->name]["isActive"]);
        $inserted = insertRecord($child, $record);
        $allInserted[] = $inserted;
      }
      $data[$child->name] = $allInserted;
    }
  }
  return $data;
}

/* CRUD Operations - GET BY ID */

function getRecordById($schema, $id) {
  $mysqli = connect();
  $stmt = $mysqli->prepare("SELECT * FROM $schema->table WHERE $schema->idColumn = ?");
  $stmt->bind_param($schema->types[$schema->idColumn], $id);
  $stmt->execute();
  $result = $stmt->get_result();
  $resultData = NULL;
  if ($row = $result->fetch_assoc()) {
    $resultData = $row;
    foreach($schema->children as $child) {
      $stmt = $mysqli->prepare("SELECT * FROM $child->table WHERE $schema->name$schema->idColumn = ?");
      $stmt->bind_param($child->types["$schema->name$schema->idColumn"], $id);
      $stmt->execute();
      $res = $stmt->get_result();
      if ($child->relationType == "1:m") {
        $rows = array();
        while($row = $res->fetch_assoc()) {
          $rows[] = $row;
        }
        $resultData[$child->name] = $rows;
      }
      elseif ($child->relationType == "1:1") {
        if($row = $res->fetch_assoc()) {
          $resultData[$child->name] = $row;
        }
      }
    }
    $mysqli->close();
    return $resultData;
  }
  else {
    return null;
  }
}

/* CRUD Operations - GET ALL */

function getAllRecords($schema, $offset, $limit, $sort, $order) {
  $mysqli = connect();
  $stmt = $mysqli->prepare("SELECT * FROM $schema->table order by $sort $order LIMIT $limit OFFSET $offset");
  $stmt->execute();
  $result = $stmt->get_result();
  $resultData = array();
  while ($row = $result->fetch_assoc()) {
    foreach($schema->children as $child) {
      $stmt = $mysqli->prepare("SELECT * FROM $child->table WHERE $schema->name$schema->idColumn = ?");
      $stmt->bind_param($child->types["$schema->name$schema->idColumn"], $row[$schema->idColumn]);
      $stmt->execute();
      $res = $stmt->get_result();
      if ($child->relationType == "1:1") {
        if($childRow = $res->fetch_assoc()) {
          $row[$child->name] = $childRow;
        }
      }
      elseif ($child->relationType == "1:m") {
        $rows = array();
        while($childRow = $res->fetch_assoc()) {
          $rows[] = $childRow;
        }
        $row[$child->name] = $rows;
      }
    }
    $resultData[] = $row;
  }
  $mysqli->close();
  return $resultData;
}

/* DDL Operations - FETCH THE CREATE TABLE INSTRUCTIONS */

function getTableDDl($schema, $parentSchema = NULL) {
  $sql = "CREATE TABLE $schema->table (\n";
  foreach($schema->columns as $columnName=>$dataType) {
    $sql = "$sql  $columnName $dataType";
    if ($columnName == $schema->idColumn) {
      $sql = "$sql PRIMARY KEY AUTO_INCREMENT";
    }
    $sql = "$sql,\n";
  }
  if ($parentSchema != null) {
    $sql = "$sql  FOREIGN KEY ($parentSchema->name$parentSchema->idColumn) REFERENCES $parentSchema->table($parentSchema->idColumn),\n";
  }
  $sql = substr($sql, 0, -2) . "\n)";
  foreach($schema->children as $child) {
    $sql = "$sql;\n\n" . $child->ddl($schema);
  }
  return "$sql";
}

/* Schema Class Definition */

class Schema {
  public $name;
  public $table;
  public $columns;
  public $types;
  public $idColumn;
  public $references;
  public $children;
  public $relationType;
  public $foreignKey;
  
  function __construct($name) {
    $this->name = $name;
    $this->table = "table" . ucfirst($name);
    $this->idColumn = "_id";
    $this->columns = array("createdAt"=>"datetime", "updatedAt"=>"datetime", "isActive"=>"int");
    $this->types = array("createdAt"=>"s", "updatedAt"=>"s", "isActive"=>"i");
    $this->references = array();
    $this->children = array();
  }
  
  function attribute($name, $type, $length = 255) {
    global $fileAttributes;
    switch($type) {
      case 'string':
      case 'email':
        $this->columns[$name] = "varchar($length)";
        $this->types[$name] = "s";
        break;
      case 'password':
        $this->columns[$name . 'Salt'] = "varchar($length)";
        $this->types[$name . 'Salt'] = "s";
        $this->columns[$name . 'Hash'] = "varchar($length)";
        $this->types[$name . 'Hash'] = "s";
        break;
      case 'date':
        $this->columns[$name] = "date";
        $this->types[$name] = "s";
        break;
      case 'int':
      case 'bigint':
      case 'float':
      case 'double':
      case 'smallint':
        $this->columns[$name] = $type;
        $this->types[$name] = "i";
        break;
      case 'selection':
      case 'valueselection':
        $this->columns[$name] = "varchar(255)";
        $this->types[$name] = "s";
        break;
      case 'file':
        $this->childObject($name, $fileAttributes);
        break;
      case 'filearray':
        $this->childObjectArray($name, $fileAttributes);
        break;
    }
    return $this;
  }
  
  function setRelationType($relationType) {
    $this->relationType = $relationType;
  }

  function childObject($name, $attributes) {
    $schema = new Schema($name);
    $schema->table = "table" . ucfirst($this->name) . ucfirst($name);
    foreach($attributes as $attribute) {
      if (isset($attribute['length'])) {
        $schema->attribute($attribute['name'], $attribute['type'], $attribute['length']);
      }
      else {
        $schema->attribute($attribute['name'], $attribute['type']);
      }
    }
    if (!isset($schema->types[$schema->idColumn])) {
      $schema->attribute("_id", "int");
    }
    $schema->attribute("$this->name" . $this->idColumn, $this->columns[$this->idColumn]);
    $schema->setRelationType("1:1");
    $this->children[] = $schema;
    return $this;
  }
  
  function childObjectArray($name, $attributes) {
    $schema = new Schema($name);
    $schema->table = "table" . ucfirst($this->name) . ucfirst($name);
    foreach($attributes as $attribute) {
      if (isset($attribute['length'])) {
        $schema->attribute($attribute['name'], $attribute['type'], $attribute['length']);
      }
      else {
        $schema->attribute($attribute['name'], $attribute['type']);
      }
    }
    if (!isset($schema->types[$schema->idColumn])) {
      $schema->attribute("_id", "int");
    }
    $schema->attribute("$this->name" . $this->idColumn, $this->columns[$this->idColumn]);
    $schema->setRelationType("1:m");
    $this->children[] = $schema;
    return $this;
  }
  
  function insert($data) {
    if (!isset($this->columns[$this->idColumn])) {
      throw new Exception('Id column $this->idColumn, has not been added to attribute list');
    }
    $mysqli = connect();
    $mysqli->autocommit(FALSE);
    $mysqli->begin_transaction();
    try {
      return insertRecord($this, $data, $mysqli);
      $mysqli->commit();
    }
    catch(Exception $ex) {
      $mysqli->rollback();
      throw $ex;
    }
    $mysqli->autocommit(TRUE);
    $mysqli->close();
  }

  function update($id, $data) {
    $mysqli = connect();
    $mysqli->autocommit(FALSE);
    $mysqli->begin_transaction();
    try {
      return updateRecord($this, $id, $data, $mysqli);
      $mysqli->commit();
    }
    catch(Exception $ex) {
      $mysqli->rollback();
      throw $ex;
    }
    $mysqli->autocommit(TRUE);
    $mysqli->close();
  }

  function deleteById($id) {
    $mysqli = connect();
    $mysqli->autocommit(FALSE);
    $mysqli->begin_transaction();
    try {
      deleteRecord($this, $id, $mysqli);
      $mysqli->commit();
    }
    catch(Exception $ex) {
      $mysqli->rollback();
      throw $ex;
    }
    $mysqli->autocommit(TRUE);
    $mysqli->close();
  }
  
  function findById($id) {
    try {
      return getRecordById($this, $id);
    }
    catch(Exception $ex) {
      throw $ex;
    }
  }
  
  function findAll($offset, $limit, $sort, $order) {
    try {
      return getAllRecords($this, $offset, $limit, $sort, $order);
    }
    catch(Exception $ex) {
      throw $ex;
    }
  }
  
  function ddl($parentSchema = NULL) {
    try {
      return getTableDDl($this, $parentSchema);
    }
    catch(Exception $ex) {
      throw $ex;
    }
  }
}

?>
