<?php
// session_start();
require_once("_base_urls.php");
require_once("_relier_edit.php"); // Mandatory to write in check_serious_attempt()
require_once("_tasks.php");
require_once("_edit_tasks.php");

if(!check_serious_attempt('browse')) die();

// $translation_correction_english = ReadRewriteRules("english");

$test = TRUE;
$test = FALSE;
$name = "Edit song metadata";
$canonic_url = '';

$_SESSION['fixed_typo_current_workset'] = FALSE;

if(isset($_GET['start'])) $id_start = $_GET['start'];
else $id_start = 0;
if(isset($_GET['end'])) $id_end = $_GET['end'];
else $id_end = $id_start;

if(isset($_GET['group_label'])) {
	$group_label = $_GET['group_label'];
	$name = "Group ‘".$group_label."’";
	}
else $group_label = '';
if($group_label <> '') {
	$query_group = "SELECT * FROM ".BASE.".groups WHERE label = \"".$group_label."\"";
	$result_group = $bdd->query($query_group);
	$ligne_group = $result_group->fetch();
	$group_id = $ligne_group['id'];
	$result_group->closeCursor();
	}
else $group_id = 0;

$song_list = $song_list_short = '';
if(isset($_POST['song_list'])) $song_list = $_POST['song_list'];
if(isset($_GET['song_list'])) $song_list = $_GET['song_list'];
$song_list = str_replace(' ','',$song_list);
$song = array();
if($song_list <> '' AND $group_label == '') {
	$table = explode(',',$song_list);
	$n = count($table);
	if($n == 1) {
		$id_start = $id_end  /* = $song_list_short = $song[0] */ = $table[0];
		$song_list = '';
		}
	else {
		for($i = 0; $i < $n; $i++) {
			$song[$i] = $table[$i];
			if($i > 4) continue;
			if($i > 0) $song_list_short .= ",".$table[$i];
			else $song_list_short = $table[$i];
			}
		if($n > 5) $song_list_short .= " etc.";
		$name = "Edit ".$n." songs";
		if($n > 8) $hide_url = TRUE;
		}
	}

if($id_start == $id_end AND $id_start > 0)
	$name = "Edit #".$id_start;

if($group_label <> '') {
//	$query = "SELECT song_id FROM ".BASE.".songs WHERE group_id = \"".$group_label."\"";
	$query = "SELECT song_id FROM ".BASE.".group_index WHERE group_id = \"".$group_id."\"";
	$result_songs = $bdd->query($query);
	$n = $result_songs->rowCount();
	if($n > 0) {
		$song = array();
		$song_list = '';
		while($ligne = $result_songs->fetch()) {
			$id = $ligne['song_id'];
			$song[] = $id;
			if($song_list == '') $song_list = $id;
			else $song_list .= ",".$id;
			}
		}
	$result_songs->closeCursor();
	}

$include_french = '';
if(isset($_POST['include_french']) AND $_POST['include_french'] == "ok") {
	$include_french = $_POST['include_french'];
//	echo "@fr<br />";
	}
if(isset($_GET['include_french']) AND $_GET['include_french'] == "ok") {
	$include_french = $_GET['include_french'];
	}

if(isset($_GET['choice'])) {
	$choice = $_GET['choice'];
	if($choice == "recent") $name = "My recent entries";
	if($choice == "old") $name = "My old entries";
	}
else $choice = '';
	
require_once("_header.php");

echo "<h2>Editing song metadata in database<br />";

if($group_label <> '') echo "Group ".$group_label."<br />(".count($song)." songs)";
else if($choice == "recent") echo "(all my recent entries)";
else if($choice == "old") echo "(all my old entries)";
else if($song_list <> '') {
	echo "#".str_replace(',','-',$song_list_short)."<br />(";
	echo count($song)." songs)";
	}
else echo ($id_end - $id_start + 1)." song(s)";
echo "</h2>";

if(isset($_POST['get_google'])) {
	$google_link = $_POST['google_link'];
	echo "<script type=\"text/javascript\">";
    echo "window.open('".$google_link."','_blank')";
    echo "</script>";
	}

if($choice == '' AND ($id_start > 0 OR $group_label <> '')) {
	if($group_label == '')
		$url_return = "edit-start.php?start=".$id_start."&end=".$id_end."&reset=1";
	else
		$url_return = "edit-start.php?group_label=".$group_label."&reset=1";
	if($include_french <> '') $url_return .= "&include_french=ok";
	echo "<br /><blockquote><br /><a href=\"".$url_return."\">Return to the “edit start” page</a><br /><br /></blockquote>";
	}


if(isset($_SESSION['login'])) $login = $_SESSION['login'];
else $login = '';
if(!identified() OR !is_editor($login)) {
	echo "<span style=\"color:red;\">You logged out, or your edit session expired, or your status does not grant you access to this page.<br />You need to log in or return to the “edit start” page.</span>";
	die();
	}
$_SESSION['try'] = 0;

// echo $login;

$old_time = time() - 3600;
$sql = "DELETE FROM ".BASE.".t_access WHERE acce_time < \"".$old_time."\"";
$result = $bdd->query($sql);
$result->closeCursor();

// echo "</blockquote>";

$warning_glossary = array();

$song_devanagari_comment = $song_roman_comment = $song_translation_comment = $song_translation_comment_fr = $song_metadata_hilite = array();
$message_DAT_index = array();

$red_transcription = $mark_translation = array();
$id_last_edit_metadata = $id_last_edit_transcription = 0;
$last_login_transcription = $last_edit_date_transcription = '';
$date = date('Y-m-d H:i:s');

if(is_admin($login) AND isset($_GET['reset_versions']) AND $_GET['reset_versions'] > 0) {
	$song_id = $_GET['reset_versions'];
	reset_versions($song_id);
	}

if(isset($_POST['use_transliteration'])) {
	$id = $_POST['song_id'];
	if($id > 0) {
		$query = "SELECT devanagari FROM ".BASE.".songs WHERE song_id = \"".$id."\"";
		$result = $bdd->query($query);
		$ligne = $result->fetch();
		$result->closeCursor();
		$roman_transliteration = Transliterate($id,"<br />",trim($ligne['devanagari']));
	//	echo $roman_transliteration;
		if($roman_transliteration <> '') {
			$query = "UPDATE ".BASE.".songs SET roman_devanagari = \"".$roman_transliteration."\" WHERE song_id = \"".$id."\"";
			$result = $bdd->query($query);
			if(!$result) {
				echo "<br /><span style=\"color:red;\">".$query."<br />";
				echo "ERROR: FAILED</span>";
				die();
				}
			$result->closeCursor();
			LearnTransliterationFromSong($id);
			}
		}
	}
else if(isset($_POST['action']) AND $_POST['action'] == "save") {
	if($choice <> '') {
		if($choice == "recent") $query_min = "SELECT song_id FROM ".BASE.".songs WHERE login = \"".$login."\" AND date_modified >= \"".$date."\" ORDER BY song_id ASC LIMIT 1";
		else if($choice == "old") $query_min = "SELECT song_id FROM ".BASE.".songs WHERE login = \"".$login."\" ORDER BY song_id ASC LIMIT 1";
		$result_min = $bdd->query($query_min);
		$n = $result_min->rowCount();
		if($n > 0) {
			$ligne = $result_min->fetch();
			$id_start = $ligne['song_id'];
			if($choice == "recent") $query_max = "SELECT song_id FROM ".BASE.".songs WHERE login = \"".$login."\" AND date_modified >= \"".$date."\" ORDER BY song_id DESC LIMIT 1";
			else if($choice == "old") $query_max = "SELECT song_id FROM ".BASE.".songs WHERE login = \"".$login."\" ORDER BY song_id DESC LIMIT 1";
			$result_max = $bdd->query($query_max);
			$n = $result_max->rowCount();
			if($n > 0) {
				$ligne = $result_max->fetch();
				$id_end = $ligne['song_id'];
				}
			$result_max->closeCursor();
			}
		$result_min->closeCursor();
		}
	if(count($song) == 0) {
		for($id = $id_start; $id <= $id_end; $id++) {
			$song[] = $id;
			}
		}
	for($i_song = 0; $i_song < count($song); $i_song++) {
		$id = $song[$i_song];
	//	echo $id." ";
		if($choice <> '') {
			if($choice == "recent")
				$query = "SELECT date_modified, login FROM ".BASE.".songs WHERE song_id = \"".$id."\" LIMIT 1";
			else if($choice == "old")
				$query = "SELECT date_modified, login FROM ".BASE.".songs WHERE song_id = \"".$id."\" LIMIT 1";
			$result = $bdd->query($query);
			if(!$result) continue;
			$n = $result->rowCount();
			if($n == 0) continue;
			$ligne = $result->fetch();
			$result->closeCursor();
			if($ligne['login'] <> $login) continue;
			if($choice == "recent" AND $ligne['date_modified'] < $date) continue;
			}
		$translation_id = "translation".$id;
		$translation_fr_id = "translation_fr".$id;
		$oldversion_id = "oldversion".$id;
		$oldversion_fr_id = "oldversion_fr".$id;
		$metadata_id = "metadata".$id;
		$devanagari_id = "devanagari".$id;
		$roman_devanagari_id = "roman_devanagari".$id;
		$old_devanagari_id = "old_devanagari".$id;
		$old_roman_devanagari_id = "old_roman_devanagari".$id;
		$query_there = "SELECT song_id, date, login FROM ".BASE.".song_metadata WHERE song_id = \"".$id."\" AND (devanagari <> \"\" OR roman_devanagari <> \"\")";
		$result_there = $bdd->query($query_there);
		$n_there = $result_there->rowCount();
		$last_login_transcription = $last_edit_date_transcription = '';
		if($n_there > 0) {
			$already_there_transcription = TRUE;
			$ligne_old = $result_there->fetch();
			$last_login_transcription = $ligne_old['login'];
			$last_edit_date_transcription = $ligne_old['date'];
			}
		else $already_there_transcription = FALSE;
		$result_there->closeCursor();
		
		// UPDATE Transcriptions
		$devanagari_words = $roman_words = array();
		$changed_devanagari = FALSE;
		if(isset($_POST[$devanagari_id])) {
		//	echo $id." Devanagari<br />";
		//	ResetLexicon('mr',$id);
			$devanagari = reshape_entry($_POST[$devanagari_id]);
			$devanagari = str_replace(" (","(",$devanagari);
			$devanagari = str_replace("("," (",$devanagari);
			$devanagari = str_replace(") ",")",$devanagari);
			$devanagari = str_replace(")",") ",$devanagari);
			$devanagari = str_replace("( ","(",$devanagari);
			$devanagari = str_replace(" )",")",$devanagari);
			$devanagari = str_replace('।'," । ",$devanagari);
			$devanagari = str_replace("  ",' ',$devanagari);
			$devanagari_words = all_words('',$devanagari);
		//	StoreSpelling(FALSE,'mr',$devanagari);
			$old_devanagari = reshape_entry($_POST[$old_devanagari_id]);
		//	$old_devanagari = $_POST[$old_devanagari_id];
			$id_last_edit_transcription = $id; // $$$
			if($devanagari <> $old_devanagari AND isset($_POST['save_devanagari'])) {
				ResetLexicon('mr',$id); // $$$
				StoreSpelling(FALSE,'mr',$devanagari); // $$$
				if(mb_strlen($devanagari) < 30) {
					$song_devanagari_comment[$id] = "<span style=\"color:red;\">Error: too short transcription:</span><br />".$devanagari;
					}
				else {
					$changed_devanagari = TRUE;
				//	$id_last_edit_transcription = $id;
					$show_gold_transcription[$id] = TRUE;
					$query = "UPDATE ".BASE.".songs SET devanagari = \"".$devanagari."\" WHERE song_id = \"".$id."\"";
					$result = $bdd->query($query);
					if(!$result) {
						echo "<br /><span style=\"color:red;\">".$query."<br />";
						echo "ERROR: FAILED</span>";
						die();
						}
					$result->closeCursor();
					$song_devanagari_comment[$id] = "<span style=\"color:blue;\">Old version:</span><br />".$old_devanagari;
					$roman_devanagari = Transliterate($id,"<br />",$devanagari);
					$query = "UPDATE ".BASE.".songs SET roman_devanagari = \"".$roman_devanagari."\" WHERE song_id = \"".$id."\"";
					$result = $bdd->query($query);
					if(!$result) {
						echo "<br /><span style=\"color:red;\">".$query."<br />";
						echo "ERROR: FAILED</span>";
						die();
						}
					LearnTransliterationFromSong($id);
					// Enter this change in table ‘song_metadata’
					if($already_there_transcription) {
						$query_update = "UPDATE ".BASE.".song_metadata SET devanagari = \"".$devanagari."\", roman_devanagari = \"".$roman_devanagari."\", login = \"".$login."\", date = \"".$date."\" WHERE song_id = \"".$id."\" AND (devanagari <> \"\" OR roman_devanagari <> \"\")";
						$result_update = $bdd->query($query_update);
						if(!$result_update) {
							echo "<br /><span style=\"color:red;\">ERROR modifying table:</span> ".$query_update."<br />";
							die();
							}
						$result_update->closeCursor();
						}
					else {
						$query_update = "INSERT INTO ".BASE.".song_metadata (song_id, devanagari, roman_devanagari, login, remarks_marathi, remarks_english) VALUES (\"".$id."\",\"".$devanagari."\",\"".$roman_devanagari."\",\"".$login."\",\"\",\"\")";
						$result_update = $bdd->query($query_update);
						if(!$result_update) {
							echo "<br /><span style=\"color:red;\">ERROR modifying table:</span> ".$query_update."<br />";
							die();
							}
						$result_update->closeCursor();
						$already_there_transcription = TRUE;
						}
					// Delete table of word matchings
					$query_update = "UPDATE ".BASE.".songs SET word_ids = \"\" WHERE song_id = \"".$id."\"";
			//		echo $query."<br />";
					$result_update = $bdd->query($query_update);
					if(!$result_update) {
						echo "<br /><span style=\"color:red;\">".$query_update."<br />";
						echo "ERROR: FAILED</span>";
						die();
						}
					$result_update->closeCursor();
					}
				}
			else $song_devanagari_comment[$id] = "<span style=\"color:red;\">No change…</span><br />";
			}
		if(isset($_POST[$roman_devanagari_id]) AND isset($_POST['save_roman'])) {
			$post_roman = $_POST[$roman_devanagari_id];
			$done_by_google = done_by_google($post_roman); // Obsolete
			$done_by_google = TRUE;
			if($done_by_google) ResetLexicon('ro',$id);
			$roman_devanagari = mb_strtolower(reshape_entry($post_roman));
			$roman_devanagari = CleanGoogleQuotes($roman_devanagari);
			$roman_devanagari = FixSomeGliphs($roman_devanagari);
			$roman_words = all_words('',$roman_devanagari);
			if($done_by_google AND count($roman_words) == count($devanagari_words)) {
				$done_learn = LearnTransliteration($id,$devanagari_words,$roman_words,$done_by_google);
				if($done_learn) echo "<br /><small><span style=\"color:red;\">➡ </span><a href=\"transliteration-rules.php?mode=recent\" target=\"_blank\">Display recent changes in transliteration rules</a></small><br />";
				}
			else {
				if(/* $changed_devanagari OR */ mb_strlen($roman_devanagari) < 10) {
					$roman_devanagari = Transliterate($id,"<br />",$devanagari);
					}
				} 
			if($done_by_google) StoreSpelling(FALSE,'ro',$roman_devanagari);
			$old_roman_devanagari = reshape_entry($_POST[$old_roman_devanagari_id]);
			if($roman_devanagari <> $old_roman_devanagari) {
				if(mb_strlen($roman_devanagari) < 30) {
					$song_roman_comment[$id] = "<span style=\"color:red;\">Error: too short transcription:</span><br />".$roman_devanagari."<br />";
					}
				else {
					$id_last_edit_transcription = $id;
					$show_gold_transcription[$id] = TRUE;
					$query = "UPDATE ".BASE.".songs SET roman_devanagari = \"".$roman_devanagari."\" WHERE song_id = \"".$id."\"";
					$result = $bdd->query($query);
					if(!$result) {
						echo "<br /><span style=\"color:red;\">".$query."<br />";
						echo "ERROR: FAILED</span>";
						die();
						}
					LearnTransliterationFromSong($id);
					$song_roman_comment[$id] = "<span style=\"color:blue;\">Old version:</span><br />".$old_roman_devanagari."<br />";
					if($already_there_transcription) {
						$query_update = "UPDATE ".BASE.".song_metadata SET roman_devanagari = \"".$roman_devanagari."\", login = \"".$login."\", date = \"".$date."\" WHERE song_id = \"".$id."\" AND (devanagari <> \"\" OR roman_devanagari <> \"\")";
						$result_update = $bdd->query($query_update);
						if(!$result_update) {
							echo "<br /><span style=\"color:red;\">ERROR modifying table:</span> ".$query_update."<br />";
							die();
							}
						$result_update->closeCursor();
						}
					else {
						$query_update = "INSERT INTO ".BASE.".song_metadata (song_id, roman_devanagari, login, devanagari, remarks_marathi, remarks_english) VALUES (\"".$id."\",\"".$roman_devanagari."\",\"".$login."\",\"\",\"\",\"\")";
						$result_update = $bdd->query($query_update);
						if(!$result_update) {
							echo "<br /><span style=\"color:red;\">ERROR modifying table:</span> ".$query_update."<br />";
							die();
							}
						$result_update->closeCursor();
						}
					}
				}
			else $song_roman_comment[$id] = "<span style=\"color:red;\">No change…</span><br />";
			}
		
		// UPDATE English translation
		if(isset($_POST[$translation_id])) {
			ResetLexicon('en',$id);
			$translation = reshape_entry($_POST[$translation_id]);
			$oldversion = reshape_entry($_POST[$oldversion_id]);
			$translation = fix_typo($translation,$id);
			$rule = ReadRewriteRules("english");
		//	echo "@@@<br />";
			$translation = apply_rules(TRUE,TRUE,$translation,$rule);
		//	echo "<br />@@Translation = ".$translation."<br />";
			$translation = str_replace('_',' ',$translation);
			$translation = str_replace("||","’",$translation);
			StoreSpelling(FALSE,'en',$translation);
			if(str_replace('*','',$translation) <> str_replace('*','',$oldversion)) {
	/*			echo str_replace(' ','+',$oldversion)."<br />";
				echo "=> ".str_replace(' ','+',$translation)."<br />"; */
				if(strlen($translation) < 30 AND $oldversion <> '') {
					$song_translation_comment[$id] = "<span style=\"color:red;\">Error: too short translation:</span><br />".$translation;
					}
				else {
					$query = "UPDATE ".BASE.".songs SET translation_english = \"".$translation."\" WHERE song_id = \"".$id."\"";
					$result = $bdd->query($query);
					if(!$result) {
						echo "<br /><span style=\"color:red;\">".$query."<br />";
						echo "ERROR: FAILED</span>";
						die();
						}
					$result->closeCursor();
					$query = "SELECT version, text FROM ".BASE.".translations WHERE song_id = \"".$id."\" ORDER BY version DESC";
					$result = $bdd->query($query);
					$n = $result->rowCount();
					$preceding_text = '';
					if($n == 0) $version = 1;
					else {
						$ligne = $result->fetch();
						$version = 1 + $ligne['version'];
						$preceding_text = $ligne['text'];
						}
					$result->closeCursor();
					if($translation <> $preceding_text) {
						// This avoids that a new (identical) version is created if the page is reloaded
						if($translation == '') $translation = '~';
						if($version == 1 AND trim($oldversion) <> '') {
							$query = "INSERT INTO ".BASE.".translations (song_id, version, text, login) VALUES (\"".$id."\",\"0\",\"".$oldversion."\",\"\")";
							$result = $bdd->query($query);
							if(!$result) {
								echo "<br /><span style=\"color:red;\">".$query."<br />";
								echo "ERROR: FAILED</span>";
								die();
								}
							$result->closeCursor();
							}
						$query = "INSERT INTO ".BASE.".translations (song_id, version, text, login) VALUES (\"".$id."\",\"".$version."\",\"".$translation."\",\"".$login."\")";
					//	echo $query."<br />";
						$result = $bdd->query($query);
						if(!$result) {
							echo "<br /><span style=\"color:red;\">".$query."<br />";
							echo "ERROR: FAILED</span>";
							die();
							}
						$result->closeCursor();
						$song_translation_comment[$id] = "<span style=\"color:blue;\">Old version:</span><br />".$oldversion;
						$mark_translation[$id] = TRUE;
						}
					}
				}
			else $song_translation_comment[$id] = "<span style=\"color:red;\">No change (after applying correction + typographic rules)…</span>";
			}
		
		// UPDATE French translation
		if(isset($_POST[$translation_fr_id])) {
			ResetLexicon('fr',$id);
			$translation_fr = reshape_entry($_POST[$translation_fr_id]);
			$oldversion_fr = reshape_entry($_POST[$oldversion_fr_id]);
			StoreSpelling(FALSE,'fr',$translation_fr);
			if($translation_fr <> $oldversion_fr) {
				if($translation_fr <> '' AND strlen($translation_fr) < 30 AND $oldversion_fr <> '') {
					$song_translation_comment_fr[$id] = "<span style=\"color:red;\">Error: too short translation:</span><br />".$translation_fr;
					}
				else {
					$query = "UPDATE ".BASE.".songs SET translation_french = \"".$translation_fr."\" WHERE song_id = \"".$id."\"";
					$result = $bdd->query($query);
					if(!$result) {
						echo "<br /><span style=\"color:red;\">".$query."<br />";
						echo "ERROR: FAILED</span>";
						die();
						}
					$result->closeCursor();
					$song_translation_comment_fr[$id] = "<span style=\"color:blue;\">Old version:</span><br />".$oldversion_fr;
					$mark_translation[$id] = TRUE;
					}
				}
			else $song_translation_comment_fr[$id] = "<span style=\"color:red;\">No change…</span>";
			}
		
		// UPDATE METADATA
		$recording_DAT_index_id = "recording_DAT_index".$id;
		$song_number_id = "song_number".$id;
		$group_label_id = "group_label".$id;
		$time_code_start_id = "time_code_start".$id;
		$recording_date_id = "recording_date".$id;
		$separate_recording_yes_no_id = "separate_recording_yes_no".$id;
		$performer_id_id = "performer_id".$id;
		$location_id_id = "location_id".$id;
		$semantic_class_id_id = "semantic_class_id".$id;
		$remarks_marathi_id = "remarks_marathi".$id;
		$remarks_english_id = "remarks_english".$id;
		$query_there = "SELECT song_id FROM ".BASE.".song_metadata WHERE song_id = \"".$id."\" AND devanagari = \"\" AND roman_devanagari = \"\"";
		$result_there = $bdd->query($query_there);
		$n_there = $result_there->rowCount();
		if($n_there > 0) $already_there_metadata = TRUE;
		else $already_there_metadata = FALSE;
		$result_there->closeCursor();
		$query_old = "SELECT * FROM ".BASE.".songs WHERE song_id = \"".$id."\"";
		$result_old = $bdd->query($query_old);
		$ligne_old = $result_old->fetch();
		$result_old->closeCursor();
		$performer_id = $ligne_old['performer_id'];
		$location_id = $ligne_old['location_id'];
		$semantic_class_id = $ligne_old['semantic_class_id'];
		$recording_DAT_index = $ligne_old['recording_DAT_index'];
		$song_number = $ligne_old['song_number'];
	//	$new_group_id = $ligne_old['group_id'];
		$time_code_start = $ligne_old['time_code_start'];
		$recording_date = $ligne_old['recording_date'];
		$separate_recording_yes_no = $separate_recording = $ligne_old['separate_recording'];
		if($separate_recording == '') $separate_recording_yes_no = "no";
		if(isset($_POST[$metadata_id])) {
			echo "<blockquote><span style=\"color:MediumTurquoise;\">Saving metadata for</span> song_id = ".$id."<br />";
			$song_metadata_hilite[$id] = TRUE;
			if(isset($_POST[$song_number_id])) {
				$song_number = $_POST[$song_number_id];
				if($test) echo "<small>song_number = ".$song_number;
				if($ligne_old['song_number'] <> $song_number) {
					$id_last_edit_metadata = $id;
					if($test) echo " <span style=\"color:red;\">(was ".$ligne_old['song_number'].")</span>";
					}
				if($already_there_metadata) {
					$query_update = "UPDATE ".BASE.".song_metadata SET song_number = \"".$song_number."\", login = \"".$login."\", date = \"".$date."\" WHERE song_id = \"".$id."\" AND devanagari = \"\" AND roman_devanagari = \"\"";
					$result_update = $bdd->query($query_update);
					if(!$result_update) {
						echo "<br /><span style=\"color:red;\">ERROR modifying table:</span> ".$query_update."<br />";
						die();
						}
					$result_update->closeCursor();
					}
				else {
					$query_update = "INSERT INTO ".BASE.".song_metadata (song_id, song_number, login, devanagari, roman_devanagari, remarks_marathi, remarks_english) VALUES (\"".$id."\",\"".$song_number."\",\"".$login."\",\"\",\"\",\"\",\"\")";
					$result_update = $bdd->query($query_update);
					if(!$result_update) {
						echo "<br /><span style=\"color:red;\">ERROR modifying table:</span> ".$query_update."<br />";
						die();
						}
					$result_update->closeCursor();
					$already_there_metadata = TRUE;
					}
				if($test) echo "</small><br />";
				}
			$change_groups = FALSE;
			if(isset($_POST[$group_label_id])) {
				$change_groups = TRUE;
				$new_group_labels = trim($_POST[$group_label_id]);
			/*	do $new_group_labels = str_replace("  "," ",$new_group_labels,$count);
				while($count > 0); */
				$new_group_labels = preg_replace('/\s+/',' ',$new_group_labels);
				$new_group_labels = str_replace(", ",",",$new_group_labels);
				$new_group_labels = str_replace(' ','_',$new_group_labels);
				$new_group_labels = str_replace('"','',$new_group_labels);
				if($test) echo "<small>group_label = ".$new_group_labels;
				if($test) echo "</small><br />";
				}
			if(isset($_POST[$recording_DAT_index_id])) {
				$recording_DAT_index = $_POST[$recording_DAT_index_id];
			/*	do $recording_DAT_index = str_replace(' ','',$recording_DAT_index,$count);
				while($count > 0); */
				$result_check_DAT_index = check_DAT_index($recording_DAT_index,$id);
				$message_DAT_index[$id] = $result_check_DAT_index['message'];
				if($result_check_DAT_index['correct']) {
					$recording_DAT_index = $result_check_DAT_index['dat_index'];
					$index = $recording_DAT_index;
					if($index == '') $index = '~';
					if($test) echo "<small>DAT index = ".$index;
					if($ligne_old['recording_DAT_index'] <> $recording_DAT_index AND $ligne_old['recording_DAT_index'] <> '') {
						$id_last_edit_metadata = $id;
						if($test) echo " <span style=\"color:red;\">(was ".$ligne_old['recording_DAT_index'].")</span>";
						}
					if($already_there_metadata) {
						$query_update = "UPDATE ".BASE.".song_metadata SET recording_DAT_index = \"".$index."\", login = \"".$login."\", date = \"".$date."\" WHERE song_id = \"".$id."\" AND devanagari = \"\" AND roman_devanagari = \"\"";
						$result_update = $bdd->query($query_update);
						if(!$result_update) {
							echo "<br /><span style=\"color:red;\">ERROR modifying table:</span> ".$query_update."<br />";
							die();
							}
						$result_update->closeCursor();
						}
					else {
						$query_update = "INSERT INTO ".BASE.".song_metadata (song_id, recording_DAT_index, login, devanagari, roman_devanagari, remarks_marathi, remarks_english) VALUES (\"".$id."\",\"".$index."\",\"".$login."\",\"\",\"\",\"\",\"\")";
						$result_update = $bdd->query($query_update);
						if(!$result_update) {
							echo "<br /><span style=\"color:red;\">ERROR modifying table:</span> ".$query_update."<br />";
							die();
							}
						$result_update->closeCursor();
						$already_there_metadata = TRUE;
						}
					if($test) echo "</small><br />";
					}
				}
			if(isset($_POST[$time_code_start_id])) {
				$time_code_start = $_POST[$time_code_start_id];
				$time_code_start = fix_time_code($time_code_start);
				$time = $time_code_start;
				if($time == '') $time = '~';
				if($test) echo "<small>time start = ".$time;
				if($ligne_old['time_code_start'] <> $time_code_start AND $ligne_old['time_code_start'] <> ''){
					$id_last_edit_metadata = $id;
					if($test) echo " <span style=\"color:red;\">(was ".$ligne_old['time_code_start'].")</span>";
					}
				if($already_there_metadata) {
					$query_update = "UPDATE ".BASE.".song_metadata SET time_code_start = \"".$time."\", login = \"".$login."\", date = \"".$date."\" WHERE song_id = \"".$id."\" AND devanagari = \"\" AND roman_devanagari = \"\"";
					$result_update = $bdd->query($query_update);
					if(!$result_update) {
						echo "<br /><span style=\"color:red;\">ERROR modifying table:</span> ".$query_update."<br />";
						die();
						}
					$result_update->closeCursor();
					}
				else {
					$query_update = "INSERT INTO ".BASE.".song_metadata (song_id, time_code_start, login, devanagari, roman_devanagari, remarks_marathi, remarks_english) VALUES (\"".$id."\",\"".$time."\",\"".$login."\",\"\",\"\",\"\",\"\")";
					$result_update = $bdd->query($query_update);
					if(!$result_update) {
						echo "<br /><span style=\"color:red;\">ERROR modifying table:</span> ".$query_update."<br />";
						die();
						}
					$result_update->closeCursor();
					$already_there_metadata = TRUE;
					}
				if($test) echo "</small><br />";
				}
			if(isset($_POST[$recording_date_id])) {
				$recording_date = $_POST[$recording_date_id];
				if($ligne_old['recording_date'] <> $recording_date AND $ligne_old['recording_date'] <> ''){
					$id_last_edit_metadata = $id;
					if($test) echo " <span style=\"color:red;\">(was ".$ligne_old['recording_date'].")</span>";
					}
				if($already_there_metadata) {
					$query_update = "UPDATE ".BASE.".song_metadata SET recording_date = \"".$recording_date."\", login = \"".$login."\", date = \"".$date."\" WHERE song_id = \"".$id."\" AND devanagari = \"\" AND roman_devanagari = \"\"";
					$result_update = $bdd->query($query_update);
					if(!$result_update) {
						echo "<br /><span style=\"color:red;\">ERROR modifying table:</span> ".$query_update."<br />";
						die();
						}
					$result_update->closeCursor();
					}
				else {
					$query_update = "INSERT INTO ".BASE.".song_metadata (song_id, recording_date, login, devanagari, roman_devanagari, remarks_marathi, remarks_english) VALUES (\"".$id."\",\"".$recording_date."\",\"".$login."\",\"\",\"\",\"\",\"\")";
					$result_update = $bdd->query($query_update);
					if(!$result_update) {
						echo "<br /><span style=\"color:red;\">ERROR modifying table:</span> ".$query_update."<br />";
						die();
						}
					$result_update->closeCursor();
					$already_there_metadata = TRUE;
					}
				if($test) echo "</small><br />";
				}
			if(isset($_POST[$separate_recording_yes_no_id])) {
				$separate_recording_yes_no = trim($_POST[$separate_recording_yes_no_id]);
				if($separate_recording_yes_no == '') $separate_recording_yes_no = "no";
				if($separate_recording_yes_no <> "yes" AND $separate_recording_yes_no <> "no") {
					echo "<br /><span style=\"color:red;\">ERROR: separate_recording should be ‘yes’ or ‘no’, can't be</span> ‘".$separate_recording_yes_no."’<br />";
					}
				else {
					if($test) echo "<small>separate recording = ".$separate_recording_yes_no;
					if($ligne_old['separate_recording'] <> $separate_recording_yes_no) {
						$id_last_edit_metadata = $id;
						if($test) echo " <span style=\"color:red;\">(was ".$ligne_old['separate_recording'].")</span>";
						}
					if($already_there_metadata) {
						$query_update = "UPDATE ".BASE.".song_metadata SET separate_recording = \"".$separate_recording_yes_no."\", login = \"".$login."\", date = \"".$date."\" WHERE song_id = \"".$id."\" AND devanagari = \"\" AND roman_devanagari = \"\"";
						$result_update = $bdd->query($query_update);
						if(!$result_update) {
							echo "<br /><span style=\"color:red;\">ERROR modifying table:</span> ".$query_update."<br />";
							die();
							}
						$result_update->closeCursor();
						}
					else {
						$query_update = "INSERT INTO ".BASE.".song_metadata (song_id, separate_recording, login, devanagari, roman_devanagari, remarks_marathi, remarks_english) VALUES (\"".$id."\",\"".$separate_recording_yes_no."\",\"".$login."\",\"\",\"\",\"\",\"\")";
						$result_update = $bdd->query($query_update);
						if(!$result_update) {
							echo "<br /><span style=\"color:red;\">ERROR modifying table:</span> ".$query_update."<br />";
							die();
							}
						$result_update->closeCursor();
						$already_there_metadata = TRUE;
						}
					if($test) echo "</small><br />";
					}
				}
			if(isset($_POST[$performer_id_id])) {
				$performer_id = $_POST[$performer_id_id];
				if($performer_id == '' OR $performer_id < 1) {
					echo "<br /><span style=\"color:red;\">ERROR performer id =</span> ‘".$performer_id."’<br />";
					}
				else {
					if($test) echo "<small>performer id = ".$performer_id;
					if($ligne_old['performer_id'] <> $performer_id) {
						$id_last_edit_metadata = $id;
						if($test) echo " <span style=\"color:red;\">(was ".$ligne_old['performer_id'].")</span>";
						}
					if($already_there_metadata) {
						$query_update = "UPDATE ".BASE.".song_metadata SET performer_id = \"".$performer_id."\", login = \"".$login."\", date = \"".$date."\" WHERE song_id = \"".$id."\" AND devanagari = \"\" AND roman_devanagari = \"\"";
						$result_update = $bdd->query($query_update);
						if(!$result_update) {
							echo "<br /><span style=\"color:red;\">ERROR modifying table:</span> ".$query_update."<br />";
							die();
							}
						$result_update->closeCursor();
						}
					else {
						$query_update = "INSERT INTO ".BASE.".song_metadata (song_id, performer_id, login, devanagari, roman_devanagari, remarks_marathi, remarks_english) VALUES (\"".$id."\",\"".$performer_id."\",\"".$login."\",\"\",\"\",\"\",\"\")";
						$result_update = $bdd->query($query_update);
						if(!$result_update) {
							echo "<br /><span style=\"color:red;\">ERROR modifying table:</span> ".$query_update."<br />";
							die();
							}
						$result_update->closeCursor();
						$already_there_metadata = TRUE;
						}
					if($test) echo "</small><br />";
					}
				}
			if(isset($_POST[$location_id_id])) {
				$location_id = $_POST[$location_id_id];
				if($location_id == '' OR $location_id < 1) {
					echo "<br /><span style=\"color:red;\">ERROR location id =</span> ‘".$location_id."’<br />";
					}
				else {
					if($test) echo "<small>location id = ".$location_id;
					if($ligne_old['location_id'] <> $location_id) {
						$id_last_edit_metadata = $id;
						if($test) echo " <span style=\"color:red;\">(was ".$ligne_old['location_id'].")</span>";
						}
					if($already_there_metadata) {
						$query_update = "UPDATE ".BASE.".song_metadata SET location_id = \"".$location_id."\", login = \"".$login."\", date = \"".$date."\" WHERE song_id = \"".$id."\" AND devanagari = \"\" AND roman_devanagari = \"\"";
						$result_update = $bdd->query($query_update);
						if(!$result_update) {
							echo "<br /><span style=\"color:red;\">ERROR modifying table:</span> ".$query_update."<br />";
							die();
							}
						$result_update->closeCursor();
						}
					else {
						$query_update = "INSERT INTO ".BASE.".song_metadata (song_id, location_id, login, devanagari, roman_devanagari, remarks_marathi, remarks_english) VALUES (\"".$id."\",\"".$location_id."\",\"".$login."\",\"\",\"\",\"\",\"\")";
						$result_update = $bdd->query($query_update);
						if(!$result_update) {
							echo "<br /><span style=\"color:red;\">ERROR modifying table:</span> ".$query_update."<br />";
							die();
							}
						$result_update->closeCursor();
						$already_there_metadata = TRUE;
						}
					if($test) echo "</small><br />";
					}
				}
			if(isset($_POST[$semantic_class_id_id])) {
				$semantic_class_id = $_POST[$semantic_class_id_id];
				if($semantic_class_id == '') {
					echo "<br /><span style=\"color:red;\">ERROR semantic_class_id =</span> ‘".$semantic_class_id."’<br />";
					}
				else {
					if($test) echo "<small>semantic_class id = ".$semantic_class_id;
					if($ligne_old['semantic_class_id'] <> $semantic_class_id) {
						$id_last_edit_metadata = $id;
						if($test) echo " <span style=\"color:red;\">(was ".$ligne_old['semantic_class_id'].")</span>";
						}
					if($already_there_metadata) {
						$query_update = "UPDATE ".BASE.".song_metadata SET semantic_class_id = \"".$semantic_class_id."\", login = \"".$login."\", date = \"".$date."\" WHERE song_id = \"".$id."\" AND devanagari = \"\" AND roman_devanagari = \"\"";
						$result_update = $bdd->query($query_update);
						if(!$result_update) {
							echo "<br /><span style=\"color:red;\">ERROR modifying table:</span> ".$query_update."<br />";
							die();
							}
						$result_update->closeCursor();
						}
					else {
						$query_update = "INSERT INTO ".BASE.".song_metadata (song_id, semantic_class_id, login, devanagari, roman_devanagari, remarks_marathi, remarks_english) VALUES (\"".$id."\",\"".$semantic_class_id."\",\"".$login."\",\"\",\"\",\"\",\"\")";
						$result_update = $bdd->query($query_update);
						if(!$result_update) {
							echo "<br /><span style=\"color:red;\">ERROR modifying table:</span> ".$query_update."<br />";
							die();
							}
						$result_update->closeCursor();
						$already_there_metadata = TRUE;
						}
					if($test) echo "</small><br />";
					}
				}
			if(isset($_POST[$remarks_marathi_id])) {
				$remarks_marathi = $remarks_marathi_tag = fix_typo(reshape_entry($_POST[$remarks_marathi_id]),0);
				if($remarks_marathi_tag == '') $remarks_marathi_tag = '~';
				if($test) echo "<small>remarks_marathi = ".$remarks_marathi_tag;
				if($ligne_old['remarks_marathi'] <> $remarks_marathi AND $ligne_old['remarks_marathi'] <> '') {
					$id_last_edit_metadata = $id;
					if($test) echo "<br /><span style=\"color:red;\">was:<br />".$ligne_old['remarks_marathi']."</span>";
					}
				if($already_there_metadata) {
					$query_update = "UPDATE ".BASE.".song_metadata SET remarks_marathi = \"".$remarks_marathi_tag."\", login = \"".$login."\", date = \"".$date."\" WHERE song_id = \"".$id."\" AND devanagari = \"\" AND roman_devanagari = \"\"";
					$result_update = $bdd->query($query_update); 
					if(!$result_update) {
						echo "<br /><span style=\"color:red;\">ERROR modifying table:</span> ".$query_update."<br />";
						die();
						}
					$result_update->closeCursor();
					}
				else {
					$query_update = "INSERT INTO ".BASE.".song_metadata (song_id, remarks_marathi, login, roman_devanagari, remarks_english) VALUES (\"".$id."\",\"".$remarks_marathi_tag."\",\"".$login."\",\"\",\"\",\"\")";
					$result_update = $bdd->query($query_update);
					if(!$result_update) {
						echo "<br /><span style=\"color:red;\">ERROR modifying table:</span> ".$query_update."<br />";
						die();
						}
					$result_update->closeCursor();
					$already_there_metadata = TRUE;
					}
				if($test) echo "</small><br />";
				}
			if(isset($_POST[$remarks_english_id])) {
				$remarks_english = $remarks_english_tag = fix_typo(reshape_entry($_POST[$remarks_english_id]),0);
				if($remarks_english == '') $remarks_english_tag = '~';
				else {
				//	$rule = ReadRewriteRules("english");
				//	$text = apply_rules(TRUE,TRUE,$remarks_english,$rule);
				//	$text = str_replace('_',' ',$text);
					$text = $remarks_english;
			//		StoreSpelling(FALSE,'en',$text);
					}
				if($test) echo "<small>remarks_english = ".$remarks_english_tag;
				if($ligne_old['remarks_english'] <> $remarks_english AND $ligne_old['remarks_english'] <> '') {
					$id_last_edit_metadata = $id;
					if($test) echo "<br /><span style=\"color:red;\">was:<br />".$ligne_old['remarks_english']."</span>";
					}
				if($already_there_metadata) {
					$query_update = "UPDATE ".BASE.".song_metadata SET remarks_english = \"".$remarks_english_tag."\", login = \"".$login."\", date = \"".$date."\" WHERE song_id = \"".$id."\" AND devanagari = \"\" AND roman_devanagari = \"\"";
					$result_update = $bdd->query($query_update);
					if(!$result_update) {
						echo "<br /><span style=\"color:red;\">ERROR modifying table:</span> ".$query_update."<br />";
						die();
						}
					$result_update->closeCursor();
					}
				else {
					$query_update = "INSERT INTO ".BASE.".song_metadata (song_id, remarks_english, login, devanagari, roman_devanagari, remarks_marathi) VALUES (\"".$id."\",\"".$remarks_english_tag."\",\"".$login."\",\"\",\"\",\"\")";
					$result_update = $bdd->query($query_update);
					if(!$result_update) {
						echo "<br /><span style=\"color:red;\">ERROR modifying table:</span> ".$query_update."<br />";
						die();
						}
					$result_update->closeCursor();
					$already_there_metadata = TRUE;
					}
				if($test) echo "</small><br />";
				}
			echo "</blockquote>";
			
			// Enter changes into table ‘SONGS’
			$query_update = "UPDATE ".BASE.".songs SET song_number = \"".$song_number."\", performer_id = \"".$performer_id."\", location_id = \"".$location_id."\", semantic_class_id = \"".$semantic_class_id."\", recording_DAT_index = \"".$recording_DAT_index."\", separate_recording = \"".$separate_recording_yes_no."\", time_code_start = \"".$time_code_start."\", recording_date = \"".$recording_date."\", remarks_marathi = \"".$remarks_marathi."\", remarks_english = \"".$remarks_english."\" WHERE song_id = \"".$id."\"";
			$result_update = $bdd->query($query_update);
			if(!$result_update) {
				echo "<br /><span style=\"color:red;\">ERROR modifying table:</span> ".$query_update."<br />";
				die();
				}
			$result_update->closeCursor();
			
			// Enter new semantic_class_id into any work set
			$query_update = "UPDATE ".BASE.".workset SET semantic_class_id = \"".$semantic_class_id."\" WHERE song_id = \"".$id."\"";
			$result_update = $bdd->query($query_update);
			if(!$result_update) {
				echo "<br /><span style=\"color:red;\">ERROR modifying table:</span> ".$query_update."<br />";
				die();
				}
			$result_update->closeCursor();
			
			// Store group IDs
			if($change_groups) {
				delete_groups($id);
				$table_labels = explode(',',$new_group_labels);
				for($ig = 0; $ig < count($table_labels); $ig++) {
					$this_group_label = trim($table_labels[$ig]);
				//	echo $id." ‘".$this_group_label."’<br />";
					$this_group_label = str_replace(' ','_',$this_group_label);
					$this_group_label = str_replace('"','',$this_group_label);
					$this_group_label = str_replace('‘','',$this_group_label);
					$this_group_label = str_replace('’','',$this_group_label);
					$this_group_label = str_replace("'",'',$this_group_label);
					assign_group($id,$this_group_label);
					}
				}
			
			// Fix DAT index if time_code_start has been modified
			if($time_code_start <> '') {
				$forgood = TRUE; 
				$new_DAT_index = guess_DAT_index($forgood,$id);
				if($new_DAT_index <> $recording_DAT_index) {
					if($new_DAT_index <> '') $message_DAT_index[$id] = "<font color=red>Adjusted in compliance with start time-code “</font>".$time_code_start."<font color=red>”<br />➡ Former value was “</font>".$recording_DAT_index."<font color=red>”</font><br />";
					else  $message_DAT_index[$id] = "<font color=red>No index matches start time-code “</font>".$time_code_start."<font color=red>”</font><br />";
					}
				}
			}
		}
	}
	
if(isset($_POST['action']) AND $_POST['action'] == "glossary") {
	foreach($_POST as $key => $value) {
		if(is_integer(strpos($key,"definition_")))
			change_definition($key,$value);
		}
	if(isset($_POST['new_word']) AND $_POST['new_word'] <> '') {
		$this_song_id = $_POST['song_id'];
		$word = str_replace(' ','_',trim(simple_form($_POST['new_word'])));
		$word = str_replace("'","’",$word);
		$plural = str_replace(' ','_',trim(simple_form($_POST['new_plural'])));
		$plural = str_replace("'","’",$plural);
		if(isset($_POST['new_force_case'])) $force_case = 1;
		else $force_case = 0;
		$create_glossary_entry = create_glossary_entry($word,$plural,$this_song_id,$force_case);
		$warning_glossary[$this_song_id] = $create_glossary_entry['warning'];
		if($create_glossary_entry['already'] <> '') 
			$warning_glossary[$this_song_id] = $create_glossary_entry['already'];
		}
	}
	
$translation_correction_english = ReadRewriteRules("english");


if(isset($_POST['delete_song']) AND is_admin($login)) {
	$id_delete = $_POST['song_id_delete'];
	$song_id = $_POST['song_id'];
//	echo $id_delete." ".$song_id."<br />";
	$owner_of_song = owner_of_song($id_delete);
	if($id_delete == $song_id AND (is_admin($login) OR $owner_of_song == $login)) {
		echo "<blockquote><span style=\"color:red;\">Deleting song</span> #".$id_delete."<br /><small>";
		$result = delete_song($id_delete,TRUE);
		echo "</small></blockquote>";
		// Keep a record of this action
		$session = session_id();
		$story = "delete #".$id_delete;
		$query_update = "INSERT INTO ".BASE.".history (login, session, page) VALUES (\"".$login."\", \"".$session."\", \"".$story."\")";
		$result_update = $bdd->query($query_update);
		if(!$result_update) {
			echo "<br /><span style=\"color:red;\">ERROR writing:</span> ".$query_update;
			die();
			}
		else $result_update->closeCursor();
		}
	}


//echo "ok1";
echo "<table width=100%>";
$flag = '';
if($choice == '' AND $song_list == '') {
	display_records($include_french,$group_label,$song_list,$id_start,$id_end,$choice,$last_login_transcription,$id_last_edit_transcription,$id_last_edit_metadata,$red_transcription,$mark_translation,$song_metadata_hilite,$song_devanagari_comment,$song_roman_comment,$song_translation_comment,$song_translation_comment_fr,$last_edit_date_transcription,$translation_correction_english);
	}
else {
	if(count($song) > 0) {
	//	echo "ok3";
		for($i = 0; $i < count($song); $i++) {
			if($song[$i] > 0) {
				$id_start = $id_end = $song[$i];
				display_records($include_french,$group_label,$song_list,$id_start,$id_end,$choice,$last_login_transcription,$id_last_edit_transcription,$id_last_edit_metadata,$red_transcription,$mark_translation,$song_metadata_hilite,$song_devanagari_comment,$song_roman_comment,$song_translation_comment,$song_translation_comment_fr,$last_edit_date_transcription,$translation_correction_english);
				}
			}
		}
	else {
		if($choice == "recent") $query = "SELECT * FROM ".BASE.".songs WHERE login = \"".$login."\" AND date_modified >= \"".$date."\"";
		else if($choice == "old") $query = "SELECT * FROM ".BASE.".songs WHERE login = \"".$login."\"";
		else if($group_label <> '') $query = "SELECT * FROM ".BASE.".songs WHERE group_id = \"".$group_label."\""; // Currently this query case is not used
	//	echo $query."<br />";
		$result = $bdd->query($query);
		$n = $result->rowCount();
		if($n == 0) {
			if($group_label <> '') echo "<tr><td><span style=\"color:red;\">No song in group ‘".$group_label."’</span></td></tr>";
			else if($choice == "recent") echo "<tr><td><span style=\"color:red;\">No recent entry</span></td></tr>";
			else if($choice == "old") echo "<tr><td><span style=\"color:red;\">No old entry</span></td></tr>";
			}
		else {
		//	echo "<span style=\"color:red;\">➡ Using ‘choice’ mode…</span>";
			while($ligne=$result->fetch()) {
				$id_start = $id_end = $ligne['song_id']; // echo "*";
				display_records($include_french,$group_label,$song_list,$id_start,$id_end,$choice,$last_login_transcription,$id_last_edit_transcription,$id_last_edit_metadata,$red_transcription,$mark_translation,$song_metadata_hilite,$song_devanagari_comment,$song_roman_comment,$song_translation_comment,$song_translation_comment_fr,$last_edit_date_transcription,$translation_correction_english);
				}
			}
		$result->closeCursor();
		}
	}
echo "</table>";
echo "</body>";
echo "</html>";

// ========= FUNCTIONS ============

function display_records($include_french,$group_label,$song_list,$id_start,$id_end,$choice,$last_login_transcription,$id_last_edit_transcription,$id_last_edit_metadata,$red_transcription,$mark_translation,$song_metadata_hilite,$song_devanagari_comment,$song_roman_comment,$song_translation_comment,$song_translation_comment_fr,$last_edit_date_transcription,$translation_correction_english) {
	global $bdd,$login,$warning_glossary,$message_DAT_index;
	for($id = $id_start; $id <= $id_end; $id++) {
		fix_translation($id,$translation_correction_english);
		$query = "SELECT * FROM ".BASE.".songs WHERE song_id = \"".$id."\"";
		$result = $bdd->query($query);
		$n = $result->rowCount();
		$flag = "song".$id;
		$ligne = $result->fetch();
		$result->closeCursor();
		$translation = $oldversion = trim($ligne['translation_english']);
		$translation_fr = $oldversion_fr = trim($ligne['translation_french']);
	//	echo "<br />@@ english glossary<br />";
		$english_glossary = apply_rules(TRUE,TRUE,$translation,$translation_correction_english);
		$glossary_form = glossary_form($id,$english_glossary,TRUE,"350");
		$busy_in_work_set = busy_in_work_set($id);
		if($glossary_form <> '' AND !$busy_in_work_set) $rowspan = 2;
		else $rowspan = 1;
		echo "<tr>";
		echo "<td class=\"tight\" rowspan=\"".$rowspan."\" style=\"vertical-align:top; text-align:center; padding-right:4px; background-color:Cornsilk;\">";
	//	echo $translation."<br />";
		$url = "songs.php?song_id=".$id;
		if($group_label <> '')
			$url_this_page = "edit-songs.php?group_label=".$group_label;
		else if($choice <> '')
			$url_this_page = "edit-songs.php?choice=".$choice;
		else if($song_list <> '')
			$url_this_page = "edit-songs.php?song_list=".$song_list;
		else
			$url_this_page = "edit-songs.php?start=".$id_start."&end=".$id_end;
		echo "<h3>Song #";
		if($n == 0) echo $id."</h3>";
		else {
			echo "<a target=\"_blank\" title=\"".$url."\" href=\"".$url."\">".$id."</a></h3>";
			$owner = owner_of_song($id);
			if(!$busy_in_work_set AND (is_super_admin($login) OR ($owner <> '' AND (is_admin($login) OR $owner == $login)))) {
				$url_delete = $url_this_page."&delete=".$id."&login=".$login;
				echo "<br /><br />";
				echo "<form name=\"delete_song\" method=\"post\" action=\"".$url_this_page."#".$flag."\" enctype=\"multipart/form-data\">";
				echo "<input type=\"hidden\" name=\"song_id\" value=\"".$id."\">";
				
				echo "<div class=\"tooltip\">";
				echo "<input type=\"submit\" name=\"delete_song\" class=\"button\" value=\"DELETE song:\"><br />";
				echo "#<input type=\"text\" name=\"song_id_delete\" size=\"8\" value=\"00000\" />";
				echo "<br /><small>(<span style=\"color:red;\">Enter song ID to confirm deletion</span>)</small>";
				echo "<span class=\"tooltiptext\">Danger!<br />Can't be undone…</span></div>";
				echo "</form>";
				}
			}
		if($busy_in_work_set) {
			$other_set_user = set_user($busy_in_work_set);
			echo "</td><td class=\"tight\" style=\"background-color:Cornsilk;  min-width:300px;\">";
			echo $ligne['devanagari']."<br />".$ligne['roman_devanagari'];
			echo "</td>";
			echo "<td class=\"tight\" rowspan=\"".$rowspan."\" style=\"background-color:Cornsilk; white-space:nowrap;\">";
			echo "<small> ← <i>in work set #".$busy_in_work_set." by ‘".$other_set_user."’</small></i>&nbsp;<br />";
			echo "</td>";
			echo "</tr>";
			continue;
			}
		echo "</td>";
		
		// Transcription
		$old_devanagari = $ligne['devanagari'];
		$old_devanagari = str_replace("<br />","\n",$old_devanagari);
		$old_roman_devanagari = $ligne['roman_devanagari'];
		$old_roman_devanagari = str_replace("<br />","\n",$old_roman_devanagari);
		$old_roman_devanagari = str_replace("n̄","ñ",$old_roman_devanagari);
		echo "<form name=\"transcription\" method=\"post\" action=\"".$url_this_page."#".$flag."\" enctype=\"multipart/form-data\">";
		echo "<input type=\"hidden\" name=\"song_id\" value=\"".$id."\">";
		echo "<input type=\"hidden\" name=\"song_list\" value=\"".$song_list."\">";
		echo "<input type=\"hidden\" name=\"group_label\" value=\"".$group_label."\">";
		echo "<input type=\"hidden\" name=\"include_french\" value=\"".$include_french."\">";
		echo "<input type=\"hidden\" name = \"action\" value = \"save\" />";
		if($n == 0) {
			echo "<td  class=\"tight\" rowspan=\"".$rowspan."\" colspan=\"4\"><span style=\"color:red;\">Serial number “".$id."” does not match any song.</span></td>";
			continue;
			}
		echo "<input type=\"hidden\" name=\"old_devanagari".$id."\" value=\"".$old_devanagari."\">";
		echo "<input type=\"hidden\" name=\"old_roman_devanagari".$id."\" value=\"".$old_roman_devanagari."\">";
		// if(isset($red[$id])) echo "<span style=\"color:red;\">";
		if(isset($show_gold_transcription[$id])) $color = "Gold";
		else $color = "Cornsilk";
		$spelling_marks_mr = spelling_marks('mr',$ligne['devanagari'],"red");
		if($spelling_marks_mr <> $ligne['devanagari']) $display_spelling_mr = TRUE;
		else $display_spelling_mr = FALSE;
		$roman_transliteration = Transliterate($id,"<br />",trim($ligne['devanagari']));
		$devanagari = str_replace("<br />","\n",$ligne['devanagari']);
		$devanagari_words = all_words('',$devanagari);
		echo "<td class=\"tight\" rowspan=\"".$rowspan."\" style=\"vertical-align:top; background-color:".$color.";white-space:nowrap;\">";
		echo "<a name=\"".$flag."\"></a>";
		if($display_spelling_mr) echo "<small>".$spelling_marks_mr."<br /><br /></small>";
		echo "<textarea name=\"devanagari".$id."\" ROWS=\"4\" style=\"width:330px;\">";
		echo $devanagari;
		echo "</textarea><br />";
		if(!is_integer(strpos($devanagari,"\n"))) echo "<span style=\"color:red;\">➡ Devanagari transcription should be at least on 2 lines!</span><br />";
		echo "<div style=\"float:right;\"><input type=\"submit\" name=\"save_devanagari\" class=\"button\" value=\"SAVE Devanagari\"></div>";
		$devanagari_count = count($devanagari_words);
		if(isset($song_devanagari_comment[$id])) echo "<small>".$song_devanagari_comment[$id]."<br /></small>";
		$roman_devanagari_source = str_replace(" <br />","<br />",trim($ligne['roman_devanagari']));
		$spelling_marks_ro = spelling_marks('ro',$roman_devanagari_source,"red");
		if($spelling_marks_ro <> $roman_devanagari_source) $display_spelling_ro = TRUE;
		else $display_spelling_ro = FALSE;
		$roman_devanagari = str_replace("<br />","\n",$roman_devanagari_source);
		$roman_words = all_words('',$roman_devanagari);
		if($display_spelling_ro) echo "<br /><small>".$spelling_marks_ro."<br /></small>";
		echo "<br />";
		$highlight_google = FALSE;
		if(is_integer(strpos($roman_transliteration,"[???]")) OR is_integer(strpos($roman_devanagari_source,"[???]"))) $highlight_google = TRUE;
		$roman_count = count($roman_words);
		if($roman_count <> $devanagari_count) echo "<small><span style=\"color:red;\">Inconsistent transliteration:</span> ".$roman_count." words, should be ".$devanagari_count."</small><br />";
		if(!is_integer(strpos($roman_transliteration,"[???]")) AND $roman_transliteration <> $roman_devanagari_source) {
			$highlight_google = TRUE;
			echo "<small><span style=\"color:red;\">Inconsistent transliteration, should be:</span><br /><span style=\"background-color:yellow;\">".$roman_transliteration."</span></small><br />";
		//	echo "<input type=\"submit\" name=\"use_transliteration\" class=\"button\" value=\"Use this version\"><br />";
			}
		/*$google_link = "https://translate.google.com/#mr/en/".str_replace("\n"," <br> ",$devanagari);
		echo "<input type=\"hidden\" name=\"google_link\" value=\"".$google_link."\" />";
		echo "<br /><div style=\"float:right;\">";
		if($highlight_google) echo "<span style=\"color:red;\">Do&nbsp;this&nbsp;➡&nbsp;</span>";
		echo "<input type=\"submit\" name=\"get_google\" class=\"";
		if($highlight_google) echo "buttongooglehighlight";
		else  echo "buttongoogle";
		echo "\" ";
		echo "value=\"⤋⤋ Get Roman transcription from “Google Translate” ⤋⤋\" /></div><br />"; */
		echo "<p style=\"text-align:left;";
		if($highlight_google) echo " background-color:yellow; font-size:large;";
		echo "\"><a target=\"_blank\" href=\"https://hindityping.info/converter/hindi-to-roman/\">";
		if($highlight_google) echo "➡&nbsp;use&nbsp;"; 
		echo "Devanagari to Diacritic Roman convertor</a></p>";
		echo "<div style=\"float:right;\"><input type=\"submit\" name=\"save_roman\" class=\"button\" value=\"SAVE Roman\"></div>";
		echo "<textarea name=\"roman_devanagari".$id."\" ROWS=\"4\" style=\"width:225px;\">";
		echo $roman_devanagari;
		echo "</textarea>";
		echo "<br />";
		if(is_integer(strpos($roman_devanagari,"r̥"))) echo "<span style=\"color:red;\">➡ Transcription contains illicit character ‘r̥’: click “SAVE Roman” to fix it.</span><br />";
		if(is_integer(strpos($roman_devanagari,"'"))) echo "<span style=\"color:red;\">➡ Transcription contains an apostrophe…</span><br />";
		if(isset($song_roman_comment[$id])) echo "<small>".$song_roman_comment[$id]."</small>";
		echo "</td>";
		echo "</form>";
		echo "<td class=\"tight\" style=\"text-align:center; background-color:".$color.";\">";
		$query_there = "SELECT login, date FROM ".BASE.".song_metadata WHERE song_id = \"".$id."\" AND (devanagari <> \"\" OR roman_devanagari <> \"\")";
		$result_there = $bdd->query($query_there);
		$n_there = $result_there->rowCount();
		if($n_there > 0) {
			$ligne_old = $result_there->fetch();
			$result_there->closeCursor();
			$last_login_transcription = $ligne_old['login'];
			$last_edit_date_transcription = $ligne_old['date'];
			}
		else $last_login_transcription = $last_edit_date_transcription = '';
		$result_there->closeCursor();
		
		if($last_login_transcription <> '') {
			if($id == $id_last_edit_transcription) echo "<span style=\"color:red;\">";
			echo "<br /><small>Edit by <i>".$last_login_transcription."</i><br />".$last_edit_date_transcription."</small>";
			if($id == $id_last_edit_transcription) echo "</span>";
			}
		echo "</td>";
		
		// Translations
		$query2 = "SELECT version FROM ".BASE.".translations WHERE song_id = \"".$id."\" ORDER BY version DESC";
		$result2 = $bdd->query($query2);
		$n = $result2->rowCount();
		if($n == 0) $version = -1;
		else {
			$ligne2 = $result2->fetch();
			$version = $maxversion = $ligne2['version'];
			if(isset($_GET['change_id']) AND $id == $_GET['change_id'] AND isset($_GET['set_version'])) {
				$version = $_GET['set_version'];
				}
			$query3 = "SELECT text, login, date FROM ".BASE.".translations WHERE song_id = \"".$id."\" AND version = \"".$version."\"";
			$result3 = $bdd->query($query3);
			$n = $result3->rowCount();
			if($n == 0) {
				if($version == 0) $version = 1;
				$result3->closeCursor();
				$query3 = "SELECT text, login, date FROM ".BASE.".translations WHERE song_id = \"".$id."\" AND version = \"".$version."\"";
				$result3 = $bdd->query($query3);
				$n = $result3->rowCount();
				if($n == 0) {
					echo "<span style=\"color:red;\">ERROR: can't reach version ".$version."</span><br />";
					}
				}
			$ligne3 = $result3->fetch();
			$translation = trim($ligne3['text']);
			$author = $ligne3['login'];
			$timestamp = $ligne3['date'];
			$result3->closeCursor();
			}
		$result2->closeCursor();
/*		if($busy_in_work_set) {
			$other_set_user = set_user($busy_in_work_set);
			echo "<td class=\"tight\" style=\"background-color:Cornsilk;\">";
			echo $translation;
			echo "</td>";
			echo "<td class=\"tight\" style=\"background-color:Cornsilk; white-space:nowrap;\">";
			echo "<small> ← <i>in work set #".$busy_in_work_set." by ‘".$other_set_user."’</small></i>&nbsp;<br />";
			echo "</td>";
			}
		else { */
			echo "<form name=\"translation\" method=\"post\" action=\"".$url_this_page."#".$flag."\" enctype=\"multipart/form-data\">";
			echo "<input type=\"hidden\" name=\"song_list\" value=\"".$song_list."\">";
			echo "<input type=\"hidden\" name=\"group_label\" value=\"".$group_label."\">";
			echo "<input type=\"hidden\" name=\"include_french\" value=\"".$include_french."\">";
			echo "<input type=\"hidden\" name = \"action\" value = \"save\" />";
			$spelling_marks = spelling_marks('en',$translation,"red");
			if($spelling_marks <> $translation) $display_spelling = TRUE;
			else $display_spelling = FALSE;
			$translation = str_replace("<br />","\n",$translation);
			$oldversion = str_replace("<br />","\n",$oldversion);
			$spelling_marks_fr = spelling_marks('fr',$translation_fr,"red");
			if($spelling_marks_fr <> $translation_fr) $display_spelling_fr = TRUE;
			else $display_spelling_fr = FALSE;
			$translation_fr = str_replace("<br />","\n",$translation_fr);
			$oldversion_fr = str_replace("<br />","\n",$oldversion_fr);
			if(isset($mark_translation[$id])) $color = "Gold";
			else $color = "Cornsilk";
			echo "<td class=\"tight\" style=\"background-color:".$color.";\">";
			echo "<i>Translation in English:</i><br />";
			if(TRUE OR $translation <> '') {
				if($display_spelling) echo "<small>".$spelling_marks."<br /><br />";
				echo "<textarea name=\"translation".$id."\" ROWS=\"3\" style=\"width:330px;\">";
				echo $translation;
				echo "</textarea><br />";
				echo "<input type=\"hidden\" name=\"oldversion".$id."\" value=\"".$oldversion."\">";
					
				if($version >= 0) {
					if($version >= 1) echo "<a href=\"".$url_this_page."&change_id=".$id."&set_version=".($version - 1)."#".$flag."\">previous &lt;&lt;</a>&nbsp;&nbsp;";
					echo "Version ".$version." - <small>";
					if($author <> '') echo "<i>".$author."</i> - ";
					echo $timestamp."</small>";
					if($version < $maxversion) echo "&nbsp;&nbsp;<a href=\"".$url_this_page."&change_id=".$id."&set_version=".($version + 1)."#".$flag."\">&gt;&gt; next</a><br />";
					else {
						echo "<br />";
						if(is_admin($login) AND $version > 1) {
							echo "<small>➡&nbsp;<a href=\"".$url_this_page."&reset_versions=".$id."\" title=\"DANGER!\">delete previous versions</a> (<span style=\"color:red;\">can't be undone!</span>)</small><br />";
							}
						}
					}
				if(isset($song_translation_comment[$id])) {
					echo "<small>".$song_translation_comment[$id];
					echo "</small><br />";
					}
				}
			else echo "<i><small>no translation</small></i>";
			if($include_french) {
				echo "<br /><br /><i>Traduction en français :</i><br />";
				if($display_spelling_fr) echo "<small>".$spelling_marks_fr."<br /></small>";
				echo "<textarea name=\"translation_fr".$id."\" ROWS=\"3\" style=\"width:330px;\">";
				echo $translation_fr;
				echo "</textarea><br />";
				echo "<input type=\"hidden\" name=\"oldversion_fr".$id."\" value=\"".$oldversion_fr."\">";
				if(isset($song_translation_comment_fr[$id])) {
					echo "<small>".$song_translation_comment_fr[$id];
		/*			$url_reset_lexicon = $url_this_page."&reset_lexicon_fr=".$id."&include_french=ok";
					if(is_integer(strpos($song_translation_comment_fr[$id],"No change")) AND is_translator($login)) echo "<span style=\"color:MediumTurquoise;\"> ➡ </span><a href=\"".$url_reset_lexicon."\">Reset lexical entries</a></small><br />";
					else */ echo "</small><br />";
					}
				}
			echo "</td>";
			/* if($translation <> '' OR $translation_fr <> '' OR $include_french) */ echo "<td class=\"tight\" style=\"text-align:center; background-color:".$color.";\"><input type=\"submit\" class=\"button\" value=\"← SAVE\">";
		//	else echo "<td>";
			echo "</td>";
			echo "</form>";
		//	}
		echo "</tr>";
		if($glossary_form <> '') {
			echo "<form method=\"post\" action=\"".$url_this_page."#".$flag."\" enctype=\"multipart/form-data\">";
			echo "<input type=\"hidden\" name=\"song_id\" value=\"".$id."\">";
			echo "<input type=\"hidden\" name=\"action\" value=\"glossary\">";
			echo "<tr>";
			echo "<td colspan=\"3\" style=\"background-color:ghostwhite; font-size:80%\">";
			echo $glossary_form;
			if(isset($warning_glossary[$id])) echo $warning_glossary[$id]."<br />";
			echo "&nbsp;➡ <a target=\"_blank\" href=\"glossary.php\">Edit glossary</a>";
			echo "<div style=\"float:right;\">";
			echo "<input type=\"submit\" class=\"button\" value=\"SAVE GLOSSARY ENTRIES\">";
			echo "</div>";
			echo "</td>";
			echo "</tr>";
			echo "</form>";
			}
		
		// METADATA
		echo "<tr>";
		$performer_id = $ligne['performer_id'];
		$location_id = $ligne['location_id'];
		$semantic_class_id = $ligne['semantic_class_id'];
		$recording_DAT_index = $ligne['recording_DAT_index'];
		$recording_date = $ligne['recording_date'];
		$separate_recording = $separate_recording_yes_no = $ligne['separate_recording'];
		if($separate_recording == '') $separate_recording_yes_no = "no";
		$time_code_start = $ligne['time_code_start'];
		$song_number = $ligne['song_number'];
	//	$this_group_label = $ligne['group_id'];
		if(isset($song_metadata_hilite[$id]) AND $song_metadata_hilite[$id]) $color = "Gold";
		else $color = "Ivory";
		echo "<form name=\"metadata\" method=\"post\" action=\"".$url_this_page."#".$flag."\" enctype=\"multipart/form-data\">";
		echo "<input type=\"hidden\" name=\"song_list\" value=\"".$song_list."\">";
		echo "<input type=\"hidden\" name=\"group_label\" value=\"".$group_label."\">";
		echo "<input type=\"hidden\" name=\"include_french\" value=\"".$include_french."\">";
		echo "<input type=\"hidden\" name = \"action\" value = \"save\" />";
		echo "<input type=\"hidden\" name=\"metadata".$id."\" value=\"yes\">";
		echo "<td></td>";
		echo "<td class=\"tight\" style=\"background-color:".$color.";\">";
		echo "Song number <input type=\"text\" style=\"text-align:right;\" name=\"song_number".$id."\" size=\"3\" value=\"".$song_number."\"><br /><br />";
		echo "Group(s) <input type=\"text\" style=\"text-align:left;\" name=\"group_label".$id."\" size=\"45\" value=\"".group_list(FALSE,"label",FALSE,$id,FALSE)."\"><br />";
		echo "<small>… separated with commas</small><br />";
		echo "&nbsp;➡ <small><a target=\"_blank\" href=\"groups.php\">Edit groups</a></small><br /><br />";
		if($time_code_start <> '' AND $recording_DAT_index == '')
			echo "<font color=red>➡&nbsp;Recording DAT index?";
		else echo "Recording DAT index";
		echo "&nbsp;<input type=\"text\" style=\"text-align:right;\" name=\"recording_DAT_index".$id."\" size=\"10\" value=\"".$recording_DAT_index."\">";
		if($recording_DAT_index <> '') echo " (<a target=\"_blank\" href=\"".recording_url($recording_DAT_index,"ogg")."\">listen</a>)";
		echo " ➡&nbsp;<small>e.g.&nbsp;UVS-15-13</small><br />";
		if($time_code_start <> '' AND $recording_DAT_index == '') echo "</font>";
		if(isset($message_DAT_index[$id]) AND $message_DAT_index[$id] <> '') echo "<font color=red>➡</font> ".$message_DAT_index[$id]."</font><br />";
		
		echo "Performer <input type=\"text\" name=\"performer_id".$id."\" size=\"4\" value=\"".$performer_id."\"> ";
		$performer_names = performer_names($performer_id);
		if($performer_names['performer_name_english'] == '')
			echo "<span style=\"color:red;\">???</span>";
		else echo "<a target=\"_blank\" href=\"performer.php?performer_id=".$performer_id."\" title=\"Details of this performer\">".$performer_names['performer_name_english']." (".$performer_names['performer_name_devanagari'].")</a>";
		echo "</td>";
		echo "<td class=\"tight\" rowspan = \"2\" style=\"text-align:center; background-color:".$color.";\"><input type=\"submit\" class=\"button\" value=\"← SAVE\">";
		if(isset($song_metadata_hilite[$id]) AND $song_metadata_hilite[$id])
			echo "<br /><br /><span style=\"color:red;\">All saved…</span>";
			
		$query_there = "SELECT login, date FROM ".BASE.".song_metadata WHERE song_id = \"".$id."\" AND devanagari = \"\" AND roman_devanagari = \"\"";
		$result_there = $bdd->query($query_there);
		$n_there = $result_there->rowCount();
		if($n_there > 0) {
			$ligne_old = $result_there->fetch();
			$result_there->closeCursor();
			$last_login_metadata = $ligne_old['login'];
			$last_edit_date_metadata = $ligne_old['date'];
			}
		else $last_login_metadata = $last_edit_date_metadata = '';
		$result_there->closeCursor();
			
		if($last_login_metadata <> '') {
			if($id == $id_last_edit_metadata) echo "<span style=\"color:red;\">";
			echo "<br /><small>Edit by <i>".$last_login_metadata."</i><br />".$last_edit_date_metadata."</small>";
			if($id == $id_last_edit_metadata) echo "</span>";
			}
		echo "</td>";
		echo "<td class=\"tight\" style=\"background-color:".$color."; min-width:300px;\">";
		echo "Recording date";
		echo " <input type=\"text\" style=\"text-align:right;\" name=\"recording_date".$id."\" size=\"12\" value=\"".$recording_date."\">";
		echo "&nbsp;➡ <small>yyyy-mm-dd</small><br />";
		$flag_index = flag_incorrect_DAT_index($id);
		if($flag_index <> '') echo "<font color=red><b>➡&nbsp;";
		echo "Start time-code on tape";
		if($flag_index <> '') $time_code_start_show = "???";
		else $time_code_start_show = $time_code_start;
		if($recording_DAT_index <> '') echo " (".link_to_tape($recording_DAT_index).")";
		echo " <input type=\"text\" style=\"text-align:right;\" name=\"time_code_start".$id."\" size=\"8\" value=\"".$time_code_start_show."\" onFocus=\"this.select()\">";
		echo " ➡&nbsp;<small>e.g.&nbsp;01:21:45</small>";
		if($flag_index <> '') {
			echo "</b></font>";
			echo "<br />".flag_incorrect_DAT_index($id)."<br />";
			}
		else echo "<br />";
		echo "Separate recording exists&nbsp;<input type=\"text\" name=\"separate_recording_yes_no".$id."\" size=\"3\" value=\"".$separate_recording_yes_no."\">&nbsp;➡ yes/no ";
		if($separate_recording_yes_no == "yes") echo "&nbsp;(<a target=\"_blank\" href=\"".OGG_URL."SONGS/".$ligne['song_id'].".ogg\">listen</a>)";
		echo "<br />Location <input type=\"text\" name=\"location_id".$id."\" size=\"4\" value=\"".$location_id."\"> ";
		$location_features = location_features($location_id);
		if($location_features['village_english'] == '')
			echo "<span style=\"color:red;\">???</span>";
		else {
			echo "<a target=\"_blank\" href=\"location.php?location_id=".$location_id."\" title=\"Details of this location\">".$location_features['village_english']." (".$location_features['village_devanagari'].") ";
			if($location_features['hamlet_english'] <> '') echo " / ".$location_features['hamlet_english']." (".$location_features['hamlet_devanagari'].") ";
			echo "</a>";
			}
		echo "<br /><br />Class <input type=\"text\" name=\"semantic_class_id".$id."\" size=\"13\" value=\"".$semantic_class_id."\"> ";
		$query_class = "SELECT semantic_class,semantic_class_title, semantic_class_title_prefix FROM ".BASE.".classification WHERE semantic_class_id = \"".$semantic_class_id."\"";
		$result_class = $bdd->query($query_class);
		$ligne_class = $result_class->fetch();
		$result_class->closeCursor();
		if($ligne_class['semantic_class_title_prefix'] == '')
			echo "<span style=\"color:red;\">???</span><small> ➡ e.g. A01-01-14</small>";
		else {
			$url_this_class = SITE_URL."songs.php?semantic_class_id=".$semantic_class_id;
			echo "<b>".$ligne_class['semantic_class']."</b>";
			if(is_translator($login)) echo " <small><span style=\"color:MediumTurquoise;\">➡</span>&nbsp;<a target=\"_blank\" href=\"edit-classification.php?class=".str_replace(' ','+',$ligne_class['semantic_class_title_prefix'])."\">Edit or comment this class…</a></small>";
			}
			echo "<br /><a target=\"_blank\" href=\"".$url_this_class."\" title=\"All songs in this class\">".$ligne_class['semantic_class_title_prefix']." / ".$ligne_class['semantic_class_title']."</a>";
		echo "</td>";
		echo "<td class=\"tight\" rowspan = \"2\" style=\"text-align:center; background-color:".$color.";\"><input type=\"submit\" class=\"button\" value=\"← SAVE\">";
		if(isset($song_metadata_hilite[$id]) AND $song_metadata_hilite[$id])
			echo "<br /><br /><span style=\"color:red;\">All saved…</span>";
		echo "</td>";
		echo "</tr>";
		
		// COMMENTS 
		echo "<tr>";
		$remarks_marathi = $ligne['remarks_marathi'];
		$remarks_english = $ligne['remarks_english'];
		$group_comments = group_list(TRUE,"comment",FALSE,$id,FALSE);
		echo "<td></td>";
		echo "<td class=\"tight\" style=\"background-color:".$color."; vertical-align:top; min-width:300px;\">";
		if($group_comments['mr'] <> '') {
			echo "<i>Group comments in Marathi:</i><br />";
			echo $group_comments['mr']."<br /><br />";
			}
		echo "<i>Comment of this song in Marathi:</i><br />";
/*		if($busy_in_work_set > 0) {
			echo $remarks_marathi;
			$other_set_user = set_user($busy_in_work_set);
			echo "<small><br />← <i>in work set #".$busy_in_work_set." by ‘".$other_set_user."’</small></i>&nbsp;<br />";
			}
		else { */
		/*	$spelling_marks = spelling_marks('mr',$remarks_marathi,"red");
			if($spelling_marks <> $remarks_marathi) $display_spelling = TRUE;
			else $display_spelling = FALSE;
			if($display_spelling AND $spelling_marks <> '') echo "<small>".$spelling_marks."<br /></small>"; */
			echo "<textarea name=\"remarks_marathi".$id."\" ROWS=\"3\" style=\"width:330px;\">";
			$remarks_marathi = str_replace("<br />","\n",$remarks_marathi);
			if($remarks_marathi == '~') $remarks_marathi = '';
			echo $remarks_marathi;
			echo "</textarea>";
	//		}
		echo "</td><td class=\"tight\" style=\"background-color:".$color.";vertical-align:top;\">";
		if($group_comments['en'] <> '') {
			echo "<i>Group comments in English:</i><br />";
			echo $group_comments['en']."<br /><br />";
			}
		echo "<i>Comment of this song in English:</i><br />";
	/*	if($busy_in_work_set > 0) {
			echo $remarks_english;
			$other_set_user = set_user($busy_in_work_set);
			echo "<small><br />← <i>in work set #".$busy_in_work_set." by ‘".$other_set_user."’</small></i>&nbsp;<br />";
			}
		else { */
	/*		$spelling_marks = spelling_marks('en',$remarks_english,"red");
			if($spelling_marks <> $remarks_english) $display_spelling = TRUE;
			else $display_spelling = FALSE;
			if($display_spelling AND $spelling_marks <> '') echo "<small>".$spelling_marks."<br /></small>"; */
			echo "<textarea name=\"remarks_english".$id."\" ROWS=\"3\" style=\"width:330px;\">";
			$remarks_english = str_replace("<br />","\n",$remarks_english);
			if($remarks_english == '~') $remarks_english = '';
			echo $remarks_english;
			echo "</textarea>";
	//		}
		echo "</td>";
		echo "</form>";
		echo "</tr>";
		echo "<tr><td colspan=\"5\" style=\"padding:0px;\"><hr style=\"border-color:Gold; border-style:solid; color:Gold; border-width:3px;\"></td></tr>";
		}
	return;
	}

function reset_versions($id) {
	global $bdd;
	$query = "SELECT version, text FROM ".BASE.".translations WHERE song_id = \"".$id."\" ORDER BY version DESC";
	$result = $bdd->query($query);
	$n = $result->rowCount();
	$preceding_text = '';
	if($n == 0) $version = 1;
	else {
		$ligne = $result->fetch();
		$version = $ligne['version'];
		}
	$result->closeCursor();
	echo "<span style=\"color:MediumTurquoise;\">Resetting versions < ".$version." in song #".$id."</span><br />";
	$query_delete = "DELETE FROM ".BASE.".translations WHERE song_id = \"".$id."\" AND version > \"0\" AND version < \"".$version."\"";
//	echo $query_delete."<br />";
	$result_delete = $bdd->query($query_delete);
	if(!$result_delete) {
		echo "<span style=\"color:red;\">ERROR deleting:</span> ".$query_delete."<br />";
		return;
		}
	else $result_delete->closeCursor();
	$query_update = "UPDATE ".BASE.".translations SET version = \"1\" WHERE song_id = \"".$id."\" AND version = \"".$version."\"";
//	echo $query_update."<br />";
	$result_update = $bdd->query($query_update);
	if(!$result_update) {
		echo "<span style=\"color:red;\">ERROR updating:</span> ".$query_update."<br />";
		}
	else $result_update->closeCursor();
	return;
	}

function FixSomeGliphs($text) {
	// Fixing converted text on https://techwelkin.com/tools/transliteration/
	$text = "<|".$text."<|";
	$text = str_replace("<br","<br§",$text);
	$text = str_replace("cch","§cc§",$text);
	$text = str_replace("ch","c",$text);
	$text = str_replace("ā)a","ā)",$text); // A bug of https://techwelkin.com/tools/transliteration/
	$text = str_replace("c ","ca ",$text);
	$text = str_replace("c<","ca<",$text);
	$text = str_replace("sh","ś",$text);
	$text = str_replace("phe","fe",$text);
	$text = str_replace("e","ē",$text);
	$text = str_replace("o","ō",$text);
	$text = str_replace("īa","ī",$text);
	$text = str_replace("ṅ","ṇ",$text);
	$text = str_replace("ṇk","ṅk",$text);
	$text = str_replace("ṇg","ṅg",$text);
	$text = str_replace("ṇd","nd",$text);
	$text = str_replace("ṇc","ñc",$text);
	$text = str_replace("ṇj","ñj",$text);
	$text = str_replace("ūa)","ū)",$text);
	$text = str_replace("ūa<","ū<",$text);
	$text = str_replace("ūa ","ū ",$text);
	$text = str_replace("aṇ)","aṇa)",$text);
	$text = str_replace("aṇ<","aṇa<",$text);
	$text = str_replace("aṇ ","aṇa",$text);
	$text = str_replace("aṁ)","aṇa)",$text);
	$text = str_replace("aṁ<","aṇa<",$text);
	$text = str_replace("aṁ ","aṇa ",$text);
	$text = str_replace("ṇb","mb",$text);
	$text = str_replace("ṇp","mp",$text);
	$text = str_replace("ṇt","nt",$text);
	$text = str_replace("g)","ga)",$text);
	$text = str_replace("g<","ga<",$text);
	$text = str_replace("g ","ga ",$text);
	$text = str_replace("j<","ja<",$text);
	$text = str_replace("j)","ja)",$text);
	$text = str_replace("j ","ja ",$text);
	$text = str_replace("n<","na<",$text);
	$text = str_replace("n)","na)",$text);
	$text = str_replace("n ","na ",$text);
	$text = str_replace("ṇ<","ṇa<",$text);
	$text = str_replace("ṇ)","ṇa)",$text);
	$text = str_replace("ṇ ","ṇa ",$text);
	$text = str_replace("l)","la)",$text);
	$text = str_replace("l<","la<",$text);
	$text = str_replace("l ","la ",$text);
	$text = str_replace("p)","pa)",$text);
	$text = str_replace("p<","pa<",$text);
	$text = str_replace("p ","pa ",$text);
	$text = str_replace("t)","ta)",$text);
	$text = str_replace("t<","ta<",$text);
	$text = str_replace("t ","ta ",$text);
	$text = str_replace("s)","sa)",$text);
	$text = str_replace("s<","sa<",$text);
	$text = str_replace("s ","sa ",$text);
	$text = str_replace("ḍ)","ḍa)",$text);
	$text = str_replace("ḍ<","ḍa<",$text);
	$text = str_replace("ḍ ","ḍa ",$text);
	$text = str_replace("d)","da)",$text);
	$text = str_replace("d<","da<",$text);
	$text = str_replace("d ","da ",$text);
	$text = str_replace("ṭ)","ṭa)",$text);
	$text = str_replace("ṭ<","ṭa<",$text);
	$text = str_replace("ṭ ","ṭa ",$text);
	$text = str_replace("ḷ)","ḷa)",$text);
	$text = str_replace("ḷ<","ḷa<",$text);
	$text = str_replace("ḷ ","ḷa ",$text);
	$text = str_replace("h)","ha)",$text);
	$text = str_replace("h<","ha<",$text);
	$text = str_replace("h ","ha ",$text);
	$text = str_replace("r)","ra)",$text);
	$text = str_replace("r<","ra<",$text);
	$text = str_replace("r ","ra ",$text);
	$text = str_replace("ṣ)","ṣa)",$text);
	$text = str_replace("ṣ<","ṣa<",$text);
	$text = str_replace("ṣ ","ṣa ",$text);
	$text = str_replace("v)","va)",$text);
	$text = str_replace("v<","va<",$text);
	$text = str_replace("v ","va ",$text);
	$text = str_replace("f)","fa)",$text);
	$text = str_replace("f<","fa<",$text);
	$text = str_replace("f ","fa ",$text);
	$text = str_replace(")a",")",$text);
	$text = str_replace("§cc§","cch",$text);
	$text = str_replace("<br§","<br",$text);
	$text = str_replace("rr","ṛ",$text);
	$text = str_replace("<|",'',$text);
	return $text; 
	}
?>