<?php
// session_start();
require_once("_base_urls.php");
require_once("_relier_edit.php");
require_once("_tasks.php");
require_once("_edit_tasks.php");

if(!check_serious_attempt('browse')) die();

$test = FALSE;
// $test = TRUE; 

if(isset($_GET['song_id'])) $this_song_id = $_GET['song_id'];
else if(isset($_POST['song_id'])) $this_song_id = $_POST['song_id'];
else $this_song_id = 0;

$i_word = -1;

if(isset($_POST['word_stress'])) $word_stress = $_POST['word_stress'];
else if(isset($_GET['word_stress'])) $word_stress = urldecode($_GET['word_stress']);
else $word_stress = '';
// echo $word_stress."<br />";

if($this_song_id == 0) {
	echo "<span style=\"color:red;\">No song has been selected</span>";
	die();
	}

if(identified()) {
	$login = $_SESSION['login'];
	$old_time = time() - 3600;
	$sql = "DELETE FROM ".BASE.".t_access WHERE acce_time < \"".$old_time."\"";
	$result = $bdd->query($sql);
	$result->closeCursor();
	}
else $login = '';
$_SESSION['try'] = 0;

// This page saves changes either in the 'songs' or in the 'workset' table.
// By default, the 'songs' table
// If the song is "busy" in an active workset (current, stored, submitted)
// and its devanagari transcription is not empty (i.e. it has been modified in the work set)
// and if the user is the owner of the work set, or an admin
// then changes of word_ids are saved in the work set
// If no translation has been saved in the work set, translation will be picked up in the 'songs' table


$table_name = "songs";
// if(isset($_POST['table_name'])) $table_name = $_POST['table_name'];

$translation_english = $word_ids = ''; $n = 0;
$busy_in_work_set = busy_in_work_set($this_song_id);
if($busy_in_work_set > 0) {
	$other_user = set_user($busy_in_work_set);
	$query = "SELECT devanagari, roman, translation, word_ids FROM ".BASE.".workset WHERE song_id = \"".$this_song_id."\" AND status <> \"valid\"";
	$result = $bdd->query($query);
	$n = $result->rowCount();
	if($n > 0) {
		$ligne = $result->fetch();
		$result->closeCursor();
		$devanagari = $ligne['devanagari'];
		$roman_devanagari = $ligne['roman'];
		if($login == $other_user OR is_admin($login)) {
			$translation_english = $ligne['translation'];
			if($translation_english == '') $translation_english = transcription($this_song_id,"translation");
			if($devanagari == '') $devanagari = transcription($this_song_id,"devanagari");
			else {
				$word_ids = $ligne['word_ids'];
				$table_name = "workset";
				}
			}
//		else $table_name = "songs";
		}
	}

if($test) echo "table_name = ".$table_name."<br />";
if($test) echo "translation_english = ".$translation_english."<br />";

if($table_name == "songs") {
	$query = "SELECT devanagari, roman_devanagari, translation_english, word_ids FROM ".BASE.".songs WHERE song_id = \"".$this_song_id."\"";
	$result = $bdd->query($query);
	$n = $result->rowCount();
	$ligne = $result->fetch();
	$result->closeCursor();
	$devanagari = $ligne['devanagari'];
	$roman_devanagari = $ligne['roman_devanagari'];
	if($translation_english == '') $translation_english = $ligne['translation_english'];
	$word_ids = $ligne['word_ids'];
	}

$devanagari_words = all_words('',$devanagari);
$number_words = count($devanagari_words);
if($test) echo "word_ids = ".$word_ids."<br />";
$word_choice = array();
if($word_ids <> '') {
	$word_choice = explode('-',$word_ids);
	$changed = FALSE;
	for($i = 0; $i < $number_words; $i++) {
		$id = $word_choice[$i];
		if($id == 0) continue;
		$devanagari_word = $devanagari_words[$i];
		$query_already = "SELECT * FROM ".BASE.".meaning WHERE id = \"".$id."\" AND devanagari LIKE \"".$devanagari_word."\"";
		$result_already = $bdd->query($query_already);
		$n_already = $result_already->rowCount();
		$result_already->closeCursor();
		if($n_already == 0) {
			$word_choice[$i] = 0;
			$changed = TRUE;
			if($test) echo "Rule #".$id." deleted or irrelevant => word_choice[".$i."] = 0<br />";
			}
		}
	if($changed) {
		$word_ids = implode('-',$word_choice);
		$query_change = "UPDATE ".BASE.".".$table_name." SET word_ids = \"".$word_ids."\" WHERE song_id = \"".$this_song_id."\"";
		if($test) echo $query_change." (storing word_ids in song)<br />";
		$result_change = $bdd->query($query_change);
		$result_change->closeCursor();
		}
	}
else for($i = 0; $i < $number_words; $i++) $word_choice[$i] = 0;

$devanagari_full = "§beg§ ".str_replace("<br />"," §end§<br />§beg§ ",$devanagari)." §end§";
$devanagari_words = all_words("<br />",$devanagari_full);
$roman_words = array();
if($roman_devanagari <> '') {
	// Can be empty in a workset importing a devanagari file
	$roman_devanagari_full = "§beg§ ".str_replace("<br />"," §end§<br />§beg§ ",$roman_devanagari)." §end§";
	$roman_words = all_words("<br />",$roman_devanagari_full);
	}
if(count($devanagari_words) <> count($roman_words)) {
	$url = SITE_URL."edit-songs.php?start=".$this_song_id."&end=".$this_song_id;
	$message_roman = "Roman Devanagari does not match Devanagari.<br />➡ You should <a target=\"_blank\" href=\"".$url."\">edit the transcription</a> of this song.";
	$roman_devanagari = '';
	}
else $message_roman = '';

if($n == 0) {
	echo "<p style=\"text-align:center; color:red;\">No song found with identifier #".$this_song_id."</p>";
	die();
	}
	
$name = "Map id = ".$this_song_id;
$canonic_url = '';
$mssg = '';
	
require_once("_header.php");

echo "<h2>Mapping words in a transcription<br />";
if($busy_in_work_set == 0) {
	$url = SITE_URL."songs.php?song_id=".$this_song_id;
	echo "Song #<a target=\"_blank\" href=\"".$url."\">".$this_song_id."</a>";
	}
else echo "Song #".$this_song_id." in work set #".$busy_in_work_set;
echo "</h2>";

if(is_mapper($login) AND isset($_POST['delete']) AND $_POST['delete'] > 0) {
	$id = $_POST['delete'];
	$query_delete = "DELETE FROM ".BASE.".meaning WHERE id = \"".$id."\"";
	if($test) echo $query_delete." (delete)<br />";
	$result_delete = $bdd->query($query_delete);
	if(!$result_delete) {
		echo "<br /><span style=\"color:red;\">".$query_delete."<br />";
		echo "ERROR: DELETE FAILED</span>";
		die();
		}
	$result_delete->closeCursor();
	unset($_POST['delete']);
	}

if(isset($_GET['show_dictionary'])) $show_dictionary = $_GET['show_dictionary'];
	else $show_dictionary = FALSE;

if(is_mapper($login)) {
	$query = "SELECT * FROM ".BASE.".meaning ORDER BY devanagari";
	$result = $bdd->query($query);
	$n_dict_entries = $result->rowCount();
	$result->closeCursor();
	}

if(isset($_POST['i_word']))	$i_word = $_POST['i_word'];
else $i_word = -1;

if(is_mapper($login) AND isset($_POST['choice']) AND $_POST['choice'] == 0 AND $i_word >= 0 AND (!isset($_POST['english_word_new']) OR trim($_POST['english_word_new']) == '')) {
	$word_choice[$i_word] = 0;
	$word_ids = implode('-',$word_choice);
	$query_change = "UPDATE ".BASE.".".$table_name." SET word_ids = \"".$word_ids."\" WHERE song_id = \"".$this_song_id."\"";
	if($test) echo $query_change." (storing word_ids in song where word_choice[".$i_word."] = 0)<br />";
	$result_change = $bdd->query($query_change);
	$result_change->closeCursor();
	$_POST['choice'] = -1;
//	unset($_POST['i_word']);
	}

if(is_mapper($login) AND isset($_POST['i_word']) AND (!isset($_POST['english_word_new']) OR trim($_POST['english_word_new']) == '')) {
	$devanagari_word = $_POST['word_stress'];
	if(isset($_POST['choice'])) $id_choice = $_POST['choice'];
	else $id_choice = -1;
	$left_context = $_POST['left_context'];
	$right_context = $_POST['right_context'];
	$query = "SELECT * FROM ".BASE.".meaning WHERE devanagari LIKE \"".$devanagari_word."\"";
	$result = $bdd->query($query);
	$n = $result->rowCount();
	$default_done = array();
	while($ligne = $result->fetch()) {
		$id = $ligne['id'];
		if(!isset($_POST["default_".$id])) {
			if(is_super_admin($login)) {
				echo "<br /><span style=\"color:red;\">ERROR !isset(POST[default_".$id."]</span><br />";
			//	die();
				}
			continue;
			}
		$default = $_POST["default_".$id];
		$left_context_id = $ligne['left_context'];
		$right_context_id = $ligne['right_context'];
		if(!isset($_POST["english_word_".$id])) {
			if(is_super_admin($login)) {
				echo "<br /><span style=\"color:red;\">ERROR !isset(POST[english_word_".$id."]</span><br />";
				die();
				}
			continue;
			}
		$english_word = simple_form(my_keep_quotes($_POST["english_word_".$id]));
		$context = simple_form(my_keep_quotes($_POST["context_".$id]));
		if($test) echo $id." ".$devanagari_word." ".$english_word." ".$default." ".$context."<br />";
		if($english_word <> '') {
			$query_change = "UPDATE ".BASE.".meaning SET english = \"".$english_word."\", context = \"".$context."\", default_choice = \"".$default."\" WHERE id = \"".$id."\"";
			$result_change = $bdd->query($query_change);
			if($test) echo $query_change." (CHANGE ENGLISH AND REMARKS)<br />";
			$result_change->closeCursor();
			}
		else {
			$query_change = "DELETE FROM ".BASE.".meaning WHERE id = \"".$id."\"";
			$result_change = $bdd->query($query_change);
			if($test) echo $query_change." (DELETE RULE BECAUSE ENGLISH = '')<br />";
			$result_change->closeCursor();
			$word_choice[$i_word] = 0;
			$word_ids = implode('-',$word_choice);
			$query_change = "UPDATE ".BASE.".".$table_name." SET word_ids = \"".$word_ids."\" WHERE song_id = \"".$this_song_id."\"";
			if($test) echo $query_change." (storing word_ids in song after deleting rule)<br />";
			$result_change = $bdd->query($query_change);
			$result_change->closeCursor();
			}
		if($id == $id_choice) {
			$id_new = $id;
			$n_already = 1;
			if($english_word <> '') {
				$query_already = "SELECT * FROM ".BASE.".meaning WHERE devanagari = \"".$devanagari_word."\" AND left_context = \"".$left_context."\" AND right_context = \"".$right_context."\"";
				$result_already = $bdd->query($query_already);
				$n_already = $result_already->rowCount();
				$result_already->closeCursor();
				if($test) echo $query_already." (check whether this rule exists: n_already = ".$n_already.")<br />";
				if($n_already == 0) {
					$roman_devanagari_word = Transliterate($this_song_id,"<br />",$devanagari_word);
					$simple_form = simple_form($roman_devanagari_word);
					$query_change = "INSERT INTO ".BASE.".meaning (devanagari, left_context, right_context, english, simple_form, default_choice, song_example) VALUES (\"".$devanagari_word."\",\"".$left_context."\",\"".$right_context."\",\"".$english_word."\",\"".$simple_form."\",\"".$default."\",\"".$this_song_id."\")";
					if($test) {
						echo "left_context_id = ".$left_context_id."<br />";
						echo "right_context_id = ".$right_context_id."<br />";
						echo "old left_context = ".$left_context."<br />";
						echo "old right_context = ".$right_context."<br />";
						echo $query_change." (insert different context)<br />";
						}
					$result_change = $bdd->query($query_change);
					$result_change->closeCursor();
					$query_id = "SELECT id FROM ".BASE.".meaning WHERE devanagari = \"".$devanagari_word."\" AND english = \"".$english_word."\" AND left_context = \"".$left_context."\" AND right_context = \"".$right_context."\" AND song_example = \"".$this_song_id."\"";
					$result_id = $bdd->query($query_id);
					$ligne_id = $result_id->fetch();
					$result_id->closeCursor();
					$id_new = $ligne_id['id'];
					if($test) echo $query_id." (get id of just created rule: ".$id_new.")<br />";
					}
				$word_choice[$i_word] = $id_new;
				$word_ids = implode('-',$word_choice);
				$query_change = "UPDATE ".BASE.".".$table_name." SET word_ids = \"".$word_ids."\" WHERE song_id = \"".$this_song_id."\"";
				if($test) echo $query_change." (storing word_ids in song)<br />";
				$result_change = $bdd->query($query_change);
				$result_change->closeCursor();
				}
			}
		}
	$result->closeCursor();
	}

if(is_mapper($login) AND isset($_POST['english_word_new']) AND trim($_POST['english_word_new']) <> '') {
	$english_word_new = simple_form(my_keep_quotes($_POST['english_word_new']));
	$remark_new = simple_form(my_keep_quotes($_POST['remark_new']));
	$devanagari_word = $_POST['devanagari_word'];
	$left_context = $_POST['left_context'];
	$right_context = $_POST['right_context'];
	$i_word = $_POST['i_word'];
	$message = "<span style=\"color:blue;\">➡ Creating</span> ";
	if($left_context <> '') $message .= "(".$left_context.") ";
	$message .= $devanagari_word;
	if($remark_new <> '') $remarks = "[".$remark_new."]";
	else $remarks = '';
	if($right_context <> '') $message .= " (".$right_context.")";
	$message .= " = ".$english_word_new." ".$remarks;
	echo $message."<br />";
	$query = "SELECT id FROM ".BASE.".meaning WHERE devanagari LIKE \"".$devanagari_word."\" AND english LIKE \"".$english_word_new."\"";
	$query .= " AND song_example = \"".$this_song_id."\""; // *****
	if($left_context <> '') $query .= " AND left_context LIKE \"".$left_context."\"";
	if($right_context <> '') $query .= " AND right_context LIKE \"".$right_context."\"";
	if($test) echo $query." (find all occurrences of Devanagari)<br />";
	$result = $bdd->query($query);
	$n = $result->rowCount();
	if($n == 0) {
		$roman_devanagari_word = Transliterate($this_song_id,"<br />",$devanagari_word);
		$simple_form = simple_form($roman_devanagari_word);
		$query_change = "INSERT INTO ".BASE.".meaning (devanagari, left_context, right_context, english, simple_form, context, song_example) VALUES (\"".$devanagari_word."\",\"".$left_context."\",\"".$right_context."\",\"".$english_word_new."\",\"".$simple_form."\",\"".$remark_new."\",\"".$this_song_id."\")";
		if($test) echo $query_change." (310)<br />";
		}
	else {
		$ligne = $result->fetch();
		$id = $ligne['id'];
		$query_change = "UPDATE ".BASE.".meaning SET context = \"".$remark_new."\", song_example = \"".$this_song_id."\" WHERE id = \"".$id."\"";
		}
	$result->closeCursor();
	if($test) echo $query_change." (update or create rule)<br />";
	$result_change = $bdd->query($query_change);
	if(!$result_change) {
		echo "<br /><span style=\"color:red;\">".$query_change."<br />";
		echo "ERROR: FAILED</span>";
		die();
		}
	$result_change->closeCursor();
	$query = "SELECT id FROM ".BASE.".meaning WHERE devanagari LIKE \"".$devanagari_word."\" AND english LIKE \"".$english_word_new."\"";
	if($left_context <> '') $query .= " AND left_context LIKE \"".$left_context."\"";
	if($right_context <> '') $query .= " AND right_context LIKE \"".$right_context."\"";
	if($test) echo $query." (get id of new rule)<br />";
	$result = $bdd->query($query);
	$ligne = $result->fetch();
	$result->closeCursor();
	$id = $ligne['id'];
	$word_choice[$i_word] = $id;
	$word_ids = implode('-',$word_choice);
	$query = "UPDATE ".BASE.".".$table_name." SET word_ids = \"".$word_ids."\" WHERE song_id = \"".$this_song_id."\"";
	if($test) echo $query." (storing word_ids in song)<br />";
	$result = $bdd->query($query);
	$result->closeCursor();
	$word_stress = $devanagari_word;
	}

$nb_words_and_breaks = count($devanagari_words); // Including line breaks

echo "<blockquote>";
$devanagari_color = $devanagari;
$roman_color = $roman_devanagari;
if($i_word >= 0 OR $word_stress <> '') {
	if($test) echo $i_word." ".$word_stress." ok<br />";
	$devanagari_color = $roman_color = '';
	for($i = $j = 0; $i < $nb_words_and_breaks; $i++) {
		$devanagari_word = $devanagari_words[$i];
		if($roman_devanagari <> '') $roman_word = $roman_words[$i];
		if($devanagari_word == "§beg§" OR $devanagari_word == "§end§") continue;
		if($devanagari_word == "<br />") {
			$devanagari_color .= $devanagari_word;
			if($roman_devanagari <> '') $roman_color .= $roman_word;
			continue;
			}
		if($j == $i_word OR ($i_word == -1 AND $devanagari_word == $word_stress)) {
			$devanagari_color .= "<span style=\"color:red;\">".$devanagari_word."</span> ";
			if($roman_devanagari <> '')$roman_color .= "<span style=\"color:red;\">".$roman_word."</span> ";
			}
		else {
			$devanagari_color .= $devanagari_word." ";
			if($roman_devanagari <> '') $roman_color .= $roman_word." ";
			}
		$j++;
		}
	}
echo $devanagari_color."<br />";
if($message_roman <> '') echo "<p style=\"color:red;\">".$message_roman."</p>";
else echo $roman_color;
echo "<br /><br />";

echo "<i>".$translation_english."</i><br /><br />";
$word_show = Mapping($devanagari,$word_ids,$i_word,FALSE);
echo "<p style=\"border-left:1em solid yellow; padding-left:6px;\">▷&nbsp;".str_replace("<br />","<br />▷&nbsp;",$word_show)."</p>";

echo "</blockquote>";

if(!is_mapper($login)) {
	echo "<p style=\"color:red; text-align:center;\">➡ Log in as a mapper to edit this mapping</p>";
	die();
	}

if($busy_in_work_set > 0) {
//	$other_user = set_user($busy_in_work_set);
	if($login <> $other_user AND !is_admin($login)) {
		echo "<p style=\"text-align:center;\"><span style=\"color:red;\">This song is currently in work set #".$busy_in_work_set.". Its mapping should be edited by ‘</span>".$other_user."<span style=\"color:red;\">’ or by an admin.</span></p>";
		die();
		}
	}

$text = Mapping($devanagari,$word_ids,-1,TRUE);
if(isset($_POST['approve_spelling'])) {	
	StoreSpelling(FALSE,'en',$text);
	}
$spelling_marks = spelling_marks('en',$text,"GoldenRod");
if(is_integer(strpos($spelling_marks,"<span"))) {
	echo "<p><b>Spelling problems:</b></p>";
	echo "<form name=\"delete_dict\" method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
	echo "<p>".$spelling_marks;
	echo "<br /><span style=\"color:GoldenRod;\">➡</span>&nbsp;";
//	echo "<input type=\"hidden\" name=\"table_name\" value=\"".$table_name."\">";
	echo "<input type=\"submit\" name=\"approve_spelling\" class=\"button\" value=\"ACCEPT ALL NEW WORDS\"></p>";
	echo "</form>";
	}
	
echo "<table>";
echo "<tr>";
echo "<th>Word</th><th></th><th></th><th>English mapping</th><th>Remarks</th><th colspan=\"2\" style=\"text-align:left;\">Default</th>";
echo "</tr>";
$rule = array();
for($i = $j = 0; $i < $nb_words_and_breaks; $i++) {
	echo "<form name=\"metadata\" method=\"post\" action=\"".$url_this_page."#word".$j."\" enctype=\"multipart/form-data\">";
	$devanagari_word = $devanagari_words[$i];
	if($roman_devanagari <> '') $roman_word = $roman_words[$i];
	$left_context = $left_context_roman = '';
	if($i > 0) {
		$left_context = trim($devanagari_words[$i-1]);
		if($roman_devanagari <> '') $left_context_roman = trim($roman_words[$i-1]);
		}
	if($left_context == "<br />" OR $left_context == '(' OR $left_context == ')' OR $left_context == ',') $left_context = $left_context_roman = '';
	$right_context = $right_context_roman = '';
	if($i < ($nb_words_and_breaks - 1)) {
		$right_context = trim($devanagari_words[$i+1]);
		if($roman_devanagari <> '') $right_context_roman = trim($roman_words[$i+1]);
		}
	if($right_context == "<br />" OR $right_context == '(' OR $right_context == ')' OR $right_context == ',') $right_context = $right_context_roman = '';
	if($devanagari_word == "<br />") {
		echo "<tr><td colspan=\"8\"></td></tr>";
		continue;
		}
	if($devanagari_word == "§beg§" OR $devanagari_word == "§end§") continue;
//	echo "<input type=\"hidden\" name=\"table_name\" value=\"".$table_name."\">";
	echo "<input type=\"hidden\" name=\"word_stress\" value=\"".$devanagari_word."\">";
	echo "<input type=\"hidden\" name=\"devanagari_word\" value=\"".$devanagari_word."\">";
	echo "<input type=\"hidden\" name=\"left_context\" value=\"".$left_context."\">";
	echo "<input type=\"hidden\" name=\"right_context\" value=\"".$right_context."\">";
	echo "<input type=\"hidden\" name=\"i_word\" value=\"".$j."\">";
	$get_word = GuessEnglishWord($word_ids,$j,$devanagari_word,$left_context,$right_context);
	$get_english = $get_word['english'];
	$get_id = $get_word['id'];
	$query_value = "SELECT * FROM ".BASE.".meaning WHERE devanagari LIKE \"".$devanagari_word."\" AND default_choice = \"1\"";
	$result_value = $bdd->query($query_value);
	if($result_value) {
		$n_value = $result_value->rowCount();
		$result_value->closeCursor();
		}
	else $n_value = 0;
	if($n_value == 0) $firstentry = TRUE;
	// We will take the first entry because none is tagged as default
	else $firstentry = FALSE;
	$query = "SELECT * FROM ".BASE.".meaning WHERE devanagari LIKE \"".$devanagari_word."\" OR simple_form LIKE \"".$roman_word."\"";
	$result = $bdd->query($query);
	$n = $result->rowCount();
	echo "<tr id=\"word".$j."\">";
	echo "<td class=\"tight\" rowspan=\"".($n + 1)."\" style=\"padding-left:4px; padding-right:4px; text-align:center;\">";
	$both_words = '';
	if($left_context <> '' AND $left_context <> "§beg§") $both_words .= "(".$left_context.") ";
	$both_words .= "<span style=\"font-size:large;\">".$devanagari_word."</span>";
	if($right_context <> '' AND $right_context <> "§end§") $both_words .= " (".$right_context.")";
	if($roman_devanagari <> '') {
		$both_words .= "<br />";
		if($left_context_roman <> '' AND $left_context_roman <> "§beg§") $both_words .= "(".$left_context_roman.") ";
		$both_words .= "<span style=\"font-size:large;\">".$roman_word."</span>";
		if($right_context_roman <> '' AND $right_context_roman <> "§end§") $both_words .= " (".$right_context_roman.")";
		}
	if($i_word == $j)
		echo "<span style=\"color:red;\">".$both_words."</span>";
	else echo $both_words;
	if(isset($word_choice[$j])) {
		$id_choice = $word_choice[$j];
		$query_choice = "SELECT * FROM ".BASE.".meaning WHERE id = \"".$id_choice."\"";
		$result_choice = $bdd->query($query_choice);
		if($result_choice) {
			$n_choice = $result_choice->rowCount();
			$result_choice->closeCursor();
			}
		else $n_choice = 0;
		if($n_choice == 0) $id_choice = 0;
		}
	else $id_choice = 0;
	if($test) echo "<br />id_choice = ".$id_choice;
	echo "</td>";
	echo "<td class=\"tight\" style=\"background-color:Cornsilk;\">";
	echo "<input type=\"radio\" name=\"choice\" value=\"0\"";
	if(isset($word_choice[$j]) AND $word_choice[$j] == 0) echo "checked";
	echo "><small><i>no&nbsp;select</i></small></td>";
	echo "<td class=\"tight\" colspan=\"1\" style=\"text-align:right; background-color:AquaMarine;\">";
	echo "<small>new&nbsp;➡</small>";
	echo "</td>";
	echo "<td class=\"tight\" style=\"background-color:Cornsilk; padding:4px;\">";
	echo "<input type=\"text\" style=\"\" name=\"english_word_new\" size=\"30\" value=\"\">";
	echo "</td>";
	echo "<td class=\"tight\" style=\"background-color:Lavender;\">";
	echo "<input type=\"text\" style=\"\" name=\"remark_new\" size=\"50\" value=\"\">";
	echo "</td>";
	echo "<td>";
	echo "</td>";
	echo "<td class=\"tight\" rowspan=\"".($n + 1)."\" style=\"background-color:Cornsilk;\">";
	echo "<input type=\"submit\" class=\"button\" value=\"SAVE\">";
	echo "</td>";
	echo "</tr>";
	if($n > 0) {
		$default_done = array();
		$conflicting_default = FALSE;
		while($ligne = $result->fetch()) {
			$english_word = $ligne['english'];
			$context = $ligne['context'];
			$left_context_other = $ligne['left_context'];
			$right_context_other = $ligne['right_context'];
			$id = $ligne['id'];
			$default = $ligne['default_choice'];
			$song_example = $ligne['song_example'];
			$yellow_box = FALSE;
			if($i_word == $j AND $get_id == $id) $yellow_box = TRUE;
			echo "<tr>";
			echo "<td class=\"tight\" style=\"background-color:MistyRose;\">";
			echo "<div class=\"tooltip\">";
			echo "<input type=\"checkbox\" name=\"delete\" value=\"".$id."\" /><small>delete&nbsp;</small>";
			echo "<span class=\"tooltiptext\">Danger!<br />Can't be undone…</span></div>";
			echo "</td>";
			echo "<td class=\"tight\" style=\"background-color:Cornsilk;\">";
			echo "<input type=\"hidden\" name=\"id\" value=\"".$id."\">";
			echo "<input type=\"radio\" name=\"choice\" value=\"".$id."\"";
			if(isset($word_choice[$j]) AND $word_choice[$j] == $id) echo "checked";
			echo "><small>select&nbsp;➡</small></td>";
			echo "<td class=\"tight\"";
			if($yellow_box)
				echo " style=\"background-color:Yellow;\">";
			else echo " style=\"background-color:Cornsilk;\">";
			echo "<input type=\"text\" style=\"\" name=\"english_word_".$id."\" size=\"30\" value=\"".$english_word."\">";
			echo "</td>";
			echo "<td class=\"tight\"  style=\"background-color:Lavender;\">";
			$morecontext = FALSE;
			if($song_example <> $this_song_id) {
				if($left_context_other <> '' AND $left_context_other <> ')' AND $left_context_other <> '(' AND $left_context_other <> "§beg§") {
					echo "(".$left_context_other.") ".$devanagari_word;
					$morecontext = TRUE;
					}
				if($right_context_other <> '' AND $right_context_other <> ')' AND $right_context_other <> '(' AND $right_context_other <> "§end§") {
					if(!$morecontext) echo $devanagari_word;
					echo " (".$right_context_other.")";
					$morecontext = TRUE;
					}
				if($morecontext AND $song_example > 0) {
					echo " ➡&nbsp;Example song #<a target=\"_blank\" title=\"Display mapping of this song\" href=\"words.php?song_id=".$song_example."&word_stress=".$devanagari_word."\">".$song_example."</a>";
					}
				echo "<br />";
				}
			else echo "In the context of this song<br />";
			echo "<input type=\"text\" style=\"\" name=\"context_".$id."\" size=\"50\" value=\"".$context."\">";
			echo " <a title=\"Show rule in full dictionary\" href=\"#r".$id."\">".$id."</a>";
			$rule[$id] = TRUE;
			if($default) {
				if(isset($default_done[$left_context_other][$right_context_other])) {
					$conflicting_default = TRUE;
					if($test)
						echo "<br />conflict ".$left_context_other." ".$right_context_other."<br />";
					}
				else {
					$default_done[$left_context_other][$right_context_other] = TRUE;
					if($test)
						echo "<br />storing (".$left_context_other.") (".$right_context_other.")<br />";
					}
				}
			echo "</td>";
			echo "<td class=\"tight\"";
			if($conflicting_default)
				echo " style=\"background-color:yellow !important;\"";
	echo "><small>";
			echo "<input type=\"radio\" name=\"default_".$id."\" value=\"1\"";
			if($default) echo "checked";
			echo " />yes<br />";
			echo "<input type=\"radio\" name=\"default_".$id."\" value=\"0\"";
			if(!$default) echo "checked";
			echo " />no";
			echo "</small></td>";
			if($conflicting_default) {
				echo "<td style=\"background-color:yellow;\">";
				echo "<p><b>&nbsp;<span style=\"color:red;\">➡ CONFLICTING DEFAULT!&nbsp;</span></b></p>";
				echo "</td>";
				$conflicting_default = FALSE;
				}
			echo "</tr>";
			}
		}
	$j++;
	echo "</form>";
	$result->closeCursor();
	}
echo "</table>";


echo "<h3>Rules used for this song</h3>";

echo "<table style=\"background-color:Cornsilk; border-collapse:none;\">";
echo "<tr><th>#</th><th colspan=\"2\">word in context</th><th>simple</th><th>mapped to</th><th></th><th>&nbsp;deft&nbsp;</th><th>&nbsp;song&nbsp;</th><th></th></tr>";

foreach($rule as $key => $value) {
	$id = $key;
	$query_dict = "SELECT * FROM ".BASE.".meaning WHERE id = \"".$id."\"";
	$result_dict = $bdd->query($query_dict);
	$ligne = $result_dict->fetch();
	echo "<tr>";
	$english_word = $ligne['english'];
	$devanagari_word = $ligne['devanagari'];
	$left_context = $ligne['left_context'];
	$right_context = $ligne['right_context'];
	$simple_form = $ligne['simple_form'];
	$song_example = $ligne['song_example'];
	$roman_devanagari_word = Transliterate($song_example,"<br />",$devanagari_word);
	$left_context_roman = Transliterate($song_example,"<br />",$left_context);
	$right_context_roman = Transliterate($song_example,"<br />",$right_context);
	// if($simple_form == '') $simple_form = update_simple_form($id,$roman_devanagari_word);
	$left_arg = '';
	if($left_context <> '') $left_arg .= "(".$left_context.") ";
	$left_arg .= "<span style=\"color:red;\">".$devanagari_word."</span>";
	if($right_context <> '') $left_arg .= " (".$right_context.")";
	$left_arg_roman = '';
	if($left_context_roman <> '') $left_arg_roman .= "(".$left_context_roman.") ";
	$left_arg_roman .= "<span style=\"color:red;\">".$roman_devanagari_word."</span>";
	if($right_context_roman <> '') $left_arg_roman .= " (".$right_context_roman.")";
	$devanagari_word = $ligne['devanagari'];
	$left_context = $ligne['left_context'];
	$right_context = $ligne['right_context'];
	$left_arg = '';
	if($left_context <> '') $left_arg .= "(".$left_context.") ";
	$left_arg .= "<span style=\"color:red;\">".$devanagari_word."</span>";
	if($right_context <> '') $left_arg .= " (".$right_context.")";
	$remark = $ligne['context'];
	$default = $ligne['default_choice'];
	echo "<td class=\"tight\">".$id."</td>";
	echo "<td class=\"tight\" style=\"background-color:white;\">".$left_arg."</td>";
	echo "<td class=\"tight\" style=\"background-color:white;\">".$left_arg_roman."</td>";
	echo "<td class=\"tight\" style=\"background-color:white;\">".$simple_form."</td>";
	echo "<td class=\"tight\" style=\"background-color:CornSilk;\">".$english_word."</td>";
	echo "<td class=\"tight\" style=\"background-color:Lavender;\">".$remark."</td>";
	echo "<td class=\"tight\">".$default."</td>";
	echo "<td class=\"tight\"";
	if($song_example == $this_song_id) {
		echo " style=\"background-color:yellow;\"";
		$example_link = $song_example;
		}
	else 
		$example_link = "<a target=\"_blank\" title=\"Display mapping of this song\" href=\"words.php?song_id=".$song_example."&word_stress=".$devanagari_word."\">".$song_example."</a>";
	echo ">".$example_link."</td>";
	echo "<td class=\"tight\">";
	if($song_example == $this_song_id) {
		echo "<div class=\"tooltip\">";
		echo "<form name=\"delete_dict\" method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
		echo "<input type=\"hidden\" name=\"delete\" value=\"".$id."\">";
	//	echo "<input type=\"hidden\" name=\"table_name\" value=\"".$table_name."\">";
		echo "<input type=\"submit\" class=\"button\" style=\"background-color:MistyRose;\" value=\"DELETE\">";
		echo "</form>";
		echo "<span class=\"tooltiptext\">Danger!<br />Can't be undone…</span></div>";
		}
	echo "</td>";
	echo "</tr>";
	$result_dict->closeCursor();
	}
echo "</table>";

if(!$show_dictionary)
	echo "<p><span style=\"color:red;\">➡</span> <a href=\"words.php?song_id=".$this_song_id."&show_dictionary=yes#dict\">Show full dictionary</a> (".$n_dict_entries." entries)</p>";
else {
	$query_dict = "SELECT * FROM ".BASE.".meaning ORDER BY devanagari";
	$result_dict = $bdd->query($query_dict);
	$n = $result_dict->rowCount();
	echo "<div id=\"dict\"></div>";
	echo "<h3>Full dictionary (".$n_dict_entries." entries)</h3>";
//	if($n > 1998) echo "Lack of space: only ".$n." entries are displayed…<br />";
	echo "<p><span style=\"color:red;\">➡ </span><a href=\"words.php?song_id=".$this_song_id."\">Hide dictionary</a></p>";
	echo "<table style=\"background-color:Cornsilk; border-collapse:none;\">";
	while($ligne = $result_dict->fetch()) {
		$id = $ligne['id'];
		echo "<tr id=\"r".$id."\">";
		$english_word = $ligne['english'];
		$devanagari_word = $ligne['devanagari'];
		$left_context = $ligne['left_context'];
		$right_context = $ligne['right_context'];
		$simple_form = $ligne['simple_form'];
		$song_example = $ligne['song_example'];
		$roman_devanagari_word = Transliterate($song_example,"<br />",$devanagari_word);
		$left_context_roman = Transliterate($song_example,"<br />",$left_context);
		$right_context_roman = Transliterate($song_example,"<br />",$right_context);
		// if($simple_form == '')
		// $simple_form = update_simple_form($id,$roman_devanagari_word);
		$left_arg = '';
		if($left_context <> '') $left_arg .= "(".$left_context.") ";
		$left_arg .= "<span style=\"color:red;\">".$devanagari_word."</span>";
		if($right_context <> '') $left_arg .= " (".$right_context.")";
		$left_arg_roman = '';
		if($left_context_roman <> '') $left_arg_roman .= "(".$left_context_roman.") ";
		$left_arg_roman .= "<span style=\"color:red;\">".$roman_devanagari_word."</span>";
		if($right_context_roman <> '') $left_arg_roman .= " (".$right_context_roman.")";
		
		$remark = $ligne['context'];
		$default = $ligne['default_choice'];
		echo "<td class=\"tight\">".$id."</td>";
		echo "<td class=\"tight\" style=\"background-color:white;\">".$left_arg."</td>";
		echo "<td class=\"tight\" style=\"background-color:white;\">".$left_arg_roman."</td>";
		echo "<td class=\"tight\" style=\"background-color:white;\">".$simple_form."</td>";
		echo "<td class=\"tight\" style=\"background-color:CornSilk;\">".$english_word."</td>";
		echo "<td class=\"tight\" style=\"background-color:Lavender;\">".$remark."</td>";
		echo "<td class=\"tight\">".$default."</td>";
		echo "<td class=\"tight\"";
		if($song_example == $this_song_id) {
			echo " style=\"background-color:yellow;\"";
			$example_link = $song_example;
			}
		else 
			$example_link = "<a target=\"_blank\" title=\"Display mapping of this song\" href=\"words.php?song_id=".$song_example."&word_stress=".$devanagari_word."\">".$song_example."</a>";
		echo ">".$example_link."</td>";
	
		echo "<td class=\"tight\">";
		echo "<div class=\"tooltip\">";
		echo "<form name=\"delete_dict\" method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
		echo "<input type=\"hidden\" name=\"delete\" value=\"".$id."\">";
	//	echo "<input type=\"hidden\" name=\"table_name\" value=\"".$table_name."\">";
		echo "<input type=\"submit\" class=\"button\" style=\"background-color:MistyRose;\" value=\"DELETE\">";
		echo "</form>";
		echo "<span class=\"tooltiptext\">Danger!<br />Can't be undone…</span></div>";
		echo "</td>";
		echo "</tr>";
		}
	$result_dict->closeCursor();
	echo "</table>";
	}
echo "</body>";
echo "</html>";

function update_simple_form($rule_id,$roman_devanagari_word) {
	global $bdd;
	$simple_form = simple_form($roman_devanagari_word);
	$query_change = "UPDATE ".BASE.".meaning SET simple_form = \"".$simple_form."\" WHERE id = \"".$rule_id."\"";
	$result_change = $bdd->query($query_change);
	$result_change->closeCursor();
	return $simple_form;
	}
?>