<?php
// session_start();
require_once("_base_urls.php");
if(isset($_SESSION['login']) AND $_SESSION['login'] <> '')
	require_once("_relier_edit.php");
	// user is allowed to write
else require_once("_relier.php");
	// user only allowed to read
require_once("_tasks.php");
require_once("_edit_tasks.php");

$name = "Edit classification";
$canonic_url = '';

$semantic_class_title_prefix = $title_comment = $title_comment_mr = '';
if(isset($_GET['class'])) $semantic_class_title_prefix = urldecode($_GET['class']);
else echo "<font color=red>No semantic class has been selected…</font>";

require_once("_header.php");

echo "<h2>&nbsp;</h2>";
echo "<h2>Edit classification</h2><br />";

if(!identified()) {
	echo "<font color=red>You logged out, or your edit session expired.<br />You need to log in or return to the “edit start” page.</font>";
	die();
	}
$login = $_SESSION['login'];
$_SESSION['try'] = 0;

$old_time = time() - 3600;
$sql_delete = "DELETE FROM ".BASE.".t_access WHERE acce_time < \"".$old_time."\"";
$result_delete = $bdd->query($sql_delete);
$result_delete->closeCursor();

if(!is_translator($login)) {
	echo "<font color=red>Access restricted to translator</font>";
	die();
	}

$old_semantic_class_title_prefix = $semantic_class_title_prefix;

/* $url_this_page = "edit-classification.php?class=".$semantic_class_title_prefix."#table"; */
$url_this_page .= "#table";

$row = array();

$imax = count($row);
if(isset($_POST['index_max'])) $imax = $_POST['index_max'];

if(isset($_POST['save_entries'])) {
	if(isset($_POST['semantic_class_title_prefix'])) {
		$semantic_class_title_prefix = trim($_POST['semantic_class_title_prefix']);
		}
	if(isset($_POST['title_comment'])) {
		$title_comment = fix_typo(reshape_entry($_POST['title_comment']),0);
		}
	if(isset($_POST['title_comment_mr'])) {
		$title_comment_mr = fix_typo(reshape_entry($_POST['title_comment_mr']),0);
		}
	foreach($_POST as $key => $value) {
		for($j = 0; $j < 8; $j++) {
			if(is_integer(strpos($key,$j."_"))) {
				$i = str_replace($j."_",'',$key);
				if($j == 3 OR $j == 4) $row[$i][$j] = fix_typo(reshape_entry($value),0);
				else $row[$i][$j] = trim($value);
				}
			}
		}
	$drift = 0;
	foreach($_POST as $key => $value) {
		if(is_integer(strpos($key,"insert_"))) {
			$i = str_replace("insert_",'',$key) + $drift;
			$imax++; $drift++;
			for($k = ($imax - 1); $k > ($i + 1); $k--) {
				for($j = 0; $j < 8; $j++)
					$row[$k][$j] = $row[$k-1][$j];
				}
			for($j = 0; $j < 8; $j++)
				$row[$i+1][$j] = $row[$i+1][$j] = '';
			}
		
		if(is_integer(strpos($key,"delete_"))) {
			$i = str_replace("delete_",'',$key) + $drift;
			for($j = 0; $j < 8; $j++) $row[$i][$j] = '';
			}
		}
	SaveClassification($row,$semantic_class_title_prefix,$old_semantic_class_title_prefix,$title_comment,$title_comment_mr);
	}

if(count($row) == 0) $row = LoadClassification($semantic_class_title_prefix);

if(isset($_POST['create_lines'])) {
	$imax += 5;
	}

// echo count($row)."<br />";

echo "<p><b>".$semantic_class_title_prefix."</b>";
echo "<ul>";
$query = "SELECT * FROM ".BASE.".classification WHERE semantic_class_title_prefix = \"".$semantic_class_title_prefix."\" ORDER BY semantic_class_id";
$result = $bdd->query($query);
while($ligne = $result->fetch()) {
	$title_comment = $ligne['title_comment']; // Repeated
	$title_comment_mr = $ligne['title_comment_mr']; // Repeated
	echo "<li>(".$ligne['semantic_class_id'].") <font color=green>".$ligne['semantic_class']."</font> - <a target=\"_blank\" title=\"All songs in this class\" href=\"songs.php?semantic_class_id=".$ligne['semantic_class_id']."\">".$ligne['semantic_class_title']."</a> ";
	$list_cross_references = list_cross_references($ligne['cross_references'],"MediumTurquoise",TRUE);
	if($list_cross_references <> '') echo "-> Cross-reference(s): <font color=green>".$list_cross_references."</font>";
	echo "</li>";
	}
$result->closeCursor();
echo "</ul></p>";
echo "<a name=\"table\"></a>";
echo "<table>";
echo "<tr>";
echo "<form name=\"edit_tape\" method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
echo "<td colspan=\"2\" style=\"text-align:left; white-space:nowrap;\">";
echo "<b><font color=green><sub>⇣</sub>&nbsp;<small><input type=\"submit\" class=\"button\" value=\"CREATE NEW LINE(S)\"><br />after checked boxes</font></b></small>";
echo "<input type=\"hidden\" name=\"save_entries\" value = \"ok\" />";
echo "<td class=\"tight\" colspan=\"2\" style=\"background-color:Cornsilk; text-align:center;\">";
echo "&nbsp;<b>Class title</b><br /><br />";
echo "<input type=\"text\" name=\"semantic_class_title_prefix\" size=\"45\" value=\"".$semantic_class_title_prefix."\" />";
echo "</td>";
echo "<td class=\"tight\" style=\"background-color:Cornsilk; text-align:center;\">";
echo "<b>Comments (English)</b><br /><br />";
echo "<textarea name=\"title_comment\" style=\"text-align:left;\" rows=\"2\" cols=\"30\">".str_replace("<br />","\n",$title_comment)."</textarea>";
echo "</td>";
echo "<td class=\"tight\" style=\"background-color:Cornsilk; text-align:center;\">";
echo "<b>Comments (Marathi)</b><br /><br />";
echo "<textarea name=\"title_comment_mr\" style=\"text-align:left;\" rows=\"2\" cols=\"30\">".str_replace("<br />","\n",$title_comment_mr)."</textarea>";
echo "</td>";
echo "<td class=\"tight\" style=\"background-color:Cornsilk; text-align:left;\">";
echo "<input type=\"submit\" class=\"button\" value=\"SAVE ALL THESE ENTRIES\">&nbsp;";
echo "<p style=\"text-align:center;\"><b>Cross-references</b></p>";
echo "</td>";
echo "<td class=\"tight\" style=\"background-color:Cornsilk; text-align:center;\">Delete<br />⇣(<a href=\"\" title=\"There is a protection against deleting classes that contain songs\">?</a>)</td>";
echo "</tr>";
for($i = 0; $i < count($row); $i++) {
	echo "<tr>";
	echo "<td class=\"tight\" style=\"background-color:Cornsilk;\">";
	echo "<b>⇣</b><input type=\"checkbox\" name=\"insert_".$i."\" value=\"ok\"";
	if(isset($check_box[$i]) AND $check_box[$i]) echo " checked";
	echo " />";
	echo "</td>";
	echo "<td class=\"tight\" style=\"background-color:Cornsilk;\">";
	if($row[$i][0] <> '' AND !check_semantic_id("full",$row[$i][0])) {
		echo "<br />";
		echo "<input type=\"text\" name=\"0_".$i."\" size=\"12\" value=\"".$row[$i][0]."\" />";
		}
	else {
		if($row[$i][0] == '')
		echo "<input type=\"text\" name=\"0_".$i."\" size=\"12\" value=\"".$row[$i][0]."\" />";
		else {
			echo class_link($row[$i][0]);
			echo "<input type=\"hidden\" name=\"0_".$i."\" value = \"".$row[$i][0]."\" />";
			}
		}
	echo "</td>";
	echo "<td class=\"tight\" style=\"background-color:Cornsilk;\">";
	echo "<input type=\"text\" name=\"1_".$i."\" size=\"12\" value=\"".$row[$i][1]."\" />";
	echo "</td>";
	echo "<td class=\"tight\" style=\"background-color:Cornsilk;\">";
	echo "<input type=\"text\" name=\"2_".$i."\" size=\"30\" value=\"".$row[$i][2]."\" />";
	echo "</td>";
	echo "<td class=\"tight\" style=\"background-color:Cornsilk;\">";
	echo "<textarea name=\"3_".$i."\" style=\"text-align:left;\" rows=\"2\" cols=\"30\">".str_replace("<br />","\n",$row[$i][3])."</textarea>";
	echo "</td>";
	echo "<td class=\"tight\" style=\"background-color:Cornsilk;\">";
	echo "<textarea name=\"4_".$i."\" style=\"text-align:left;\" rows=\"2\" cols=\"30\">".str_replace("<br />","\n",$row[$i][4])."</textarea>";
	echo "</td>";
	echo "<td class=\"tight\" style=\"background-color:Cornsilk;\">";
	echo "<input type=\"text\" name=\"5_".$i."\" size=\"60\" value=\"".$row[$i][5]."\" />";
	echo "</td>";
	echo "<td class=\"tight\" style=\"background-color:Cornsilk;\">";
	echo "<input type=\"checkbox\" name=\"delete_".$i."\" value=\"ok\"";
	$class_link = class_link($row[$i][0]);
	if(is_integer(strpos($class_link,"</a>"))) {
		echo " disabled";
		}
	echo " />";
	echo "</td>";
	echo "<td class=\"tight\" style=\"white-space:nowrap;\">";
	echo "<input type=\"hidden\" name=\"6_".$i."\" value = \"".$_SESSION['login']."\" />";
	echo "<input type=\"hidden\" name=\"7_".$i."\" value = \"".date("Y-m-d H:i:s")."\" />";
	echo "<small>".$row[$i][6]."<br />".$row[$i][7]."</small>";
	echo "</td>";
	echo "</tr>";
	}
while($i < $imax) {
	echo "<tr>";
	echo "<td class=\"tight\" style=\"background-color:Cornsilk;\">";
	echo "<b>⇣</b><input type=\"checkbox\" name=\"insert_".$i."\" value=\"ok\"";
	echo " />";
	echo "</td>";
	echo "<td class=\"tight\" style=\"background-color:Cornsilk;\">";
	echo "<input type=\"text\" name=\"0_".$i."\" size=\"12\" value=\"\" />";
	echo "</td>";
	echo "<td class=\"tight\" style=\"background-color:Cornsilk;\">";
	echo "<input type=\"text\" name=\"1_".$i."\" size=\"12\" value=\"\" />";
	echo "</td>";
	echo "<td class=\"tight\" style=\"background-color:Cornsilk;\">";
	echo "<input type=\"text\" name=\"2_".$i."\" size=\"30\" value=\"\" />";
	echo "</td>";
	echo "<td class=\"tight\" style=\"background-color:Cornsilk;\">";
	echo "<textarea name=\"3_".$i."\" style=\"text-align:left;\" rows=\"2\" cols=\"30\"></textarea>";
	echo "</td>";
	echo "<td class=\"tight\" style=\"background-color:Cornsilk;\">";
	echo "<textarea name=\"4_".$i."\" style=\"text-align:left;\" rows=\"2\" cols=\"30\"></textarea>";
	echo "</td>";
	echo "<td class=\"tight\" style=\"background-color:Cornsilk;\">";
	echo "<input type=\"text\" name=\"5_".$i."\" size=\"60\" value=\"\" />";
	echo "</td>";
	echo "<td class=\"tight\" style=\"background-color:Cornsilk;\">";
	echo "<input type=\"checkbox\" name=\"delete_".$i."\" value=\"ok\"";
	echo " />";
	echo "</td>";
	echo "<td class=\"tight\" style=\"white-space:nowrap;\">";
	echo "<input type=\"hidden\" name=\"6_".$i."\" value = \"".$_SESSION['login']."\" />";
	echo "<input type=\"hidden\" name=\"7_".$i."\" value = \"".date("Y-m-d H:i:s")."\" />";
	echo "</td>";
	echo "</tr>";
	$i++;
	}
echo "<input type=\"hidden\" name=\"index_max\" value = \"".$i."\" />";
echo "<tr>";
echo "<td colspan=\"2\" style=\"text-align:left; white-space:nowrap;\">";
echo "<b><font color=green>↑&nbsp;<small><b><font color=green>&nbsp;<input type=\"submit\" class=\"button\" value=\"CREATE NEW LINE(S)\"><br />after checked boxes</font></b></small>";
echo "</td>";
echo "<td class=\"tight\" colspan=\"2\" style=\"background-color:Cornsilk; text-align:right;\">";
echo "<input type=\"submit\" class=\"button\" value=\"SAVE ALL THESE ENTRIES\">&nbsp;";
echo "</td>";
echo "</form>";
echo "<td class=\"tight\" colspan=\"2\" style=\"background-color:Cornsilk; text-align:center;\">";
echo "<form name=\"create_lines\" method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
echo "<input type=\"hidden\" name=\"index_max\" value = \"".$i."\" />";
echo "<input type=\"hidden\" name=\"create_lines\" value = \"ok\" />";
echo "<input type=\"submit\" class=\"button\" value=\"CREATE MORE LINES\">&nbsp;";
echo "</form>";
echo "</td>";
echo "</tr>";
echo "</table>";

// =========== FUNCTIONS =============

function LoadClassification($semantic_class_title_prefix) {
	global $bdd;
	$row = array();
	$query = "SELECT * FROM ".BASE.".classification WHERE semantic_class_title_prefix = \"".$semantic_class_title_prefix."\" ORDER BY semantic_class_id";
	$result = $bdd->query($query);
	$i = 0;
	while($ligne = $result->fetch()) {
		$row[$i][0] = $ligne['semantic_class_id'];
		$row[$i][1] = $ligne['semantic_class'];
		$row[$i][2] = $ligne['semantic_class_title'];
		
		$row[$i][3] = $ligne['class_comment'];
		$row[$i][4] = $ligne['class_comment_mr'];
		
		$list_cross_references = list_cross_references($ligne['cross_references'],'',TRUE);
		$row[$i][5] = $list_cross_references;
		$row[$i][6] = $ligne['login'];
		$row[$i][7] = $ligne['date_modified'];
		$i++;
		}
	$result->closeCursor();
	return $row;
	}

function SaveClassification($row,$semantic_class_title_prefix,$old_semantic_class_title_prefix,$title_comment,$title_comment_mr) {
	global $bdd;
	$query_delete = "DELETE FROM ".BASE.".classification WHERE semantic_class_title_prefix = \"".$old_semantic_class_title_prefix."\"";
	$result_delete = $bdd->query($query_delete);
	if(!$result_delete) {
		echo "<br /><font color=red>ERROR modifying table:</font> ".$query_delete."<br />";
		die();
		}
	$result_delete->closeCursor();
	$done = array();
	for($i = 0; $i < count($row); $i++) {
		if(trim($row[$i][0]) == '' AND trim($row[$i][1]) == '' AND trim($row[$i][2]) == '' AND trim($row[$i][3]) == '') continue;
		if(trim($row[$i][0]) == '' OR trim($row[$i][1]) == '' OR trim($row[$i][2]) == '') {
			echo "<font color=red>ERROR: missing field(s) on this line:</font><br />";
			echo "[".$i."] ‘".$row[$i][0]."’ ‘".$row[$i][1]."’ ‘".$row[$i][2]."’ ‘".$row[$i][3]."’<br />";
			continue;
			}
		$semantic_class_id = trim($row[$i][0]);
		if(!check_semantic_id("full",$semantic_class_id)) {
			echo " ‘".$semantic_class_id."’<br />";
			continue;
			}
		if(isset($done[$semantic_class_id])) {
			echo "<font color=red>ERROR: duplicate class:</font> ".$semantic_class_id ."<br />";
			continue;
			}
		$done[$semantic_class_id] = TRUE;
		$cross_references = trim($row[$i][5]);
		$cross_references = str_replace('?','',$cross_references);
		$date = date("Y-m-d H:i:s");
		$query_update = "INSERT INTO ".BASE.".classification (semantic_class_title_prefix, title_comment, title_comment_mr, semantic_class_id, semantic_class, semantic_class_title, cross_references, class_comment, class_comment_mr, login, date_modified) VALUES (\"".$semantic_class_title_prefix."\",\"".$title_comment."\",\"".$title_comment_mr."\",\"".trim($row[$i][0])."\",\"".trim($row[$i][1])."\",\"".trim($row[$i][2])."\",\"".$cross_references."\",\"".trim($row[$i][3])."\",\"".trim($row[$i][4])."\",\"".$_SESSION['login']."\",\"".$date."\")";
	//	echo $query_update."<br />";
		$result_update = $bdd->query($query_update);
		if(!$result_update) {
			echo "<br /><font color=red>ERROR modifying table:</font> ".$query_update."<br />";
			die();
			}
		$result_update->closeCursor();
		}
	}

function class_link($semantic_class_id) {
	global $bdd;
	$query = "SELECT count(*) from ".BASE.".songs WHERE semantic_class_id = \"".$semantic_class_id."\"";
	$result = $bdd->query($query);
	$n = $result->fetchColumn();
	$result->closeCursor();
	if($n == 0) return $semantic_class_id;
	$link = "<a target=\"_blank\" title=\"Show ".$n." songs in this class\" href=\"songs.php?semantic_class_id=".$semantic_class_id."\">".$semantic_class_id."</a>";
	return $link;
	}
?>