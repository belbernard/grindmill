<?php
// session_start();
require_once("_base_urls.php");
require_once("_relier.php");
require_once("_tasks.php");

$name = "Recordings";
$canonic_url = SITE_URL."recordings.php";
require_once("_header.php");
echo "<h2>&nbsp;</h2>";
echo "<h2>Grindmill songs of Maharashtra — Recordings</h2><br />";

if(isset($_GET['choice'])) $choice = $_GET['choice'];
else $choice = '';
if(isset($_GET['tape_id'])) $tape_id_display = $_GET['tape_id'];
else $tape_id_display = '';

if(is_admin($login)) {
	if(isset($_POST['export_index'])) {
		$export_index = $_POST['export_index'];
		echo "<blockquote><span style=\"color:MediumTurquoise;\">Exporting </span>".$export_index."<br />";
		echo "<form name=\"select_all\" method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
		export_index($login,$export_index);
		echo "<br /><input type=\"submit\" class=\"button\" value=\"OK\">";
		echo "</form></blockquote>";
		die();
		}
	if(isset($_GET['list_of_singers']) AND $_GET['list_of_singers'] == "yes") {
		echo "<blockquote><span style=\"color:MediumTurquoise;\">Exporting list of singers</span><br />";
		echo "<form name=\"select_all\" method=\"post\" action=\"recordings.php\" enctype=\"multipart/form-data\">";
		list_of_singers($login);
		echo "<br /><input type=\"submit\" class=\"button\" value=\"OK\">";
		echo "</form></blockquote>";
		die();
		}
	}
echo "<ul>";
if(is_admin($login)) {
	echo "<li><a target=\"_blank\" href=\"edit-tunes.php\">Edit/create tunes</a></li>";
	echo "<li><a href=\"recordings.php?list_of_singers=yes\">Export list of singers</a></li>";
	}
if($choice <> "all") echo "<li><span style=\"color:red;\">➡</span> <a href=\"recordings.php?choice=all\">Detailed list of segments</a></li>";
else echo "<li><span style=\"color:red;\">➡</span> <a href=\"recordings.php\">Return to simple display</a></li>";
echo "</ul>";

$tune_comment = "Sample(s) of the tune used in this section and its interpretation on the sarangi by V.P. Singh";

echo "<table class=\"recording\">";

$table_header = "<th class=\"tight\" style='text-align:center;'>DAT index</th><th class=\"tight\" style='text-align:center;'>Start<br />h:mn:s</th><th class=\"tight\" style='text-align:center;'>End<br />h:mn:s</th><th class=\"tight\" style='text-align:center;'>Duration<br />h:mn:s</th><th class=\"tight\" style='text-align:center;'>Songs</th><th class=\"tight\">Village / Hamlet</th><th class=\"tight\">Taluka</th><th class=\"tight\">District</th><th class=\"tight\">Date</th><th class=\"tight\">Part</th><th class=\"tight\">Tune (<a href=\"\" title=\"".$tune_comment."\">?</a>)</th><th class=\"tight\">Comment</th>";
if($choice == "all") echo "<tr>".$table_header."</tr>";
$order_by = "recording_DAT_index";
$query = "SELECT * FROM ".BASE.".recordings WHERE recording_duration <> \"???\" ORDER BY ".$order_by."";
$result = $bdd->query($query);
$total_linked_songs = 0;

$tape = 0; $missing = FALSE;

while($ligne = $result->fetch()) {
	$DAT_index = $ligne['recording_DAT_index'];
	$location_id = $ligne['recording_location_id'];
	$location_features = location_features($location_id);
	$table = explode('-',$DAT_index);
	$newtape = intval($table[1]);
	$tape_id = $table[0]."-".$table[1];
	if($newtape <> $tape) {
		$url_ogg = OGG_URL.strtolower($tape_id).".ogg";
		$url_mp3 = MP3_URL.strtolower($tape_id).".mp3";
		$villages_english = '';
		if($choice <> "all" AND $tape_id <> $tape_id_display) {
			$query_locations = "SELECT DISTINCT recording_location_id FROM ".BASE.".recordings WHERE recording_DAT_index LIKE \"".$tape_id."%\"";
			$result_locations = $bdd->query($query_locations);
			$n_locations = $result_locations->rowCount();
			while($ligne_locations = $result_locations->fetch()) {
				$location_id = $ligne_locations['recording_location_id'];
				$location_features = location_features($location_id);
				$villages_english .= "<li><a target=\"_blank\" href=\"location.php?location_id=".$location_id."\">".$location_features['village_devanagari']." ".$location_features['village_english']."</a>";
				if($location_features['hamlet_english'] <> '')
					$villages_english .= " ➡ <i>hamlet</i> ".$location_features['hamlet_devanagari']." ".$location_features['hamlet_english'];
				$villages_english .= " (".$location_features['district_english'].")";
				
				$query_date = "SELECT recording_date FROM ".BASE.".recordings WHERE recording_DAT_index LIKE \"".$tape_id."%\" AND recording_location_id = \"".$location_id."\"";
				$result_date = $bdd->query($query_date);
				$ligne_date = $result_date->fetch();
				$result_date->closeCursor();
				$recording_date = $ligne_date['recording_date'];
				$villages_english .= "<small> ÷ ".$recording_date."</small>";
				$query_singers = "SELECT DISTINCT performer_id FROM ".BASE.".songs WHERE recording_DAT_index LIKE \"".$tape_id."%\" AND location_id = \"".$location_id."\"";
				$result_singers = $bdd->query($query_singers);
				$n_singers = $result_singers->rowCount();
				$first = TRUE;
				$villages_english .= "<small>";
				while($ligne_singer = $result_singers->fetch()) {
					$performer_id = $ligne_singer['performer_id'];
					$performer_names = performer_names($performer_id);
					$name = $performer_names['performer_name_english'];
					if($first) $villages_english .= "<br />";
					else $villages_english .= ", ";
					$first = FALSE;
					$villages_english .= "<a target=\"_blank\" href=\"performer.php?performer_id=".$performer_id."\">".$name."</a>";
					}
				$result_singers->closeCursor();
				$villages_english .= "</small></li>";
				}
			$result_locations->closeCursor();
			}
		if($tape_id == $tape_id_display) echo "<tr id=\"".$tape_id."\">".$table_header."</tr>";
		echo "<tr>";
		$colspan = 12;
		if(is_admin($login) OR $choice <> "all") {
			echo "<td class=\"tight\" style=\"font-size:100%; text-align:center; vertical-align:middle; white-space:nowrap;\" colspan=\"1\">";
			if(($choice == "all" OR $tape_id == $tape_id_display) AND is_admin($login)) {
				echo "<b><a target=\"_blank\" title=\"Edit this tape\" href=\"edit-recordings.php?tape_id=".$tape_id."\">EDIT<br />".$tape_id;
				echo "<br />↓↓↓</a></b>";
				}
			else if($choice <> "all") {
				echo "<b>".$tape_id."</b>";
				if($tape_id <> $tape_id_display) echo "<br />(<a title=\"Detail of this tape\" href=\"recordings.php?tape_id=".$tape_id."#".$tape_id."\">detail</a>)";
				}
			echo "</td>";
			$colspan--;
			}
		if($villages_english <> '') {
			echo "<td class=\"tight\" style=\"font-size:100%; vertical-align:middle;\" colspan=\"8\">";
			echo "<ul>";
			echo $villages_english;
			echo "</ul>";
			echo "</td>";
			$colspan -= 8;
			}
		echo "<td class=\"tight\" style=\"text-align:center; vertical-align:middle;\" colspan=\"".$colspan."\">";
		if(in_array($tape_id,$missing_tapes)) {
			echo "<h3 style=\"text-align:center; vertical-align:middle;\">Tape ".$tape_id." (missing)</h3>";
			$missing = TRUE;
			}
		else {
			echo "<h3 style=\"text-align:center; vertical-align:middle;\">".SOUND_ICON."&nbsp;Listen to full tape <a href=\"".$url_ogg."\" title=\"Listen to this tape\" target=\"_blank\">".$tape_id;
			echo "</a> (generally starts with 2mn silence)</h3>";
	//		echo $url_mp3."<br />";
			echo "<audio  style=\"vertical-align:middle;\" controls preload=\"none\">";
			echo "<source src=\"".$url_ogg."\" type=\"audio/ogg\">";
			echo "<source src=\"".$url_mp3."\" type=\"audio/mpeg\">";
			echo "Your browser does not support the audio element.";
			echo "</audio>";
			echo "<br /></td>";
			$missing = FALSE;
			}
		if(in_array($tape_id,$missing_chunks)) $nochunks = TRUE;
		else $nochunks = FALSE;
		echo "</tr>";
		$tape = $newtape;
		}
	if($choice <> "all" AND $tape_id <> $tape_id_display) continue;
	echo "<tr style=\"vertical-align:middle;\">";
	
	$url = OGG_URL.strtolower($tape_id)."/".$table[2].".ogg";
	echo "<td class=\"tight\" style=\"text-align:center; white-space:nowrap;\">".$DAT_index;
	if(!$missing AND !$nochunks) {
		echo "<br />(<a href=\"".$url."\" title=\"Listen to this section\" target=\"_blank\">listen</a>)";
		}
	echo "</td>";
	echo "<td class=\"tight\" style=\"text-align:right;\">".$ligne['time_code_start']."&nbsp;</td>";
	echo "<td class=\"tight\" style=\"text-align:right;\">".$ligne['time_code_end']."&nbsp;</td>";
	echo "<td class=\"tight\" style=\"text-align:center;\">".$ligne['recording_duration']."</td>";	
	$query_count = "SELECT count(*) from ".BASE.".songs WHERE recording_DAT_index = \"".$DAT_index."\"";
	$result_count = $bdd->query($query_count);
	$number_songs = $result_count->fetchColumn();
	$result_count->closeCursor();
	$total_linked_songs += $number_songs;
	if($number_songs > 0) {
		echo "<td class=\"tight\" style='text-align:center; white-space:nowrap;'><a target=\"_blank\" title=\"Display ".$number_songs." songs\" href=\"".SITE_URL."songs.php?recording_DAT_index=".$DAT_index."\">".$number_songs." songs</a>";
		if(identified() AND !$missing AND !$nochunks) {
			echo "<form name=\"select_all\" method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
			echo "<input type=\"hidden\" name=\"export_index\" value=\"".$DAT_index."\">";
			echo "<input type=\"submit\" class=\"button\" value=\"export\">";
			echo "</form>";
			}
		}
	else echo "<td class=\"tight\">";
	echo "</td>";
	
	echo "<td class=\"tight\" style=\"text-align:center; white-space:nowrap;\"><a target=\"_blank\" title=\"Details on this village\" href=\"".SITE_URL."location.php?location_id=".$location_id."\">".$location_features['village_devanagari']." - ".$location_features['village_english']."</a>&nbsp;";
	if($location_features['hamlet_devanagari'] <> '')
		echo "/&nbsp;".$location_features['hamlet_devanagari']." - ".$location_features['hamlet_english']."</td>";
//	else echo "<td class=\"tight\"></td>";
	
	if($location_features['taluka_devanagari'] <> '')
		echo "<td class=\"tight\"  style=\"text-align:center; white-space:nowrap;\"><a target=\"_blank\" title=\"Show all locations\" href=\"".SITE_URL."location.php?taluka_devanagari=".$location_features['taluka_devanagari']."\">".$location_features['taluka_devanagari']."</a> - <a target=\"_blank\" title=\"Show all locations\" href=\"".SITE_URL."location.php?taluka_english=".$location_features['taluka_english']."\">".$location_features['taluka_english']."</a></td>";
	else echo "<td class=\"tight\"></td>";
	
	if($location_features['district_devanagari'] <> '')
		echo "<td class=\"tight\"  style=\"text-align:center; white-space:nowrap;\"><a target=\"_blank\" title=\"Show all locations\" href=\"".SITE_URL."location.php?district_devanagari=".$location_features['district_devanagari']."\">".$location_features['district_devanagari']."</a> - <a target=\"_blank\" title=\"Show all locations\" href=\"".SITE_URL."location.php?district_english=".$location_features['district_english']."\">".$location_features['district_english']."</a></td>";
	else echo "<td class=\"tight\"></td>";
	
	echo "<td class=\"tight\" style=\"text-align:center; white-space:nowrap;\">".$ligne['recording_date']."&nbsp;</td>";
	echo "<td class=\"tight\" style=\"text-align:center;\">".$ligne['recording_part']."&nbsp;</td>";
	
	$tune = intval($ligne['tune_id']);
	$query_tune = "SELECT * FROM ".BASE.".tunes WHERE tune_id = \"".$tune."\"";
	$result_tune = $bdd->query($query_tune);
	$ligne_tune = $result_tune->fetch();
	$result_tune->closeCursor();
	$notation = $ligne_tune['notation'];
	$comment_tune = trim($ligne_tune['comment']);
	if($tune < 10) $tune = "0".$tune;
	$url = OGG_URL."TUNES/".$tune.".ogg";
	if($ligne['tune_id'] <> '') {
		echo "<td class=\"tight\" style=\"text-align:center;\">";
		echo "<a href=\"".$url."\" target=\"_blank\" title=\"".$notation."\">#".$tune."</a><br />(<a href=\"".$url."\"  target=\"_blank\" title=\"".$tune_comment."\">listen</a>)";
		echo "</td>";
		}
	else echo "<td class=\"tight\"></td>";
	echo "<td class=\"tight\">".$ligne['recording_comment'];
	if($comment_tune <> '') {
		if($ligne['recording_comment'] <> '') echo "<br />-----<br />";
		echo $comment_tune;
		}
	echo "</td>";
	echo "</tr>";
	}
$result->closeCursor();
echo "</table>";
if($choice == "all") {
	$query = "SELECT count(*) FROM ".BASE.".songs WHERE recording_DAT_index <> \"\"";
	$result_count = $bdd->query($query);
	$recorded_songs = $result_count->fetchColumn();
	$result_count->closeCursor();
	echo "<span style=\"color:red;\">".$total_linked_songs."</span> songs are correctedly linked on this table. The SONGS table mentions <span style=\"color:red;\">".$recorded_songs."</span> recorded songs. Therefore <span style=\"color:red;\">".($recorded_songs - $total_linked_songs)."</span> are not yet properly linked.";
	}
echo "</body>";
echo "</html>";

// =============== FUNCTIONS ================

function list_of_singers($login) {
	global $bdd;
	$olddir =  getcwd();
	chdir("WORK_FILES");
	if(!file_exists($login)) {
		echo "<span style=\"color:red;\">Created ‘".$login."’ folder</span><br />";
		$cmd = "mkdir ".$login;
		exec($cmd);
		}
	if(!file_exists($login)) {
		echo "<span style=\"color:red;\">ERROR: ‘".$login."’ folder could not be created</span><br />";
		return;
		}
	chdir($login);
	$filename = "list_of_singers.txt";
	$export_file = fopen($filename,'w');
	fprintf($export_file,"%s\r\n","Date\tLast index\tSinger ID\tSinger name\tLocation ID\tVillage\tHamlet\tTaluka\tValley\tDistrict");
	$query = "SELECT DISTINCT(performer_id) FROM ".BASE.".songs WHERE recording_DAT_index <> \"\"  ORDER BY performer_id";
	$result = $bdd->query($query);
	while($ligne = $result->fetch()) {
		$performer_id = $ligne['performer_id'];
		$performer_name = performer_names($performer_id);
		$query2 = "SELECT location_id FROM ".BASE.".performers WHERE performer_id = \"".$performer_id."\"";
		$result2 = $bdd->query($query2);
		$ligne2 = $result2->fetch();
		$result2->closeCursor();
		$location_id = $ligne2['location_id'];
		$location_features = location_features($location_id);
		$village = $location_features['village_english'];
		$hamlet = $location_features['hamlet_english'];
		$taluka = $location_features['taluka_english'];
		$valley = $location_features['valley_english'];
		$district = $location_features['district_english'];
		$query3 = "SELECT recording_DAT_index FROM ".BASE.".songs WHERE performer_id = \"".$performer_id."\" ORDER BY recording_DAT_index DESC";
		$result3 = $bdd->query($query3);
		$ligne3 = $result3->fetch();
		$result3->closeCursor();
		$recording_DAT_index = $ligne3['recording_DAT_index'];
		$table = explode('-',$recording_DAT_index);
		$newtape = intval($table[1]);
		$tape_id = $table[0]."-".$table[1];
		
		$query4 = "SELECT recording_date FROM ".BASE.".recordings WHERE recording_DAT_index LIKE \"".$tape_id."%\" ORDER BY recording_date DESC";
		$result4 = $bdd->query($query4);
		$ligne4 = $result4->fetch();
		$result4->closeCursor();
		$recording_date = $ligne4['recording_date'];
//		echo $performer_name['performer_name_english']." ".$location_id."<br />";
	
		fprintf($export_file,"%s\r\n",$recording_date."\t".$recording_DAT_index."\t".$performer_id."\t".$performer_name['performer_name_english']."\t".$location_id."\t".$village."\t".$hamlet."\t".$taluka."\t".$valley."\t".$district);
		}
	$result->closeCursor();
	fclose($export_file);
	echo "<br /><span style=\"color:red;\">Please download the</span> <a href=\"WORK_FILES/".$login."/".$filename."\">list of singers</a><br />";
	return;
	}
	
function export_index($login,$export_index) {
	global $bdd;
	$olddir =  getcwd();
	chdir("WORK_FILES");
	if(!file_exists($login)) {
		echo "<span style=\"color:red;\">Created ‘".$login."’ folder</span><br />";
		$cmd = "mkdir ".$login;
		exec($cmd);
		}
	if(!file_exists($login)) {
		echo "<span style=\"color:red;\">ERROR: ‘".$login."’ folder could not be created</span><br />";
		return;
		}
	chdir($login);
	$filename = $export_index."_export.txt";
	$export_file = fopen($filename,'w');
	fprintf($export_file,"%s\r\n","Date\tSound file\tSubject\tNumber of songs\tSong ID sequence\tNo. of Performers\tName of Performer\tPerformer ID\tLocation ID\tVillage\tHamlet\tTaluka\tValley\tDistrict\tState\tCorpus\tLink");
	$song_ids = $performer_ids = $performer_names = '';
	$query = "SELECT * FROM ".BASE.".songs WHERE recording_DAT_index = \"".$export_index."\" ORDER BY time_code_start";
	$result = $bdd->query($query);
	$number_of_songs = $result->rowCount();
	if($number_of_songs == 0) {
		echo "<span style=\"color:red;\">Recording DAT index [".$export_index."] => no songs</span>";
		return;
		}
	while($ligne = $result->fetch()) {
		if($song_ids <> '') $song_ids .= " / ";
		$song_ids .= $ligne['song_id'];
		}
	$result->closeCursor();
	$query = "SELECT DISTINCT(performer_id) from ".BASE.".songs WHERE recording_DAT_index = \"".$export_index."\" ORDER BY performer_id";
	$result = $bdd->query($query);
	$number_performers = $result->rowCount();
	while($ligne = $result->fetch()) {
		if($performer_ids <> '') $performer_ids .= " / ";
		$performer_ids .= $ligne['performer_id'];
		if($performer_names <> '') $performer_names .= " / ";
		$performer_name = performer_names($ligne['performer_id']);
		$performer_names .= $performer_name['performer_name_english'];
		}
	$result->closeCursor();
	$query = "SELECT * FROM ".BASE.".recordings WHERE recording_DAT_index = \"".$export_index."\"";
	$result = $bdd->query($query);
	$ligne = $result->fetch();
	$date = $ligne['recording_date'];
	$location_id = $ligne['recording_location_id'];
	$location_features = location_features($location_id);
	$village = $location_features['village_english'];
	$hamlet = $location_features['hamlet_english'];
	$taluka = $location_features['taluka_english'];
	$valley = $location_features['valley_english'];
	$district = $location_features['district_english'];
	$state = $location_features['state'];
	$corpus = $location_features['corpus'];
	$table = explode('-',$export_index);
	$tape_id = $table[0]."-".$table[1];
	$link = OGG_URL.strtolower($tape_id)."/".$table[2].".ogg";
	
	fprintf($export_file,"%s\r\n",$date."\t".$export_index."\t"."\t".$number_of_songs."\t".$song_ids."\t".$number_performers."\t".$performer_names."\t".$performer_ids."\t".$location_id."\t".$village."\t".$hamlet."\t".$taluka."\t".$valley."\t".$district."\t".$state."\t".$corpus."\t".$link);
	
	fclose($export_file);
	echo "<small>".$song_ids."</small><br />";
	echo "<span style=\"color:red;\">Please download the</span> <a href=\"WORK_FILES/".$login."/".$filename."\">export file</a> ";
	return;
	}
?>