<?php
// session_start();
ini_set('max_execution_time',5000);
ini_set("auto_detect_line_endings",true);
set_time_limit(5000);

require_once("_base_urls.php");
if(isset($_SESSION['login']) AND $_SESSION['login'] <> '')
	require_once("_relier_edit.php");
	// user is allowed to write
else require_once("_relier.php");
	// user only allowed to read
require_once("_users.php");
require_once("_tasks.php");
require_once("_edit_tasks.php");

if(!check_serious_attempt('browse')) die();

$name = "Index translations";
$canonic_url = '';
$mssg = '';
require_once("_header.php");

echo "<a name=\"top\"></a>";

echo "<h2>Fix and index translations</h2>";

if(!identified()) {
	echo "<font color=red>You need to log in to read this page.</font>";
	die();
	}
$login = $_SESSION['login'];
$_SESSION['try'] = 0;

$old_time = time() - 3600;
$sql = "DELETE FROM ".BASE.".t_access WHERE 'acce_time' < '".$old_time."'";
// echo $sql."<br />";
$result = $bdd->query($sql);
$result->closeCursor();

if(isset($_POST['action']) AND $_POST['action'] == "reload_rules") {
	$translation_correction_english = LoadRewriteRules();
	echo "<font color=blue>".count($translation_correction_english)." rewrite rules have been stored in the database</font><br /><br />";
	}

if(isset($_POST['action']) AND $_POST['action'] == "load_file") {
	$folder = $_POST['folder'];
	ReloadFileInList("index_translations.php",$folder);
	}
	
if(isset($_POST['action']) AND $_POST['action'] == "reload_files") {
	$list = array("TranslationCorrectionsEnglish.txt", "AddToIndex.txt", "ExclusionList.txt");
	$_SESSION['list'] = $list;
	$reload = ReloadFileInList("index_translations.php",SETTINGS);
	}

if(isset($_POST['action']) AND $_POST['action'] == "index") {
	$try = $_POST['try'];
	if($try == "yes") {
		echo "<p style=\"text-align:center;\">Are you sure?<br /><i>Trying to fix translations on the entire corpus<br />may end up with a 'time-out’.<br />It is wiser to do it on a <a target=\"_blank\" href=\"workset.php\">work set</a>.</i></p>";
		echo "<table>";
		echo "<tr>";
		echo "<td class=\"tight\" style=\"text-align:center; background-color:Bisque;\">";
		echo "<form name=\"ok to read\" method=\"post\" action=\"index_translations.php\" enctype=\"multipart/form-data\">";
		echo "<input type=\"hidden\" name = \"action\" value = \"index\" />";
		echo "<input type=\"hidden\" name = \"try\" value = \"no\" />";
		echo "<input type=\"hidden\" name = \"fix_translations\" value = \"yes\" />";
		echo "<input type=\"hidden\" name = \"create_index\" value = \"yes\" />";
		echo "<input type=\"submit\" class=\"button\" value=\"FIX TRANSLATIONS + CONSTRUCT INDEX\">";
		echo "</form>";
		echo "<small>Risk of time-out</small>";
		echo "</td>";
		echo "<td class=\"tight\" style=\"text-align:center; background-color:Bisque;\">";
		echo "<form name=\"ok to read\" method=\"post\" action=\"index_translations.php\" enctype=\"multipart/form-data\">";
		echo "<input type=\"hidden\" name = \"action\" value = \"index\" />";
		echo "<input type=\"hidden\" name = \"try\" value = \"no\" />";
		echo "<input type=\"hidden\" name = \"fix_translations\" value = \"yes\" />";
		echo "<input type=\"hidden\" name = \"create_index\" value = \"no\" />";
		echo "<input type=\"submit\" class=\"button\" value=\"ONLY FIX TRANSLATIONS\">";
		echo "</form>";
		echo "<small>Risk of time-out</small>";
		echo "</td>";
		echo "<td class=\"tight\" style=\"text-align:center; background-color:Bisque;\">";
		echo "<form name=\"ok to read\" method=\"post\" action=\"index_translations.php\" enctype=\"multipart/form-data\">";
		echo "<input type=\"hidden\" name = \"action\" value = \"index\" />";
		echo "<input type=\"hidden\" name = \"try\" value = \"no\" />";
		echo "<input type=\"hidden\" name = \"fix_translations\" value = \"no\" />";
		echo "<input type=\"hidden\" name = \"create_index\" value = \"yes\" />";
		echo "<input type=\"submit\" class=\"button\" value=\"ONLY RECONSTRUCT INDEX\">";
		echo "</form>";
		echo "<small>This is safe!</small>";
		echo "</td>";
		echo "<td class=\"tight\" style=\"text-align:center; background-color:Red;\">";
		echo "<form name=\"discard_file\" method=\"post\" action=\"index_translations.php\" enctype=\"multipart/form-data\">";
		echo "<input type=\"submit\" class=\"button\" value=\"CANCEL!\">";
		echo "</form>";
		echo "</td>";
		echo "</tr>";
		echo "</table>";
		echo "[<a href=\"admin.php\">Return to Admin page</a>]";	
		die();
		}
	}

if(!isset($_POST['action']) OR $_POST['action'] <> "index") {
	echo "Fixing translations will modify the content of the SONGS table, applying current typographic rules to all English translations.<br /><br />It may end up with a time-out if applied to the entire corpus. Therefore it is wiser to apply it to submitted <a target=\"_blank\" href=\"workset.php\">work sets</a> (button ‘FIX TYPOGRAPHY’).<br /><br />";
	echo "Indexing English translations will update the following index files (without modifying the content of the SONGS table):<br />";
	echo "<ul>";
	echo "<li><a href=\"".INDEX."SONGS_English_index_tagged.txt\">SONGS_English_index_tagged.txt</a></li>";
	echo "<li><a href=\"".INDEX."SONGS_English_index_full.txt\">SONGS_English_index_full.txt</a></li>";
	echo "<li><a href=\"".INDEX."SONGS_English_index_proper.txt\">SONGS_English_index_proper.txt</a></li>";
	echo "</ul>";
	echo "These operations require the following settings files:<br />";
	echo "<ul>";
	echo "<li><a href=\"".SETTINGS."TranslationCorrectionsEnglish.txt\">TranslationCorrectionsEnglish.txt</a></li>";
//	echo "<li><a href=\"".SETTINGS."ListOfTaggedWords.txt\">ListOfTaggedWords.txt</a></li>";
	echo "<li><a href=\"".SETTINGS."AddToIndex.txt\">AddToIndex.txt</a></li>";
	echo "<li><a href=\"".SETTINGS."ExclusionList.txt\">ExclusionList.txt</a></li>";
	echo "</ul>";
	
	echo "<blockquote><table>";
	echo "<tr>";
	echo "<td class=\"tight\" style=\"text-align:center; background-color:Bisque;\">";
	echo "<form name=\"ok to read\" method=\"post\" action=\"index_translations.php\" enctype=\"multipart/form-data\">";
	echo "<input type=\"hidden\" name = \"action\" value = \"index\" />";
	echo "<input type=\"hidden\" name = \"try\" value = \"yes\" />";
	echo "<p><input type=\"submit\" class=\"button\" value=\"START FIXING/INDEXING\"></p>";
	echo "</form>";
	echo "</td>";
	
	echo "<td class=\"tight\" style=\"text-align:center; background-color:Bisque;\">";
	echo "<form name=\"ok to read\" method=\"post\" action=\"index_translations.php\" enctype=\"multipart/form-data\">";
	echo "<input type=\"hidden\" name = \"action\" value = \"reload_files\" />";
	echo "<input type=\"submit\" class=\"button\" style=\"text-align:center;\" value=\"RELOAD FILES\"><br />";
//	echo "<small>Reload all settings files<br />‘TranslationCorrectionsEnglish.txt’<br /> ‘ListOfTaggedWords.txt’ etc.</small>";
	echo "<small>Reload all settings files<br />‘TranslationCorrectionsEnglish.txt’ etc.</small>";
	echo "</form>";
	echo "</td>";
	
	echo "<td class=\"tight\" style=\"text-align:center; background-color:Bisque;\">";
	echo "<form name=\"ok to read\" method=\"post\" action=\"index_translations.php\" enctype=\"multipart/form-data\">";
	echo "<input type=\"hidden\" name = \"action\" value = \"reload_rules\" />";
	echo "<input type=\"submit\" class=\"button\" style=\"text-align:center;\" value=\"RELOAD RULES\"><br />";
	echo "<small>Load rules from files:<br />‘TranslationCorrectionsEnglish.txt’<br />‘DevanagariCorrections.txt’<br />‘DiacriticalCorrections.txt’</small>";
	echo "</form>";
	echo "</td>";
	
	echo "</tr>";
	echo "</table>";
	echo "[<a href=\"admin.php\">Return to Admin page</a>]</blockquote>";
	die();
	}

$create_index = FALSE;
if(isset($_POST['create_index']) AND $_POST['create_index'] == "yes")
	$create_index = TRUE;
$fix_translations = FALSE;
if(isset($_POST['fix_translations']) AND $_POST['fix_translations'] == "yes")
	$fix_translations = TRUE;

$time_start = time();
$index_common = $index_proper = $index_common_list = $index_proper_list = array();
$errors = $warnings = 0;

$translation_correction_english = ReadRewriteRules("english");

/* $imax = count($translation_correction_english);
for($i = 0; $i < $imax; $i++) {
	echo str_replace(' ','_',$translation_correction_english[$i][0])." => ".str_replace(' ','_',$translation_correction_english[$i][1])."<br />";
	} */
	
echo "<font color=blue>".count($translation_correction_english)." rewrite rules found in the database</font><br />";

// die();

// Read include list
$included_in_index = array();
$read_name = SETTINGS."AddToIndex.txt";
$read_file = @fopen($read_name,"rb");
if($read_file == FALSE) {
	echo "ERROR: ‘".$read_name."’ is missing!";
	die();
	}
else {
	echo "<font color=blue>Reading ‘".$read_name."’</font><br />";
	$i = 0;
	while(!feof($read_file)) {
		$line = fgets($read_file);
		$line = trim($line);
		if($line == '' OR (is_integer($pos=strpos($line,"//")) AND $pos == 0)) continue;
		$included_in_index[] = $line;
		}
	}

// Read exclusion list
$excluded_word = array();
$read_name = SETTINGS."ExclusionList.txt";
$read_file = fopen($read_name,"rb");
if($read_file == FALSE) {
	echo "ERROR: ‘".$read_name."’ is missing!";
	die();
	}
else {
	echo "<font color=blue>Reading ‘".$read_name."’</font><br />";
	$i = 0;
	while(!feof($read_file)) {
		$line = fgets($read_file);
		$line = trim($line);
		if($line == '' OR (is_integer($pos=strpos($line,"//")) AND $pos == 0)) continue;
//		echo "".$line."<br />";
		$excluded_word[] = $line;
		}
	}

$query = "SELECT song_id, translation_english FROM ".BASE.".songs WHERE translation_english <> \"\" ORDER BY song_id DESC";
$result = $bdd->query($query);
$imax = $result->rowCount();
$ligne = $result->fetch();
$idmax = $ligne['song_id'];
$result->closeCursor();
echo "<br />Processing ".$imax." translations<br /><hr><small>";

if($fix_translations) {
	// We will do it in several steps to avoid time-outs
	$i_changed = $j = $jj = 0;
	for($id_min = 0; $id_min < $idmax; $id_min += 1000) {
		fix_song_translations($id_min,($id_min + 1000),0,$translation_correction_english);
		}
	echo "<br /><font color=green>END OF PROCESS</font><br />";
	}

echo "</small>";
if($create_index) {
	index_all_translations($translation_correction_english);
	echo "<br /><font color=blue>Saving new indexes:</font><br />";
	echo "<ul>";
	echo "<li><a href=\"".INDEX."SONGS_English_index_tagged.txt\">SONGS_English_index_tagged.txt</a></li>";
	echo "<li><a href=\"".INDEX."SONGS_English_index_full.txt\">SONGS_English_index_full.txt</a></li>";
	echo "<li><a href=\"".INDEX."SONGS_English_index_proper.txt\">SONGS_English_index_proper.txt</a></li>";
	echo "</ul>";
	$SONGS_English_index_tagged_txt = fopen(INDEX."SONGS_English_index_tagged.txt",'w');
	$SONGS_English_index_proper_txt = fopen(INDEX."SONGS_English_index_proper.txt",'w');
	$SONGS_English_index_full_txt = fopen(INDEX."SONGS_English_index_full.txt",'w');
	// Include all words listed in ‘AddToIndex.txt’
	for($i = 0; $i < count($included_in_index); $i++) {
		$phrase = $included_in_index[$i];
		$index_common[] = $phrase;
		$index_common_list[$phrase] = '';
		}
	natcasesort($index_common);
	$index_common = array_values($index_common);
	fprintf($SONGS_English_index_full_txt,"%s\r\n","// Detailed list of tagged words found in English translations");
	fprintf($SONGS_English_index_full_txt,"%s\r\n\n","// Date: ".date('Y-m-d',time()));
	fprintf($SONGS_English_index_tagged_txt,"%s\r\n","// List of tagged words found in English translations");
	fprintf($SONGS_English_index_tagged_txt,"%s\r\n\n","// Date: ".date('Y-m-d',time()));
	for($i = 0; $i < count($index_common); $i++) {
		$word = $index_common[$i];
		fprintf($SONGS_English_index_full_txt,"%s\r\n",str_replace('_',' ',$word)."\t".$index_common_list[$word]);
		$short_list = short_list($index_common_list[$word],3);
		fprintf($SONGS_English_index_tagged_txt,"%s\r\n",$word."\t".$short_list);
		}
	natcasesort($index_proper);
	$index_proper = array_values($index_proper);
	fprintf($SONGS_English_index_full_txt,"%s\r\n\n","\n// Detailed list of proper nouns found in English translations");
	fprintf($SONGS_English_index_proper_txt,"%s\r\n\n","// List of proper nouns found in English translations");
	fprintf($SONGS_English_index_proper_txt,"%s\r\n\n","// Date: ".date('Y-m-d',time()));
	for($i = 0; $i < count($index_proper); $i++) {
		$word = $index_proper[$i];
		fprintf($SONGS_English_index_full_txt,"%s\r\n",str_replace('_',' ',$word)."\t".$index_proper_list[$word]);
		$short_list = short_list($index_proper_list[$word],3);
		fprintf($SONGS_English_index_proper_txt,"%s\r\n",$word."\t".$short_list);
		}
	fclose($SONGS_English_index_tagged_txt);
	fclose($SONGS_English_index_proper_txt);
	fclose($SONGS_English_index_full_txt);
	}
	
echo "<a name=\"bottom\"></a>";
echo "<br />";
if($fix_translations) echo $i_changed." changes<br />";
if($errors > 0) echo "<font color=red>".$errors." ERRORS!</font><br />";
else echo "<font color=blue>No error</font><br />";
if($warnings > 0) echo "<font color=green>".$warnings." warnings!</font><br />";
else echo "<font color=blue>No warning</font><br />";
echo "<small>Processed in ".intval((time() - $time_start)/60)." minutes<br />";
echo date('F j, Y, H:i:s',time());
echo "<br /></small>[<a href=\"index_translations.php\">Return to current page</a>]<br />";
echo "[<a target=\"_blank\" href=\"admin.php\">Return to Admin page</a>]";
echo "<hr>";
echo "[<a href=\"#top\">Top of page</a>]";
echo "</body>";
echo "</html>";
?>