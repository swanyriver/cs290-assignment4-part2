<?php
ini_set('display_errors', 'On');

include "storedInfo.php"; //contains hostname/username/password/databasename

//set up logfile and form action adress
$LogFile = fopen("logfile.txt", "w");
$SELF = "\"http://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . "\"";
$isError = false;
$errorMsg = '';
$categorySelected = '';

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
    fwrite($LogFile, "selected category:{$_POST['category']} ");
    $categorySelected = $_POST['category'];
  }
  //user chose to delete all
  if($_POST['ACTION'] == "deleteAll"){
    fwrite($LogFile, "deleting all videos, ");
    $mysqli->query("TRUNCATE TABLE records");
  }
  //user added a video
  if($_POST['ACTION'] == "addvideo"){

    //verify entries
    if (!isset($_POST['name']) || $_POST['name']=='' || $_POST['name'] == NULL){
      $isError = true;
      $errorMsg = $errorMsg . "name must be supplied <br>";
    }
    if (!(!isset($_POST['length']) || $_POST['length']=='' || $_POST['length'] == NULL)){
      if(!is_numeric($_POST['length'])){
        $isError = true;
        $errorMsg = $errorMsg . "length must be numeric <br>";
      }
      else if($_POST['length']<0){
        $isError = true;
        $errorMsg = $errorMsg . "length must a positive number <br>";
      }
    } 

    //add video to database if no errors
    if(!$isError){
      fwrite($LogFile, "adding video:{$_POST['name']}, ");
      $addstmt = $mysqli->prepare("INSERT INTO records ( name, category, length ) VALUES (?, ?, ?)");
      $addstmt->bind_param("ssi", $_POST['name'], $_POST['category'], $_POST['length']);
      $addstmt->execute();
    }
  }
  //user deleted a video
  if($_POST['ACTION'] == "deleteVideo"){
    //$video = intval(var)
    fwrite($LogFile, "deleting video {$_POST['id']} , ");
    $dltstmt = $mysqli->prepare("DELETE FROM records WHERE id=?");
    $dltstmt->bind_param("i",$_POST['id']);
    $dltstmt->execute();
  }
  //user rented/returned a video
  if($_POST['ACTION'] == "rent"){
    fwrite($LogFile, "renting video {$_POST['id']} rented:{$_POST['rented']}, ");
    $rentstmt = $mysqli->prepare("UPDATE records SET rented=? WHERE id=?");
    $rented = !$_POST['rented'];
    $rentstmt->bind_param("ii", $rented, $_POST['id']);
    $rentstmt->execute();
  }

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

      if($isError)echo "<div id='errorMsg'> $errorMsg </div>";

      $fieldcount = $mysqli->query("SELECT COUNT(*) FROM records");
      $fieldcount = $fieldcount->fetch_array(MYSQLI_NUM)[0];
      fwrite($LogFile, "rowcount: ". $fieldcount);
      if($fieldcount){
        echo "
          <form id='categoryfilter' action = $SELF method ='post'>
            <input type='hidden' name='ACTION' value='categoryfilter'>
            <select name='category'>
              <option value='AllCategories'>All Movies</option>
            ";
              
        //generate category items here
        $ctgStmt = $mysqli->prepare("SELECT DISTINCT (category) FROM records");
        $ctgStmt->execute();
        $ctgStmt->bind_result($nextCat);
        while($ctgStmt->fetch()){
          $selected = ($categorySelected == $nextCat) ? 'selected=selected' : '';
          echo "<option value='$nextCat' $selected>$nextCat</option>
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
          if(!$categorySelected || $categorySelected == "AllCategories"){
            $vidStmt = $mysqli->prepare("SELECT * FROM records");
          }else{
            $vidStmt = $mysqli->prepare("SELECT * FROM records WHERE category=?");
            $vidStmt->bind_param("s",$categorySelected);
          }
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
              <input type='hidden' name='rented' value='$rented'>
              <input type='hidden' name='id' value='$id'>
              $rentText[$rented]
              <input type='submit' value='$rentButton[$rented]'>
            </form>
            <td>  <form name='delete' action = $SELF method ='post'>
              <input type='hidden' name='ACTION' value='deleteVideo'>
              <input type='hidden' name='id' value='$id'>
              <input type='submit' value='Delete Video'>
            </form>
            ";
          }
          ?>
        </table>

    </body>
</html>

<?php fclose($GLOBALS['LogFile']); ?>