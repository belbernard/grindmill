<?php
ini_set('max_execution_time',600);
// session_start();
require_once("_base_urls.php");
require_once("_relier_edit.php");
require_once("_tasks.php");
require_once("_edit_tasks.php");

$only_definitions = FALSE;

if(isset($_GET['word'])) $word_detail = $_GET['word'];
else $word_detail = '';

if(isset($_GET['minutes'])) {
	$minutes = $_GET['minutes'];
	$word_detail = '';
	}
else $minutes = 0;

if($minutes == 0) {
	$query = "SELECT plural FROM ".BASE.".glossary WHERE word=\"".$word_detail."\" AND plural <> \"\"";
	$result = $bdd->query($query);
	$ligne = $result->fetch();
	$word_plural = $ligne['plural'];
	$result->closeCursor();
	$name = "“".$word_detail."”";
	$canonic_url = SITE_URL."glossary-detail.php?word=".$word_detail;
	}
else {
	$name = $minutes." minutes";
	$canonic_url = SITE_URL."glossary-detail.php";
	}
require_once("_header.php");

echo "<h2>Grindmill songs of Maharashtra — Glossary detail</h2>";

if(is_translator($login) AND isset($_POST['edit'])) {
	$edit_mode = TRUE;
	}
else $edit_mode = FALSE;

$message_flag = '';

if($minutes == 0 AND $word_detail == '') {
	die();
	}

if($minutes == 0) {
	echo "<h3 style=\"text-align:center;\"><span style=\"color:blue;\">".$word_detail."</span>";
	if($word_plural <> '') echo " (<span style=\"color:blue;\">".$word_plural."</span>)";
	echo "</h3>";
	}

if($minutes == 0) {
	if(is_translator($login) AND isset($_POST['save'])) {
		$flag = '';
		foreach($_POST as $key => $value) {
			if(is_integer(strpos($key,"old_definition_"))) {
				$word_id_edit = str_replace("old_definition_",'',$key);
				$definition = reshape_entry($_POST["definition_".$word_id_edit]);
				$old_definition = $_POST["old_definition_".$word_id_edit];
				if($definition <> $old_definition) {
					$flag = "id_".$word_id_edit;
					$query_update = "UPDATE ".BASE.".glossary SET login = \"".$login."\", definition = \"".$definition."\" WHERE id = \"".$word_id_edit."\"";
					$result_update = $bdd->query($query_update);
					$result_update->closeCursor();
					}
				}
			}
		$edit_mode = TRUE;
		if($flag <> '') $message_flag = "<span style=\"color:red;\">➡</span> <a href=#".$flag.">Return to ".$flag."</a></p>";
		}
		
	$delete_list = array();
	if(is_translator($login)) {
		foreach($_POST as $key => $value) {
			if(is_integer(strpos($key,"delete_"))) {
				$word_id_delete = str_replace("delete_",'',$key);
				if(isset($_POST["forgood_".$word_id_delete])) {
					echo "<span style=\"color:red;\">➡</span>&nbsp;Deleting #<b>".$word_id_delete."</b></span><br />";
					$query_delete = "DELETE FROM ".BASE.".glossary WHERE id = \"".$word_id_delete."\"  ";
					$result_delete = $bdd->query($query_delete);
					$result_delete->closeCursor();
				//	echo $query_delete."<br />";
					}
				else $delete_list[] = $word_id_delete;
				}
			}
		}
	
	$hide_edit_button = FALSE;
	$hilite = array();
	if(count($delete_list) > 0) {
		echo "<form method=\"post\" action=\"glossary-detail.php?word=".$word_detail."\" enctype=\"multipart/form-data\">";
		echo "<table><tr><td>";
		echo "<input type=\"submit\" class=\"button\" name=\"stop_delete\" value=\"DON'T DELETE\">";
		echo "</td>";
		echo "</tr>";
		for($i_delete_list = 0; $i_delete_list < count($delete_list); $i_delete_list++) {
			$word_id_delete = $delete_list[$i_delete_list];
			$hilite[$word_id_delete] = TRUE;
			$query = "SELECT * FROM ".BASE.".glossary WHERE id = \"".$word_id_delete."\"";
			$result = $bdd->query($query);
			$ligne = $result->fetch();
			$result->closeCursor();
		//	$id = $ligne['id'];
			$specific_song_id = $ligne['specific_song_id'];
			$specific_class_id = $ligne['specific_class_id'];
			$specific_group_id = $ligne['specific_group_id'];
			echo "<tr>";
			echo "<td class=\"tight\" style=\"font-size:100%;\">Delete&nbsp;entry&nbsp;#<b>".$word_id_delete."</b></span>?";
			echo "</td><td style=\"font-size:80%; background-color:Lavender;\">".$specific_song_id."</td>";
			echo "</td><td style=\"font-size:80%; background-color:Lavender;\">".$specific_group_id."</td>";
			echo "</td><td style=\"font-size:80%; background-color:Lavender;\">".$specific_class_id."</td>";
			echo "<td class=\"tight\" style=\"white-space:nowrap;\">";
			echo "<input type=\"hidden\" name=\"forgood_".$word_id_delete."\" value=\"yes\">";
			echo "<input type=\"checkbox\" name=\"delete_".$word_id_delete."\" value=\"yes\"";
			echo "><span style=\"color:red;\">➡</span>&nbsp;yes";
			echo "</td>";
			echo "</tr>";
			}
		echo "<tr>";
		echo "<td colspan=\"3\">";
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
	}
else {
	$edit_mode = FALSE;
	$hide_edit_button = TRUE;
	}

if($minutes == 0) {
	$query = "SELECT * FROM ".BASE.".glossary WHERE (word=\"".$word_detail."\"";
	if($word_plural <> '') $query .= " OR word=\"".$word_plural."\"";
	$query .= ")";
	if($only_definitions) $query .= " AND definition <> \"\"";
	$query .= " ORDER BY specific_song_id ASC, \"specific_class_id\" ASC, \"specific_group_id\" ASC";
	}
else {
	$start_time = time() - ($minutes * 60);
	$start_date = date('Y-m-d H:i:s',$start_time);
	$query = "SELECT * FROM ".BASE.".glossary WHERE login <> '' AND date > \"".$start_date."\" ORDER BY sort ASC, \"date\" ASC";
	}
$result = $bdd->query($query);

echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
echo "<table>";
if($edit_mode AND $message_flag <> '')
	echo "<tr><td colspan=\"5\">".$message_flag."</td></tr>";

if(is_translator($login)) {
	echo "<tr>";
	echo "<td></td>";
	echo "<th colspan=\"6\" style=\"padding:6px; text-align:left;\">";
	if($edit_mode AND !$hide_edit_button) echo "<input type=\"submit\" name=\"cancel\" class=\"button\" value=\"EXIT EDITOR (NO SAVE)\">";
	echo "<div style=\"float:right;\">";
	if(!$edit_mode AND !$hide_edit_button) echo "<input type=\"submit\" name=\"edit\" class=\"button\" value=\"EDIT DEFINITIONS\">";
	else if(!$hide_edit_button) echo "<input type=\"submit\" name=\"save\" class=\"button\" value=\"APPLY ALL CHANGES\">&nbsp;";
	echo "</div>";
	echo "</th>";
	echo "</tr>";
	}
echo "<tr>";
$dark_color = FALSE;
if($minutes == 0) {
	echo "<tr><th></th><th>Definition of word “<span style=\"color:blue;\">".$word_detail."</span>”</th><th></th><th>Assigned<br />to song</th><th>Assigned<br />to&nbsp;group(s)</th><th>Assigned<br />to class</th><th>Verbose</th>";
	echo "</tr>";
	}
else $current_word = '';
while($ligne = $result->fetch()) {
	$dark_color = !$dark_color;
	$word = $ligne['word'];
	if($minutes > 0 AND $word <> $current_word) {
	//	if($current_word <> '') {
			echo "</table>";
			echo "<table>";
			echo "<tr><th></th><th>Definition of word “<span style=\"color:blue;\">".$word."</span>”</th><th></th><th>Assigned<br />to song</th><th>Assigned<br />to&nbsp;group(s)</th><th>Assigned<br />to class</th><th>Verbose</th>";
echo "</tr>";
	//		}
		$current_word = $word;
		}
	$word_id = $ligne['id'];
	$flag = "id_".$word_id;
	$song_id = $ligne['song_id'];
	$user = $ligne['login'];
	$date = $ligne['date'];
	// $created = $ligne['created'];
	$definition = $ligne['definition'];
	$specific_song_id = $ligne['specific_song_id'];
	$specific_group_id = $ligne['specific_group_id'];
	$specific_class_id = $ligne['specific_class_id'];
	echo "<tr id=\"".$flag."\">";
	echo "<td style=\"vertical-align:middle; font-size:80%;";
	if(isset($hilite[$word_id])) echo " background-color:yellow;";
	echo "\">".$word_id."</td>";
	echo "<td style=\"vertical-align:middle; font-size:80%; padding:0px; padding-left:6px; padding-right:6px; background-color:";
	if($definition <> '') echo "Cornsilk";
	else echo "none";
	echo ";\">";
	if(!$edit_mode OR $definition == '') echo $definition;
	else {
		echo "<input type=\"hidden\" name = \"old_definition_".$word_id."\" value = \"".$definition."\" />";
		echo "<textarea name= \"definition_".$word_id."\" rows=\"3\" style=\"width:330px;\">";
		echo str_replace("<br />","\n",$definition);
		echo "</textarea>";
		}
	echo "</td>";
	echo "<td style=\"vertical-align:middle; font-size:80%; white-space:nowrap; padding:6px; background-color:";
	if($dark_color) echo "Lavender";
	else echo "GhostWhite";
	echo ";\">";
	if($song_id > 0) echo "➡&nbsp;".song($song_id,"Ex.");
	echo "</td>";
	echo "<td style=\"vertical-align:middle; font-size:80%; white-space:nowrap; padding:6px; background-color:";
	if($dark_color) echo "Lavender";
	else echo "GhostWhite";
	echo ";\">";
	if($specific_song_id > 0) echo song($specific_song_id,$specific_song_id);
	echo "</td>";
	echo "<td style=\"vertical-align:middle; font-size:80%; padding:6px; background-color:";
	if($dark_color) echo "Lavender";
	else echo "GhostWhite";
	echo ";\">";
	if($specific_group_id > 0) echo $specific_group_id;
	echo "</td>";
	echo "<td style=\"vertical-align:middle; font-size:80%; white-space:nowrap; padding:6px; background-color:";
	if($dark_color) echo "Lavender";
	else echo "GhostWhite";
	echo ";\">";
	if($specific_class_id <> '') echo $specific_class_id;
	$semantic_class_name = semantic_class_name($specific_class_id);
	if($semantic_class_name <> '') echo "<br/>".$semantic_class_name;
	echo "</td>";
	echo "<td style=\"vertical-align:middle; font-size:80%; white-space:nowrap; padding:6px; background-color:Cornsilk;\">";
	if($specific_class_id <> '') {
		if($specific_song_id > 0) echo "Class definition ".$specific_class_id." is used by ".song($specific_song_id,"song #".$specific_song_id);
		else echo "Definition for class ".$specific_class_id;
		}
	else if($specific_group_id > 0) {
		$group_label = group_link("label",$specific_group_id,FALSE,FALSE,FALSE,FALSE);
		if($specific_song_id > 0) echo "Group definition ".$specific_group_id." (".$group_label.") is used by ".song($specific_song_id,"song #".$specific_song_id);
		else echo "Definition for group ".$specific_group_id." (".$group_label.")";
		}
	else if($specific_song_id > 0) {
		if($definition == '') echo "Generic definition is used by ".song($specific_song_id,"song #".$specific_song_id);
		else echo "Specific definition is used by ".song($specific_song_id,"song #".$specific_song_id);
		}
	else echo "Generic definition";
	// if($created) echo " <span style=\"color:red;\">created</span>";
	echo "</td>";
	if(is_translator($login)) {
		echo "<td style=\"vertical-align:middle; padding:0px; font-size:80%; white-space:nowrap;\">";
		if(!$hide_edit_button) {
			echo "<div class=\"tooltip\">";
			if($edit_mode)
				echo "<input type=\"checkbox\" name=\"delete_".$word_id."\" value=\"ok\" > Delete this entry<br />";
			else echo "<input type=\"submit\" name=\"delete_".$word_id."\" style=\"background-color:yellow;\" class=\"button\" value=\"DELETE THIS ENTRY\">";
			echo "<span class=\"tooltiptext\">Danger!<br />Can't be undone…</span></div><br />";
			}
		if($user <> '') echo "<small>&nbsp;".$user."&nbsp;".$date."</small>";
		echo "</td>";
		}
	
	echo "</tr>";
	}
if($edit_mode AND !$hide_edit_button) {
	echo "<tr>";
	echo "<td colspan=\"7\" >";
	echo "<div style=\"float:right;\">";
	echo "<input type=\"submit\" name=\"save\" class=\"button\" value=\"APPLY ALL CHANGES\">";
	echo "</div>";
	echo "</td>";
	echo "</tr>";
	}
$result->closeCursor();
echo "</table>";
echo "</form>";
echo "</body>";
echo "</html>";
?>