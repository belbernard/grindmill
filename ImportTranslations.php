<?php

// OBSOLETE: THIS SCRIPT WAS CREATED FOR A SINGLE RUN WHEN IMPORTING A LARGE SET OF TRANSLATIONS 
// CREATED IN THE FILEMAKER ENVIRONMENT

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
require_once("mytymes_replace_array.php");

$imax = $errors = $changes = 0;
$user = "Asha";
	
// Read translations_pending.txt file
$number_pending_translations = 0;
$pending_translation = array();
$pending_name = "translations_pending.txt";
$pending_file = @fopen($pending_name,"rb");
if($pending_file == FALSE) {
	echo "ERROR: ‘".$pending_name."’ is missing!";
	die();
	}
else {
	echo "<font color=blue>Reading ‘".$pending_name."’</font><br />";
	$i = 0;
	while(!feof($pending_file)) {
		$line = fgets($pending_file);
		$line = trim($line);
		if($line == '') continue;
		$song_id = intval($line);
		if(isset($pending_translation[$song_id])) {
			echo "<font color=red>ERROR: duplicate value: </font>‘".$song_id."’<br />";
			$errors++;
			}
		$pending_translation[$song_id] = TRUE;
		$number_pending_translations++;
		}
	}

$rule = ReadRewriteRules("english"); 

$liste = "last_translations.tab";
$liste_file = fopen($liste,"rb");
if($liste_file == FALSE) {
	echo "ERROR: ‘last_translations.tab’ not found!";
	die();
	}
while(!feof($liste_file)) {
	$line = fgets($liste_file);
	$line = trim($line);
	if($line == '') continue;
	$imax++;
//	echo $line."<br />";
	$table = explode("\t",$line);
	$song_id = intval($table[0]);
	$class = $table[1];
	
	if(isset($pending_translation[$song_id]) AND $pending_translation[$song_id])
		$pending_translation[$song_id] = FALSE;
	
	$translation = $table[2];
	$translation = str_replace(chr(11),"<br />",$translation);
	
	$line2 = array();
	for($i=0; $i < strlen($translation); $i++) {
		$code = ord($translation[$i]);
		if(isset($mytymes_macos_replace[$code]) AND $mytymes_macos_replace[$code] <> '')
			$line2[$i] = $mytymes_macos_replace[$code];
		else $line2[$i] = $translation[$i];
		}
	$translation = implode($line2);
	
	$translation = fix_typo($translation,$song_id);
	$translation = apply_rules(TRUE,$translation,$rule);
	$translation = str_replace('_',' ',$translation);
			
	StoreSpelling($translation);
	
	$old_translation = transcription($song_id,"translation");
	if($translation <> '' AND $translation <> $old_translation) {
		// Enter translation change in table ‘SONGS’
		$query_update = "UPDATE ".BASE.".songs SET translation_english = \"".$translation."\" WHERE song_id = \"".$song_id."\"";
		$result_update = $bdd->query($query_update);
		if(!$result_update) {
			echo "<br /><font color=red>ERROR modifying table:</font> ".$query_update."<br />";
			die();
			}
		$result_update->closeCursor();
		$changes++;
		echo $song_id."<br />";
		echo "OLD = ".$old_translation."<br />";
		echo "NEW = ".$translation."<br /><br />";
		
		// Enter this change in table ‘translations’ if it is new
		$query = "SELECT version, text FROM ".BASE.".translations WHERE song_id = \"".$song_id."\" ORDER BY version DESC LIMIT 1";
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
			if($translation == '') $translation = '~';
			if($version == 1 AND trim($old_translation) <> '') {
				$query = "INSERT INTO ".BASE.".translations (song_id, version, text, login) VALUES (\"".$song_id."\",\"0\",\"".$old_translation."\",\"\")";
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
			if(!$result) {
				echo "<br /><font color=red>".$query."<br />";
				echo "ERROR: FAILED</font>";
				die();
				}
			$result->closeCursor();
			}
		}
	else {
	/*	echo "OLD = ".$old_translation."<br />";
		echo "NEW = ".$translation."<br /><br />"; */
		}	
	}
fclose($liste_file);
echo "-------------<br />";
echo $imax." records<br />";
echo $changes." changes<br />";
if($errors > 0) echo "<font color=red>".$errors." ERRORS!</font><br />";
else echo "<font color=blue>No error</font><br />";
echo "Missing translations:";
foreach($pending_translation as $song_id => $status) {
	if($status) echo " <a target=\"_blank\" href=\"http://ccrss.org/database/songs.php?song_id=".$song_id."\">".$song_id."</a>";
	}
?>
