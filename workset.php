<?php
// session_start();
ini_set("auto_detect_line_endings",true);
require_once("_base_urls.php");
if(isset($_SESSION['login']) AND $_SESSION['login'] <> '')
	require_once("_relier_edit.php");
	// user is allowed to write
else require_once("_relier.php");
	// user only allowed to read
require_once("_users.php");
require_once("_tasks.php");
require_once("_edit_tasks.php");

$test = FALSE;
// $test = TRUE;
$edit_mode = FALSE;

if(!check_serious_attempt('browse')) die();

$this_song_id = 0;
if(isset($_GET['this_song_id']) AND $_GET['this_song_id'] > 0) {
	$this_song_id = $_GET['this_song_id'];
	}
if(isset($_POST['this_song_id']) AND $_POST['this_song_id'] > 0) {
	$this_song_id = $_POST['this_song_id'];
	}

$canonic_url = '';
$mssg = '';
if($this_song_id == 0) $name = "Work sets";
else $name = "Detached song #".$this_song_id;
require_once("_header.php");
echo "<a name=\"top\"></a>";
if($this_song_id == 0) echo "[<a href=\"#bottom\">Bottom of page</a>]";

echo "<h2>".$name."</h2>";

if(isset($_POST['get_google'])) {
	$google_link = $_POST['google_link'];
	echo "<script type=\"text/javascript\">";
    echo "window.open('".$google_link."','_blank')";
    echo "</script>";
    $_POST['action'] = "edit_transcription";
//	$edit_mode = TRUE;
	}

if(!identified()) {
	echo "<font color=red>You need to log in to read this page.</font>";
	die();
	}
$_SESSION['try'] = 0;

$old_time = time() - 3600;
$sql = "DELETE FROM ".BASE.".t_access WHERE 'acce_time' < '".$old_time."'";
$result = $bdd->query($sql);
$result->closeCursor();

if(!is_translator($login)) {
	echo "<font color=red>Access to this page is only granted to translators and admin.</font>";
	die();
	}
$user = $login;

$show_errors = TRUE;
$fix = FALSE;
// if(is_super_admin($user)) fix_versions($show_errors,$fix);

$short_display = FALSE;
if($this_song_id > 0) $short_display = FALSE;

$set_id = current_workset_id($login);
$warning_glossary = array();

$temp_filename = "temp";
$olddir =  getcwd();
chdir("WORK_FILES");
if(!file_exists($login)) {
	echo "<font color=red>Created ‘".$login."’ folder</font><br />";
	$cmd = "mkdir ".$login;
	exec($cmd);
	}
if(!file_exists($login)) {
	echo "<font color=red>ERROR 6: ‘".$login."’ folder could not be created</font><br />";
	die();
	}
else {
	chdir($login);
	if(isset($_POST['action']) AND $_POST['action'] == "discard_file") {
		$type = $_POST['type'];
		$filename = $temp_filename."_".$type.".txt";
		if(file_exists($filename)) {
			$cmd = "rm ".$filename;
			exec($cmd);
			}
		}
	}
chdir($olddir);

$change_class_msg = $hilite_song_class = $hilite_song_transcription = $hilite_song_translation = $hilite_song_comment = array();

if(isset($_GET['limit_workset'])) {
	$limit_workset = $_GET['limit_workset'];
	$_SESSION['limit_workset'] = $limit_workset;
	}
else if(isset($_SESSION['limit_workset']))
		$limit_workset = $_SESSION['limit_workset'];
else $limit_workset = "less";

if(isset($_POST['action']) AND $_POST['action'] == "change_owner") {
	$set_id = $_POST['set_id'];
	$user = $_POST['user'];
	$new_owner = $_POST['new_owner'];
	echo "<font color=blue>Changing owner of set</font> #".$set_id." <font color=blue>to</font> ‘".$new_owner."’<br />";
	if(!isset($user_role[$new_owner])) {
		echo "<font color=red>ERROR 7:</font> ‘".$new_owner."’ <font color=red>is not a known user!</font><br />";
		}
	else {
		$query_update = "UPDATE ".BASE.".workset SET login = \"".$new_owner."\" WHERE set_id = \"".$set_id."\"";
		$result_update = $bdd->query($query_update);
		if(!$result_update) {
			echo "<br /><font color=red>ERROR 8 modifying table:</font> ".$query_update."<br />";
			die();
			}
		$result_update->closeCursor();
		}
	}

if(isset($_POST['action']) AND $_POST['action'] == "move_song") {
	$song_id = $_POST['song_id'];
	$hilite_song_class[$song_id] = TRUE;
	$new_set_id = $_POST['new_set_id'];
	if($new_set_id <> $set_id) {
		$workset_parameters = workset_parameters($new_set_id);
		if(($workset_parameters['status'] == "submit" OR $workset_parameters['status'] == "stored") AND $workset_parameters['login'] == $login) {
		//	echo "OK move"; die();
			$query_update = "UPDATE ".BASE.".workset SET set_id = \"".$new_set_id."\", status = \"submit\" WHERE song_id = \"".$song_id."\"";
			$result_update = $bdd->query($query_update);
			if(!$result_update) {
				echo "<br /><font color=red>ERROR 9 modifying table:</font> ".$query_update."<br />";
				die();
				}
			$result_update->closeCursor();
			echo "<blockquote><font color=green>Song</font> #".$song_id ." <font color=green>successfully moved to work set</font> #".$new_set_id."</blockquote>";
			}
		}
	$edit_mode = TRUE;
	}

if(isset($_POST['action']) AND $_POST['action'] == "glossary") {
	foreach($_POST as $key => $value) {
		if(is_integer(strpos($key,"definition_"))) {
			change_definition($key,$value);
			$edit_mode = TRUE;
			}
		}
	if(isset($_POST['new_word']) AND $_POST['new_word'] <> '') {
		$curr_song_id = $_POST['song_id'];
		$word = str_replace(' ','_',trim(simple_form($_POST['new_word'])));
		$word = str_replace("'","’",$word);
		if($word <> '') {
			$plural = str_replace(' ','_',trim(simple_form($_POST['new_plural'])));
			$plural = str_replace("'","’",$plural);
			if(isset($_POST['new_force_case'])) $force_case = 1;
			else $force_case = 0;
			$create_glossary_entry = create_glossary_entry($word,$plural,$curr_song_id,$force_case);
			
			$warning_glossary[$curr_song_id] = $create_glossary_entry['warning'];
			echo $create_glossary_entry['already'];
			}
		}
	$edit_mode = TRUE;
	}

if(isset($_POST['action']) AND $_POST['action'] == "save_comments") {
//	save_remarks($_POST['song_id'],$login);
	$song_id = $_POST['song_id'];
	$hilite_song_comment[$_POST['song_id']] = TRUE;
	$remarks_marathi = fix_typo(reshape_entry($_POST['remarks_marathi']),0);
	$remarks_english = fix_typo(reshape_entry($_POST['remarks_english']),0);
	$query_update = "UPDATE ".BASE.".workset SET remarks_marathi = \"".$remarks_marathi."\", remarks_english = \"".$remarks_english."\" WHERE song_id = \"".$song_id."\" AND status=\"current\"";
	$result_update = $bdd->query($query_update);
	if(!$result_update) {
		echo "<br /><font color=red>ERROR 10 modifying table:</font> ".$query_update."<br />";
		die();
		}
	$result_update->closeCursor();
	$edit_mode = TRUE;
	}
				
if(isset($_POST['action']) AND $_POST['action'] == "delete_current_work_set") {
	$old_set_id = $_POST['set_id'];
	delete_workset($old_set_id);
	}
	
if(isset($_POST['action']) AND $_POST['action'] == "change_translation") {
	$song_id = $_POST['song_id'];
	$set_id = $_POST['set_id'];
	$user = $_POST['user'];
	$status = $_POST['status']; 
	$translation = fix_typo(reshape_entry($_POST['translation']),0);
	$translation_correction_english = ReadRewriteRules("english");
	$translation = apply_rules(TRUE,TRUE,$translation,$translation_correction_english);
	$translation = str_replace('_',' ',$translation);
	$translation = str_replace("||","’",$translation);
	StoreSpelling(FALSE,'en',$translation);
	$query_update = "UPDATE ".BASE.".workset SET translation = \"".$translation."\", editor = \"".$login."\" WHERE song_id = \"".$song_id."\" AND status=\"".$status."\"";
	$result_update = $bdd->query($query_update);
	if(!$result_update) {
		echo "<br /><font color=red>ERROR 11 modifying table:</font> ".$query_update."<br />";
		die();
		}
	$result_update->closeCursor();
	$hilite_song_translation[$song_id] = TRUE;
	}

if(isset($_POST['action']) AND $_POST['action'] == "change_transcription") {
	$song_id = $_POST['song_id'];
	$set_id = $_POST['set_id'];
	$user = $_POST['user'];
	$status = $_POST['status']; 
	$devanagari = fix_typo(reshape_entry($_POST['devanagari']),0);
	StoreSpelling(FALSE,'mr',$devanagari);
	$devanagari_words = all_words('',$devanagari);
	$old_devanagari = fix_typo(reshape_entry($_POST['old_devanagari']),0);
	$post_roman = $_POST['roman'];
	/* if(is_integer(strpos($post_roman,"<br>"))) $done_by_google = TRUE;
	else $done_by_google = FALSE; */
	$done_by_google = done_by_google($post_roman);
	if($done_by_google) ResetLexicon('ro',$id);
	$roman = mb_strtolower(reshape_entry($post_roman));
	$roman = CleanGoogleQuotes($roman);
	$roman_words = all_words('',$roman);
	if($done_by_google AND count($roman_words) == count($devanagari_words)) {
		$done_learn = LearnTransliteration($song_id,$devanagari_words,$roman_words,$done_by_google);
		if($done_learn) echo "<br /><small><span style=\"color:red;\">➡ </span><a href=\"transliteration-rules.php?mode=recent\" target=\"_blank\">Display recent changes in transliteration rules</a></small><br />";
		}
	else {
		if($old_devanagari <> $devanagari OR mb_strlen($roman) < 10) {
			$roman = Transliterate($song_id,"<br />",$devanagari);
			}
		}
	if(mb_strlen($devanagari) > 10) {
		$query_update = "UPDATE ".BASE.".workset SET devanagari = \"".$devanagari."\", word_ids = \"\", editor = \"".$login."\", word_ids = \"\" WHERE song_id = \"".$song_id."\" AND status=\"".$status."\"";
		$result_update = $bdd->query($query_update);
		if(!$result_update) {
			echo "<br /><font color=red>ERROR 12 modifying table:</font> ".$query_update."<br />";
			die();
			}
		$result_update->closeCursor();
		if($done_by_google) StoreSpelling(FALSE,'ro',$roman);
		$query_update = "UPDATE ".BASE.".workset SET roman = \"".$roman."\", editor = \"".$login."\" WHERE song_id = \"".$song_id."\" AND status=\"".$status."\"";
		$result_update = $bdd->query($query_update);
		if(!$result_update) {
			echo "<br /><font color=red>ERROR 13 modifying table:</font> ".$query_update."<br />";
			die();
			}
		$result_update->closeCursor();
		}
	$hilite_song_transcription[$song_id] = TRUE;
	}

if($test AND isset($_POST['action'])) echo "post[action] = ".$_POST['action']."<br />";

if(is_admin($login) AND isset($_POST['action']) AND $_POST['action'] == "validate") {
	$set_id = $_POST['set_id'];
	$user = $_POST['user'];
	validate_workset($user,$set_id);
	echo "<blockquote><font color=red>Work set </font>#".$set_id ." <font color=red>of</font> ‘".$user."’ <font color=red>has been validated</font></blockquote>";
	}

$apply_typo_rules = FALSE;

if(isset($_POST['action']) AND ($_POST['action'] == "change_transcription" OR $_POST['action'] == "edit_transcription" OR $_POST['action'] == "change_translation" OR $_POST['action'] == "edit_translation"  OR $_POST['action'] == "edit_comments" OR $_POST['action'] == "save_comments" OR $_POST['action'] == "glossary")) $edit_mode = TRUE;
// else $edit_mode = FALSE;

if(is_admin($login) AND isset($_POST['action']) AND $_POST['action'] == "fix_typo") {
	$typo_set_id = $_POST['set_id'];
	$user = $_POST['user'];
	$apply_typo_rules = TRUE;
//	echo $user." ".$typo_set_id."<br />";
	}

if(isset($_POST['action']) AND $_POST['action'] == "unvalidate") {
	$set_id = $_POST['set_id'];
	$user = $_POST['user'];
	unvalidate_workset($user,$set_id);
	}
		
if(isset($_POST['action']) AND $_POST['action'] == "process_file") {
	// Read and interpret the file that the translator has just uploaded
	$try = $_POST['try'];
	$user = $_POST['user'];
	$set_id = $_POST['set_id'];
	$status = $_POST['status'];
	$type = $_POST['type'];
//	echo "@".$status."@<br />";
	$errors = interpret_temp_file($user,$set_id,$temp_filename,$try,$status,$type);
	if($try == "yes" /* OR $errors > 0 */) {
		echo "<table>";
		echo "<tr>";
		echo "<td class=\"tight\" style=\"text-align:center; background-color:Bisque;\">";
		echo "<form name=\"ok to read\" method=\"post\" action=\"workset.php\" enctype=\"multipart/form-data\">";
		echo "<input type=\"hidden\" name = \"action\" value = \"process_file\" />";
		echo "<input type=\"hidden\" name = \"try\" value = \"no\" />";
		echo "<input type=\"hidden\" name = \"user\" value = \"".$user."\" />";
		echo "<input type=\"hidden\" name = \"set_id\" value = \"".$set_id."\" />";
		echo "<input type=\"hidden\" name = \"status\" value = \"".$status."\" />";
		echo "<input type=\"hidden\" name = \"type\" value = \"".$type."\" />";
		if($errors > 0)
			echo "<p>CANNOT IMPORT: ".$errors." error(s) found…</p>";
		else
			echo "<p><input type=\"submit\" class=\"button\" value=\"OK, IMPORT DATA\"></p>";
		echo "</form>";
		echo "</td>";
		echo "<td class=\"tight\" style=\"text-align:center; background-color:Bisque;\">";
		echo "<form name=\"discard_file\" method=\"post\" action=\"workset.php\" enctype=\"multipart/form-data\">";
		echo "<input type=\"hidden\" name = \"action\" value = \"discard_file\" />";
		echo "<input type=\"hidden\" name = \"type\" value = \"".$type."\" />";
		echo "<input type=\"submit\" class=\"button\" value=\"CANCEL IMPORT\">";
		echo "</form>";
		echo "</td>";
		echo "</tr>";
		echo "</table>";
		echo "<a name=\"bottom\"></a>";
		echo "[<a href=\"#top\">Top of page</a>]<br />";	
		die();
		}
	}

if(isset($_POST['action']) AND $_POST['action'] == "load_file" AND isset($_FILES["file"]["name"]) AND $_FILES["file"]["name"] <> '') {
	$upload_message = '';
	$user = $_POST['user'];
	$set_id = $_POST['set_id'];
	$status = $_POST['status'];
	$type = $_POST['type'];
	if(($_FILES["file"]["type"] == "text/plain") AND ($_FILES["file"]["size"] < MAXFILESIZE)) {
		if($_FILES["file"]["error"] > 0) {
			$upload_message .= "<font color=red>ERROR 14: ".$_FILES["file"]["error"]."</font><br />";
			}
		else {
			$upload_message .= "<font color=green>Uploaded: ".$_FILES["file"]["name"]."<br />Type: ".$_FILES["file"]["type"]."<br />Size: ".($_FILES["file"]["size"] / 1024) ." Kb</font>";
			$filename = $temp_filename."_".$type.".txt";
			move_uploaded_file($_FILES["file"]["tmp_name"],"WORK_FILES/".$login."/".$filename);
			}
		}
	else {
		$upload_message .= "<font color=red>Incorrect file: ".$_FILES["file"]["name"]."<br />(Should be TXT with size < ".MAXFILESIZE.")</font>";
		}
	echo $upload_message;
	echo "<table>";
	echo "<tr>";
	echo "<td class=\"tight\" style=\"text-align:center; background-color:Bisque;\">";
	echo "<form name=\"ok to read\" method=\"post\" action=\"workset.php\" enctype=\"multipart/form-data\">";
	echo "<input type=\"hidden\" name = \"action\" value = \"process_file\" />";
	echo "<input type=\"hidden\" name = \"user\" value = \"".$user."\" />";
	echo "<input type=\"hidden\" name = \"set_id\" value = \"".$set_id."\" />";
	echo "<input type=\"hidden\" name = \"status\" value = \"".$status."\" />";
	echo "<input type=\"hidden\" name = \"type\" value = \"".$type."\" />";
	echo "<input type=\"hidden\" name = \"try\" value = \"yes\" />";
	echo "<p><input type=\"submit\" class=\"button\" value=\"OK, READ THIS FILE\"></p>";
	echo "</form>";
	echo "</td>";
	echo "<td class=\"tight\" style=\"text-align:center; background-color:Bisque;\">";
	echo "<form name=\"discard_file\" method=\"post\" action=\"workset.php\" enctype=\"multipart/form-data\">";
	echo "<input type=\"hidden\" name = \"action\" value = \"discard_file\" />";
	echo "<input type=\"hidden\" name = \"type\" value = \"".$type."\" />";
	echo "<input type=\"submit\" class=\"button\" value=\"CANCEL\">";
	echo "</form>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";	
	die();
	}

$import = '';
if(is_admin($login) AND isset($_POST['action']) AND $_POST['action'] == "import") {
	$user = $_POST['user'];
	$set_id = $_POST['set_id'];
	echo "<font color=red>Import a file to update work set </font>#".$set_id;
	echo "<table>";
	echo "<tr>";
	echo "<td class=\"tight\" style=\"text-align:center; background-color:Bisque;\">";
	echo "<form name=\"ok to read\" method=\"post\" action=\"workset.php\" enctype=\"multipart/form-data\">";
	echo "<input type=\"hidden\" name = \"action\" value = \"import_type\" />";
	echo "<input type=\"hidden\" name = \"user\" value = \"".$user."\" />";
	echo "<input type=\"hidden\" name = \"type\" value = \"translation\" />";
	echo "<input type=\"hidden\" name = \"set_id\" value = \"".$set_id."\" />";
	echo "<input type=\"hidden\" name = \"try\" value = \"yes\" />";
	echo "<p><input type=\"submit\" class=\"button\" value=\"IMPORT TRANSLATION\"></p>";
	echo "</form>";
	echo "</td>";
	
	echo "<td class=\"tight\" style=\"text-align:center; background-color:Bisque;\">";
	echo "<form name=\"ok to read\" method=\"post\" action=\"workset.php\" enctype=\"multipart/form-data\">";
	echo "<input type=\"hidden\" name = \"action\" value = \"import_type\" />";
	echo "<input type=\"hidden\" name = \"user\" value = \"".$user."\" />";
	echo "<input type=\"hidden\" name = \"type\" value = \"devanagari\" />";
	echo "<input type=\"hidden\" name = \"set_id\" value = \"".$set_id."\" />";
	echo "<input type=\"hidden\" name = \"try\" value = \"yes\" />";
	echo "<p><input type=\"submit\" class=\"button\" value=\"IMPORT DEVANAGARI\"></p>";
	echo "</form>";
	echo "</td>";
	
	echo "<td class=\"tight\" style=\"text-align:center; background-color:Bisque;\">";
	echo "<form name=\"ok to read\" method=\"post\" action=\"workset.php\" enctype=\"multipart/form-data\">";
	echo "<input type=\"hidden\" name = \"action\" value = \"import_type\" />";
	echo "<input type=\"hidden\" name = \"user\" value = \"".$user."\" />";
	echo "<input type=\"hidden\" name = \"type\" value = \"roman\" />";
	echo "<input type=\"hidden\" name = \"set_id\" value = \"".$set_id."\" />";
	echo "<input type=\"hidden\" name = \"try\" value = \"yes\" />";
	echo "<p><input type=\"submit\" class=\"button\" value=\"IMPORT ROMAN\"></p>";
	echo "</form>";
	echo "</td>";
	
	echo "<td class=\"tight\" style=\"text-align:center; background-color:Bisque;\">";
	echo "<form name=\"discard_file\" method=\"post\" action=\"workset.php\" enctype=\"multipart/form-data\">";
	echo "<input type=\"submit\" class=\"button\" value=\"CANCEL\">";
	echo "</form>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";	
	die();
	}
	
if(is_admin($login) AND isset($_POST['action']) AND $_POST['action'] == "import_type") {
	$import = "admin";
	$status = "submit";
	$user = $_POST['user'];
	$type = $_POST['type'];
	$set_id = $_POST['set_id'];
	}
if(isset($_GET['import']) AND $_GET['import'] == "yes") {
	$import = "user";
	$status = "current";
	$type = $_GET['type'];
	$user = $login;
	$set_id = current_workset_id($login);
	}
if($import <> '') {
	echo "<form action=\"workset.php\" METHOD = \"POST\" ENCTYPE=\"multipart/form-data\">";
	echo "<input type=\"hidden\" name = \"action\" value = \"load_file\" />";
	echo "<input type=\"hidden\" name = \"user\" value = \"".$user."\" />";
	echo "<input type=\"hidden\" name = \"set_id\" value = \"".$set_id."\" />";
	echo "<input type=\"hidden\" name = \"status\" value = \"".$status."\" />";
	echo "<input type=\"hidden\" name = \"type\" value = \"".$type."\" />";
    echo "<label for=\"file\">Filename (".$type."): </label>";
	echo "<input type=\"file\" name=\"file\" id=\"file\" />";
	echo "➡&nbsp;<input type=\"submit\" class=\"button\" name=\"submit\" value=\"IMPORT FILE\" /></form>";
	echo "<form action=\"workset.php\" METHOD = \"POST\" ENCTYPE=\"multipart/form-data\">";
	echo "<input type=\"submit\" class=\"button\" name=\"submit\" value=\"CANCEL\" /></form>";
	die();
	}

if((isset($_POST['action']) AND $_POST['action'] == "submit_this")) {
	$set_id = $_POST['set_id'];
	$query_update = "UPDATE ".BASE.".workset SET status = \"submit\" WHERE login = \"".$login."\" AND set_id = \"".$set_id."\"";
	$result_update = $bdd->query($query_update);
	if(!$result_update) {
		echo "<br /><font color=red>ERROR 15 modifying table:</font> ".$query_update."<br />";
		die();
		}
	$result_update->closeCursor();
	}

if((isset($_POST['action']) AND $_POST['action'] == "store_current")) {
	if(isset($_POST['move_translations'])) {
		$query_maxid = "SELECT set_id FROM ".BASE.".workset ORDER BY set_id DESC";
		$result_maxid = $bdd->query($query_maxid);
		$ligne_maxid = $result_maxid->fetch();
		$max_set_id = $ligne_maxid['set_id'];
		$result_maxid->closeCursor();
		$max_set_id++;
		echo "<p>Creating set #".$max_set_id."</p>";
		$query_update = "UPDATE ".BASE.".workset SET set_id = \"".$max_set_id."\", status = \"stored\" WHERE login = \"".$login."\" AND status = \"current\" AND translation <> \"\"";
		$result_update = $bdd->query($query_update);
		if(!$result_update) {
			echo "<br /><font color=red>ERROR 16 modifying table:</font> ".$query_update."<br />";
			die();
			}
		$result_update->closeCursor();
		}
	else {
		$query_update = "UPDATE ".BASE.".workset SET status = \"stored\" WHERE login = \"".$login."\" AND status = \"current\"";
		$result_update = $bdd->query($query_update);
		if(!$result_update) {
			echo "<br /><font color=red>ERROR 16 modifying table:</font> ".$query_update."<br />";
			die();
			}
		$result_update->closeCursor();
		}
	}

$export = '';
if(is_admin($login) AND isset($_POST['action']) AND $_POST['action'] == "export") {
	$export = "admin";
	$user = $_POST['user'];
	$set_id = $_POST['set_id'];
	}
if(isset($_GET['export']) AND $_GET['export'] == "yes") {
	$export = "user";
	$user  = $login;
	$set_id = current_workset_id($login);
	}
if($export <> '') {
	$olddir =  getcwd();
	chdir("WORK_FILES");
	chdir($login);
	// echo $export."<br />";
	if($export == "user") $filename1 = "translations.txt";
	else $filename1 = "set_".$set_id."_translations.txt";
	$export_file = fopen($filename1,'w');
	$query = "SELECT * FROM ".BASE.".workset WHERE login = \"".$user."\" AND set_id = \"".$set_id."\" ORDER BY semantic_class_id";
	$result = $bdd->query($query);
	if($result) {
		$old_semantic_class_id = '';
		while($ligne = $result->fetch()) {
			$song_id = $ligne['song_id'];
			$translation_english = $ligne['translation'];
			$semantic_class_id = $ligne['semantic_class_id'];
			if($semantic_class_id <> $old_semantic_class_id) {
				$old_semantic_class_id = $semantic_class_id;
				$query_class = "SELECT semantic_class FROM ".BASE.".classification WHERE semantic_class_id = \"".$semantic_class_id."\"";
				$result_class = $bdd->query($query_class);
				$ligne = $result_class->fetch();
				$result_class->closeCursor();
				$semantic_class = $ligne['semantic_class'];
				fprintf($export_file,"\r\n%s\r\n\r\n",$semantic_class);
				}
			if($translation_english == '') $translation_english = transcription($song_id,"translation");
			if($translation_english == '') $translation_english = "...<br />...<br />";
			$text = str_replace("<br />","\n",$translation_english);
			$text = fix_number_of_lines($text,3);
			fprintf($export_file,"%s\r\n",$song_id."\r\n".$text);
			}
		}
	fclose($export_file);
	if($export == "user") $filename2 = "devanagari.txt";
	else $filename2 = "set_".$set_id."_devanagari.txt";
	$export_file = fopen($filename2,'w');
	fprintf($export_file,"%s\r\n","// Devanagari transcription file\n// This file can be edited and uploaded back to a work set\n// Select output format ‘ISO’");
	$query = "SELECT * FROM ".BASE.".workset WHERE login = \"".$user."\" AND set_id = \"".$set_id."\" ORDER BY semantic_class_id";
	$result = $bdd->query($query);
	if($result) {
		$old_semantic_class_id = '';
		while($ligne = $result->fetch()) {
			$song_id = $ligne['song_id'];
			$semantic_class_id = $ligne['semantic_class_id'];
			if($semantic_class_id <> $old_semantic_class_id) {
				$old_semantic_class_id = $semantic_class_id;
				$query_class = "SELECT semantic_class FROM ".BASE.".classification WHERE semantic_class_id = \"".$semantic_class_id."\"";
				$result_class = $bdd->query($query_class);
				$ligne = $result_class->fetch();
				$result_class->closeCursor();
				$semantic_class = $ligne['semantic_class'];
				fprintf($export_file,"\r\n%s\r\n\r\n",$semantic_class);
				}
			$text = str_replace("<br />","\n",transcription($song_id,"devanagari"));
			fprintf($export_file,"%s\r\n",$song_id."\r\n".$text);
			}
		}
	fclose($export_file);
	if($export == "user") $filename3 = "roman.txt";
	else $filename3 = "set_".$set_id."_roman.txt";
	$export_file = fopen($filename3,'w');
	$query = "SELECT * FROM ".BASE.".workset WHERE login = \"".$user."\" AND set_id = \"".$set_id."\" ORDER BY semantic_class_id";
	$result = $bdd->query($query);
	if($result) {
		$old_semantic_class_id = '';
		while($ligne = $result->fetch()) {
			$song_id = $ligne['song_id'];
			$semantic_class_id = $ligne['semantic_class_id'];
			if($semantic_class_id <> $old_semantic_class_id) {
				$old_semantic_class_id = $semantic_class_id;
				$query_class = "SELECT semantic_class FROM ".BASE.".classification WHERE semantic_class_id = \"".$semantic_class_id."\"";
				$result_class = $bdd->query($query_class);
				$ligne = $result_class->fetch();
				$result_class->closeCursor();
				$semantic_class = $ligne['semantic_class'];
				fprintf($export_file,"\r\n%s\r\n\r\n",$semantic_class);
				}
			$text = str_replace("<br />","\n",transcription($song_id,"roman"));
			fprintf($export_file,"%s\r\n",$song_id."\r\n".$text);
			}
		}
	fclose($export_file);
	if($export == "user") $filename4 = "combined.txt";
	else $filename4 = "set_".$set_id."_combined.txt";
	$export_file = fopen($filename4,'w');
	$query = "SELECT * FROM ".BASE.".workset WHERE login = \"".$user."\" AND set_id = \"".$set_id."\" ORDER BY semantic_class_id";
	$result = $bdd->query($query);
	if($result) {
		while($ligne = $result->fetch()) {
			$song_id = $ligne['song_id'];
			$other_work_set = other_work_set($song_id,'','valid');
			if($other_work_set > 0 AND $other_work_set <> $set_id AND $export == "admin") continue;
			$translation_english = $ligne['translation'];
			if($translation_english == '') $translation_english = trim(transcription($song_id,"translation"));
			if($translation_english == '') $translation_english = "...<br />...";
			$text2 = str_replace("<br />",chr(10),$translation_english);
			$text1 = str_replace("<br />",chr(10),trim(transcription($song_id,"devanagari")));
		//	fprintf($export_file,"%s\r\n",'"'.$song_id.'"'.";".'"'.trim($text1).'"'.";".'"'.trim($text2).'"');
			fprintf($export_file,"%s\r\n",$song_id."\t".$text1."\t".$text2);
			}
		}
	fclose($export_file);
	
	echo "<font color=red>Please download the</font> <a href=\"WORK_FILES/".$login."/".$filename1."\">translation work file</a> ";
echo "<font color=red>and the</font> <a href=\"WORK_FILES/".$login."/".$filename2."\">Devanagari transcription work file</a> <font color=red>and the</font> <a href=\"WORK_FILES/".$login."/".$filename3."\">Roman transcription work file</a> <font color=red>and the</font> <a href=\"WORK_FILES/".$login."/".$filename4."\">combined work file</a> ";
	echo "<font color=red>just created for set </font>#".$set_id."<br />";
	echo "<form name=\"ok\" method=\"post\" action=\"workset.php\" enctype=\"multipart/form-data\">";
	echo "<p><input type=\"submit\" class=\"button\" value=\"OK, DONE\"></p>";
	echo "</form>";
	die();
	chdir($olddir);
	}

if($test AND isset($_POST['action'])) echo "542@@post[action] = ".$_POST['action']."<br />";

foreach($_POST as $key => $value) {
	if($key == "action") continue;
	if(is_integer(strpos($key,"unstore_"))) {
		$set_id = str_replace("unstore_",'',$key);
		// First store current set if any
		$query_update = "UPDATE ".BASE.".workset SET status = \"stored\" WHERE login = \"".$login."\" AND status = \"current\"";
		$result_update = $bdd->query($query_update);
		if(!$result_update) {
			echo "<br /><font color=red>ERROR 17 modifying table:</font> ".$query_update."<br />";
			die();
			}
		$result_update->closeCursor();
		// Move stored set to current set
		$query_update = "UPDATE ".BASE.".workset SET status = \"current\" WHERE login = \"".$login."\" AND status = \"stored\" AND set_id = \"".$set_id."\"";
		$result_update = $bdd->query($query_update);
		if(!$result_update) {
			echo "<br /><font color=red>ERROR 18 modifying table:</font> ".$query_update."<br />";
			die();
			}
		$result_update->closeCursor();
		$_SESSION['fixed_typo_current_workset'] = FALSE;
		break;
		}
		
	if(is_integer(strpos($key,"add_group_"))) {
		$set_id = str_replace("add_group_",'',$key);
		$new_group_label = trim($_POST['new_group_label']);
		$new_group_label = str_replace(' ','_',$new_group_label);
		$new_group_label = str_replace('"','',$new_group_label);
		$new_group_label = str_replace('‘','',$new_group_label);
		$new_group_label = str_replace('’','',$new_group_label);
		$new_group_label = str_replace("'",'',$new_group_label);
		if($new_group_label <> '') {
			$query_songs = "SELECT song_id FROM ".BASE.".workset WHERE set_id = \"".$set_id."\"";
			$result_songs = $bdd->query($query_songs);
			if($result_songs) $n_songs = $result_songs->rowCount();
			else $n_songs = 0;
			if($n_songs > 0) {
				while($ligne_songs = $result_songs->fetch()) {
					$song_id = $ligne_songs['song_id'];
					assign_group($song_id,$new_group_label);
					}
				}
			if($result_songs) $result_songs->closeCursor();
			}
		break;
		}
	
	if(is_integer(strpos($key,"remove_group_"))) {
		$set_id = str_replace("remove_group_",'',$key);
		$old_group_label = trim($_POST['old_group_label']);
		$old_group_label = str_replace(' ','_',$old_group_label);
		$old_group_label = str_replace('"','',$old_group_label);
		$old_group_label = str_replace('‘','',$old_group_label);
		$old_group_label = str_replace('’','',$old_group_label);
		$old_group_label = str_replace("'",'',$old_group_label);
		if($old_group_label <> '') {
			$query_songs = "SELECT song_id FROM ".BASE.".workset WHERE set_id = \"".$set_id."\"";
			$result_songs = $bdd->query($query_songs);
			if($result_songs) $n_songs = $result_songs->rowCount();
			else $n_songs = 0;
			if($n_songs > 0) {
				while($ligne_songs = $result_songs->fetch()) {
					$song_id = $ligne_songs['song_id'];
					$remove_group = remove_group($song_id,$old_group_label);
					}
				}
			if($result_songs) $result_songs->closeCursor();
			}
		$edit_mode = TRUE;
		break;
		}
		
	if(is_integer(strpos($key,"unsubmit_"))) {
		$set_id = str_replace("unsubmit_",'',$key);
		// First store current set if any
		$query_update = "UPDATE ".BASE.".workset SET status = \"stored\" WHERE login = \"".$login."\" AND status = \"current\"";
		$result_update = $bdd->query($query_update);
		if(!$result_update) {
			echo "<br /><font color=red>ERROR 19 modifying table:</font> ".$query_update."<br />";
			die();
			}
		$result_update->closeCursor();
		// Move submitted set to current set
		$query_update = "UPDATE ".BASE.".workset SET status = \"current\" WHERE login = \"".$login."\" AND status = \"submit\" AND set_id = \"".$set_id."\"";
		$result_update = $bdd->query($query_update);
		if(!$result_update) {
			echo "<br /><font color=red>ERROR 20 modifying table:</font> ".$query_update."<br />";
			die();
			}
		$result_update->closeCursor();
		break;
		}
	
	if(is_integer(strpos($key,"classify_forgood_"))) {
		$song_id = str_replace("classify_forgood_",'',$key);
		$semantic_class_id = $_POST['new_class'];
		$flag = "song".$song_id;
		echo "<blockquote><font color=red>Reclassifed song</font> #<a href=\"workset.php#".$flag."\">".$song_id."</a> <font color=red>to ".$semantic_class_id."</font></blockquote>";
		$hilite_song_class[$song_id] = TRUE;
		
		// Enter this change in table ‘song_metadata’
		$query_there = "SELECT song_id FROM ".BASE.".song_metadata WHERE song_id = \"".$song_id."\" AND devanagari = \"\" AND roman_devanagari = \"\"";
		$result_there = $bdd->query($query_there);
		$n_there = $result_there->rowCount();
		if($n_there > 0) $already_there_metadata = TRUE;
		else $already_there_metadata = FALSE;
		if($already_there_metadata) {
			$query_update = "UPDATE ".BASE.".song_metadata SET semantic_class_id = \"".$semantic_class_id."\", login = \"".$login."\" WHERE song_id = \"".$song_id."\" AND devanagari = \"\" AND roman_devanagari = \"\"";
			$result_update = $bdd->query($query_update);
			if(!$result_update) {
				echo "<br /><font color=red>ERROR 21 modifying table:</font> ".$query_update."<br />";
				die();
				}
			$result_update->closeCursor();
			}
		else {
			$query_update = "INSERT INTO ".BASE.".song_metadata (song_id, semantic_class_id, login, devanagari, roman_devanagari, remarks_marathi, remarks_english) VALUES (\"".$song_id."\",\"".$semantic_class_id."\",\"".$login."\",\"\",\"\",\"\",\"\")";
			$result_update = $bdd->query($query_update);
			if(!$result_update) {
				echo "<br /><font color=red>ERROR 1 modifying table:</font> ".$query_update."<br />";
				die();
				}
			$result_update->closeCursor();
			}
			
		// Enter this change in table ‘SONGS’
		$query_update = "UPDATE ".BASE.".songs SET semantic_class_id = \"".$semantic_class_id."\" WHERE song_id = \"".$song_id."\"";
		$result_update = $bdd->query($query_update);
		if(!$result_update) {
			echo "<br /><font color=red>ERROR 22 modifying table:</font> ".$query_update."<br />";
			die();
			}
		$result_update->closeCursor();
		
		// Enter this change in table ‘workset’
		$query_update = "UPDATE ".BASE.".workset SET semantic_class_id = \"".$semantic_class_id."\" WHERE song_id = \"".$song_id."\"";
		$result_update = $bdd->query($query_update);
		if(!$result_update) {
			echo "<br /><font color=red>ERROR 23 modifying table:</font> ".$query_update."<br />";
			die();
			}
		$result_update->closeCursor();
		break;
		}
	if(is_integer(strpos($key,"classify_")) AND $value == "yes") {
		$song_id = str_replace("classify_",'',$key);
		$post_class_id = "class_".$song_id;
		$semantic_class_id = $_POST[$post_class_id];
		$old_semantic_class_id = semantic_class_id($song_id);
		$query_class = "SELECT semantic_class, semantic_class_title, semantic_class_title_prefix FROM ".BASE.".classification WHERE semantic_class_id = \"".$semantic_class_id."\"";
		if($test) echo "query_class = ".$query_class."<br />";
		$result_class = $bdd->query($query_class);
		$n = $result_class->rowCount();
		if($n == 0) {
			$change_class_msg[$song_id] = "<font color=red>Unable to reclassify this song to </font>‘".$semantic_class_id."’<br /><font color=red>This code does not match any entry in the classification</font> (<a target=\"_blank\" href=\"classification.php\">see table</a>)";
			$hilite_song_class[$song_id] = TRUE;
			break;
			}
		$flag = "song".$song_id;
		if($semantic_class_id <> $old_semantic_class_id) {
			$ligne = $result_class->fetch();
			$result_class->closeCursor();
			$semantic_class = $ligne['semantic_class'];
			$semantic_class_title_prefix = $ligne['semantic_class_title_prefix'];
			$semantic_class_title = $ligne['semantic_class_title'];
			$semantic_class_text = semantic_class_text($semantic_class_title_prefix,$semantic_class_title);
			echo "<blockquote><font color=red>You wish to reclassify song </font>#".$song_id." <font color=red>to </font>".$semantic_class_id."<br /><br />";
			echo "<b><font color=green>".$semantic_class." </b></font>(".$semantic_class_id.") <font color=green><b>— ".$semantic_class_text."</b></font><br />";
			echo "<font color=red>… will replace:</font><br />";
			echo semantic_class_text_of_song($song_id)."<br />";
			echo "<table>";
			echo "<tr>";
			echo "<td class=\"tight\"><font color=red>Do you confirm this change?</font></td>";
			echo "<td class=\"tight\">";
			echo "<form name=\"confirm_change\" method=\"post\" action=\"workset.php#".$flag."\" enctype=\"multipart/form-data\">";
			echo "<input type=\"hidden\" name=\"classify_forgood_".$song_id."\" value=\"yes\">";
			echo "<input type=\"hidden\" name=\"new_class\" value=\"".$semantic_class_id."\">";
			echo "<input type=\"submit\" class=\"button\" value=\"YES\">";
			echo "</form>";
			echo "</td>";
			echo "<td class=\"tight\">";
			echo "<form name=\"refuse_change\" method=\"post\" action=\"workset.php#".$flag."\" enctype=\"multipart/form-data\">";
			echo "<input type=\"submit\" class=\"button\" value=\"NO\">";
			echo "</form>";
			echo "</td>";
			echo "</tr>";
			echo "</table></blockquote>";
			}
		else {
			echo "<font color=red>Song is already in this class. No change.</font><br />";
			echo "<form name=\"no_change\" method=\"post\" action=\"workset.php#".$flag."\" enctype=\"multipart/form-data\">";
		//	echo "<input type=\"hidden\" name=\"action\" value=\"display_current\">";
			echo "<input type=\"submit\" class=\"button\" value=\"OK, RETURN TO SONG\">";
			echo "</form>";
			$change_class_msg[$song_id] = "<font color=red>Song is already in this class. No change.</font><br />";
			$hilite_song_class[$song_id] = TRUE;
			}
		break;
		}
	if(is_integer(strpos($key,"remove_forgood_"))) {
		$song_id = str_replace("remove_forgood_",'',$key);
		$query = "DELETE FROM ".BASE.".workset WHERE login = \"".$login."\" AND status = \"current\" AND song_id = \"".$song_id."\"";
		$result = $bdd->query($query);	
		break;
		}
	if(is_integer(strpos($key,"remove_"))) {
		$song_id = str_replace("remove_",'',$key);
		$flag = "song".$song_id;
		if($test) echo "Removing ".$song_id."<br />";
		$query = "SELECT * FROM ".BASE.".workset WHERE login = \"".$login."\" AND status = \"current\" AND song_id = \"".$song_id."\"";
		$result = $bdd->query($query);
		$ligne = $result->fetch();
		$result->closeCursor();
		$translation_english = $ligne['translation'];
		$old_translation_english = transcription($song_id,"translation");
		$devanagari = $ligne['devanagari'];
		$old_devanagari = transcription($song_id,"devanagari");
		$roman = $ligne['roman'];
		$old_roman = transcription($song_id,"roman");
		if(($translation_english <> $old_translation_english AND $translation_english <> '') OR ($devanagari <> $old_devanagari AND $devanagari <> '') OR ($roman <> $old_roman AND $roman <> '')) {
			echo "<font color=red>You wish to remove song</font> #".$song_id." <font color=red>from your current work set.<br /></font><small>";
			if($translation_english <> $old_translation_english) {
				echo "<br /><font color=red>Beware that your new translation will be deleted:</font><br />";
				echo $translation_english."<br />";
				if($old_translation_english <> '') {
					echo "<font color=red>… and returned to the old one:</font><br />";
					echo $old_translation_english."<br />";
					}
				}
			if($devanagari <> $old_devanagari AND $devanagari <> '') {
				echo "<br /><font color=red>Beware that your new transcription will be deleted:</font><br />";
				echo $devanagari."<br />";
				echo "<font color=red>… and returned to the old one:</font><br />";
				echo $old_devanagari."<br />";
				}
			if($roman <> $old_roman AND $roman <> '') {
				echo "<br /><font color=red>Beware that your new transcription will be deleted:</font><br />";
				echo $roman."<br />";
				echo "<font color=red>… and returned to the old one:</font><br />";
				echo $old_roman."<br />";
				}
			echo "</small>";
			echo "<table>";
			echo "<tr>";
			echo "<td class=\"tight\"><font color=red>Do you confirm the removal?</font></td>";
			echo "<td class=\"tight\">";
			echo "<form name=\"confirm_removal\" method=\"post\" action=\"workset.php#display\" enctype=\"multipart/form-data\">";
		//	echo "<input type=\"hidden\" name=\"action\" value=\"display_current\">";
			echo "<input type=\"hidden\" name=\"remove_forgood_".$song_id."\" value=\"yes\">";
			echo "<input type=\"submit\" class=\"button\" value=\"YES\">";
			echo "</form>";
			echo "</td>";
			echo "<td class=\"tight\">";
			echo "<form name=\"select_all\" method=\"post\" action=\"workset.php#".$flag."\" enctype=\"multipart/form-data\">";
	//		echo "<input type=\"hidden\" name=\"action\" value=\"display_current\">";
			echo "<input type=\"submit\" class=\"button\" value=\"NO\">";
			echo "</form>";
			echo "</td>";
			echo "</tr>";
			echo "</table>";
			}
		else {
			$query = "DELETE FROM ".BASE.".workset WHERE login = \"".$login."\" AND status = \"current\" AND song_id = \"".$song_id."\"";
			$result = $bdd->query($query);
			}
		}
	}

if($this_song_id > 0) {
	$query = "SELECT * FROM ".BASE.".workset WHERE song_id = \"".$this_song_id."\"";
	$result = $bdd->query($query);
	$n = $result->rowCount();
	if($n > 0) {
		$ligne = $result->fetch();
		$set_id = $ligne['set_id'];
		$status = $ligne['status'];
		$user = $ligne['login'];
	//	echo "this_song_id = ".$this_song_id." set_id = ".$set_id." status = ".$status." user = ".$user."<br />";
	//	echo "User = “".$user."”<br />";
		}
	if($user == $login OR is_admin($login))
		display_workset($short_display,$this_song_id,FALSE,$user,$set_id,$status);
	die();
	}

$set_id = current_workset_id($login);

if($test AND isset($_POST['action'])) echo "766@@post[action] = ".$_POST['action']."<br />";

$query = "SELECT count(*) from ".BASE.".songs WHERE translation_english <> \"\"";
$result = $bdd->query($query);
$number_of_translations_english = $result->fetchColumn();
$result->closeCursor();
$query = "SELECT count(*) from ".BASE.".workset WHERE status <> \"valid\"";
$result = $bdd->query($query);
$number_of_songs_in_workset = $result->fetchColumn();
$result->closeCursor();
echo "<ul>";
echo "<li><font color=green>".$number_of_translations_english."</font> translated songs  (English)</li>";
echo "<li><font color=brown>".$number_of_songs_in_workset."</font> songs awaiting translation/edition in work sets</li>";
echo "</ul>";

echo "<h3>My current work set";
if(!empty_current_set($login)) echo " (#".$set_id.")";
echo "</h3>";
if(empty_current_set($login)) {
	echo "<blockquote><font color=red><i>My current work set is empty.<br />I can select songs to include them in the set.</i></font>";
	echo "<form name=\"trash\" method=\"post\" action=\"workset.php\" enctype=\"multipart/form-data\">";
	echo "<input type=\"submit\" class=\"button\" value=\"Try reloading to be sure\">";
	echo "</form></blockquote>";
	}

if(!empty_current_set($login)) {
	echo "<table>";
	echo "<tr>";
	$workset_parameters = workset_parameters($set_id);
	$date = $workset_parameters['date'];
	$editor = $workset_parameters['editor'];
	$size = $workset_parameters['size'];
	$translations = $workset_parameters['translations'];
	echo "<td class=\"tight\" style=\"white-space:nowrap;\">";
	echo "Work set #".$set_id."<br />";
	echo $translations." translations<br />";
	echo "<small>Modified";
	// if($editor <> '' AND $editor <> $login) echo " by ‘".$editor."’";
	echo " on ".$date."</small><br />";
	echo "</td>";
	echo "<td class=\"tight\" style=\"text-align:center; background-color:Bisque;\">";
	echo "<form name=\"display_current\" method=\"post\" action=\"workset.php#display\" enctype=\"multipart/form-data\">";
	echo "<input type=\"hidden\" name=\"set_id\" value=\"".$set_id."\">";
	echo "<input type=\"hidden\" name=\"user\" value=\"".$login."\">";
	echo "<input type=\"hidden\" name=\"status\" value=\"current\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"display_current\">";
	echo "<input type=\"submit\" class=\"button\" value=\"← EDIT THIS WORK SET (".$size." songs)\">";
	echo "</form>";
	echo "</td>";
	echo "<td class=\"tight\" style=\"text-align:center; background-color:Bisque;\">";
	echo "<form name=\"trash\" method=\"post\" action=\"workset.php\" enctype=\"multipart/form-data\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"store_current\">";
	echo "<input type=\"submit\" class=\"button\" value=\"STORE THIS WORK SET\">";
	echo "<input type=\"hidden\" name=\"set_id\" value=\"".$set_id."\">";
	echo "<input type=\"hidden\" name=\"user\" value=\"".$login."\">";
	echo "<input type=\"hidden\" name=\"status\" value=\"current\">";
	if($translations > 0 AND $translations < $size) {
		echo "<td class=\"tight\" style=\"text-align:center; background-color:Bisque;\">";
		echo "<input type=\"submit\" class=\"button\" name=\"move_translations\" value=\"DETACH TRANSLATED SONGS\"><br />(create a new workset)";
		echo "</td>";
		}	
	echo "</form>";
	echo "</td>";
	
	echo "<td class=\"tight\" style=\"text-align:center; background-color:red;\">";
	echo "<form name=\"submit_current\" method=\"post\" action=\"workset.php#display\" enctype=\"multipart/form-data\">";
	echo "<input type=\"hidden\" name=\"set_id\" value=\"".$set_id."\">";
	echo "<input type=\"hidden\" name=\"user\" value=\"".$login."\">";
	echo "<input type=\"hidden\" name=\"status\" value=\"current\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"submit_this\">";
	echo "<input type=\"submit\" class=\"button\" value=\"SUBMIT THIS WORK SET\">";
	echo "</form>";
	echo "</td>";
	echo "<td class=\"tight\" style=\"text-align:center; background-color:Bisque;\">";
	echo "<form name=\"trash\" method=\"post\" action=\"workset.php\" enctype=\"multipart/form-data\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"try_delete_work_set\">";
	echo "<input type=\"submit\" class=\"button\" value=\"DELETE THIS WORK SET\">";
	echo "<input type=\"hidden\" name=\"set_id\" value=\"".$set_id."\">";
	echo "<input type=\"hidden\" name=\"user\" value=\"".$login."\">";
	echo "<input type=\"hidden\" name=\"status\" value=\"current\">";
	echo "</form>";
	echo "</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td></td>";
	echo "<td class=\"tight\" style=\"text-align:center; background-color:Bisque;\">";
	echo "<form name=\"add_to_group\" method=\"post\" action=\"workset.php\" enctype=\"multipart/form-data\">";
	echo "<input type=\"hidden\" name=\"add_group_".$set_id."\" value=\"yes\">";
echo "<input type=\"hidden\" name=\"user\" value=\"".$login."\">";
	echo "<input type=\"submit\" class=\"button\" value=\"ADD SONGS TO GROUP:\"><br />";
	echo "<input type=\"text\" name=\"new_group_label\" size=\"10\" value=\"\">";
	echo "</form>";
	echo "</td>";
	echo "<td class=\"tight\" style=\"text-align:center; background-color:Bisque;\">";
	echo "<form name=\"remove_from_group\" method=\"post\" action=\"workset.php\" enctype=\"multipart/form-data\">";
	echo "<input type=\"hidden\" name=\"remove_group_".$set_id."\" value=\"yes\">";
echo "<input type=\"hidden\" name=\"user\" value=\"".$login."\">";
	echo "<input type=\"submit\" class=\"button\" value=\"REMOVE SONGS FROM GROUP:\"><br />";
	echo "<input type=\"text\" name=\"old_group_label\" size=\"10\" value=\"\">";
	echo "</form>";
	echo "</td>";
	echo "</tr>";
	
	echo "</table>";
	echo "<ul>";
//	echo "<li><a href=\"workset.php?fix_typo=yes#display\">Fix typography</a> in my current work set</li>";
	}
else echo "<ul>";
echo "<li><a href=\"workset.php?import=yes&type=translation\">Import an English translation file</a> I have completed to feed (or create) my current work set</li>";
echo "<li><a href=\"workset.php?import=yes&type=devanagari\">Import a Devanagari transcription file</a> I have completed to feed (or create) my current work set</li>";
// echo "<li><a href=\"workset.php?import=yes&type=roman\">Import a Roman transcription file</a> I have completed to feed (or create) my current work set</li>";
if(!empty_current_set($login)) {
	echo "<li><a href=\"workset.php?export=yes\">Export my current work set</a> to work files (translations and transcriptions)</li>";
	}
echo "</ul>";

if($test AND isset($_POST['action'])) echo "829@@post[action] = ".$_POST['action']."<br />";

/* if(is_admin($login) AND !empty_current_set($login) AND isset($_GET['fix_typo']) AND $_GET['fix_typo'] == "yes") {
	$apply_typo_rules = TRUE;
	$typo_set_id = $set_id;
	} */

if($apply_typo_rules) {
	$translation_correction_english = ReadRewriteRules("english");
	echo "<blockquote><font color=blue>".count($translation_correction_english)." rewrite rules found in the database</font><br /></blockquote>";
//	echo "@@@<br />";
//	$typo_set_id = $_POST['set_id'];
	fix_song_translations(0,0,$typo_set_id,$translation_correction_english);
	$user = $_POST['user'];
	$status = $_POST['status'];
	display_workset($short_display,$this_song_id,FALSE,$user,$typo_set_id,$status);
	}
else {
//	echo "@@@<br />";
	if((!isset($_SESSION['fixed_typo_current_workset']) OR $_SESSION['fixed_typo_current_workset'] == FALSE) AND !empty_current_set($login)) {
		$_SESSION['fixed_typo_current_workset'] = TRUE;
		$translation_correction_english = ReadRewriteRules("english");
		fix_song_translations(0,0,$set_id,$translation_correction_english);
		}
	}

if($test AND isset($_POST['action'])) echo "1005 @post[action] = ".$_POST['action']."<br />";

// if(isset($_POST['action'])) echo "status = ".$_POST['status']." user = ".$_POST['user']."<br />";

if((isset($_POST['action']) AND ($_POST['action'] == "display_current" OR $edit_mode) AND $_POST['status'] == "current" AND $_POST['user'] == $login) OR ($apply_typo_rules AND $typo_set_id == $set_id)) {
//	echo "@@@";
	display_workset($short_display,$this_song_id,FALSE,$login,$set_id,"current");
	unset($_POST['action']);
	die();
	}
else {
	if(isset($_POST['action']) AND $_POST['action'] == "try_delete_work_set") {
		echo "<table><tr>";
		echo "<td class=\"tight\" style=\"background-color:Bisque;\">";
		echo "<form name=\"trash\" method=\"post\" action=\"workset.php\" enctype=\"multipart/form-data\">";
		echo "<input type=\"hidden\" name=\"set_id\" value=\"".$set_id."\">";
		echo "<input type=\"hidden\" name=\"action\" value=\"delete_current_work_set\">";
		echo "<input type=\"submit\" class=\"button\" value=\"Yes, delete this work set!\">";
		echo "</form>";
		echo "</td><td style=\"background-color:Red;\">";
		echo "<form name=\"trash\" method=\"post\" action=\"workset.php\" enctype=\"multipart/form-data\">";
	//	echo "<input type=\"hidden\" name=\"action\" value=\"display_current\">";
		echo "<input type=\"submit\" class=\"button\" value=\"No, do not delete!\">";
		echo "</form>";
		echo "</td></tr></table>";
		die();
		}
	}
echo "<h3>My stored work sets</h3>";

// if(isset($_POST['action'])) echo "873@@post[action] = ".$_POST['action']."<br />";

$query = "SELECT DISTINCT(set_id) FROM ".BASE.".workset WHERE login = \"".$login."\" AND status = \"stored\" ORDER BY set_id";
$result = $bdd->query($query);
$n = $result->rowCount();
if($n > 0) {
	echo "<table>";
	while($ligne = $result->fetch()) {
		echo "<tr>";
		$set_id = $ligne['set_id'];
		$workset_parameters = workset_parameters($set_id);
		$date = $workset_parameters['date'];
		$editor = $workset_parameters['editor'];
		$size = $workset_parameters['size'];
		$translations = $workset_parameters['translations'];
		echo "<td class=\"tight\" style=\"white-space:nowrap;\">";
		echo "Work set #".$set_id."<br />";
		echo $translations." translations<br />";
		echo "<small>Modified";
	//	if($editor <> '' AND $editor <> $login) echo " by ‘".$editor."’";
		echo " on ".$date."</small><br />";
		echo "</td>";
		echo "<td class=\"tight\" style=\"text-align:center; background-color:Bisque;\">";
		echo "<form name=\"display\" method=\"post\" action=\"workset.php#display\" enctype=\"multipart/form-data\">";
		echo "<input type=\"hidden\" name=\"set_id\" value=\"".$set_id."\">";
		echo "<input type=\"hidden\" name=\"user\" value=\"".$login."\">";
		echo "<input type=\"hidden\" name=\"status\" value=\"stored\">";
		echo "<input type=\"hidden\" name=\"action\" value=\"display_user\">";
		echo "<input type=\"submit\" class=\"button\" value=\"← DISPLAY THIS WORK SET (".$size." songs)\">";
		echo "</form>";
		echo "</td>";
		echo "<td class=\"tight\" style=\"text-align:center; background-color:Bisque; white-space:nowrap;\">";
		echo "<form name=\"change_owner\" method=\"post\" action=\"workset.php#display\" enctype=\"multipart/form-data\">";
		echo "<input type=\"hidden\" name=\"set_id\" value=\"".$set_id."\">";
		echo "<input type=\"hidden\" name=\"user\" value=\"".$login."\">";
		echo "<input type=\"hidden\" name=\"status\" value=\"stored\">";
		echo "<input type=\"hidden\" name=\"action\" value=\"change_owner\">";
		echo "<input type=\"submit\" class=\"button\" value=\"CHANGE OWNER TO:\">";
		if($login == "Asha") echo "<input type=\"text\" name=\"new_owner\" size=\"6\" value=\"\">";
		else echo "<input type=\"text\" name=\"new_owner\" size=\"6\" value=\"Asha\">";
		echo "</form>";
		echo "</td>";
		echo "<td class=\"tight\" style=\"text-align:center; background-color:red;\">";
		echo "<form name=\"submit_this\" method=\"post\" action=\"workset.php#display\" enctype=\"multipart/form-data\">";
		echo "<input type=\"hidden\" name=\"set_id\" value=\"".$set_id."\">";
		echo "<input type=\"hidden\" name=\"user\" value=\"".$login."\">";
		echo "<input type=\"hidden\" name=\"status\" value=\"stored\">";
		echo "<input type=\"hidden\" name=\"action\" value=\"submit_this\">";
		echo "<input type=\"submit\" class=\"button\" value=\"SUBMIT THIS WORK SET\">";
		echo "</form>";
		echo "</td>";
		echo "<td class=\"tight\" style=\"text-align:center; background-color:Bisque;\">";
		echo "<form name=\"unsubmit\" method=\"post\" action=\"workset.php\" enctype=\"multipart/form-data\">";
		echo "<input type=\"hidden\" name=\"unstore_".$set_id."\" value=\"yes\">";
		echo "<input type=\"hidden\" name=\"user\" value=\"".$login."\">";
		echo "<input type=\"hidden\" name=\"status\" value=\"stored\">";
		echo "<input type=\"submit\" class=\"button\" value=\"UNSTORE THIS WORK SET";
		echo "\"";
		echo ">";
		echo "</form>";
		echo "</td>";
		echo "</tr>";
		}
	$result->closeCursor();
	echo "</table>";
//	if(isset($_POST['action']) AND $_POST['action'] == "display_user" AND $_POST['user'] == $login) {
	if(isset($_POST['action']) AND ($_POST['action'] == "display_user" OR $edit_mode) AND $_POST['user'] == $login AND $_POST['status'] == "stored") {
		$set_id = $_POST['set_id'];
		$user = $_POST['user'];
		$status = "stored";
	//	echo $user." ".$set_id."@<br />";
		display_workset($short_display,$this_song_id,FALSE,$user,$set_id,$status);
		unset($_POST['action']);
		}
	}
else echo "<blockquote><i>➡ No stored work set…</i></blockquote>";

echo "<h3>My submitted work sets</h3>";
// if(isset($_POST['action'])) echo "936@@post[action] = ".$_POST['action']."<br />";
echo "<blockquote><i>➡ Submitted work sets can be unsubmitted.</i></blockquote>";
$query = "SELECT DISTINCT(set_id) FROM ".BASE.".workset WHERE login = \"".$login."\" AND status = \"submit\" ORDER BY set_id";
$result = $bdd->query($query);
if($result) {
	$status = "submit";
	echo "<table>";
	while($ligne = $result->fetch()) {
		echo "<tr>";
		$set_id = $ligne['set_id'];
		$workset_parameters = workset_parameters($set_id);
		$date = $workset_parameters['date'];
		$editor = $workset_parameters['editor'];
		$size = $workset_parameters['size'];
		$translations = $workset_parameters['translations'];
		echo "<td class=\"tight\" style=\"white-space:nowrap;\">";
		echo "Work set #".$set_id."<br />";
		echo $translations." translations<br />";
		echo "<small>Modified";
		if($editor <> '' AND $editor <> $login) echo " by ‘".$editor."’";
		echo " on ".$date."</small><br />";
		echo "</td>";
		echo "<td class=\"tight\" style=\"text-align:center; background-color:Bisque;\">";
		echo "<form name=\"display\" method=\"post\" action=\"workset.php#display\" enctype=\"multipart/form-data\">";
		echo "<input type=\"hidden\" name=\"set_id\" value=\"".$set_id."\">";
		echo "<input type=\"hidden\" name=\"status\" value=\"".$status."\">";
		echo "<input type=\"hidden\" name=\"user\" value=\"".$login."\">";
		echo "<input type=\"hidden\" name=\"action\" value=\"display_user\">";
		echo "<input type=\"submit\" class=\"button\" value=\"← DISPLAY THIS SUBMITTED WORK SET (".$size." songs)\">";
		echo "</form>";
		echo "</td>";
		echo "<td class=\"tight\" style=\"text-align:center; background-color:Bisque;\">";
		echo "<form name=\"add_to_group\" method=\"post\" action=\"workset.php\" enctype=\"multipart/form-data\">";
		echo "<input type=\"hidden\" name=\"add_group_".$set_id."\" value=\"yes\">";
		echo "<input type=\"submit\" class=\"button\" value=\"ADD SONGS TO GROUP:\"><br />";
		echo "<input type=\"text\" name=\"new_group_label\" size=\"10\" value=\"\">";
		echo "</form>";
		echo "</td>";
		echo "<td class=\"tight\" style=\"text-align:center; background-color:Bisque;\">";
		echo "<form name=\"remove_from_group\" method=\"post\" action=\"workset.php\" enctype=\"multipart/form-data\">";
		echo "<input type=\"hidden\" name=\"remove_group_".$set_id."\" value=\"yes\">";
		echo "<input type=\"submit\" class=\"button\" value=\"REMOVE SONGS FROM GROUP:\"><br />";
		echo "<input type=\"text\" name=\"old_group_label\" size=\"10\" value=\"\">";
		echo "</form>";
		echo "</td>";
		echo "<td class=\"tight\" style=\"text-align:center; background-color:Bisque;\">";
		echo "<form name=\"unsubmit\" method=\"post\" action=\"workset.php\" enctype=\"multipart/form-data\">";
		echo "<input type=\"hidden\" name=\"unsubmit_".$set_id."\" value=\"yes\">";
		echo "<input type=\"submit\" class=\"button\" value=\"UNSUBMIT THIS WORK SET\">";
		echo "</form>";
		echo "</td>";
		echo "</tr>";
		}
	$result->closeCursor();
	echo "</table>";
	
//	if(isset($_POST['action'])) echo "988@@post[action] = ".$_POST['action']."<br />";

	if(isset($_POST['action']) AND ($_POST['action'] == "display_user" OR $edit_mode) AND $_POST['user'] == $login AND $_POST['status'] == "submit") {
		$set_id = $_POST['set_id'];
		$user = $_POST['user'];
		$status = $_POST['status'];
	//	echo $user." ".$set_id." ".$status."@<br />";
		display_workset($short_display,$this_song_id,FALSE,$user,$set_id,$status);
		unset($_POST['action']);
		}
	}

if(is_admin($login)) {
	echo "<hr>";
	echo "<h2>Admin space</h2>";
	echo "<hr>";
	echo "<h3>Current work sets by other users (admin only)</h3>";
	$query = "SELECT DISTINCT(set_id) FROM ".BASE.".workset WHERE status = \"current\" AND login <> \"".$login."\" ORDER BY set_id";
	$result = $bdd->query($query);
	if($result) {
		echo "<table>";
		while($ligne = $result->fetch()) {
			echo "<tr>";
			$set_id = $ligne['set_id'];
			$workset_parameters = workset_parameters($set_id);
			$date = $workset_parameters['date'];
			$editor = $workset_parameters['editor'];
			$user = $workset_parameters['login'];
			$size = $workset_parameters['size'];
			$translations = $workset_parameters['translations'];
			echo "<td class=\"tight\" style=\"white-space:nowrap;\">";
			echo "Work set #".$set_id." by ".$user."<br />";
			echo $translations." translations<br />";
			echo "<small>Modified ";
			if($editor <> '' AND $editor <> $user) echo "by ‘".$editor."’";
			echo " on ".$date."</small><br />";
			echo "</td>";
			echo "<td class=\"tight\" style=\"text-align:center; background-color:Bisque;\">";
			echo "<form name=\"unsubmit\" method=\"post\" action=\"workset.php#display\" enctype=\"multipart/form-data\">";
			echo "<input type=\"hidden\" name=\"set_id\" value=\"".$set_id."\">";
			echo "<input type=\"hidden\" name=\"user\" value=\"".$user."\">";
			echo "<input type=\"hidden\" name=\"status\" value=\"current\">";
			echo "<input type=\"hidden\" name=\"action\" value=\"display_admin\">";
			echo "<input type=\"submit\" class=\"button\" value=\"← DISPLAY THIS WORK SET (".$size." songs)\">";
			echo "</form>";
			echo "</td>";
			echo "</tr>";
			}
		$result->closeCursor();
		echo "</table>";
		}
	if(isset($_POST['action']) AND $_POST['action'] == "display_admin" AND $_POST['status'] == "current") {
		if($_POST['user'] <> $login) {
			$set_id = $_POST['set_id'];
			$user = $_POST['user'];
			$status = $_POST['status'];
			display_workset($short_display,$this_song_id,TRUE,$user,$set_id,$status);
			unset($_POST['action']);
			die();
			}
		}
	echo "<h3>Stored sets by other users (admin only)</h3>";
	$query = "SELECT DISTINCT(set_id) FROM ".BASE.".workset WHERE status = \"stored\" AND login <> \"".$login."\" ORDER BY set_id";
	$result = $bdd->query($query);
	if($result) {
		echo "<table>";
		while($ligne = $result->fetch()) {
			echo "<tr>";
			$set_id = $ligne['set_id'];
			$workset_parameters = workset_parameters($set_id);
			$date = $workset_parameters['date'];
			$editor = $workset_parameters['editor'];
			$user = $workset_parameters['login'];
			$size = $workset_parameters['size'];
			$translations = $workset_parameters['translations'];
			echo "<td class=\"tight\" style=\"white-space:nowrap;\">";
			echo "Work set #".$set_id." by ".$user."<br />";
			echo $translations." translations<br />";
			echo "<small>Modified ";
			if($editor <> '' AND $editor <> $user) echo "by ‘".$editor."’";
			echo " on ".$date."</small><br />";
			echo "</td>";
			echo "<td class=\"tight\" style=\"text-align:center; background-color:Bisque;\">";
			echo "<form name=\"unsubmit\" method=\"post\" action=\"workset.php#display\" enctype=\"multipart/form-data\">";
			echo "<input type=\"hidden\" name=\"set_id\" value=\"".$set_id."\">";
			echo "<input type=\"hidden\" name=\"user\" value=\"".$user."\">";
			echo "<input type=\"hidden\" name=\"status\" value=\"stored\">";
			echo "<input type=\"hidden\" name=\"action\" value=\"display_admin\">";
			echo "<input type=\"submit\" class=\"button\" value=\"← DISPLAY THIS WORK SET (".$size." songs)\">";
			echo "</form>";
			echo "</td>";
			
			echo "</tr>";
			}
		$result->closeCursor();
		echo "</table>";
		}
	if(isset($_POST['action']) AND $_POST['action'] == "display_admin" AND $_POST['status'] == "stored" AND $_POST['user'] <> $login) {
		$set_id = $_POST['set_id'];
		$user = $_POST['user'];
		$status = $_POST['status'];
		display_workset($short_display,$this_song_id,FALSE,$user,$set_id,$status);
		unset($_POST['action']);
		}
	echo "<h3>Submitted work sets (admin only)</h3>";
	echo "<blockquote><i>➡ Validating a work set transfers all its translations to the database…</i></blockquote>";
	$query = "SELECT DISTINCT(set_id) FROM ".BASE.".workset WHERE status = \"submit\" ORDER BY set_id";
	$result = $bdd->query($query);
	if($result) {
		$status = "submit";
		echo "<table>";
		while($ligne = $result->fetch()) {
			echo "<tr>";
			$set_id = $ligne['set_id'];
			$workset_parameters = workset_parameters($set_id);
			$date = $workset_parameters['date'];
			$editor = $workset_parameters['editor'];
			$user = $workset_parameters['login'];
			$size = $workset_parameters['size'];
			$translations = $workset_parameters['translations'];
			echo "<td class=\"tight\" style=\"white-space:nowrap;\">";
			echo "Work set #".$set_id." by ".$user."<br />";
			echo $translations." translations<br />";
			echo "<small>Modified ";
			if($editor <> '' AND $editor <> $user) echo "by ‘".$editor."’";
			echo " on ".$date."</small><br />";
			echo "</td>";
			echo "<td class=\"tight\" style=\"text-align:center; background-color:Bisque;\">";
			echo "<form name=\"unsubmit\" method=\"post\" action=\"workset.php#display\" enctype=\"multipart/form-data\">";
			echo "<input type=\"hidden\" name=\"set_id\" value=\"".$set_id."\">";
			echo "<input type=\"hidden\" name=\"user\" value=\"".$user."\">";
			echo "<input type=\"hidden\" name=\"status\" value=\"submit\">";
			echo "<input type=\"hidden\" name=\"action\" value=\"display_admin\">";
			echo "<input type=\"submit\" class=\"button\" value=\"← DISPLAY THIS WORK SET (".$size." songs)\">";
			echo "</form>";
			echo "</td>";
			echo "<td class=\"tight\" style=\"text-align:center; background-color:Bisque; white-space:nowrap;\">";
			echo "<form name=\"change_owner\" method=\"post\" action=\"workset.php#display\" enctype=\"multipart/form-data\">";
			echo "<input type=\"hidden\" name=\"set_id\" value=\"".$set_id."\">";
			echo "<input type=\"hidden\" name=\"user\" value=\"".$user."\">";
			echo "<input type=\"hidden\" name=\"status\" value=\"submit\">";
			echo "<input type=\"hidden\" name=\"action\" value=\"change_owner\">";
			echo "<input type=\"submit\" class=\"button\" value=\"CHANGE OWNER TO:\">";
			if($login == "Asha" OR $user == "Asha") echo "<input type=\"text\" name=\"new_owner\" size=\"6\" value=\"\">";
			else echo "<input type=\"text\" name=\"new_owner\" size=\"6\" value=\"Asha\">";
			echo "</form>";
			echo "</td>";
			echo "<td class=\"tight\" style=\"text-align:center; background-color:Bisque;\">";
			echo "<form name=\"unsubmit\" method=\"post\" action=\"workset.php\" enctype=\"multipart/form-data\">";
			echo "<input type=\"hidden\" name=\"action\" value=\"export\">";
			echo "<input type=\"hidden\" name=\"set_id\" value=\"".$set_id."\">";
			echo "<input type=\"hidden\" name=\"user\" value=\"".$user."\">";
			echo "<input type=\"hidden\" name=\"status\" value=\"submit\">";
			echo "<input type=\"submit\" class=\"button\" value=\"EXPORT TO CORRECTIONS\">";
			echo "</form>";
			echo "</td>";
			echo "<td class=\"tight\" style=\"text-align:center; background-color:Bisque;\">";
			echo "<form name=\"unsubmit\" method=\"post\" action=\"workset.php\" enctype=\"multipart/form-data\">";
			echo "<input type=\"hidden\" name=\"action\" value=\"import\">";
			echo "<input type=\"hidden\" name=\"set_id\" value=\"".$set_id."\">";
			echo "<input type=\"hidden\" name=\"user\" value=\"".$user."\">";
			echo "<input type=\"hidden\" name=\"status\" value=\"submit\">";
			echo "<input type=\"hidden\" name=\"type\" value=\"translation\">";
			echo "<input type=\"submit\" class=\"button\" value=\"IMPORT CORRECTIONS\">";
			echo "</form>";
			echo "</td>";
			echo "<td class=\"tight\" style=\"text-align:center; background-color:Bisque;\">";
			echo "<form name=\"unsubmit\" method=\"post\" action=\"workset.php#display\" enctype=\"multipart/form-data\">";
			echo "<input type=\"hidden\" name=\"action\" value=\"fix_typo\">";
			echo "<input type=\"hidden\" name=\"set_id\" value=\"".$set_id."\">";
			echo "<input type=\"hidden\" name=\"user\" value=\"".$user."\">";
			echo "<input type=\"hidden\" name=\"status\" value=\"submit\">";
		//	echo "<input type=\"hidden\" name=\"action\" value=\"display_admin\">";
			echo "<input type=\"submit\" class=\"button\" value=\"FIX TYPOGRAPHY\">";
			echo "</form>";
			echo "</td>";
			echo "<td class=\"tight\" style=\"text-align:center; background-color:Red;\">";
			echo "<form name=\"unsubmit\" method=\"post\" action=\"workset.php\" enctype=\"multipart/form-data\">";
			echo "<input type=\"hidden\" name=\"action\" value=\"validate\">";
			echo "<input type=\"hidden\" name=\"set_id\" value=\"".$set_id."\">";
			echo "<input type=\"hidden\" name=\"user\" value=\"".$user."\">";
			echo "<input type=\"hidden\" name=\"status\" value=\"submit\">";
			echo "<input type=\"hidden\" name=\"status\" value=\"".$status."\">";
			echo "<input type=\"submit\" class=\"button\" value=\"VALIDATE\">";
			echo "</form>";
			echo "</td>";
			echo "</tr>";
			}
		$result->closeCursor();
		echo "</table>";
		}
//	if(isset($_POST['action'])) echo "@post[action] = ".$_POST['action']."<br />";
	
	if(isset($_POST['action']) AND ($_POST['action'] == "display_admin" OR $edit_mode) AND $_POST['status'] == "submit") {
		$set_id = $_POST['set_id'];
		$user = $_POST['user'];
		$status = $_POST['status'];
		display_workset($short_display,$this_song_id,FALSE,$user,$set_id,$status);
		unset($_POST['action']);
		}
	echo "<h3>Validated work sets (admin only)</h3>";
	echo "<blockquote><i>➡ Unvalidating a work set makes it possible to enter more modifications.</i></blockquote>";
	$query = "SELECT DISTINCT(set_id) FROM ".BASE.".workset WHERE status = \"valid\" ORDER BY set_id DESC";
	}
else {
	echo "<h3>My validated work sets</h3>";
	$query = "SELECT DISTINCT(set_id) FROM ".BASE.".workset WHERE status = \"valid\" AND login = \"".$login."\" ORDER BY set_id DESC";
	}
if($limit_workset == "less") $query .= " LIMIT 10";
$result = $bdd->query($query);
if($result) {
	if($limit_workset == "all")
		echo "<blockquote>[<a href=\"workset.php?limit_workset=less\">Display only 10 work sets</a>…]</blockquote>";
	echo "<table>";
	while($ligne = $result->fetch()) {
		echo "<tr>";
	//	$user = $ligne['login'];
		$set_id = $ligne['set_id'];
	//	$date = $ligne['date'];
		$workset_parameters = workset_parameters($set_id);
		$date = $workset_parameters['date'];
		$editor = $workset_parameters['editor'];
		$user = $workset_parameters['login'];
		$size = $workset_parameters['size'];
		$translations = $workset_parameters['translations'];
		echo "<td class=\"tight\" style=\"white-space:nowrap;\">";
		echo "Work set #".$set_id." by ".$user."<br />";
		echo $translations." translations<br />";
		echo "<small>Modified ";
		if($editor <> '' AND $editor <> $user) echo "by ‘".$editor."’";
		echo " on ".$date."</small><br />";
		echo "</td>";
		echo "<td class=\"tight\" style=\"text-align:center; background-color:Bisque;\">";
		echo "<form name=\"unsubmit\" method=\"post\" action=\"workset.php#display\" enctype=\"multipart/form-data\">";
		echo "<input type=\"hidden\" name=\"set_id\" value=\"".$set_id."\">";
		echo "<input type=\"hidden\" name=\"user\" value=\"".$user."\">";
		echo "<input type=\"hidden\" name=\"status\" value=\"valid\">";
		echo "<input type=\"hidden\" name=\"action\" value=\"display_valid\">";
		echo "<input type=\"submit\" class=\"button\" value=\"← DISPLAY THIS VALIDATED WORK SET (".$size." songs)\">";
		echo "</form>";
		echo "</td>";
		if(is_admin($login)) {
			echo "<td class=\"tight\" style=\"text-align:center; background-color:Bisque;\">";
			echo "<form name=\"unsubmit\" method=\"post\" action=\"workset.php\" enctype=\"multipart/form-data\">";
			echo "<input type=\"hidden\" name=\"action\" value=\"fix_typo\">";
			echo "<input type=\"hidden\" name=\"set_id\" value=\"".$set_id."\">";
			echo "<input type=\"hidden\" name=\"user\" value=\"".$user."\">";
			echo "<input type=\"hidden\" name=\"status\" value=\"submit\">";
			echo "<input type=\"submit\" class=\"button\" value=\"FIX TYPOGRAPHY\">";
			echo "</form>";
			echo "</td>";
			echo "<td class=\"tight\" style=\"text-align:center; background-color:Bisque;\">";
			echo "<form name=\"unsubmit\" method=\"post\" action=\"workset.php\" enctype=\"multipart/form-data\">";
			echo "<input type=\"hidden\" name=\"set_id\" value=\"".$set_id."\">";
			echo "<input type=\"hidden\" name=\"user\" value=\"".$user."\">";
			echo "<input type=\"hidden\" name=\"status\" value=\"valid\">";
			echo "<input type=\"hidden\" name=\"action\" value=\"unvalidate\">";
			echo "<input type=\"submit\" class=\"button\" value=\"UNVALIDATE THIS WORK SET\">";
			echo "</form>";
			echo "</td>";
			}
		echo "</tr>";
		}
	$result->closeCursor();
	echo "</table>";
	if($limit_workset == "less") echo "<blockquote>[<a href=\"workset.php?limit_workset=all\">Display all work sets</a>…]</blockquote>";
	if(isset($_POST['action']) AND $_POST['action'] == "display_valid") {
		$set_id = $_POST['set_id'];
		$user = $_POST['user'];
		$status = "valid";
		display_workset($short_display,$this_song_id,TRUE,$user,$set_id,$status);
		unset($_POST['action']);
		}
	}
echo "<hr>";
// echo "<small> Total time = ".(time() - $start_time_all)." seconds</small>";
check_todays_backup();
echo "</body>";
echo "</html>";

// ------------------------------ FUNCTIONS -----------------------------

function interpret_temp_file($user,$set_id,$temp_filename,$try,$status,$type) {
	global $bdd, $login;
//	global $song_id;
	$filename = $temp_filename."_".$type.".txt";
	$olddir = getcwd();
	chdir("WORK_FILES");
	if(!file_exists($login)) {
		echo "<font color=red>ERROR 24: no folder for </font>‘".$login."’<br />";
		return;
		}
	chdir($login);
	if($try == "yes") {
		$forgood = FALSE;
		echo "<blockquote><font color=green>➡ Checking the file recently uploaded<br />";
		echo "➡ Records will replace matching ones, or will be added to the current work set.</font></blockquote>";
		}
	else $forgood = TRUE;
	if($status == "current") $editor = $user;
	else if($status == "stored") $editor = $login;
	else if($status == "submit") $editor = $login;
	else $editor = '';
	$liste_file = fopen($filename,"rb");
	$errors = $warnings = $imax = $number_different = 0;
	$not_said = TRUE;
	$song_status = array();
	if(!$forgood) {
		echo "<h3>Updating work set #".$set_id."</h3>";
		echo "<table width=\"100%\">";
		echo "<tr>";
		echo "<th class=\"tight\">ID</th>";
		if($type <> "devanagari" AND $type <> "roman") echo "<th class=\"tight\">Song</th>";
		echo "<th class=\"tight\">Importing ".$type."…</th>";
		echo "<th class=\"tight\">Previous in a work set</th>";
		echo "<th class=\"tight\">Previous in database</th>";
		echo "</tr>";
		}
	else echo "<blockquote>";
	switch($type) {
		case 'translation': $language = "english"; break;
		case 'devanagari': $language = "devanagari"; break;
		case 'roman': $language = "roman"; break;
		}
	if($language == "english") $rule = ReadRewriteRules($language);
	else $rule = array();
	$fix_uppercase = TRUE;
	$some_in_database = FALSE;
	$class = '';
	if($type == "roman") $fix_uppercase = FALSE;
	while(!feof($liste_file)) {
		$line = fgets($liste_file);
		$line = trim($line);
		if(is_integer($pos=strpos($line,"//")) AND $pos == 0) continue;
		if($line == '') continue;
		$id = intval($line);
		while(strcmp($id,$line) == 0 AND $id > 0) {
			$id = sprintf("%d",$id);
			$song_id = $id;
			$other_set_id = other_work_set($song_id,'','');
			$other_set_user = set_user($other_set_id);
			if($other_set_id > 0 AND $other_set_id <> $set_id)
				$already_in_other_set = TRUE;
			else $already_in_other_set = FALSE;
			$devanagari = transcription($song_id,"devanagari");
			$roman = transcription($song_id,"roman");
			$current_type = work_version($song_id,$user,$type);
			$old_type = transcription($song_id,$type);
			$imax++;
			$song = array();
			$song[0] = $song[1] = $song[2] = '';
			$stop = FALSE;
			for($j=0; $j < 3; $j = $j) {
				$line = fgets($liste_file);
		/*		do $line = str_replace("  "," ",$line,$count);
				while($count > 0); */
				$line = preg_replace('/\s+/',' ',$line);
				$line = trim($line);
				if($line == '') {
					$stop = TRUE;
					$id = 0;
					break;
					}
				$jd = intval($line);
				if(strcmp($jd,$line) == 0 AND $jd > 0) {
					$stop = TRUE;
					$id = $jd;
					break;
					}
				else {
					$line = fix_typo($line,0);
					$song[$j++] = $line;
					if($j > 2) {
						$id = 0; $line = '';
						break;
						}
					}
				}
			$input = '';
			if($song[0] <> '') {
				$input = trim($song[0]);
				if($song[1] <> '') {
					for($i=1; $i < 3; $i++) {
						if($song[$i] <> '') 
						$input = $input."<br />".trim($song[$i]);
						}
					}
				}
			$input = reshape_entry($input);
			$input = apply_rules(TRUE,$fix_uppercase,$input,$rule);
			$input = str_replace('_',' ',$input);
			$input = str_replace("||","’",$input);
			if($already_in_other_set) {
				$input = "<font color=red>WARNING: already in work set </font>#".$other_set_id." <font color=red>by</font> ‘".$other_set_user."’ <font color=red>=> will be ignored</font>";
	//			$errors++;
				$warnings++;
				}
			if(isset($song_status[$song_id]) AND $song_status[$song_id] == "done") {
				$errors++;
				echo "<tr><td colspan=\"5\"><font color=red>ERROR 26: duplicate serial number ".$song_id."</font><br /></td></tr>";
				}
			if(!$forgood) {
				echo "<tr>";
				echo "<td class=\"tight\" style=\"white-space:nowrap;\"><a target=\"_blank\" href=\"songs.php?song_id=".$song_id."\">".$song_id."</a>";
				$semantic_class_id = semantic_class_id($song_id);
				$song_class = semantic_class_given_id($semantic_class_id);
				if($semantic_class_id == '') {
					echo "<br /><font color=red>ERROR 27 (probably wrong ID)<br />This song belongs to unknown class</font>";
					$errors++;
					}
				else if($current_semantic_class_id == '') {
					echo "<br /><font color=red>Semantic class:</font><br /><font color=green><b>".$song_class."</b></font> (".$semantic_class_id.")";
					}
				else if($semantic_class_id <> $current_semantic_class_id) {
					echo "<br /><font color=red>ERROR 28 (probably wrong ID)<br />This song belongs to different class:<br />".$semantic_class_id."</font>";
					$errors++;
					}
				echo "</td>";
					
				if($type <> "devanagari" AND $type <> "roman")
					echo "<td class=\"tight\" style=\"white-space:nowrap;\">".$devanagari."<br />".$roman."</td>";
				echo "<td class=\"tight\" style=\"white-space:nowrap;\">";
				if($type == "roman") echo $devanagari."<br />";
				echo "<font color=blue>".$input."</font>";
				if($type == "devanagari") echo "<br />".$roman;
				echo "</td>";
				if(other_work_set($song_id,$user,'') == 0) {
					if($input == $old_type AND $old_type <> '')
						echo "<td class=\"tight\" style=\"white-space:nowrap;\"><font color=red>Already same in database,<br />no need to import</font></td>";
					else 
						echo "<td class=\"tight\" style=\"white-space:nowrap;\"><font color=red>Not yet in work set</font> #".$set_id."</td>";
					}
				else if($current_type == '')
					echo "<td class=\"tight\" style=\"white-space:nowrap;\"><i>No ".$type."</i></td>";
				else if($input == $current_type)
					echo "<td class=\"tight\" style=\"white-space:nowrap;\"><i>← Same</i></td>";
				else {
					echo "<td class=\"tight\" style=\"white-space:nowrap;\">";
					if($other_set_id > 0 AND $other_set_id <> $set_id) echo "<font color=red>#".$other_set_id."</font> ";
					echo $current_type."</td>";
					}
				if($old_type == '') {
					echo "<td class=\"tight\" style=\"white-space:nowrap;\"><i>No ".$type."</i></td>";
			//		if($input <> '') $number_different++;
					}
				else {
					$some_in_database = TRUE;
					if($input == $old_type)
						echo "<td class=\"tight\" style=\"white-space:nowrap;\"><i>← Same</i></td>";
					else {
						echo "<td class=\"tight\" style=\"white-space:nowrap;\">";
						echo $old_type;
						if($other_set_id == 0 OR $other_set_id == $set_id)
							$number_different++;
						echo "</td>";
						}
					}
				echo "</tr>";
				}
			else {
				if(other_work_set($song_id,$user,$status) == 0) {
					if($input == $old_type AND $old_type <> '') {
						$song_status[$song_id] = "done";
						continue;
						}
				//	$current_semantic_class_id = semantic_class_id_given_class($class);
					$semantic_class_id = semantic_class_id($song_id);
				//	$class = semantic_class_given_id($semantic_class_id);
				/*	if($semantic_class_id <> $current_semantic_class_id) {
						if(!$forgood) echo "<tr><td colspan=\"5\"><font color=green><b>".$class."</b></font> <small>(".$semantic_class_id.")</small></td></tr>";
						} */
					
					if(!is_integer(strpos($input,"already in work set"))) {
						if($not_said) {
							echo "<blockquote><font color=green>Creating new entries in current work set…</font><br /></blockquote>";
							$not_said = FALSE;
							}
						
						$opposite_type = '';
						if($type == "roman") $opposite_type = "devanagari";
						if($type == "devanagari") $opposite_type = "roman";
						if($opposite_type <> '')
							$query_update = "INSERT INTO ".BASE.".workset (set_id, song_id, ".$type.", ".$opposite_type.", semantic_class_id, login, editor) VALUES (\"".$set_id."\",\"".$song_id."\",\"".$input."\",\"\",\"".$semantic_class_id."\",\"".$user."\",\"".$editor."\")";
						else $query_update = "INSERT INTO ".BASE.".workset (set_id, song_id, ".$type.", semantic_class_id, login, editor, devanagari, roman) VALUES (\"".$set_id."\",\"".$song_id."\",\"".$input."\",\"".$semantic_class_id."\",\"".$user."\",\"".$editor."\",\"\",\"\")";
						$result_update = $bdd->query($query_update);
						if(!$result_update) {
							echo "<br /><font color=red>ERROR 29 modifying table:</font> ".$query_update."<br />";
							die();
							}
						$result_update->closeCursor();
						}
					}
				else {
					if(!is_integer(strpos($input,"already in work set"))) {
						echo "<small><i>Updating entry in work set #".$set_id." for song #".$song_id."…</i></small><br />";
						$query_update = "UPDATE ".BASE.".workset SET ".$type." = \"".$input."\", editor = \"".$editor."\" WHERE set_id = \"".$set_id."\" AND song_id = \"".$song_id."\"";
						$result_update = $bdd->query($query_update);
						if(!$result_update) {
							echo "<br /><font color=red>ERROR 30 modifying table:</font> ".$query_update."<br />";
							die();
							}
						$result_update->closeCursor();
						}
					}
				}
			$song_status[$song_id] = "done";
			continue;
			}
		if(strlen($line) > 25) {
			$errors++;
			if(!$forgood) echo "<tr><td colspan=\"5\"><font color=red>ERROR 31: this line is too long </font>“".$line."”</td></tr>";
			}
		else {
			if($line <> '') {
				$class = trim($line);
				$current_semantic_class_id = semantic_class_id_given_class($class);
				$current_semantic_class_id_mssg = $current_semantic_class_id;
				if($current_semantic_class_id == '') {
					$current_semantic_class_id_mssg = "<font color=red>ERROR 32 semantic class:</font> ‘".$class."’";
			/*		echo "<br />";
					for($m=0; $m < strlen($class); $m++) {
						echo $class[$m]."(".ord($class[$m]).") ";
						}
					echo "<br />"; */
					$errors++;
					}
				if(!$forgood) echo "<tr><td colspan=\"5\"><font color=green><b>".$class."</b></font> <small>(".$current_semantic_class_id_mssg.")</small></td></tr>";
				}
			}
		}
	if(!$forgood) {
		echo "</table>";
		if((2 * $number_different) > $imax AND $errors == 0) {
			echo "<br /><font color=red>WARNING: more than half of the ".$imax." entries are different from current values in the database.<br />Are you sure you imported a </font>‘".$type."’ <font color=red>file?</font><br />";
			$warnings++;
			}
		else if($errors == 0 AND $some_in_database) echo "<br /><font color=green>In the ".$imax." entries, ".$number_different." is/are different from current values in the database.</font><br />";
		if($warnings > 0) echo "<br /><font color=red>".$warnings." warning(s)!</font><br />";
		}
	else echo "</blockquote>";
	fclose($liste_file);
	chdir($olddir);
	return $errors;
	}

function display_workset($short_display,$this_song_id,$read_only,$user,$set_id,$status) {
	global $bdd, $login, $change_class_msg, $hilite_song_class, $hilite_song_transcription, $hilite_song_translation, $hilite_song_comment, $url_this_page, $warning_glossary, $test;
	if($test) echo "post[action] display_workset = ".$_POST['action']."<br />";
	$start_time = time();
	$query = "SELECT * FROM ".BASE.".workset WHERE login = \"".$user."\" AND set_id = \"".$set_id."\" AND status = \"".$status."\"";
	if($this_song_id > 0) $query .= " AND song_id = \"".$this_song_id."\"";
	else $query .= " ORDER BY semantic_class_id";
	$result = $bdd->query($query);
//	echo $query."<br />";
	$n = $result->rowCount();
//	echo "n = ".$n."<br />";
	if($result AND $n > 0) {
		$old_semantic_class_id = '';
		echo "<a name=\"display\"></a>";
		if($this_song_id == 0) {
			echo "<blockquote><h3><i>";
			if($status == "current") echo "Current ";
			if($status == "stored") echo "Stored ";
			if($status == "submit") echo "Submitted ";
			if($status == "valid") echo "Validated ";
			echo "work set #".$set_id." ";
			if($login <> $user) echo "by ".$user." ";
			echo "(".$n." songs)</i></h3></blockquote>";
			}
		echo "<table>";
		if($this_song_id == 0) {
			echo "<tr>";
			echo "<td><span style=\"color:red\">➡</span> <a href=\"workset.php\">Hide display</a></td>";
			if($status == "current" AND $user == $login) {
				echo "<td class=\"tight\" style=\"background-color:Bisque; white-space:nowrap;\">";
				if(isset($_POST['action']) AND $_POST['action'] == "try_delete_work_set") {
					echo "<form name=\"trash\" method=\"post\" action=\"workset.php\" enctype=\"multipart/form-data\">";
					echo "<input type=\"hidden\" name=\"set_id\" value=\"".$set_id."\">";
					echo "<input type=\"hidden\" name=\"action\" value=\"delete_current_work_set\">";
					echo "<input type=\"submit\" class=\"button\" value=\"Yes, delete this work set!\">";
					echo "</form>";
					echo "</td><td style=\"background-color:Red;\">";
					echo "<form name=\"trash\" method=\"post\" action=\"workset.php\" enctype=\"multipart/form-data\">";
				//	echo "<input type=\"hidden\" name=\"action\" value=\"display_current\">";
					echo "<input type=\"submit\" class=\"button\" value=\"No, do not delete!\">";
					echo "</form>";
					echo "</td></tr></table>";
					die();
					}
				else {
					echo "<form name=\"trash\" method=\"post\" action=\"workset.php\" enctype=\"multipart/form-data\">";
			//		echo "<input type=\"hidden\" name=\"action\" value=\"display_current\">";
					echo "<input type=\"hidden\" name=\"action\" value=\"try_delete_work_set\">";
					echo "<input type=\"submit\" class=\"button\" value=\"Delete this work set\">&nbsp;<small>←&nbsp;cannot be undone&nbsp;</small>";
					}
				echo "<input type=\"hidden\" name=\"set_id\" value=\"".$set_id."\">";
				echo "</form>";
				echo "</td>";
				}
			echo "</tr>";
			}
		echo "</table>";
		echo "<table>";
		$translation_correction_english = ReadRewriteRules("english");
		while($ligne = $result->fetch()) {
			$song_id = $ligne['song_id'];
			$input_params = "<input type=\"hidden\" name=\"set_id\" value=\"".$set_id."\"><input type=\"hidden\" name=\"song_id\" value=\"".$song_id."\"><input type=\"hidden\" name=\"user\" value=\"".$user."\"><input type=\"hidden\" name=\"status\" value=\"".$status."\"><input type=\"hidden\" name=\"this_song_id\" value=\"".$this_song_id."\">";
			$devanagari = transcription($song_id,"devanagari");
			$word_ids = transcription($song_id,"word_ids");
			$roman = transcription($song_id,"roman");
			$remarks = remarks($song_id);
			$remarks_marathi = $remarks['marathi'];
			$remarks_english = $remarks['english'];
			$translation_english = reshape_entry($ligne['translation']);
			if($translation_english == '')
				$translation_english = transcription($song_id,"translation");
			if(($status == "current" AND ($user == $login OR is_admin($login))) OR ($status == "submit" AND is_admin($login))) {
				$english_glossary = apply_rules(TRUE,TRUE,$translation_english,$translation_correction_english);
				if($short_display) $edit_glossary = FALSE;
				else $edit_glossary = TRUE;
				$glossary_form = glossary_form($song_id,$english_glossary,$edit_glossary,"400");
				}
			else $glossary_form = '';	
			$remarks_marathi_new = reshape_entry($ligne['remarks_marathi']);
			$word_ids_new = $ligne['word_ids'];
			if($word_ids_new == '') $word_ids_new = $word_ids;
			if($remarks_marathi_new == '') $remarks_marathi_new = $remarks_marathi;
			$remarks_english_new = reshape_entry($ligne['remarks_english']);
			if($remarks_english_new == '') $remarks_english_new = $remarks_english;
			$devanagari_new = reshape_entry($ligne['devanagari']);
			if($devanagari_new == '') {
				$devanagari_new = $devanagari;
			//	$word_ids_new = $word_ids;
				}
			$roman_new = reshape_entry($ligne['roman']);
			if($roman_new == '') $roman_new = $roman;
			$semantic_class_id = $ligne['semantic_class_id'];
			if($semantic_class_id == '')
				$semantic_class_id = semantic_class_id($song_id);
			if($semantic_class_id <> $old_semantic_class_id) {
				echo "</table>";
				echo semantic_class_text_of_song($song_id);
				$query_class = "SELECT * FROM ".BASE.".classification WHERE semantic_class_id = \"".$semantic_class_id."\"";
				$result_class = $bdd->query($query_class);
				$ligne_class = $result_class->fetch();
				$result_class->closeCursor();
				$title_comment = str_replace("\n","<br />",$ligne_class['title_comment']);
				$class_comment = str_replace("\n","<br />",$ligne_class['class_comment']);
				$title_comment_mr = str_replace("\n","<br />",$ligne_class['title_comment_mr']);
				$class_comment_mr = str_replace("\n","<br />",$ligne_class['class_comment_mr']);
				if($title_comment <> '' OR $class_comment <> '' OR $title_comment_mr <> '' OR $class_comment_mr <> '') {
					echo "<table style=\"border-spacing:0px; empty-cells:hide; font-size:80%; padding:0px;\"><tr>";
					echo "<td style=\"background-color:Cornsilk; border:3px solid white;\">".$title_comment_mr."</td>";
					echo "<td style=\"background-color:Cornsilk; border:3px solid white;\">".$title_comment."</td>";
					echo "</tr><tr>";
					echo "<td style=\"background-color:Cornsilk; border:3px solid white;\">".$class_comment_mr."</td>";
					echo "<td style=\"background-color:Cornsilk; border:3px solid white;\">".$class_comment."</td>";
					echo "</tr></table>";
					}
				echo "<table>"; 
				$old_semantic_class_id = $semantic_class_id;
				}
			echo "<tr>";
			if($glossary_form == '') $rowspan = 1;
			else $rowspan = 2;
			$other_set_id = other_work_set($song_id,'','');
			$other_set_user = set_user($other_set_id);
			if($other_set_id > 0 AND $other_set_id <> $set_id) {
				$other_set_mssg = "<small>➡ Already in set #".$other_set_id." by ‘".$other_set_user."’</small>";
				}
			else $other_set_mssg = '';
			if((is_admin($login) OR $login == $user) AND ($status == "current" OR $status == "submit") AND $other_set_mssg == '' AND !$read_only) $can_edit = TRUE;
			else $can_edit = FALSE;
			if($can_edit AND isset($hilite_song_transcription[$song_id]) AND $hilite_song_transcription[$song_id]) $color = "Gold";
			else $color = "white";
			$editing_devanagari = FALSE;
			echo "<td class=\"tight\" rowspan=\"".$rowspan."\" style=\"min-width:60px; vertical-align:top; text-align:center;\">";
			$flag = "song".$song_id;
			echo "id&nbsp;=&nbsp;<a target=\"_blank\" href=\"songs.php?song_id=".$song_id."\">".$song_id."</a>";
	/*		if($can_edit) echo " yes"; else echo " no";
			echo "<br />".$login." ".$user." ".$status; */
			$group_list = group_list(TRUE,"label",FALSE,$song_id,FALSE);	
			if($group_list <> '') {
				echo "<br /><small>Group(s) = ".$group_list."</small><br />";
				}
			if($this_song_id == 0 AND $can_edit) echo "<p>➡&nbsp;<a target=\"_blank\" href=\"workset.php?this_song_id=".$song_id."\">Detach&nbsp;song<a></p>";
			echo "</td>";
			if($can_edit AND isset($_POST['action']) AND $_POST['action'] == "edit_transcription" AND $_POST['song_id'] == $song_id) {
				$editing_devanagari = TRUE;
				echo "<td class=\"tight\" rowspan=\"".$rowspan."\" style=\"min-width:200px; max-width:350px; background-color:Gold; vertical-align:top;\">";
				echo "<a name=\"".$flag."\"></a>";
				echo "<form name=\"change_transcription\" method=\"post\" action=\"workset.php#".$flag."\" enctype=\"multipart/form-data\">";
				echo $input_params;
				echo "<input type=\"hidden\" name=\"action\" value=\"change_transcription\">";
				$roman_transliteration = Transliterate(0,"<br />",$devanagari_new);
				$devanagari_new = str_replace("<br />","\n",$devanagari_new);
				echo "<input type=\"hidden\" name=\"old_devanagari\" value=\"".$devanagari_new."\">";
	//			echo "<input type=\"hidden\" name=\"word_ids\" value=\"".$word_ids_new."\">";
				echo "<textarea name=\"devanagari\" ROWS=\"3\" style=\"width:330px;\">";
				echo $devanagari_new;
				echo "</textarea>";
				$roman_new_words = all_words('',$roman_new);
				$roman_transliteration_words = all_words('',$roman_transliteration);
				$roman_count = count($roman_new_words);
				$roman_transliteration_count = count($roman_transliteration_words);
				echo "<br /><br />";
				$highlight_google = FALSE;
				if($roman_count <> $roman_transliteration_count)
					echo "<span style=\"color:red;\">Inconsistent transliteration:</span><br />".$roman_count." words, should be ".$roman_transliteration_count."<br />";
				if($roman_new <> $roman_transliteration) {
					$highlight_google = TRUE;
					echo "<span style=\"color:red;\">Inconsistent transliteration, should be:</span><br /><span style=\"background-color:yellow;\">".$roman_transliteration."</span><br /><br />";
					}
				$roman_new = str_replace("<br />","\n",$roman_new);
				echo "<textarea name=\"roman\" ROWS=\"3\" style=\"width:330px;\">";
				echo $roman_new;
				echo "</textarea>";
				$google_link = "https://translate.google.com/#mr/en/".str_replace("\n"," <br> ",$devanagari_new);
	//			echo "<small>➡&nbsp;<b>Correct Roman transcription:</b> <a title=\"Click this link to get Google's Roman transliteration\" href=\"https://translate.google.com/#mr/en/".str_replace("\n"," <br> ",$devanagari_new)."\" target=\"_blank\">https://translate.google.com/#mr/en/</a></small>";
				echo "<input type=\"hidden\" name=\"google_link\" value=\"".$google_link."\" />";
				echo "<br /><div style=\"float:right;\">";
				if($highlight_google) echo "<span style=\"color:red;\">Do this ➡&nbsp;</span>";
				echo "<input type=\"submit\" name=\"get_google\" class=\"button\" style=\"background-color:";
				if($highlight_google) echo "Fuchsia";
				else echo "yellow";
				echo ";\" value=\"Get Roman transcription from “Google Translate”\" /></div>";
				echo "<br /><input type=\"submit\" class=\"button\" value=\"SAVE\"><br />";
				echo "</form>";
				echo "<form name=\"cancel_edit_transcription\" method=\"post\" action=\"workset.php#".$flag."\" enctype=\"multipart/form-data\">";
				echo $input_params;
				echo "<input type=\"hidden\" name=\"action\" value=\"display_current\"><input type=\"submit\" class=\"button\" value=\"Cancel\">";
				echo "</form>";
				}
			else {
				echo "<td class=\"tight\" rowspan=\"".$rowspan."\" style=\"min-width:250px; max-width:350px; background-color:".$color."; vertical-align:top;\">";
				echo "<a name=\"".$flag."\"></a>";
				echo "<form name=\"edit_transcription\" method=\"post\" action=\"workset.php#".$flag."\" enctype=\"multipart/form-data\">";
				echo $input_params;
				if($can_edit AND !$short_display) {
					$edit_button = " <input type=\"hidden\" name=\"action\" value=\"edit_transcription\"><input type=\"submit\" class=\"button\" value=\"EDIT\">";
					}
				else $edit_button = '';
				if($devanagari_new <> $devanagari) {
					echo "<font color=red><i>New transcription:</i></font><br />";
			//		echo $devanagari_new." ".$edit_button."<br />";
					echo $devanagari_new."<br /><br />";
					echo "<font color=red><i>Old transcription in database:</font><br />";
					echo $devanagari."</i><br /><br />";
					}
				else echo $devanagari_new."<br /><br />";
			//	else echo $devanagari_new.$edit_button."<br />";
				if($roman_new <> $roman) {
					echo "<font color=red><i>New transcription:</i></font><br />";
					echo $roman_new."<br /><br />";
					echo "<font color=red><i>Old transcription in database:</font><br />";
					echo $roman."</i><br /><br />";
					}
				else echo $roman_new."<br /><br />";
				$roman_transliteration = Transliterate(0,"<br />",$devanagari_new);
				$roman_new_words = all_words('',$roman_new);
				$devanagari_words = all_words('',$devanagari_new);
				$roman_count = count($roman_new_words);
				$devanagari_count = count($devanagari_words);
				if($roman_count <> $devanagari_count)
					echo "<br /><span style=\"color:red;\">Inconsistent transliteration:</span><br />".$roman_count." words, should be ".$devanagari_count."<br />";
				if($roman_new <> $roman_transliteration)
					echo "<span style=\"color:red;\">Inconsistent transliteration, should be:</span><br /><span style=\"background-color:yellow;\">".$roman_transliteration."</span><br />";
				echo "<br />".$edit_button;
				echo "</form>";
				if($other_set_mssg <> '') echo $other_set_mssg;
				}
			echo "</td>";
			
			echo "<td style=\"vertical-align:top; font-size:80%; text-align:left;\">";
			if(!$editing_devanagari) {
				$word_show = Mapping($devanagari_new,$word_ids_new,-1,FALSE);
				echo "▷&nbsp;<i>".str_replace("<br />","<br /></i>▷&nbsp;<i>",$word_show)."</i>";
			//	if($status <> "valid") {
					$url_match = "words.php?song_id=".$song_id;
					echo "<br />➡&nbsp;<a target=\"_blank\" title=\"".$url_match."\" href=\"".$url_match."\">Map&nbsp;words…</a>";
			//		}
				}
			echo "</td>";
			if($can_edit AND isset($hilite_song_translation[$song_id]) AND $hilite_song_translation[$song_id]) $color = "Gold";
			else $color = "white";
			if($can_edit AND isset($_POST['action']) AND $_POST['action'] == "edit_translation" AND $_POST['song_id'] == $song_id) {
				echo "<td class=\"tight\" style=\"min-width:350px; max-width:250px; background-color:Gold;\">";
				echo "<a name=\"".$flag."\"></a>";
				echo "<form name=\"change_translation\" method=\"post\" action=\"workset.php#".$flag."\" enctype=\"multipart/form-data\">";
		//		echo "<input type=\"hidden\" name=\"action\" value=\"display_current\">";
				echo $input_params;
				echo "<input type=\"hidden\" name=\"action\" value=\"change_translation\">";
		/*		if($translation_english == '')
					$translation_english = transcription($song_id,"translation"); */
				$spelling_marks = spelling_marks('en',$translation_english,"red");
				if($spelling_marks <> $translation_english) $display_spelling = TRUE;
				else $display_spelling = FALSE;
				$rule = ReadRewriteRules("english");
				$translation_english = apply_rules(TRUE,TRUE,$translation_english,$rule);
				$translation_english = str_replace('_',' ',$translation_english);
				$translation_english = str_replace("||","’",$translation_english);
				$translation = str_replace("<br />","\n",$translation_english);
				if($display_spelling) echo "<small>".$spelling_marks."<br /><br /></small>";
				echo "<textarea name=\"translation\" ROWS=\"3\" style=\"width:330px;\">";
				echo $translation;
				echo "</textarea>";
				echo "<input type=\"submit\" class=\"button\" value=\"SAVE\">";
				echo "</form>";
				echo "<form name=\"cancel_edit_transcription\" method=\"post\" action=\"workset.php#".$flag."\" enctype=\"multipart/form-data\">";
				echo $input_params;
				echo " <input type=\"hidden\" name=\"action\" value=\"display_current\"><input type=\"submit\" class=\"button\" value=\"Cancel\">";
				echo "</form>";
				}
			else {
				echo "<td class=\"tight\" style=\"min-width:300px; max-width:350px; background-color:".$color.";\">";
				echo "<a name=\"".$flag."\"></a>";
				echo "<form name=\"edit_translation\" method=\"post\" action=\"workset.php#".$flag."\" enctype=\"multipart/form-data\">";
				echo $input_params;
				if($can_edit AND !$short_display) {
					$edit_button = " <input type=\"hidden\" name=\"action\" value=\"edit_translation\"><input type=\"submit\" class=\"button\" value=\"EDIT\">";
					}
				else $edit_button = '';
				if($translation_english == '') {
					$old_translation_english = transcription($song_id,"translation");
					if($old_translation_english == '')
						echo "<i>No translation yet</i>".$edit_button;
					else {
						$spelling_marks = spelling_marks('en',$old_translation_english,"red");
						echo $spelling_marks." ".$edit_button;
						}
					}
				else {
					$old_translation_english = transcription($song_id,"translation");
					$spelling_marks = spelling_marks('en',$translation_english,"red");
					if($translation_english <> $old_translation_english AND $old_translation_english <> '') {
						echo "<font color=red><i>New translation:</i></font><br />";
						echo $spelling_marks." ".$edit_button."<br /><br />";
						echo "<font color=red><i>Old translation in database:</i></font><br />";
						echo $old_translation_english."<br />";
						}
					else echo $spelling_marks.$edit_button;
					}
				echo "</form>";
				if($other_set_mssg <> '') echo $other_set_mssg;
				}
			echo "</td>";
			
			if($login == $user AND $status == "current") {
				if(isset($hilite_song_class[$song_id]) AND $hilite_song_class[$song_id]) $color = "Gold";
				else $color = "Bisque";
				echo "<td class=\"tight\" style=\"text-align:center; background-color:".$color."; min-width:300px; max-width:300px;\">";
				if($this_song_id == 0) {
					echo "<form name=\"remove\" method=\"post\" action=\"workset.php\" enctype=\"multipart/form-data\">";
					echo "<input type=\"hidden\" name=\"action\" value=\"display_current\">";
					echo $input_params;
					echo "<input type=\"hidden\" name=\"remove_".$song_id."\" value=\"yes\">";
					echo "<input type=\"submit\" class=\"button\" value=\"REMOVE THIS SONG FROM MY WORK SET\">";
					echo "</form>";
					}
				if($other_set_mssg == '') {
					echo "<form name=\"move\" method=\"post\" action=\"workset.php#".$flag."\" enctype=\"multipart/form-data\">";
					echo "<input type=\"hidden\" name=\"action\" value=\"move_song\">";
					echo $input_params;
					echo "&nbsp;<input type=\"submit\" class=\"button\" value=\"MOVE THIS SONG TO WORK SET\">";
					echo "&nbsp;#<input type=\"text\" style=\"background-color:Cornsilk;\" name=\"new_set_id\" size=\"3\" value=\"".$set_id."\">";
					if(isset($_POST['action']) AND $_POST['action'] == "move_song" AND $_POST['song_id'] == $song_id AND $_POST['new_set_id'] <> $set_id) {
						echo "<br /><font color=red>Could not move this song to work set #".$_POST['new_set_id'].".<br />It is not one of my stored or submitted work sets.</font>";
						}
					echo "</form>";
					$semantic_class_id = semantic_class_id($song_id);
					echo "<form name=\"classify\" method=\"post\" action=\"workset.php\" enctype=\"multipart/form-data\">";
					echo "<input type=\"hidden\" name=\"action\" value=\"display_current\">";
					echo "<input type=\"hidden\" name=\"classify_".$song_id."\" value=\"yes\">";
					echo $input_params;
					echo "<input type=\"text\" style=\"background-color:Cornsilk;\" name=\"class_".$song_id."\" size=\"13\" value=\"".$semantic_class_id."\">";
					echo "&nbsp;<input type=\"submit\" class=\"button\" value=\"← CHANGE CLASS OF THIS SONG\">";
					if(isset($change_class_msg[$song_id]) AND $change_class_msg[$song_id] <> '')
						echo "<br /><i>".$change_class_msg[$song_id]."</i>";
					echo "</form>";
					}
				echo "</td>";
				}
			echo "</tr>";
			if($glossary_form <> '') {
				echo "<form method=\"post\" action=\"".$url_this_page."#".$flag."\" enctype=\"multipart/form-data\">";
				echo "<input type=\"hidden\" name=\"action\" value=\"glossary\">";
				echo $input_params;
				echo "<tr>";
				echo "<td colspan=\"3\" style=\"background-color:ghostwhite; font-size:80%\">";
				echo $glossary_form;
				if(isset($warning_glossary[$song_id]))
					echo $warning_glossary[$song_id]."<br />";
				if(!$short_display) {
					echo "<span style=\"color:red;\">➡</span> <a target=\"_blank\" href=\"glossary.php\">Edit glossary</a>";
					echo "<div style=\"text-align:center;\">";
					echo "<input type=\"submit\" class=\"button\" value=\"SAVE GLOSSARY ENTRIES\">";
					echo "</div>";
					}
				echo "</td>";
				echo "</tr>";
				echo "</form>";
				}
	//		$remarks = remarks($song_id);
			$group_comments = group_list(TRUE,"comment",FALSE,$song_id,FALSE);
			if($login == $user AND isset($hilite_song_comment[$song_id]) AND $hilite_song_comment[$song_id]) $color = "Gold";
			else $color = "Cornsilk";
			if(!$short_display AND $login == $user AND isset($_POST['action']) AND $_POST['action'] == "edit_comments" AND $_POST['song_id'] == $song_id) $editing_comments = TRUE;
			else $editing_comments = FALSE;
		//	echo "Remarks = ".$remarks_marathi_new." ".$remarks_english_new."<br />";
			if($editing_comments OR $remarks_marathi_new <> '' OR $remarks_english_new <> '' OR $group_comments['mr'] <> '' OR $group_comments['en'] <> '') {
				echo "<tr>";
				echo "<td></td>";
				if($editing_comments) {
					echo "<td class=\"tight\" style=\"background-color:Gold;\" style=\"min-width:250px; max-width:350px;\">";
					if($group_comments['mr'] <> '') echo $group_comments['mr']."<br />";
					echo "<form name=\"change_comments\" method=\"post\" action=\"workset.php#".$flag."\" enctype=\"multipart/form-data\">";
			//		echo "<input type=\"hidden\" name=\"action\" value=\"display_current\">";
					echo $input_params;
					echo "<input type=\"hidden\" name=\"action\" value=\"save_comments\">";
					$remarks_marathi = str_replace("<br />","\n",$remarks_marathi_new);
					echo "<textarea name=\"remarks_marathi\" ROWS=\"10\" style=\"width:330px;\">";
					echo $remarks_marathi;
					echo "</textarea>";
					echo "<input type=\"submit\" class=\"button\" value=\"SAVE\">";
					echo "</td>";
					}
				else {
					echo "<td class=\"tight\" style=\"background-color:".$color."; min-width:250px; max-width:350px;\">";
					if($remarks_marathi_new <> '') echo $remarks_marathi_new;
					else if($group_comments['mr'] <> '') echo $group_comments['mr'];
					else echo "<i>No comments in Marathi</i>";
					echo "</td>";
					}
				if($editing_comments) {
					echo "<td class=\"tight\" style=\"background-color:Gold;\" style=\"min-width:250px; max-width:350px;\" colspan=\"2\">";
					if($group_comments['en'] <> '') echo $group_comments['en']."<br />";
					$remarks_english = str_replace("<br />","\n",$remarks_english_new);
					echo "<textarea name=\"remarks_english\" ROWS=\"10\" style=\"width:330px;\">";
					echo $remarks_english;
					echo "</textarea><br />";
					echo $input_params;
					echo "<input type=\"submit\" class=\"button\" value=\"SAVE\">";
					echo "</form>";
					echo "</td>";
					}
				else {
					echo "<td class=\"tight\" style=\"background-color:".$color."; min-width:250px; max-width:350px;\" colspan=\"2\">";
					if($remarks_english_new <> '') echo $remarks_english_new;
					else if($group_comments['en'] <> '') echo $group_comments['en'];
					else echo "No comments in English";
					echo "</td>";
					}
				if(!$short_display AND $login == $user AND $status == "current" AND $other_set_mssg == '') {
					echo "<td class=\"tight\" style=\"text-align:center; background-color:Cornsilk; min-width:300px; max-width:300px;\">";
					if($editing_comments /* OR $status <> "current" */) {
						echo "<b>← Editing comments</b>";
				//		echo "<br /><br />Saved modifications will be<br />immediately visible in the database.";
						echo "</td>";
						}
					else {
						echo "<form name=\"edit_comments\" method=\"post\" action=\"workset.php#".$flag."\" enctype=\"multipart/form-data\">";
				//		echo "<input type=\"hidden\" name=\"action\" value=\"display_current\">";
						echo "<input type=\"hidden\" name=\"action\" value=\"edit_comments\">";
						echo $input_params;
						echo "<input type=\"submit\" class=\"button\" value=\"← EDIT THESE COMMENTS\">";
						echo "</form>";
						echo "</td>";
						}
					}
				echo "</tr>";
				}
			else {
				if((!$short_display OR $group_comments['mr'] <> '' OR $group_comments['en'] <> '') AND $login == $user AND $status == "current" AND $other_set_mssg == '') {
					echo "<tr>";
					echo "<td></td>";
					echo "<td class=\"tight\" style=\"background-color:".$color.";\">";
					if($group_comments['mr'] <> '') echo $group_comments['mr'];
					else echo "<i>No comments in Marathi</i>";
					echo "</td>";
					echo "<td class=\"tight\" colspan=\"2\" style=\"background-color:".$color.";\">";
					if($group_comments['en'] <> '') echo $group_comments['en'];
					else echo "<i>No comments in English</i>";
					echo "</td>";
					echo "<td class=\"tight\" style=\"background-color:Cornsilk; text-align:center; min-width:300px; max-width:300px;\">";
					echo "<form name=\"remove\" method=\"post\" action=\"workset.php#".$flag."\" enctype=\"multipart/form-data\">";
					echo "<input type=\"hidden\" name=\"action\" value=\"edit_comments\">";
					echo $input_params;
					echo "<input type=\"submit\" class=\"button\" value=\"← CREATE COMMENTS FOR THIS SONG\">";
					echo "</form>";
					echo "</td>";
					echo "</tr>";
					}
				}
			echo "<tr><td colspan=\"5\" style=\"padding:0px; background-color:Azure;\">&nbsp;</td></tr>";
			}
		$result->closeCursor();
		echo "</table>";
		}
	if($this_song_id == 0) {
		echo "<hr>";
		echo "<p><span style=\"color:red\">➡</span> <a href=\"workset.php\">Hide display</a><br />";
		echo "<small>Exec time = ".(time() - $start_time)." seconds</small></p>";
		die();
		}
	return;
	}

function workset_parameters($set_id) {
	global $bdd;
	$workset_parameters['date'] = $workset_parameters['login'] = $workset_parameters['editor'] = $workset_parameters['status'] = '';
	$workset_parameters['size'] = 0;
	$query2 = "SELECT * FROM ".BASE.".workset WHERE set_id = \"".$set_id."\" ORDER BY date DESC";
	$result2 = $bdd->query($query2);
	if(!$result2) return $workset_parameters;
	$ligne2 = $result2->fetch();
	$workset_parameters = $ligne2;
/*	$workset_parameters['editor'] = $ligne2['editor'];
	$workset_parameters['date'] = $ligne2['date'];
	$workset_parameters['login'] = $ligne2['login']; */
	$workset_parameters['size'] = $result2->rowCount();
	$result2->closeCursor();
	$query2 = "SELECT * FROM ".BASE.".workset WHERE set_id = \"".$set_id."\" AND translation <> '' ORDER BY date DESC";
	$result2 = $bdd->query($query2);
	if(!$result2) return $workset_parameters;
	$ligne2 = $result2->fetch();
	$workset_parameters['translations'] = $result2->rowCount();
	$result2->closeCursor();
	return $workset_parameters;
	}

function unvalidate_workset($user,$set_id) {
	global $bdd;
	$query_update = "UPDATE ".BASE.".workset SET status = \"submit\" WHERE set_id = \"".$set_id."\"";
	$result_update = $bdd->query($query_update);
	if(!$result_update) {
		echo "<br /><font color=red>ERROR 33 modifying table:</font> ".$query_update."<br />";
		die();
		}
	$result_update->closeCursor();
	return;
	}

function validate_workset($user,$set_id) {
	global $bdd, $login;
	$workset_parameters = workset_parameters($set_id);
	if($workset_parameters['status'] <> "submit") {
		echo "<br /><font color=red>ERROR 34 validating work set</font> #".$set_id."<font color=red>. Its status is </font>‘".$workset_parameters['status']."’<font color=red> and it should be ‘submit’.<br /></font>";
		return;
		}
	$query_update = "UPDATE ".BASE.".workset SET status = \"valid\" WHERE set_id = \"".$set_id."\"";
	$result_update = $bdd->query($query_update);
	if(!$result_update) {
		echo "<br /><font color=red>ERROR 35 modifying table:</font> ".$query_update."<br />";
		die();
		}
	$result_update->closeCursor();
	$query_songs = "SELECT * FROM ".BASE.".workset WHERE set_id = \"".$set_id."\" ORDER BY song_id ASC";
	$result_songs = $bdd->query($query_songs);
	echo "<blockquote><small>";
	$ignored_translations = $new_translations = $changed_translations = 0;
	while($ligne_songs = $result_songs->fetch()) {
		$song_id = $ligne_songs['song_id'];
		$other_work_set = other_work_set($song_id,'','valid');
		if($other_work_set > 0 AND $other_work_set <> $set_id) {
			$other_user = set_user($other_work_set);
			echo "<font color=red>Song</font> #".$song_id." <font color=red>discarded because it was already in set</font> #".$other_work_set." <font color=red>by</font> ‘".$other_user."’<br />";
			$query_delete = "DELETE FROM ".BASE.".workset WHERE set_id = \"".$set_id."\" AND song_id = \"".$song_id."\"";
			$result_delete = $bdd->query($query_delete);
			if(!$result_delete) {
				echo "<br /><font color=red>ERROR 36 modifying table:</font> ".$query_delete."<br />";
				die();
				}
			$result_delete->closeCursor();
			}
		$semantic_class_id = semantic_class_id($song_id);
		$query_update = "UPDATE ".BASE.".workset SET semantic_class_id = \"".$semantic_class_id."\" WHERE song_id = \"".$song_id."\"";
	//	echo $query_update."<br />";
		$result_update = $bdd->query($query_update);
		if(!$result_update) {
			echo "<br /><font color=red>ERROR 37 modifying table:</font> ".$query_update."<br />";
			die();
			}
		$result_update->closeCursor();
		$translation = fix_typo(reshape_entry($ligne_songs['translation']),0);
		$devanagari = fix_typo(reshape_entry($ligne_songs['devanagari']),0);
		$word_ids = $ligne_songs['word_ids'];
		$roman = fix_typo(reshape_entry($ligne_songs['roman']),0);
		$remarks_marathi = fix_typo(reshape_entry($ligne_songs['remarks_marathi']),0);
		$remarks_english = fix_typo(reshape_entry($ligne_songs['remarks_english']),0);
		StoreSpelling(FALSE,'en',$translation);
		$old_translation = transcription($song_id,"translation");
/*		echo "song_id = ".$song_id."<br />";
		echo "translation = “".$translation."”<br />";
		echo "old_translation = “".$old_translation."”<br />"; */
		if($translation <> '' AND $translation <> $old_translation) {
			if($old_translation <> '') $changed_translations++;
			else $new_translations++;
			// Enter translation change in table ‘SONGS’
			StoreSpelling(FALSE,'en',$translation);
			$query_update = "UPDATE ".BASE.".songs SET translation_english = \"".$translation."\" WHERE song_id = \"".$song_id."\"";
			$result_update = $bdd->query($query_update);
	//		echo $query_update."<br />";
			if(!$result_update) {
				echo "<br /><font color=red>ERROR 38 modifying table:</font> ".$query_update."<br />";
				die();
				}
			$result_update->closeCursor();
			// Enter this change in table ‘translations’ if it is new
			$query = "SELECT version, text FROM ".BASE.".translations WHERE song_id = \"".$song_id."\" ORDER BY version DESC LIMIT 1";
		//	echo $query."<br />";
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
		//		echo "translation <> preceding_text<br />";
				if($translation == '') $translation = '~';
				if($version == 1 AND trim($old_translation) <> '') {
					$query = "INSERT INTO ".BASE.".translations (song_id, version, text, login) VALUES (\"".$song_id."\",\"0\",\"".$old_translation."\",\"\")";
		//			echo $query."<br />";
					$result = $bdd->query($query);
					if(!$result) {
						echo "<br /><font color=red>".$query."<br />";
						echo "ERROR: FAILED</font>";
						die();
						}
					$result->closeCursor();
					}
				$query = "INSERT INTO ".BASE.".translations (song_id, version, text, login) VALUES (\"".$song_id."\",\"".$version."\",\"".$translation."\",\"".$user."\")";
				$result = $bdd->query($query);
		//		echo $query."<br />";
				if(!$result) {
					echo "<br /><font color=red>".$query."<br />";
					echo "ERROR: FAILED</font>";
					die();
					}
				$result->closeCursor();
				}
			}
		else {
			echo "<small>Song #".song($song_id,$song_id)." ignored, unchanged translation</small><br />";
			$ignored_translations++;
			}
		
		if($devanagari <> '') {
			$query_update = "UPDATE ".BASE.".songs SET word_ids = \"".$word_ids."\" WHERE song_id = \"".$song_id."\"";
			$result_update = $bdd->query($query_update);
			if(!$result_update) {
				echo "<br /><font color=red>ERROR 39 modifying table:</font> ".$query_update."<br />";
				die();
				}
			$result_update->closeCursor();
			}
			
		$old_devanagari = transcription($song_id,"devanagari");
		if($devanagari <> '' AND $devanagari <> $old_devanagari) {
			// Enter transcription change in table ‘SONGS’
			StoreSpelling(FALSE,'mr',$devanagari);
			$query_update = "UPDATE ".BASE.".songs SET devanagari = \"".$devanagari."\" WHERE song_id = \"".$song_id."\"";
			$result_update = $bdd->query($query_update);
			if(!$result_update) {
				echo "<br /><font color=red>ERROR 40 modifying table:</font> ".$query_update."<br />";
				die();
				}
			$result_update->closeCursor();
			// Enter this change in table ‘song_metadata’
			$query = "SELECT devanagari FROM ".BASE.".song_metadata WHERE song_id = \"".$song_id."\" AND (devanagari <> \"\" OR roman_devanagari <> \"\")";
			$result = $bdd->query($query);
			$n = $result->rowCount();
			$preceding_text = '';
			if($n > 0) {
				$ligne = $result->fetch();
				$preceding_text = $ligne['devanagari'];
				}
			$result->closeCursor();
			if($devanagari <> $preceding_text) {
				if($n == 0) {
					$query = "INSERT INTO ".BASE.".song_metadata (song_id, devanagari, login, roman_devanagari, remarks_marathi, remarks_english) VALUES (\"".$song_id."\",\"".$devanagari."\",\"".$login."\",\"\",\"\",\"\")";
					$result = $bdd->query($query);
					if(!$result) {
						echo "<br /><font color=red>".$query."<br />";
						echo "ERROR 2: FAILED</font>";
						die();
						}
					$result->closeCursor();
					}
				else {
					$query = "UPDATE ".BASE.".song_metadata SET devanagari = \"".$devanagari."\", login = \"".$login."\" WHERE song_id = \"".$song_id."\" AND (devanagari <> \"\" OR roman_devanagari <> \"\")";
					$result = $bdd->query($query);
					if(!$result) {
						echo "<br /><font color=red>".$query."<br />";
						echo "ERROR: FAILED</font>";
						die();
						}
					$result->closeCursor();
					}
				}		
			}
		
		$old_roman = transcription($song_id,"roman");
		if($roman <> '' AND $roman <> $old_roman) {
			// Enter transcription change in table ‘SONGS’
			$query_update = "UPDATE ".BASE.".songs SET roman_devanagari = \"".$roman."\" WHERE song_id = \"".$song_id."\"";
			$result_update = $bdd->query($query_update);
			if(!$result_update) {
				echo "<br /><font color=red>ERROR 41 modifying table:</font> ".$query_update."<br />";
				die();
				}
			$result_update->closeCursor();
			// Enter this change in table ‘song_metadata’
			$query = "SELECT roman_devanagari FROM ".BASE.".song_metadata WHERE song_id = \"".$song_id."\" AND (devanagari <> \"\" OR roman_devanagari <> \"\")";
			$result = $bdd->query($query);
			$n = $result->rowCount();
			$preceding_text = '';
			if($n > 0) {
				$ligne = $result->fetch();
				$preceding_text = $ligne['roman_devanagari'];
				}
			$result->closeCursor();
			if($roman <> $preceding_text) {
				if($n == 0) {
					$query = "INSERT INTO ".BASE.".song_metadata (song_id, roman_devanagari, login, devanagari, remarks_marathi, remarks_english) VALUES (\"".$song_id."\",\"".$roman."\",\"".$login."\",\"\",\"\",\"\")";
					$result = $bdd->query($query);
					if(!$result) {
						echo "<br /><font color=red>".$query."<br />";
						echo "ERROR 3: FAILED</font>";
						die();
						}
					$result->closeCursor();
					}
				else {
					$query = "UPDATE ".BASE.".song_metadata SET roman_devanagari = \"".$roman."\", login = \"".$login."\" WHERE song_id = \"".$song_id."\" AND (devanagari <> \"\" OR roman_devanagari <> \"\")";
					$result = $bdd->query($query);
					if(!$result) {
						echo "<br /><font color=red>".$query."<br />";
						echo "ERROR: FAILED</font>";
						die();
						}
					$result->closeCursor();
					}
				}		
			}
		
		if($remarks_marathi <> '') {
			// Enter translation change in table ‘SONGS’
			$query_update = "UPDATE ".BASE.".songs SET remarks_marathi = \"".$remarks_marathi."\" WHERE song_id = \"".$song_id."\"";
			$result_update = $bdd->query($query_update);
			if(!$result_update) {
				echo "<br /><font color=red>ERROR 42 modifying table:</font> ".$query_update."<br />";
				die();
				}
			$result_update->closeCursor();
			// Enter this change in table ‘song_metadata’
			$query = "SELECT remarks_marathi FROM ".BASE.".song_metadata WHERE song_id = \"".$song_id."\" AND devanagari = \"\" AND roman_devanagari = \"\"";
			$result = $bdd->query($query);
			$n = $result->rowCount();
			$preceding_text = '';
			if($n > 0) {
				$ligne = $result->fetch();
				$preceding_text = $ligne['remarks_marathi'];
				}
			$result->closeCursor();
			if($remarks_marathi <> $preceding_text) {
				if($n == 0) {
					$query = "INSERT INTO ".BASE.".song_metadata (song_id, remarks_marathi, login, roman_devanagari, remarks_english) VALUES (\"".$song_id."\",\"".$remarks_marathi."\",\"".$login."\",\"\",\"\")";
					$result = $bdd->query($query);
					if(!$result) {
						echo "<br /><font color=red>".$query."<br />";
						echo "ERROR 4: FAILED</font>";
						die();
						}
					$result->closeCursor();
					}
				else {
					$query = "UPDATE ".BASE.".song_metadata SET remarks_marathi = \"".$remarks_marathi."\", login = \"".$login."\" WHERE song_id = \"".$song_id."\" AND devanagari = \"\" AND roman_devanagari = \"\"";
					$result = $bdd->query($query);
					if(!$result) {
						echo "<br /><font color=red>".$query."<br />";
						echo "ERROR: FAILED</font>";
						die();
						}
					$result->closeCursor();
					}
				}		
			}	
		
		if($remarks_english <> '') {
			// Enter translation change in table ‘SONGS’
			$query_update = "UPDATE ".BASE.".songs SET remarks_english = \"".$remarks_english."\" WHERE song_id = \"".$song_id."\"";
			$result_update = $bdd->query($query_update);
			if(!$result_update) {
				echo "<br /><font color=red>ERROR 43 modifying table:</font> ".$query_update."<br />";
				die();
				}
			$result_update->closeCursor();
			// Enter this change in table ‘song_metadata’
			$query = "SELECT remarks_english FROM ".BASE.".song_metadata WHERE song_id = \"".$song_id."\" AND devanagari = \"\" AND roman_devanagari = \"\"";
			$result = $bdd->query($query);
			$n = $result->rowCount();
			$preceding_text = '';
			if($n > 0) {
				$ligne = $result->fetch();
				$preceding_text = $ligne['remarks_english'];
				}
			$result->closeCursor();
			if($remarks_english <> $preceding_text) {
				if($n == 0) {
					$query = "INSERT INTO ".BASE.".song_metadata (song_id, remarks_english, login, devanagari, roman_devanagari, remarks_marathi) VALUES (\"".$song_id."\",\"".$remarks_english."\",\"".$login."\",\"\",\"\",\"\")";
					$result = $bdd->query($query);
					if(!$result) {
						echo "<br /><font color=red>".$query."<br />";
						echo "ERROR 5: FAILED</font>";
						die();
						}
					$result->closeCursor();
					}
				else {
					$query = "UPDATE ".BASE.".song_metadata SET remarks_english = \"".$remarks_english."\", login = \"".$login."\" WHERE song_id = \"".$song_id."\" AND devanagari = \"\" AND roman_devanagari = \"\"";
					$result = $bdd->query($query);
					if(!$result) {
						echo "<br /><font color=red>".$query."<br />";
						echo "ERROR 44: FAILED</font>";
						die();
						}
					$result->closeCursor();
					}
				}		
			}
		}
	$result_songs->closeCursor();
	echo "</small><br />";
	echo $new_translations." new translations have been created, ".$changed_translations." have been modified, ".$ignored_translations." old translations have been unchanged.<br />";
	echo "</blockquote>";
	$query_update = "UPDATE ".BASE.".workset SET devanagari = \"\", word_ids = \"\", roman = \"\", translation = \"\", remarks_marathi= \"\", remarks_english= \"\" WHERE set_id = \"".$set_id."\"";
	$result_update = $bdd->query($query_update);
	if(!$result_update) {
		echo "<br /><font color=red>ERROR 45 modifying table:</font> ".$query_update."<br />";
		die();
		}
	return;
	}

function delete_workset($set_id) {
	global $bdd;
	$sql = "DELETE FROM ".BASE.".workset WHERE set_id = \"".$set_id."\"";
	$result = $bdd->query($sql);
	$result->closeCursor();
	return;
	}

function fix_versions($show_errors,$fix) {
	global $bdd;
	$missing = 0;
	$query_workset = "SELECT * FROM ".BASE.".workset WHERE status = \"valid\"";
	$result_workset = $bdd->query($query_workset);
	while($ligne = $result_workset->fetch()) {
		$song_id = $ligne['song_id'];
		$set_id = $ligne['set_id'];
		$user = $ligne['login'];
		$date = $ligne['date'];
		$translation = reshape_entry(transcription($song_id,"translation"));
		if($translation == '') {
			if($show_errors) echo "<small>Missing translation #".song($song_id,$song_id)." in work set ".$set_id." dated ".$date." by ".$user."<br /></small>";
			$missing++;
			continue;
			}
		if(!$fix) continue;
		
		// Enter this change in table ‘translations’ if it is new
		$query = "SELECT version, text FROM ".BASE.".translations WHERE song_id = \"".$song_id."\" ORDER BY version DESC LIMIT 1";
		$result = $bdd->query($query);
		$n = $result->rowCount();
		$preceding_text = '';
		if($n == 0) $version = 1;
		else {
			$ligne = $result->fetch();
			$version = 1 + $ligne['version'];
			$preceding_text = reshape_entry($ligne['text']);
			}
		$result->closeCursor();
		if($version > 1) continue;
		if($translation <> $preceding_text) {
			if($preceding_text <> '') {
				echo "<small>➡ Workset ".$set_id." #".song($song_id,$song_id)." translation <> preceding_text<br />";
				echo "• ".$preceding_text."<br />";
				echo "• ".$translation."<br /></small>";
				}
			$query = "INSERT INTO ".BASE.".translations (song_id, version, text, login, date) VALUES (\"".$song_id."\",\"".$version."\",\"".$translation."\",\"".$user."\",\"".$date."\")";
			echo "<small>Updating ".song($song_id,$song_id)." by ".$user." dated ".$date."<br /></small>";
		//	echo $query."<br /><br />";
			$result = $bdd->query($query);
			$result->closeCursor();
			$query = "UPDATE ".BASE.".translations SET date = \"".$date."\" WHERE song_id = \"".$song_id."\" AND version = \"".$version."\"";
			$result = $bdd->query($query);
			$result->closeCursor();
			}
		}
	$result_workset->closeCursor();
	if($show_errors) echo "<small>➡ ".$missing." missing translations in validated work sets</small><br />";
	return;
	}
?>