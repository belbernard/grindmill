<?php
// error_reporting(E_ALL & ~E_NOTICE);
// session_start();
require_once("_base_urls.php");
require_once("_relier_edit.php"); // Mandatory to write in check_serious_attempt()
require_once("_users.php");
require_once("_tasks.php");
require_once("_edit_tasks.php");
ini_set('memory_limit','512M');
ini_set('max_execution_time',4000);

if(!check_serious_attempt('browse')) die();

$name = "Admin";
$canonic_url = '';
$mssg = '';
require_once("_header.php");

$query_set = "SET SQL_BIG_SELECTS=1";
$bdd->query($query_set);

echo "<h2>Admin</h2>";

if(!identified()) {
	echo "<font color=red>You need to log in to read this page.</font>";
	die();
	}
$login = $_SESSION['login'];
$_SESSION['try'] = 0;

echo "<p><font color=red>➡</font> <a href=\"enter-time-codes.php\" target=\"_blank\">Fix missing time codes!</a></p>";

if(isset($_GET['export_performers'])) {
	$message = ExportPerformers();
	echo "<blockquote><br /><br />".$message."</blockquote>";
	}
if(isset($_GET['export_locations'])) {
	$message = ExportLocations();
	echo "<blockquote><br /><br />".$message."</blockquote>";
	}
if(isset($_GET['export_translations'])) {
	$message = ExportTranslations();
	echo "<blockquote><br /><br />".$message."</blockquote>";
	}
if(isset($_GET['export_song_metadata'])) {
	$message = ExportSongMetadata();
	echo "<blockquote><br /><br />".$message."</blockquote>";
	}
if(isset($_GET['number_translations'])) $number_translations = $_GET['number_translations'];
else $number_translations = 25;
if(isset($_GET['number_transcriptions'])) $number_transcriptions = $_GET['number_transcriptions'];
else $number_transcriptions = 25;
if(isset($_GET['number_metadata'])) $number_metadata = $_GET['number_metadata'];
else $number_metadata = 25;
if(isset($_GET['number_locations'])) $number_locations = $_GET['number_locations'];
else $number_locations = 25;
if(isset($_GET['number_biodata'])) $number_biodata = $_GET['number_biodata'];
else $number_biodata = 25;
if(isset($_GET['number_connections'])) $number_connections = $_GET['number_connections'];
else $number_connections = 25;

if(isset($_GET['rule_delete']) AND $_GET['rule_delete'] > 0) {
	if(is_super_admin($login)) {
		$id = $_GET['rule_delete'];
		$query_rule = "SELECT * FROM ".BASE.".dev_roman WHERE id = \"".$id."\"";
		$result_rule = $bdd->query($query_rule);
		$ligne_rule = $result_rule->fetch();
		$result_rule->closeCursor();
		$roman = $ligne_rule['roman'];
		$devanagari = $ligne_rule['devanagari'];
		add_to_rule_history('','',$devanagari,$roman,0,$id);
		$query_delete = "DELETE FROM ".BASE.".dev_roman WHERE id = \"".$id."\"";
		echo $query_delete."<br />";
		$result_delete = $bdd->query($query_delete);
		if(!$result_delete) {
			echo "<span style=\"color:red;\">ERROR deleting:</span> ".$query_delete."<br />";
			die();
			}
		else $result_delete->closeCursor();
		}
	else echo "<p style=\"color:red;\">Only a superadmin can delete these rules! (risky)</p>";
	$_GET['consistency'] = "check";
	$_GET['duplicates'] = "trace";
	}
	
if(is_admin($login) AND isset($_POST['force_reverse_rules'])) {
	echo "<span style=\"color:blue;\">Forcing selected reverse rules and making them unique:</span><br />";
	if($login == "Bernard") {
	//	echo "<ul>";
		$i = 0;
		foreach($_POST as $key => $value) {
			if(is_integer(strpos($key,"rule_"))) {
				$rule_id = str_replace("rule_",'',$key);
			//	echo "<li>Forcing rule ".$rule_id;
				ForceReverseRule($rule_id,TRUE);
			//	echo "</li>";
				$i++;
				}
			}
		if($i == 0) echo "<p><span style=\"color:red;\">No rule has been selected!</span></p>";
	//	echo "</ul>";
		}
	else echo "<p><span style=\"color:red;\">Only Bernard is allowed to run this process…</span></p>";
	}
	
if(is_admin($login)) {
	if(isset($_GET['consistency']) AND $_GET['consistency'] == "check") {
		if(isset($_GET['duplicates']) AND $_GET['duplicates'] == "trace") {
			echo "<br /><span style=\"color:blue;\">Duplicates in transliteration table:</span><br /><br />";
			$query = "SELECT * from ".BASE.".dev_roman ORDER BY roman";
			$result = $bdd->query($query);
			$oldroman = ''; $old_id = $i_duplicate = 0;
			echo "<form name=\"force_reverse_rules\" method=\"post\" action=\"admin.php?consistency=check&duplicates=trace\" enctype=\"multipart/form-data\">";
			while($ligne = $result->fetch()) {
				$roman = $ligne['roman'];
				if($roman == $oldroman) {
					$id = $ligne['id'];
				/*	if($id == 13804 OR $id == 53770) {
						// ‘,’ and ‘।’
						$oldroman = ''; $old_id = 0;
						continue;
						} */
					display_ambiguous_rule($devanagari,$roman,$song_id,$old_id);
					echo "<br /><span style=\"color:orange;\">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<===========></span><br />";
					$song_id = $ligne['song_id'];
					$devanagari = $ligne['devanagari'];
					display_ambiguous_rule($devanagari,$roman,$song_id,$id);
					echo "<hr>";
					$i_duplicate++;
					}
				else {
					$id = $ligne['id'];
					$devanagari = $ligne['devanagari'];
					$song_id = $ligne['song_id'];
					}
				$old_id = $id;
				$oldroman = $roman;
				}
			$result->closeCursor();
			echo $i_duplicate." duplicate entries<br /><br />";
			if($i_duplicate > 0) echo "<input type=\"submit\" class=\"button\" name=\"force_reverse_rules\" value=\"FORCE REVERSE RULES (selected ones)\">";
			echo "</form><br />";
			echo "[<a href=\"admin.php\">Cancel</a>]<br />";
			die();
			}
		else {
			if($login == "Bernard") {
				echo "<h4>Checking inconsistencies.<br />List of incorrect transliterations:</h4>";
				if(isset($_GET['start'])) $id_start = $_GET['start'];
				else $id_start = 0;
				$query = "SELECT song_id, devanagari, roman_devanagari from ".BASE.".songs WHERE song_id > \"".$id_start."\"";
				$result = $bdd->query($query);
				$n = $result->rowCount();
				$i_songs = $i_bad = $i_changed = 0;
				$imax_songs = 20000;
				echo "<ul>";
				while($ligne = $result->fetch()) {
					$bad = FALSE;
					$change = TRUE;
					$i_songs++;
					$song_id = $ligne['song_id'];
					$devanagari = $ligne['devanagari'];
					$roman = str_replace(" <br />","<br />",trim($ligne['roman_devanagari']));
					$imax = 4;
					$target = "";
					if($target <> '' AND !is_integer(strpos(" ".$roman." ",' '.$target.' ')) AND !is_integer(strpos(" ".$roman." ",'>'.$target.' ')) AND !is_integer(strpos(" ".$roman." ",' '.$target.'<'))) continue;
					if($target <> '') $change = TRUE;
					$roman_transliteration = Transliterate($song_id,"<br />",$devanagari);
					if($roman_transliteration <> $roman) {
						if($change AND is_integer(strpos($roman_transliteration,"[???]")))
							$change = FALSE;
						$bad = TRUE;
						if($change) {
							$imax = 20;
							$query2 = "UPDATE ".BASE.".songs SET roman_devanagari = \"".$roman_transliteration."\" WHERE song_id = \"".$song_id."\"";
							$result2 = $bdd->query($query2);
							if(!$result2) {
								echo "<br /><span style=\"color:red;\">".$query2."<br />";
								echo "ERROR: FAILED</span>";
								die();
								}
							$result2->closeCursor();
							$i_changed++;
							}
						}
					if($bad AND !$change) {
						$url = "edit-songs.php?start=".$song_id."&end=".$song_id;
						echo "<li><span style=\"color:red;\">Song</span> #<a href=\"".$url."\" target=\"_blank\">".$song_id."</a>";
						if($change) echo " fixed";
						echo "</li>";
						$i_bad++;
						if($i_bad >= $imax) {
							echo "<li>... etc. More than ".$imax." (<a href=\"admin.php?consistency=check&start=".$song_id."\">continue</a> from #".$song_id.")</li>";
							break;
							}
						}
					if($i_songs >= $imax_songs) break;
					}
				$result->closeCursor();
				if($i_bad == 0) echo "<li>&nbsp;<span style=\"color:blue;\">No inconsistency found</span></li>";
				if($i_changed > 0) echo "<li>".$i_changed." Roman Devanagari transcriptions have been changed</li>";
				if($i_songs >= $imax_songs) echo "<li>... etc. ➡ <a href=\"admin.php?consistency=check&start=".$song_id."\">More songs</a> from #".$song_id."</li>";
				echo "</ul>(<a href=\"admin.php\">Cancel</a>)";
				die();
				}
			}
		}
	else {
		echo "➡ <a href=\"admin.php?consistency=check&duplicates=trace\" target=\"_blank\">Display ambiguous Devanagari Roman transliteration rules</a><br />";
		if($login == "Bernard" AND FALSE) echo "➡ <a href=\"admin.php?consistency=check\">Check consistency in transliterations</a><br />";
		}
	}

$query = "SELECT login, date, page FROM ".BASE.".history WHERE page LIKE \"%delete%\" ORDER BY date DESC";
if(!isset($_GET['all']) OR $_GET['all'] <> "delete") {
	$query .= " LIMIT 20";
	$less = TRUE;
	}
else $less = FALSE;
$result = $bdd->query($query);
$n = $result->rowCount();
if($n > 0 AND is_admin($login)) {
	echo "<h4>Recent deletes:</h4>";
	echo "<ol>";
	if($less) echo "<li>[<a href=\"admin.php?all=delete\">Display all…</a>]</li>";
	while($ligne = $result->fetch()) {
		$login_connect = $ligne['login'];
		$date = $ligne['date'];
		$page = $ligne['page'];
		echo "<li><small>".$ligne['date']."</small> ".$page." <i><small>".$login_connect."</small></i></li>";
		}
	if(!$less) echo "<li>[<a href=\"admin.php\">Display less…</a>]</li>";
	echo "</ol>";
	}
if($result) $result->closeCursor();
	
if(is_super_admin($login) AND $login == "Bernard") {
	echo "<a name=\"connections\"></a>";
	echo "<h4>Recent connections (superadmin):</h4>";
	$ip = $_SERVER['REMOTE_ADDR'];
	echo "<p>ip = ".$ip."</p>";
	$query = "SELECT login, date, page FROM ".BASE.".history ORDER BY date DESC";
	$query .= " LIMIT ".$number_connections;
	$result = $bdd->query($query);
	$n = $result->rowCount();
	// echo $n."<br />";
	echo "<ul>";
	echo "<li>[<a href=\"admin.php?number_connections=".($number_connections + 100)."#connections\">Display more…</a>]</li>";
	if($n > 0) {
		while($ligne = $result->fetch()) {
			$login_connect = $ligne['login'];
			$date = $ligne['date'];
			$page = $ligne['page'];
			echo "<li><small>".$ligne['date']."</small> ".$page." <i><small>".$login_connect."</small></i></li>";
			}
		if($number_connections > 100) echo "<li>[<a href=\"admin.php#connections\">Display less…</a>]</li>";
		}
	$result->closeCursor();
	echo "</ul>";
	}

if(is_super_admin($login)) {
	echo "<h4>Fix class identifiers (superadmin):</h4>";
	$class = $class_id_bad = $class_id_good = '';
	if(isset($_POST['fix_class']) AND $_POST['fix_class'] == "yes") {
		$class = trim($_POST['class']);
		$class = str_replace(' ','',$class);
		$class = str_replace('—','-',$class);
		$class_id_bad = trim($_POST['class_id_bad']);
		$class_id_good = trim($_POST['class_id_good']);
		$current_class_id = semantic_class_id_given_class($class);
		$ok = FALSE;
		if(!check_semantic_id("full",$class_id_good)) {
			echo " ‘".$class_id_good."’<br />";
			}
		else if($current_class_id == $class_id_bad) {
			echo "<font color=red>Trying to replace ".$class_id_bad." with ".$class_id_good." for class ".$class."</font><br />";
			$query = "SELECT song_id FROM ".BASE.".songs WHERE semantic_class_id = \"".$class_id_bad."\"";
			$result = $bdd->query($query);
			$n_bad = $result->rowCount();
			$result->closeCursor();
			echo $n_bad." songs contained the bad id.<br />";
			$query = "SELECT song_id FROM ".BASE.".songs WHERE semantic_class_id = \"".$class_id_good."\"";
			$result = $bdd->query($query);
			$n_good = $result->rowCount();
			$result->closeCursor();
			echo $n_good." songs contained the good id.<br />";
			if($n_good == 0) {
				$query = "SELECT song_id FROM ".BASE.".song_metadata WHERE semantic_class_id = \"".$class_id_bad."\"";
				$result = $bdd->query($query);
				$n_bad = $result->rowCount();
				$result->closeCursor();
				echo $n_bad." song metadata entries contained the bad id.<br />";
				$query = "SELECT song_id FROM ".BASE.".song_metadata WHERE semantic_class_id = \"".$class_id_good."\"";
				$result = $bdd->query($query);
				$n_good = $result->rowCount();
				$result->closeCursor();
				echo $n_good." song metadata entries contained the good id.<br />";
				if($n_good == 0) {
					$query = "SELECT song_id FROM ".BASE.".workset WHERE semantic_class_id = \"".$class_id_bad."\"";
					$result = $bdd->query($query);
					$n_bad = $result->rowCount();
					$result->closeCursor();
					echo $n_bad." workset entries contained the bad id.<br />";
					$query = "SELECT song_id FROM ".BASE.".workset WHERE semantic_class_id = \"".$class_id_good."\"";
					$result = $bdd->query($query);
					$n_good = $result->rowCount();
					$result->closeCursor();
					echo $n_good." workset entries contained the good id.<br />";
					if($n_good == 0) {
						$ok = TRUE;
						echo "<font color=green>➡ Fixing SONGS table<br />";
						$query_update = "UPDATE ".BASE.".songs SET semantic_class_id = \"".$class_id_good."\" WHERE semantic_class_id = \"".$class_id_bad."\"";
				//		echo "<small>".$query_update."</small><br />";
						$result_update = $bdd->query($query_update);
						if(!$result_update) {
							echo "<br /><font color=red>ERROR modifying table:</font> ".$query_update."<br />";
							die();
							}
						$result_update->closeCursor();
						echo "➡ Fixing WORKSET table<br />";
						$query_update = "UPDATE ".BASE.".workset SET semantic_class_id = \"".$class_id_good."\" WHERE semantic_class_id = \"".$class_id_bad."\"";
				//		echo "<small>".$query_update."</small><br />";
						$result_update = $bdd->query($query_update);
						if(!$result_update) {
							echo "<br /><font color=red>ERROR modifying table:</font> ".$query_update."<br />";
							die();
							}
						$result_update->closeCursor();
						echo "➡ Fixing SONG_METADATA table><br />";
						$query_update = "UPDATE ".BASE.".song_metadata SET semantic_class_id = \"".$class_id_good."\" WHERE semantic_class_id = \"".$class_id_bad."\"";
				//		echo "<small>".$query_update."</small><br />";
						$result_update = $bdd->query($query_update);
						if(!$result_update) {
							echo "<br /><font color=red>ERROR modifying table:</font> ".$query_update."<br />";
							die();
							}
						$result_update->closeCursor();
						echo "➡ Fixing CLASSIFICATION table<br />";
						$query_update = "UPDATE ".BASE.".classification SET semantic_class_id = \"".$class_id_good."\" WHERE semantic_class_id = \"".$class_id_bad."\"";
				//		echo "<small>".$query_update."</small><br />";
						$result_update = $bdd->query($query_update);
						if(!$result_update) {
							echo "<br /><font color=red>ERROR modifying table:</font> ".$query_update."<br />";
							die();
							}
						$result_update->closeCursor();
						echo "</font>";
						}
					}
				}
			if(!$ok)
				echo "➡ Cannot modify class ids in these songs because of possible confusion.<br />";
			}
		else {
			if($current_class_id == '')
				echo "<font color=red>This class does not exist: ‘".$class."’ (incomplete or misspelled)</font><br />";
			else
				echo "<font color=red>Incorrect fix: class id ‘".$class_id_bad."’ never matches class ‘".$class."’, current class id is always ‘".$current_class_id."’</font><br />";
			}
		}
	else {
		echo "<small>(e.g. in class A:II-3.5d, replace A02-03-0502 with A02-03-05d02)</small><br />";
		}
	echo "<form name=\"fix_class\" method=\"post\" action=\"admin.php\" enctype=\"multipart/form-data\">";
	echo "<input type=\"hidden\" name = \"fix_class\" value = \"yes\" />";
	echo "<p>➡ In this class: ";
	echo "<input type=\"text\" name=\"class\" size=\"12\" value=\"".$class."\">";
	echo "&nbsp;replace <input type=\"text\" name=\"class_id_bad\" size=\"15\" value=\"".$class_id_bad."\">";
	echo "&nbsp;with <input type=\"text\" name=\"class_id_good\" size=\"15\" value=\"".$class_id_good."\">";
	echo "&nbsp;<input type=\"submit\" class=\"button\" value=\"REPLACE IN ALL MySQL TABLES\">";
	echo "</p>";
	echo "</form>";
	echo "<div id=\"pwd\"></div>";

	echo "<h4>Current editors (superadmin):</h4><ul>";
	foreach($user_role as $name => $value) {
		echo "<li><i>".$name. " </i>(".$user_role[$name].")</li>";
		}
		echo "</ul>";
	echo "<h4>Create a new editor (superadmin):</h4>";
	$password = $name = '';
	if(isset($_POST['create_editor']) AND $_POST['create_editor'] == "yes") {
		$password = trim($_POST['password']);
		$name = str_replace(' ','_',trim($_POST['name']));
		$role = trim($_POST['role']);
		if(strlen($name) < 3) echo "<span style=\"color:red;\">This name is too short: </span>‘".$name."’<br />";
		else if(strlen($password) < 4) echo "<span style=\"color:red;\">This password is too short: </span>‘".$password."’<br />";
		else if(isset($mot_de_passe[$name])) {
			echo "<span style=\"color:red;\">This name already exists: </span>‘".$name."’<br />";
			echo $mot_de_passe[$name]."<br />";
			}
		else {
	//		$options = ['cost' => 12,'salt' => mcrypt_create_iv(22,MCRYPT_DEV_URANDOM),];
			$options = ['cost' => 12,];
			$hash = password_hash($password,PASSWORD_BCRYPT,$options);
			echo "<span style=\"color:MediumTurquoise;\">Created user</span> ‘".$name."’ (".$role.") <span style=\"color:MediumTurquoise;\">with password</span> ‘".$password."’<br />";
			echo "<span style=\"color:MediumTurquoise;\">encrypted</span> <small>".$hash."</small>";
			if(password_verify($password,$hash)) {
			// Below is a procedure used to temporarily store the id and encrypted password of a newly created user
			// These should be stored in "_users.php", then set permissions, email etc. and "delete _editor_list.txt"
				echo " <span style=\"color:blue;\">(checked)</span>";
				$user_file = fopen("_editor_list.txt","a");
				fwrite($user_file,"\n".$name." ".$role." ".$hash);
				fclose($user_file);
				}
			else echo " <span style=\"color:red;\"> -> verification failed!</span> ➡ may be inappropriate character";
			}
		}
	echo "<form name=\"create_editor\" method=\"post\" action=\"admin.php#pwd\" enctype=\"multipart/form-data\">";
	echo "<input type=\"hidden\" name = \"create_editor\" value = \"yes\" />";
	echo "<blockquote style=\"padding:6px; background-color:CornSilk; width:20em;\">Name = ";
	echo "<input type=\"text\" name=\"name\" size=\"12\" value=\"".$name."\"> (single word)<br />";
	echo "<input type=\"radio\" name=\"role\" value=\"edit\" checked>Editor (role)<br />";
	echo "<input type=\"radio\" name=\"role\" value=\"translate\">Translator (role)<br />";
	echo "<input type=\"radio\" name=\"role\" value=\"mapping\">Mapper (role)<br />";
	echo "Password = ";
	echo "<input type=\"text\" name=\"password\" size=\"12\" value=\"".$password."\">";
	echo "&nbsp;<input type=\"submit\" class=\"button\" value=\"CREATE\"></blockquote>";
	echo "</p>";
	echo "</form>";
	}
echo "<p>ok</p>";
$query = "SELECT count(*) from ".BASE.".songs";
$result = $bdd->query($query);
$number_of_songs = $result->fetchColumn();
$result->closeCursor();
$query = "SELECT count(*) from ".BASE.".songs WHERE recording_DAT_index <> \"\"";
$result = $bdd->query($query);
$number_of_recordings = $result->fetchColumn();
$result->closeCursor();
$query = "SELECT count(*) from ".BASE.".songs WHERE translation_english <> \"\"";
$result = $bdd->query($query);
$number_of_translations_english = $result->fetchColumn();
$result->closeCursor();
$query = "SELECT count(*) from ".BASE.".songs WHERE translation_french <> \"\"";
$result = $bdd->query($query);
$number_of_translations_french = $result->fetchColumn();
$result->closeCursor();
$query = "SELECT count(*) from ".BASE.".workset WHERE status <> \"valid\"";
$result = $bdd->query($query);
$number_of_songs_in_workset = $result->fetchColumn();
$result->closeCursor();
echo "<h4>Statistics:</h4>";
echo "<ul>";
echo "<li><font color=red>".$number_of_songs."</font> songs in database</li>";
echo "<li>".SOUND_ICON." <font color=purple>".$number_of_recordings."</font> recorded songs</li>";
echo "<li>".TRANSLATION_ICON." <font color=green>".$number_of_translations_english."</font> translated songs  (English)</li>";
echo "<li>".TRANSLATION_ICON." <font color=brown>".$number_of_songs_in_workset."</font> songs awaiting translation/edition in work sets</li>";
echo "<li>".TRANSLATION_ICON." <font color=blue>".$number_of_translations_french."</font> translated songs (French)</li>";
echo "</ul>";

echo "<h4>Roman Devanagari transliteration:</h4>";
echo "<ul>";
echo "<li><a href=\"transliteration-rules.php?mode=recent\" target=\"_blank\">Transliteration rules</a></li>";
echo "<li><a href=\"rules-history.php\" target=\"_blank\">Detailed history of changes</a></li>";
echo "</ul>";

if(is_translator($login)) {
	echo "<h4>Create items:</h4>";
	echo "<ul>";
	echo "<li><a target=\"_blank\" href=\"songs.php?mode=create\">Create new songs</a></li>";
	echo "<li><a target=\"_blank\" href=\"performer.php?mode=create\">Create new performers</a></li>";
	echo "<li><a target=\"_blank\" href=\"edit-recordings.php\">Create new recordings</a></li>";
	echo "</ul>";
	
	echo "<h4>List songs:</h4>";
	echo "<ul>";
	echo "<li><a target=\"_blank\" href=\"missing_translations.php?mode=short\">List untranslated songs</a></li>";
	echo "<li><a target=\"_blank\" href=\"need_translations.php?mode=short\">List untranslated recorded songs</a></li>";
	echo "</ul>";
	}
		
if(isset($_GET['dump']) AND $_GET['dump'] == "all") {
/*	if(isset($_GET['choice']) AND $_GET['choice'] == "settings") {
		backup_settings(TRUE);
		} */
	$date = date("Y-m-d");
	$olddir =  getcwd();
	$backup_path = "DB_DUMP/".$date;
	chdir($backup_path);
	if(isset($_GET['format'])) {
		$format = $_GET['format'];
		if($format == "all") {
		/*	$tables = array();  2025-01-23
			backup_tables(TRUE,$tables,TRUE,"sql"); */
			$tables = array();
			backup_tables(TRUE,$tables,FALSE,"sql");
			$tables = array();
			backup_tables(TRUE,$tables,FALSE,"csv");
			chdir($olddir);
			backup_settings(TRUE);
			}
		else {
			if(!isset($_GET['compression'])) die();
			if($_GET['compression'] == "yes") $compression = TRUE;
			else $compression = FALSE;
			$tables = array();
			backup_tables(TRUE,$tables,$compression,$format);
			}
		}
	chdir($olddir);
	ClearBackups(TRUE);
	echo "<form name=\"ok\" method=\"post\" action=\"admin.php\" enctype=\"multipart/form-data\">";
	echo "<input type=\"submit\" class=\"button\" value=\"OK\">";
	echo "</form>";
	die();
	}

if(is_admin($login)) {
	echo "<h4>Backups:</h4>";
	echo "<ul>";
	echo "<li><a href=\"admin.php?dump=all&format=all\">Dump MySQL tables</a> in all formats to DB_DUMP (sql, sql.gz, cvs) and SETTINGS</li>";
	echo "<ul>";
	echo "<li><a href=\"admin.php?dump=all&compression=no&format=sql\">Dump all MySQL tables</a> to DB_DUMP (sql)</li>";
	echo "<li><a href=\"admin.php?dump=all&compression=yes&format=sql\">Dump all MySQL tables</a> to DB_DUMP (sql.gz)</li>";
	echo "<li><a href=\"admin.php?dump=all&compression=no&format=csv\">Dump all MySQL tables</a> to DB_DUMP (csv)</li>";
//	echo "<li><a href=\"admin.php?dump=all&choice=settings\">Dump SETTINGS</a></li>";
	echo "</ul>";
	echo "<li><a target=\"_blank\" href=\"revert.php\">Display earlier versions of MySQL tables</a> (and revert if necessary)</li>";
	echo "</ul>";
	
	if(is_super_admin($login)) {
		$query = "SELECT * FROM ".BASE.".snif ORDER BY freq DESC";
		$result = $bdd->query($query);
		if($result) $n = $result->rowCount();
		else $n = 0;
		echo "<h4>Trace unidentified access (superadmin):</h4>";
		echo "<ul>";
		echo "<li><a target=\"_blank\" href=\"snif.php\">Check this page</a> (<span style=\"color:red;\">".$n."</span> entries)</li>";
		echo "</ul>"; 
		}
	
	echo "<h4>Index files:</h4>";
	echo "<blockquote>To produce/update these files, <a target=\"blank\" href=\"index_translations.php\">visit this page</a>.</blockquote>";
	echo "<ul>";
	echo "<li><a href=\"".INDEX."SONGS_English_index_tagged.txt\">SONGS_English_index_tagged.txt</a></li>";
	echo "<li><a href=\"".INDEX."SONGS_English_index_proper.txt\">SONGS_English_index_proper.txt</a></li>";
	echo "<li><a href=\"".INDEX."SONGS_English_index_full.txt\">SONGS_English_index_full.txt</a></li>";
	echo "</ul>";
	}

echo "<a name=\"locations\"></a>";
echo "<h4>Recent changes in location descriptions:</h4>";
$query = "SELECT DISTINCT location_id FROM ".BASE.".stories where location_id <> \"\" ORDER BY date DESC";
$result = $bdd->query($query);
$location_max = $result->rowCount();
$result->closeCursor();
$query = "SELECT DISTINCT location_id FROM ".BASE.".stories where location_id <> \"\" ORDER BY date DESC";
$query .= " LIMIT ".$number_locations;
$result = $bdd->query($query);
$n = $result->rowCount();
echo "<ol>";
if($n < $location_max) echo "<li>[<a href=\"admin.php?number_locations=".($number_locations + 100)."#locations\">Display more…</a>]</li>";
if($n > 0) {
	while($ligne = $result->fetch()) {
		$location_id = $ligne['location_id'];
		$location_features = location_features($location_id);
		$query2 = "SELECT * FROM ".BASE.".stories WHERE location_id = \"".$location_id."\" AND login <> \"\" ORDER BY version DESC";
		$result2 = $bdd->query($query2);
		$ligne2 = $result2->fetch();
		$result2->closeCursor();
		$login_connect = $ligne2['login'];
		echo "<li><small>".$ligne2['date']."</small> ".$location_features['village_english']." [<a target=\"_blank\" href=\"location.php?location_id=".$location_id."\">".$location_id."</a>] <i><small>".$login_connect."</small></i></li>";
		}
	if($number_locations > 100) echo "<li>[<a href=\"admin.php#locations\">Display less…</a>]</li>";
	}
$result->closeCursor();
echo "</ol>";

echo "<a name=\"biodata\"></a>";
echo "<h4>Recent changes in performers biodata and other metadata:</h4>";
$query = "SELECT DISTINCT performer_id FROM ".BASE.".stories where performer_id <> \"\" ORDER BY date DESC";
$result = $bdd->query($query);
$biodata_max = $result->rowCount();
$result->closeCursor();
$query = "SELECT DISTINCT performer_id FROM ".BASE.".stories where performer_id <> \"\" ORDER BY date DESC";
$query .= " LIMIT ".$number_biodata;
// echo $query."<br />";
$result = $bdd->query($query);
$n = $result->rowCount();
// echo $n."<br />";
echo "<ol>";
if($n < $biodata_max) echo "<li>[<a href=\"admin.php?number_biodata=".($number_biodata + 100)."#biodata\">Display more…</a>]</li>";
if($n > 0) {
	while($ligne = $result->fetch()) {
		$performer_id = $ligne['performer_id'];
		$performer_names = performer_names($performer_id); 
		$query2 = "SELECT * FROM ".BASE.".stories WHERE performer_id = \"".$performer_id."\" AND login <> \"\" ORDER BY version DESC";
		$result2 = $bdd->query($query2);
		$ligne2 = $result2->fetch();
		$result2->closeCursor();
		$login_connect = $ligne2['login'];
		echo "<li><small>".$ligne2['date']."</small> ".$performer_names['performer_name_english']." [<a target=\"_blank\" href=\"performer.php?performer_id=".$performer_id."\">".$performer_id."</a>] <i><small>".$login_connect."</small></i></li>";
		}
	if($number_biodata > 100) echo "<li>[<a href=\"admin.php#biodata\">Display less…</a>]</li>";
	}
$result->closeCursor();
echo "</ol>";

echo "<a name=\"metadata\"></a>";
echo "<h4>Recent changes in song metadata:</h4>";
$query = "SELECT song_id, login, date FROM ".BASE.".song_metadata WHERE (devanagari = \"\" AND roman_devanagari = \"\") ORDER BY date DESC";
$query .= " LIMIT ".$number_metadata;
// echo $query."<br />";
$result = $bdd->query($query);
$n = $result->rowCount();
// echo $n."<br />";
echo "<ol>";
echo "<li>[<a href=\"admin.php?number_metadata=".($number_metadata + 100)."#metadata\">Display more…</a>]</li>";
if($n > 0) {
	while($ligne = $result->fetch()) {
		$login_connect = $ligne['login'];
		$song_id = $ligne['song_id'];
		$bugsign = '☛';
		$bugs = bugs($song_id,$bugsign);
	//	if(!$less AND $login_connect == "Bernard" AND $bugs == '') continue;
		$bugs = "<span style=\"color:red;\">".$bugs."</span>";
		echo "<li><small>".$ligne['date']."</small> ".$bugs.flag_incorrect_DAT_index($song_id)." [<a target=\"_blank\" href=\"edit-songs.php?start=".$song_id."&end=".$song_id."\">".$song_id."</a>] <i><small>".$login_connect."</small></i></li>";
		}
	if($number_metadata > 100) echo "<li>[<a href=\"admin.php#metadata\">Display less…</a>]</li>";
	}
$result->closeCursor();
echo "</ol>";

echo "<a name=\"translations\"></a>";
echo "<h4>Recent changes in translations:</h4>";
$query = "SELECT DISTINCT song_id FROM ".BASE.".translations WHERE version <> \"0\" ORDER BY date DESC";
$query .= " LIMIT ".$number_translations;
$result = $bdd->query($query);
$n = $result->rowCount();
// echo $n."<br />";
echo "<ol>";
echo "<li>[<a href=\"admin.php?number_translations=".($number_translations + 100)."#translations\">Display more…</a>]</li>";
if($n > 0) {
	while($ligne = $result->fetch()) {
		$song_id = $ligne['song_id'];
	//	$date = $ligne['date'];
		$bugsign = '☛';
		$bugs = bugs($song_id,$bugsign);
		$query2 = "SELECT * FROM ".BASE.".translations WHERE song_id = \"".$song_id."\" AND version <> \"0\" ORDER BY version DESC";
		$result2 = $bdd->query($query2);
		$ligne2 = $result2->fetch();
		$result2->closeCursor();
		$login_connect = $ligne2['login'];
	//	if(!$less AND $login_connect == "Bernard" AND $bugs == '') continue;
		$bugs = "<span style=\"color:red;\">".$bugs."</span>";
		$version = $ligne2['version'];
	//	$date_show = $date;
		$date_show = $ligne2['date'];
		echo "<li><small>".$date_show."</small> ".$bugs.flag_incorrect_DAT_index($song_id)." [<a target=\"_blank\" href=\"edit-songs.php?start=".$song_id."&end=".$song_id."\">".$song_id."</a>] version ".$version." <i><small>".$login_connect."</small></i></li>";
		}
	if($number_translations > 100) echo "<li>[<a href=\"admin.php#translations\">Display less…</a>]</li>";
echo "</ol>";
	}
$result->closeCursor();

echo "<a name=\"transcriptions\"></a>";
echo "<h4>Recent changes in song transcriptions:</h4>";
$query = "SELECT song_id, login, date FROM ".BASE.".song_metadata WHERE (devanagari <> \"\" OR roman_devanagari <> \"\") ORDER BY date DESC";
$query .= " LIMIT ".$number_transcriptions;
$result = $bdd->query($query);
$n = $result->rowCount();
// echo $n."<br />";
echo "<ol>";
echo "<li>[<a href=\"admin.php?number_transcriptions=".($number_transcriptions + 100)."#transcriptions\">Display more…</a>]</li>";
if($n > 0) {
	while($ligne = $result->fetch()) {
		$login_connect = $ligne['login'];
		$song_id = $ligne['song_id'];
		$bugsign = '☛';
		$bugs = bugs($song_id,$bugsign);
	//	if(!$less AND $login_connect == "Bernard" AND $bugs == '') continue;
		$bugs = "<span style=\"color:red;\">".$bugs."</span>";
		echo "<li><small>".$ligne['date']."</small> ".$bugs.flag_incorrect_DAT_index($song_id)." [<a target=\"_blank\" href=\"edit-songs.php?start=".$song_id."\">".$song_id."</a>] <i><small>".$login_connect."</small></i></li>";
		}
	if($number_transcriptions > 25) echo "<li>[<a href=\"admin.php#transcriptions\">Display less…</a>]</li>";
	}
$result->closeCursor();
echo "</ol>";

/*
if(is_admin($login)) {
	echo "<a name=\"bugs\"></a>";
	echo "<h4>Songs with faulty transliteration:</h4>";
	$last_id = 120000;
//	$last_id = 40000;
	if(isset($_POST['bugs'])) {
		if(isset($_POST['last_id'])) $last_id = $_POST['last_id'];
	//	echo $last_id."<br />";
		$query = "SELECT song_id FROM ".BASE.".songs WHERE song_id < \"".$last_id."\" ORDER BY song_id DESC";
		$result = $bdd->query($query);
		$i = $j = 0;
		$bugsign = '☛';
		$first = TRUE;
		$more = FALSE;
		while($ligne = $result->fetch()) {
			$song_id = $ligne['song_id'];
			$bugs = bugs($song_id,$bugsign);
			$j++;
			if($bugs == '') {
				if($j > 1000) {
					$j = 0;
		//			$last_id = $song_id;
		//			echo "<small>(".$last_id.")</small><br />";
					if($first) {
						if($more) echo "<br /><small><i>Checking more songs...</i></small>";
						else echo "<small><i>Checking songs...</i></small>";
						}
					else echo '.';
					$first = FALSE;
					$more = TRUE;
					}
				continue;
				}
			$i++;
			$first = TRUE;
			$query2 = "SELECT login, date_modified FROM ".BASE.".songs WHERE song_id = \"".$song_id."\"";
			$result2 = $bdd->query($query2);
			$ligne2 = $result2->fetch();
			$result2->closeCursor();
			$login2 = $ligne2['login'];
			$date_modified = $ligne2['date_modified'];
			$bugs = "<span style=\"color:red;\">".$bugs."</span>";
			echo "<br /><small>".$date_modified."</small> ".$bugs.flag_incorrect_DAT_index($song_id)." [<a target=\"_blank\" href=\"edit-songs.php?start=".$song_id."\">".$song_id."</a>] <i><small>".$login2."</small></i>";
			if($i >= 4) break;
			}
		$result->closeCursor();
		if($more) echo "<br />";
		if($i == 0) echo "<br />➡ <i>No more faulty transcription!</i><br />";
		else {
			$last_id = $song_id;
			echo "<form name=\more_bugs\" method=\"post\" action=\"admin.php#bugs\" enctype=\"multipart/form-data\">";
			echo "<input type=\"hidden\" name = \"bugs\" value = \"yes\" />";
			echo "<input type=\"hidden\" name = \"last_id\" value = \"".$last_id."\" />";
			echo "<input type=\"submit\" class=\"button\" value=\"REFRESH this list (once transcriptions have been fixed)\">";
			echo "</form>";
			}
		}
	else {
		echo "<form name=\show_bugs\" method=\"post\" action=\"admin.php#bugs\" enctype=\"multipart/form-data\">";
		echo "<input type=\"hidden\" name = \"bugs\" value = \"yes\" />";
		echo "<input type=\"submit\" class=\"button\" value=\"SHOW 4 faulty transcriptions (needs time)\">";
		echo "</form>";
		}
	}  */
echo "<hr>";
check_todays_backup();

echo "</body>";
echo "</html>";
?>