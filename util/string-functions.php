<?php
function endsWith($haystack, $needle) {
  return substr($haystack, -strlen($needle)) === $needle;
}

function startsWith($haystack, $needle) {
  return substr($haystack, 0, strlen($needle)) === $needle;
}
?>
