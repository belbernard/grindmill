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

if(isset($_GET['mode'])) $mode = $_GET['mode'];
else $mode = '';
	
$name = "Untranslated recorded songs";
$canonic_url = $song_list = '';
require_once("_header.php");

echo "<h2>&nbsp;</h2>";
echo "<h2>Grindmill songs of Maharashtra — Untranslated recorded songs</h2><br />";
echo "<h2>&nbsp;</h2>";	
echo "<blockquote>";

if(!is_translator($login)) {
	echo "➡ <font color=red>Only translators have access to this page. Please log in!</font>";
	die();
	}

$song_list = '';
if(isset($_POST['song_list'])) {
	$song_list = $_POST['song_list'];
//	echo $_POST['song_list'];
	$table = explode(',',$song_list);
	$songs_added_to_workset = FALSE;
	$set_id = current_workset_id($login);
	for($i = 0; $i < count($table); $i++) {
		$add_id = trim($table[$i]);
		$query = "SELECT song_id FROM ".BASE.".songs WHERE song_id = \"".$add_id."\"";
		$result = $bdd->query($query);
		$n = $result->rowCount();
		$result->closeCursor();
		if($n > 0) {
			if(($other_set=other_work_set($add_id,'','submit')) > 0) {
				$other_user = set_user($other_set);
				echo "<span style=\"color:red;\">Selected song </span>#".$add_id." <span style=\"color:red;\">is already in submitted work set</span> #".$other_set." <span style=\"color:red;\">by</span> ‘".$other_user."’<br />";
				continue;
				}
			$semantic_class_id_of_song = semantic_class_id($add_id);
			$query_update = "INSERT INTO ".BASE.".workset (set_id, song_id, semantic_class_id, login) VALUES (\"".$set_id."\",\"".$add_id."\",\"".$semantic_class_id_of_song."\",\"".$login."\")";
	//		echo $query_update."<br />";
			$result_update = $bdd->query($query_update);
			if(!$result_update) {
				echo "<br /><span style=\"color:red;\">ERROR modifying table:</span> ".$query_update."<br />";
				die();
				}
			$result_update->closeCursor();
			}
		}
	echo "<span style=\"color:MediumTurquoise;\">➡ All songs have been transfered to your current work set!</span><br /><br />";
	}

$pending = array();
$translations_pending = "translations_pending.txt";
$translations_pending_file = @fopen($translations_pending,"rb");
if($translations_pending_file) {
	echo "<font color=green>Reading ‘".$translations_pending."’</font><br />";
	echo "<small>";
	while(!feof($translations_pending_file)) {
		$line = fgets($translations_pending_file);
		if(is_integer($pos=strpos($line,"//")) AND $pos == 0) {
			echo "<font color=green>".$line."</font><br />";
			continue;
			}
		if(trim($line) == '') continue;
		$song_id = intval($line);
		if($song_id > 0) $pending[$song_id] = TRUE;
		}
	echo "</small>";
	fclose($translations_pending_file);
	}
else {
	if(is_super_admin($login)) echo "<font color=red>No ‘".$translations_pending."’ file</font><br />";
	}
$translations_pending = "translations_pending_new.txt";
$translations_pending_file = @fopen($translations_pending,"rb");	
if($translations_pending_file) {
	echo "<font color=green>Reading ‘".$translations_pending."’</font><br />";
	echo "<small>";
	while(!feof($translations_pending_file)) {
		$line = fgets($translations_pending_file);
		if(is_integer($pos=strpos($line,"//")) AND $pos == 0) {
			echo "<font color=green>".$line."</font><br />";
			continue;
			}
		if(trim($line) == '') continue;
		$song_id = intval($line);
		if($song_id > 0) $pending[$song_id] = TRUE;
		}
	echo "</small>";
	fclose($translations_pending_file);
	}
else if(is_super_admin($login)) echo "<font color=red>No ‘".$translations_pending."’ file</font><br />";
if($mode == "short")
	echo "<p>➡ <a href=\"need_translations.php\">Display song texts</a></p>";
else
	echo "<p>➡ <a href=\"need_translations.php?mode=short\">Display list</a></p>";
echo "</blockquote>";

$query = "SELECT song_id FROM ".BASE.".workset WHERE status <> \"valid\"";
$result = $bdd->query($query);
if($result) {
	while($ligne = $result->fetch()) {
		$song_id = $ligne['song_id'];
	//	echo $song_id." ";
		$pending[$song_id] = TRUE;
		}
	}

$number_songs = array();
$number_of_these_pending = 0;
$order_by = "semantic_class_id";
$query = "SELECT DISTINCT(semantic_class_id) FROM ".BASE.".songs WHERE recording_DAT_index <> \"\" AND translation_english = \"\"";
$result = $bdd->query($query);
while($ligne = $result->fetch()) {
//	echo $ligne['semantic_class_id']."<br />";
	$semantic_class_id = $ligne['semantic_class_id'];
	
	$query2 = "SELECT count(*) from ".BASE.".songs WHERE semantic_class_id = \"".$semantic_class_id."\" AND recording_DAT_index <> \"\" AND translation_english = \"\"";
	$result2 = $bdd->query($query2);
	$n = $result2->fetchColumn();
	$result2->closeCursor();
	
	$query2 = "SELECT song_id FROM ".BASE.".songs WHERE recording_DAT_index <> \"\" AND translation_english = \"\" AND semantic_class_id = \"".$semantic_class_id."\"";
	$result2 = $bdd->query($query2);
	while($ligne = $result2->fetch()) {
		$song_id = $ligne['song_id'];
		if(isset($pending[$song_id]) AND $pending[$song_id]) {
			$n--;
			$number_of_these_pending++;
			}
		}
	$result2->closeCursor();
	if($n > 0) $number_songs[$semantic_class_id] = $n;
	}
$result->closeCursor();
natsort($number_songs);
$number_songs = array_reverse($number_songs,true);
$number_classes = count($number_songs);
$i = 0;
/* foreach($number_songs as $semantic_class_id => $n) {
	echo "(".$semantic_class_id.") ".$n."<br />";
	$i++;
	} */


echo "<center>";
if($mode == "short") echo "<table>";
else echo "<table class=\"recording\">";
echo "<tr>";
if($mode == "short")
	echo "<th class=\"tight\" style='text-align:center;'>Songs</th>";
else
	echo "<th class=\"tight\" style='text-align:center;'>Song</th><th class=\"tight\" style='text-align:center;'>DAT index</th><th class=\"tight\" style='text-align:center;'>Transcription</th><th class=\"tight\" style='text-align:center;'></th>";
echo "</tr>";
$total_songs = 0;

foreach($number_songs as $semantic_class_id => $n) {
	$query_class = "SELECT * FROM ".BASE.".classification WHERE semantic_class_id = \"".$semantic_class_id."\"";
//	$result_class = mysql_query($query_class);
	$result_class = $bdd->query($query_class);
//	$ligne_class = mysql_fetch_array($result_class);
	$ligne_class = $result_class->fetch();
	$semantic_class = $ligne_class['semantic_class'];
	$semantic_class_title = $ligne_class['semantic_class_title'];
	$semantic_class_title_prefix = $ligne_class['semantic_class_title_prefix'];
	echo "<tr>";
	if($mode == "short") echo "<td>";
	else echo "<td colspan=\"4\" style='text-align:center; white-space:nowrap;'>";
	echo "<b>".$semantic_class."</b> <a target=\"_blank\" title=\"Display all songs in this class\" href=\"".SITE_URL."songs.php?semantic_class_id=".$semantic_class_id."\">".$semantic_class_title_prefix." / ".$semantic_class_title."</a> (".$n." songs)";
	echo "</td>";
	echo "</tr>";
	if($mode == "short") {
		$query = "SELECT song_id FROM ".BASE.".songs WHERE recording_DAT_index <> \"\" AND translation_english = \"\" AND semantic_class_id = \"".$semantic_class_id."\" ORDER BY song_number";
		echo "<tr><td class=\"tight\">";
		}
	else
		$query = "SELECT * FROM ".BASE.".songs WHERE recording_DAT_index <> \"\" AND translation_english = \"\" AND semantic_class_id = \"".$semantic_class_id."\" ORDER BY song_number";
	$result = $bdd->query($query);
	$first = TRUE;
	while($ligne = $result->fetch()) {
		$song_id = $ligne['song_id'];
		if(isset($pending[$song_id]) AND $pending[$song_id]) {
	//		$number_of_these_pending++;
			continue;
			}	
		$total_songs++;
		if($song_list == '')
			$song_list = $song_id;
		else $song_list .= ",".$song_id;
		if($mode == "short") {
			if(!$first) echo ", ";
			$first = FALSE;
			echo "<a target=\"_blank\" title=\"Display song\" href=\"".SITE_URL."songs.php?song_id=".$song_id."\">".$song_id."</a>";
			continue;
			}
		$DAT_index = $ligne['recording_DAT_index'];
		$devanagari = $ligne['devanagari'];
		$performer_id = $ligne['performer_id'];
		$performer_names = performer_names($performer_id);
		$performer_name_devanagari = $performer_names['performer_name_devanagari'];
		$location_id = $ligne['location_id'];
		$location_features = location_features($location_id);
		$village_name_devanagari = $location_features['village_devanagari'];
		echo "<tr>";
		echo "<td class=\"tight\" style='text-align:center; white-space:nowrap;'>";
		echo "<a target=\"_blank\" title=\"Display song\" href=\"".SITE_URL."songs.php?song_id=".$song_id."\">".$song_id."</a><br /><a target=\"_blank\" title=\"Songs by this performer\" href=\"".SITE_URL."songs.php?performer_id=".$performer_id."\">".$performer_name_devanagari."</a>";
		echo "</td>";
		
		echo "<td class=\"tight\" style='text-align:center; white-space:nowrap;'>";
		echo "<a target=\"_blank\" title=\"Songs on this track\" href=\"".SITE_URL."songs.php?recording_DAT_index=".$DAT_index."\">".$DAT_index."</a><br /><a target=\"_blank\" title=\"Songs in this place\" href=\"".SITE_URL."songs.php?location_id=".$location_id."\">".$village_name_devanagari."</a>";
		echo "</td>";
		echo "<td style='white-space:nowrap;'>";
		echo $devanagari;
		echo "</td>";
		echo "<td style='white-space:nowrap;'>";
		echo "&nbsp;";
		echo "</td>";
		echo "</tr>";
		}
	if($mode == "short") echo "</td></tr>";
	$result->closeCursor();
	}
echo "</table>";

if($total_songs > 0) {
	echo "<form name=\"select_all\" method=\"post\" action=\"need_translations.php\" enctype=\"multipart/form-data\" style=\"vertical-align:middle;\">";
	echo "<input type=\"hidden\" name=\"song_list\" value=\"".$song_list."\">";
	echo "<input type=\"submit\" class=\"button\" value=\"ADD ALL THESE SONGS TO CURRENT WORK SET (or create a new one)\">";
	echo "</form><br />";
	}

$query = "SELECT count(*) FROM ".BASE.".songs WHERE recording_DAT_index <> \"\"";
$result = $bdd->query($query);
$recorded_songs = $result->fetchColumn();
$result->closeCursor();

// $number_of_these_pending = $recorded_songs - $total_songs;
echo "These <font color=red>".$total_songs."</font> songs need translations, and <font color=red>".$number_of_these_pending."</font> translations are pending out of the <font color=red>".$recorded_songs."</font> recorded songs (generally in unvalidated work sets)";
echo "</center>";
echo "</body>";
echo "</html>";
?>