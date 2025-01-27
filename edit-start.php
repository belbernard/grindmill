<?php
// session_start();
require_once("_base_urls.php");
require_once("_relier_edit.php"); // Mandatory to write in check_serious_attempt()
require_once("_tasks.php");

$login = $_SESSION['login'];
$_SESSION['try'] = 0;

$name = "Edit";
$mssg = $canonic_url = $group_label = '';

$id_start = $id_end = 0;
$reset = 0;
if(isset($_GET['reset'])) $reset = $_GET['reset'];
if(isset($_GET['start'])) $id_start = $_GET['start'];
if(isset($_GET['end'])) $id_end = $_GET['end'];
if(isset($_GET['group_label'])) $group_label = $_GET['group_label'];

$include_french = '';
if(isset($_GET['include_french'])) $include_french = $_GET['include_french'];
if(isset($_POST['include_french'])) $include_french = $_POST['include_french'];

if(isset($_POST['id_start'])) $id_start = $_POST['id_start'];
if(isset($_POST['id_end'])) $id_end = $_POST['id_end'];

if(isset($_POST['group_label'])) $group_label = $_POST['group_label'];

if(isset($_POST['id_median'])) $id_median = $_POST['id_median'];
else $id_median = '';
if(isset($_POST['nr_records'])) $nr_records = $_POST['nr_records'];
else $nr_records = '';

if($id_median > 0 AND $nr_records > 0) {
	$half_number = intval($nr_records / 2);
	$id_start = $id_median - $half_number;
	if($id_start < 1) $id_start = 1;
	$id_end = $id_median + $half_number;
	}

if($group_label <> '' AND is_editor($login) AND $reset == 0) {
	$url = "edit-songs.php?group_label=".$group_label;
	if($include_french <> '') $url .= "&include_french=ok";
	header("Location: ".$url);
	}
else if($id_start > 0 AND !ctype_digit($id_start))
	$mssg = "Incorrect start serial number = ‘".$id_start."’<br />";
else if($id_end > 0 AND !ctype_digit($id_end))
	$mssg = "Incorrect end serial number = ‘".$id_end."’<br />";
else if($id_start > $id_end)
	$mssg = "Negative range = “".$id_start." - ".$id_end."”<br />";
else if(($id_end - $id_start) > 500)
	$mssg = "Maximum range is 500. Can't accept “".$id_start." - ".$id_end."”<br />";
else if($id_median > 0 AND !ctype_digit($id_median))
	$mssg = "Incorrect start serial number = ‘".$id_median."’<br />";
else if($nr_records > 500)
	$mssg = "Maximum range is 500. Can't accept “".$nr_records."”<br />";
else if(ctype_digit($id_start) AND ctype_digit($id_end) AND is_editor($login) AND $reset == 0) {
	$url = "edit-songs.php?start=".$id_start."&end=".$id_end;
	if($include_french <> '') $url .= "&include_french=ok";
	header("Location: ".$url);
	}
	
require_once("_header.php");

echo "<h2>Edit database</h2>";

if(!identified()) {
	echo "<font color=red>You logged out, or your edit session expired.<br />You need to log in or return to the “edit start” page.</font>";
	die();
	}

echo "<blockquote><font color=red>".$mssg."</font></blockquote>";

echo "<table border=1>";
echo "<tr>";
echo "<td>Range of songs:</td>";
if($id_start > 0) $start = $id_start;
else $start = '';
if($id_end > 0) $end = $id_end;
else $end = '';
echo "<td><form name=\"search\" method=\"post\" action=\"edit-start.php\" enctype=\"multipart/form-data\">";
echo "#<input type='text' name='id_start' size='12' value=\"".$start."\">";
echo " to ";
echo "#<input type='text' name='id_end' size='12' value=\"".$end."\"><br /><small>e.g. 79971 to 79981<br />(less than 500 records)</small>";
echo "</td>";
echo "<td rowspan=4 style=\"vertical-align:middle;\">";
echo "<input type=\"submit\" class=\"button\" value=\"EDIT\">";
echo "</td>";
echo "</tr>";

echo "<tr>";
echo "<td><span style=\"color:red;\">or</span> Sequence of songs:</td>";
echo "<td>";
echo "<input type='text' name='nr_records' size='3' value=\"".$nr_records."\">";
echo " records around ";
echo "#<input type='text' name='id_median' size='12' value=\"".$id_median."\"><small><br />(less than 500 records)</small>";
echo "</td>";
echo "</tr>";

echo "<tr>";
echo "<td><span style=\"color:red;\">or</span> Group:</td>";
echo "<td>";
echo "<input type='text' name='group_label' size='20' value=\"".$group_label."\">";
echo "</td>";
echo "</tr>";

echo "<tr>";
echo "<td colspan=\"2\">";
echo "<input type=\"checkbox\" name=\"include_french\" value=\"ok\" ";
if($include_french <> '') echo "checked";
else echo "unchecked";
echo " />";
echo "&nbsp;Include translations in French";
echo "</td>";
echo "</tr>";

echo "</form>";
echo "</table>";

echo "</body>";
echo "</html>";
?>