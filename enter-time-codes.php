<?php
// session_start();
require_once("_base_urls.php");
require_once("_relier_edit.php"); // Mandatory to write in check_serious_attempt()
require_once("_tasks.php");
require_once("_edit_tasks.php");

if(!check_serious_attempt('browse')) die();

$name = "Enter time codes";
	
require_once("_header.php");

echo "<h2>Editing time codes</h2>";

if(!identified()) {
	echo "<span style=\"color:red;\">You logged out, or your edit session expired.<br />You need to log in or return to the “edit start” page.</span>";
	die();
	}
$login = $_SESSION['login'];
$_SESSION['try'] = 0;

$old_time = time() - 3600;
$sql = "DELETE FROM ".BASE.".t_access WHERE acce_time < \"".$old_time."\"";
$result = $bdd->query($sql);
$result->closeCursor();

if(!is_editor($login)) {
	echo "<span style=\"color:red;\">Your status does not grant you access to this page.</span>";
	die();
	}
	
$date = date('Y-m-d H:i:s');

if(isset($_POST['time_code_start'])) {
	$time_code_start = trim($_POST['time_code_start']);
	if($time_code_start == '') $time_mark = "~";
	else $time_mark = '';
	$time_code_start = fix_time_code($time_code_start);
	if($time_mark == '') $time_mark = $time_code_start;
	$song_id = $_POST['song_id'];
	$query_update = "UPDATE ".BASE.".songs SET time_code_start = \"".$time_code_start."\", date_modified = \"".date("Y-m-d")."\" WHERE song_id = \"".$song_id."\"";
//	echo $query_update."<br />";
	$result_update = $bdd->query($query_update);
	if(!$result_update) {
		echo "<br /><span style=\"color:red;\">ERROR modifying table:</span> ".$query_update."<br />";
		die();
		}
	$result_update->closeCursor();
	$recording_DAT_index = guess_DAT_index(TRUE,$song_id);
	$query_there = "SELECT song_id FROM ".BASE.".song_metadata WHERE song_id = \"".$song_id."\" AND devanagari = \"\" AND roman_devanagari = \"\"";
	$result_there = $bdd->query($query_there);
	$n_there = $result_there->rowCount();
//	echo "n_there = ".$n_there."<br />";
	if($n_there > 0) $already_there_metadata = TRUE;
	else $already_there_metadata = FALSE;
	$result_there->closeCursor();
	if($already_there_metadata) {
		$query_update = "UPDATE ".BASE.".song_metadata SET time_code_start = \"".$time_mark."\", recording_DAT_index = \"".$recording_DAT_index."\" WHERE song_id = \"".$song_id."\" AND devanagari = \"\" AND roman_devanagari = \"\"";
//		echo $query_update."<br />";
		$result_update = $bdd->query($query_update);
		if(!$result_update) {
			echo "<br /><span style=\"color:red;\">ERROR modifying table:</span> ".$query_update."<br />";
			die();
			}
		$result_update->closeCursor();
		}
	else {
		if($time_mark <> "~") {
			$query_update = "INSERT INTO ".BASE.".song_metadata (song_id, time_code_start, recording_DAT_index, login, date, devanagari, roman_devanagari, remarks_marathi, remarks_english) VALUES (\"".$song_id."\",\"".$time_mark."\",\"".$recording_DAT_index."\",\"".$login."\",\"".$date."\",\"\",\"\",\"\",\"\")";
			$result_update = $bdd->query($query_update);
			if(!$result_update) {
				echo "<br /><span style=\"color:red;\">ERROR modifying table:</span> ".$query_update."<br />";
				die();
				}
			$result_update->closeCursor();
			}
		}
	}

$query = "SELECT song_id, login, date FROM ".BASE.".song_metadata WHERE (devanagari = \"\" AND roman_devanagari = \"\") ORDER BY date ASC";
$result = $bdd->query($query);
$time_before = time() - (10 * 86400);
// echo $time_before;
echo "<table>";
echo "<tr><th>Song nr</th><th></th><th></th><th>Time code</th><th></th></tr>";
$n = 0;
while($ligne = $result->fetch()) {
	$login_connect = $ligne['login'];
	$song_id = $ligne['song_id'];
	$date = $ligne['date'];
	
	$query2 = "SELECT time_code_start, recording_DAT_index, devanagari, date_modified FROM ".BASE.".songs WHERE song_id = \"".$song_id."\"";
	$result2 = $bdd->query($query2);
	$ligne2 = $result2->fetch();
	$time_code = $ligne2['time_code_start'];
	$recording_DAT_index = $ligne2['recording_DAT_index'];
	$date_modified = $ligne2['date_modified'];
	$result2->closeCursor();
	
	$table =  explode(' ',$date_modified);
	$date_time = new DateTime($table[0]);
	$time = $date_time->getTimestamp();
	if($recording_DAT_index == '') continue;
	$flag = flag_incorrect_DAT_index($song_id);
	if($flag == '' AND $time < $time_before) continue;
	$devanagari = $ligne2['devanagari'];
	echo "<tr>";
	echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
	echo "<td><a target=\"_blank\" href=\"edit-songs.php?start=".$song_id."\">".$song_id."</a></td>";
	echo "<td style=\"white-space:nowrap;\">";
	if($flag <> '') echo $flag;
	else echo "<small><b>".$recording_DAT_index."</b></small>";
	echo "<br /><small><i>".$date."</i></small></td>";
	echo "<td class=\"tight\">".$devanagari."</td>";
	echo "<td>";
	echo " <input type=\"text\" style=\"text-align:right;\" name=\"time_code_start\" size=\"8\" value=\"".$time_code."\">";
	echo "</td><td>";
	echo "<input type=\"hidden\" name=\"song_id\" value=\"".$song_id."\">";
	echo "<input type=\"submit\" name=\"enter_time_code\" class=\"button\" value=\"SAVE\"><br />";
	echo "</td></form></tr>";
	$n++;
//	if ($n > 50) die();
	}
$result->closeCursor();
echo "</table>";
echo "</body>";
echo "</html>";
?>