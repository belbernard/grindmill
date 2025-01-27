<?php
// ini_set('max_execution_time',600);
// session_start();
require_once("_base_urls.php");
require_once("_relier_edit.php");
require_once("_tasks.php");
require_once("_edit_tasks.php");

$name = "Glossary";
$canonic_url = SITE_URL."glossary.php";

$_SESSION['fixed_typo_current_workset'] = FALSE;

require_once("_header.php");

echo "<h2>Grindmill songs of Maharashtra — Glossary</h2>";

echo "<br />&nbsp;<br /><blockquote>";

$clean_glossary = FALSE;
if($clean_glossary) {
	$query_delete = "DELETE FROM ".BASE.".glossary WHERE word LIKE \"%’s\"";
	$result_delete = $bdd->query($query_delete);
	$result_delete->closeCursor();
	}

$edit_mode = FALSE;
$message_flag = '';
$date = date('Y-m-d H:i:s');

$letter_range = '';
if(isset($_POST['letter_range'])) {
	$letter_range = $_POST['letter_range'];
	}
	
if(isset($_POST['change_letter_range'])) {
	$letter_range = $_POST['new_letter_range'];
	}

if(is_translator($login) AND isset($_POST['edit_table'])) {
	$edit_mode = TRUE;
	}

if(is_translator($login) AND isset($_POST['new'])) {
	$word = str_replace(' ','_',trim(simple_form($_POST['new_word'])));
	if($word <> '') {
		$plural = str_replace(' ','_',trim(simple_form($_POST['new_plural'])));
		$definition = reshape_entry($_POST['new_definition']);
		if(isset($_POST['new_force_case'])) $force_case = 1;
		else $force_case = 0;
	//	echo $force_case." ".$word." ".$definition."<br />";
		$sort = ucfirst($word);
		$letter_range_this_word = $sort[0];
		$query_insert = "INSERT INTO ".BASE.".glossary (word, sort, plural, letter_range, definition, force_case, login, date) VALUES (\"".$word."\", \"".$word."\", \"".$plural."\", \"".$letter_range_this_word."\", \"".$definition."\", \"".$force_case."\", \"".$login."\", \"".$date."\")";
		$result_insert = $bdd->query($query_insert);
	//	if($test) echo $query_insert." (11)<br />";
		if($result_insert) $result_insert->closeCursor();
		$n_rules = update_tag_rules();
	//	echo "<p>".$n_rules." rules stored</p>";
		$query_word = "SELECT * FROM ".BASE.".glossary WHERE word = \"".$word."\"";
		$result_word = $bdd->query($query_word);
		$ligne_word = $result_word->fetch();
		$result_word->closeCursor();
		$id_word = $ligne_word['id'];
		echo "<ul>";
		$message = find_example($id_word,$word);
		if($message == '')
			echo "<li><span style=\"color:blue;\"><b>".$word."</b></span> <span style=\"color:red;\">not found</span> in any song translation.</li>";
		else echo $message;
		echo "</ul>";
		for($i = 0; $i < 26; $i += 3) {
			if($alphabet[$i+1] == 'Z') {
				$letter_range = $alphabet[$i]."to".$alphabet[$i+1];
				break;
				}
			else if($alphabet[$i+3] > $letter_range_this_word) {
				$letter_range = $alphabet[$i]."to".$alphabet[$i+2];
				break;
				}
			}
		}
	$edit_mode = TRUE;
	}

if(is_translator($login) AND isset($_POST['find_examples'])) {
	$query = "SELECT id, word FROM ".BASE.".glossary WHERE specific_song_id = \"0\" AND specific_group_id = \"0\" AND specific_class_id = \"\" ORDER BY sort";
	$result = $bdd->query($query);
	echo "<ul>";
	while($ligne = $result->fetch()) {
		$word_id_example = $ligne['id'];
		$word = $ligne['word'];
	//	echo "Searching an example for ‘".$word."’<br />";
		$message = find_example($word_id_example,$word);
		if($message == '')
			echo "<li><span style=\"color:blue;\"><b>".$word."</b></span> <span style=\"color:red;\">not found</span> in any song translation.</li>";
		else echo $message;
		}
	echo "</ul>";
	$edit_mode = TRUE;
	}
	
if(is_translator($login) AND isset($_POST['save'])) {
	$flag = $word_flag = '';
	foreach($_POST as $key => $value) {
		if(is_integer(strpos($key,"word_"))) {
			$word_id_edit = str_replace("word_",'',$key);
			$word = reshape_entry($_POST["word_".$word_id_edit]);
			$plural = str_replace(' ','_',trim(simple_form(reshape_entry($_POST["plural_".$word_id_edit]))));
			if(isset($_POST["force_case_".$word_id_edit])) $force_case = 1;
			else $force_case = 0;
			$definition = reshape_entry($_POST["definition_".$word_id_edit]);
			$old_definition = $_POST["old_definition_".$word_id_edit];
			$old_plural = $_POST["old_plural_".$word_id_edit];
			$old_force_case = $_POST["old_force_case_".$word_id_edit];
			if($definition <> $old_definition) {
				$flag = "word_".$word_id_edit;
				$word_flag = str_replace('_',' ',$word);
			//	echo $word." ".$definition."<br />";
				$query_update = "UPDATE ".BASE.".glossary SET login = \"".$login."\", definition = \"".$definition."\" WHERE id = \"".$word_id_edit."\" AND specific_song_id = \"0\" AND specific_group_id = \"0\" AND specific_class_id = \"\"";
				$result_update = $bdd->query($query_update);
				$result_update->closeCursor();
				}
			if($plural <> $old_plural) {
				$flag = "word_".$word_id_edit;
				$word_flag = str_replace('_',' ',$word);
			//	echo $word." ".$plural."<br />";
				$query_update = "UPDATE ".BASE.".glossary SET login = \"".$login."\", plural = \"".$plural."\" WHERE id = \"".$word_id_edit."\" AND specific_song_id = \"0\" AND specific_group_id = \"0\" AND specific_class_id = \"\"";
				$result_update = $bdd->query($query_update);
				$result_update->closeCursor();
				}
			if($force_case <> $old_force_case) {
				$flag = "word_".$word_id_edit;
				$word_flag = str_replace('_',' ',$word);
		//		echo $word." ".$force_case."<br />";
				$query_update = "UPDATE ".BASE.".glossary SET login = \"".$login."\", force_case = \"".$force_case."\" WHERE id = \"".$word_id_edit."\" AND specific_song_id = \"0\" AND specific_group_id = \"0\" AND specific_class_id = \"\"";
				$result_update = $bdd->query($query_update);
				$result_update->closeCursor();
				}
			}
		}
	$n_rules = update_tag_rules();
//	echo "<p>".$n_rules." rules stored</p>";
	$edit_mode = TRUE;
	if($word_flag <> '') $message_flag = "<span style=\"color:red;\">➡</span> <a href=#".$flag.">Return to word ‘".$word_flag."’</a></p>";
	}

$delete_list = array();
if(is_translator($login)) {
	$need_update = FALSE;
	foreach($_POST as $key => $value) {
		if(is_integer(strpos($key,"change_case_"))) {
			$word_id_case = str_replace("change_case_",'',$key);
			$word = $_POST["word_".$word_id_case];
			$flag = "word_".$word_id_case;
			if(ucwords($word) == $word)
				$word = strtolower($word);
			else $word = str_replace(' ','_',ucwords(str_replace('_',' ',$word)));
			$message_flag = "<span style=\"color:red;\">➡</span> <a href=#".$flag.">Jump back to word ‘".str_replace('_',' ',$word)."’</a></p>";
			$query_update = "UPDATE ".BASE.".glossary SET login = \"".$login."\", word = \"".$word."\", sort = \"".$word."\", song_id = \"0\" WHERE id = \"".$word_id_case."\" AND specific_song_id = \"0\" AND specific_group_id = \"0\" AND specific_class_id = \"\"";
			$result_update = $bdd->query($query_update);
			$result_update->closeCursor();
			$need_update = TRUE;
			$edit_mode = TRUE;
			}
		if(is_integer(strpos($key,"find_example_"))) {
			$word_id_example = str_replace("find_example_",'',$key);
			$word = $_POST["word_".$word_id_example];
			$flag = "word_".$word_id_example;
			$message_flag = "<ul>";
			$message = find_example($word_id_example,$word);
			if($message == '') $message_flag .= "<li><span style=\"color:blue;\"><b>".$word."</b></span> <span style=\"color:red;\">not found</span> in any song translation.</li>";
			else $message_flag .= $message;
			$message_flag .= "</ul><br /><span style=\"color:red;\">➡</span> <a href=#".$flag.">Jump back to word ‘".str_replace('_',' ',$word)."’</a></p>";
			$edit_mode = TRUE;
			break;
			}
		if(is_integer(strpos($key,"delete_"))) {
			$word_id_delete = str_replace("delete_",'',$key);
			if(isset($_POST["forgood_".$word_id_delete])) {
				$word = $_POST["word_".$word_id_delete];
				echo "<span style=\"color:red;\">➡</span>&nbsp;Deleting <span style=\"color:blue;\"><b>".$word."</b></span><br />";
				$query_delete = "DELETE FROM ".BASE.".glossary WHERE id = \"".$word_id_delete."\"";
				$result_delete = $bdd->query($query_delete);
			//	echo $query_delete."<br />";
				$result_delete->closeCursor();
				$need_update = TRUE;
				$edit_mode = TRUE;
				}
			else $delete_list[] = $word_id_delete;
			}
		}
	if($need_update) update_tag_rules();
	}

$hide_edit_button = FALSE;
if(count($delete_list) > 0) {
	echo "<form method=\"post\" action=\"glossary.php\" enctype=\"multipart/form-data\">";
	echo "<input type=\"hidden\" name = \"letter_range\" value = \"".$letter_range."\" />";
	echo "<table><tr><td>";
	echo "<input type=\"submit\" class=\"button\" name=\"stop_delete\" value=\"DON'T DELETE\">";
	echo "</td>";
	echo "</tr>";
	for($i_delete_list = 0; $i_delete_list < count($delete_list); $i_delete_list++) {
		$word_id_delete = $delete_list[$i_delete_list];
		$query = "SELECT * FROM ".BASE.".glossary WHERE id = \"".$word_id_delete."\" AND specific_song_id = \"0\" AND specific_group_id = \"0\" AND specific_class_id = \"\"";
		$result = $bdd->query($query);
		$ligne = $result->fetch();
		$word = $ligne['word'];
		$definition = $ligne['definition'];
		$song_id = $ligne['song_id'];
		$condition = "word = \"".$word."\""; // OR plural = \"".$word."\"";
		echo "<tr>";
		echo "<td class=\"tight\" style=\"font-size:100%;\">Delete&nbsp;entry&nbsp;for&nbsp;<span style=\"color:blue;\"><b>".$word."</b></span>?";
		echo "</td><td style=\"font-size:80%; background-color:Lavender;\"><i>".$definition."</i></td>";
				echo "<td>";
				if($song_id > 0) {
					echo "&nbsp;<small>➡&nbsp;<a href=\"songs.php?song_id=".$song_id."\" target=\"_blank\">";
					echo "example song";
					echo "</a></small>";
					}
				echo "</td>";
		echo "<td class=\"tight\" style=\"white-space:nowrap;\">";
		echo "<input type=\"hidden\" name=\"word_".$word_id_delete."\" value=\"".$word."\">";
		echo "<input type=\"hidden\" name=\"forgood_".$word_id_delete."\" value=\"yes\">";
		echo "<input type=\"checkbox\" name=\"delete_".$word_id_delete."\" value=\"yes\"";
		if(isset($_POST["duplicate_".$word_id_delete])) echo " checked";
		echo "><span style=\"color:red;\">➡</span>&nbsp;yes";
		echo "</td>";
		echo "</tr>";
		$query_others = "SELECT * FROM ".BASE.".glossary WHERE (".$condition.") AND id <> \"".$word_id_delete."\"";
		$result_others = $bdd->query($query_others);
		if($result_others) {
			while($ligne_others = $result_others->fetch()) {
				$word_other = $ligne_others['word'];
				$definition_others = $ligne_others['definition'];
				$song_id_others = $ligne_others['song_id'];
				$id_others = $ligne_others['id'];
				echo "<input type=\"hidden\" name=\"forgood_".$id_others."\" value=\"yes\">";
				echo "<tr>";
				echo "<td></td>";
				echo "<td style=\"font-size:80%; background-color:Lavender;\"><i>".$definition_others."</i></td>";
				echo "<td>";
				if($song_id_others > 0) {
					echo "&nbsp;<small>➡&nbsp;<a href=\"songs.php?song_id=".$song_id_others."\" target=\"_blank\">";
					echo "example song";
					echo "</a></small>";
					}
				echo "</td>";
				echo "<td class=\"tight\" style=\"white-space:nowrap;\">";
				
				echo "<input type=\"checkbox\" name=\"delete_".$id_others."\" value=\"yes\">";
		echo "<input type=\"hidden\" name=\"word_".$id_others."\" value=\"".$word."\">";
				echo "<span style=\"color:red;\">➡</span>&nbsp;yes&nbsp;(be careful!)";
				echo "</td>";
				echo "</tr>";
				}
			$result_others->closeCursor();
			}
		}
	echo "<tr>";
	echo "<td colspan=\"4\">";
	echo "<div style=\"float:right;\">";
	echo "<input type=\"submit\" class=\"button\" name=\"delete\" value=\"DELETE CHECKED\">";
	echo "</div>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";
	echo "</form>";
	$edit_mode = FALSE;
	$hide_edit_button = TRUE;
	}

if($edit_mode) {
	echo "<p><span style=\"color:red;\">➡</span> <span style=\"color:blue;\">Tip: you can edit several words and click ‘SAVE ALL ENTRIES’ only once…</span></p>";
	}
echo "</blockquote>";
	
$n_words = 0;
$done = array();
	
echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
if($edit_mode) echo "<input type=\"hidden\" name = \"edit\" value = \"".$edit_mode."\" />";
echo "<table><tr>";
echo "<td>Select range of display:</td>";
echo "<td>";
echo "<select name=\"letter_range\">";
echo "<option value=all";
if($letter_range == "all") echo " selected";
echo ">all</option>";
echo "<option value=AtoC";
if($letter_range == "AtoC") echo " selected";
echo ">A —> C</option>";
echo "<option value=DtoF";
if($letter_range == "DtoF") echo " selected";
echo ">D —> F</option>";
echo "<option value=GtoI";
if($letter_range == "GtoI") echo " selected";
echo ">G —> I</option>";
echo "<option value=JtoL";
if($letter_range == "JtoL") echo " selected";
echo ">J —> L</option>";
echo "<option value=MtoO";
if($letter_range == "MtoO") echo " selected";
echo ">M —> O</option>";
echo "<option value=PtoR";
if($letter_range == "PtoR") echo " selected";
echo ">P —> R</option>";
echo "<option value=StoU";
if($letter_range == "StoU") echo " selected";
echo ">S —> U</option>";
echo "<option value=VtoX";
if($letter_range == "VtoX") echo " selected";
echo ">V —> X</option>";
echo "<option value=YtoZ";
if($letter_range == "YtoZ") echo " selected";
echo ">Y —> Z</option>";
echo "</select>";
echo "</td>";
echo "<td>";
echo "<input type=\"submit\" name=\"select_range\" class=\"button\" value=\"<- select and click me!\">";
echo "</td>";
echo "</tr></table>";
echo "</form>";

if($letter_range == "all") {
	$letter_min = 'A';
	$letter_max = 'Z';
	}
else if($letter_range <> '') {
	$table = explode('to',$letter_range);
	$letter_min = $table[0];
	$letter_max = $table[1];
	}
else {
	echo "<font color=red><blockquote>➡</font> Select a range and click button ‘select’…</blockquote>";
	die();
	}

if(is_translator($login) AND $letter_range == "all") $export_file = fopen(EXPORT."missing-definitions.txt",'w');
else $export_file = FALSE;
$n_missing = 0;

if(is_translator($login)) echo "<font color=red>➡</font>&nbsp;<a href=\"https://s3-us-west-2.amazonaws.com/mpthreebackupfolder/000717/Documentation/EditingGlossary.pdf\" target=\"_blank\">Download PDF tutorial…</a>";
	
echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
echo "<input type=\"hidden\" name = \"letter_range\" value = \"".$letter_range."\" />";
echo "<table>";
if($edit_mode AND $message_flag <> '')
	echo "<tr><td colspan=\"5\">".$message_flag."</td></tr>";
echo "<tr>";
echo "<th style=\"padding:6px; text-align:left;\" colspan=\"";
if($edit_mode) echo "2";
else echo "3";
echo "\" style=\"padding-left:6px; text-align:left;\"><b>⇣</b> Force upper/lower case as written";
echo "<div class=\"tooltip\">";
echo "<i>&nbsp;<font color=red>➡</font>&nbsp;explain!</i>";
echo "<span class=\"tooltiptext\">Examples:<br />— “Abir” shall be forced to “abir”<br />— “bhaubij” shall be forced to “Bhaubij”</span></div><br />";
echo "</div>";
if(is_translator($login)) {
	echo "</th>";
	echo "<th colspan=\"3\" style=\"padding:6px; text-align:left;\">";
	if($edit_mode) echo "<input type=\"submit\" name=\"cancel\" class=\"button\" value=\"EXIT EDITOR (NO SAVE)\">";
	echo "<div style=\"float:right;\">";
	if(!$edit_mode AND !$hide_edit_button) echo "<input type=\"submit\" name=\"edit_table\" class=\"button\" value=\"EDIT THIS TABLE, CHANGE CASE ETC.\">";
	else if(!$hide_edit_button) echo "<input type=\"submit\" name=\"save\" class=\"button\" value=\"APPLY ALL CHANGES\">&nbsp;";
	echo "</div>";
	}
echo "</th>";
echo "</tr>";
if($edit_mode) {
	echo "<tr>";
	echo "<td style=\"padding:6px; background-color:yellow; vertical-align:middle;\">";
	echo "<input type=\"checkbox\" name=\"new_force_case\" value=\"ok\" />";
	echo "</td>";
	echo "<td style=\"vertical-align:middle; padding:6px; background-color:yellow; white-space:nowrap;\">";
	echo "<span style=\"color:red;\">singular word</span><br />";
	echo "<input type=\"text\" name=\"new_word\" size=\"20\" value=\"\" />";
	echo "</td>";
	echo "<td style=\"vertical-align:middle; padding:6px; background-color:yellow; white-space:nowrap;\">";
	echo "<span style=\"color:red;\">plural word</span><br />";
	echo "<input type=\"text\" name=\"new_plural\" size=\"20\" value=\"\" />";
	echo "</td>";
	echo "<td colspan=\"2\" style=\"vertical-align:middle; padding:6px; background-color:yellow; white-space:nowrap;\">";
	echo "<span style=\"color:red;\">generic definition</span><br />";
	echo "<textarea name= \"new_definition\" rows=\"3\" style=\"width:330px;\">";
	echo "</textarea>";
	echo "<td colspan=\"2\" style=\"vertical-align:middle; text-align:center; padding:0px; background-color:yellow;\">";
	echo "<input type=\"submit\" name=\"new\" class=\"button\" value=\"<••• NEW ENTRY\">";
	echo "</td>";
	echo "</tr>";
	}
else {
	echo "<tr>";
	echo "<td></td><td>Word</td>";
	echo "<td style=\"white-space:nowrap;\">";
	echo "Generic definition";
	echo "</td>";
	echo "</tr>";
	}
$query = "SELECT * FROM ".BASE.".glossary WHERE specific_song_id = \"0\" AND specific_group_id = \"0\" AND specific_class_id = \"\" AND letter_range >= \"".$letter_min."\" AND letter_range <= \"".$letter_max."\" ORDER BY sort, \"id\", \"date\"";
$result = $bdd->query($query);
$n = $result->rowCount();
// echo "n = ".$n."<br />";
$dark_color = FALSE;
$j_line = 0;
while($ligne = $result->fetch()) {
	if(!isset($ligne['word'])) continue;
	$word = reshape_entry($ligne['word']);
	// echo $ligne['word']."<br />";
	$plural = $ligne['plural'];
	$definition = $ligne['definition'];
	// echo "word = ".$word."<br />";
	if($export_file AND trim($definition) == '') {
		fprintf($export_file,"%s\r\n",$word);
		$n_missing++;
		}
	$word_id = $ligne['id'];
	$user = $ligne['login'];
	$date = $ligne['date'];
	$song_id = $ligne['song_id'];
	$force_case = $ligne['force_case'];
	$flag = "word_".$word_id;
	$n_words++;
	$j_line++;
	$dark_color = !$dark_color;
	$duplicate = FALSE;
	if(!isset($done[$word])) $done[$word] = TRUE;
	else $duplicate = TRUE;
	$more_meanings_link = more_meanings("link",$word_id,$word,$plural);
	echo "<input type=\"hidden\" name = \"old_force_case_".$word_id."\" value = \"".$force_case."\" />";
	echo "<input type=\"hidden\" name = \"old_plural_".$word_id."\" value = \"".$plural."\" />";
	echo "<tr id=\"".$flag."\">";
	echo "<td style=\"padding:6px; padding-top:9px; vertical-align:middle; background-color:ghostwhite;\">";
	echo "<input type=\"checkbox\" name=\"force_case_".$word_id."\" value=\"ok\"";
	if($force_case) echo " checked";
	if(!$edit_mode) echo " disabled";
	echo " />";
	echo "</td>";
	echo "<td style=\"vertical-align:middle; text-align:left; padding:0px; padding-left:6px; padding-right:6px;";
	if($duplicate AND is_translator($login)) echo " background-color:yellow;\">";
	else {
		if($edit_mode) echo " background-color:bisque;\">";
		else {
			echo " background-color:";
			if($dark_color) echo "Cyan";
			else echo "LightCyan";
			echo "; border-radius:10%;\">";
			}
		}
	if($duplicate)
		echo "<span style=\"text-decoration:line-through;\">".trim(str_replace('_',' ',$word))."</span>&nbsp;<small><span style=\"color:red;\">duplicate!</span></small>";
	else {
		echo "<b><span style=\"color:blue;\">".trim(str_replace('_',' ',$word))."</span></b>";
		if(lcfirst($word) == $word) $low = TRUE;
		else $low = FALSE;
		if($edit_mode) {
			echo "<br /><small><input type=\"checkbox\" name=\"change_case_".$word_id."\" value=\"ok\" >➡&nbsp;<i>change word to ";
			if($low) echo "uppercase";
			else echo "lowercase";
			echo "</i></small>";
			}
		}
	if($edit_mode) {
		echo "</td>";
		echo "<td colspan=\"2\" style=\"background-color:bisque; vertical-align:middle; padding:0px; padding-left:6px; padding-right:6px; white-space:nowrap;\">";
		echo "<small><span style=\"color:red;\">plural word</span></small><br />";
		echo "<input type=\"text\" name=\"plural_".$word_id."\" size=\"20\" value=\"".trim(str_replace('_',' ',$plural))."\">";
		}
	else if($plural <> '') echo "<small>&nbsp;(".trim(str_replace('_',' ',$plural)).")</small>";
	if($song_id > 0) {
	//	echo "<div style=\"float:right;\">";
		echo "&nbsp;<small>➡&nbsp;<a href=\"songs.php?song_id=".$song_id."\" target=\"_blank\">";
		if(!$edit_mode) echo "<i>example</i>";
		else echo "example song";
		echo "</a></small>";
	//	echo "</div>";
		}
	else if($edit_mode AND !$duplicate) {
	//	echo "<div style=\"float:right;\">";
		echo "&nbsp;<small>➡&nbsp;<input type=\"submit\" name=\"find_example_".$word_id."\" class=\"button\" style=\"background-color:yellow;\" value=\"find example\">";
	//	echo "</div>";
		}
	echo "</td>";
	echo "<td style=\"vertical-align:middle; font-size:80%; padding:0px; padding-left:6px; padding-right:6px; background-color:";
	if($dark_color) echo "Lavender";
	else echo "GhostWhite";
	echo ";\">";
	echo "<input type=\"hidden\" name = \"word_".$word_id."\" value = \"".$word."\" />";
	if($duplicate) echo "<input type=\"hidden\" name = \"duplicate_".$word_id."\" value = \"yes\" />";
	if(!$edit_mode) echo $definition;
	else {
		echo "<input type=\"hidden\" name = \"old_definition_".$word_id."\" value = \"".$definition."\" />";
		echo "<textarea name= \"definition_".$word_id."\" rows=\"3\" style=\"width:330px;\">";
		echo str_replace("<br />","\n",$definition);
		echo "</textarea>";
		}
	echo "</td>";
	echo "<td style=\"vertical-align:middle; font-size:80%; text-align:left; background-color:";
	if($dark_color) echo "Lavender";
	else echo "GhostWhite";
	echo ";\">";
	if($more_meanings_link <> '') echo "➡&nbsp;".$more_meanings_link;
	echo "</td>";
	if(is_translator($login)) {
		echo "<td style=\"vertical-align:middle; padding:0px; font-size:80%; white-space:nowrap;";
		if($duplicate AND $edit_mode) echo " background-color:yellow;";
		else {
			if($dark_color) echo " background-color:Lavender;";
			else echo " background-color:GhostWhite;";
			}
		echo "\">";
		if(!$duplicate) echo "<div class=\"tooltip\">";
		if($edit_mode) {
			echo "<input type=\"checkbox\" name=\"delete_".$word_id."\" value=\"ok\" > Delete this entry<br />";
			}
		else if($duplicate)
			echo "<input type=\"submit\" name=\"delete_".$word_id."\" style=\"background-color:yellow;\" class=\"button\" value=\"DELETE THIS ENTRY\"><br />";
		if(!$duplicate) echo "<span class=\"tooltiptext\">Danger!<br />Can't be undone…</span></div><br />";
		if($user <> '') echo "<small>&nbsp;".$user."&nbsp;".$date."</small>";
		echo "</td>";
		}
	echo "</tr>";
	if($edit_mode AND $j_line > 10) {
		$j_line = 0;
		echo "<tr>";
		echo "<th style=\"padding:6px; text-align:left;\" colspan=\"2\" style=\"padding-left:6px; text-align:left;\"><b>⇣</b> Force upper/lower case (as written)";
		echo "</th>";
		echo "<td colspan=\"3\" style=\"padding:6px; text-align:left;\">";
		echo "<div style=\"float:right;\">";
		echo "<input type=\"submit\" name=\"save\" class=\"button\" value=\"APPLY ALL CHANGES\">&nbsp;";
		echo "</div>";
		echo "</td>";
		echo "</tr>";
		}
	}
echo "<tr>";
if(!$edit_mode) {
	if($letter_range <> "all" AND $letter_range <> '' AND $letter_range <> "YtoZ") {
		for($i = 0; $i < 26; $i += 3) {
			if($alphabet[$i+3] > $letter_range) {
				if($alphabet[$i+4] == 'Z')
					$new_letter_range = $alphabet[$i+3]."to".$alphabet[$i+4];
				else $new_letter_range = $alphabet[$i+3]."to".$alphabet[$i+5];
				break;
				}
			}
		echo "<td colspan=\"3\">";
		echo "<input type=\"hidden\" name = \"new_letter_range\" value = \"".$new_letter_range."\" />";
		echo "............&nbsp;<font color=red>➡</font>&nbsp;<input type=\"submit\" name=\"change_letter_range\" class=\"button\" value=\"NEXT ENTRIES, CLICK ME!\">";
		echo "</td>";
		}
	else if(is_admin($login) AND $letter_range == '') {
		echo "<td>";
		echo "</td>";
		echo "<td colspan=\"3\">";
		echo "<input type=\"submit\" name=\"find_examples\" class=\"button\" value=\"CHECK AGAIN FINDING ALL EXAMPLES (slow!)\">";
		echo "</td>";
		}
	}
else {
	echo "<td>";
	echo "</td>";
	echo "<td>";
	echo "</td>";
	echo "<td colspan=\"3\" >";
	echo "<div style=\"float:right;\">";
	echo "<input type=\"submit\" name=\"save\" class=\"button\" value=\"APPLY ALL CHANGES\">";
	echo "</div>";
	echo "</td>";
	}
echo "</tr>";
$result->closeCursor();
echo "</table>";
echo "</form>";

if($export_file) {
	echo "<blockquote>This glossary contains ".$n_words." words<br />";
	fclose($export_file);
	$url = EXPORT."missing-definitions.txt";
	if($n_missing > 0) echo "➡ <a href=\"".$url."\" target=\"_blank\">Download ".$n_missing." entries with empty definitions</a>";
	echo "</blockquote>";
	}

echo "</body>";
echo "</html>";

function clean_glossary_entry($word) {
	$word = trim($word);
	$word = str_replace('*','',$word);
	$word = str_replace("’s",'',$word);
	$word = str_replace(' ','_',$word);
	return $word;
	}
?>