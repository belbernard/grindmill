<?php
// session_start();
ini_set("auto_detect_line_endings",true);
require_once("_base_urls.php");
require_once("_relier.php");
require_once("_tasks.php");

if(isset($_GET['mode'])) $mode = $_GET['mode'];
else $mode = '';
	
$name = "Untranslated songs";
$canonic_url = '';
require_once("_header.php");

echo "<h2>&nbsp;</h2>";
echo "<h2>Grindmill songs of Maharashtra — Untranslated songs</h2><br />";
echo "<h2>&nbsp;</h2>";	
echo "<blockquote>";

if(!is_translator($login)) {
	echo "➡ <font color=red>Only translators have access to this page. Please log in!</font>";
	die();
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

$this_class = '';
if(isset($_GET['class'])) $this_class = trim($_GET['class']);
$mode_display = '';
if(isset($_GET['display'])) $mode_display = trim($_GET['display']);
if(isset($_POST['only_class']) AND $_POST['only_class'] == "yes") {
	$this_class = trim($_POST['this_class']);
	}
$this_class = str_replace(' ','',$this_class);
$this_class = str_replace('—','-',$this_class);

$url_all = SITE_URL."missing_translations.php?mode=short&display=all";
$url_some = SITE_URL."missing_translations.php?mode=short";
$url_text_all = SITE_URL."missing_translations.php?display=all";
$url_text_some = SITE_URL."missing_translations.php";
	
echo "<p>➡ <a href=\"".$url_text_some."\">Display song texts (500 songs)</a></p>";
echo "<p>➡ <a href=\"".$url_text_all."\">Display song texts (all songs)</a> ➡ VERY SLOW</p>";
echo "<p>➡ <a href=\"".$url_some."\">Display list</a> (only 500 songs)</p>";
echo "<p>➡ <a href=\"".$url_all."\">Display list (all songs)</a> ➡ SLOW</p>";
echo "<form name=\"display_class\" method=\"post\" action=\"missing_translations.php\" enctype=\"multipart/form-data\">";
echo "<p>➡ Display all song texts in class: ";
echo "<input type=\"hidden\" name = \"only_class\" value = \"yes\" />";
echo "<input type=\"text\" name=\"this_class\" size=\"10\" value=\"".$this_class."\">";
echo "&nbsp;<input type=\"submit\" class=\"button\" value=\"DISPLAY\">";
echo "</p>";
echo "</form>";

if($this_class <> '') {
	$url = SITE_URL."missing_translations.php?class=".$this_class;
	echo "URL of this selected display: <a href=\"".$url."\">".$url."</a>";
	}
echo "</blockquote>";

$query = "SELECT song_id FROM ".BASE.".workset";
$result = $bdd->query($query);
if($result) {
	while($ligne = $result->fetch()) {
		$song_id = $ligne['song_id'];
//		echo $song_id." ";
		$pending[$song_id] = TRUE;
		}
	}

$number_songs = array();
$number_of_these_pending = 0;
$order_by = "semantic_class_id";
$query = "SELECT DISTINCT(semantic_class_id) FROM ".BASE.".songs WHERE translation_english = \"\"";
if($this_class <> '') {
	$query = "SELECT DISTINCT(semantic_class_id) FROM ".BASE.".classification WHERE semantic_class LIKE \"".$this_class."%\" ORDER BY semantic_class";
	}
$result = $bdd->query($query);
if(!$result) {
	echo "Incorrect query:<br />".$query;
	die();
	}
$n = $result->rowCount();
if($n == 0) {
	echo "No untranslated song found in class ‘".$this_class."’";
	die();
	}
while($ligne = $result->fetch()) {
//	echo $ligne['semantic_class_id']."<br />";
	$semantic_class_id = $ligne['semantic_class_id'];
	
	$query2 = "SELECT count(*) from ".BASE.".songs WHERE semantic_class_id = \"".$semantic_class_id."\" AND translation_english = \"\"";
	$result2 = $bdd->query($query2);
	$n = $result2->fetchColumn();
	$result2->closeCursor();
	
	$query2 = "SELECT song_id FROM ".BASE.".songs WHERE translation_english = \"\" AND semantic_class_id = \"".$semantic_class_id."\"";
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
if($this_class == '') {
	natsort($number_songs);
	$number_songs = array_reverse($number_songs,true);
	}
$number_classes = count($number_songs);
$i = 0;
/* foreach($number_songs as $semantic_class_id => $n) {
	echo "(".$semantic_class_id.") ".$n."<br />";
	$i++;
	} */


// echo "<center>";
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
	$result_class = $bdd->query($query_class);
	$ligne_class = $result_class->fetch();
	$semantic_class = $ligne_class['semantic_class'];
	$semantic_class_title = $ligne_class['semantic_class_title'];
	$semantic_class_title_prefix = $ligne_class['semantic_class_title_prefix'];
	echo "<tr>";
	if($mode == "short") echo "<td>";
	else echo "<td colspan=\"4\" style='text-align:center; white-space:nowrap;'>";
	echo "(".$semantic_class_id.") <b>".$semantic_class."</b> <a target=\"_blank\" title=\"Display all songs in this class\" href=\"".SITE_URL."songs.php?semantic_class_id=".$semantic_class_id."\">".$semantic_class_title_prefix." / ".$semantic_class_title."</a> (".$n." songs)";
	echo "</td>";
	echo "</tr>";
	if($mode == "short") {
		$query = "SELECT song_id FROM ".BASE.".songs WHERE translation_english = \"\" AND semantic_class_id = \"".$semantic_class_id."\" ORDER BY song_number";
		echo "<tr><td class=\"tight\">";
		}
	else
		$query = "SELECT * FROM ".BASE.".songs WHERE translation_english = \"\" AND semantic_class_id = \"".$semantic_class_id."\" ORDER BY song_number";
	$result = $bdd->query($query);
	$first = TRUE;
	while($ligne = $result->fetch()) {
		$song_id = $ligne['song_id'];
		if(isset($pending[$song_id]) AND $pending[$song_id]) {
	//		$number_of_these_pending++;
			continue;
			}	
		$total_songs++;
		if($mode == "short") {
			if(!$first) echo ", ";
			$first = FALSE;
			echo "<a target=\"_blank\" title=\"Display song\" href=\"".SITE_URL."songs.php?song_id=".$song_id."\">".$song_id."</a>";
			if($mode_display <> "all" AND $total_songs > 500) {
				echo "</td></tr></table><br />➡ <font color=red>Sorry, only 500 songs can be displayed in this mode !<br />&nbsp;Try: </font><a href=\"".$url_all."\">".$url_all."</a> (VERY SLOW)";
				die();
				}
			continue;
			}
		else if($total_songs > 500) {
			echo "</table><br />➡ <font color=red>Sorry, only 500 songs can be displayed in this mode !<br />&nbsp;Try: </font><a href=\"".$url_text_all."\">".$url_text_all."</a> (VERY SLOW)";
			die();
			}
		$DAT_index = trim($ligne['recording_DAT_index']);
		$devanagari = $ligne['devanagari'];
		$performer_id = $ligne['performer_id'];
		$performer_names = performer_names($performer_id);
		$performer_name_devanagari = $performer_names['performer_name_devanagari'];
		$location_id = $ligne['location_id'];
		$location_features = location_features($location_id);
		$village_name_devanagari = $location_features['village_devanagari'];
		echo "<tr>";
		echo "<td class=\"tight\" style='text-align:center; white-space:nowrap; padding:0px; margin:0px;'>";
		echo "<a target=\"_blank\" title=\"Display song\" href=\"".SITE_URL."songs.php?song_id=".$song_id."\">".$song_id."</a><br /><a target=\"_blank\" title=\"Songs by this performer\" href=\"".SITE_URL."songs.php?performer_id=".$performer_id."\">".$performer_name_devanagari."</a>";
		echo "</td>";
		
		echo "<td class=\"tight\" style='text-align:center; white-space:nowrap; padding:0px; margin:0px;'>";
		if($DAT_index <> '')
			echo "<a target=\"_blank\" title=\"Songs on this track\" href=\"".SITE_URL."songs.php?recording_DAT_index=".$DAT_index."\">".$DAT_index."</a><br />";
		echo "<a target=\"_blank\" title=\"Songs in this place\" href=\"".SITE_URL."songs.php?location_id=".$location_id."\">".$village_name_devanagari."</a>";
		echo "</td>";
		echo "<td style='white-space:nowrap; padding:0px; margin:0px;'>";
		echo $devanagari;
		echo "</td>";
		echo "<td style='white-space:nowrap; padding:0px; margin:0px;'>";
		echo "&nbsp;";
		echo "</td>";
		echo "</tr>";
		}
	if($mode == "short") echo "</td></tr>";
	$result->closeCursor();
	}
echo "</table>";

// $number_of_these_pending = $recorded_songs - $total_songs;
echo "These <font color=red>".$total_songs."</font> songs need translations, and <font color=red>".$number_of_these_pending."</font> translations are pending";
// echo "</center>";
echo "</body>";
echo "</html>";
?>