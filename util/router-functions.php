<?php
function parseParams($pattern) {
  $params = array();
  $urlParts = explode("/", $_SERVER['SCRIPT_URL']);
  $patternParts = explode("/", $pattern);
  for ($i = 0; $i < count($urlParts); $i++) {
    if (substr($patternParts[$i], 0, 1) == ":") {
      $params[substr($patternParts[$i], 1)] = $urlParts[$i];
    }
  }
  return $params;
}

function isMatch($pattern, $method) {
  if ($_SERVER["REQUEST_METHOD"] != $method) {
    return false;
  }
  $urlParts = explode("/", $_SERVER['SCRIPT_URL']);
  $patternParts = explode("/", $pattern);
  if (count($urlParts) != count($patternParts)) {
    return false;
  }
  for ($i = 0; $i < count($urlParts); $i++) {
    if (substr($patternParts[$i], 0, 1) != ":" && $urlParts[$i] != $patternParts[$i]) {
      return false;
    }
  }
  $params = parseParams($pattern);
  return count($params) == 0 ? true:$params;
}

function verifyAdminJwt() {
 return;
}
?>

