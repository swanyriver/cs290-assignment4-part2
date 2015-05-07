<?php
ini_set('display_errors', 'On');

include "storedInfo.php"; //contains hostname/username/password/databasename

$SELF = "\"http://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . "\"";

$mysqli = new mysqli($hostname, $Username, $Password, $DatabaseName);
if ($mysqli->connect_errno || $mysqli->connect_error)
{
  echo "error #" . $mysqli->connect_errno . ":" . $mysqli->connect_error;
  return;
}

$mysqli->query("CREATE TABLE IF NOT EXISTS records (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  category VARCHAR(255) NOT NULL,
  length INT UNSIGNED NOT NULL,
  rented BOOL DEFAULT FALSE
  )");
?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title>[CS290] PHP part 2</title>
        <style type="text/css">
          th {font-weight: bold;}
          th,td {padding:5px; border: solid 1px;}
          table {border-collapse: collapse; border: solid thick;}
        </style>
    </head>
    <body>
        <form id='categoryfilter' action = <?php echo $SELF; ?> method ='post'>
          <input type='hidden' name='ACTION' value='categoryfilter'>
          <select name='category'>
            <?php
            //generate category items here
            //<option value='testa'>testa</option>
            ?>
          </select>
          <input type='submit' value='Filter Videos by Category'>
        </form>

        <form id='deleteAll' action = <?php echo $SELF; ?> method ='post'>
          <input type='hidden' name='ACTION' value='deleteAll'>
          <input type="submit" value="Delete All Videos">
        </form>

        <br>

        <form id='addvideo' action = <?php echo $SELF; ?> method ='post'>
          <fieldset>
            <input type='hidden' name='ACTION' value='addvideo'>
            <input type="submit" value="ADD VIDEO">
            Name:<input type="text" name="name">
            Category:<input type="text" name="category">
            Length (minutes):<input type="text" name="length">
          </fieldset>
        </form>

        <table id="videos" >
          <thead><tr><th>Name<th>Category<th>Length<th>Avalability<th></thead>
          <?php
            //generate table here from database
          ?>
        </table>

    </body>
</html>