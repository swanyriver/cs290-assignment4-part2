<?php
ini_set('display_errors', 'On');

include "storedInfo.php"; //contains hostname/username/password/databasename

//set up logfile and form action adress, category filter, error message
$LogFile = fopen("logfile.txt", "w");
$SELF = "\"http://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . "\"";
$isError = false;
$errorMsg = '';
$categorySelected = '';
$hiddenCategory = '';
$Allcats = 'AllCategories';

//connect to database with created mysqli object
$mysqli = new mysqli($hostname, $Username, $Password, $DatabaseName);
if ($mysqli->connect_errno || $mysqli->connect_error)
{
  fwrite($LogFile, "error #" . $mysqli->connect_errno . ":" . $mysqli->connect_error);
  echo "Unable to connect to database";
  fclose($logfile);
  exit();
} else fwrite($LogFile, "sucessful connection to database,  ");


//create table if it doesnt exist
$mysqli->query("CREATE TABLE IF NOT EXISTS records (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) UNIQUE NOT NULL,
  category VARCHAR(255),
  length INT UNSIGNED,
  rented BOOL DEFAULT FALSE
  )");


////////////////////////////////////////////////////////////////////////////////////////
///////////MODIFY DATABASE ACORDING TO USER ACTION DEFINED IN POST ARRAY ///////////////
////////////////////////////////////////////////////////////////////////////////////////

//check for a form action///
if(isset($_POST['ACTION'])){
  
  fwrite($LogFile, "user action:" . $_POST['ACTION'] . ",  ");

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
      $errorMsg = $errorMsg . "Name must be supplied <br>";
    }
    if (!(!isset($_POST['length']) || $_POST['length']=='' || $_POST['length'] == NULL)){
      if(!is_numeric($_POST['length'])){
        $isError = true;
        $errorMsg = $errorMsg . "Length must be numeric <br>";
      }
      else if($_POST['length']<0){
        $isError = true;
        $errorMsg = $errorMsg . "Length must a positive number <br>";
      }
    } 

    //add video to database if no errors
    if(!$isError){
      fwrite($LogFile, "adding video:{$_POST['name']}, ");
      if(!$_POST['length'])$_POST['length'] = NULL;
      if(!$_POST['categoryADD'])$_POST['categoryADD'] = NULL;
      $addstmt = $mysqli->prepare("INSERT INTO records ( name, category, length ) VALUES (?, ?, ?)");
      $addstmt->bind_param("ssi", $_POST['name'], $_POST['categoryADD'], $_POST['length']);
      if(!$addstmt->execute()){
        $isError = true;
        if($addstmt->errno==1062) $errorMsg = "{$_POST['name']} has already been added to the records";
        else $errorMsg = $errorMsg . "Error adding {$_POST['name']} to records <br>";
      }

      //IF ADDED MOVIE IS NOT IN THIS CATEGORY, DESELECT CATEGORY
      if( (isset($_POST['category']) &&  $_POST['category'] != $Allcats) && $_POST['category'] != $_POST['categoryADD']){
        $isError = true;
        $errorMsg = $errorMsg . "Your new movie is not in:{$_POST['category']}, Showing all movies <br>";
        unset($_POST['category']);

      }

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

//user filtered categories 
if( isset($_POST['category']) &&  $_POST['category'] != $Allcats ){
  fwrite($LogFile, "selected category:{$_POST['category']} ");

  //check that catagory still has entries
  $crStmt = $mysqli->prepare("SELECT COUNT(*) FROM records WHERE category = ?");
  $crStmt->bind_param("s",$_POST['category']);
  $crStmt->execute();
  $crStmt->bind_result($catCount);
  $crStmt->fetch();
  fwrite($LogFile, "has $catCount entries,   ");
  $crStmt->close();

  //if there are entries for category filter, set local variable, and html tag for form input
  //hidden input tag is inserted to all forms to carry category selection to next page reload
  if($catCount){
    $categorySelected = $_POST['category'];
    $hiddenCategory = "<input type='hidden' name='category' value='$categorySelected'>";
  } else {
    //no entries, switch to show all and inform user
    $isError = true;
    $errorMsg = $errorMsg . "There are no more {$_POST['category']} movies remaining, showing all movies <br>";
  }

}

?>

<!--////////////////////////////////////////////////////////////////////////////////////
//////DATABASE MANIPULATION COMPLETE,  GENERATE HTML////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////-->

<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title>[CS290] PHP part 2</title>
        <style type="text/css">
          th {font-weight: bold;}
          th,td {padding:5px; border: solid 1px;}
          table {border-collapse: collapse; 
            border: solid thick;
            margin: 5px;}
          #deleteAll, #categoryfilter {display: inline;}
          #controls {display: inline-block; 
            border: 5px double #00FF00;  
            padding: 5px; 
            margin: 5px;}
          #controls fieldset {margin-bottom: 10px; margin-top: 10px}
          #deleteAll {float: right;}
          #deleteAll input, .delete input:last-child {background-color: #E60000;
            font-weight: bold;}
          .delete input:last-child {background-color: #EE4D4D;}
          #catlist {width:45%; background-color: #FFFFFF;}
          #errorMsg {font-weight: bold;
            font-size: large;
            color: red;}
          .rent input:last-child {float: right; margin-left: 10px}

        </style>
    </head>


    <body>
      <?php


      ////////////////////////////////////////////////////////////////////////////////////////////////
      ////////////////FILTER AND DELETE ALL CONTROLS//////////////////////////////////////////////////
      ////////////////////////////////////////////////////////////////////////////////////////////////

      //display error messages if they exist
      if($isError)echo "<div id='errorMsg'> $errorMsg </div>";

      echo "<div id='controls'>";

      //count number of rows in database
      $fieldcountQ = $mysqli->query("SELECT COUNT(*) FROM records");
      $fieldcount = $fieldcountQ->fetch_array(MYSQLI_NUM)[0];
      $fieldcountQ->close();
      fwrite($LogFile, "rowcount: ". $fieldcount);

      //if there are any entries display filter and delete all controls
      if($fieldcount){

        //category filter dynamiclly generated
        echo "
          <form id='categoryfilter' action = $SELF method ='post'>
            <input type='hidden' name='ACTION' value='categoryfilter'>
            <input type='submit' value='Filter Videos by Category'>
            <select id='catlist' name='category'>
              <option value='$Allcats'>All Movies</option>
            ";
              
        //generate category items from sql query
        $ctgStmt = $mysqli->prepare("SELECT DISTINCT (category) FROM records WHERE category IS NOT NULL");
        $ctgStmt->execute();
        $ctgStmt->bind_result($nextCat);
        while($ctgStmt->fetch()){
          $selected = ($categorySelected == $nextCat) ? 'selected=selected' : '';
          echo "<option value='$nextCat' $selected>$nextCat</option>
          ";
        }
        $ctgStmt->close();

        echo "   
            </select>
            
          </form>

          <form id='deleteAll' action = $SELF method ='post'>
            <input type='hidden' name='ACTION' value='deleteAll'>
            <input type='submit' value='Delete All Videos'>
          </form>

          <br>
          ";
      }
      ?>

      <!--////////////////////////////////////////////////////////////////////////////////////
      ////// ADD VIDEOS FORM ////////////////////////////////////////////////////////////////
      /////////////////////////////////////////////////////////////////////////////////////-->

        <form id='addvideo' action = <?php echo $SELF; ?> method ='post'>
          <fieldset>
            <?php echo $hiddenCategory; ?>
            <input type='hidden' name='ACTION' value='addvideo'>
            <input type="submit" value="ADD VIDEO">
            Name:<input type="text" name="name">
            Category:<input type="text" name="categoryADD">
            Length (minutes):<input type="text" name="length">
          </fieldset>
        </form>
        </div>


         <!--////////////////////////////////////////////////////////////////////////////////////
        ////// GENERATED VIDEO INFORMATION TABLE FROM SQL DATABASE  /////////////////////////////
        /////////////////////////////////////////////////////////////////////////////////////-->

        <table id="videos" >
          <thead><tr><th>Name<th>Category<th>Length<th>Avalability<th></thead>
          <?php

          //inserted into checkout/return button using true:1/false:0 as array index
          $rentText = array("Avalabile","Checked Out");
          $rentButton = array("Rent","Return");

          //prepare statement for all entries of category filtered
          if(!$categorySelected){
            $vidStmt = $mysqli->prepare("SELECT * FROM records");
          }else{
            $vidStmt = $mysqli->prepare("SELECT * FROM records WHERE category=?");
            $vidStmt->bind_param("s",$categorySelected);
          }
          $vidStmt->execute();
          $vidStmt->bind_result($id,$name,$category,$length,$rented);

          //iterate over all videos in database query, creating <tr> for each
          while($vidStmt->fetch()){
            echo "<tr>
            <td> $name <td> $category <td> $length <td> 
            <form class='rent' action = $SELF method ='post'>
              $hiddenCategory
              <input type='hidden' name='ACTION' value='rent'>
              <input type='hidden' name='rented' value='$rented'>
              <input type='hidden' name='id' value='$id'>
              $rentText[$rented]
              <input type='submit' value='$rentButton[$rented]'>
            </form>
            <td>  <form class='delete' action = $SELF method ='post'>
              $hiddenCategory
              <input type='hidden' name='ACTION' value='deleteVideo'>
              <input type='hidden' name='id' value='$id'>
              <input type='submit' value='Delete Video'>
            </form>
            ";
          }
          $vidStmt->close();
          ?>
        </table>

    </body>
</html>

<?php fclose($GLOBALS['LogFile']); ?>