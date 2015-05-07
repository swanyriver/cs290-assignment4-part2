<?php
ini_set('display_errors', 'On');

include "storedInfo.php"; //contains hostname/username/password/databasename

//set up logfile and form action adress
$LogFile = fopen("logfile.txt", "w");
$SELF = "\"http://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . "\"";

//connect to database with created mysqli object
$mysqli = new mysqli($hostname, $Username, $Password, $DatabaseName);
if ($mysqli->connect_errno || $mysqli->connect_error)
{
  fwrite($LogFile, "error #" . $mysqli->connect_errno . ":" . $mysqli->connect_error);
  safeExit("Unable to connect to database");
} else fwrite($LogFile, "sucessful connection to database,  ");


//create table if it doesnt exist
$mysqli->query("CREATE TABLE IF NOT EXISTS records (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) UNIQUE NOT NULL,
  category VARCHAR(255) NOT NULL,
  length INT UNSIGNED NOT NULL,
  rented BOOL DEFAULT FALSE
  )");

function safeExit($msg){
  echo $msg;
  fclose($GLOBALS['LogFile']);
  exit();
}

//check for a form action, modify database acordingly
if(isset($_POST['ACTION'])){
  
  fwrite($LogFile, "user action:" . $_POST['ACTION'] . ",  ");

  //user filtered categories 
  if($_POST['ACTION'] == "categoryfilter"){

  }
  //user chose to delete all
  if($_POST['ACTION'] == "deleteAll"){

  }
  //user added a video
  if($_POST['ACTION'] == "addvideo"){

  }
  // //user 
  // if($_POST['ACTION'] == ""){

  // }

}


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
      <?php
      $fieldcount = $mysqli->query("SELECT COUNT(*) FROM records");
      $fieldcount = $fieldcount->fetch_array(MYSQLI_NUM)[0];
      fwrite($LogFile, "rowcount: ". $fieldcount);
      if($fieldcount){
        echo "
          <form id='categoryfilter' action = $SELF method ='post'>
            <input type='hidden' name='ACTION' value='categoryfilter'>
            <select name='category'>";
              
        //generate category items here
        $ctgStmt = $mysqli->prepare("SELECT DISTINCT (category) FROM records");
        $ctgStmt->execute();
        $ctgStmt->bind_result($nextCat);
        while($ctgStmt->fetch()){
          echo "<option value='$nextCat'>$nextCat</option>
          ";
        }

        echo "   
            </select>
            <input type='submit' value='Filter Videos by Category'>
          </form>

          <form id='deleteAll' action = $SELF method ='post'>
            <input type='hidden' name='ACTION' value='deleteAll'>
            <input type='submit' value='Delete All Videos'>
          </form>

          <br>
          ";
      }
      ?>

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
          $vidStmt = $mysqli->prepare("SELECT * FROM records");
          $vidStmt->execute();
          $vidStmt->bind_result($id,$name,$category,$length,$rented);
          $rentText = array("Avalabile","Checked Out");
          $rentButton = array("Rent","Return");
          while($vidStmt->fetch()){
            //fwrite($LogFile, $id . ", " . $name . ", " . $category . ", " . $length . ", " . $rented);
            echo "<tr>
            <td> $name <td> $category <td> $length <td> 
            <form name='rent' action = $SELF method ='post'>
              <input type='hidden' name='ACTION' value='rent'>
              <input type='hidden' name='returning' value='$rented'>
              $rentText[$rented]
              <input type='submit' value='$rentButton[$rented]'>
            </form>
            <td> delete will go here
            ";
          }
          ?>
        </table>

    </body>
</html>