<?php
// session_start();
require_once("_base_urls.php");
if(isset($_SESSION['login']) AND $_SESSION['login'] <> '')
	require_once("_relier_edit.php");
	// user is allowed to write
else require_once("_relier.php");
	// user only allowed to read
require_once("_tasks.php");

$name = "Tunes";
$canonic_url = '';

require_once("_header.php");

$url_this_page = "edit-tunes.php";

echo "<h2>&nbsp;</h2>";
echo "<h2>Grindmill songs of Maharashtra<br />Editing tunes</h2><br />";

if(!identified()) {
	echo "<font color=red>You logged out, or your edit session expired.<br />You need to log in or return to the “edit start” page.</font>";
	die();
	}
$login = $_SESSION['login'];
$_SESSION['try'] = 0;

$old_time = time() - 3600;
$sql_delete = "DELETE FROM ".BASE.".t_access WHERE acce_time < \"".$old_time."\"";
$result_delete = $bdd->query($sql_delete);
$result_delete->closeCursor();

if(!is_admin($login)) {
	echo "<font color=red>Access restricted to Admin</font>";
	die();
	}
// echo $url_this_page;
$date = date("Y-m-d");

if(isset($_POST['create_lines'])) {
	$index_max = $_POST['index_max'];
	$imin = $index_max + 1;
	$imax = $imin + 5;
	for($i=$imin; $i < $imax; $i++) {
		$query_update = "INSERT INTO ".BASE.".tunes (tune_id, login, date_modified) VALUES (\"".$i."\",\"".$login."\",\"".$date."\")";
		$result_update = $bdd->query($query_update);
		if(!$result_update) {
			echo "<br /><font color=red>ERROR modifying table:</font> ".$query_update."<br />";
			die();
			}
		$result_update->closeCursor();
		}			
	}

$message_index = $check_box = array();

if(isset($_POST['save_entries'])) {
	foreach($_POST as $key => $value) {
		if(is_integer(strpos($key,"tune_id_"))) {
			$new_tune_id = $value;
			}
		if(is_integer(strpos($key,"notation_"))) {
			$notation = reshape_entry(trim($value));
			}
		if(is_integer(strpos($key,"transpose_"))) {
			$transpose = intval($value);
			}
		if(is_integer(strpos($key,"comment_"))) {
			$comment =  reshape_entry(trim($value));
			$tune_id = str_replace("comment_",'',$key);
			if($new_tune_id <> $tune_id) {
				$query_there = "SELECT tune_id FROM ".BASE.".tunes WHERE tune_id = \"".$new_tune_id."\"";
				$result_there = $bdd->query($query_there);
				$n = $result_there->rowCount();
				$result_there->closeCursor();
				if($n > 0) $message_index[$tune_id] = "<small><font color=red>Can't change to<br />".$new_tune_id.", first delete target!</font></small>";
				else {
					$query_delete = "DELETE FROM ".BASE.".tunes WHERE tune_id = \"".$tune_id."\"";
					$result_delete = $bdd->query($query_delete);
					$result_delete->closeCursor();
					$tune_id = $new_tune_id;
					$query_update = "INSERT INTO ".BASE.".tunes (tune_id, login, date_modified) VALUES (\"".$tune_id."\",\"".$login."\",\"".$date."\")";
					$result_update = $bdd->query($query_update);
					if(!$result_update) {
						echo "<br /><font color=red>ERROR modifying table:</font> ".$query_update."<br />";
						die();
						}
					$result_update->closeCursor();
					}
				}
			if(isset($_POST["index_".$tune_id])) {
				if($notation == '' AND $transpose == "0" AND $comment == '') {
					$query_delete = "DELETE FROM ".BASE.".tunes WHERE tune_id = \"".$tune_id."\"";
					$result_delete = $bdd->query($query_delete);
					$result_delete->closeCursor();
					continue;
					}
				$check_box[$tune_id] = TRUE;
				$message_index[$tune_id] = "<small><font color=red>Can't delete:<br />";
				if($notation <> '')
					$message_index[$tune_id] .= "• notation not erased<br />";
				if($transpose <> 0)
					$message_index[$tune_id] .= "• transpose not empty";
				if($comment <> '')
					$message_index[$tune_id] .= "• comment not empty";
				$message_index[$tune_id] .= "</font></small>";
				}
			$query_update = "UPDATE ".BASE.".tunes SET tune_id = \"".$tune_id."\", notation = \"".$notation."\", transpose = \"".$transpose."\", comment = \"".$comment."\", login = \"".$login."\", date_modified = \"".$date."\" WHERE tune_id = \"".$tune_id."\"";
		//	echo $query_update."<br />";
			$result_update = $bdd->query($query_update);
			if(!$result_update) {
				echo "<br /><font color=red>ERROR modifying table:</font> ".$query_update."<br />";
				die();
				}
			$result_update->closeCursor();
			}
		}
	}

$query = "SELECT * FROM ".BASE.".tunes ORDER BY tune_id";
$result = $bdd->query($query);
echo "<table>";
echo "<form name=\"edit_tape\" method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
echo "<input type=\"hidden\" name=\"save_entries\" value = \"ok\" />";
echo "<tr>";
echo "<td colspan=\"2\"><b><sub>⇣</sub></b>←&nbsp;&nbsp;delete</td><td>notation</td><td>transpose</td><td>comment</td>";
echo "</tr>";
while($ligne = $result->fetch()) {
	$tune_id = $ligne['tune_id'];
	$notation = $ligne['notation'];
	$transpose = $ligne['transpose'];
	$comment = $ligne['comment'];
	$date_modified = $ligne['date_modified'];
	$login_modified = $ligne['login'];
	echo "<tr>";
	echo "<td class=\"tight\" style=\"background-color:Cornsilk;\">";
	echo "<input type=\"checkbox\" name=\"index_".$tune_id."\" value=\"ok\"";
	if(isset($check_box[$tune_id]) AND $check_box[$tune_id])
	echo " checked";
	echo " />";
	echo "</td>";
	echo "<td class=\"tight\" style=\"background-color:Cornsilk; white-space:nowrap;\">";
	echo "<input type=\"text\" name=\"tune_id_".$tune_id."\" size=\"9\" value=\"".$tune_id."\" />";
	if(isset($message_index[$tune_id])) echo "<br />".$message_index[$tune_id];
	echo "</td>";
	echo "<td class=\"tight\" style=\"background-color:Cornsilk;\">";
		echo "<textarea name=\"notation_".$tune_id."\" ROWS=\"2\" style=\"width:150px;\">";
	echo str_replace("<br />","\n",$notation);
	echo "</textarea>";
	echo "</td>";
	echo "<td class=\"tight\" style=\"background-color:Cornsilk;\">";
	echo "<input type=\"text\" name=\"transpose_".$tune_id."\" size=\"3\" value=\"".$transpose."\" />";
	echo "</td>";
	echo "</td>";
	echo "<td class=\"tight\" style=\"background-color:Cornsilk;\">";
		echo "<textarea name=\"comment_".$tune_id."\" ROWS=\"2\" style=\"width:150px;\">";
	echo str_replace("<br />","\n",$comment);
	echo "</textarea>";
	echo "</td>";
	echo "<td class=\"tight\" style=\"white-space:nowrap;\">";
	echo "<small>".$date_modified;
	if($login_modified <> '')
		echo "<br />by <i>".$login_modified."</i>";
	echo "</small>";
	echo "</td>";
	echo "</tr>";
	}
$result->closeCursor();
echo "<tr>";
echo "<td colspan=\"3\" style=\"text-align:left\">";
echo "<small><b><font color=green>↑&nbsp;Delete if checked<br />with erased values</font></b></small>";
echo "</td>";
echo "<td class=\"tight\" colspan=\"2\" style=\"background-color:Cornsilk; text-align:right;\">";
echo "<input type=\"submit\" class=\"button\" value=\"SAVE ALL THESE ENTRIES\">&nbsp;";
echo "</td>";
echo "</form>";
echo "<td class=\"tight\" colspan=\"1\" style=\"background-color:Cornsilk; text-align:right;\">";
echo "<form name=\"create_lines\" method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
echo "<input type=\"hidden\" name=\"create_lines\" value = \"ok\" />";
echo "<input type=\"hidden\" name=\"index_max\" value = \"".$tune_id."\" />";
echo "<input type=\"submit\" class=\"button\" value=\"CREATE MORE LINES\">&nbsp;";
echo "</form>";
echo "</td>";
echo "</tr>";
echo "</table>";

echo "</body>";
echo "</html>";
?>