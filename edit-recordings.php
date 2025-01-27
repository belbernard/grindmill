<?php
// session_start();
require_once("_base_urls.php");
if(isset($_SESSION['login']) AND $_SESSION['login'] <> '')
	require_once("_relier_edit.php");
	// user is allowed to write
else require_once("_relier.php");
	// user only allowed to read
require_once("_tasks.php");

if(isset($_GET['tape_id'])) $tape_id = $_GET['tape_id'];
else $tape_id = "create";

if($tape_id == "create")
	$name = "Create tape records";
else
	$name = "Edit ".$tape_id;
$canonic_url = '';
require_once("_header.php");

$url_this_page = "edit-recordings.php?tape_id=".$tape_id;

echo "<h2>&nbsp;</h2>";
if($tape_id <> "create") {
	echo "<h2>Grindmill songs of Maharashtra<br />Editing tape ".$tape_id."</h2><br />";

	}
else {
	echo "<h2>Grindmill songs of Maharashtra<br />Creating tape records</h2><br />";
	}

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

if(isset($_POST['new_tape_index'])) {
	$new_tape_index = intval($_POST['new_tape_index']);
	$new_tape_id = "UVS-".sprintf("%'.02d",$new_tape_index);
//	echo $new_tape_id."<br />";
	$query = "SELECT recording_DAT_index FROM ".BASE.".recordings WHERE recording_duration <> \"???\" AND recording_DAT_index LIKE \"".$new_tape_id."%\"";
	$result = $bdd->query($query);
	$n = $result->rowCount();
	$result->closeCursor();
	if($n > 0) {
		echo "<blockquote><font color=red>ERROR:</font> ".$new_tape_id." <font color=red>— this tape ID is already used!</font> </blockquote>";
		}
	else {
		for($i=1; $i < 21; $i++) {
			$recording_DAT_index = $new_tape_id."-".sprintf("%'.02d",$i);
			// Avoid duplicating records if page is reloaded
			$query = "SELECT recording_DAT_index FROM ".BASE.".recordings WHERE recording_DAT_index = \"".$recording_DAT_index."\"";
			$result = $bdd->query($query);
			$n = $result->rowCount();
			$result->closeCursor();
			if($n == 0) {
				$query_update = "INSERT INTO ".BASE.".recordings (recording_DAT_index, login, date_modified) VALUES (\"".$recording_DAT_index."\",\"".$login."\",\"".$date."\")";
				$result_update = $bdd->query($query_update);
				if(!$result_update) {
					echo "<br /><font color=red>ERROR modifying table:</font> ".$query_update."<br />";
					die();
					}
				$result_update->closeCursor();
				}
		//	else echo $recording_DAT_index." <font color=red>already exists. Page reloaded?</font><br />";
			}
		$tape_id = $new_tape_id;
		echo "<h2>".$tape_id."</h2>";
		}
	}

if($tape_id == "create") {
	$query = "SELECT DISTINCT(recording_DAT_index) FROM ".BASE.".recordings WHERE recording_duration = \"???\"";
	$result = $bdd->query($query);
	$draft = array();
	while($ligne = $result->fetch()) {
		$recording_DAT_index = $ligne['recording_DAT_index'];
		$table = explode('-',$recording_DAT_index);
		$tape = $table[0]."-".$table[1];
		$query2 = "SELECT recording_DAT_index FROM ".BASE.".recordings WHERE recording_DAT_index LIKE \"".$tape."%\" AND (recording_duration <> \"???\" OR recording_location_id > 0 OR recording_date <> '' OR recording_part <> '' OR recording_comment <> '') LIMIT 1";
		$result2 = $bdd->query($query2);
		$n = $result2->rowCount();
		if($n > 0) {
			$table = explode('-',$recording_DAT_index);
			$draft[$table[1]] = TRUE;
			}
		$result2->closeCursor();
		}
	$result->closeCursor();
	if(count($draft) > 0) {
		echo "<blockquote>The following tapes are already drafted:<ul>";
		foreach($draft as $key => $value) {
			$pending_tape_id = "UVS-".$key;
			echo "<li>".$pending_tape_id." [<a href=\"edit-recordings.php?tape_id=".$pending_tape_id."\">Edit</a>]</li>";
			}
		echo "</ul></blockquote>";
		}
	echo "<table align=\"center\">";
	echo "<form name=\"edit_tape\" method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
	echo "<tr>";
	echo "<td class=\"tight\" style=\"background-color:Cornsilk;\">";
	echo "New tape ID:";
	echo "</td>";
	echo "<td class=\"tight\" style=\"background-color:Cornsilk;\">";
	echo "UVS-<input type=\"text\" name=\"new_tape_index\" size=\"3\">";
	echo "</td>";
	echo "<td class=\"tight\" style=\"background-color:Cornsilk;\">";
	echo "<input type=\"submit\" class=\"button\" value=\"CREATE NEW TAPE\">";
	echo "</td>";
	echo "</tr>";
	echo "</form>";
	echo "</table>";
	echo "<blockquote>";
	echo "<font color=blue>Valid choices:</font><br /><br />";
	$query = "SELECT recording_DAT_index FROM ".BASE.".recordings WHERE recording_duration <> \"???\" ORDER BY recording_DAT_index DESC LIMIT 1";
	$result = $bdd->query($query);
	$ligne = $result->fetch();
	$result->closeCursor();
	$recording_DAT_index = $ligne['recording_DAT_index'];
	$table = explode('-',$recording_DAT_index);
	$max_tape = $table[1];
	$empty = TRUE;
	for($i=1; $i < $max_tape; $i++) {
		$tape_id = "UVS-".sprintf("%'.02d",$i);
		// echo $tape_id." ";
		$query = "SELECT recording_DAT_index FROM ".BASE.".recordings WHERE recording_duration <> \"???\" AND recording_DAT_index LIKE \"".$tape_id."%\"";
		$result = $bdd->query($query);
		$n = $result->rowCount();
		if($n == 0) {
			if(!$empty) echo ", ";
			echo $tape_id;
			$empty = FALSE;
			}
		}
	if(!$empty) echo ", ";
	echo "UVS-".sprintf("%'.02d",($max_tape + 1))." <font color=blue>and more…</font>";
	echo "</blockquote>";
	die();
	}

$prefix = $tape_id."-";

if(isset($_POST['create_lines'])) {
	$index_max = $_POST['index_max'];
	$imin = $index_max + 1;
	$imax = $imin + 5;
	for($i=$imin; $i < $imax; $i++) {
		$recording_DAT_index = $tape_id."-".sprintf("%'.02d",$i);
		$query_update = "INSERT INTO ".BASE.".recordings (recording_DAT_index, recording_location_id, login, date_modified) VALUES (\"".$recording_DAT_index."\",\"0\",\"".$login."\",\"".$date."\")";
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
	/*	if(is_integer(strpos($key,"old_recording_DAT_index_"))) {
			$old_recording_DAT_index = $value;
			} */
		if(is_integer(strpos($key,"recording_DAT_index_"))) {
			$new_recording_DAT_index = $value;
			}
		if(is_integer(strpos($key,"time_code_start_"))) {
			$time_code_start = fix_time_code($value);
		//	echo $time_code_start."<br />";
			if($time_code_start == '') $time_code_start = "00:00";
			}
		if(is_integer(strpos($key,"time_code_end_"))) {
			$time_code_end = fix_time_code($value);
			if($time_code_end == '') $time_code_end = "00:00";
			}
		if(is_integer(strpos($key,"duration_"))) {
			if($value <> "???") $duration = fix_time_code($value);
			else $duration = $value;
			}
		if(is_integer(strpos($key,"recording_location_id_"))) {
			$recording_location_id = intval($value);
			}
		if(is_integer(strpos($key,"recording_date_"))) {
			$recording_date = $value;
			}
		if(is_integer(strpos($key,"recording_part_"))) {
			$recording_part = $value;
			}
		if(is_integer(strpos($key,"tune_id_"))) {
			$tune_id = $value;
			}
		if(is_integer(strpos($key,"recording_comment_"))) {
			$recording_comment = reshape_entry(trim($value));
			$index = str_replace("recording_comment_",'',$key);
			$index2 = sprintf("%'.02d",$index);
			if(isset($_POST["index".$index2]))
				echo "➡ <a href=\"#index".$index2."\">Return to segment ".$index.".aif</a><br />";
			$recording_DAT_index = $tape_id."-".$index2;
			if($new_recording_DAT_index <> $recording_DAT_index) {
			//	echo $new_recording_DAT_index." ".$recording_DAT_index."<br />";
				$query_there = "SELECT recording_DAT_index FROM ".BASE.".recordings WHERE recording_DAT_index = \"".$new_recording_DAT_index."\"";
				$result_there = $bdd->query($query_there);
				$n = $result_there->rowCount();
				$result_there->closeCursor();
				if($n > 0) $message_index[$index] = "<small><font color=red>Can't change to<br />".$new_recording_DAT_index.", first delete </font>".$new_recording_DAT_index."…</small>";
				else {
					$query_delete = "DELETE FROM ".BASE.".recordings WHERE recording_DAT_index = \"".$recording_DAT_index."\"";
			//		echo $query_delete."<br />";
					$result_delete = $bdd->query($query_delete);
					$result_delete->closeCursor();
			//		$recording_DAT_index = $new_recording_DAT_index;
					$query_update = "INSERT INTO ".BASE.".recordings (recording_DAT_index, login, date_modified) VALUES (\"".$new_recording_DAT_index."\",\"".$login."\",\"".$date."\")";
					$result_update = $bdd->query($query_update);
			//		echo $query_update."<br />";
					if(!$result_update) {
						echo "<br /><font color=red>ERROR modifying table:</font> ".$query_update."<br />";
						die();
						}
					$result_update->closeCursor();
					$query_update = "UPDATE ".BASE.".songs SET recording_DAT_index = \"".$new_recording_DAT_index."\" WHERE recording_DAT_index = \"".$recording_DAT_index."\"";
					$result_update = $bdd->query($query_update);
			//		echo $query_update."<br />";
					if(!$result_update) {
						echo "<br /><font color=red>ERROR modifying table:</font> ".$query_update."<br />";
						die();
						}
					$result_update->closeCursor();
					
					$table = explode('-',$new_recording_DAT_index);
					$index3 = $table[2];
					$message_index[intval($index3)] = "<small><font color=red>Don't forget to rename:<br /></font>".$index2.".aif ➡ ".$index3.".aif</small>";
					$recording_DAT_index = $new_recording_DAT_index;
					}
				}
	//		echo "<small>".$recording_DAT_index." ".$time_code_start." ".$time_code_end." ".$duration." ".$recording_location_id." ".$recording_date." ".$recording_part." ".$tune_id." ".$recording_comment."</small><br />";
			if(isset($_POST["index_".$index])) {
				if($time_code_start == "00:00" AND $time_code_end == "00:00" AND $recording_location_id == "0") {
					$query_delete = "DELETE FROM ".BASE.".recordings WHERE recording_DAT_index = \"".$recording_DAT_index."\"";
					$result_delete = $bdd->query($query_delete);
					$result_delete->closeCursor();
					continue;
					}
				$check_box[$index] = TRUE;
				$message_index[$index] = "<small><font color=red>Can't delete:<br />";
				if($time_code_start <> "00:00" OR $time_code_end <> "00:00")
					$message_index[$index] .= "• time-code not erased<br />";
				if($recording_location_id > 0)
					$message_index[$index] .= "• recording location not empty";
				$message_index[$index] .= "</font></small>";
				}
			$query_update = "UPDATE ".BASE.".recordings SET time_code_start = \"".$time_code_start."\", time_code_end = \"".$time_code_end."\", recording_duration = \"".$duration."\", recording_location_id = \"".$recording_location_id."\", recording_date = \"".$recording_date."\", recording_part = \"".$recording_part."\", tune_id = \"".$tune_id."\", recording_comment = \"".$recording_comment."\", login = \"".$login."\", date_modified = \"".$date."\" WHERE recording_DAT_index = \"".$recording_DAT_index."\"";
			$result_update = $bdd->query($query_update);
	//		echo $query_update."<br />";
			if(!$result_update) {
				echo "<br /><font color=red>ERROR modifying table:</font> ".$query_update."<br />";
				die();
				}
			$result_update->closeCursor();
			
			$query = "SELECT song_id FROM ".BASE.".songs WHERE recording_DAT_index = \"".$recording_DAT_index."\"";
			$result = $bdd->query($query);
			while($ligne = $result->fetch()) {
				$song_id = $ligne['song_id'];
				$new_DAT_index = guess_DAT_index(TRUE,$song_id);
				if($new_DAT_index <> '' AND $new_DAT_index <> $recording_DAT_index)
					echo "<span style=\"color:red;\">➡ Song</span> #".song($song_id,$song_id)." <span style=\"color:red;\">moved to</span> ".$new_DAT_index."<br />";
				}
			$result->closeCursor();
			
			
			}
		}
	}

$url_this_page = "edit-recordings.php?tape_id=".$tape_id;

$query = "SELECT * FROM ".BASE.".recordings WHERE recording_DAT_index LIKE \"".$tape_id."%\" ORDER BY recording_DAT_index";
$result = $bdd->query($query);
echo "<table>";
echo "<form name=\"edit_tape\" method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
echo "<input type=\"hidden\" name=\"save_entries\" value = \"ok\" />";
echo "<tr>";
echo "<td colspan=\"2\"><b><sub>⇣</sub></b>←&nbsp;&nbsp;delete</td><td>start</td><td>end</td><td>dur</td><td colspan=\"2\">location</td><td>date</td><td>part</td><td>tune</td><td>comment</td><td>comment on tune</td>";
echo "</tr>";
$seconds_end_previous = 0;
while($ligne = $result->fetch()) {
	$recording_DAT_index = $ligne['recording_DAT_index'];
	$recording_part = $ligne['recording_part'];
	$table = explode('-',$recording_DAT_index);
	$index = $table[2];
	$t = $time_code_start = $ligne['time_code_start'];
	if($time_code_start == '') $time_code_start = "00:00";
	else $time_code_start = fix_time_code($time_code_start);
	$time_code_end = $ligne['time_code_end'];
	if($time_code_end == '') $time_code_end = "00:00";
	else $time_code_end = fix_time_code($time_code_end);
	$start = time_code_to_seconds($time_code_start);
	$end = time_code_to_seconds($time_code_end);
	if($end >= $start AND $end > 0)
		$duration = seconds_to_time_code($end - $start);
	else $duration = "???";
	$recording_comment = $ligne['recording_comment'];
	$recording_date = $ligne['recording_date'];
	$recording_location_id = intval($ligne['recording_location_id']);
	$location_features = location_features($recording_location_id);
	$tune_id = $ligne['tune_id'];
	if($tune_id > 0) {
		$query_tune = "SELECT * FROM ".BASE.".tunes WHERE tune_id = \"".$tune_id."\"";
		$result_tune = $bdd->query($query_tune);
		$ligne_tune = $result_tune->fetch();
		$result_tune->closeCursor();
		$comment_tune = trim($ligne_tune['comment']);
		}
	else $comment_tune = '';
	$date_modified = $ligne['date_modified'];
	$login_modified = $ligne['login'];
	$query_count = "SELECT count(*) from ".BASE.".songs WHERE recording_DAT_index = \"".$recording_DAT_index."\"";
	$result_count = $bdd->query($query_count);
	$number_songs = $result_count->fetchColumn();
	$result_count->closeCursor();
//	echo "<input type=\"hidden\" name=\"old_recording_DAT_index_".$index."\" value = \"".$recording_DAT_index."\" />";
	echo "<tr>";
	echo "<td class=\"tight\" id=\"index".$index."\" style=\"background-color:Cornsilk;\">";
	echo "<input type=\"checkbox\" name=\"index_".$index."\" value=\"ok\"";
	if(isset($check_box[$index]) AND $check_box[$index])
	echo " checked";
	echo " />";
	echo "</td>";
	echo "<td class=\"tight\" style=\"background-color:Cornsilk; white-space:nowrap;\">";
	echo "<span style=\"color:red;\">".$index.".aif</span>&nbsp;";
	echo "<input type=\"text\" name=\"recording_DAT_index_".$index."\" size=\"11\" value=\"".$recording_DAT_index."\" />";
	if(isset($message_index[$index])) echo "<br />".$message_index[$index];
	if($number_songs > 0) echo "<br />➡&nbsp;<a target=\"_blank\" title=\"Display ".$number_songs." songs\" href=\"songs.php?recording_DAT_index=".$recording_DAT_index."\">".$number_songs." songs</a>";
	echo "</td>";
	echo "<td class=\"tight\" style=\"background-color:Cornsilk;\">";
	echo "<input type=\"text\" name=\"time_code_start_".$index."\" size=\"8\" value=\"".$time_code_start."\" />";
	$seconds_start = time_code_to_seconds($time_code_start);
	if($seconds_start < $seconds_end_previous) $overlapping = TRUE;
	else $overlapping = FALSE;
	if($overlapping) echo "<br /><small><font color=green>➡&nbsp;overlapping</font></small>";
	echo "</td>";
	echo "<td class=\"tight\" style=\"background-color:Cornsilk;\">";
	$seconds_end_previous = time_code_to_seconds($time_code_end);
	echo "<input type=\"text\" name=\"time_code_end_".$index."\" size=\"8\" value=\"".$time_code_end."\" />";
	echo "</td>";
	echo "<td class=\"tight\" style=\"background-color:Cornsilk;\">";
	if($duration == "???") echo "<small><font color=red>".$duration."</font></small>";
	else echo "<small>".$duration."</small>";
	echo "<input type=\"hidden\" name=\"duration_".$index."\" value = \"".$duration."\" />";
	echo "</td>";
	echo "<td class=\"tight\" style=\"background-color:Cornsilk;\">";
	if($recording_location_id > 0) $location = $recording_location_id;
	else $location = '';
	echo "<input type=\"text\" name=\"recording_location_id_".$index."\" size=\"6\" value=\"".$location."\" />";
	echo "</td>";
	echo "<td class=\"tight\" style=\"background-color:Cornsilk;\">";
	if($location_features['village_english'] == '')
		echo "<font color=red>Unknown location</font>";
	else
		echo "<a target=\"_blank\" title=\"Details on this village\" href=\"location.php?location_id=".$recording_location_id."\">".$location_features['village_devanagari']." - ".$location_features['village_english']."</a>&nbsp;";
	if($location_features['hamlet_devanagari'] <> '')
		echo "/&nbsp;".$location_features['hamlet_devanagari']." - ".$location_features['hamlet_english'];
	echo "</td>";
	echo "<td class=\"tight\" style=\"background-color:Cornsilk;\">";
	echo "<input type=\"text\" name=\"recording_date_".$index."\" size=\"12\" value=\"".$recording_date."\" />";
	echo "</td>";
	echo "<td class=\"tight\" style=\"background-color:Cornsilk;\">";
	echo "<input type=\"text\" name=\"recording_part_".$index."\" size=\"2\" value=\"".$recording_part."\" />";
	echo "</td>";
	echo "<td class=\"tight\" style=\"background-color:Cornsilk;\">";
	echo "<input type=\"text\" name=\"tune_id_".$index."\" size=\"2\" value=\"".$tune_id."\" />";
	echo "</td>";
	echo "<td class=\"tight\" style=\"background-color:Cornsilk;\">";
		echo "<textarea name=\"recording_comment_".$index."\" ROWS=\"2\" style=\"width:150px;\">";
	echo str_replace("<br />","\n",$recording_comment);
	echo "</textarea>";
	echo "</td>";
	echo "<td class=\"tight\" style=\"background-color:Cornsilk;\">";
	echo "<small>".$comment_tune."</small>";
	echo "</td>";
	echo "<td class=\"tight\" style=\"white-space:nowrap;\">";
	echo "<small>".$date_modified;
	if($login_modified <> '')
		echo "<br />by <i>".$login_modified."</i>";
	echo "</small>";
	echo "</td>";
echo "<td class=\"tight\" style=\"background-color:Cornsilk; text-align:left;\">";
echo "<input type=\"submit\" class=\"button\" name=\"index".$index."\" value=\"SAVE ALL LINES\">&nbsp;";
echo "</td>";
	echo "</tr>";
	}
$result->closeCursor();
echo "<tr>";
echo "<td colspan=\"4\" style=\"text-align:left\">";
echo "<small><b><font color=green>↑&nbsp;Delete if checked<br />with erased time-codes<br />and erased recording location</font></b></small>";
echo "</td>";
echo "<td class=\"tight\" colspan=\"7\" style=\"background-color:Cornsilk; text-align:right;\">";
echo "<input type=\"submit\" class=\"button\" value=\"SAVE ALL THESE ENTRIES\">&nbsp;";
echo "</td>";
echo "</form>";
echo "<td class=\"tight\" colspan=\"2\" style=\"background-color:Cornsilk; text-align:right;\">";
echo "<form name=\"create_lines\" method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
echo "<input type=\"hidden\" name=\"create_lines\" value = \"ok\" />";
echo "<input type=\"hidden\" name=\"index_max\" value = \"".$index."\" />";
echo "<input type=\"submit\" class=\"button\" value=\"CREATE MORE LINES\">&nbsp;";
echo "</form>";
echo "</td>";
echo "</tr>";
echo "</table>";

echo "</body>";
echo "</html>";
?>