<?php
// session_start();
ini_set("auto_detect_line_endings",true);
require_once("_base_urls.php");
if(isset($_SESSION['login']) AND $_SESSION['login'] <> '')
	require_once("_relier_edit.php");
	// user is allowed to write
else require_once("_relier.php");
	// user only allowed to read
require_once("_tasks.php");

$name = "Update recorded songs";
$canonic_url = $song_list = '';
require_once("_header.php");

echo "<h2>&nbsp;</h2>";
echo "<h2>Grindmill songs of Maharashtra — Updating recorded songs</h2><br />";
echo "<h2>&nbsp;</h2>";	
echo "<blockquote>";

if(!is_admin($login)) {
	echo "➡ <font color=red>Only admin has access to this page. Please log in!</font>";
	die();
	}

$pending = array();
$recordings_pending = "recordings_pending.txt";
$recordings_pending_file = @fopen($recordings_pending,"rb");
if($recordings_pending_file) {
	echo "<font color=green>Reading ‘".$recordings_pending."’</font><br />";
	echo "<small>";
	while(!feof($recordings_pending_file)) {
		$line = fgets($recordings_pending_file);
		if(is_integer($pos=strpos($line,"//")) AND $pos == 0) {
			echo "<font color=green>".$line."</font><br />";
			continue;
			}
		if(trim($line) == '') continue;
		$song_id = intval($line);
		if($song_id > 0) {
			echo "<a target=\"_blank\" href=\"".SITE_URL."songs.php?song_id=".$song_id."\">".$song_id."</a><br />";
			$query_update = "update ".BASE.".songs SET separate_recording = \"yes\" where song_id = \"".$song_id."\"";
			$result_update = $bdd->query($query_update);
			if(!$result_update) {
				echo "<br /><font color=red>ERROR modifying table:</font> ".$query_update."<br />";
				die();
				}
			$result_update->closeCursor();
			}
		}
	echo "End of file</small>";
	fclose($recordings_pending_file);
	}
else {
	if(is_super_admin($login)) echo "<font color=red>No ‘".$recordings_pending."’ file</font><br />";
	die();
	}
echo "</body>";
echo "</html>";
?>