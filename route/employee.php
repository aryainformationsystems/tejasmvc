<?php
  include_once($_SERVER['DOCUMENT_ROOT'] . '/util/router-functions.php');
  include_once($_SERVER['DOCUMENT_ROOT'] . '/controller/employee.php');

  if ($params = isMatch('/employee/all', 'GET')) {
    verifyAdminJwt();
    getAll($params);
  }
  elseif ($params = isMatch('/employee/ddl', 'GET')) {
    verifyAdminJwt();
    ddl($params);
  }
  elseif ($params = isMatch('/employee/:id', 'GET')) {
    verifyAdminJwt();
    getById($params);
  }
  elseif ($params = isMatch('/employee/delete', 'POST')) {
    verifyAdminJwt();
    deleteMany($params);
  }
  elseif ($params = isMatch('/employee/search', 'POST')) {
    verifyAdminJwt();
    search($params);
  }
  elseif ($params = isMatch('/employee', 'POST')) {
    verifyAdminJwt();
    create($params);
  }
  elseif ($params = isMatch('/employee/:id', 'PUT')) {
    verifyAdminJwt();
    update($params);
  }
  elseif ($params = isMatch('/employee/:id', 'DELETE')) {
    verifyAdminJwt();
    deleteById($params);
  }
?>
