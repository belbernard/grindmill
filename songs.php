<?php
ini_set('max_execution_time',600);
// session_start();
require_once("_base_urls.php");
require_once("_relier_edit.php"); // Mandatory to write in check_serious_attempt()
require_once("_tasks.php");
require_once("_edit_tasks.php");

ini_set('memory_limit','300M'); // Added 21 June 2018

$query_set = "SET SQL_BIG_SELECTS=1"; // Added 21 June 2018
$bdd->query($query_set);

$time_start = time();

$translation_correction_english = ReadRewriteRules("english");

$url_songs_page = "songs.php?";
if(isset($_GET['mode'])) $mode = $_GET['mode'];
else $mode = '';
if(isset($_GET['performer_id'])) $performer_id = $_GET['performer_id'];
else $performer_id = 0;
if(isset($_GET['semantic_class_id'])) $semantic_class_id = $_GET['semantic_class_id'];
else $semantic_class_id = '';
if(isset($_GET['semantic_class_title_prefix_id'])) $semantic_class_title_prefix_id = $_GET['semantic_class_title_prefix_id'];
else $semantic_class_title_prefix_id = '';
if(isset($_GET['location_id'])) $location_id = $_GET['location_id'];
else $location_id = 0;
if(isset($_GET['song_id'])) $song_id = $_GET['song_id'];
else if(isset($_POST['song_id'])) $song_id = $_POST['song_id'];
else $song_id = 0;
// if($song_id > 0) { echo $song_id; die(); }
if(isset($_GET['group_label'])) $group_label = $_GET['group_label'];
else if(isset($_POST['group_label'])) $group_label = $_POST['group_label'];
else $group_label = '';

if($group_label <> '') {
	$query_group = "SELECT * FROM ".BASE.".groups WHERE label = \"".$group_label."\"";
	$result_group = $bdd->query($query_group);
	$ligne_group = $result_group->fetch();
	$group_id = $ligne_group['id'];
	$result_group->closeCursor();
	}
else $group_id = 0;

$n = 0;

if(isset($_GET['translation_english'])) $translation_english = urldecode($_GET['translation_english']);
else if(isset($_POST['translation_english'])) $translation_english =$_POST['translation_english'];
else $translation_english = '';
if(isset($_GET['translation_french'])) $translation_french = urldecode($_GET['translation_french']);
else if(isset($_POST['translation_french'])) $translation_french =$_POST['translation_french'];
else $translation_french = '';
if(isset($_GET['devanagari'])) $devanagari = urldecode($_GET['devanagari']);
else if(isset($_POST['devanagari'])) $devanagari = $_POST['devanagari'];
else $devanagari = '';
if(isset($_GET['roman_devanagari'])) $roman_devanagari = urldecode($_GET['roman_devanagari']);
else if(isset($_POST['roman_devanagari'])) $roman_devanagari = $_POST['roman_devanagari'];
else $roman_devanagari = '';
// echo "‘".$roman_devanagari."’<br />";

if(isset($_GET['recording_DAT_index'])) $recording_DAT_index = trim($_GET['recording_DAT_index']);
else if(isset($_POST['recording_DAT_index'])) $recording_DAT_index = trim($_POST['recording_DAT_index']);
else $recording_DAT_index = '';

$select_all = $select_all_untranslated = $select_none = FALSE;
if(isset($_POST['select_all'])) {
	if($_POST['select_all'] == "yes") $select_all = TRUE;
	if($_POST['select_all'] == "untranslated") $select_all_untranslated = TRUE;
	if($_POST['select_all'] == "no") $select_none = TRUE;
	}

$pending = array();
$translations_pending = "translations_pending.txt";
$translations_pending_file = @fopen($translations_pending,"rb");
if($translations_pending_file) {
	while(!feof($translations_pending_file)) {
		$line = fgets($translations_pending_file);
		if(is_integer($pos=strpos($line,"//")) AND $pos == 0) {
			continue;
			}
		if(trim($line) == '') continue;
		$id = intval($line);
		if($id > 0) $pending[$id] = TRUE;
		}
	fclose($translations_pending_file);
	}
$translations_pending = "translations_pending_new.txt";
$translations_pending_file = @fopen($translations_pending,"rb");	
if($translations_pending_file) {
	while(!feof($translations_pending_file)) {
		$line = fgets($translations_pending_file);
		if(is_integer($pos=strpos($line,"//")) AND $pos == 0) {
			continue;
			}
		if(trim($line) == '') continue;
		$id = intval($line);
		if($id > 0) $pending[$id] = TRUE;
		}
	fclose($translations_pending_file);
	}

if($song_id > 0) {
//	echo "song_id = ".$song_id."<br />";
	fix_translation($song_id,$translation_correction_english);
	}
$songs_added_to_workset = $songs_added_to_group = $songs_removed_from_group = $song_created = FALSE;
$songs_added_msg = '';
$songs_remarks_msg = '';
$song_list = '';
if(is_translator($login)) {
	if(isset($_POST['delete_song']) AND $_POST['delete_song'] > 0) {
		$delete_song_id = $_POST['delete_song'];
		$result = delete_song($delete_song_id,FALSE);
		}
	if(isset($_POST['create_song']) AND $_POST['create_song'] == "yes") {
		$query = "SELECT MAX(song_id) AS song_id FROM ".BASE.".songs";
		$result = $bdd->query($query);
		$ligne = $result->fetch();
		$new_song_id = $ligne['song_id'] + 1;
		$song_created = TRUE;
		$song_created_msg = "<span style=\"color:red;\">➡ Created new song</span> #<a target=\"_blank\" title=\"Details\" href=\"".SITE_URL."songs.php?song_id=".$new_song_id."\">".$new_song_id."</a>";
		$date = date("Y-m-d");
		$new_group_label = trim($_POST['new_group_label']);
		$new_group_label = str_replace(' ','_',$new_group_label);
		$new_group_label = str_replace('"','',$new_group_label);
		$new_group_label = str_replace('‘','',$new_group_label);
		$new_group_label = str_replace('’','',$new_group_label);
		$new_group_label = str_replace("'",'',$new_group_label);
		$new_semantic_class_id = trim($_POST['new_semantic_class_id']);
		$query_class = "SELECT semantic_class_title_prefix FROM ".BASE.".classification WHERE semantic_class_id = \"".$new_semantic_class_id."\"";
		$result_class = $bdd->query($query_class);
		$n = $result_class->rowCount();
		if(!$result_class OR $n == 0) {
			$semantic_class_title_prefix = '';
			$song_created_msg .= "<br /><span style=\"color:red;\">ERROR unknown semantic class:</span> #".$new_semantic_class_id;
			$new_semantic_class_id = '';
			}
		else {
			$ligne_class = $result_class->fetch();
			$result_class->closeCursor();
			$semantic_class_title_prefix = $ligne_class['semantic_class_title_prefix'];
			}
		$new_performer_id = trim($_POST['new_performer_id']);
		$performer_names = performer_names($new_performer_id);
		if($performer_names['performer_name_english'] == '') {
			$song_created_msg .= "<br /><span style=\"color:red;\">WARNING: unknown performer:</span> #".$new_performer_id;
			$new_performer_id = 0;
			}
		$new_location_id = trim($_POST['new_location_id']);
		$location_features = location_features($new_location_id);
		if($location_features['village_english'] == '') {
			$song_created_msg .= "<br /><span style=\"color:red;\">ERROR: unknown location:</span> #".$new_location_id;
			$new_location_id = 0;
			}
		$new_song_number = intval($_POST['new_song_number']);
		$new_tune_id = intval($_POST['tune_id']);
		$new_recording_DAT_index = trim($_POST['new_recording_DAT_index']);
		if($new_recording_DAT_index <> '') {
			$query_recording = "SELECT recording_location_id FROM ".BASE.".recordings WHERE recording_DAT_index = \"".$new_recording_DAT_index."\"";
			$result_recording = $bdd->query($query_recording);
			$n = $result_recording->rowCount();
			if(!$result_recording OR $n == 0) {
				$song_created_msg .= "<br /><span style=\"color:red;\">ERROR unknown recording DAT index:</span> ".$new_recording_DAT_index;
				$new_recording_DAT_index = '';
				}
			}
		$new_time_code_start = fix_time_code(trim($_POST['time_code_start']));
		$new_separate_recording = trim($_POST['separate_recording']);
		$new_recording_date = trim($_POST['recording_date']);
		if($new_separate_recording <> "yes" AND $new_separate_recording <> "no") {
			$song_created_msg .= "<br /><span style=\"color:red;\">ERROR separate recording should be ‘yes’ or ‘no’:</span> ".$new_separate_recording;
			$new_separate_recording = '';
			}
		$new_devanagari = reshape_entry($_POST['new_devanagari']);
		$new_roman_devanagari = mb_strtolower(reshape_entry($_POST['new_roman_devanagari']));
		$new_roman_devanagari = CleanGoogleQuotes($new_roman_devanagari);
		$new_translation = reshape_entry($_POST['new_translation']);
		$new_remarks_marathi = reshape_entry($_POST['new_remarks_marathi']);
		$new_remarks_english = reshape_entry($_POST['new_remarks_english']);
		$query_update = "INSERT INTO ".BASE.".songs (song_id, devanagari, roman_devanagari, language, translation_english, performer_id, location_id, semantic_class_id, semantic_class_title_prefix, song_number, tune_id, recording_DAT_index, time_code_start, remarks_marathi, remarks_english, date_modified, separate_recording, recording_date, login) VALUES (\"".$new_song_id."\",\"".$new_devanagari."\",\"".$new_roman_devanagari."\",\"".$_POST['language']."\",\"".$new_translation."\",\"".$new_performer_id."\",\"".$new_location_id."\",\"".$new_semantic_class_id."\",\"".$semantic_class_title_prefix."\",\"".$new_song_number."\",\"".$new_tune_id."\",\"".$new_recording_DAT_index."\",\"".$new_time_code_start."\",\"".$new_remarks_marathi."\",\"".$new_remarks_english."\",\"".$date."\",\"".$new_separate_recording."\",\"".$new_recording_date."\",\"".$login."\")";
		$result_update = $bdd->query($query_update);
		if(!$result_update) {
			echo "<br /><span style=\"color:red;\">ERROR modifying table:</span> ".$query_update."<br />";
			die();
			}
		$result_update->closeCursor();
		if($new_group_label <> '') $assign_group = assign_group($new_song_id,$new_group_label);
		}
	if(isset($_POST['new_group_label'])) {
		$new_group_label = trim($_POST['new_group_label']);
		$new_group_label = str_replace(' ','_',$new_group_label);
		$new_group_label = str_replace('"','',$new_group_label);
		$new_group_label = str_replace('‘','',$new_group_label);
		$new_group_label = str_replace('’','',$new_group_label);
		$new_group_label = str_replace("'",'',$new_group_label);
		}
	else $new_group_label = '';
	
	if(isset($_POST['old_group_label'])) {
		$old_group_label = trim($_POST['old_group_label']);
		$old_group_label = str_replace(' ','_',$old_group_label);
		$old_group_label = str_replace('"','',$old_group_label);
		$old_group_label = str_replace('‘','',$old_group_label);
		$old_group_label = str_replace('’','',$old_group_label);
		$old_group_label = str_replace("'",'',$old_group_label);
		}
	else $old_group_label = '';
	
	if(isset($_POST['edit_button'])) $edit_button = TRUE;
	else $edit_button = FALSE;
	if(isset($_POST['group_add_button'])) $group_add_button = TRUE;
	else $group_add_button = FALSE;
	if(isset($_POST['group_remove_button'])) $group_remove_button = TRUE;
	else $group_remove_button = FALSE;
	foreach($_POST as $key => $value) {
		if(is_integer(strpos($key,"song_work_"))) {
			$add_id = str_replace("song_work_",'',$key);
		//	echo "add_id = ".$add_id."<br />";
			if($edit_button) {
				if($song_list <> '') $song_list .= ", ".$add_id;
				else $song_list = $add_id;
				}
			else if($group_add_button) {
				$assign_group = assign_group($add_id,$new_group_label);
				if(!$songs_added_to_group) {
					if($assign_group)
						$songs_added_msg = "<span style=\"color:red;\">➡ Songs have been assigned to newly created group</span> ‘".$new_group_label."’";
					else $songs_added_msg = "<span style=\"color:red;\">➡ More songs have been assigned to group</span> ‘".$new_group_label."’";
					}
				$songs_added_to_group = TRUE;
				}
			else if($group_remove_button) {
				$remove_group = remove_group($add_id,$old_group_label);
				$songs_removed_from_group = TRUE;
				if($remove_group) $songs_removed_msg = "<span style=\"color:red;\">➡ Selected songs have been removed from group</span> ‘".$old_group_label."’";
				else $songs_removed_msg = "<span style=\"color:red;\">➡ No song has been removed from group</span> ‘".$old_group_label."’ <span style=\"color:red;\">as it does not exist</span>";
				}
			else {
				$set_id = current_workset_id($login);
				if(!$songs_added_to_workset)
					$songs_added_msg = "<span style=\"color:red;\">➡ Selected songs are already in a work set</span>";
				$songs_added_to_workset = TRUE;
			//	echo "workset_add_id=".$add_id."<br />";
				if(($other_set=other_work_set($add_id,'','submit')) > 0) {
					$other_user = set_user($other_set);
					$songs_remarks_msg .= "<span style=\"color:red;\">Selected song </span>#".$add_id." <span style=\"color:red;\">is already in submitted work set</span> #".$other_set." <span style=\"color:red;\">by</span> ‘".$other_user."’<br />";
					}
				else {
					$semantic_class_id_of_song = semantic_class_id($add_id);
					if($set_id == 0) {
						echo "<br /><span style=\"color:red;\">ERROR:</span> set_id = 0<br />";
						}
					else {
						$other_set = other_work_set($add_id,'','current');
					//	$word_ids = transcription($add_id,"word_ids");
						if($other_set == 0) {
							$query_update = "INSERT INTO ".BASE.".workset (set_id, song_id, semantic_class_id, login, devanagari, roman, remarks_marathi, remarks_english, word_ids, translation, editor) VALUES (\"".$set_id."\",\"".$add_id."\",\"".$semantic_class_id_of_song."\",\"".$login."\",\"\",\"\",\"\",\"\",\"\",\"\",\"\")";
							$result_update = $bdd->query($query_update);
							if(!$result_update) {
								echo "<br /><span style=\"color:red;\">ERROR modifying table:</span> ".$query_update."<br />";
								echo "\nPDO::errorInfo():\n";
								print_r($bdd->errorInfo());
								die();
								}
							$result_update->closeCursor();
							$songs_added_msg = "<span style=\"color:red;\">➡ More songs have been added to work set #".$set_id." of</span> <i>".$login."</i>";
							}
						}
					}
				}
			}
		}
	}

if($mode == '') {
	$n = 0;
	$query = '';
	if($performer_id > 0) {
		$url_songs_page .= "performer_id=".$performer_id;
		$query = "SELECT semantic_class_id, location_id FROM ".BASE.".songs WHERE performer_id = \"".$performer_id."\"";
		$result = $bdd->query($query);
		if($result) $n = $result->rowCount(); else $n = 0;
		if($n == 0) {
			echo "<span style=\"color:red;\">Performer</span> ".$performer_id." <span style=\"color:red;\"> => no songs<br />";
			if(!$result) echo "Query is ill-formed.";
			echo "</span>";
			die();
			}
		}
	if($semantic_class_id <> '') {
		$url_songs_page .= "semantic_class_id=".$semantic_class_id;
		$query = "SELECT semantic_class_id FROM ".BASE.".songs WHERE semantic_class_id LIKE \"".$semantic_class_id."%\"";
		$result = $bdd->query($query);
		if($result) $n = $result->rowCount(); else $n = 0;
		if($n == 0) {
			echo "<span style=\"color:red;\">Semantic class id</span> ".$semantic_class_id." <span style=\"color:red;\">does not exist.<br />";
			if(!$result) echo "Query is ill-formed.";
			echo "</span>";
			die();
			}
		}
	if($semantic_class_title_prefix_id <> '') {
		$url_songs_page .= "semantic_class_title_prefix_id=".$semantic_class_title_prefix_id;
		$query = "SELECT semantic_class_title_prefix FROM ".BASE.".classification WHERE semantic_class_id LIKE \"".$semantic_class_title_prefix_id."%\" LIMIT 1";
	//	echo $query."<br />";
		$result = $bdd->query($query);
		if($result) $n = $result->rowCount(); else $n = 0;
		if($n == 0) {
			echo "<span style=\"color:red;\">Semantic class id</span> ".$semantic_class_title_prefix_id." <span style=\"color:red;\">does not exist.<br />";
			if(!$result) echo "Query is ill-formed.";
			echo "</span>";
			die();
			}
		$ligne = $result->fetch();
		$semantic_class_title_prefix = $ligne['semantic_class_title_prefix'];
		$result->closeCursor();
		$query = "SELECT semantic_class_id FROM ".BASE.".songs WHERE semantic_class_title_prefix = \"".$semantic_class_title_prefix."\"";
	//	echo $query."<br />";
		$result = $bdd->query($query);
		if($result) $n = $result->rowCount(); else $n = 0;
		if($n == 0) {
			echo "<span style=\"color:red;\">Semantic class id </span>".$semantic_class_title_prefix_id." <span style=\"color:red;\">=> no songs<br />";
			if(!$result) echo "Query is ill-formed.";
			echo "</span>";
			die();
			}
	//	echo " ".$n."<br />";
		}
	if($recording_DAT_index <> '') {
		$url_songs_page .= "recording_DAT_index=".$recording_DAT_index;
		$query = "SELECT semantic_class_id FROM ".BASE.".songs WHERE recording_DAT_index LIKE \"".$recording_DAT_index."%\"";
		$result = $bdd->query($query);
		if($result) $n = $result->rowCount(); else $n = 0;
		if($n == 0) {
			echo "<span style=\"color:red;\">Recording DAT index</span> ".$recording_DAT_index." <span style=\"color:red;\">=> no songs<br />";
			if(!$result) echo "Query is ill-formed.";
			echo "</span>";
			die();
			}
		}
	if($location_id > 0) {
		$url_songs_page .= "location_id=".$location_id;
		$query = "SELECT song_id FROM ".BASE.".songs WHERE location_id = \"".$location_id."\"";
		$result = $bdd->query($query);
		if($result) $n = $result->rowCount(); else $n = 0;
		if($n == 0) {
			echo "<span style=\"color:red;\">Location </span>".$location_id."<span style=\"color:red;\"> => no songs<br />";
			if(!$result) echo "Query is ill-formed.";
			echo "</span>";
			die();
			}
		$result->closeCursor();
		$query = "SELECT village_devanagari, village_english, hamlet_devanagari, hamlet_english, location_id FROM ".BASE.".locations WHERE location_id = \"".$location_id."\"";
		$result = $bdd->query($query);
		}
	if($translation_english <> '') {
		$url_songs_page .= "translation_english=".str_replace(' ','+',$translation_english);
		$query = QueryWordInTranscription("translation_english",$translation_english,"song_id",'');
	//	if($login == "Bernard")	echo $query."<br/>";
		$result = $bdd->query($query);
		if($result) $n = $result->rowCount(); else $n = 0;
		if($n == 0) {
			echo "<span style=\"color:red;\">Words </span>".$translation_english."<span style=\"color:red;\"> => no songs<br />";
			if(!$result) echo "Query is ill-formed.";
			echo "</span>";
			die();
			}
		}
	if($translation_french <> '') {
		$url_songs_page .= "translation_french=".str_replace(' ','+',$translation_french);
		$query = QueryWordInTranscription("translation_french",$translation_french,"song_id",'');
	//	if($login == "Bernard")	echo $query."<br/>";
		$result = $bdd->query($query);
		if($result) $n = $result->rowCount(); else $n = 0;
		if($n == 0) {
			echo "<span style=\"color:red;\">Words </span>".$translation_french."<span style=\"color:red;\"> => no songs<br />";
			if(!$result) echo "Query is ill-formed.";
			echo "</span>";
			die();
			}
		}
	if($devanagari <> '') {
		$url_songs_page .= "devanagari=".str_replace(' ','+',$devanagari);
		$query = QueryWordInTranscription("devanagari",$devanagari,"song_id",'');
		// if($login == "Bernard")	echo $query."<br/>";
		$result = $bdd->query($query);
		if($result) $n = $result->rowCount(); else $n = 0;
		if($n == 0) {
			echo "<span style=\"color:red;\">Words </span>".$devanagari."<span style=\"color:red;\"> => no songs<br />";
			if(!$result) echo "Query is ill-formed.";
			echo "</span>";
			die();
			}
		}
	if($roman_devanagari <> '') {
		$url_songs_page .= "roman_devanagari=".str_replace(' ','+',$roman_devanagari);
		$query = QueryWordInTranscription("roman_devanagari",$roman_devanagari,"song_id",'');
	//	if($login == "Bernard")	echo $query."<br/>";
		$result = $bdd->query($query);
		if($result) $n = $result->rowCount(); else $n = 0;
		if($n == 0) {
			echo "<span style=\"color:red;\">Words </span>".$roman_devanagari."<span style=\"color:red;\"> => no songs<br />";
			if(!$result) echo "Query is ill-formed.";
			echo "</span>";
			die();
			}
		}	
	if($song_id > 0) {
		$url_songs_page .= "song_id=".$song_id;
		$query = "SELECT performer_id, semantic_class_id, location_id FROM ".BASE.".songs WHERE song_id = \"".$song_id."\"";
		$result = $bdd->query($query);
		if($result) $n = $result->rowCount(); else $n = 0;
		if($n == 0) {
			echo "<span style=\"color:red;\">Serial number “".$song_id."” does not match any song.</span>"; die();
			}
		}
	if($group_label <> '') {
		$url_songs_page .= "group_label=".$group_label;
	//	$query = "SELECT performer_id, semantic_class_id, location_id FROM ".BASE.".songs WHERE group_id = \"".$group_label."\"";
		$query = "SELECT * FROM ".BASE.".group_index WHERE group_id = \"".$group_id."\"";
		$result = $bdd->query($query);
		if($result) $n = $result->rowCount(); else $n = 0;
		if($n == 0) {
			echo "<span style=\"color:red;\">Group “".$group_label."” does not contain any song.</span>"; die();
			}
		}
	}
	
$canonic_url = '';
if($performer_id > 0) $canonic_url = SITE_URL."songs.php?performer_id=".$performer_id;
else if($semantic_class_id <> '') $canonic_url = SITE_URL."songs.php?semantic_class_id=".$semantic_class_id;
else if($semantic_class_title_prefix_id <> '') $canonic_url = SITE_URL."songs.php?semantic_class_title_prefix_id=".$semantic_class_title_prefix_id;
else if($recording_DAT_index <> '') $canonic_url = SITE_URL."songs.php?recording_DAT_index=".$recording_DAT_index;
else if($location_id > 0) $canonic_url = SITE_URL."songs.php?location_id=".$location_id;
else if($song_id > 0) $canonic_url = SITE_URL."songs.php?song_id=".$song_id;
else if($group_label <> '') $canonic_url = SITE_URL."songs.php?group_label=".$group_label;

if($performer_id > 0) $name = "Songs by performer “".$performer_id."”";
else if($semantic_class_id <> '') $name = "Semantic class (".$semantic_class_id.")";
else if($semantic_class_title_prefix_id <> '') $name = "Semantic class: ".$semantic_class_title_prefix;
else if($recording_DAT_index <> '') $name = $recording_DAT_index;
else if($location_id > 0) $name = "Songs location “".$location_id."”";
else if($song_id > 0) $name = "Song id = ".$song_id;
else if($group_label <> '') $name = "Group = ".$group_label;
else if($translation_english <> '') $name = "“".$translation_english."…”";
else if($translation_french <> '') $name = "“".$translation_french."…”";
else if($devanagari <> '') $name = "“".$devanagari."…”";
else if($roman_devanagari <> '') $name = "“".$roman_devanagari."…”";
else $name = "Songs";

require_once("_header.php");

$number_of_songs = 0;
$semantic_class_list = '';
if($mode == '' AND $n == 0) {
	echo "<p><div style=\"color:red; text-align:center\">No valid query on performer, semantic class, location etc.</div>";
//	echo "<br />Link = ".$link_this_page."</p>";
	die();
	}
if(is_editor($login) AND $n < 50) $show_spelling_marks = TRUE;
else $show_spelling_marks = FALSE;

if($mode == "create") {
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
	}
else {
	$ligne = $result->fetch();
	
	$query = "SELECT count(*) from ".BASE.".classification";
	$result_count = $bdd->query($query);
	$number_classes = $result_count->fetchColumn();
	$result_count->closeCursor();
		
	if($performer_id > 0 OR $song_id > 0) {
		$performer_names = array();
		if($performer_id > 0) $performer_names = performer_names($performer_id);
		else if($ligne['performer_id'] > 0) $performer_names = performer_names($ligne['performer_id']);
		else $performer_names['performer_name_english'] = '';
		if($song_id == 0)
			echo "<h2>Grindmill songs of Maharashtra<br />Songs by <a target=\"_blank\" title=\"Details\" href=\"".SITE_URL."performer.php?performer_id=".$performer_id."\">".$performer_names['performer_name_english']."</a><br />(".$n." records)</h2>";
		else {
			echo "<h2>Grindmill songs of Maharashtra — Song #<span style=\"color:MediumTurquoise;\">".$song_id."</span>";
			if($performer_names['performer_name_english'] <> '') echo " by <a target=\"_blank\" title=\"Details\" href=\"".SITE_URL."performer.php?performer_id=".$ligne['performer_id']."\">".$performer_names['performer_name_english']."</a>";
			echo "</h2>";
			}
		if($song_id > 0) $performer_of_song = $ligne['performer_id'];
		else $performer_of_song = 0;
		if($ligne['location_id'] == 0)
			$location_id = location_of_performer($performer_of_song);
		else $location_id = $ligne['location_id'];
		if($location_id > 0) {
			$location_features = location_features($location_id);
		if($location_features['village_devanagari'] == '') $location_features['village_devanagari'] = "[".$location_id."]";
			echo "<p style='text-align:center;'>Village: <a target=\"_blank\" title=\"Details\" href=\"".SITE_URL."location.php?location_id=".$location_id."\">".$location_features['village_devanagari']." - ".$location_features['village_english']."</a> ";
			echo "<small>".map_link($location_features['GPS'],FALSE)."</small>";
			if($location_features['hamlet_devanagari'] <> '')
				echo "<br />Hamlet: ".$location_features['hamlet_devanagari']." - ".$location_features['hamlet_english'];
			echo "</p>";
			}
		}
	else if($group_label <> '') {
		echo "<h2>Grindmill songs of Maharashtra — Group <span style=\"color:MediumTurquoise;\">".$group_label."</span><br />(".$n." songs)</h2>";
		}		
	else if($semantic_class_id <> '') {
		$query_class2 = "SELECT semantic_class FROM ".BASE.".classification WHERE semantic_class_id LIKE \"".$semantic_class_id."%\" ORDER BY semantic_class_id";
		$result_class2 = $bdd->query($query_class2);
		$n2 = $result_class2->rowCount();
		$ligne_class2 = $result_class2->fetch();
		$result_class2->closeCursor();
		$semantic_class = $ligne_class2['semantic_class'];
		$semantic_class_list = '';
		$query_class = "SELECT semantic_class_id, semantic_class, semantic_class_title, semantic_class_title_prefix FROM ".BASE.".classification WHERE semantic_class_id LIKE \"".$semantic_class_id."%\" ORDER BY semantic_class_id";
		$result_class = $bdd->query($query_class);
		while($ligne_class = $result_class->fetch()) {
			if($semantic_class_list <> '') $semantic_class_list .= ", ";
			$semantic_class_list .= $ligne_class['semantic_class'];
			}
		$result_class->closeCursor();
		echo "<h2>Grindmill songs of Maharashtra<br />Semantic class";
		if($n2 > 1) echo "es";
		echo " <span style=\"color:MediumTurquoise;\">".$semantic_class;
		if($n2 > 1) echo " ..."; 
		echo "</span> (".$semantic_class_id.")<br />(".$n." records)";
		echo "</h2>";
		echo "<blockquote>";
	//	if($n2 > 1) echo "<p><span style=\"color:MediumTurquoise;\">".$semantic_class_list."</span></p>";
		$higher_level = higher_level($semantic_class_id);
		if($higher_level <> '')
			echo "➡ <a target=\"_blank\" title=\"Higher level\" href=\"".SITE_URL."songs.php?semantic_class_id=".$higher_level."\">Display songs in class at higher level</a> (".$higher_level.")<br />";
		echo "➡ <a target=\"_blank\" title=\"Complete classification\" href=\"classification.php\">Display complete classification scheme</a> (<span style=\"color:red;\">".$number_classes."</span> classes)";
		echo "</blockquote>";
		}
	else if($semantic_class_title_prefix_id <> '') {
		$query_class = "SELECT semantic_class_id, semantic_class, semantic_class_title, semantic_class_title_prefix FROM ".BASE.".classification WHERE semantic_class_title_prefix = \"".$semantic_class_title_prefix."\" ORDER BY semantic_class_id";
		$result_class = $bdd->query($query_class);
		while($ligne_class = $result_class->fetch()) {
			if($semantic_class_list <> '') $semantic_class_list .= ", ";
			$semantic_class_list .= $ligne_class['semantic_class'];
			}
		$result_class->closeCursor();
		echo "<h2>Grindmill songs of Maharashtra<br />Semantic class title:";
		echo "<br /><i><span style=\"color:MediumTurquoise;\">".$semantic_class_title_prefix;
		echo "</span></i><br />(".$n." records)";
		echo "</h2>";
		echo "<blockquote>";
		$higher_level = higher_level($semantic_class_id);
		if($higher_level <> '')
			echo "➡ <a target=\"_blank\" title=\"Higher level\" href=\"".SITE_URL."songs.php?semantic_class_id=".$higher_level."\">Display songs in class at higher level</a> (".$higher_level.")<br />";
		echo "➡ <a target=\"_blank\" title=\"Complete classification\" href=\"classification.php\">Display complete classification scheme</a> (<span style=\"color:red;\">".$number_classes."</span> classes)";
		echo "</blockquote>";
		}
	else if($recording_DAT_index <> '') {
		echo "<h2>Grindmill songs of Maharashtra recorded in “".$recording_DAT_index;
		echo "”<br />(".$n." records)</h2>";
		echo "<p>&nbsp;</p>";
		echo "<ul>";
		echo "<li><a target=\"_blank\" title=\"Complete classification\" href=\"classification.php\">Display complete classification scheme</a> (<span style=\"color:red;\">".$number_classes."</span> classes)</li>";
		$table = explode('-',$recording_DAT_index);
		$tape = $table[0]."-".$table[1];
		if(!in_array($tape,$missing_chunks)) {
			$query_recording = "SELECT recording_DAT_index, recording_date FROM ".BASE.".songs WHERE recording_DAT_index LIKE \"".$recording_DAT_index."%\"";
			$result_recording = $bdd->query($query_recording);
			if($result_recording <> FALSE) {
				$ligne_recording = $result_recording->fetch();
				$recording_date = $ligne_recording['recording_date'];
				$url_ogg = recording_url($recording_DAT_index,"ogg");
				$url_aiff = recording_url($recording_DAT_index,"aif");
				echo "<li><a target=\"_blank\" title=\"OGG file\" href=\"".$url_ogg."\">Listen to full section</a> (<span style=\"color:red;\">".$recording_DAT_index."</span>";
				if($recording_date <> '') echo " recorded ".$recording_date;
				echo ")&nbsp;";
		/*		if(NO_OGG)
					echo "<small>➡&nbsp;<a title=\"Download sound file\" target=\"_blank\" href=\"".$url_aiff."\">AIFF sound file</a></small><br />";
				else { */
					echo "<audio  style=\"vertical-align:middle;\" controls preload=\"none\">";
					echo "<source src=\"".$url_ogg."\" type=\"audio/ogg\">";
					echo "<source src=\"".$url_aiff."\" type=\"audio/aiff\">";
					echo "Your browser does not support the audio element.";
					echo "</audio>";
		//			}
				echo "</li>";
				$result_recording->closeCursor();
				}
			}
		else {
			echo "<li>Section <span style=\"color:red;\">".$recording_DAT_index."</span> not yet chunked</li>";
			}
		echo "</ul>";
		}
	else if($translation_english <> '')
		echo "<h2>Grindmill songs of Maharashtra matching “".$translation_english."”<br />(".$n." records)</h2>";
	else if($translation_french <> '')
		echo "<h2>Grindmill songs of Maharashtra matching “".$translation_french."”<br />(".$n." records)</h2>";
	else if($devanagari <> '')
		echo "<h2>Grindmill songs of Maharashtra matching “".$devanagari."”<br />(".$n." records)</h2>";
	else if($roman_devanagari <> '')
		echo "<h2>Grindmill songs of Maharashtra matching “".$roman_devanagari."”<br />(".$n." records)</h2>";
	else if($location_id > 0) {
		echo "<h2>Grindmill songs of Maharashtra — Songs in village:<br /><a target=\"_blank\" title=\"Details\" href=\"".SITE_URL."location.php?location_id=".$ligne['location_id']."\">".$ligne['village_devanagari']." - ".$ligne['village_english']."</a>";
		if($ligne['hamlet_devanagari'] <> '') echo "<br />Hamlet ".$ligne['hamlet_devanagari']." - ".$ligne['hamlet_english'];
		echo "<br />(".$n." records)</h2>";
		}
	
	echo "<div id=\"top\"></div>";
	if(is_translator($login)) {
		if($songs_remarks_msg <> '') echo "<blockquote>".$songs_remarks_msg."</blockquote>";
		if($songs_added_to_workset) echo "<blockquote>".$songs_added_msg."</blockquote>";
		if($songs_added_to_group) echo "<blockquote>".$songs_added_msg."</blockquote>";
		if($songs_removed_from_group) echo "<blockquote>".$songs_removed_msg."</blockquote>";
		echo "<blockquote><table>";
		if($song_list <> '') {
			echo "<tr>";
			echo "<form name=\"select_all\" method=\"post\" action=\"edit-songs.php\" target=\"_blank\" enctype=\"multipart/form-data\" style=\"vertical-align:middle;\">";
			echo "<td class=\"tight\" style=\"background-color:Bisque; padding:6px;\">";
			echo "<span style=\"color:blue;\"><b>EDIT ".(substr_count($song_list,',') + 1)." SONG(s):</b> </span>";
			echo "</td>";
			echo "<td class=\"tight\" style=\"text-align:center; background-color:Bisque; padding:6px;\">";
			echo "<textarea name=\"song_list\" ROWS=\"3\" style=\"width:200px;\">";
			echo $song_list;
			echo "</textarea><br />";
			echo "<input type=\"checkbox\" name=\"include_french\" value=\"ok\" unchecked />";
			echo "&nbsp;Include translation in French";
			echo "</td>";
			echo "<td class=\"tight\" style=\"text-align:center; background-color:Bisque; padding:6px;\">";
			echo "<input type=\"submit\" class=\"button\" value=\"OK, edit\">";
			echo "</td>";
			echo "</form>";
			echo "<td class=\"tight\" style=\"text-align:center; background-color:Bisque; padding:6px;\">";
			echo "<form name=\"select_all\" method=\"post\" action=\"".$url_songs_page."\" enctype=\"multipart/form-data\">";
			echo "<input type=\"submit\" class=\"button\" value=\"CANCEL\">";
			echo "</form>";
			echo "</td>";
			}
		else {
			echo "<tr>";
			echo "<td class=\"tight\" style=\"text-align:center; background-color:Bisque;\">";
			echo "<form name=\"select_all\" method=\"post\" action=\"".$url_songs_page."\" enctype=\"multipart/form-data\">";
			echo "<input type=\"hidden\" name=\"select_all\" value=\"yes\">";
			echo "<input type=\"submit\" class=\"button\" value=\"SELECT ALL SONGS\">";
			echo "</form>";
			echo "</td>";
			echo "<td class=\"tight\" style=\"text-align:center; background-color:Bisque;\">";
			echo "<form name=\"select_all\" method=\"post\" action=\"".$url_songs_page."\" enctype=\"multipart/form-data\">";
			echo "<input type=\"hidden\" name=\"select_all\" value=\"untranslated\">";
			echo "<input type=\"submit\" class=\"button\" value=\"SELECT ALL UNTRANSLATED SONGS\">";
			echo "</form>";
			echo "</td>";
			echo "<td class=\"tight\" style=\"text-align:center; background-color:Bisque;\">";
			echo "<form name=\"select_none\" method=\"post\" action=\"".$url_songs_page."\" enctype=\"multipart/form-data\">";
			echo "<input type=\"hidden\" name=\"select_all\" value=\"no\">";
			echo "<input type=\"submit\" class=\"button\" value=\"UNSELECT ALL SONGS\">";
			echo "</form>";
			echo "</td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td class=\"tight\" style=\"text-align:center; background-color:Bisque;\">";
			echo "<form name=\"workset\" method=\"post\" action=\"".$url_songs_page."\" enctype=\"multipart/form-data\">";
			echo "<input type=\"submit\" name=\"workset_button\" class=\"button\" value=\"ADD SELECTED SONGS TO WORK SET\">";
			echo "</td>";
			echo "<td colspan=\"2\" class=\"tight\" style=\"text-align:center; background-color:Bisque;\">";
			echo "<input type=\"submit\" name=\"group_add_button\" class=\"button\" value=\"ADD SELECTED SONGS TO GROUP:\">";
			echo "&nbsp;<input type=\"text\" style=\"text-align:center;\" name=\"new_group_label\" size=\"10\"";
			if($group_label <> '') echo " value=\"".$group_label."\"";
			echo "><br />";
			echo "<input type=\"submit\" name=\"group_remove_button\" class=\"button\" value=\"REMOVE SELECTED SONGS FROM GROUP:\">";
			echo "&nbsp;<input type=\"text\" style=\"text-align:center;\" name=\"old_group_label\" size=\"10\"";
			if($group_label <> '') echo " value=\"".$group_label."\"";
			
			
			echo "</td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td colspan=\"3\" class=\"tight\" style=\"text-align:center; background-color:Bisque;\">";
			echo "<input type=\"submit\" name=\"edit_button\" class=\"button\" value=\"EDIT SELECTED SONGS\">";
			echo "</td>";
			echo "</tr>";
			}
		echo "</table></blockquote>";
		}
	
	if($performer_id > 0)
		$query_class = "SELECT DISTINCT(semantic_class_id) FROM ".BASE.".songs WHERE performer_id = \"".$performer_id."\" ORDER BY semantic_class_id";
	else if($recording_DAT_index <> '')
		$query_class = "SELECT DISTINCT(semantic_class_id), cross_references_for_this_song FROM ".BASE.".songs WHERE recording_DAT_index LIKE \"".$recording_DAT_index."%\" ORDER BY semantic_class_id";
	else if($semantic_class_id  <> '')
		$query_class = "SELECT semantic_class_id, semantic_class, semantic_class_title, semantic_class_title_prefix, cross_references FROM ".BASE.".classification WHERE semantic_class_id LIKE \"".$semantic_class_id."%\" ORDER BY semantic_class_id";
	else if($semantic_class_title_prefix_id  <> '') {
		$query_class = "SELECT semantic_class_id, semantic_class, semantic_class_title, semantic_class_title_prefix, cross_references FROM ".BASE.".classification WHERE semantic_class_title_prefix = \"".$semantic_class_title_prefix."\" ORDER BY semantic_class_id";
	//	echo $query_class."<br />";
		}
	else if($song_id > 0)
		$query_class = "SELECT semantic_class_id, cross_references_for_this_song FROM ".BASE.".songs WHERE song_id = \"".$song_id."\"";
	else if($group_label <> '') {
		$query_class = "SELECT DISTINCT(semantic_class_id), cross_references_for_this_song FROM ".BASE.".songs as S, ".BASE.".group_index as G WHERE G.group_id = \"".$group_id."\" AND S.song_id = G.song_id ORDER BY semantic_class_id";
		}
	else if($translation_english <> '') {
		$query_class = QueryWordInTranscription("translation_english",$translation_english,"DISTINCT(semantic_class_id), cross_references_for_this_song","semantic_class_id");
		}
	else if($translation_french <> '') {
		$query_class = QueryWordInTranscription("translation_french",$translation_french,"DISTINCT(semantic_class_id), cross_references_for_this_song","semantic_class_id");
		}
	else if($devanagari <> '') {
		$query_class = QueryWordInTranscription("devanagari",$devanagari,"DISTINCT(semantic_class_id), cross_references_for_this_song","semantic_class_id");
		}
	else if($roman_devanagari <> '') {
		$query_class = QueryWordInTranscription("roman_devanagari",$roman_devanagari,"DISTINCT(semantic_class_id), cross_references_for_this_song","semantic_class_id");
		}
	else if($location_id > 0) $query_class = "SELECT DISTINCT(semantic_class_id), cross_references_for_this_song FROM ".BASE.".songs WHERE location_id = \"".$location_id."\" ORDER BY semantic_class_id";
	$result_class = $bdd->query($query_class);
	// echo $query_class."<br />";
	$number_classes = $result_class->rowCount();
	if($number_classes > 1) {
		echo $number_classes." semantic classes ➡&nbsp;list at <a href=\"#bottom\">the bottom of this page</a><br />";
		if($semantic_class_list <> '') echo "<span style=\"color:MediumTurquoise;\">".$semantic_class_list."</span><br />";
		}
	$class_title = array();
	while($ligne_class = $result_class->fetch()) {
		if($ligne_class['semantic_class_id'] == '') continue;
		$query_class2 = "SELECT * FROM ".BASE.".classification WHERE semantic_class_id = \"".$ligne_class['semantic_class_id']."\"";
		$result_class2 = $bdd->query($query_class2);
		$ligne_class2 = $result_class2->fetch();
		$result_class2->closeCursor();
		$semantic_class_title_prefix = $ligne_class2['semantic_class_title_prefix'];
		$semantic_class_title = $ligne_class2['semantic_class_title'];
		$title_comment = str_replace("\n","<br />",$ligne_class2['title_comment']);
		$class_comment = str_replace("\n","<br />",$ligne_class2['class_comment']);
		$title_comment_mr = str_replace("\n","<br />",$ligne_class2['title_comment_mr']);
		$class_comment_mr = str_replace("\n","<br />",$ligne_class2['class_comment_mr']);
		$class_title[$semantic_class_title] = TRUE;
		$semantic_class_text = semantic_class_text($semantic_class_title_prefix,$semantic_class_title);
		$cross_references = $ligne_class2['cross_references'];
		echo "<br /><h3><span style=\"color:MediumTurquoise;\">".$ligne_class2['semantic_class']."</span> (".$ligne_class['semantic_class_id'].") - <a target=\"_blank\" title=\"All songs in this class\" href=\"".SITE_URL."songs.php?semantic_class_id=".$ligne_class['semantic_class_id']."\">".$semantic_class_text."</a>";
		if(is_translator($login)) echo " <small><span style=\"color:MediumTurquoise;\">➡</span>&nbsp;<a target=\"_blank\" href=\"edit-classification.php?class=".$semantic_class_title_prefix."\">Edit&nbsp;or&nbsp;comment this class…</a></small>";
		echo "</h3>";
		if($title_comment <> '' OR $class_comment <> '' OR $title_comment_mr <> '' OR $class_comment_mr <> '') {
			echo "<table style=\"border-spacing:0px; empty-cells:hide; font-size:80%; padding:0px;\"><tr>";
			echo "<td style=\"background-color:Cornsilk; border:3px solid white;\">".$title_comment_mr."</td>";
			echo "<td style=\"background-color:Cornsilk; border:3px solid white;\">".$title_comment."</td>"; 
			echo "</tr><tr>";
			echo "<td style=\"background-color:Cornsilk; border:3px solid white;\">".$class_comment_mr."</td>";
			echo "<td style=\"background-color:Cornsilk; border:3px solid white;\">".$class_comment."</td>";
			echo "</tr></table>";
			}
		if($cross_references <> '') {
			$list_cross_references = list_cross_references($cross_references,"MediumTurquoise",FALSE);
			echo "<table><tr>";
			echo "<td style='text-align:right;'>Cross-references:</td>";
			echo "<td>".$list_cross_references."</td>";
			echo "</tr></table>";
			}
		if(strlen($recording_DAT_index) < 9) {
			// Display streamers for UVS-04 but not UVS-04-07 for instance
			$query_recording = "SELECT DISTINCT(recording_DAT_index) FROM ".BASE.".songs WHERE semantic_class_id LIKE \"".$ligne_class['semantic_class_id']."%\" AND recording_DAT_index <> ''";
			if($song_id > 0) $query_recording = "SELECT recording_DAT_index, recording_date FROM ".BASE.".songs WHERE song_id = \"".$song_id."\"";
			if($group_label <> '') {
			//	$query_recording .= " AND group_id = \"".$group_label."\"";
				$query_recording = "SELECT DISTINCT(recording_DAT_index) FROM ".BASE.".songs as S, ".BASE.".group_index as G WHERE G.group_id = \"".$group_id."\" AND S.song_id = G.song_id ORDER BY semantic_class_id";
				}
			if($performer_id > 0) $query_recording .= " AND performer_id = \"".$performer_id."\"";
			if($location_id > 0) $query_recording .= " AND location_id = \"".$location_id."\"";
			if($recording_DAT_index <> '') $query_recording = "SELECT DISTINCT(recording_DAT_index) FROM ".BASE.".songs WHERE recording_DAT_index LIKE \"".$recording_DAT_index."%\" AND semantic_class_id LIKE \"".$ligne_class['semantic_class_id']."%\"";
			if($translation_english <> '') {
				$query_recording .= " AND ".QueryWordInTranscriptionWhere("translation_english",$translation_english,'');
				}
			if($translation_french <> '') {
				$query_recording .= " AND ".QueryWordInTranscriptionWhere("translation_french",$translation_french,'');
				}
			if($devanagari <> '') {
				$query_recording .= " AND ".QueryWordInTranscriptionWhere("devanagari",$devanagari,'');
				}
			if($roman_devanagari <> '') {
				$query_recording .= " AND ".QueryWordInTranscriptionWhere("roman_devanagari",$roman_devanagari,'');
				}
			$result_recording = $bdd->query($query_recording);
//			echo $query_recording."<br />";
			echo "<ul>";
			while($ligne_recording = $result_recording->fetch()) {
				$DAT_index = $ligne_recording['recording_DAT_index'];
				$recording_features = recording_features($DAT_index);
				if($recording_features['recording_DAT_index'] == '') continue;
				$table = explode('-',$DAT_index);
				$tape = $table[0]."-".$table[1];
				if(in_array($tape,$missing_chunks)) {
					echo "<li>Section <span style=\"color:red;\">".$DAT_index."</span> not yet chunked</li>";
					continue;
					}
				$recording_date = $recording_features['recording_date'];
				$url_ogg = recording_url($DAT_index,"ogg");
				$url_aiff = recording_url($DAT_index,"aif");
		//		echo $url_aiff."<br />";
				echo "<li><a target=\"_blank\" title=\"OGG file\" href=\"".$url_ogg."\">Listen to full section</a> (<span style=\"color:red;\">".$DAT_index."</span>";
				if($recording_date <> '') echo " recorded ".$recording_date;
				echo ")&nbsp;";
	//			echo "<br />".$url_ogg."<br />".$url_aiff."<br />";
		/*		if(FALSE AND NO_OGG) {
					echo "<small>➡&nbsp;<a title=\"Download sound file\" target=\"_blank\" href=\"".$url_aiff."\">AIFF sound file</a></small><br />";
					}
				else { */
					echo "<audio  style=\"vertical-align:middle;\" controls preload=\"none\">";
					echo "<source src=\"".$url_ogg."\" type=\"audio/ogg\">";
					echo "<source src=\"".$url_aiff."\" type=\"audio/aiff\">";
					echo "Your browser does not support the audio element.";
					echo "</audio>";
		//			}
				echo "</li>";
				}
			echo "</ul>";
			$result_recording->closeCursor();
			}
		if($performer_id > 0) $query = "SELECT * FROM ".BASE.".songs WHERE performer_id = \"".$performer_id."\" AND semantic_class_id = \"".$ligne_class['semantic_class_id']."\" ORDER BY song_number";
		else if($semantic_class_id  <> '' OR $semantic_class_title_prefix_id <> '') $query = "SELECT * FROM ".BASE.".songs WHERE semantic_class_id = \"".$ligne_class['semantic_class_id']."\" ORDER BY song_number";
		else if($recording_DAT_index  <> '') $query = "SELECT * FROM ".BASE.".songs WHERE recording_DAT_index LIKE \"".$recording_DAT_index."%\" AND semantic_class_id = \"".$ligne_class['semantic_class_id']."\" ORDER BY song_number";
		else if($song_id > 0) $query = "SELECT * FROM ".BASE.".songs WHERE song_id = \"".$song_id ."\"";
		else if($group_label <> '') {
		//	$query = "SELECT * FROM ".BASE.".songs WHERE group_id = \"".$group_label ."\" AND semantic_class_id = \"".$ligne_class['semantic_class_id']."\" ORDER BY song_number";
			$query = "SELECT * FROM ".BASE.".songs as S, ".BASE.".group_index as G WHERE G.group_id = \"".$group_id."\" AND S.song_id = G.song_id AND S.semantic_class_id = \"".$ligne_class['semantic_class_id']."\" ORDER BY song_number";
			}
		else if($translation_english <> '') {
			$query = "SELECT * FROM ".BASE.".songs WHERE semantic_class_id = \"".$ligne_class['semantic_class_id']."\" AND ".QueryWordInTranscriptionWhere("translation_english",$translation_english,"song_number");
			}
		else if($translation_french <> '') {
			$query = "SELECT * FROM ".BASE.".songs WHERE semantic_class_id = \"".$ligne_class['semantic_class_id']."\" AND ".QueryWordInTranscriptionWhere("translation_french",$translation_french,"song_number");
			}
		else if($devanagari <> '') {
			$query = "SELECT * FROM ".BASE.".songs WHERE semantic_class_id = \"".$ligne_class['semantic_class_id']."\" AND ".QueryWordInTranscriptionWhere("devanagari",$devanagari,"song_number");
			}
		else if($roman_devanagari <> '') {
			$query = "SELECT * FROM ".BASE.".songs WHERE semantic_class_id = \"".$ligne_class['semantic_class_id']."\" AND ".QueryWordInTranscriptionWhere("roman_devanagari",$roman_devanagari,"song_number");
			}
		else $query = "SELECT * FROM ".BASE.".songs WHERE location_id = \"".$location_id."\" AND semantic_class_id = \"".$ligne_class['semantic_class_id']."\" ORDER BY song_number";
		$result = $bdd->query($query);
	//	echo $query."<br />";
		$number_songs = $result->rowCount();
	//	echo "**** ".$number_songs." ***";
		echo "<table style=\"width:100%;\">";
		while($ligne = $result->fetch()) {
			echo "<tr>";
			$english = $ligne['translation_english'];
			if($number_songs < 300) {
				$english_glossary = apply_rules(TRUE,TRUE,$english,$translation_correction_english);
		//		echo $english_glossary."<br />";
				$glossary_form = glossary_form($ligne['song_id'],$english_glossary,FALSE,"450");
				}
			else $glossary_form = '';
			if($glossary_form == '') $rowspan = 1;
			else $rowspan = 2;
			if($number_songs < 30 OR $group_label <> '') {
				fix_translation($ligne['song_id'],$translation_correction_english);
				$english = apply_rules(TRUE,TRUE,$english,$translation_correction_english);
				$english = str_replace('_',' ',$english);
				$english = str_replace("||","’",$english);
				}
			$url_edit = "edit-songs.php?start=".$ligne['song_id']."&end=".$ligne['song_id'];
			$url_match = "words.php?song_id=".$ligne['song_id'];
			$DAT_index = $ligne['recording_DAT_index'];
		//	$this_group_label = $ligne['group_id'];
			$busy_in_work_set = busy_in_work_set($ligne['song_id']);
			echo "<td rowspan=\"".$rowspan."\" style=\"min-width:200px; text-align:left;\">";
			if(is_translator($login)) {
				if(!$select_none AND $busy_in_work_set > 0) {
			//		echo "<input type=\"checkbox\" name=\"song_work_".$ligne['song_id']."\" value=\"ok\" disabled />";
					echo "<input type=\"checkbox\" name=\"song_work_".$ligne['song_id']."\" value=\"ok\">";
					$other_user = set_user($busy_in_work_set);
					echo "<small>".in_work_set($busy_in_work_set,$other_user)."<br />";
					}
				else if(!$select_none AND isset($pending[$ligne['song_id']]) AND $pending[$ligne['song_id']]) {
					echo "<input type=\"checkbox\" name=\"song_work_".$ligne['song_id']."\" value=\"ok\" checked>";
					echo "<small> ← <i>pending new translation</small></i><br />";
					}
				else {
					echo "<input type=\"checkbox\" name=\"song_work_".$ligne['song_id']."\" value=\"ok\" ";
					if($select_all) echo "checked";
					else if($select_all_untranslated AND $ligne['translation_english'] == '') echo "checked";
				//	else if($select_none) echo "unchecked";
					else echo "unchecked";
					echo ">&nbsp;";
					}
				}
			$url_this_song = SITE_URL."songs.php?song_id=".$ligne['song_id'];
			
	//		if($login == "Bernard") LearnTransliterationFromSong($ligne['song_id']);
			$number_of_songs++;
			echo "[".$ligne['song_number']."]&nbsp;id = <b><span style=\"color:MediumTurquoise;\">".$ligne['song_id']."</span> <a target=\"_blank\" href=\"".$url_this_song."\">✓</a></b>";
			if(is_editor($login)) echo "<small><br />➡&nbsp;<a target=\"_blank\" title=\"".$url_edit."\" href=\"".$url_edit."\">Edit metadata</a></small>";
			echo "<br />";
			if($performer_id == 0) {
				$location_of_performer = 0;
				if($ligne['performer_id'] > 0) {
					$performer_names = performer_names($ligne['performer_id']);
					$location_of_performer = location_of_performer($ligne['performer_id']);
					}
				else $performer_names['performer_name_english'] = '';
				$location_of_song = $ligne['location_id'];
				if($location_of_song == 0) $location_of_song = $location_of_performer;
				if($performer_names['performer_name_english'] <> '') {
					echo "<a target=\"_blank\" title=\"Details\" href=\"".SITE_URL."performer.php?performer_id=".$ligne['performer_id']."\">".$performer_names['performer_name_devanagari']." - ".$performer_names['performer_name_english']."</a><br />";
					}
				if($location_of_song > 0) {
					$location_features = location_features($location_of_song);
					$village_devanagari = $location_features['village_devanagari'];
					$village_english = $location_features['village_english'];
					$map_link = map_link($location_features['GPS'],FALSE);
					if($location_id == 0) echo "Village <a target=\"_blank\" title=\"Details\" href=\"".SITE_URL."location.php?location_id=".$ligne['location_id']."\">".$village_devanagari." - ".$village_english."</a><br />";
					if($map_link <> '') echo "<small>".$map_link."</small><br />";
					}
				$group_list = group_list(TRUE,"label",TRUE,$ligne['song_id'],FALSE);	
				if($group_list <> '') {
					echo "<small>Group(s) = ".$group_list."</small><br /><br />";
					}
				}
			if($DAT_index <> '') {
				$time_code = $ligne['time_code_start'];
				$time_code = time_code_to_seconds($time_code);
				$time_start_index = time_start_index($DAT_index);
				$time_start_index = time_code_to_seconds($time_start_index);
				$time_end_index = time_end_index($DAT_index);
				$time_end_index = time_code_to_seconds($time_end_index);
				if($time_code > 0 AND $time_start_index > 0 AND $time_end_index > 0)
					$relative_time = $time_code - $time_start_index;
				else $relative_time = '';
				if($relative_time <> '' AND ($time_code < $time_start_index OR $time_code >= $time_end_index)) {
				//	echo $ligne['song_id']."<br />";
					$DAT_index = guess_DAT_index(TRUE,$ligne['song_id']);
					$time_start_index = time_start_index($DAT_index);
					$time_start_index = time_code_to_seconds($time_start_index);
					$relative_time = $time_code - $time_start_index;
					}
				// else $relative_time = '';
				echo SOUND_ICON." <span style=\"color:red;\">".$DAT_index."</span> ";
				if($relative_time <> '') echo "<small>start ".fix_time_code($relative_time)." ➡&nbsp;".link_to_chunk($DAT_index)."</small>";
				echo "<br />";
				}
			if($ligne['separate_recording'] == "yes") {
				$url_ogg = OGG_URL."SONGS/".$ligne['song_id'].".ogg";
				$url_aiff = AIFF_URL."SONGS/".$ligne['song_id'].".aif";
				$url_mp3 = MP3_URL."SONGS/".$ligne['song_id'].".mp3";
				echo "<audio  style=\"vertical-align:middle;\" controls>";
				echo "<source src=\"".$url_ogg."\" type=\"audio/ogg\">";
				echo "<source src=\"".$url_mp3."\" type=\"audio/mpeg\">";
				echo "Your browser does not support the audio element.";
				echo "</audio><br />";
				}
			echo "</td>";
			echo "<td style=\"background-color:Cornsilk; min-width:300px; text-align:left !important;\" lang=\"mr\">";
			$devanagari_this_song = $ligne['devanagari'];
			if($show_spelling_marks) $devanagari_this_song = spelling_marks('mr',$devanagari_this_song,"red");
			$roman_devanagari_this_song = $ligne['roman_devanagari'];
			if($show_spelling_marks) $roman_devanagari_this_song = spelling_marks('ro',$roman_devanagari_this_song,"red");
	/*		$bugsign = '☛';
			$bugs = bugs($ligne['song_id'],$bugsign); */
			echo $devanagari_this_song."<br /><small>".$roman_devanagari_this_song."</small>";
			$busy = FALSE;
			if(is_editor($login)) {
				$roman_transliteration = Transliterate(0,"<br />",trim($ligne['devanagari']));
				if($roman_transliteration <> trim($ligne['roman_devanagari'])) {
				//	echo str_replace(" ","_",$ligne['roman_devanagari'])."<br />";
					echo "<small><span style=\"color:red;\"><br />➡&nbsp;Inconsistent transliteration, should be:</span><br /><span style=\"background-color:yellow;\">".$roman_transliteration."</span></small>";
					}
				if($busy_in_work_set > 0) {
					$other_user = set_user($busy_in_work_set);
					echo "<small><br />".in_work_set($busy_in_work_set,$other_user);
					$busy = TRUE;
					}
				else echo "<small> ➡&nbsp;<a target=\"_blank\" title=\"".$url_edit."\" href=\"".$url_edit."\">Edit…</a></small>";
				}
			echo "</td>";
			if($english == '') $english = "<i>no translation in English</i>";
			else if($show_spelling_marks) $english = spelling_marks('en',$english,"red");
			echo "<td style=\"background-color:Lavender; min-width:300px; text-align:left !important;\" lang=\"en\">";
			echo "<small>".TRANSLATION_ICON." ".$english;
			if(is_editor($login) AND $ligne['translation_english'] <> '') {
				if($busy_in_work_set > 0) {
					$other_user = set_user($busy_in_work_set);
					echo "<br />".in_work_set($busy_in_work_set,$other_user);
					}
				else 
					echo " ➡&nbsp;<a target=\"_blank\" title=\"".$url_edit."\" href=\"".$url_edit."\">Edit…</a>";
				}
			$mapping = Mapping($ligne['devanagari'],$ligne['word_ids'],-1,FALSE);
			if($english <> '') echo "<br />";
	//		echo $devanagari_this_song."<br /> word_ids = ".$ligne['word_ids']."<br />";
			echo "▷&nbsp;<i>".str_replace("<br />","<br /></i>▷&nbsp;<i>",$mapping)."</i> ";
			if($busy_in_work_set == 0 AND is_mapper($login))
				echo "➡&nbsp;<a target=\"_blank\" title=\"".$url_match."\" href=\"".$url_match."\">Map&nbsp;words…</a>";
			echo "</small></td>";
			$french = $ligne['translation_french'];
			if($french == '') $french = "<i>pas de traduction en français</i>";
			else if($show_spelling_marks) $french = spelling_marks('fr',$french,"red");
			echo "<td rowspan=\"".$rowspan."\" style=\"background-color:MistyRose; text-align:left !important;\" lang=\"fr\">";
			echo "<small>".$french."</small>";
			if(is_editor($login))
				echo "<small> ➡&nbsp;<a target=\"_blank\" title=\"".$url_edit."\" href=\"".$url_edit."&include_french=ok\">Edit…</a></small>";
			echo "</td>";
			echo "</tr>";
			if($glossary_form <> '') {
				echo "<tr>";
				echo "<td colspan=\"2\" style=\"background-color:ghostwhite; font-size:80%\">";
				echo $glossary_form;
				echo "</td>";
				echo "</tr>";
				}
			if($ligne['cross_references_for_this_song'] <> '') {
				$list_cross_references_for_this_song = list_cross_references($ligne['cross_references_for_this_song'],"MediumTurquoise",FALSE);
				echo "<tr>";
				echo "<td style='text-align:right;'>";
				echo "Cross references for this song:";
				echo "</td>";
				echo "<td style='background-color:Azure;' colspan=\"3\">";
				echo $list_cross_references_for_this_song;
				echo "</td>";
				echo "</tr>";
				}
				
			$group_comments = group_list(TRUE,"comment",FALSE,$ligne['song_id'],FALSE);
			
			$remarks_marathi = $ligne['remarks_marathi'];
			if($remarks_marathi == '')
				$remarks_marathi = $group_comments['mr'];
		//	if($show_spelling_marks) $remarks_marathi = spelling_marks('mr',$remarks_marathi,"red");
			$remarks_english = $ligne['remarks_english'];
			if($remarks_english == '')
				$remarks_english = $group_comments['en'];
			if($remarks_marathi <> '' OR $remarks_english <> '') {
				echo "<tr>";
				echo "<td style='text-align:right;'>";
				echo "<small><span style=\"color:DarkCyan;\">Notes =></span></small>";
				echo "</td>"; 
				echo "<td lang=\"mr\">";
				echo "<small><span style=\"color:DarkCyan;\">".creer_liens($remarks_marathi)."</span></small>";
				if(is_editor($login)) {
					if($busy_in_work_set > 0) {
						$other_user = set_user($busy_in_work_set);
						echo "<small><br />".in_work_set($busy_in_work_set,$other_user);
						}
					else 
						echo " <small>[<a target=\"_blank\" title=\"".$url_edit."\" href=\"".$url_edit."\">Edit…</a>]</small>";
					}
				echo "</td>";
				echo "<td colspan=\"2\" lang=\"en\">";
			//	if(is_editor($login)) $remarks_english = spelling_marks('en',$remarks_english,"red");
				echo "<small><span style=\"color:DarkCyan;\">".creer_liens($remarks_english)."</span></small>";
				if(is_editor($login)) {
					if($busy_in_work_set > 0) {
						$other_user = set_user($busy_in_work_set);
						echo "<small><br />".in_work_set($busy_in_work_set,$other_user);
						}
					else 
						echo " <small>[<a target=\"_blank\" title=\"".$url_edit."\" href=\"".$url_edit."\">Edit…</a>]</small>";
					}
				echo "</td>";
				echo "</tr>";
				}
			}
		$result->closeCursor();
		echo "</table><br />";
		}
	$result_class->closeCursor();
	echo "<div style=\"float:right;\" id=\"bottom\">";
	echo "<a href=\"http://jigsaw.w3.org/css-validator/validator?uri=".SITE_URL.urlencode($url_this_page)."\" target=\"_blank\">";
    echo "<img alt=\"Valid CSS!\" src=\"images/icons/vcss-blue.gif\" style=\"border:0; width:60px;\">";
    echo "</a>";
	echo "&nbsp;<a href=\"http://validator.w3.org/check?uri=".SITE_URL.urlencode($url_this_page)."\" target=\"_blank\">";
    echo "<img alt=\"Valid HTML!\" src=\"images/icons/valid-xhtml10.png\" style=\"border:0; width:60px;\">";
    echo "</a>";
	echo "</div>";
	$number_semantic_class_titles = 0;
	echo "Sections of semantic classes:";
	echo "<ol>";
	foreach($class_title as $key => $value) {
		$number_semantic_class_titles++;
		echo "<li><small>".$key."</small></li>";
		}
	if($number_semantic_class_titles == 0) echo "<li>No section…</li>";
	echo "</ol>";
		
	if(is_translator($login)) {
		echo "<blockquote><input type=\"submit\" class=\"button\" value=\"ADD SELECTED SONGS TO WORK SET\"></blockquote>";
		echo "</form>";
		echo "<hr>";
		}
	}

if(is_translator($login)) {
	if($song_created) echo $song_created_msg;
	$flag = "recent_songs";
	echo "<a name=\"".$flag."\"></a>";
	echo "<h3>Create a new song</h3>";
	$url_this_page = substr($_SERVER["SCRIPT_NAME"],strrpos($_SERVER["SCRIPT_NAME"],"/")+1)."?".$_SERVER['QUERY_STRING'];
	echo "<table>";
	echo "<tr>";
	echo "<form name=\"create_song\" method=\"post\" action=\"".$url_this_page."#".$flag."\" enctype=\"multipart/form-data\">";
	echo "<input type=\"hidden\" name=\"create_song\" value=\"yes\">";
	$color = "Cornsilk";
	echo "<td class=\"tight\" style=\"background-color:".$color.";\">";
	echo "<b>Devanagari:</b><br />";
	echo "<textarea name=\"new_devanagari\" ROWS=\"3\" style=\"width:330px;\">";
	echo "</textarea>";
	echo "<br />";
	echo "<b>Roman Devanagari:</b><br />";
	echo "<textarea name=\"new_roman_devanagari\" ROWS=\"3\" style=\"width:330px;\">";
	echo "</textarea>";
	echo "<br /><small>➡ Transcode to Roman with <a href=\"https://translate.google.com/#mr/en/\" target=\"_blank\">https://translate.google.com/#mr/en/</a></small>";
	echo "</td>";
	echo "<td class=\"tight\" style=\"background-color:".$color.";\">";
	echo "<b>English translation:</b><br />";
	echo "<textarea name=\"new_translation\" ROWS=\"3\" style=\"width:330px;\">";
	echo "</textarea>";
	echo "</td>";
	echo "<td class=\"tight\" rowspan = \"3\" style=\"text-align:center; background-color:".$color.";\"><input type=\"submit\" class=\"button\" value=\"CREATE\">";
	echo "</td>";
	echo "</tr>";
	
	echo "<tr>";
	echo "<td class=\"tight\" style=\"background-color:".$color.";\">";
	echo "Language <input type=\"text\" name=\"language\" size=\"10\" value=\"Marathi\"><br />";
	echo "Song number <input type=\"text\" style=\"text-align:right;\" name=\"new_song_number\" size=\"3\" >&nbsp;&nbsp;—&nbsp;&nbsp;Group <input type=\"text\" style=\"text-align:right;\" name=\"new_group_label\" size=\"10\" value=\"".$group_label."\"><br />";
	echo "Recording DAT index <input type=\"text\" style=\"text-align:right;\" name=\"new_recording_DAT_index\" size=\"10\">";
	echo "<small>&nbsp;➡ e.g. UVS-15-13</small><br />";
	echo "Performer <input type=\"text\" name=\"new_performer_id\" size=\"4\"><small>&nbsp;➡ integer</small>";
	echo "</td>";
	echo "<td class=\"tight\" style=\"background-color:".$color."; min-width:300px;\">";
	echo "Recording date";
	$date = date("Y-m-d");
	echo " <input type=\"text\" style=\"text-align:right;\" name=\"recording_date\" size=\"12\" value=\"".$date."\"><small>&nbsp;➡ yyyy-mm-dd</small><br />";
	echo "Start time-code on tape";
	echo " <input type=\"text\" style=\"text-align:right;\" name=\"time_code_start\" size=\"8\">";
	echo "<small>&nbsp;➡ e.g. 01:21:45</small><br />";
	echo "Separate recording exists&nbsp;<input type=\"text\" name=\"separate_recording\" size=\"3\" value=\"no\"><small>&nbsp;➡ yes/no</small><br />";
	echo "Location <input type=\"text\" name=\"new_location_id\" size=\"4\"><small>&nbsp;➡ integer&nbsp;</small>";
	echo "—&nbsp;Tune <input type=\"text\" name=\"tune_id\" size=\"4\"><small>&nbsp;➡ integer</small>";
	echo "<br />Class <input type=\"text\" name=\"new_semantic_class_id\" size=\"13\" value=\"".$semantic_class_id."\">";
	echo "<small>&nbsp;➡ e.g. B03-01-05f</small><br />";
	echo "</td>";
	echo "</tr>";
	
	echo "<tr>";
	echo "<td class=\"tight\" style=\"background-color:".$color."; min-width:300px;\">";
	echo "<i>Comment in Marathi:</i><br />";
	echo "<textarea name=\"new_remarks_marathi\" ROWS=\"3\" style=\"width:330px;\">";
	echo "</textarea>";
	echo "</td><td class=\"tight\" style=\"background-color:".$color.";\">";
	echo "<i>Comment in English:</i><br />";
	echo "<textarea name=\"new_remarks_english\" ROWS=\"3\" style=\"width:330px;\">";
	echo "</textarea>";
	echo "</td>";
	echo "</form>";
	echo "</tr>";
	echo "</table>";
	$date = date("Y-m-d");
	$title = "My recent entries";
	$old = FALSE;
	if(isset($_POST['old_entries'])) {
		$date = "0000-00-00";
		$title = "My old entries";
		$old = TRUE;
		}
	$query = "SELECT * FROM ".BASE.".songs WHERE login = \"".$login."\" AND date_modified >= \"".$date."\" ORDER BY date_modified DESC";
	$result = $bdd->query($query);
	if($result) $n = $result->rowCount(); else $n = 0;
	if($n == 0) {
		echo "<blockquote>";
		if(!$old) {
			echo "<form name=\"old_entries\" method=\"post\" action=\"".$url_this_page."#".$flag."\" enctype=\"multipart/form-data\">";
			echo "<input type=\"hidden\" name=\"old_entries\" value=\"yes\">";
			echo "<input type=\"submit\" class=\"button\" value=\"DISPLAY MY OLD ENTRIES\">";
			echo "</form>";
			}
		else echo "<span style=\"color:red;\">No old entries in my name</span>";
		echo "</blockquote>";
		}
	else {
		echo "<h3>".$title."</h3>";
		if(!$old) echo "<blockquote>[<a target=\"_blank\" title=\"Edit all recent entries\" href=\"edit-songs.php?choice=recent\">Edit all my recent entries</a>]</blockquote>";
		else echo "<blockquote>[<a target=\"_blank\" title=\"Edit all my old entries\" href=\"edit-songs.php?choice=old\">Edit all my old entries</a>]</blockquote>";
		echo "<table>";
		while($ligne=$result->fetch()) {
			$song_id = $ligne['song_id'];
			echo "<tr>";
			echo "<form name=\"my_entries\" method=\"post\" action=\"".$url_this_page."#".$flag."\" enctype=\"multipart/form-data\">";
			echo "<input type=\"hidden\" name=\"delete_song\" value=\"".$song_id."\">";
			if($old) echo "<input type=\"hidden\" name=\"old_entries\" value=\"yes\">";
			echo "<td class=\"tight\"><a target=\"_blank\" title=\"Details\" href=\"".SITE_URL."songs.php?song_id=".$song_id."\">".$song_id."</a></td>";
			echo "<td class=\"tight\" style=\"background-color:".$color."; min-width:300px;\">".$ligne['devanagari']."<br />".$ligne['roman_devanagari']."</td>";
			echo "<td class=\"tight\" style=\"background-color:".$color."; min-width:300px;\">".$ligne['translation_english']."</td>";
			echo "<td class=\"tight\" style=\"text-align:center; background-color:".$color.";\"><input type=\"submit\" class=\"button\" value=\"DELETE\">";
			echo "</form>";
			echo "</tr>";
			}
		echo "</table>";
		$result->closeCursor();
		if(!$old) {
			echo "<blockquote><form name=\"old_entries\" method=\"post\" action=\"".$url_this_page."#".$flag."\" enctype=\"multipart/form-data\">";
			echo "<input type=\"hidden\" name=\"old_entries\" value=\"yes\">";
			echo "<input type=\"submit\" class=\"button\" value=\"DISPLAY MY OLD ENTRIES\">";
			echo "</form></blockquote>";
			}
		}
	}

echo "<div style=\"text-align:center;\">";
if($number_of_songs > 8) echo "<a style=\"color:red;\" href=\"#top\">⇑ Top of page ⇑</a>";
echo "</div><br />";
$time_end = time();
if(is_admin($login)) echo "<small>Exec time = ".($time_end - $time_start)." seconds</small><br />";
/* if(is_super_admin($login)) {
	$query = "SELECT id FROM ".BASE.".dev_roman";
	$result = $bdd->query($query);
	$n = $result->rowCount();
	echo "<small>Now ".$n." lines in dev_roman. Learned ".($n - $old_n_dev_roman)." new transliterations.</small>";
	$result->closeCursor();
	} */
echo "</body>";
echo "</html>";
?>