<?php
// session_start();
require_once("_base_urls.php");
require_once("_relier_edit.php");
require_once("_tasks.php");

ini_set('max_execution_time',600);
ini_set('memory_limit','300M');
// ini_set('error_reporting', E_ALL);

$name = "Replace";
$canonic_url = '';
require_once("_header.php");

echo "<h2>Replace procedures</h2>";

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

$query_set = "SET SQL_BIG_SELECTS=1"; // Added 21 June 2018
$bdd->query($query_set);

// First clean-up old records
$months = 36;
$create_time = time() - (3600 * 24 * 30 * $months);
$create_date = date('Y-m-d\TH:i:s',$create_time);
$query = "SELECT id FROM ".BASE.".replace_operations WHERE date < \"".$create_date."\"";
$result = $bdd->query($query);
$n = $result->rowCount();
if($n > 0) {
	while($ligne = $result->fetch()) {
		$id_replacement = $ligne['id'];
		$query_delete = "DELETE FROM ".BASE.".replace_records WHERE replace_id = \"".$id_replacement."\"";
		$result_delete = $bdd->query($query_delete);
		$result_delete->closeCursor();
		}
	$query_delete = "DELETE FROM ".BASE.".replace_operations WHERE date < \"".$create_date."\"";
	$result_delete = $bdd->query($query_delete);
	$result_delete->closeCursor();
	}
$result->closeCursor();
	
$list_old = '';
if(isset($_GET['list'])) {
	$list_old = $_GET['list'];
	}

// echo "<h3>Replace text in translations</h3>";

$search_english = $search_devanagari = $replace_english = $replace_devanagari = $devanagari = $roman = $semantic_class_id = '';

if(isset($_POST['search_english'])) {
	if(isset($_POST['search_english'])) $search_english = reshape_entry($_POST['search_english']);
	if(isset($_POST['replace_english'])) $replace_english = reshape_entry($_POST['replace_english']);
	if(isset($_POST['devanagari'])) $devanagari = reshape_entry($_POST['devanagari']);
	if(isset($_POST['roman'])) $roman = reshape_entry($_POST['roman']);
	$forgood = $_POST['forgood'];
	$good_query = TRUE;
	echo "<span style=\"color:red;\">";
	if(strlen($search_english) < 3) {
		echo "Search string ‘".$search_english."’ is too short.<br />";
		$good_query = FALSE;
		}
	else {
		if(FALSE AND strlen($replace_english) < 3) {
			echo "Replace string ‘".$replace_english."’ is too short.<br />";
			$good_query = FALSE;
			}
		else {
			if($devanagari == '' AND $roman == '') {
				echo "Transcription context is missing.<br />";
				$good_query = FALSE;
				}
			else {
				if(isset($_POST['semantic_class_id'])) $semantic_class_id = trim($_POST['semantic_class_id']);
				if(strlen($semantic_class_id) <> '') {
					$query = "SELECT semantic_class_id FROM ".BASE.".classification WHERE semantic_class_id LIKE \"".$semantic_class_id."%\" LIMIT 1";
					$result = $bdd->query($query);
					$n = $result->rowCount();
					$result->closeCursor();
					if($n == 0) {
						echo "Semantic class ‘".$semantic_class_id."…’ does not exist.<br />";
						$good_query = FALSE;
						}
					}
				}
			}
		}
	echo "</span>";
	if($good_query) {
		$rule = ReadRewriteRules("english");
		$context = '';
		if($devanagari <> '') {
			$context = "S.devanagari LIKE \"%".$devanagari."%\"";
			if($roman <> '') $context .= " OR ";
			}
		if($roman <> '') $context .= "S.roman_devanagari LIKE \"%".$roman."%\"";
//		$bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$query = "SELECT DISTINCT(S.song_id), S.devanagari, S.roman_devanagari, S.translation_english, S.semantic_class_id FROM ".BASE.".songs AS S, ".BASE.".workset AS W WHERE (".$context.") AND (S.translation_english LIKE \"%".$search_english."%\" OR (W.song_id = S.song_id AND W.translation LIKE \"%".$search_english."%\"))";
		$query_nb = "SELECT COUNT(DISTINCT(S.song_id)) FROM ".BASE.".songs AS S, ".BASE.".workset AS W WHERE (".$context.") AND (S.translation_english LIKE \"%".$search_english."%\" OR (W.song_id = S.song_id AND W.translation LIKE \"%".$search_english."%\"))";
		if($semantic_class_id <> '') {
			$query .= " AND S.semantic_class_id LIKE \"".$semantic_class_id."%\" ";
			$query_nb .= " AND S.semantic_class_id LIKE \"".$semantic_class_id."%\" ";
			}
	//	if(is_super_admin($login)) echo "<small>".$query."</small><br />";
	//	if(is_super_admin($login)) echo "<small>".$query_nb."</small><br />";
		$result = $bdd->query($query_nb);
		$nb = $result->fetchColumn();
		$result->closeCursor();
	//	$nb = 45;
		$show_number = 10;
		if($nb < 10) $show_number = $nb;
		if($forgood == "no") {
			$query_limit = $query." LIMIT 10";
			$result = $bdd->query($query_limit);
			if(($nb) > 0) {
				echo "<p><b><span style=\"color:red;\">➡ </span>Examples of replacements (showing ".$show_number." out of ".($nb )." occurrences found):</b></p>";
				echo "<table>";
				while($ligne = $result->fetch()) {
					$song_id = $ligne['song_id'];
					$devanagari_this_song = $ligne['devanagari'];
					$roman_this_song = $ligne['roman_devanagari'];
					$translation_english = $ligne['translation_english'];
					$semantic_class_id_this_song = $ligne['semantic_class_id'];
					$query_workset = "SELECT set_id, translation FROM ".BASE.".workset WHERE song_id = \"".$song_id."\"";
					$result_workset = $bdd->query($query_workset);
					$ligne_workset = $result_workset->fetch();
					$result_workset->closeCursor();
					$set_id = $ligne_workset['set_id'];
					$workset_translation = $ligne_workset['translation'];
					echo "<tr>";
					echo "<td class=\"tight\" style=\"white-space:nowrap;\">#".song($song_id,$song_id)."<br /><small>".$semantic_class_id_this_song;
					if($set_id > 0 AND $workset_translation <> '')
						echo "<br />Workset #".$set_id;
					echo "</small></td>";
					echo "<td class=\"tight\" style=\"white-space:nowrap;\">";
					echo $devanagari_this_song."<br />".$roman_this_song;
					echo "</td>";
					echo "<td class=\"tight\">";
					if($workset_translation <> '' AND $workset_translation <> $translation_english) {
						echo "In workset:<br />".$workset_translation;
						$new_translation = str_ireplace($search_english,$replace_english,$workset_translation);
						$translation_english = spelling_marks('en',$translation_english,"red");
						echo "<br /><br />In SONGS table:<br /><i>".$translation_english."</i>";
						}
					else {
						$new_translation = str_ireplace($search_english,$replace_english,$translation_english);
						$translation_english = spelling_marks('en',$translation_english,"red");
						echo $translation_english;
						}
					echo "</td>";
					$new_translation = fix_uppercase($new_translation,"<br />");
					$new_translation = fix_typo($new_translation,$song_id);
					$new_translation = apply_rules(TRUE,TRUE,$new_translation,$rule);
					$new_translation = str_replace('_',' ',$new_translation);
					$new_translation = str_replace("||","’",$new_translation);
					$new_translation = spelling_marks('en',$new_translation,"red");
					echo "<td class=\"tight\">";
					echo "<p><b>➡</b></p>";
					echo "</td>";
					echo "<td class=\"tight\" style=\"color:brown !important;\">";
					echo $new_translation;
					echo "</td>";
					echo "</tr>";
					}
				$result->closeCursor();
				echo "</table>";
				echo "<form name=\"replace_translation\" method=\"post\" action=\"replace.php\" enctype=\"multipart/form-data\">";
				echo "<input type=\"hidden\" name=\"forgood\" value=\"yes\">";
				echo "<input type=\"submit\" name=\"cancel\" class=\"button\" value=\"CANCEL\">";
				echo "&nbsp;&nbsp;<input type=\"submit\" class=\"button\" name=\"replace_a_few\" value=\"REPLACE ONLY THESE ".$show_number." OCCURRENCES\">";
				if($nb > 3000)
					echo "&nbsp;&nbsp;<input type=\"submit\" class=\"button\" value=\"OK, REPLACE 3000 OUT OF ".$nb." OCCURRENCES - BE CAREFUL!\">";
				else
					echo "&nbsp;&nbsp;<input type=\"submit\" class=\"button\" value=\"OK, REPLACE ".$nb." OCCURRENCES - BE CAREFUL!\">";
				echo "<input type=\"hidden\" name=\"search_english\" value=\"".$search_english."\">";
				echo "<input type=\"hidden\" name=\"replace_english\" value=\"".$replace_english."\">";
				echo "<input type=\"hidden\" name=\"devanagari\" value=\"".$devanagari."\">";
				echo "<input type=\"hidden\" name=\"roman\" value=\"".$roman."\">";
				echo "<input type=\"hidden\" name=\"semantic_class_id\" value=\"".$semantic_class_id."\">";
				
				echo "</form>";
				echo "<hr>";
				}
			else {
				echo "<p><span style=\"color:red;\">No occurrence found</span></p>";
				$result->closeCursor();
				}
			}
		else {
			if(!isset($_POST['cancel'])) {
				if(isset($_POST['replace_a_few'])) {
					$query = $query." LIMIT 10";
					echo "<p><b>Replacing only a few occurrences:</b></p>";
					}
				else {
					$query = $query." LIMIT 3000";
					if($nb < 3000)
						echo "<p><b>Replacing ".$nb." occurrences:</b></p>";
					else
						echo "<p><b>Replacing 3000 occurrences:</b></p>";
					}
				$id_replacement = store_replacement("english",$search_english,$replace_english,$devanagari,$roman,$semantic_class_id);
		//		echo "id_replacement = ".$id_replacement."<br />";
				$result = $bdd->query($query);
				while($ligne = $result->fetch()) {
					$song_id = $ligne['song_id'];
					$translation_english = $ligne['translation_english'];
					$new_translation = str_ireplace($search_english,$replace_english,$translation_english);
					$new_translation = fix_uppercase($new_translation,"<br />");
					$new_translation = fix_typo($new_translation,$song_id);
					$new_translation = apply_rules(TRUE,TRUE,$new_translation,$rule);
					$new_translation = str_replace('_',' ',$new_translation);
					$new_translation = str_replace("||","’",$new_translation);
					
					$set_id = 0;
					$query_workset = "SELECT set_id, translation FROM ".BASE.".workset WHERE song_id = \"".$song_id."\"";
					$result_workset = $bdd->query($query_workset);
					$ligne_workset = $result_workset->fetch();
					$result_workset->closeCursor();
					$set_id = $ligne_workset['set_id'];
					$workset_translation = $ligne_workset['translation'];
					
					$translation_english_display = spelling_marks('en',$translation_english,"red");
					$new_translation_display = spelling_marks('en',$new_translation,"red");
					$workset_translation_display = spelling_marks('en',$workset_translation,"red");
					echo "<p>Song #".song($song_id,$song_id);
					if($translation_english_display <> $new_translation_display) echo "<br /><small>".$translation_english_display."<br />➡ ".$new_translation_display."</small>";
					if($set_id > 0 AND $workset_translation <> '' AND $workset_translation_display <> $new_translation_display) {
						echo "<br /><i>… also workset #".$set_id.":</i><br /><small>".$workset_translation_display."<br />➡ ".$new_translation_display."</small>";
						}
					echo "</p>";
					$query_insert = "INSERT INTO ".BASE.".replace_records (song_id, replace_id) VALUES (\"".$song_id."\",\"".$id_replacement."\")";
					$result_insert = $bdd->query($query_insert);
					$result_insert->closeCursor();
					
					// Change translation in SONGS
					$query_update = "UPDATE ".BASE.".songs SET translation_english = \"".$new_translation."\", login = \"".$login."\" WHERE song_id = \"".$song_id."\" AND translation_english <> \"\" AND translation_english <> \"".$new_translation."\"";
					$result_update = $bdd->query($query_update);
					if(!$result_update) {
						echo "<br /><span style=\"color:red;\">".$query_update."<br />";
						echo "ERROR: FAILED</span>";
						die();
						}
					$result_update->closeCursor();
					// Change translation in WORKSET
					$query_update = "UPDATE ".BASE.".workset SET translation = \"".$new_translation."\" WHERE song_id = \"".$song_id."\" AND translation <> \"\" AND translation <> \"".$new_translation."\"";
					$result_update = $bdd->query($query_update);
				//	echo $query_update."<br />";
					if(!$result_update) {
						echo "<br /><span style=\"color:red;\">".$query_update."<br />";
						echo "ERROR: FAILED</span>";
						die();
						}
					$result_update->closeCursor();
					// Change translation in TRANSLATIONS
					$query_translation = "SELECT version, text FROM ".BASE.".translations WHERE song_id = \"".$song_id."\" ORDER BY version DESC";
					$result_translation = $bdd->query($query_translation);
					$n_translation = $result_translation->rowCount();
					$preceding_text = '';
					if($n_translation == 0) $version = 1;
					else {
						$ligne_translation = $result_translation->fetch();
						$version = 1 + $ligne_translation['version'];
						$preceding_text = $ligne_translation['text'];
						}
					$result_translation->closeCursor();
					if($new_translation <> $preceding_text) {
						if($new_translation == '') $new_translation = '~';
						if($version == 1) {
							$query_insert = "INSERT INTO ".BASE.".translations (song_id, version, text, login) VALUES (\"".$song_id."\",\"0\",\"".$translation_english."\",\"\")";
							$result_insert = $bdd->query($query_insert);
							if(!$result_insert) {
								echo "<br /><span style=\"color:red;\">".$query_insert."<br />";
								echo "ERROR: FAILED</span>";
								die();
								}
							$result_insert->closeCursor();
							}
						$query_insert = "INSERT INTO ".BASE.".translations (song_id, version, text, login) VALUES (\"".$song_id."\",\"".$version."\",\"".$new_translation."\",\"".$login."\")";
						$result_insert = $bdd->query($query_insert);
						if(!$result_insert) {
							echo "<br /><span style=\"color:red;\">".$query_insert."<br />";
							echo "ERROR: FAILED</span>";
							die();
							}
						$result_insert->closeCursor();
						}
					}
				$result->closeCursor();
				echo "<hr>";
				}
			}
		}
	else echo "<p><span style=\"color:red;\">Bad query</span></p><hr>";
	}
else if(isset($_POST['search_devanagari'])) {
	if(isset($_POST['search_devanagari'])) $search_devanagari = reshape_entry($_POST['search_devanagari']);
	if(isset($_POST['replace_devanagari'])) $replace_devanagari = reshape_entry($_POST['replace_devanagari']);
	if(isset($_POST['devanagari'])) $devanagari = reshape_entry($_POST['devanagari']);
	if(isset($_POST['roman'])) $roman = reshape_entry($_POST['roman']);
	$forgood = $_POST['forgood'];
	$good_query = TRUE;
	echo "<span style=\"color:red;\">";
	if(strlen($search_devanagari) < 3) {
		echo "Search string ‘".$search_devanagari."’ is too short.<br />";
		$good_query = FALSE;
		}
	else {
		if(strlen($replace_devanagari) < 3) {
			echo "Replace string ‘".$replace_devanagari."’ is too short.<br />";
			$good_query = FALSE;
			}
		else {
			if(isset($_POST['semantic_class_id'])) $semantic_class_id = trim($_POST['semantic_class_id']);
			if(strlen($semantic_class_id) <> '') {
				$query = "SELECT semantic_class_id FROM ".BASE.".classification WHERE semantic_class_id LIKE \"".$semantic_class_id."%\" LIMIT 1";
				$result = $bdd->query($query);
				$n = $result->rowCount();
				$result->closeCursor();
				if($n == 0) {
					echo "Semantic class ‘".$semantic_class_id."…’ does not exist.<br />";
					$good_query = FALSE;
					}
				}
			}
		}
	echo "</span>";
	if($good_query) {
		$context = '';
		if($devanagari <> '') {
			$context = "devanagari LIKE \"%".$devanagari."%\"";
			if($roman <> '') $context .= " OR ";
			}
		if($roman <> '') $context .= "roman_devanagari LIKE \"%".$roman."%\"";
		$query = "SELECT song_id, devanagari, roman_devanagari, semantic_class_id FROM ".BASE.".songs WHERE devanagari LIKE \"%".$search_devanagari."%\" ";
		if($context <> '') $query .= " AND (".$context.")";
		if($semantic_class_id <> '') $query .= " AND semantic_class_id LIKE \"".$semantic_class_id."%\" ";
	//	if(is_super_admin($login)) echo "<small>".$query."</small><br />";
		$result = $bdd->query($query);
		$nb = $result->rowCount();
		$show_number = 5;
		if($nb < 5) $show_number = $nb;
		$result->closeCursor();
		if($forgood == "no") {
			$query_limit = $query." LIMIT 5";
			$result = $bdd->query($query_limit);
			if($nb > 0) {
				echo "<p><b><span style=\"color:red;\">➡ </span>Examples of replacements (showing ".$show_number." out of ".$nb." occurrences found):</b></p>";
				echo "<table>";
				while($ligne = $result->fetch()) {
					$song_id = $ligne['song_id'];
					$devanagari_this_song = $ligne['devanagari'];
					$roman_this_song = $ligne['roman_devanagari'];
					$semantic_class_id_this_song = $ligne['semantic_class_id'];
					$new_transcription = str_ireplace($search_devanagari,$replace_devanagari,$devanagari_this_song);
					$query_workset = "SELECT set_id, translation FROM ".BASE.".workset WHERE song_id = \"".$song_id."\"";
					$result_workset = $bdd->query($query_workset);
					$ligne_workset = $result_workset->fetch();
					$result_workset->closeCursor();
					$set_id = $ligne_workset['set_id'];
					echo "<tr>";
					echo "<td class=\"tight\" style=\"white-space:nowrap;\">#".song($song_id,$song_id)."<br /><small>".$semantic_class_id_this_song;
					if($set_id > 0) echo "<br />Workset #".$set_id;
					echo "</small></td>";
					echo "<td class=\"tight\" style=\"white-space:nowrap;\">";
					$devanagari_this_song = spelling_marks('mr',$devanagari_this_song,"red");
					$roman_this_song = spelling_marks('ro',$roman_this_song,"red");
					echo $devanagari_this_song."<br />".$roman_this_song;
					echo "</td>";
					echo "<td class=\"tight\">";
					echo "<p><b>➡</b></p>";
					echo "</td>";
					echo "<td class=\"tight\" style=\"color:brown !important;\">";
					echo $new_transcription;
					echo "</td>";
					echo "</tr>";
					}
				echo "</table>";
				$result->closeCursor();
				echo "<form name=\"replace_transcription\" method=\"post\" action=\"replace.php\" enctype=\"multipart/form-data\">";
				echo "<input type=\"hidden\" name=\"forgood\" value=\"yes\">";
				echo "<input type=\"submit\" name=\"cancel\" class=\"button\" value=\"CANCEL\">";
				echo "&nbsp;&nbsp;<input type=\"submit\" class=\"button\" name=\"replace_a_few\" value=\"REPLACE ONLY THESE ".$show_number." OCCURRENCES\">";
				echo "&nbsp;&nbsp;<input type=\"submit\" class=\"button\" value=\"OK, REPLACE ".$nb." OCCURRENCES - BE CAREFUL!\">";
				echo "<input type=\"hidden\" name=\"search_devanagari\" value=\"".$search_devanagari."\">";
				echo "<input type=\"hidden\" name=\"replace_devanagari\" value=\"".$replace_devanagari."\">";
				echo "<input type=\"hidden\" name=\"devanagari\" value=\"".$devanagari."\">";
				echo "<input type=\"hidden\" name=\"roman\" value=\"".$roman."\">";
				echo "<input type=\"hidden\" name=\"semantic_class_id\" value=\"".$semantic_class_id."\">";
				echo "</form>";
				echo "<hr>";
				}
			else {
				echo "<p><span style=\"color:red;\">No occurrence found</span></p>";
				$result->closeCursor();
				}
			}
		else {
			if(!isset($_POST['cancel'])) {
				if(isset($_POST['replace_a_few'])) {
					$query = $query." LIMIT 5";
					echo "<p><b>Replacing only ".$show_number." occurrences:</b></p>";
					}
				else echo "<p><b>Replacing ".$nb." occurrences:</b></p>";
				$result = $bdd->query($query);
				$i = 0;
				$id_replacement = store_replacement("devanagari",$search_devanagari,$replace_devanagari,$devanagari,$roman,$semantic_class_id);
				while($ligne = $result->fetch()) {
					$i++;
					$song_id = $ligne['song_id'];
					$devanagari_this_song = $ligne['devanagari'];
					$new_transcription = str_ireplace($search_devanagari,$replace_devanagari,$devanagari_this_song);
					$devanagari_this_song_display = spelling_marks('mr',$devanagari_this_song,"red");
					$new_transcription_display = spelling_marks('mr',$new_transcription,"red");
					echo "<p>Song #".song($song_id,$song_id)."<br />".$devanagari_this_song_display."<br />➡ ".$new_transcription_display."</p>";
					
					$query_insert = "INSERT INTO ".BASE.".replace_records (song_id, replace_id) VALUES (\"".$song_id."\",\"".$id_replacement."\")";
					$result_insert = $bdd->query($query_insert);
					$result_insert->closeCursor();
					
					// Change transcription in SONGS
					$query_update = "UPDATE ".BASE.".songs SET devanagari = \"".$new_transcription."\", login = \"".$login."\" WHERE song_id = \"".$song_id."\" AND devanagari <> \"".$new_transcription."\"";
					$result_update = $bdd->query($query_update);
					if(!$result_update) {
						echo "<br /><span style=\"color:red;\">".$query_update."<br />";
						echo "ERROR: FAILED</span>";
						die();
						}
					$result_update->closeCursor();
					// Change transcription in WORKSET
					$query_update = "UPDATE ".BASE.".workset SET devanagari = \"".$new_transcription."\", login = \"".$login."\" WHERE song_id = \"".$song_id."\" AND devanagari <> \"\" AND devanagari <> \"".$new_transcription."\"";
					$result_update = $bdd->query($query_update);
					// echo $query_update."<br />";
					if(!$result_update) {
						echo "<br /><span style=\"color:red;\">".$query_update."<br />";
						echo "ERROR: FAILED</span>";
						die();
						}
					$result_update->closeCursor();
					// Change transcription in SONG METADATA
					$query_update = "UPDATE ".BASE.".song_metadata SET devanagari = \"".$new_transcription."\", login = \"".$login."\" WHERE song_id = \"".$song_id."\" AND devanagari <> \"\" AND devanagari <> \"".$new_transcription."\"";
					$result_update = $bdd->query($query_update);
					// echo $query_update."<br />";
					if(!$result_update) {
						echo "<br /><span style=\"color:red;\">".$query_update."<br />";
						echo "ERROR: FAILED</span>";
						die();
						}
					$result_update->closeCursor();
					}
				$result->closeCursor();
				echo "<hr>";
				}
			}
		}
	else echo "<p><span style=\"color:red;\">Bad query</span></p><hr>";
	}
else {
	echo "<p>Replacements cannot be undone.<br />In order to make a secure choice, you must include contextual information restricting the target of replacements:</p>";
	echo "<ul>";
	echo "<li>text in the Devanagari or Roman transcription, or both (compulsory)</li>";
	echo "<li>semantic class (optional)</li>";
	echo "</ul>";
	echo "<p>Example: replace “dark-complexioned” with “wheat-complexioned” in songs whose Devanagari transcription contains “सावळी” or Roman transcription contains “sāvaḷī”</p><hr>";
	}
echo "<form name=\"replace_translation\" method=\"post\" action=\"replace.php\" enctype=\"multipart/form-data\">";

echo "<h3>Replace text in English translations</h3>";
echo "<p>";
echo "REPLACE&nbsp;";
echo "<input type=\"text\" name=\"search_english\" size=\"60\" value=\"".$search_english."\">";
echo "&nbsp;&nbsp;&nbsp;<input type=\"submit\" class=\"button\" value=\"TRY REPLACING ENGLISH ➡ no risk, you will be asked to confirm\">";
echo "</p><p>___&nbsp;WITH&nbsp;";
echo "<input type=\"text\" name=\"replace_english\" size=\"60\" value=\"".$replace_english."\"> <span style=\"color:blue;\">in English translations</span>";
echo "</p>";
echo "<p>WHERE Devanagari transcription contains <input type=\"text\" name=\"devanagari\" size=\"40\" value=\"".$devanagari."\"> <span style=\"color:blue;\">e.g. “सावळी”</span></p>";
echo "<p>___&nbsp;OR/AND Roman transcription contains <input type=\"text\" name=\"roman\" size=\"40\" value=\"".$roman."\"> <span style=\"color:blue;\">e.g. “sāvaḷī”</span></p>";
echo "<p>AND (optional):</p>";
echo "<ul>";
echo "<li>Semantic class starts with <input type=\"text\" name=\"semantic_class_id\" size=\"12\" value=\"".$semantic_class_id."\"> <span style=\"color:blue;\">e.g. ‘A01-01’</span></li>";
echo "</ul>";
echo "<input type=\"hidden\" name=\"forgood\" value=\"no\">";
echo "</form><hr>";

echo "<h3>Replace text in Devanagari transcriptions</h3>";
echo "<form name=\"replace_devanagari\" method=\"post\" action=\"replace.php\" enctype=\"multipart/form-data\">";
echo "<p>";
echo "REPLACE&nbsp;";
echo "<input type=\"text\" name=\"search_devanagari\" size=\"60\" value=\"".$search_devanagari."\">";
echo "&nbsp;&nbsp;&nbsp;<input type=\"submit\" class=\"button\" value=\"TRY REPLACING DEVANAGARI ➡ no risk, you will be asked to confirm\">";
echo "</p><p>___&nbsp;WITH&nbsp;";
echo "<input type=\"text\" name=\"replace_devanagari\" size=\"60\" value=\"".$replace_devanagari."\"> <span style=\"color:blue;\">in Devanagari transcriptions</span>";
echo "</p>";
echo "<p>WHERE Devanagari transcription contains <input type=\"text\" name=\"devanagari\" size=\"40\" value=\"".$devanagari."\"> <span style=\"color:blue;\">e.g. “सावळी”</span></p>";
echo "<p>___&nbsp;OR/AND Roman transcription contains <input type=\"text\" name=\"roman\" size=\"40\" value=\"".$roman."\"> <span style=\"color:blue;\">e.g. “sāvaḷī”</span></p>";
echo "<p>AND (optional):</p>";
echo "<ul>";
echo "<li>Semantic class starts with <input type=\"text\" name=\"semantic_class_id\" size=\"12\" value=\"".$semantic_class_id."\"> <span style=\"color:blue;\">e.g. ‘A01-01’</span></li>";
echo "</ul>";
echo "<input type=\"hidden\" name=\"forgood\" value=\"no\">";
echo "</form><hr>";

if($list_old <> '') {
	echo "<h3 id=\"list\">List of replacements during the past ".$months." months</h3>";
	$query = "SELECT * FROM ".BASE.".replace_operations ORDER BY date";
	$result = $bdd->query($query);
	$n = $result->rowCount();
	if($n > 0) {
		echo "<span style=\"color:red;\">➡</span> <a href=\"replace.php\">Hide this list of replacements</a>";
		echo "<table>";
		echo "<tr><th>#</th><th>Date</th><th>Login</th><th>Field</th><th>Old</th><th></th><th>New</th><th colspan=\"3\">Context</th><th>Songs</th></tr>";
		while($ligne = $result->fetch()) {
			$id_replacement = $ligne['id'];
			$query_records = "SELECT * FROM ".BASE.".replace_records WHERE replace_id = \"".$id_replacement."\"";
			$result_records = $bdd->query($query_records);
			$number_records = $result_records->rowCount();
			$song_list = '';
			while($ligne_records = $result_records->fetch()) {
				if($song_list <> '') $song_list .= ",";
				$song_list .= $ligne_records['song_id'];
				}
			$result_records->closeCursor();
			echo "<tr>";
			echo "<td class=\"tight\">".$id_replacement."</td>";
			echo "<td class=\"tight\">".$ligne['date']."</td>";
			echo "<td class=\"tight\">".$ligne['login']."</td>";
			echo "<td class=\"tight\">".$ligne['field']."</td>";
			echo "<td class=\"tight\">".$ligne['old_string']."</td>";
			echo "<td>➡</td>";
			echo "<td class=\"tight\">".$ligne['new_string']."</td>";
			echo "<td class=\"tight\">".$ligne['where_devanagari']."</td>";
			echo "<td class=\"tight\">".$ligne['where_roman']."</td>";
			echo "<td class=\"tight\">".$ligne['where_semantic']."</td>";
			echo "<td class=\"tight\">".$number_records." (<a target=\"_blank\" href=\"edit-songs.php?song_list=".$song_list."\">edit</a>)</td>";
			echo "</tr>";
			}
		echo "</table>";
		}
	else echo "No replacement found during this period…";
	$result->closeCursor();
	} 
else {
	echo "<span style=\"color:red;\">➡</span> <a href=\"replace.php?list=all#list\">List all replacements<a>";
	}

echo "</body>";
echo "</html>";

function store_replacement($field,$old_string,$new_string,$devanagari,$roman,$semantic_class_id) {
	global $bdd, $login;
	$query = "SELECT id FROM ".BASE.".replace_operations WHERE field = \"".$field."\" AND old_string = \"".$old_string."\" AND new_string = \"".$new_string."\" AND where_devanagari = \"".$devanagari."\" AND where_roman = \"".$roman."\" AND where_semantic = \"".$semantic_class_id."\"";
	$result = $bdd->query($query);
	$n = $result->rowCount();
	if($n > 0) {
		$ligne = $result->fetch();
		return $ligne['id'];
		}
	$result->closeCursor();
	$local_time = get_the_local_time();
	$create_date = $local_time['local-machine-time'];
	$query_insert = "INSERT INTO ".BASE.".replace_operations (date, login, field, old_string, new_string, where_devanagari, where_roman, where_semantic) VALUES (\"".$create_date."\",\"".$login."\", \"".$field."\",\"".$old_string."\",\"".$new_string."\",\"".$devanagari."\",\"".$roman."\",\"".$semantic_class_id."\")";
	$result_insert = $bdd->query($query_insert);
	$result_insert->closeCursor();
	$query = "SELECT id FROM ".BASE.".replace_operations ORDER BY id DESC LIMIT 1";
	$result = $bdd->query($query);
	$n = $result->rowCount();
	if($n > 0) {
		$ligne = $result->fetch();
		return $ligne['id'];
		}
	$result->closeCursor();
	return '';
	}
?>