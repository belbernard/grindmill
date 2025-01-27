<?php
ini_set('max_execution_time',600);
// session_start();
require_once("_base_urls.php");
require_once("_relier_edit.php");
require_once("_tasks.php");

$name = "Groups";
$canonic_url = '';

require_once("_header.php");

echo "<h2>Grindmill songs of Maharashtra — Groups</h2>";

if(is_editor($login) AND isset($_GET['edit'])) {
	$edit_id = $_GET['edit'];
	}
else $edit_id = 0;

if(isset($_POST['delete_group'])) {
	$group_id = $_POST['group_id'];
	$tag = $group_id;
	$group_label = trim($_POST['group_label']);
//	echo $group_id." ".$group_label."<br />";
	if(isset($_POST['forgood'])) $forgood = TRUE;
	else $forgood = FALSE;
	if(!$forgood) {
		echo "<table>";
		echo "<tr>";
		echo "<td class=\"tight\"><span style=\"color:red;\">Do you confirm the deletion of group</span> ‘".$group_label."’?</font><br />➡ No song will be deleted!</td>";
		echo "<td class=\"tight\">";
		echo "<form method=\"post\" action=\"groups.php\" enctype=\"multipart/form-data\">";
		echo "<input type=\"hidden\" name=\"forgood\" value=\"yes\">";
		echo "<input type=\"hidden\" name=\"group_id\" value=\"".$group_id."\">";
		echo "<input type=\"hidden\" name=\"group_label\" value=\"".$group_label."\">";
		echo "<input type=\"submit\" name = \"delete_group\" class=\"button\" value=\"YES\">";
		echo "</form>";
		echo "</td>";
		echo "<td class=\"tight\">";
		echo "<form method=\"post\" action=\"groups.php#".$tag."\" enctype=\"multipart/form-data\">";
		echo "<input type=\"submit\" class=\"button\" value=\"NO\">";
		echo "</form>";
		echo "</td>";
		echo "</tr>";
		echo "</table>";
		die();
		}
	else {
		unset($_POST['save_group']);
		$query_delete = "DELETE FROM ".BASE.".groups WHERE id = \"".$group_id."\"";
		$result_delete = $bdd->query($query_delete);
	//	echo $query_delete."<br />";
		$result_delete->closeCursor();
		$query_delete = "DELETE FROM ".BASE.".group_index WHERE group_id = \"".$group_id."\"";
		$result_delete = $bdd->query($query_delete);
		$result_delete->closeCursor();
	//	echo $query_delete."<br />";
		}
	}
	
if($edit_id > 0 AND isset($_POST['save_group'])) {
	$group_id = $_POST['group_id'];
	$group_label = trim($_POST['group_label']);
	do $group_label = str_replace("  "," ",$group_label,$count);
	while($count > 0);
	$group_label = str_replace(' ','_',$group_label);
	$group_label = str_replace('"','',$group_label);
	$group_label = str_replace('‘','',$group_label);
	$group_label = str_replace('’','',$group_label);
	$group_label = str_replace("'",'',$group_label);
	if($group_label == '') $group_label = "???";
	$parent_group_label = trim($_POST['parent_group_label']);
	if($parent_group_label <> '') {
		$parent_group_id = -1;
		$query_parent = "SELECT id FROM ".BASE.".groups WHERE label = \"".$parent_group_label."\"";
		$result_parent = $bdd->query($query_parent);
		$n = $result_parent->rowCount();
		if($n > 0) {
			$ligne_parent = $result_parent->fetch();
			$result_parent->closeCursor();
			$parent_group_id = $ligne_parent['id'];
			}
		}
	else $parent_group_id = 0;
	$comment_en = fix_typo(reshape_entry($_POST['comment_en']),0);
	$comment_mr = fix_typo(reshape_entry($_POST['comment_mr']),0);
	$query_update = "UPDATE ".BASE.".groups SET label = \"".$group_label."\", comment_en = \"".$comment_en."\", comment_mr = \"".$comment_mr."\", parent = \"".$parent_group_id."\", login = \"".$login."\" WHERE id = \"".$group_id."\"";
	$result_update = $bdd->query($query_update);
	$result_update->closeCursor();
	}
DisplayGroups($edit_id,0);
echo "</body>";
echo "</html>";

function DisplayGroups($edit_id,$parent) {
	global $bdd, $url_this_page, $login;
	if($parent == 0)
		$query = "SELECT * FROM ".BASE.".groups WHERE parent = \"".$parent."\" OR parent = \"-1\" ORDER BY label";
	else $query = "SELECT * FROM ".BASE.".groups WHERE parent = \"".$parent."\" ORDER BY label";
//	echo $query."<br />";
	$result = $bdd->query($query);
	$n = $result->rowCount();
	if($n == 0) {
		if($result) $result->closeCursor();
		return;
		}
//	echo "<tr><td colspan=\"5\"><hr></td></tr>";
	echo "<table>";
	while($ligne = $result->fetch()) {
		$group_id = $ligne['id'];
		$label = $ligne['label'];
		$tag = $group_id;
		$comment_mr = $ligne['comment_mr'];
		$comment_en = $ligne['comment_en'];
		$user = $ligne['login'];
		$date = $ligne['date'];
		$parent_group_id = $ligne['parent'];
		$parent_label = '';
		if($parent_group_id > 0) {
			$query_parent = "SELECT label FROM ".BASE.".groups WHERE id = \"".$parent_group_id."\"";
			$result_parent = $bdd->query($query_parent);
			if($result_parent) {
				$ligne_parent = $result_parent->fetch();
				$result_parent->closeCursor();
				$parent_label = $ligne_parent['label'];
				}
			}
		$comment_mr_all = $comment_en_all = array();
		$query_group_index = "SELECT * FROM ".BASE.".group_index WHERE group_id = \"".$group_id."\"";
		$result_group_index = $bdd->query($query_group_index);
		$n_group_index = $result_group_index->rowCount();
		$song_example_id = 0;
		while($ligne_index = $result_group_index->fetch()) {
			$song_id = $ligne_index['song_id'];
			if($song_example_id == 0) $song_example_id = $song_id;
			$devanagari = transcription($song_id,"devanagari");
			$roman_devanagari = transcription($song_id,"roman");
			$translation_english = transcription($song_id,"translation");
			if($translation_english <> '') {
				$song_example_id = $song_id;
				}
			$remarks_marathi = transcription($song_id,"remarks_marathi");
			if($remarks_marathi <> '') {
				if(!in_array($remarks_marathi,$comment_mr_all))
				$comment_mr_all[] = $remarks_marathi;
				}
			$remarks_english = transcription($song_id,"remarks_english");
			if($remarks_english <> '') {
				if(!in_array($remarks_english,$comment_en_all))
				$comment_en_all[] = $remarks_english;
				}
			$comment_mr_songs = implode("<br />",$comment_mr_all);
			$comment_en_songs = implode("<br />",$comment_en_all);
			}
		$result_group_index->closeCursor();
		$devanagari = song($song_example_id,"Example:")." ".transcription($song_example_id,"devanagari");
		$roman_devanagari = transcription($song_example_id,"roman");
		$translation_english = transcription($song_example_id,"translation");
		$parent_label_display = "<br /><small>";
		if($edit_id <> $group_id) {
			$devanagari = firstline($devanagari)."…";
			$roman_devanagari = firstline($roman_devanagari)."…";
			if($translation_english <> '')
				$translation_english = firstline($translation_english)."…";
			$parent_label_display = "<br /><small>•••> ";
			if($comment_mr <> '' OR $comment_en <> '') $rowspan = 2;
			else $rowspan = 1;
			$color = "GhostWhite";
			}
		else {
			$rowspan = 3;
			$color = "Yellow";
			}
		if($parent_group_id == "-1") {
			$parent_label = "???";
			$parent_label_display .= $parent_label;
			}
		else if($parent_group_id == 0) $parent_label_display = '';
		else $parent_label_display .= $parent_label;
		$parent_label_display .= "</small>";
		if($translation_english <> '') $translation_english = "Example: ".$translation_english;
		echo "<form method=\"post\" action=\"".$url_this_page."#".$tag."\" enctype=\"multipart/form-data\">";
		echo "<input type=\"hidden\" name=\"group_id\" value=\"".$group_id."\">";
		echo "<tr id=\"".$tag."\">";
		echo "<td style=\"font-size:100%; text-align:center; vertical-align:middle;\">";
		if($parent > 0) echo "➡";
		echo "</td>";
		echo "<td  style=\"text-align:center; vertical-align:middle; background-color:".$color.";\" rowspan=\"".$rowspan."\">";
		if($edit_id <> $group_id) {
			echo "<span style=\"color:red;\"><b>".$label."</b></span>";
			}
		else {
			echo "<b>This group:</b><br />";
			echo "<textarea name=\"group_label\" style=\"text-align:center;\" rows=\"2\" style=\"width:160px;\">".$label."</textarea>";
			}
		if($edit_id == 0 AND is_editor($login)) {
			echo "<br /><small>Saved by ".$user."<br />".$date."</small><br />";
			$url = "groups.php?edit=".$group_id."#".$tag;
			echo "<small>[<a href=\"".$url."\">Edit</a>]</small>";
			}
		if($edit_id == $group_id) {
			echo "<br /><br /><b>Parent group:</b><br />";
			echo "<textarea name=\"parent_group_label\" style=\"text-align:center;\" rows=\"2\" style=\"width:160px;\">".$parent_label."</textarea><br /><br />";
			echo "<input type=\"submit\" title=\"Save this group\" name=\"save_group\" class=\"button\" value=\"SAVE\"><br /><br />";
			echo "<small>[<a href=\"groups.php\">Close editing</a>]</small>";
			}
		else echo "<br />".$parent_label_display;
		echo "</td>";
		echo "<td rowspan=\"".$rowspan."\" style=\"text-align:center; vertical-align:middle;\">";
		echo "<a target=\"_blank\" title=\"Display songs in this group\" href=\"songs.php?group_label=".$label."\">".$n_group_index."&nbsp;songs</a>";
	//	echo "<br />&nbsp;➡&nbsp;<a target=\"_blank\" title=\"Edit songs in this group\" href=\"edit-songs.php?group_label=".$label."\">edit&nbsp;songs</a>";
		echo "</td>";
		echo "<td style=\"background-color:Cornsilk; min-width:300px;\" lang=\"mr\"><small>".$devanagari."<br />".$roman_devanagari."</small></td>";
		echo "<td style=\"background-color:Lavender; min-width:300px;\" lang=\"en\"><small>".$translation_english."</small></td>";
		if($edit_id == $group_id) {
			echo "<td class=\"tight\" style=\"background-color:Cornsilk; text-align:center; vertical-align:middle;\" rowspan=\"".$rowspan."\">";
			if($user <> '') echo "<br /><small>Saved by ".$user."<br />".$date."</small><br />";
			echo "<div class=\"tooltip\"><br /><input type=\"submit\" name=\"delete_group\" class=\"button\" style=\"background-color:red;\" value=\"DELETE\"><span class=\"tooltiptext\">Danger!<br />Can't be undone…</span></div>";
			echo "</td>";
			}
		echo "</tr>";
		if($edit_id == $group_id) {
			echo "<tr>";
			echo "<td></td>";
			if($comment_mr_songs <> '' OR $comment_en_songs <> '') {
				echo "<td class=\"tight\" style=\"background-color:ghostwhite; vertical-align:top;\">";
				if($comment_mr_songs <> '') echo "<b>Marathi comments found in songs:</b><br />";
				echo $comment_mr_songs;
				echo "</td>";
				echo "<td class=\"tight\" style=\"background-color:ghostwhite; vertical-align:top;\">";
				if($comment_en_songs <> '') echo "<b>English comments found in songs:</b><br />";
				echo $comment_en_songs;
				echo "</td>";
				}
			echo "</tr>";
			}
		if($comment_mr <> '' OR $comment_en <> '' OR $edit_id == $group_id) {
			echo "<tr>";
			echo "<td></td>";
			echo "<td style=\"background-color:ghostwhite;";
			if($edit_id <> $group_id) echo "font-size:80%;";
			echo "\"";
			if($edit_id == $group_id) echo " class=\"tight\"";
			echo ">";
		//	if($comment_mr <> '') echo "<b>Group comment in Marathi:</b><br />";
			if($edit_id <> $group_id) echo $comment_mr;
			else {
				echo "<p><b>Group comment in Marathi:</b></p>";
				echo "<textarea name=\"comment_mr\" ROWS=\"3\" style=\"width:330px;\">".str_replace("<br />","\n",$comment_mr)."</textarea>";
				}
			echo "</td>";
		//	echo "<td class=\"tight\" style=\"background-color:ghostwhite;\">";
			echo "<td style=\"background-color:ghostwhite;";
			if($edit_id <> $group_id) echo "font-size:80%;";
			echo "\"";
			if($edit_id == $group_id) echo " class=\"tight\"";
			echo ">";
		//	if($comment_en <> '') echo "<b>Group comment in English:</b><br />";
			if($edit_id <> $group_id) echo $comment_en;
			else {
				echo "<p><b>Group comment in English:</b></p>";
				echo "<textarea name=\"comment_en\" ROWS=\"3\" style=\"width:330px;\">".str_replace("<br />","\n",$comment_en)."</textarea>";
				}
			echo "</td>";
			echo "</tr>";
			}
		echo "</form>";
		echo "</table>";
		echo "<blockquote>";
		DisplayGroups($edit_id,$group_id);
		echo "</blockquote>";
		echo "<table>";
		}
	if($result) $result->closeCursor();
	echo "</table>";
	return;
	}
?>