<?php
ini_set('display_errors', 'On');

include "storedInfo.php"; //contains hostname/username/password/databasename

$mysqli = new mysqli($hostname, $Username, $Password, $DatabaseName);
if ($mysqli->connect_errno || $mysqli->connect_error)
{
  echo "error #" . $mysqli->connect_errno . ":" . $mysqli->connect_error;
  return;
}

echo "SUCCESS";

?>