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

$name = "Corrections";
$canonic_url = '';

require_once("_header.php");

$url_this_page = "edit-corrections.php";

echo "<h2>&nbsp;</h2>";
echo "<h2>Changing correction rules</h2><br />";
echo "<blockquote>";
echo "• These rules are used for correcting words and phrases in English translations. Rules are applied in order top-to-bottom. Since trailing spaces are discarded, if a space is required it must be encoded as '<font color=red><b>_</b></font>'.<br /><br />";
echo "• These rules are applied prior to <a target=\"_blank\" href=\"edit-typography.php\">typographic rules</a> enlisted in <i><small>ListOfTaggedWords.txt</small></i>. <a target=\"_blank\" href=\"edit-typography.php\">Click this link</a> to edit typographic rules.<br /><br />";
echo "• Several rules may seem deprecated as they were used to fix errors whenever data was imported from the source FileMaker database.<br />";
echo "</blockquote>";

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

echo "<h3>OBSOLETE!</h3>";

if(!is_super_admin($login)) die();

if(!is_admin($login)) {
	echo "<font color=red>Access restricted to Admin</font>";
	die();
	}

$date = date("Y-m-d");
$check_box = array();

if(isset($_POST['action']) AND $_POST['action'] == "reload_rules") {
	$translation_correction_english = LoadRewriteRules();
	echo "<font color=green>➡ ".count($translation_correction_english)." rewrite rules have been stored in the database</font><br /><br />";
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
	SaveFile($row);
	}

if(count($row) == 0) $row = LoadFile();

if(isset($_POST['create_lines'])) {
	$imax += 5;
	}
	
echo "<table>";
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
echo "<td colspan=\"4\" style=\"text-align:left\">";
echo "<b><font color=green><sub>⇣</sub>&nbsp;<small><input type=\"submit\" class=\"button\" value=\"CREATE NEW LINE\"> after checked boxes</font></b></small>";
echo "</td>";

echo "<input type=\"hidden\" name=\"save_entries\" value = \"ok\" />";

echo "<td class=\"tight\" style=\"background-color:Cornsilk; text-align:right;\">";
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
	echo "<td class=\"tight\" style=\"background-color:Cornsilk;\">";
	echo "<input type=\"text\" name=\"left_".$i."\" size=\"30\" value=\"".$row[$i][0]."\" />";
	echo "</td>";
	echo "<td class=\"tight\" style=\"background-color:Cornsilk; white-space:nowrap;\">";
	if($row[$i][1] <> '') echo " —> ";
	echo "</td>";
	echo "<td class=\"tight\" style=\"background-color:Cornsilk;\">";
	echo "<input type=\"text\" name=\"right_".$i."\" size=\"30\" value=\"".$row[$i][1]."\" />";
	echo "</td>";
	echo "<td class=\"tight\" style=\"background-color:Cornsilk; white-space:nowrap;\">";
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
echo "<td class=\"tight\" colspan=\"2\" style=\"background-color:Cornsilk; text-align:right;\">";
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

// =========== FUNCTIONS =============

function LoadFile() {
	$corrections_name = SETTINGS."TranslationCorrectionsEnglish.txt";
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
echo "</body>";
echo "</html>";

function SaveFile($row) {
	$file_header = "// This is a list of strings that need to be modified in English translations
// Entries are case-sensitive and spaces are significant (encoded as '_')
// Each line contains a tabulation, left is the target and right the replacement
// Rules produced by this table are applied before rules produced by ‘ListOfTaggedWords.txt’";
	$filename = SETTINGS."TranslationCorrectionsEnglish.txt";
	echo "<blockquote><small><font color=blue>Saving ‘".$filename."’<br />";
	echo "Don't forget to click <font color=red>RECONSTRUCT ALL RULES</font> <font color=blue>after completing changes!</font></small>";
	echo "</blockquote>";
	$export_file = fopen($filename,'w');
	fprintf($export_file,"%s\r\n\n",$file_header);
	for($i = 0; $i < count($row); $i++) {
		if($row[$i][0] <> '') {
			if($row[$i][1] <> '')
				fprintf($export_file,"%s\r\n",$row[$i][0]."\t".$row[$i][1]);
			else
				echo "<font color=red>WARNING! Missing replacement string for</font> ‘".$row[$i][0]."’<br />";;
			}
		}
	fclose($export_file);
	return;
	}
?>