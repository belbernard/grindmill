<?php
// session_start();
function fix_song_translations($id_min,$idmax,$set_id,$translation_correction) {
	global $bdd,$i_changed,$j,$jj;
	set_time_limit(5000);
//	echo "@@@<br />";
	if($set_id == 0)
		$query = "SELECT song_id, translation_english FROM ".BASE.".songs WHERE translation_english <> \"\" AND song_id >= \"".$id_min."\" AND song_id <= \"".$idmax."\"";
	else {
		$query = "SELECT song_id, translation FROM ".BASE.".workset WHERE set_id = \"".$set_id."\"";
//		echo $query."<br />";
		}
	$result = $bdd->query($query);
//	echo $query."<br />";
	while($ligne = $result->fetch()) {
		$id = $ligne['song_id'];
		if($set_id == 0) $old_translation = $ligne['translation_english'];
		else {
			$old_translation = $ligne['translation'];
			if($old_translation == '')
				$old_translation = transcription($id,"translation");
			}
		$translation = $old_translation; 
		$translation = apply_rules(TRUE,TRUE,$translation,$translation_correction);
		$translation = str_replace('_',' ',$translation);
		$translation = str_replace("||","’",$translation);
		$translation = fix_typo($translation,0);
		if(str_replace('*','',$translation) <> str_replace('*','',$old_translation)) {
			$i_changed++;
			if($set_id > 0) {
//				$query_update = "UPDATE ".BASE.".workset SET translation = \"".$translation."\" WHERE song_id = \"".$id."\" AND set_id = \"".$set_id."\" AND translation <> \"\"";
				$query_update = "UPDATE ".BASE.".workset SET translation = \"".$translation."\" WHERE song_id = \"".$id."\" AND set_id = \"".$set_id."\"";
				$result_update = $bdd->query($query_update);
				if(!$result_update) {
					echo "<br /><font color=red>".$query_update."<br />";
					echo "ERROR: FAILED</font>";
					die();
					}
				$result_update->closeCursor();
				continue;
				}
	//		if($set_id == 0) { 
				$query_update = "UPDATE ".BASE.".songs SET translation_english = \"".$translation."\" WHERE song_id = \"".$id."\"";
				$result_update = $bdd->query($query_update);
				if(!$result_update) {
					echo "<br /><font color=red>".$query_update."<br />";
					echo "ERROR: FAILED</font>";
					die();
					}
				$result_update->closeCursor();
	//			}
			$query2 = "SELECT version FROM ".BASE.".translations WHERE song_id = \"".$id."\" ORDER BY version DESC";
			$result2 = $bdd->query($query2);
			$n2 = $result2->rowCount();
			if($n2 > 0) {
				$ligne2 = $result2->fetch();
				$version = $ligne2['version'];
				$query_update = "UPDATE ".BASE.".translations SET text = \"".$translation."\" WHERE song_id = \"".$id."\" AND version = \"".$version."\"";
				$result_update = $bdd->query($query_update);
				if(!$result_update) {
					echo "<br /><font color=red>".$query_update."<br />";
					echo "ERROR: FAILED</font>";
					die();
					}
				$result_update->closeCursor();
				}
			$result2->closeCursor();
			}
		if(++$j > 100) {
		//	echo ".";
			$j = 0;
			if(++$jj > 50) {
			//	echo "<br />".$id;
				$jj = 0;
				}
			}
		}
	$result->closeCursor();
	return;
	}

function index_all_translations($translation_correction) {
	global $bdd,$index_common,$index_proper,$index_common_list,$index_proper_list;
	set_time_limit(1000);
	$query = "SELECT song_id, translation_english FROM ".BASE.".songs WHERE translation_english <> \"\"";
	$result = $bdd->query($query);
	$index_correction_english = IndexRewriteRules("english");
	while($ligne = $result->fetch()) {
		$id = $ligne['song_id'];
		$translation = $ligne['translation_english'];
		$fix_uppercase = TRUE;
		$index = increment_index('*',$translation,$id,$index_common,$index_proper,$index_common_list,$index_proper_list,$index_correction_english);
		$index_common = $index['index_common'];
		$index_proper = $index['index_proper'];
		$index_common_list = $index['index_common_list'];
		$index_proper_list = $index['index_proper_list'];
		if($index['bad'] <> '') {
			echo "<font color=red>*** ERROR: found indexed short word ‘</font>".$index['bad']."<font color=red>’ in English translation</font> ".str_replace("<br />"," / ",$translation)."<br />";
			$errors++;
			}
		}
	$result->closeCursor();
	return;
	}

function change_field($table,$field_name,$field_value,$id_field,$id_value) {
	global $bdd;
	$query = "UPDATE ".BASE.".".$table." SET ".$field_name." = \"".$field_value."\" WHERE ".$id_field." = \"".$id_value."\"";
//	echo $query."<br />";
	$result = $bdd->query($query);
	if(!$result) {
		echo "<br /><font color=red>ERROR modifying table:</font> ".$query."<br />";
		die();
		}
	$result->closeCursor();
	}

function increment_index($tag,$text,$id,$index_common,$index_proper,$index_common_list,$index_proper_list,$index_correction_english) {
	global $excluded_word,$warnings;
	set_time_limit(1000);
	$text = "§ ".$text;
	$text = str_replace(chr(11)," § ",$text);
	$text = str_replace(chr(13)," § ",$text);
	$text = str_replace("<br />"," § ",$text);
	$text = str_replace("/",' ',$text);
	$text = str_replace("?",' ',$text);
	$text = str_replace("!",' ',$text);
	$text = str_replace('(',' ',$text);
	$text = str_replace('(',' ',$text);
	do $text = str_replace(" *","*",$text,$count);
	while($count > 0);
	$text = str_replace(")*","*)",$text);
	$text = str_replace(",*","*,",$text);
	do $text = str_replace("**","*",$text,$count);
	while($count > 0);
	$text = str_replace(",",' ',$text);
	$text = str_replace("- "," ",$text);
	$text = str_replace(" -"," ",$text);
	$text = str_replace("—"," ",$text);
	$text = str_replace($tag,$tag." ",$text);
	$text = str_replace("’","'",$text);
	$text = str_replace("‘","'",$text);
	$text = str_replace('“',"'",$text);
	$text = str_replace('”',"'",$text);
	$text = str_replace('"',"'",$text);
	$text = str_replace("'s",'',$text);
	$text = str_replace("'".$tag,$tag."'",$text);
	do $text = str_replace("  ",' ',$text,$count);
	while($count > 0);
	$text = str_replace("§ ","§ €",$text);
	$table = explode(' ',$text);
	$result['bad'] = '';
	$seen1 = $seen2 = array();
	for($i = 0; $i < count($table); $i++) {
		$word = trim($table[$i]);
		if($word == '') continue;
		if(is_integer(strpos($word,$tag))) {
			// Picking up a tagged word
			$word = str_replace($tag,'',$word);
			$word = str_replace("'",'',$word);
			if($word == '') continue;
			if(is_integer(strpos($word,'€'))) {
				$word = str_replace('€','',$word);
		//		if($word == "Bakshi") echo $word." -> ".$index_correction_english[$word]."<br />";
				if(isset($index_correction_english[$word]))
					$word = $index_correction_english[$word];
				}
			$found = FALSE;
			for($j = 0; $j < count($index_common); $j++) {
				if(strcmp($index_common[$j],$word) == 0) {
					$found = TRUE;
					if(!isset($index_common_list[$word])) {
						echo "<font color=green>WARNING: undefined index_common_list[word]</font><br />";
						echo "text = “".$text."”<br />";
						$index_common_list[$word] = $id;
						$warnings++;
						}
					else {
						if(!isset($seen1[$word]))
							$index_common_list[$word] .= ", ".$id;
						$seen1[$word] = TRUE;
						}
					break;
					}
				}
			if(!$found) {
				if(strlen($word) < 3) $result['bad'] = $word;
				else {
				/*	if(FALSE AND $try_lowercase) {
						$word2 = lcfirst($word);
						$found = FALSE;
						for($j = 0; $j < count($index_common); $j++) {
							if(strcmp($index_common[$j],$word2) == 0) {
								$found = TRUE;
								if(!isset($seen1[$word2]))
									$index_common_list[$word2] .= ", ".$id;
								$seen1[$word2] = TRUE;
								break;
								}
							}
						if(!$found) {
							$index_common[$j] = $word;
							$index_common_list[$word] = $id;
							$seen1[$word] = TRUE;
							}
						} 
					else { */
						$index_common[$j] = $word;
						$index_common_list[$word] = $id;
						$seen1[$word] = TRUE;
				//		}
					}
				}
			continue; // Tagged words are not picked up as proper nouns
			}
		if($i == 0 OR trim($table[$i - 1]) == "§") continue;
		// Don't take the first word of a line
		$firstchar = substr($word,0,1);
		if(ctype_upper($firstchar)) {
			// Picking up a proper noun
			$word = str_replace("'",'',$word);
			$word = str_replace(',','',$word);
			$word = str_replace(')','',$word);
			if(strlen($word) < 4) continue;
			$found = FALSE;
			for($j = 0; $j < count($excluded_word); $j++) {
				if(strcasecmp($excluded_word[$j],$word) == 0) {
					$found = TRUE; break;
					}
				}
			if($found) continue;
			$found = FALSE;
			for($j = 0; $j < count($index_proper); $j++) {
				if(strcmp($index_proper[$j],$word) == 0) {
					$found = TRUE;
					if(!isset($index_proper_list[$word])) {
						echo "<font color=green>WARNING: undefined index_proper_list[word]</font><br />";
						echo "text = “".$text."”<br />";
						echo "id = ".$id." i = ".$i." "." j = ".$j." / ".count($index_proper)." “".$word."”<br />";
						$index_proper_list[$word] = $id;
						$warnings++;
						}
					else {
						if(!isset($seen2[$word]))
							$index_proper_list[$word] .= ", ".$id;
						$seen2[$word] = TRUE;
						}
					break;
					}
				}
			if(!$found) {
				$index_proper[$j] = $word;
				$index_proper_list[$word] = $id;
				$seen2[$word] = TRUE;
				}
			}		
		}
	$result['index_common'] = $index_common;
	$result['index_proper'] = $index_proper;
	$result['index_common_list'] = $index_common_list;
	$result['index_proper_list'] = $index_proper_list;
	return $result;
	}

function IndexRewriteRules($language) {
	// Creat index from database
	global $bdd;
	$index = array();
	$query = "SELECT * FROM ".BASE.".rewrite_rules WHERE language = \"".$language."\" ORDER BY id";
	$result = $bdd->query($query);
	if($result) {
		while($ligne = $result->fetch()) {
			$left_arg = trim($ligne['left_arg']);
			$right_arg = trim($ligne['right_arg']);
			if(strlen($left_arg) > 2 AND strlen($right_arg) > 2 AND !is_integer(strpos($left_arg,'*')) AND !is_integer(strpos($right_arg,'*')) AND !is_integer(strpos($right_arg,'§'))) {
				$index[$left_arg] = $right_arg;
		//		echo "‘".$left_arg."’ --> ‘".$index[$left_arg]."’<br />";
				}
			}
		}
	return $index;
	}
	
function LoadRewriteRules() {
	// Read from files and store in the database
	global $bdd;
	$query = "TRUNCATE ".BASE.".rewrite_rules ";
	$result = $bdd->query($query);
	if(!$result) {
		echo "<br /><font color=red>ERROR truncating table:</font> ".$query."<br />";
		die();
		}
	$result->closeCursor();
	echo "<blockquote><small>";
	$devanagari_correction = array();
	ReadDevanagariCorrections($devanagari_correction);
	$roman_correction = array();
	ReadRomanCorrections($roman_correction);
	$i = 0;
	$translation_correction_english = array();
//	$translation_correction_english = ReadTranslationCorrectionsEnglish($translation_correction_english,$i);
/*	$i = count($translation_correction_english);
	$translation_correction_english = ReadListOfTaggedWords($translation_correction_english,$i);
	echo "</blockquote></small>"; */
	update_tag_rules();
	return $translation_correction_english;
	}

function update_tag_rules() {
	// Take entries in 'glossary' to create rules in 'rewrite_rules'
	global $bdd;
	$test = FALSE;
/* Rule examples:
force_case
“ Ahev ” -> “ Ahev* ”
“ Ahev’s ” -> “ Ahev*’s ”
“ ahev ” -> “ Ahev* ”
“ ahev’s ” -> “ Ahev*’s ”

!force_case
“ Alphonso ” -> “ Alphonso* ”
“ Alphonso’s ” -> “ Alphonso*’s ”
“§ alphonso ” -> “§ Alphonso* ”
“§ alphonso’s” -> “§ Alphonso*’s”

force_case
“ Ambar Zumbar ” -> “ Ambar_Zumbar* ”
“ Ambar Zumbar’s ” -> “ Ambar_Zumbar*’s ”
“ ambar Zumbar ” -> “ Ambar_Zumbar* ”
“ ambar Zumbar’s” -> “ Ambar_Zumbar*’s”

!force_case
“ bahin ” -> “ bahin* ”
“ bahin’s ” -> “ bahin*’s ”

force_case
“ banyan ” -> “ banyan* ”
“ banyan’s ” -> “ banyan*’s ”
“ Banyan ” -> “ banyan* ”
“ Banyan’s ” -> “ banyan*’s ”
*/
	echo "Updating rewrite rules<br />";
	$n_rules1 = $n_rules2 = 0;
	$query = "TRUNCATE ".BASE.".rewrite_rules ";
//	$query = "DELETE FROM ".BASE.".rewrite_rules WHERE language = \"english\" AND type = \"tag\"";
	$result = $bdd->query($query);
	$result->closeCursor();
	$query = "SELECT * FROM ".BASE.".glossary WHERE specific_song_id = \"0\" AND specific_group_id = \"0\" AND specific_class_id = \"\" ORDER BY sort DESC";
	// ORDER BY sort DESC is important so that longer left arguments will be taken first
	// when applying rewrite rules, e.g. “Diksha_Bhumi” will be tried for before “Diksha”
	$result = $bdd->query($query);
	while($ligne = $result->fetch()) {
		$word = reshape_entry($ligne['word']);
		$plural = reshape_entry($ligne['plural']);
		$force_case = $ligne['force_case'];
		if($test) echo $word." ".$plural." ".$force_case."<br />";
		$n_rules1 += create_and_save_tag($test,$force_case,$word,FALSE);
		$n_rules2 += create_and_save_tag($test,$force_case,$plural,TRUE);
		}
	$result->closeCursor();
	return ($n_rules1 + $n_rules2);
	}

function create_and_save_tag($test,$force_case,$word,$is_plural) {
	$n_rules = 0;
	if($word == '') return $n_rules;
	$lower_word = lcfirst($word);
	$upper_word = ucfirst($word);
	$word2 = str_replace("’","||",$word);
	$left_arg = " ".$word." ";
	$right_arg = " ".$word2."* ";
	if($test) echo "(1) ";
	save_tag_rewrite_rule($test,$left_arg,$right_arg); // “ bahin ” -> “ bahin* ”
	if(!$is_plural) {
		$left_arg = " ".$word."’s ";
		$right_arg = " ".$word2."*’s ";
		}
	else {
		$left_arg = " ".$word."’ ";
		$right_arg = " ".$word2."*’ ";
		}
	if($test) echo "(2) ";
	save_tag_rewrite_rule($test,$left_arg,$right_arg); // “ bahin’s ” -> “ bahin*’s ”
	$n_rules += 2;
	if($force_case) {
		if($lower_word == $word) { // banyan
			$left_arg = " ".$upper_word." ";
			$right_arg = " ".$word2."* ";
			if($test) echo "(3) ";
			save_tag_rewrite_rule($test,$left_arg,$right_arg); // “ Banyan ” -> “ banyan* ”
			if(!$is_plural) {
				$left_arg = " ".$upper_word."’s ";
				$right_arg = " ".$word2."*’s ";
				}
			else {
				$left_arg = " ".$upper_word."’ ";
				$right_arg = " ".$word2."*’ ";
				}
			if($test) echo "(4) ";
			save_tag_rewrite_rule($test,$left_arg,$right_arg); // “ Banyan’s ” -> “ banyan*’s ”
			$n_rules += 2;
			}
		else {
			$left_arg = " ".$lower_word." ";
			$right_arg = " ".$word2."* ";
			if($test) echo "(5) ";
			save_tag_rewrite_rule($test,$left_arg,$right_arg); // “ ahev ” -> “ Ahev* ”
			$left_arg = " ".$lower_word."’s ";
			$right_arg = " ".$word2."*’s ";
			if($test) echo "(6) ";
			save_tag_rewrite_rule($test,$left_arg,$right_arg); // “ ahev’s ” -> “ Ahev*’s ”
			$n_rules += 2;
			}
		}
	else {
		$lower_word2 = str_replace("’","||",$lower_word);
		if($lower_word <> $word) {
			$left_arg = " ".$lower_word." ";
			$right_arg = " ".$lower_word2."* ";
			if($test) echo "(7) ";
		//	save_tag_rewrite_rule($test,$left_arg,$right_arg);
			$left_arg = "§ ".$lower_word." ";
			$right_arg = "§ ".$word2."* ";
			if($test) echo "(8) ";
			save_tag_rewrite_rule($test,$left_arg,$right_arg); // “§ alphonso ” -> “§ Alphonso* ”
			if(!$is_plural) {
				$left_arg = "§ ".$lower_word."’s ";
				$right_arg = "§ ".$word2."*’s ";
				}
			else {
				$left_arg = "§ ".$lower_word."’ ";
				$right_arg = "§ ".$word2."*’ ";
				}
			if($test) echo "(9) ";
			save_tag_rewrite_rule($test,$left_arg,$right_arg); // “§ alphonso’s” -> “§ Alphonso*’s”
			$n_rules += 3;
			}
		}
	return $n_rules;
	}

function save_tag_rewrite_rule($test,$left_arg,$right_arg) {
	global $bdd;
	$left_arg = str_replace('_',' ',$left_arg); // “ Ambar Zumbar ”
	if($test) echo "“".$left_arg."” -> “".$right_arg."”<br />";
	$query = "INSERT INTO ".BASE.".rewrite_rules (type, language, left_arg, right_arg) VALUES (\"tag\",\"english\",\"".$left_arg."\",\"".$right_arg."\")";
	$result = $bdd->query($query);
	if(!$result) {
		echo "<br /><font color=red>".$query."<br />";
		echo "ERROR: FAILED</font>";
		die();
		}
	$result->closeCursor();
	return;
	}

function ReadDevanagariCorrections($devanagari_correction) {
	global $bdd;
	$i = 0;
	$corrections_name = SETTINGS."DevanagariCorrections.txt";
	$correction_file = @fopen($corrections_name,"rb");	
	if($correction_file == FALSE) {
		echo "ERROR: ‘".$corrections_name."’ is missing!";
		die();
		}
	else {
		echo "<font color=blue>Reading ‘".$corrections_name."’</font><br />";
		while(!feof($correction_file)) {
			$line = fgets($correction_file);
			if(is_integer($pos=strpos($line,"//")) AND $pos == 0) {
				continue;
				}
			if(ctype_space(substr($line,strlen($line)-1,1)))
				$line = substr($line,0,strlen($line)-1);
			do $line = str_replace("  ",' ',$line,$count);
			while($count > 0);
			if(trim($line) == '') continue;
			if(!is_integer(strpos($line,chr(9)))) continue;
			$line = str_replace(' ','_',$line);
			$table = explode(chr(9),$line);
			$word1 = $table[0];
			$word2 = $table[1];
			$position[$i] = $table[2];
			if($word1 <> '' AND $word2 <> '') {
				if($word1 == $word2) {
					echo "<font color=red>ERROR in ‘".$corrections_name."’: no real change on this line =></font> ".$line."";
					die();
					}
				$devanagari_correction[$i][0] = str_replace('_',' ',$word1);
				$devanagari_correction[$i][1] = str_replace('_',' ',$word2);
				$i++;
				}
			}
		fclose($correction_file);
		$imax = $i;
		for($i=0; $i < $imax; $i++) {
			$query_update = "INSERT INTO ".BASE.".rewrite_rules (left_arg, right_arg, type, language, position) VALUES (\"".$devanagari_correction[$i][0]."\",\"".$devanagari_correction[$i][1]."\",\"correction\",\"devanagari\",\"".$position[$i]."\")";
			$result_update = $bdd->query($query_update);
			if(!$result_update) {
				echo "<br /><font color=red>ERROR modifying table:</font> ".$query_update."<br />";
				die();
				}
			$result_update->closeCursor();
			}
		}
	return $devanagari_correction;
	}

function ReadRomanCorrections($roman_correction) {
	global $bdd;
	$i = 0;
	$corrections_name = SETTINGS."DiacriticalCorrections.txt";
	$correction_file = @fopen($corrections_name,"rb");	
	if($correction_file == FALSE) {
		echo "ERROR: ‘".$corrections_name."’ is missing!";
		die();
		}
	else {
		echo "<font color=blue>Reading ‘".$corrections_name."’</font><br />";
		while(!feof($correction_file)) {
			$line = fgets($correction_file);
			if(is_integer($pos=strpos($line,"//")) AND $pos == 0) {
				continue;
				}
			if(ctype_space(substr($line,strlen($line)-1,1)))
				$line = substr($line,0,strlen($line)-1);
			do $line = str_replace("  ",' ',$line,$count);
			while($count > 0);
			if(trim($line) == '') continue;
			if(!is_integer(strpos($line,chr(9)))) continue;
			$line = str_replace(' ','_',$line);
			$table = explode(chr(9),$line);
			$word1 = $table[0];
			$word2 = $table[1];
			$position[$i] = $table[2];
			$context[$i] = $table[3];
			if($word1 <> '' AND $word2 <> '') {
				if($word1 == $word2) {
					echo "<font color=red>ERROR in ‘".$corrections_name."’: no real change on this line =></font> ".$line."";
					die();
					}
				$roman_correction[$i][0] = str_replace('_',' ',$word1);
				$roman_correction[$i][1] = str_replace('_',' ',$word2);
		//		echo $word1." => ".$word2."<br />";
				$i++;
				}
			}
		fclose($correction_file);
		$imax = $i;
		for($i=0; $i < $imax; $i++) {
			$query_update = "INSERT INTO ".BASE.".rewrite_rules (left_arg, right_arg, type, language, position, context) VALUES (\"".$roman_correction[$i][0]."\",\"".$roman_correction[$i][1]."\",\"correction\",\"roman\",\"".$position[$i]."\",\"".$context[$i]."\")";
			$result_update = $bdd->query($query_update);
			if(!$result_update) {
				echo "<br /><font color=red>ERROR modifying table:</font> ".$query_update."<br />";
				die();
				}
			$result_update->closeCursor();
			}
		}
	return $roman_correction;
	}
	
function ReadTranslationCorrectionsEnglish($translation_correction_english,$i) {
	global $bdd;
	$corrections_name = SETTINGS."TranslationCorrectionsEnglish.txt";
	$correction_file = @fopen($corrections_name,"rb");	
	if($correction_file == FALSE) {
		echo "ERROR: ‘".$corrections_name."’ is missing!";
		die();
		}
	else {
		echo "<font color=blue>Reading ‘".$corrections_name."’</font><br />";
		while(!feof($correction_file)) {
			$line = fgets($correction_file);
			if(is_integer($pos=strpos($line,"//")) AND $pos == 0) {
				continue;
				}
			if(ctype_space(substr($line,strlen($line)-1,1)))
				$line = substr($line,0,strlen($line)-1);
			do $line = str_replace("  ",' ',$line,$count);
			while($count > 0);
			if(trim($line) == '') continue;
			if(!is_integer(strpos($line,chr(9)))) continue;
			$line = str_replace(' ','_',$line);
			$table = explode(chr(9),$line);
			$word1 = $table[0];
			$word2 = $table[1];
			if($word1 <> '' AND $word2 <> '') {
				if($word1 == $word2) {
					echo "<font color=red>ERROR in ‘".$corrections_name."’: no real change on this line =></font> ".$line."";
					die();
					}
				$translation_correction_english[$i][0] = str_replace('_',' ',$word1);
				$translation_correction_english[$i][1] = str_replace('_',' ',$word2);
				$i++;
				}
			}
		fclose($correction_file);
		$imax = $i;
		for($i=0; $i < $imax; $i++) {
			$query_update = "INSERT INTO ".BASE.".rewrite_rules (left_arg, right_arg, type, language) VALUES (\"".$translation_correction_english[$i][0]."\",\"".$translation_correction_english[$i][1]."\",\"correction\",\"english\")";
			$result_update = $bdd->query($query_update);
			if(!$result_update) {
				echo "<br /><font color=red>ERROR modifying table:</font> ".$query_update."<br />";
				die();
				}
			$result_update->closeCursor();
			$sql_delete = "DELETE FROM ".BASE.".lexicon WHERE word = \"".$translation_correction_english[$i][0]."\"";
			$result_delete = $bdd->query($sql_delete);
			$result_delete->closeCursor();
		//	echo $sql_delete."<br />";
			}
		}
	return $translation_correction_english;
	}

function ReloadFileInList($page,$folder) {
	if(isset($_POST['nr'])) $nr = $_POST['nr'];
		else $nr = 0;
	if(isset($_SESSION['list'])) $list = $_SESSION['list'];
	else return "bad";
	if(!isset($list[$nr])) return "bad";
	else $filename = $list[$nr];
	if(isset($_POST['action']) AND $_POST['action'] == "load_file" AND isset($_FILES["file"]["name"]) AND $_FILES["file"]["name"] <> '') {
		$upload_message = ''; $good = FALSE;
		if($_FILES["file"]["name"] <> $filename) {
			$upload_message .= "<font color=red>ERROR:</font> ‘".$_FILES["file"]["name"]."’ <font color=red>is not</font> ‘".$filename."’<font color=red>!</font><br /><br />";
			}
		else {
			if(($_FILES["file"]["type"] == "text/plain") AND ($_FILES["file"]["size"] < MAXFILESIZE)) {
				if($_FILES["file"]["error"] > 0) {
					$upload_message .= "<font color=red>ERROR: ".$_FILES["file"]["error"]."</font><br />";
					}
				else {
					$upload_message .= "<small><font color=green>Uploaded: ".$_FILES["file"]["name"]."<br />Type: ".$_FILES["file"]["type"]."<br />Size: ".($_FILES["file"]["size"] / 1024) ." Kb</font></small><br /><br />";
					move_uploaded_file($_FILES["file"]["tmp_name"],$folder.$filename);
					$good = TRUE;
					}
				}
			else {
				$upload_message .= "<font color=red>Incorrect file: ".$_FILES["file"]["name"]."<br />(Should be TXT with size < ".MAXFILESIZE.")</font>";
				}
			}
		echo $upload_message;
		echo "<table>";
		echo "<tr>";
		echo "<td class=\"tight\" style=\"text-align:center; background-color:Bisque;\">";
		echo "<form name=\"ok to read\" method=\"post\" action=\"".$page."\" enctype=\"multipart/form-data\">";
		echo "<input type=\"hidden\" NAME = \"action\" VALUE = \"load_file\" />";
		if($good)  {
			$nr++;
			echo "<input type=\"hidden\" NAME = \"nr\" VALUE = \"".$nr."\" />";
			if(isset($list[$nr])) $filename = $list[$nr];
			else {
				echo "</form></td></tr></table>";
				return "ok";
				}
			echo "<input type=\"hidden\" NAME = \"folder\" VALUE = \"".$folder."\" />";
			echo "<input TYPE=\"submit\" class=\"button\" value=\"CONTINUE\">";
			echo "<br />Next file (".($nr+1)."/".count($list)."):<br />‘".$filename."’<br />";
			}
		else {
			$filename = $list[$nr];
			echo "<input type=\"hidden\" NAME = \"nr\" VALUE = \"".$nr."\" />";
			echo "<input type=\"hidden\" NAME = \"folder\" VALUE = \"".$folder."\" />";
			echo "<input TYPE=\"submit\" class=\"button\" value=\"TRY AGAIN\">";
			}
		echo "</form>";
		echo "</td>";
		echo "<td class=\"tight\" style=\"text-align:center; background-color:Red;\">";
		echo "<form name=\"discard_file\" method=\"post\" action=\"".$page."\" enctype=\"multipart/form-data\">";
		echo "<input TYPE=\"submit\" class=\"button\" value=\"CANCEL!\">";
		echo "</form>";
		echo "</td>";
		echo "</tr>";
		echo "</table>";
		echo die();
		}
	else {
		$filename = $list[$nr];
		echo "Reload ‘".$filename."’<br /><br />";
		echo "<form action=\"".$page."\" METHOD = \"POST\" ENCTYPE=\"multipart/form-data\">";
		echo "<input type=\"hidden\" NAME = \"action\" VALUE = \"load_file\" />";
		echo "<input type=\"hidden\" NAME = \"folder\" VALUE = \"".$folder."\" />";
		echo "<input type=\"hidden\" NAME = \"nr\" VALUE = \"".$nr."\" />";
	    echo "<label for=\"file\">Filename: </label>";
		echo "<input type=\"file\" name=\"file\" id=\"file\" />";
		echo "➡&nbsp;<input TYPE=\"submit\" class=\"button\" name=\"submit\" VALUE=\"IMPORT FILE\" /></form>";
		die();
		}
	return "ok";
	}

function FindDiscrepencies($n) {
	global $bdd;
	if($n < 10) {
		$random = rand(1,5);
		switch($random) {
			case 1:
				$different = FindDifferent("village");
				if(count($different) > 0) return $different;
			break;
			case 2:
				$different = FindDifferent("hamlet");
				if(count($different) > 0) return $different;
			break;
			case 3:
				$different = FindDifferent("taluka");
				if(count($different) > 0) return $different;
			break;
			case 4:
				$different = FindDifferent("valley");
				if(count($different) > 0) return $different;
			break;
			case 5:
				$different = FindDifferent("district");
				if(count($different) > 0) return $different;
			break;
			}
		$different = FindDiscrepencies($n+1);
		}
	else {
		$different = FindDifferent("village");
		if(count($different) > 0) return $different;
		$different = FindDifferent("hamlet");
		if(count($different) > 0) return $different;
		$different = FindDifferent("taluka");
		if(count($different) > 0) return $different;
		$different = FindDifferent("valley");
		if(count($different) > 0) return $different;
		$different = FindDifferent("district");
		if(count($different) > 0) return $different;
		$different = array();
		}
	return $different;
	}

function FindDifferent($field) {
	global $bdd;
	$different = array();
	$field_english = $field."_english";
	$field_devanagari = $field."_devanagari";
	$query1 = "SELECT location_id, ".$field_english.", ".$field_devanagari." FROM ".BASE.".locations WHERE ".$field_english." <> \"\" OR ".$field_devanagari." <> \"\"";
	$result1 = $bdd->query($query1);
	$n1 = $result1->rowCount();
	if($n1 > 0) {
		$ipos = rand(1,$n1 - 1);
		for($i = 0; $i < $ipos; $i++)
			$ligne1 = $result1->fetch();
		while($ligne1 = $result1->fetch()) {
			$location_id1 = $ligne1['location_id'];
			$word_devanagari1 = $ligne1[$field_devanagari];
			$word_english1 = $ligne1[$field_english];
			$query2 = "SELECT location_id, ".$field_english.", ".$field_devanagari." FROM ".BASE.".locations WHERE (".$field_devanagari." = \"".$word_devanagari1."\" AND \"".$word_devanagari1."\" <> \"\" AND ".$field_english." <> \"".$word_english1."\") OR (".$field_devanagari." <> \"".$word_devanagari1."\" AND ".$field_english." = \"".$word_english1."\" AND \"".$word_english1."\" <> \"\")";
			$result2 = $bdd->query($query2);
			$n2 = $result2->rowCount();
			if($n2 > 0) {
				$ipos = rand(1,$n2);
				for($i = 0; $i < $ipos; $i++)
					$ligne2 = $result2->fetch();
				$location_id2 = $ligne2['location_id'];
				$word_devanagari2 = $ligne2[$field_devanagari];
				$word_english2 = $ligne2[$field_english];
				$different['field'] = $field;
				$different['location_id1'] = $location_id1;
				$different['location_id2'] = $location_id2;
				$different['word_devanagari1'] = $word_devanagari1;
				$different['word_english1'] = $word_english1;
				$different['word_devanagari2'] = $word_devanagari2;
				$different['word_english2'] = $word_english2;
				return $different;
				}
			}
		}
	return $different;
	}

function UpdateLexiconWithWorkSets($status) {
	// Never used
	global $bdd;
	$query = "SELECT * FROM ".BASE.".workset WHERE status = \"".$status."\"";
	$result = $bdd->query($query);
	while($ligne = $result->fetch()) {
		$translation_english = $ligne['translation'];
		StoreSpelling(FALSE,'en',$translation_english);
		}
	}

function Transliterate($song_id,$return,$text) {
	global $bdd, $login;
	$output = '';
	if($text == "§beg§" OR $text == "§end§") return $text;
	$all_words = all_words($return,$text);
	for($i = 0; $i < count($all_words); $i++) {
		$devanagari = trim($all_words[$i]);
		if($devanagari == '') continue;
		if($devanagari == $return) $roman = $return;
		else {
			$query = "SELECT roman, song_id FROM ".BASE.".dev_roman WHERE devanagari = \"".$devanagari."\"";
			$result = $bdd->query($query);
			$n = $result->rowCount();
	//		if($song_id == 105887) echo $n." ".$query."<br />";
			if($n == 0) $roman = "[???]";
			else {
				$ligne = $result->fetch();
				$roman = $ligne['roman'];
				$old_song_id = $ligne['song_id'];
				if($song_id > 0 AND $old_song_id == 0) {
					// Updating this table
					$query_update = "UPDATE ".BASE.".dev_roman SET song_id = \"".$song_id."\" WHERE devanagari = \"".$devanagari."\"";
			//		if($login == "Bernard") echo $query_update."<br />";
					$result_update = $bdd->query($query_update);
					if(!$result_update AND $login == "Bernard") {
						echo "<br /><span style=\"color:red;\">ERROR modifying table:</span> ".$query_update."<br />";
						die();
						}
					$result_update->closeCursor();
					}
				}
			$result->closeCursor();
			}
		if($output <> '') $output .= " ";
		$output .= $roman;
		}
	do $output = str_replace("  ",' ',$output,$count);
	while($count > 0);
	$output = str_replace("( ",'(',$output);
	$output = str_replace(" )",')',$output);
	$output = str_replace(" ?",'?',$output);
	$output = str_replace(" !",'!',$output);
	$output = str_replace(" .",'.',$output);
	$output = str_replace(" ,",',',$output);
	$output = str_replace(" ;",';',$output);
//	$output = str_replace(" :",':',$output);
	if($return <> '') $output = str_replace(" ".$return." ",$return,$output);
	return $output;
	}

function ForceReverseRule($rule_id,$verbose) {
	global $bdd;
	$query_rule = "SELECT * FROM ".BASE.".dev_roman WHERE id = \"".$rule_id."\"";
	$result_rule = $bdd->query($query_rule);
	$n = $result_rule->rowCount();
	if($n == 0) {
		echo " ➡ This rule has been deleted";
		$result_rule->closeCursor();
		return FALSE;
		}
	$ligne_rule = $result_rule->fetch();
	$result_rule->closeCursor();
	$roman = $ligne_rule['roman'];
	$devanagari_rule = $ligne_rule['devanagari'];
	$devanagari_rule_length = mb_strlen(trim($devanagari_rule));
	echo "<small>Forcing rule ".$rule_id.": ".$roman." --> ".$devanagari_rule."</small>";
//	$query_devanagari = QueryWordInTranscription("devanagari",$devanagari_rule,"song_id, devanagari, roman_devanagari",'');
	$where = QueryOneWord("devanagari",$devanagari_rule);
	$query_devanagari = "SELECT song_id, devanagari, roman_devanagari FROM ".BASE.".songs WHERE ".$where;
//	echo "<br /><small><span style=\"color:red;\">Applying rule:</span> ".$devanagari_rule." -> ".$roman." <span style=\"color:red;\">to all songs</span></small>";
	$result_devanagari = $bdd->query($query_devanagari);
	$n_devanagari = $result_devanagari->rowCount();
	$result_devanagari->closeCursor();
	echo " <small>(length ".$devanagari_rule_length.")</small>";
	$query_bad_rule = "SELECT * FROM ".BASE.".dev_roman WHERE roman = \"".$roman."\" AND id <> \"".$rule_id."\"";
	$result_bad_rule = $bdd->query($query_bad_rule);
	$n = $result_bad_rule->rowCount();
	if($n > 0) {
		while($ligne_bad_rule = $result_bad_rule->fetch()) {
			$id = $ligne_bad_rule['id'];
			$devanagari_bad = $ligne_bad_rule['devanagari'];
			$devanagari_bad_length = mb_strlen(trim($devanagari_bad));
		/*	if($devanagari_bad_length < $devanagari_rule_length) {
				echo "<br /> <span style=\"color:red;\">➡ Cannot modify this rule</span> because ‘".$devanagari_bad."’ length is ".$devanagari_bad_length." in rule ".$id.", which is less than ".$devanagari_rule_length."<br />";
				$result_bad_rule->closeCursor();
				return FALSE;
				} */
			if($verbose) echo "<br /><small>Replacing ".$devanagari_bad."</small>";
		//	$query_devanagari = QueryWordInTranscription("devanagari",$devanagari_bad,"song_id",'');
			$where = QueryOneWord("devanagari",$devanagari_bad);
			$query_devanagari = "SELECT song_id, devanagari, roman_devanagari FROM ".BASE.".songs WHERE ".$where;
			echo "<br /><small><span style=\"color:red;\">Applying rule:</span> ".$devanagari_rule." -> ".$roman;
			$result_devanagari = $bdd->query($query_devanagari);
			$n_devanagari = $result_devanagari->rowCount();
			if($verbose) echo " <span style=\"color:red;\">in ".$n_devanagari." song(s):</span></small>";
			if($n_devanagari > 0) {
				while($ligne_song = $result_devanagari->fetch()) {
					$song_id = $ligne_song['song_id'];
					$song_url = "edit-songs.php?start=".$song_id;
					$song_link = "<a href=\"".$song_url."\" target=\"_blank\">".$song_id."</a>";
					if($verbose) echo "<br /><small>• Song [".$song_link."]</small>";
					$devanagari = $ligne_song['devanagari'];
					$devanagari_words = all_words('<br />',$devanagari);
					$change = FALSE;
					for($index = 0; $index < count($devanagari_words); $index++) {
						if($devanagari_words[$index] == $devanagari_bad) {
							ReplaceWordInTranscription($song_id,"devanagari",$index,$devanagari_rule);
							$change = TRUE;
							}
						}
				//	if($verbose AND $change) echo " ok";
					}
				}
			$result_devanagari->closeCursor();
			$query_delete = "DELETE FROM ".BASE.".dev_roman WHERE id = \"".$id."\"";
	//		echo "<br />".$query_delete."<br />";
			if($verbose) echo "<br /><br /><span style=\"color:red;\"><small>Deleting rule ".$id."</small></span><br /><br />";
			$result_delete = $bdd->query($query_delete);
			if(!$result_delete) {
				echo "<span style=\"color:red;\">ERROR deleting:</span> ".$query_delete."<br />";
				die();
				}
			else $result_delete->closeCursor();
			add_to_rule_history('','',$devanagari_bad,$roman,0,$id);
			}
		}
	$result_bad_rule->closeCursor();
	return TRUE;
	}

function delete_groups($song_id) {
	global $bdd;
	$query = "DELETE FROM ".BASE.".group_index WHERE song_id = \"".$song_id."\"";
	$result = $bdd->query($query);
	$result->closeCursor();
	return;
	}
	
function assign_group($song_id,$new_group_label) {
	global $bdd,$login;
	$created = FALSE;
	if($new_group_label == '') return $created;
	$query_group = "SELECT * FROM ".BASE.".groups WHERE label = \"".$new_group_label."\"";
	$result_group = $bdd->query($query_group);
	if($result_group) $n_group = $result_group->rowCount();
	else $n_group = 0;
	if($result_group) $result_group->closeCursor();
	if($n_group == 0) {
		$date = time();
		$local_time = get_the_local_time();
		$create_date = $local_time['local-machine-time'];
		$query_update = "INSERT INTO ".BASE.".groups (label,login,date,comment_mr,comment_en) VALUES(\"".$new_group_label."\",\"".$login."\",\"".$create_date."\",\"\",\"\")";
	//	echo $query_update."<br />";
		$result_update = $bdd->query($query_update);
		if(!$result_update) {
			echo "<br /><span style=\"color:red;\">ERROR modifying table:</span> ".$query_update."<br />";
			die();
			}
		$result_update->closeCursor();
		$created = TRUE;
		}
	$query_group = "SELECT id FROM ".BASE.".groups WHERE label = \"".$new_group_label."\"";
	$result_group = $bdd->query($query_group);
	$ligne_group = $result_group->fetch();
	$result_group->closeCursor();
	$group_id = $ligne_group['id'];
	$query_group_index = "SELECT * FROM ".BASE.".group_index WHERE song_id = \"".$song_id."\" AND group_id = \"".$group_id."\"";
	$result_group_index = $bdd->query($query_group_index);
	if($result_group_index) $n_group_index = $result_group_index->rowCount();
	else $n_group_index = 0;
	$result_group_index->closeCursor();
	if($n_group_index == 0) {
		$query_update = "INSERT INTO ".BASE.".group_index (group_id, song_id) VALUES(\"".$group_id."\", \"".$song_id."\")";
	//	echo $query_update."<br />";
		$result_update = $bdd->query($query_update);
		if(!$result_update) {
			echo "<br /><span style=\"color:red;\">ERROR modifying table:</span> ".$query_update."<br />";
			die();
			}
		$result_update->closeCursor();
		}
	return $created;
	}

function remove_group($song_id,$group_label) {
	global $bdd;
	$query_group = "SELECT * FROM ".BASE.".groups WHERE label = \"".$group_label."\"";
	$result_group = $bdd->query($query_group);
	if($result_group) $n_group = $result_group->rowCount();
	else $n_group = 0;
	if($n_group > 0) {
		$ligne_group = $result_group->fetch();
		$group_id = $ligne_group['id'];
	/*	$query_index = "SELECT id FROM ".BASE.".group_index WHERE song_id = \"".$song_id."\" AND group_id = \"".$group_id."\"";
		$result_index = $bdd->query($query_index);
		if($result_index) $n_index = $result_index->rowCount();
		else $n_index = 0;
		if($result_index) $result_index->closeCursor();
		if($n_index == 0) return TRUE; */
		$query = "DELETE FROM ".BASE.".group_index WHERE song_id = \"".$song_id."\" AND group_id = \"".$group_id."\"";
		$result = $bdd->query($query);
		$result->closeCursor();
		}
	if($result_group) $result_group->closeCursor();
	if($n_group == 0) return FALSE;
	$query_index = "SELECT id FROM ".BASE.".group_index WHERE group_id = \"".$group_id."\"";
	$result_index = $bdd->query($query_index);
	if($result_index) $n_index = $result_index->rowCount();
	else $n_index = 0;
	if($result_index) $result_index->closeCursor();
	if($n_index == 0) {
		// Delete group as it has become empty
		$query_delete = "DELETE FROM ".BASE.".groups WHERE id = \"".$group_id."\"";
		$result_delete = $bdd->query($query_delete);
		$result_delete->closeCursor();
		if($result_delete) $result_delete->closeCursor();
		}
	return TRUE;
	}

function SaveListOfTaggedWordsFile($warning,$row) {
	//////////// OBSOLETE /////////////
	$file_header = "// This is a list of words that need to be tagged in English translations and will be included in ‘English_index.txt’
// The current tag is ‘*’ 
// The second column contains when necessary a necessary rewriting of the word (generally for changing capitalization)
// Entries are case-sensitive and trailing spaces are not significant, unless encoded as '_'
// Rules produced by this table are applied after the rules listed in ‘TranslationCorrectionsEnglish.txt’";
	$filename = SETTINGS."ListOfTaggedWords.txt";
	echo "<blockquote><small><font color=blue>Saving ‘".$filename."’</font></small><br />";
	if($warning) echo "<small><font color=blue>Don't forget to click <font color=red>RECONSTRUCT ALL RULES</font> <font color=blue>after completing changes!</font></small>";
	echo "</blockquote>";
	$export_file = fopen($filename,'w');
	fprintf($export_file,"%s\r\n\n",$file_header);
	for($i = 0; $i < count($row); $i++) {
		if($row[$i][0] <> '') {
			if($row[$i][1] <> '')
				fprintf($export_file,"%s\r\n",$row[$i][0]."\t".$row[$i][1]);
			else
				fprintf($export_file,"%s\r\n",$row[$i][0]);
			}
		}
	fclose($export_file);
	return;
	}

function find_example($id,$word) {
	global $bdd;
	$translation_correction_english = ReadRewriteRules("english");
	$target0 = trim(str_replace('_',' ',trim($word)));
	$target0 = str_replace("'","’",$target0);
	$target1 = " ".$target0." ";
	$target2 = trim($target1)."*";
	$message = '';
	$workset = FALSE;
	$query_song = "SELECT song_id, translation_english FROM ".BASE.".songs WHERE translation_english LIKE \"%".$target2."%\" OR translation_english LIKE \"%".$target1."%\" LIMIT 1";
	// Due to ‘LIKE’ this is not case-sensitive
	$result_song = $bdd->query($query_song);
	if($result_song) {
		$n_song = $result_song->rowCount();
		$ligne_song = $result_song->fetch();
		}
	else $n_song = 0;
	if($n_song > 0) {
		// Now case-sensitive check
		$translation = $ligne_song['translation_english'];
		$translation = apply_rules(TRUE,TRUE,$translation,$translation_correction_english);
		$all_words = all_english_words(str_replace('*','',$translation));
		$word_apos1 = str_replace("'","||",$word);
		$word_apos2 = str_replace("’","||",$word);
		if(!in_array($word,$all_words) AND !in_array($word_apos1,$all_words) AND !in_array($word_apos2,$all_words)) $n_song = 0;
		}
	if($n_song == 0) {
		$workset = TRUE;
		if($result_song) $result_song->closeCursor();
		$query_song = "SELECT song_id, translation, set_id FROM ".BASE.".workset WHERE translation LIKE \"%".$target2."%\" OR translation LIKE \"%".$target1."%\" LIMIT 1";
		// Due to ‘LIKE’ this is not case-sensitive
		$result_song = $bdd->query($query_song);
		if($result_song) {
			$n_song = $result_song->rowCount();
			$ligne_song = $result_song->fetch();
			}
		else $n_song = 0;
		if($n_song > 0) {
			// Now case-sensitive check
			$translation = $ligne_song['translation'];
			$translation = apply_rules(TRUE,TRUE,$translation,$translation_correction_english);
			$all_words = all_english_words(str_replace('*','',$translation));
			$word_apos1 = str_replace("'","||",$word);
			$word_apos2 = str_replace("’","||",$word);
			if(!in_array($word,$all_words) AND !in_array($word_apos1,$all_words) AND !in_array($word_apos2,$all_words)) $n_song = 0;
			}
		}
	if($n_song > 0) {
		$song_id = $ligne_song['song_id'];
		$message = "<li><span style=\"color:blue;\"><b>".$word."</b></span> --> song #".song($song_id,$song_id);
		if($workset) $message .= " (in work set #".$ligne_song['set_id'].")";
		$message .= "</li>";
		$query_update = "UPDATE ".BASE.".glossary SET song_id = \"".$song_id."\" WHERE id = \"".$id."\" AND specific_song_id = \"0\" AND specific_group_id = \"0\" AND specific_class_id = \"\"";
		$result_update = $bdd->query($query_update);
		$result_update->closeCursor();
		}
	if($result_song) $result_song->closeCursor();
	return $message;
	}

function update_glossary_entry($song_id,$word,$plural,$mode,$word_id,$word_id_default,$word_id_class,$word_id_group,$definition,$old_class_id,$new_class_id,$old_group_id,$new_group_id) {
	global $bdd, $login;
	$test = FALSE;
	if($mode == "specific") {
		$query_update = "UPDATE ".BASE.".glossary SET login = \"".$login."\", definition = \"".$definition."\", song_id = \"".$song_id."\" WHERE id = \"".$word_id."\"";
		$result_update = $bdd->query($query_update);
		$result_update->closeCursor();
		if($test) echo $query_update."<br />";
		return;
		}
	switch($mode) {
		case "generic":
			$query_update = "UPDATE ".BASE.".glossary SET login = \"".$login."\", definition = \"".$definition."\", song_id = \"".$song_id."\" WHERE id = \"".$word_id_default."\"";
			if($test) echo $query_update."<br />";
			$result_update = $bdd->query($query_update);
			$result_update->closeCursor();
			break;
		case "group":
			check_or_create_glossary_group($song_id,$word,$plural,$new_group_id);
			if($old_group_id == $new_group_id) {
				$query_update = "UPDATE ".BASE.".glossary SET login = \"".$login."\", definition = \"".$definition."\", specific_group_id = \"".$new_group_id."\", song_id = \"".$song_id."\" WHERE id = \"".$word_id_group."\"";
				$result_update = $bdd->query($query_update);
				$result_update->closeCursor();
				if($test) echo $query_update."<br />";
				}
			else check_or_create_song_glossary_group($song_id,$word,$plural,$new_group_id);
			break;
		case "class":
			check_or_create_glossary_class($song_id,$word,$plural,$new_class_id);
			if($old_class_id == $new_class_id) {
				$query_update = "UPDATE ".BASE.".glossary SET login = \"".$login."\", definition = \"".$definition."\", specific_class_id = \"".$new_class_id."\", song_id = \"".$song_id."\" WHERE id = \"".$word_id_class."\"";
				$result_update = $bdd->query($query_update);
				$result_update->closeCursor();
				if($test) echo $query_update."<br />";
				}
			else check_or_create_song_glossary_class($song_id,$word,$plural,$new_class_id);
			break;
		}
	return;
	}

function check_or_create_song_glossary_class($song_id,$word,$plural,$new_class_id) {
	global $bdd,$login;
	$test = FALSE;
	$date = date('Y-m-d H:i:s');
	$query_class = "SELECT id FROM ".BASE.".glossary WHERE (word = \"".$word."\" OR plural = \"".$word."\") AND definition = \"\" AND specific_song_id = \"".$song_id."\" AND specific_group_id = \"0\" AND specific_class_id <> \"\"";
	$result_class = $bdd->query($query_class);
	if($test) echo "query_song_glossary_class (1) ".$query_class."<br />";
	if($result_class) {
		$n = $result_class->rowCount();
		$ligne_class = $result_class->fetch();
		$id = $ligne_class['id'];
		$result_class->closeCursor();
		}
	else $n = 0;
	if($n == 0) {
		$sort = ucfirst($word);
		$letter_range_this_word = $sort[0];
		$query_update = "INSERT INTO ".BASE.".glossary (word, sort, plural, letter_range, song_id, specific_song_id, specific_class_id, login, date) VALUES (\"".$word."\", \"".$word."\", \"".$plural."\", \"".$letter_range_this_word."\", \"".$song_id."\", \"".$song_id."\", \"".$new_class_id."\", \"".$login."\", \"".$date."\")";
		$result_update = $bdd->query($query_update);
		$result_update->closeCursor();
		if($test) echo $query_update." (1)<br />";
		}
	else  {
		$query_update = "UPDATE ".BASE.".glossary SET login = \"".$login."\", specific_class_id = \"".$new_class_id."\", specific_song_id = \"".$song_id."\", song_id = \"".$song_id."\" WHERE id = \"".$id."\"";
		$result_update = $bdd->query($query_update);
		$result_update->closeCursor();
		if($test) echo $query_update."<br />";
		}
	}

function check_or_create_song_glossary_group($song_id,$word,$plural,$new_group_id) {
	global $bdd,$login;
	$test = FALSE;
	$date = date('Y-m-d H:i:s');
	$query_group = "SELECT id FROM ".BASE.".glossary WHERE (word = \"".$word."\" OR plural = \"".$word."\") AND definition = \"\" AND specific_song_id = \"".$song_id."\" AND specific_class_id = \"\" AND specific_group_id <> \"\"";
	$result_group = $bdd->query($query_group);
	if($test) echo "query_song_glossary_group (1) ".$query_group."<br />";
	if($result_group) {
		$n = $result_group->rowCount();
		$ligne_group = $result_group->fetch();
		$id = $ligne_group['id'];
		$result_group->closeCursor();
		}
	else $n = 0;
	if($n == 0) {
		$sort = ucfirst($word);
		$letter_range_this_word = $sort[0];
		$query_update = "INSERT INTO ".BASE.".glossary (word, sort, plural, letter_range, song_id, specific_song_id, specific_group_id, login, date) VALUES (\"".$word."\", \"".$word."\", \"".$plural."\", \"".$letter_range_this_word."\", \"".$song_id."\", \"".$song_id."\", \"".$new_group_id."\", \"".$login."\", \"".$date."\")";
		$result_update = $bdd->query($query_update);
		$result_update->closeCursor();
		if($test) echo $query_update." (2)<br />";
		}
	else  {
		$query_update = "UPDATE ".BASE.".glossary SET login = \"".$login."\", specific_group_id = \"".$new_group_id."\", specific_song_id = \"".$song_id."\", song_id = \"".$song_id."\" WHERE id = \"".$id."\"";
		$result_update = $bdd->query($query_update);
		$result_update->closeCursor();
		if($test) echo $query_update."<br />";
		}
	}
	
function check_or_create_glossary_class($song_id,$word,$plural,$new_class_id) {
	global $bdd,$login;
	$test = FALSE;
	$date = date('Y-m-d H:i:s');
	$query_class = "SELECT id FROM ".BASE.".glossary WHERE (word = \"".$word."\" OR plural = \"".$word."\") AND definition <> \"\" AND specific_song_id = \"0\" AND specific_group_id = \"0\" AND specific_class_id = \"".$new_class_id."\"";
	$result_class = $bdd->query($query_class);
	if($test) echo "Query_class (1) ".$query_class."<br />";
	if($result_class) {
		$n = $result_class->rowCount();
		$result_class->closeCursor();
		}
	else $n = 0;
	if($n == 0) {
		$definition = "(new class definition)";
		$sort = ucfirst($word);
		$letter_range_this_word = $sort[0];
		$query_update = "INSERT INTO ".BASE.".glossary (word, sort, plural, letter_range, definition, specific_class_id, song_id, login, date) VALUES (\"".$word."\", \"".$word."\", \"".$plural."\", \"".$letter_range_this_word."\", \"".$definition."\", \"".$new_class_id."\", \"".$song_id."\", \"".$login."\", \"".$date."\")";
		$result_update = $bdd->query($query_update);
		$result_update->closeCursor();
		if($test) echo $query_update." (3)<br />";
		}
	return;
	}

function check_or_create_glossary_group($song_id,$word,$plural,$new_group_id) {
	global $bdd,$login;
	$test = FALSE;
	$date = date('Y-m-d H:i:s');
	$query_group = "SELECT id FROM ".BASE.".glossary WHERE (word = \"".$word."\" OR plural = \"".$word."\") AND definition <> \"\" AND specific_song_id = \"0\" AND specific_class_id = \"\" AND specific_group_id = \"".$new_group_id."\"";
	$result_group = $bdd->query($query_group);
	if($test) echo "Query_group (1) ".$query_group."<br />";
	if($result_group) {
		$n = $result_group->rowCount();
		$result_group->closeCursor();
		}
	else $n = 0;
	if($n == 0) {
		$definition = "(new group definition)";
		$sort = ucfirst($word);
		$letter_range_this_word = $sort[0];
		$query_update = "INSERT INTO ".BASE.".glossary (word, sort, plural, letter_range, definition, specific_group_id, song_id, login, date) VALUES (\"".$word."\", \"".$word."\", \"".$plural."\", \"".$letter_range_this_word."\", \"".$definition."\", \"".$new_group_id."\", \"".$song_id."\", \"".$login."\", \"".$date."\")";
		$result_update = $bdd->query($query_update);
		$result_update->closeCursor();
		if($test) echo $query_update." (4)<br />";
		}
	return;
	}

function create_glossary_entry($word,$plural,$curr_song_id,$force_case) {
	global $bdd, $login;
	$test = FALSE;
	$message['warning'] = $message['already'] = '';
	$upperword = ucfirst($word);
	$lowerword = lcfirst($word);
	$upperplural = ucfirst($plural);
	$lowerplural = lcfirst($plural);
	$query = "SELECT id FROM ".BASE.".glossary WHERE word = \"".$word."\" OR word = \"".$upperword."\" OR word = \"".$lowerword."\"";
	if($plural <> '') $query .= " OR word = \"".$plural."\" OR word = \"".$upperplural."\" OR word = \"".$lowerplural."\"";
	$result = $bdd->query($query);
	if($result) {
		$n = $result->rowCount();
		$ligne = $result->fetch();
		$id = $ligne['id'];
		}
	else $n = $id = 0;
	if($result) $result->closeCursor();
	if($n == 0) {
		$date = date('Y-m-d H:i:s');
		$sort = ucfirst($word);
		$letter_range = $sort[0];
		$query_insert = "INSERT INTO ".BASE.".glossary (word, sort, plural, letter_range, force_case, login, date) VALUES (\"".$word."\",\"".$word."\",\"".$plural."\",\"".$letter_range."\",\"".$force_case."\",\"".$login."\",\"".$date."\")";
		$result_insert = $bdd->query($query_insert);
		if($test) echo $query_insert." (5)<br />";
		if($result_insert) $result_insert->closeCursor();
		$n_rules = update_tag_rules();
		$translation_correction_english = ReadRewriteRules("english");
		$translation = transcription($curr_song_id,"translation");
		$translation = apply_rules(TRUE,TRUE,$translation,$translation_correction_english);
		$translation = str_replace('_',' ',$translation);
		$translation = str_replace("||","’",$translation);
		$query_update = "UPDATE ".BASE.".songs SET translation_english = \"".$translation."\" WHERE song_id = \"".$curr_song_id."\"";
	//	echo "create_glossary_entry => ".$query_update."<br />";
		$result_update = $bdd->query($query_update);
		$result_update->closeCursor();
		
		// Fix translation in its last version
		$query_versions = "SELECT version FROM ".BASE.".translations WHERE song_id = \"".$curr_song_id."\" ORDER BY version DESC";
		$result_versions = $bdd->query($query_versions);
		$n_versions = $result_versions->rowCount();
		if($n_versions > 0) {
			$ligne_versions = $result_versions->fetch();
			$version = $ligne_versions['version'];
			$query_update = "UPDATE ".BASE.".translations SET text = \"".$translation."\" WHERE song_id = \"".$curr_song_id."\" AND version = \"".$version."\"";
			$result_update = $bdd->query($query_update);
			$result_update->closeCursor();
			}
		$result_versions->closeCursor();
		
		// Fix translation in (unvalidated) workset
		$query_workset = "SELECT translation FROM ".BASE.".workset WHERE song_id = \"".$curr_song_id."\"";
		$result_workset = $bdd->query($query_workset);
		$ligne_workset = $result_workset->fetch();
		$result_workset->closeCursor();
		$translation = $ligne_workset['translation'];
		$translation = apply_rules(TRUE,TRUE,$translation,$translation_correction_english);
		$translation = str_replace('_',' ',$translation);
		$translation = str_replace("||","’",$translation);
		$query_update = "UPDATE ".BASE.".workset SET translation = \"".$translation."\" WHERE song_id = \"".$curr_song_id."\" AND translation <> \"\"";
		$result_update = $bdd->query($query_update);
		$result_update->closeCursor();
		
		// Pick up id of new entry
		$query_word = "SELECT id FROM ".BASE.".glossary WHERE word = \"".$word."\" AND specific_song_id = \"0\" AND specific_group_id = \"0\" AND specific_class_id = \"\"";
		$result_word = $bdd->query($query_word);
		$ligne_word = $result_word->fetch();
		$result_word->closeCursor();
		$id_word = $ligne_word['id'];
		$find_example = find_example($id_word,$word);
		if($find_example == '')
			$message['warning'] = "<span style=\"color:blue;\"><b>".$word."</b></span> <span style=\"color:red;\">not found</span> in any song translation.";
		$_SESSION['fixed_typo_current_workset'] = FALSE;
		}
	else $message['already'] = "<p>➡ <span style=\"color:red;\">Word ‘</span><span style=\"color:blue;\">".$word."</span><span style=\"color:red;\">’ is already in glossary!</span> (<a href=\"glossary.php#word_".$id."\" target=\"_blank\">check glossary</a>)</p>";
	return $message;
	}

function change_definition($key,$value) {
	global $bdd, $login;
	$test = FALSE;
	$date = date('Y-m-d H:i:s');
	$word_id = str_replace("definition_",'',$key);
	$word = $_POST["word_".$word_id];
	$plural = $_POST["plural_".$word_id];
	$old_mode = $_POST["mode_".$word_id];
	$new_mode = $_POST["new_mode_".$word_id];
	$old_definition = $_POST["old_def_".$word_id];
	$word_id_default = $_POST["word_id_default_".$word_id];
	$word_id_class = $_POST["word_id_class_".$word_id];
	$word_id_group = $_POST["word_id_group_".$word_id];
	$old_class_id = $_POST["old_class_id_".$word_id];
	$old_group_id = $_POST["old_group_id_".$word_id];
	$new_class_id = $_POST["specific_class_id_".$word_id];
	if($test) echo "<br />new_mode = ".$new_mode."<br />";
	$group_selection = $new_group_id = -1;
	$all_groups_this_song = $_POST["all_groups_this_song_".$word_id];
	if(is_integer(strpos($new_mode,"group_"))) {
		$group_selection = str_replace("group_",'',$new_mode);
		$new_mode = "group";
		if($test) echo "group_selection = ".$group_selection."<br />";
		}
	$table = explode(",",$all_groups_this_song);
	$number_groups = count($table);
	for($j = 0; $j < $number_groups; $j++) {
		if($j == $group_selection) {
			$new_group_id = trim($table[$j]);
			break;
			}
		}
//	$new_group_id = $_POST["specific_group_id_".$word_id];
	$definition = simple_form(reshape_entry($value));
	$ok_save = TRUE;
	$song_id = $_POST['song_id'];
	if($test) echo "song_id = ".$song_id."<br />word = ".$word."<br />plural = ".$plural."<br />old_definition = ".$old_definition."<br />definition = ".$definition."<br />old class = ".$old_class_id."<br />new class = ".$new_class_id."<br />old group = ".$old_group_id."<br />new group = ".$new_group_id."<br />word_id = ".$word_id."<br />word_id_class = ".$word_id_class."<br />word_id_default = ".$word_id_default."<br />old_mode = ".$old_mode."<br />new_mode = ".$new_mode."<br />";
//	return;
/*	$query_update = "UPDATE ".BASE.".glossary SET created = \"0\" WHERE word  = \"".$word."\" OR plural  = \"".$word."\"";
	if($test) echo $query_update."<br />";
	$result_update = $bdd->query($query_update);
	$result_update->closeCursor(); */
	if($new_class_id <> '') {
		if(!check_semantic_id("start",$new_class_id)) {
			echo ": ".$new_class_id."<br />";
			return;
			}
		$prefix_ok = check_semantic_class_prefix($new_class_id);
		if(!$prefix_ok) {
			echo "<span style=\"color:red;\">Incorrect semantic ID:</span> ".$new_class_id."<br />";
			return;
			}
		}
	if($new_mode == $old_mode) {
		if($definition <> $old_definition OR $new_class_id <> $old_class_id OR $new_group_id <> $old_group_id)
			update_glossary_entry($song_id,$word,$plural,$new_mode,$word_id,$word_id_default,$word_id_class,$word_id_group,$definition,$old_class_id,$new_class_id,$old_group_id,$new_group_id);
		return;
		}
	if($old_mode == "specific") {
		$query_delete = "DELETE FROM ".BASE.".glossary WHERE id = \"".$word_id."\" AND definition <> \"\"";
		if($test) echo "Delete old generic/specific ".$query_delete."<br />";
		$result_delete = $bdd->query($query_delete);
		$result_delete->closeCursor();
		}
	$query_delete = "DELETE FROM ".BASE.".glossary WHERE (word = \"".$word."\" OR plural = \"".$word."\") AND definition = \"\" AND specific_song_id = \"".$song_id."\"";
	if($test) echo $query_delete."<br />";
	$result_delete = $bdd->query($query_delete);
	$result_delete->closeCursor();
	if($new_mode == "specific") {
		if(trim($definition) == '') $definition = "(new specific definition)";
		$sort = ucfirst($word);
		$letter_range_this_word = $sort[0];
		$query_update = "INSERT INTO ".BASE.".glossary (word, sort, plural, letter_range, definition, song_id, specific_song_id, login, date) VALUES (\"".$word."\", \"".$word."\", \"".$plural."\", \"".$letter_range_this_word."\", \"".$definition."\", \"".$song_id."\", \"".$song_id."\", \"".$login."\", \"".$date."\")";
		$result_update = $bdd->query($query_update);
		$result_update->closeCursor();
		if($test) echo $query_update." (6)<br />";
		}
	if($new_mode == "generic") {
		$sort = ucfirst($word);
		$letter_range_this_word = $sort[0];
		$query_update = "INSERT INTO ".BASE.".glossary (word, sort, plural, letter_range, song_id, specific_song_id, login, date) VALUES (\"".$word."\", \"".$word."\", \"".$plural."\", \"".$letter_range_this_word."\", \"".$song_id."\", \"".$song_id."\", \"".$login."\", \"".$date."\")";
		$result_update = $bdd->query($query_update);
		$result_update->closeCursor();
		if($test) echo $query_update." (7)<br />";
		}
	if($new_mode == "group") {
		check_or_create_glossary_group($song_id,$word,$plural,$new_group_id);
		check_or_create_song_glossary_group($song_id,$word,$plural,$new_group_id);
		$already_group = FALSE; $id = 0;
		$query_group = "SELECT id FROM ".BASE.".glossary WHERE (word = \"".$word."\" OR plural = \"".$word."\") AND definition <> \"\" AND specific_song_id = \"0\" AND specific_class_id = \"\" AND specific_group_id = \"".$new_group_id."\"";
		$result_group = $bdd->query($query_group);
		if($test) echo "query_group (2) ".$query_group."<br />";
		if($result_group) {
			$n = $result_group->rowCount();
			if($n > 0) {
				$already_group = TRUE;
				if($test) echo "already_group = TRUE<br />";
				$ligne_group = $result_group->fetch();
				$id = $ligne_group['id'];
				}
			$result_group->closeCursor();
			}
		else $n = 0;
		if(!$already_group) {
			if(trim($definition) == '') $definition = "(new group definition)";
			$sort = ucfirst($word);
			$letter_range_this_word = $sort[0];
			$query_update = "INSERT INTO ".BASE.".glossary (word, sort, plural, letter_range, definition, song_id, specific_group_id, login, date) VALUES (\"".$word."\", \"".$word."\", \"".$plural."\", \"".$letter_range_this_word."\", \"".$definition."\", \"".$song_id."\", \"".$new_group_id."\", \"".$login."\", \"".$date."\")";
			$result_update = $bdd->query($query_update);
			$result_update->closeCursor();
			if($test) echo $query_update." (8)<br />";
			}
		}
	if($new_mode == "class") {
		check_or_create_glossary_class($song_id,$word,$plural,$new_class_id);
		check_or_create_song_glossary_class($song_id,$word,$plural,$new_class_id);
		$already_class = FALSE; $id = 0;
		$query_class = "SELECT id FROM ".BASE.".glossary WHERE (word = \"".$word."\" OR plural = \"".$word."\") AND definition <> \"\" AND specific_song_id = \"0\" AND specific_group_id = \"0\" AND specific_class_id = \"".$old_class_id."\"";
		$result_class = $bdd->query($query_class);
		if($test) echo "query_class (2) ".$query_class."<br />";
		if($result_class) {
			$n = $result_class->rowCount();
			if($n > 0) {
				$already_class = TRUE;
				if($test) echo "already_class = TRUE<br />";
				$ligne_class = $result_class->fetch();
				$id = $ligne_class['id'];
				}
			$result_class->closeCursor();
			}
		else $n = 0;
		if($n == 0) {
			$query_class = "SELECT id FROM ".BASE.".glossary WHERE (word = \"".$word."\" OR plural = \"".$word."\") AND definition <> \"\" AND specific_song_id = \"0\" AND specific_group_id = \"0\" AND specific_class_id = \"".$new_class_id."\"";
			$result_class = $bdd->query($query_class);
			if($test) echo "query_class (3) ".$query_class."<br />";
			if($result_class) {
				$n = $result_class->rowCount();
				if($n > 0) {
					$already_class = TRUE;
					$ligne_class = $result_class->fetch();
					$id = $ligne_class['id'];
					}
				$result_class->closeCursor();
				}
			else $n = 0;
			}
		if(!$already_class) {
			if(trim($definition) == '') $definition = "(new class definition)";
			$sort = ucfirst($word);
			$letter_range_this_word = $sort[0];
			$query_update = "INSERT INTO ".BASE.".glossary (word, sort, plural, letter_range, definition, song_id, specific_class_id, login, date) VALUES (\"".$word."\", \"".$word."\", \"".$plural."\", \"".$letter_range_this_word."\", \"".$definition."\", \"".$song_id."\", \"".$new_class_id."\", \"".$login."\", \"".$date."\")";
			$result_update = $bdd->query($query_update);
			$result_update->closeCursor();
			if($test) echo $query_update." (9)<br />";
			}
		else { // Class might have changed
			$query_update = "UPDATE ".BASE.".glossary SET specific_class_id = \"".$new_class_id."\", song_id = \"".$song_id."\" WHERE specific_song_id  = \"".$song_id."\" AND specific_class_id <> \"\"";
			$result_update = $bdd->query($query_update);
			$result_update->closeCursor();
			if($test) echo $query_update."<br />";
			}
		}
	return;
	}

function display_ambiguous_rule($devanagari,$roman,$song_id,$id) {
	global $bdd;			
	$length = mb_strlen(trim($devanagari));
	$where = QueryOneWord("devanagari",$devanagari);
	$query_devanagari = "SELECT song_id FROM ".BASE.".songs WHERE ".$where;
	// echo $query_devanagari."<br />";
	$result_devanagari = $bdd->query($query_devanagari);
	$n_devanagari = $result_devanagari->rowCount();
	$result_devanagari->closeCursor();
	$song_url = "edit-songs.php?start=".$song_id."&end=".$song_id;
	$song_link = "<a href=\"".$song_url."\" target=\"_blank\">".$song_id."</a>";
	$link_roman = "‘<a href=\"songs.php?roman_devanagari=".$roman."\" target=\"_blank\">".$roman."</a>’";
	if($n_devanagari > 0) $link_to_songs = "<small>(<a href=\"songs.php?devanagari=".$devanagari."\" target=\"_blank\">".$n_devanagari." song(s)</a>)</small>";
	else $link_to_songs = '';
	$link_delete = "<a href=\"admin.php?rule_delete=".$id."\">delete</a>";
	echo "<input type=\"checkbox\" name=\"rule_".$id."\" value=\"ok\" />";
	echo "<small>Rule ".$id;
	echo " [".$link_delete."] <span style=\"color:red;\">such as in song</span> </small>#".$song_link."<br />‘<span style=\"color:MediumTurquoise;\">".$devanagari."</span>’ --> ".$link_roman." ".$link_to_songs."<br /><small>Devanagari = ".$length." chars ➡ <i>";
	for($i = 0; $i < $length; $i++) echo ord($devanagari[$i])." ";
	echo "</i></small>";
	return;
	}
?> 