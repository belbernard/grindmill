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

$name = "Typography";
$canonic_url = '';

require_once("_header.php");

$url_this_page = "edit-typography.php";

echo "<h2>&nbsp;</h2>";
echo "<h2>Changing typographic rules</h2><br />";
echo "<blockquote>";
echo "• These rules are used for adjusting the capitalization of words in English translations and calling for a ‘<font color=red><b>*</b></font>’ tag that makes them eligible for glossary entries. Rules are applied in order top-to-bottom.<br /><br />";
echo "• Please note that prior to these rules, <a target=\"_blank\" href=\"edit-corrections.php\">correction rules</a> enlisted in <i><small>TranslationCorrectionsEnglish.txt</small></i> will be applied.<br /><a target=\"_blank\" href=\"edit-corrections.php\">Click this link</a> to edit correction rules.<br />";
echo "</blockquote>";

if(!identified()) {
	echo "<font color=red>You logged out, or your edit session expired.<br />You need to log in or return to the “edit start” page.</font>";
	die();
	}
$login = $_SESSION['login'];
$_SESSION['try'] = 0;

echo "This page is obsolete."; die();

$old_time = time() - 3600;
$sql_delete = "DELETE FROM ".BASE.".t_access WHERE acce_time < \"".$old_time."\"";
$result_delete = $bdd->query($sql_delete);
$result_delete->closeCursor();

if(!is_editor($login)) {
	echo "<font color=red>Access restricted to editors</font>";
	die();
	}

$date = date("Y-m-d");
$check_box = array();

if(isset($_POST['action']) AND $_POST['action'] == "reload_rules") {
	$translation_correction_english = LoadRewriteRules();
	echo "<blockquote><font color=green>➡ ".count($translation_correction_english)." rewrite rules have been stored in the database</font></blockquote>";
	}

$row = array();

$imax = count($row);
if(isset($_POST['index_max']))
	$imax = $_POST['index_max'];

if(isset($_POST['save_entries'])) {
	foreach($_POST as $key => $value) {
		if(is_integer(strpos($key,"left_"))) {
			$i = str_replace("left_",'',$key);
			$row[$i][0] = $value;
			}
		if(is_integer(strpos($key,"right_"))) {
			$i = str_replace("right_",'',$key);
			$row[$i][1] = $value;
			}
		}
	$drift = 0;
	foreach($_POST as $key => $value) {
		if(is_integer(strpos($key,"insert_"))) {
			$i = str_replace("insert_",'',$key) + $drift;
			$imax++; $drift++;
			for($j = ($imax - 1); $j > ($i + 1); $j--) {
				$row[$j][0] = $row[$j-1][0];
				$row[$j][1] = $row[$j-1][1];
				}
			$row[$i+1][0] = $row[$i+1][1] = '';
			}
		}
	SaveListOfTaggedWordsFile(TRUE,$row);
	}

if(count($row) == 0) $row = LoadFile();

if(isset($_POST['create_lines'])) {
	$imax += 5;
	}

$n_words = 0;
echo "<table>";
echo "<tr>";
echo "<td class=\"tight\" colspan=\"6\" style=\"text-align:center; background-color:Cornsilk;\">";
echo "<b>Example:</b> entry <font color=blue>Abhang —> abhang</font> means that ";
echo "both <font color=blue>Abhang</font> and <font color=blue>abhang</font> should be rewritten as <font color=blue>abhang</font><font color=red>*</font>";
echo "</td>";
echo "</tr>";
echo "<tr>";
echo "<form name=\"ok to read\" method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
echo "<input type=\"hidden\" name = \"action\" value = \"reload_rules\" />";
echo "<td class=\"tight\" colspan=\"6\" style=\"text-align:center; background-color:Cornsilk;\">";
echo "Don't forget to click <input type=\"submit\" class=\"button\" style=\"text-align:center;\" value=\"RECONSTRUCT ALL RULES\"> after completing changes!";
echo "</form>";
echo "</td>";
echo "</tr>";
echo "<tr>";
echo "<form name=\"edit_tape\" method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
echo "<td colspan=\"2\" style=\"text-align:left\">";
echo "<b><font color=green><sub>⇣</sub>&nbsp;<small><input type=\"submit\" class=\"button\" value=\"CREATE NEW LINE\"> after checked boxes</font></b></small>";
echo "</td>";
echo "<td colspan=\"2\" style=\"text-align:left\">";
echo "</td>";
echo "<input type=\"hidden\" name=\"save_entries\" value = \"ok\" />";
echo "<td class=\"tight\" colspan=\"2\" style=\"background-color:Cornsilk; text-align:right;\">";
echo "<input type=\"submit\" class=\"button\" value=\"SAVE ALL THESE ENTRIES\">&nbsp;";
echo "</td>";
echo "</tr>";

for($i = 0; $i < count($row); $i++) {
	echo "<tr>";
	echo "<td class=\"tight\" style=\"background-color:Cornsilk;\">";
	echo "<b>⇣</b><input type=\"checkbox\" name=\"insert_".$i."\" value=\"ok\"";
	if(isset($check_box[$i]) AND $check_box[$i]) echo " checked";
	echo " />";
	echo "</td>";
	$n_words++;
	echo "<td class=\"tight\" style=\"background-color:Cornsilk;\">";
	echo $n_words." <input type=\"text\" name=\"left_".$i."\" size=\"30\" value=\"".$row[$i][0]."\" />";
	echo "</td>";
	echo "<td class=\"tight\" style=\"background-color:Cornsilk; white-space:nowrap;\">";
	if($row[$i][1] <> '') {
		echo " —> ";
	//	$n_words++;
		}
	echo "</td>";
	echo "<td class=\"tight\" style=\"background-color:Cornsilk;\">";
	echo "<input type=\"text\" name=\"right_".$i."\" size=\"30\" value=\"".$row[$i][1]."\" />";
	echo "</td>";
	echo "<td class=\"tight\" style=\"background-color:Cornsilk; white-space:nowrap;\">";
	if($row[$i][0] <> '') echo " —> ";
	echo "</td>";
	echo "<td class=\"tight\" style=\"background-color:Cornsilk;\">";
	if($row[$i][1] <> '') echo str_replace('_',' ',$row[$i][1]);
	else if($row[$i][0] <> '') echo str_replace('_',' ',$row[$i][0]);
	echo "<span style=\"color:red;\">*</span>";
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
	echo "<input type=\"text\" name=\"left_".$i."\" size=\"30\" value=\"\" />";
	echo "</td>";
	echo "<td class=\"tight\" style=\"background-color:Cornsilk;\">";
	echo " —> ";
	echo "</td>";
	echo "<td class=\"tight\" style=\"background-color:Cornsilk;\">";
	echo "<input type=\"text\" name=\"right_".$i."\" size=\"30\" value=\"\" />";
	echo "</td>";
	echo "<td class=\"tight\" style=\"background-color:Cornsilk;\">";
	echo "</td>";
	echo "<td class=\"tight\" style=\"background-color:Cornsilk;\">";
	echo "</td>";
	echo "</tr>";
	$i++;
	}
echo "<input type=\"hidden\" name=\"index_max\" value = \"".$i."\" />";
echo "<tr>";
echo "<td colspan=\"2\" style=\"text-align:left\">";
echo "<b><font color=green>↑&nbsp;<small><b><font color=green>&nbsp;<input type=\"submit\" class=\"button\" value=\"CREATE NEW LINE\"> after checked boxes</font></b></small>";
echo "</td>";
echo "<td class=\"tight\" colspan=\"3\" style=\"background-color:Cornsilk; text-align:right;\">";
echo "<input type=\"submit\" class=\"button\" value=\"SAVE ALL THESE ENTRIES\">&nbsp;";
echo "</td>";
echo "</form>";
echo "<td class=\"tight\" colspan=\"1\" style=\"background-color:Cornsilk; text-align:right;\">";
echo "<form name=\"create_lines\" method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
echo "<input type=\"hidden\" name=\"index_max\" value = \"".$i."\" />";
echo "<input type=\"hidden\" name=\"create_lines\" value = \"ok\" />";
echo "<input type=\"submit\" class=\"button\" value=\"CREATE MORE LINES\">&nbsp;";
echo "</form>";
echo "</td>";
echo "</tr>";
echo "</table>";
echo $n_words." words";
echo "</body>";
echo "</html>";

// =========== FUNCTIONS =============

function LoadFile() {
	$corrections_name = SETTINGS."ListOfTaggedWords.txt";
	$correction_file = @fopen($corrections_name,"rb");	
	if($correction_file == FALSE) {
		echo "ERROR: ‘".$corrections_name."’ is missing!";
		die();
		}
	else {
	//	echo "<small><font color=blue>Reading ‘".$corrections_name."’</font></small><br />";
		$row = array();
		$i = 0;
		while(!feof($correction_file)) {
			$newline = fgets($correction_file);
			if(trim($newline) == '') continue;
			if(is_integer($pos=strpos($newline,"//")) AND $pos == 0) {
				continue;
				}
			$row[$i] = array();
			if(!is_integer(strpos($newline,chr(9)))) {
				$table[0] = trim($newline);
				$table[1] = '';
				}
			else $table = explode(chr(9),$newline);
			$row[$i][0] = $word0 = trim($table[0]);
			$row[$i][1] = $word2 = trim($table[1]);
			$i++;
			}
		fclose($correction_file);
		echo "</table>";
		}
	return $row;
	}
?>