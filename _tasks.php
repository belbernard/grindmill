<?php
// session_start();
// Limits for unregistered users
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
define('MAX_LIST',500);
define('MAX_DOWNLOAD',400);
define('MAX_FIND',500);
define('MAX_INDEX',500);
define('MAX_TOC',100);
define('MAX_GET',500);
define('MAX_LOGIN',5);
define('MAX_BROWSE',20);
define('MAX_DEFAULT',100);
define('ACCESS_DELAY',1); // seconds
define('MAX_SAME_RESOURCE',300);

define('MAX_BACKUPS',28);
define('NUMBER_RECENT',25);
define('SETTINGS',"SETTINGS/");
define('INDEX',SITE_URL."INDEX/");
define('EXPORT',"EXPORT_FILES/");
define('DUMP',"DB_DUMP");
define('SOUND_ICON',"<span style=\"color:aqua;\">◉</span>");
define('TRANSLATION_ICON',"<span style=\"color:orange;\">✎</span>");
define('MAXFILESIZE',10000000);
define('MAX_STORED_FILESIZE',2000000);
$allowedExts = array("jpg","jpeg","png");

$missing_tapes = array("UVS-50","UVS-51","UVS-52");
$missing_chunks = array("KAR-01","KAR-02","UVS-31","UVS-32","UVS-50","UVS-51","UVS-52","UVS-57","UVS-58","UVS-59"); 

$remote_address = real_ip();

$alphabet = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z',);

$canonic_url = $name = '';

function real_ip() {
	// http://php.net/manual/fr/reserved.variables.server.php
	$ip = $_SERVER['REMOTE_ADDR'];
    if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s',$_SERVER['HTTP_X_FORWARDED_FOR'],$matches)) {
		foreach($matches[0] AS $xip) {
			if(!preg_match('#^(10|172\.16|192\.168)\.#', $xip)) {
                $ip = $xip;
                break;
            	}
        	}
    	}
    elseif(isset($_SERVER['HTTP_CLIENT_IP']) && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/',$_SERVER['HTTP_CLIENT_IP'])) {
		$ip = $_SERVER['HTTP_CLIENT_IP'];
		}
	elseif(isset($_SERVER['HTTP_CF_CONNECTING_IP']) && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/',$_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
    	}
    elseif(isset($_SERVER['HTTP_X_REAL_IP']) && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/',$_SERVER['HTTP_X_REAL_IP'])) {
        $ip = $_SERVER['HTTP_X_REAL_IP'];
    	}
    return $ip;
	}

require_once("_users.php");

$browser_name = get_browser_name();
if($browser_name == "Safari" OR $browser_name == "Explorer")
	define('NO_OGG',TRUE);
else define('NO_OGG',FALSE);

$bottom_of_page = '';

$hide_url = FALSE;

if(isset($_SESSION['login']) AND $_SESSION['login'] <> '') {
	$login = $_SESSION['login'];
	}
else $login = '';

function is_super_admin($user) {
	global $user_role;
	if(isset($user_role[$user]) AND $user_role[$user] == "superadmin") return TRUE;
	return FALSE;
	}
	
function is_admin($user) {
	global $user_role;
	if(is_super_admin($user)) return TRUE;
	if(isset($user_role[$user]) AND $user_role[$user] == "admin") return TRUE;
	return FALSE;
	}

function is_translator($user) {
	global $user_role;
	if(is_admin($user)) return TRUE;
	if(isset($user_role[$user]) AND $user_role[$user] == "translate") return TRUE;
	return FALSE;
	}

function is_editor($user) {
	global $user_role;
	if(is_translator($user)) return TRUE;
	if(isset($user_role[$user]) AND $user_role[$user] == "edit") return TRUE;
	return FALSE;
	}

function is_mapper($user) {
	global $user_role;
	if(is_editor($user)) return TRUE;
	if(isset($user_role[$user]) AND $user_role[$user] == "mapping") return TRUE;
	return FALSE;
	}

function identified() {
	if(isset($_SESSION['login']) AND $_SESSION['login'] <> '') return TRUE;
	else return FALSE;
	}

function get_the_local_time() {
	$timezone = "Europe/Paris";
	$date = new DateTime('now', new DateTimeZone($timezone));
	return array('local-machine-time' => $date->format('Y-m-d\TH:i:s'), 'local-time' => $date->format('h:i a'));
	}

function check_serious_attempt($action) {
	global $remote_address, $bdd;
	$session = session_id();
	$url_this_page = urlencode(substr($_SERVER["SCRIPT_NAME"],strrpos($_SERVER["SCRIPT_NAME"],"/")+1)."?".$_SERVER['QUERY_STRING']);
	$good_ips = array("114.29.235.26"); // Added by BB 2023-06-13
	$good_ip = in_array($remote_address,$good_ips);
	if(!identified() AND !$good_ip) {
		$first_time = $last_time = time();
		$ip = $remote_address;
		$query = "SELECT * FROM ".BASE.".snif WHERE ip = \"".$ip."\"";
		$result = $bdd->query($query);
		if($result) $n = $result->rowCount();
		else $n = 0;
		if($n == 0) {
			$query_insert = "INSERT INTO ".BASE.".snif (ip, first_time, last_time) VALUES (\"".$ip."\",\"".$first_time."\",\"".$last_time."\")";
			$result_insert = $bdd->query($query_insert);
			if($result_insert) $result_insert->closeCursor();
			}
		else {
			$ligne = $result->fetch();
			$id = $ligne['id'];
			$freq = $ligne['freq'] + 1;
			$query_update = "UPDATE ".BASE.".snif SET last_time = \"".$last_time."\", freq = \"".$freq."\" WHERE id = \"".$id."\"";
			$result_update = $bdd->query($query_update);
			if($result_update) $result_update->closeCursor();
			}
		if($result) $result->closeCursor();
		switch($action) {
			// These options were used on SLDR
			// Their list is the set of values for field "acce_action" in "t_access"
			case 'list': $nmax = MAX_LIST; break;
			case 'download': $nmax = MAX_DOWNLOAD; break;
			case 'find': $nmax = MAX_FIND; break;
			case 'index': $nmax = MAX_INDEX; break;
			case 'toc': $nmax = MAX_TOC; break;
			case 'get': $nmax = MAX_GET; break;
			case 'login': $nmax = MAX_LOGIN; break;
			case 'browse': $nmax = MAX_BROWSE; break;
			default: $nmax = MAX_DEFAULT; break;
			}
		$query = "SELECT acce_time FROM ".BASE.".t_access WHERE acce_action = \"".$action."\" AND acce_session = \"".$session."\" ORDER BY acce_time DESC LIMIT ".($nmax + 1);
		$result = $bdd->query($query);
		$n = $result->rowCount();
		$result->closeCursor();
		if($n > $nmax) {
			echo "<p>Too many '".$action."' actions in a single session: ".$n." attempts. Unregistered users are not allowed more. You need to sign in or restart your web browser.</p>";
			if($action <> "login")
				echo "<p>[<a href=\"action.php?action=login&url_return=".$url_this_page."\">Go to login page</a>]</p>";
			return FALSE;
			}
		$query = "SELECT acce_time FROM ".BASE.".t_access WHERE acce_remote_address = \"".$remote_address."\" AND acce_action = \"".$action."\" ORDER BY acce_time DESC LIMIT 4";
		$result = $bdd->query($query);
		$n = $result->rowCount();
		if($n > 2) {
			$ligne = $result->fetch();
			$oldtime = $ligne['acce_time'];
			$time = time();
			$delay = $time - $oldtime;
			if($delay > 0 AND $delay < ACCESS_DELAY) {
			//	header($_SERVER['SERVER_PROTOCOL']." 429 - Too Many Requests",TRUE,429);
				echo "<p>Too many calls in short time. Unregistered users should wait for ".(ACCESS_DELAY + 1)." seconds!</p>";
			//	echo "action = ".$action." oldtime = ".$oldtime." time = ".$time." delay = ".$delay."<br />";
				echo "<p>[<a href=\"edit-start.php\">Go to the 'edit-start' page</a>]</p>";
				return FALSE;
				}
			}
		$result->closeCursor();
		if($action <> "browse") {
			$query = "SELECT acce_time FROM ".BASE.".t_access WHERE acce_session = \"".$session."\" AND acce_item_id = \"".$id."\"";
			$nmax = MAX_SAME_RESOURCE;
			$query .= " ORDER BY acce_time DESC LIMIT ".($nmax + 1);
			$result = $bdd->query($query);
			$n = $result->rowCount();
			$result->closeCursor();
			if($n > $nmax) {
		//		header($_SERVER['SERVER_PROTOCOL']." 429 - Too Many Requests",TRUE,429);
				echo "<p>Too many attempts to '".$action."' the same resource in a single session: ".$n." attempts. Unregistered users have limited access.</p>";
				echo "<p>[<a href=\"edit-start.php\">Go to the 'edit-start' page</a>]</p>";
				return FALSE;
				}
			}
		// This cannot be executed if "_relier_edit" has not been inserted
		$time = time();
		$query_insert = "INSERT INTO ".BASE.".t_access (acce_session, acce_time, acce_remote_address, acce_action) VALUES (\"".$session."\",\"".$time."\",\"".$remote_address."\",\"".$action."\")";
		$result_insert = $bdd->query($query_insert);
		if($result_insert) $result_insert->closeCursor();
		}
	else {
		$sql = "DELETE FROM ".BASE.".t_access WHERE acce_session = \"".$session."\" OR acce_remote_address = \"".$remote_address."\"";
		$result = $bdd->query($sql);
		$result->closeCursor();
		}
	return TRUE;
	}

function song($song_id,$arg) {
	$url = "songs.php?song_id=".$song_id;
	$song = "<a target=\"_blank\" title=\"Display song #".$song_id."\" href=\"".$url."\">".$arg."</a>";
	return $song;
	}
	
function performer_names($performer_id) {
	global $bdd;
	$performer_names = array();
	$performer_names['performer_name_devanagari'] = $performer_names['performer_name_english'] = '';
	$query = "SELECT performer_name_devanagari, performer_name_english FROM ".BASE.".performers WHERE performer_id = \"".$performer_id."\"";
	$result = $bdd->query($query);
	$n = $result->rowCount();
	if($n == 0) return $performer_names;
	$performer_names = $result->fetch();
	$result->closeCursor();
	return $performer_names;
	}

function recording_features($recording_DAT_index) {
	global $bdd;
	$recording_features = array();
	$recording_features['recording_DAT_index'] = '';
	$query = "SELECT * FROM ".BASE.".recordings WHERE recording_DAT_index = \"".$recording_DAT_index."\"";
	$result = $bdd->query($query);
	$n = $result->rowCount();
	if($n == 0) return $recording_features;
	$recording_features = $result->fetch();
	$result->closeCursor();
	return $recording_features;
	}
	
function location_features($location_id) {
	global $bdd;
	$location_features = array();
	$location_features['village_devanagari'] = $location_features['village_english'] = $location_features['hamlet_devanagari'] = $location_features['hamlet_english'] = $location_features['taluka_devanagari'] = $location_features['taluka_english'] =$location_features['district_devanagari'] = $location_features['district_english'] = $location_features['valley_devanagari'] = $location_features['valley_english'] = '';
	$query = "SELECT * FROM ".BASE.".locations WHERE location_id = \"".$location_id."\"";
	$result = $bdd->query($query);
	$n = $result->rowCount();
	if($n == 0) return $location_features;
	$location_features = $result->fetch();
	$result->closeCursor();
	return $location_features;
	}

function location_of_performer($performer_id) {
	global $bdd;
	$location_of_performer = 0;
	if($performer_id == 0) return $location_of_performer;
	$query = "SELECT location_id FROM ".BASE.".performers WHERE performer_id = \"".$performer_id."\"";
	$result = $bdd->query($query);
	$n = $result->rowCount();
	if($n == 0) return $location_of_performer;
	$ligne = $result->fetch();
	$result->closeCursor();
	$location_of_performer = $ligne['location_id'];
	return $location_of_performer;
	}

function number_of_locations() {
	global $bdd;
	$query = "SELECT count(*) from ".BASE.".locations";
	$result = $bdd->query($query);
	$number_of_rows = $result->fetchColumn();
	$result->closeCursor();
	return $number_of_rows;
	}

function higher_level($semantic_class_id) {
	$id = '';
	$length = strlen($semantic_class_id);
	for($i = 0; $i < $length; $i++) {
		$char = $semantic_class_id[$i];
		if(!is_numeric($char) AND $char <> '-' AND $i > 0) {
			if($i < ($length-1)) $id .= '-$'.$char.'-';
			else $id .= '-$'.$char;
			}
		else $id .= $char;
		}
//	echo "id =".$id."<br>"; 
	$table = explode('-',$id); 
	$higher_level = $table[0];
	if(count($table) > 1) {
		for($i = 1; $i < (count($table) - 1); $i++)
			$higher_level .= '-'.$table[$i];
		$higher_level = str_replace("-$",'',$higher_level);
		}
	else $higher_level = '';
	return $higher_level;
	}

function semantic_class_text($semantic_class_title_prefix,$semantic_class_title) {
	if($semantic_class_title == '' AND $semantic_class_title_prefix == '') return '';
	$semantic_class_title_prefix_table = explode('/',$semantic_class_title_prefix);
	$table = array();
	for($i = 0; $i < count($semantic_class_title_prefix_table); $i++) {
		if(trim($semantic_class_title_prefix_table[$i]) <> '') $table[] = trim($semantic_class_title_prefix_table[$i]);
		}
	$semantic_class_title_prefix = implode(" / ",$table);
	if($semantic_class_title <> '') $semantic_class_text = $semantic_class_title_prefix." / ".$semantic_class_title;
	else $semantic_class_text = $semantic_class_title_prefix;
	return $semantic_class_text;
	}

function semantic_class_name($semantic_class_id) {
	global $bdd;
	$query_class2 = "SELECT semantic_class, semantic_class_title, semantic_class_title_prefix, cross_references FROM ".BASE.".classification WHERE semantic_class_id = \"".$semantic_class_id."\"";
	$result_class2 = $bdd->query($query_class2);
	$ligne_class2 = $result_class2->fetch();
	$result_class2->closeCursor();
	$semantic_class_title_prefix = $ligne_class2['semantic_class_title_prefix'];
	$semantic_class_title = $ligne_class2['semantic_class_title'];
	$class_title[$semantic_class_title] = TRUE;
	$semantic_class_text = semantic_class_text($semantic_class_title_prefix,$semantic_class_title);
	return $semantic_class_text;
	}

function list_cross_references($cross_references,$color,$short) {
	// Input example 1: A:I-1.17ai,aii; A:I-1.23c,d,e => for A01-01-01
	// Input example 2: A:I-1.3a,A:I-1.3b,A:I-1.3c => for A01-01-02
	// Input example 3: B:VI-3.5c,17, B:VI-3.5b,10,22 => for B06-03-02b
	// Input example 4: H:XXI-5.8b,10,11,H:XXI-5.12b => for H21-05-08a
	$cross_references = trim($cross_references);
//	$short = FALSE;
	if($cross_references == '') return '';
	$cross_references = str_replace("<br />",';',$cross_references);
	do $cross_references = str_replace("; ",";",$cross_references,$count);
	while($count > 0);
	do $cross_references = str_replace(", ",",",$cross_references,$count);
	while($count > 0);
	do $cross_references = str_replace(": ",":",$cross_references,$count);
	while($count > 0);
	$c = '';
	$length = strlen($cross_references);
	for($i = 0; $i < $length; $i++) {
		$char = $cross_references[$i];
		$char1 = $char2 = '';
		if($i < $length - 1) $char1 = $cross_references[$i+1];
		if($i < $length - 2) $char2 = $cross_references[$i+2];
		if($char1 == ',' AND ctype_upper($char1))
			$c .= ';';
		else
			$c .= $char;
		if(ctype_upper($char1) AND $char2 == ':' AND $char <> ';') $c .= ';';
		if(ctype_upper($char) AND $char1 == ';') {
			$c .= ':'; $i++;
			}
		}
	$cross_references = $c;
	$cross_references = str_replace(".,",',',$cross_references);
	$cross_references = str_replace(",;",';',$cross_references);
	$cross_references = str_replace(".;",';',$cross_references);
	$cross_references = str_replace(";;",';',$cross_references);
	$cross_references = str_replace(", ",';',$cross_references);
	$offset = 1;
	// echo "<br />@@@ cross_references=“".$cross_references."”<br />";
	while(is_integer(strpos($cross_references,' ',$offset))) {
		$pos = strpos($cross_references,' ',$offset);
		$offset = $pos + 2;
		$char = $cross_references[$pos+1];
		if(ctype_upper($char)) {
			$cross_references = substr_replace($cross_references,';',$pos,1);
			// echo "<br />@@@ space filled cross_references=“".$cross_references."”<br />";
			}
		}
	$cross_references = str_replace(";;",';',$cross_references);
	$cross_references = str_replace(" ",'',$cross_references);
	$table = array();
	if(!is_integer(strpos($cross_references,';'))) {
		$table = explode(',',$cross_references);
		}
	else $table = explode(';',$cross_references);
	$list = $old_reference = $old_reference2 = '';
	for($i = 0; $i < count($table); $i++) {
		$reference = $table[$i];
		if($reference == '') continue;
		if(is_integer(strpos($reference,','))) {
			$table2 = explode(',',$reference);
			for($j = 0; $j < count($table2); $j++) {
				$reference2 = trim($table2[$j]);
				if($reference2 == '') continue;
				$char = $reference2[0];
				if(ctype_upper($char)) {
					$text = text_of_reference($reference2);
					if($short) {
						if($list <> '') $list .= ", ";
						if($text == "???") $list .= $reference2."?";
						else $list .= $reference2;
						}
					else {
						if(!is_integer(strpos($list,$text)) OR $text == "???") {
							if($color <> '')
								$list .= "<span style=\"color:".$color.";\">".$reference2."</span> ".$text."<br />";
							else
								$list .= $reference2." ".$text;
							}
						}
					$old_reference2 = $reference2;
					}
				else {
					if($old_reference2 <> '') {
					/*	$table3 = explode('.',$old_reference2);
						$reference3 = $table3[0].".".$reference2; */
						$old_prefix = $old_reference2;
						if(ctype_digit($char)) {
							$table3 = explode('.',$old_reference2);
							$reference3 = $table3[0].".".$reference2;
							}
						else {
							do {
								$c = $old_prefix[strlen($old_prefix)-1];
								if(!ctype_lower($c)) break;
								$old_prefix = substr($old_prefix,0,strlen($old_prefix)-1);
								}
							while(TRUE);
							$reference3 = $old_prefix.$reference2;
							}
						$text = text_of_reference($reference3);
						if($short) {
							if($list <> '') $list .= ", ";
							if($text == "???") $list .= $reference3."?";
							else $list .= $reference3;
							}
						else {
							if(!is_integer(strpos($list,$text)) OR $text == "???") {
								if($color <> '')
									$list .= "<span style=\"color:".$color.";\">".$reference3."</span> ".$text."<br />";
								else
									$list .= $reference3." ".$text;
								}
							}
						}
					}
				}
			}
		else {
			$char = $reference[0];
			if(!is_numeric($char)) {
			//	$result = text_of_reference($reference);
				$text = text_of_reference($reference);
			//	$url = $result['url'];
				if($short) {
					if($list <> '') $list .= ", ";
					if($text == "???") $list .= $reference."?";
					// else $list .= "<a target=_blank  title=\"All songs in this class\" href=\"".$url."\">".$reference."</a>";
					else $list .= $reference;
					}
				else {
					if($color <> '')
						$list .= "<span style=\"color:".$color.";\">".$reference."</span> ".$text."<br />";
					else
						$list .= $reference." ".$text;
						
					}
				$old_reference = $reference;
				}
			else {
				if($old_reference <> '') {
					$table3 = explode('.',$old_reference);
					$reference3 = $table3[0].".".$reference;
				//	$result = text_of_reference($reference3);
					$text = text_of_reference($reference3);
				//	$url = $result['url'];
					if($short) {
						if($list <> '') $list .= ", ";
						if($text == "???") $list .= $reference3."?";
					//	else $list .= "<a target=_blank  title=\"All songs in this class\" href=\"".$url."\">".$reference3."</a>";
						else $list .= $reference3;
						}
					else {
						if(!is_integer(strpos($list,$text)) OR $text == "???") {
							if($color <> '')
								$list .= "<span style=\"color:".$color.";\">".$reference3."</span> ".$text."<br />";
							else
								
								$list .= $reference3." ".$text;
							}
						}
					}
				}
			}
		}
	return $list;
	}

function text_of_reference($reference) {
	global $bdd;
	$text = "???";
	$url = '';
	$reference = trim($reference);
	// $result['text'] = $text;
	// $result['url'] = '';
	if($reference == '') return $text;
	if(!is_integer(strpos($reference,':'))) //echo "ref=".$reference."$<br>";
		$reference = substr_replace($reference,':',1,0);
	$query_class = "SELECT semantic_class_id, semantic_class_title, semantic_class_title_prefix FROM ".BASE.".classification WHERE semantic_class = \"".$reference."\"";
	$result_class = $bdd->query($query_class);
	$n = $result_class->rowCount();
	if($n > 0) { 
		$ligne_class = $result_class->fetch();
		$semantic_class_title_prefix = $ligne_class['semantic_class_title_prefix'];
		$semantic_class_title = $ligne_class['semantic_class_title'];
		$text = semantic_class_text($semantic_class_title_prefix,$semantic_class_title);
		$url = SITE_URL."songs.php?semantic_class_id=".$ligne_class['semantic_class_id'];
		$text = "(".$ligne_class['semantic_class_id'].") - <a target=\"_blank\" title=\"All songs in this class\" href=\"".$url."\">".$text."</a>";
		}
	else {
		$query_class = "SELECT semantic_class_id, semantic_class_title, semantic_class_title_prefix FROM ".BASE.".classification WHERE semantic_class LIKE \"".$reference."%\" AND semantic_class_title <> ''";
		$result_class = $bdd->query($query_class);
		$n = $result_class->rowCount();
		if($n > 0) { 
		//	$ligne_class = mysql_fetch_array($result_class);
			$ligne_class = $result_class->fetch();
			$semantic_class_title_prefix = $ligne_class['semantic_class_title_prefix'];
			$semantic_class_title = '';
			$text = semantic_class_text($semantic_class_title_prefix,$semantic_class_title);
			$semantic_class_id = higher_level($ligne_class['semantic_class_id']);
			$url = SITE_URL."songs.php?semantic_class_id=".$semantic_class_id;
			$text = "(".$semantic_class_id.") - <a target=\"_blank\" title=\"All songs in this class\" href=\"".$url."\">".$text."</a>";		
			}
		}
//	$result['text'] = $text;
//	$result['url'] = $url;
	$result_class->closeCursor();
	return $text;
	}

function recording_url($dat_index,$ext) {
	if(trim($dat_index) == '') return '';
//	echo $dat_index."<br />";
	$table = explode('-',$dat_index);
	switch($ext) {
		case 'mp3':
		case 'm4a':
			$prefix = MP3_URL;
			break;
		case 'ogg':
			$prefix = OGG_URL;
			break;
		case 'aif':
			$prefix = AIFF_URL;
			break;
		default:
			echo "<br /><font color=red>ERROR: unknown extension</font> ‘".$ext."’<br />";
			break;
		}
	if(count($table) < 3) return $prefix.strtolower($dat_index).".".$ext;
	$url = $prefix."uvs-".$table[1]."/".$table[2].".".$ext;
/*	if(MP3_LOCATION == "PARI")
		$url = str_replace('_','/',$url); */
	return $url;
	}
	
function link_to_chunk($DAT_index) {
	if($DAT_index == '') return '';
	$table = explode("-",$DAT_index);
	if(count($table) < 3) return '';
	$tape = $table[0]."-".$table[1];
/*	$file = strtolower($tape).".ogg";
	$url = OGG_URL.$file; */
	$url = OGG_URL.strtolower($tape)."/".$table[2].".ogg";
	$link = "<a target=\"_blank\" href=\"".$url."\">listen to section</a>";
	return $link;
	}
	
function link_to_tape($DAT_index) {
	if($DAT_index == '') return '';
	$table = explode("-",$DAT_index);
	if(count($table) < 2) return '';
	$tape = $table[0]."-".$table[1];
	$file = strtolower($tape).".ogg";
	$url = OGG_URL.$file;
	$link = "<a target=\"_blank\" href=\"".$url."\">listen to tape</a>";
	return $link;
	}

function url_exists($url_a_tester) {
	$F = @fopen($url_a_tester,"r");
 	if($F) {
		fclose($F);
		return true;
		}
	else return false;
	} 

function performer_picture_url($performer_id,$url_there) {
	if(trim($url_there) == '') return '';
	if(trim($url_there) == "no") return '';
	$url = PICT_URL2."performers/".$performer_id.".jpg";
	if(url_exists($url)) return $url;
	$url = PICT_URL2."performers/".$performer_id.".jpeg";
	if(url_exists($url)) return $url;
	$url = PICT_URL2."performers/".$performer_id.".png";
	if(url_exists($url)) return $url;
	$url = PICT_URL."performers/".$performer_id.".jpg";
	if(url_exists($url)) return $url;
	$url = PICT_URL."performers/".$performer_id.".jpeg";
	if(url_exists($url)) return $url;
	$url = PICT_URL."performers/".$performer_id.".png";
	if(url_exists($url)) return $url;
	return $url_there;
	}

function location_picture($url) {
	if(trim($url) == '') return $url;
	if(PICT_URL == '') return $url;
	$table =  explode('_',$url);
	$url = PICT_URL."locations/".$table[1];
	return $url;
	}

function name_location_picture($url) {
	if(trim($url) == '') return $url;
	$table =  explode('_',$url);
	return $table[1];
	}

function fix_typo($line,$id) {
	$org_line = $line;
	$line = str_replace("...","…",$line);
	$line = str_replace("'","’",$line);
	$line = str_replace("‘","’",$line);
	$line = str_replace('"','”',$line);
	$line = str_replace("–",'-',$line);
/*	do $line = str_replace("  "," ",$line,$count);
	while($count > 0); */
	$line = preg_replace('/\s+/',' ',$line);
	$line = str_replace("« ","“",$line);
	$line = str_replace(" »","”",$line);
	$line = str_replace("’ s ","’s ",$line);
	if($id > 0) $line = str_replace(".",",",$line);
	$line = str_replace(" s* ","*s ",$line,$count);
	do $line = str_replace(" *","*",$line,$count);
	while($count > 0);
	$line = str_replace(",*","*,",$line);
	$line = str_replace(")*","*)",$line);
	$line = str_replace("’s*","*’s",$line);
	$line = str_replace("’*","*’",$line);
	$line = str_replace("**","*",$line);
	$line = str_replace("*s*","*s",$line);
	$line = str_replace("*’s*","*’s",$line);
	$line = str_replace("* s ","*s ",$line);
	$line = str_replace("<"," <",$line);
	
	$line = str_replace(" :",":",$line);
	$line = str_replace(" ?","?",$line);
	$line = str_replace(" !","!",$line);
	$line = str_replace(" ;",";",$line);
	$line = str_replace(" ,",",",$line);
	$line = str_replace(" .",".",$line);
	
	$line = str_replace("*)","§)",$line);
	$line = str_replace("*”","§”",$line);
	$line = str_replace("*?","§?",$line);
	$line = str_replace("*!","§!",$line);
	$line = str_replace("*;","§;",$line);
	$line = str_replace("*:","§:",$line);
	$line = str_replace("*,","§,",$line);
	$line = str_replace("*.","§.",$line);
	$line = str_replace("* <","§ <",$line);
	$line = str_replace("*s","§s",$line);
	$line = str_replace("*’s","§’s",$line);
	$line = str_replace("*","* ",$line);
	$line = str_replace("§","*",$line);
	$line = str_replace(" <","<",$line);
	$line = str_replace("( ","(",$line);
	$line = str_replace(" )",")",$line);
	$line = str_replace(" (","(",$line); // Added 16/4/2018
	$line = str_replace(") ",")",$line); // Added 16/4/2018
	$line = str_replace("("," (",$line); // Added 16/4/2018
	$line = str_replace(")",") ",$line); // Added 16/4/2018
	$line = str_replace(" ” ","” ",$line);
	$line = str_replace(" ”,","”,",$line);
	$line = str_replace(" ”)","”)",$line);
	$line = str_replace(", ",",",$line);
	$line = str_replace(",,",",",$line);
	if($id > 0) str_replace(",<","<",$line);
	$line = str_replace(",",", ",$line);
	$line = str_replace(" ,",",",$line); // Added 16/4/2018
	$line = str_replace(" ;",";",$line); // Added 16/4/2018
	$line = str_replace(" .",".",$line); // Added 16/4/2018
	$line = str_replace("..",".",$line);
	$line = str_replace("; ",";",$line);
	$line = str_replace(";","; ",$line);
/*	do $line = str_replace("  "," ",$line,$count);
	while($count > 0); */
	$line = preg_replace('/\s+/',' ',$line);
	return trim($line);
	}
	
function reshape_entry($text) {
	$text = str_replace(chr(11),"<br />",$text);
	$text = str_replace("\n","<br />",$text);
	$text = str_replace("\r","<br />",$text);
	$text = str_ireplace("<br>","<br />",$text);
	$text = str_replace(chr(13),"<br />",$text);
	$text = trim($text);
	$text = preg_replace("/\s+/u",' ',$text);
	do $text = str_replace("<br /><br />","<br />",$text,$count);
	while($count > 0);
	$text = str_replace(" <br />","<br />",$text);
	$text = str_replace("<br /> ","<br />",$text);
	$text = "@@@".$text."@@@";
	do {
		$text = str_replace("<br />@@@",'',$text,$count1);
		$text = str_replace("@@@<br />",'',$text,$count2);
		}
	while(($count1 + $count2) > 0);
	$text = str_replace("@@@",'',$text);
	$text = str_replace('"','“',$text);
	// $text = str_replace("â€™",'’',$text);
	$text = normalizer_normalize($text); // Added 12/02/2018
	$text = str_replace("â€™",'’',$text);
	$text = str_replace("â€™",'’',$text);
	$text = str_replace("â€¦",'…',$text);
	$text = trim($text);
	return $text;
	}

function my_keep_quotes($text) {
	$text = str_replace('"','“',$text);
	$text = str_ireplace("\n","<br />",$text);
	$text = str_ireplace("\r",'',$text);
	$text = str_replace("&nbsp;"," ",$text);
	$text = str_replace("...",'…',$text);
	$text = normalizer_normalize($text);
	return TrimReturn($text);
	}

function TrimReturn($texte) {
	$texte = "§bord§§".trim($texte)."§bord§§";
	do {
		$texte = str_replace(" §bord§§","§bord§§",$texte,$count1);
		$texte = str_replace("<br />§bord§§","§bord§§",$texte,$count2);
		$texte = str_replace("§bord§§ ","§bord§§",$texte,$count3);
		$texte = str_replace("§bord§§<br />","§bord§§",$texte,$count4);
		}
	while(($count1 + $count2 + $count3 + $count4) > 0);
	$texte = str_replace("§bord§§",'',$texte);
	return $texte;
	}

function creer_liens($texte) {
	// Traitement multibyte UTF8 ajouté le 28/10/2018
	$offset = 0;
	$texte = trim($texte);
	do {
		$pos3_1 = mb_strpos($texte,"https://",$offset);
		$pos3_2 = mb_strpos($texte,"http://",$offset);
		$pos3 = FALSE;
		if($pos3_1 <> FALSE AND $pos3_2 <> FALSE) {
			if($pos3_1 < $pos3_2) $pos3 = $pos3_1;
			else $pos3 = $pos3_2;
			}
		else {
			if($pos3_1 <> FALSE) $pos3 = $pos3_1;
			else $pos3 = $pos3_2;
			}
		if($pos3 === FALSE) return $texte;
		$pos2 = mb_strpos($texte,"=https",$offset);
		if($pos2 === FALSE) $pos2 = mb_strpos($texte,"=http",$offset);
		if($pos2 === FALSE) $pos2 = -2;
		$url_length = 0;
		if($pos3 > ($pos2 + 1)) {
			$url = $url_code = copier_url($texte,$pos3);
			$url_length = mb_strlen($url);
			if($url_length > 10) {
				$texte2 = mb_substr($texte, 0, $pos3);
				$url_code = str_replace(' ',"%20",$url_code);
				$texte2 = $texte2."<a target=\"_blank\" href=\"".$url_code."\">".$url."</a>";
				$texte2 = $texte2.mb_substr($texte, $pos3 + $url_length, mb_strlen($texte) - $url_length - $pos3);
				$texte = $texte2; 
				}
			}
		$offset = $pos3 + $url_length + mb_strlen($url_code) + mb_strlen("<a href= target=\"_blank\"></a>");
		}
	while($offset < (mb_strlen($texte) - 10));
	return $texte;
	}

function copier_url($ligne,$pos) {
	$l = $ligne." $";
	$l = str_replace("&gt;", '$', $l);
	$l = str_replace(') ', '$ ', $l);
	$l = str_replace(').', '$ ', $l);
	$l = str_replace('),', '$ ', $l);
	$l = str_replace(');', '$ ', $l);
	$l = str_replace(')…', '$ ', $l);
	$l = str_replace(')?', '$ ', $l);
	$l = str_replace(')!', '$ ', $l);
	$l = str_replace('. ', '$ ', $l);
	$l = str_replace(', ', '$ ', $l);
	$l = str_replace('- ', '$ ', $l);
	$l = str_replace('… ', '$ ', $l);
	$l = str_replace('? ', '$ ', $l);
	$l = str_replace(' ', '$', $l);
	$l = str_replace('—', '$', $l);
	$l = str_replace('<', '$', $l);
	$l = str_replace('>', '$', $l);
	$l = str_replace(';', '$', $l);
	$l = str_replace(']', '$', $l);
	$l = str_replace('\r', '$', $l);
	$l = str_replace('\n', '$', $l);
	$end = mb_strpos($l,'$',$pos);
	$url = trim(mb_substr($l,$pos,$end - $pos));
	return($url);
	}
	
function CleanGoogleQuotes($text) {
	$text = str_replace("'u","u",$text);
	$text = str_replace("'ā","ā",$text);
	$text = str_replace("'a","a",$text);
	$text = str_replace("'i","i",$text);
	$text = str_replace("'ō","ō",$text);
	$text = str_replace("'o","o",$text);
	$text = str_replace("'ū","ū",$text);
	$text = str_replace("'ī","ī",$text);
	$text = str_replace("'h","h",$text);
	$text = str_replace("'d","d",$text);
	$text = str_replace("'t","t",$text);
	$text = str_replace("'p","p",$text);
	$text = str_replace("'m","m",$text);
	$text = str_replace("'n","n",$text);
	$text = str_replace("'y","y",$text);
	$text = str_replace("ā̔","ā",$text);
	$text = str_replace("n̄","ñ",$text);
	$text = str_replace("n̄c","ñc",$text);
	$text = str_replace("r̥","ṛ",$text);
	$text = str_replace("'ṛ","ṛ",$text);
	$text = str_replace(";","; ",$text);
	$text = str_replace(".",". ",$text);
	$text = str_replace("  "," ",$text);
	return $text; 
	}

function done_by_google($roman) { // Obsolete
	$roman = trim($roman);
	if(mb_strlen($roman) < 20) return FALSE;
	if(is_integer(strpos($roman,"<br>"))) return TRUE;
	$firstchar = mb_substr($roman,0,1,"UTF-8");
    if(mb_strtolower($firstchar,"UTF-8") <> $firstchar) return TRUE;
	return FALSE;
	}

function ExportPerformers() {
	global $bdd;
	$message = "<font color=green>Exporting all changes to</font> <a href=\"".EXPORT."PerformersBiodata.tab\">PerformersBiodata.tab</a> <font color=green>and</font> <a href=\"".EXPORT."PerformersMetadata.tab\">PerformersMetadata.tab</a><br />";
	$export_file = @fopen(EXPORT."PerformersBiodata.tab",'w');
	if(!$export_file) {
		$message = "<font color=red>ERROR: can't create</font> ‘PerformersBiodata.tab’<br />";
		return $message;
		}
	else {
		vfprintf($export_file,"%s\r\n","// Do not edit!\n// File exported by ‘edit-performer.php’ and used by ‘UpdatePerformers.php’\n// It should be moved to the ‘settings’ folder\n");
		$query = "SELECT DISTINCT performer_id FROM ".BASE.".stories where performer_id > \"0\" ORDER BY performer_id ASC";
		$result = $bdd->query($query);
		$n = $result->rowCount();
		while($ligne = $result->fetch()) {
			$performer_id = $ligne['performer_id'];
			$query_english = "SELECT * FROM ".BASE.".stories WHERE performer_id = \"".$performer_id."\" AND story_english <> \"\" AND performer_location_id = \"0\" ORDER BY version DESC";
			$result_english = $bdd->query($query_english);
			$ligne_english = $result_english->fetch();
			$result_english->closeCursor();
			$story_english = $ligne_english['story_english'];
			$query_marathi = "SELECT * FROM ".BASE.".stories WHERE performer_id = \"".$performer_id."\" AND story_marathi <> \"\" AND performer_location_id = \"0\" ORDER BY version DESC";
			$result_marathi = $bdd->query($query_marathi);
			$ligne_marathi = $result_marathi->fetch();
			$result_marathi->closeCursor();
			$story_marathi = $ligne_marathi['story_marathi'];
			
			vfprintf($export_file,"%s\r\n",$performer_id."\t".$story_english."\t".$story_marathi);
			}
		$result->closeCursor();
		fclose($export_file);
		}
		
	$export_file = @fopen(EXPORT."PerformersMetadata.tab",'w');
	if(!$export_file) {
		$message = "<font color=red>ERROR: can't create</font> ‘PerformersMetadata.tab’<br />";
		return $message;
		}
	else {
		vfprintf($export_file,"%s\r\n","// Do not edit!\n// File exported by ‘edit-performer.php’ and used by ‘UpdatePerformers.php’\n// It should be moved to the ‘settings’ folder\n");
		$query = "SELECT DISTINCT performer_id FROM ".BASE.".stories where performer_location_id  > \"0\" ORDER BY performer_id ASC";
		$result = $bdd->query($query);
		$n = $result->rowCount();
		while($ligne = $result->fetch()) {
			$performer_id = $ligne['performer_id'];
			$query_metadata = "SELECT * FROM ".BASE.".stories WHERE performer_id = \"".$performer_id."\" AND performer_location_id > \"0\" ORDER BY version DESC";
		//	$result_metadata = mysql_query($query_metadata);
			$result_metadata = $bdd->query($query_metadata);
	//		$ligne_metadata = mysql_fetch_array($result_metadata);
			$ligne_metadata = $result_metadata->fetch();
			$performer_name_english = $ligne_metadata['performer_name_english'];
			$performer_name_devanagari = $ligne_metadata['performer_name_devanagari'];
			$performer_gender = $ligne_metadata['performer_gender'];
			$performer_caste_english = $ligne_metadata['performer_caste_english'];
			$performer_caste_devanagari  = $ligne_metadata['performer_caste_devanagari'];
			$performer_picture = $ligne_metadata['performer_picture'];
			$performer_photo_credit  = $ligne_metadata['performer_photo_credit'];
			$performer_location_id = $ligne_metadata['performer_location_id'];
			vfprintf($export_file,"%s\r\n",$performer_id."\t".$performer_name_english."\t".$performer_name_devanagari."\t".$performer_gender."\t".$performer_caste_english."\t".$performer_caste_devanagari."\t".$performer_picture."\t".$performer_photo_credit."\t".$performer_location_id);
			}
		$result->closeCursor();
		fclose($export_file);
		}
	return $message;
	}

function ExportLocations() {
	global $bdd;
	$message = "<font color=green>Exporting all changes to</font> ‘<a href=\"".EXPORT."LocationDescriptions.tab\">LocationDescriptions.tab</a>’<br />";
	$export_file = @fopen(EXPORT."LocationDescriptions.tab",'w');
	if(!$export_file) {
		$message = "<font color=red>ERROR: can't create</font> ‘LocationDescriptions.tab’<br />";
		return $message;
		}
	else {
		vfprintf($export_file,"%s\r\n","// Do not edit!\n// File exported by ‘edit-location.php’ and used by ‘UpdateLocations.php’\n// It should be moved to the ‘settings’ folder\n");
		$query = "SELECT DISTINCT location_id FROM ".BASE.".stories where location_id <> \"\" ORDER BY location_id ASC";
	/*	$result = mysql_query($query);
		$n = mysql_num_rows($result); */
		$result = $bdd->query($query);
		$n = $result->rowCount();
	//	while($ligne = mysql_fetch_array($result)) {
		while($ligne = $result->fetch()) {
			$location_id = $ligne['location_id'];
			$query_english = "SELECT * FROM ".BASE.".stories WHERE location_id = \"".$location_id."\" AND story_english <> \"\" ORDER BY version DESC";
	//		$result_english = mysql_query($query_english);
			$result_english = $bdd->query($query_english);
	//		$ligne_english = mysql_fetch_array($result_english);
			$ligne_english = $result_english->fetch();
			$result_english->closeCursor();
			$story_english = $ligne_english['story_english'];
			
			$query_marathi = "SELECT * FROM ".BASE.".stories WHERE location_id = \"".$location_id."\" AND story_marathi <> \"\" ORDER BY version DESC";
		/*	$result_marathi = mysql_query($query_marathi);
			$ligne_marathi = mysql_fetch_array($result_marathi); */
			$result_marathi = $bdd->query($query_marathi);
			$ligne_marathi = $result_marathi->fetch();
			$result_marathi->closeCursor();
			$story_marathi = $ligne_marathi['story_marathi'];
			
			vfprintf($export_file,"%s\r\n",$location_id."\t".$story_english."\t".$story_marathi);
			}
		$result->closeCursor();
		fclose($export_file);
		}
	return $message;
	}
	
function ExportSongMetadata() {
	global $bdd;
	$message = "<font color=green>Exporting all changes to</font> ‘<a href=\"".EXPORT."SongMetadata.txt\">SongMetadata.txt</a>’<br />";
	$export_file = @fopen(EXPORT."SongMetadata.txt",'w');
	if(!$export_file) {
		$message = "<font color=red>ERROR: can't create</font> ‘SongMetadata.txt’<br />";
		return $message;
		}
	else {
		vfprintf($export_file,"%s\r\n","// Do not edit!\n// File exported by ‘edit-songs.php’ and used by ‘UpdateSongMetadata.php’\n// It should be moved to the ‘settings’ folder\n");
		$query = "SELECT * FROM ".BASE.".song_metadata ORDER BY song_id";
		$result = $bdd->query($query);
		$n = $result->rowCount();
		while($ligne = $result->fetch()) {
			$song_id = $ligne['song_id'];
			$song_number = $ligne['song_number'];
			$devanagari = $ligne['devanagari'];
			$roman_devanagari = $ligne['roman_devanagari'];
			$performer_id = $ligne['performer_id'];
			$location_id = $ligne['location_id'];
			$semantic_class_id = $ligne['semantic_class_id'];
			$recording_DAT_index = $ligne['recording_DAT_index'];
			$time_code_start = $ligne['time_code_start'];
			$separate_recording = $ligne['separate_recording'];
			$remarks_marathi = $ligne['remarks_marathi'];
			$remarks_english = $ligne['remarks_english'];
			vfprintf($export_file,"%s\r\n",$song_id."\t".$song_number."\t".$recording_DAT_index."\t".$separate_recording."\t".$time_code_start."\t".$devanagari."\t".$roman_devanagari."\t".$remarks_marathi."\t".$remarks_english."\t".$performer_id."\t".$semantic_class_id."\t".$location_id);
			}
		$result->closeCursor();
		fclose($export_file);
		}
	return $message;
	}

function ExportTranslations() {
	global $bdd;
	$message = "<font color=green>Exporting translation changes to</font> <a href=\"".EXPORT."NewTranslations.txt\">NewTranslations.txt</a><br />";
	$export_file = @fopen(EXPORT."NewTranslations.txt",'w');
	if(!$export_file) {
		$message = "<font color=red>ERROR: can't create</font> ‘NewTranslations.txt’<br />";
		return $message;
		}
	else {
		vfprintf($export_file,"%s\r\n","// Do not edit!\n// File exported by ‘edit-translations.php’ and used by ‘filemaker2utf8.php’\n// It should be moved to the ‘settings’ folder\n// When more than 3 lines, ‘br’ line-breaks merge additional lines.\n");
		$query = "SELECT DISTINCT song_id FROM ".BASE.".translations ORDER BY song_id ASC";
		$result = $bdd->query($query);
		$n = $result->rowCount();
		while($ligne = $result->fetch()) {
			$id = $ligne['song_id'];
			$query2 = "SELECT text FROM ".BASE.".translations WHERE song_id = \"".$id."\" ORDER BY version DESC";
			$result2 = $bdd->query($query2);
			$ligne2 = $result2->fetch();
			$result2->closeCursor();
			$text = $ligne2['text'];
			$text = str_replace("<br />","\n",$text);
			$text = fix_number_of_lines($text,3);
			vfprintf($export_file,"%s\r\n",$id."\r\n".$text);
			}
		$result->closeCursor();
		fclose($export_file);
		}
	return $message;
	}

function fix_number_of_lines($text,$nmax) {
	$table = explode("\n",$text);
	$n = count($table);
	if($n > $nmax) {
		$text = $table[0];
		for($i = 1; $i < $n; $i++) {
			if($i <= ($nmax - 1))
				$text .= "\n".$table[$i];
			else 
				$text .= "<br />".$table[$i];
			}
		}
	return $text;
	}
	
function check_DAT_index($dat_index,$id) {
	global $errors,$warnings;
	$old_errors = $errors;
	$result['correct'] = TRUE;
	$result['dat_index'] = $dat_index;
	$result['message'] = '';
	if($dat_index == '') return $result;
	$old_dat_index = $dat_index;
	$dat_index = strtoupper($dat_index);
	$dat_index = str_replace(" ",'',$dat_index);
	$dat_index = str_replace(".","-",$dat_index);
	$dat_index = str_replace("_","-",$dat_index);
	$dat_index = str_replace(":","-",$dat_index);
	do $dat_index = str_replace("--","-",$dat_index,$count);
	while($count > 0);
	$tape_nr = $index_nr = "???";
	$table = explode("-",$dat_index);
	$count = count($table);
	if($count == 3) {
		$prefix = $table[0];
		if($prefix == "UVS" OR $prefix == "KAR" OR $prefix == "GAN") {
			$tape = $table[1];
			$tape_nr = intval($tape);
			if(strlen($tape) < 3) {
				$tape_nr = intval($tape);
				if($tape_nr < 10) {
					$tape_nr = "0".$tape_nr;
					if(!ctype_digit($tape)) {
						$result['message'] = "<font color=green>WARNING: fixed DAT index (tape number < 10) in</font> “".$old_dat_index."”</font><br />";
						$warnings++;
						}
					}
				if($tape_nr > 0 AND $tape_nr < 100) {
					$index = $table[2];
					$index_nr = intval($index);
					if($index_nr < 10 AND $index_nr > 0) $index_nr = "0".$index_nr;
					$dat_index = $prefix."-".$tape_nr."-".$index_nr;
					if(strlen($index) > 2 AND $index_nr > 0 AND $index_nr < 99) {
						$result['message'] = "<font color=green>WARNING: fixed DAT index (".strlen($index)." chars)</font> “".$index."” <font color=green> replaced with </font> “".$index_nr."” in song_id = <font color=green>".$id."</font><br />";
						$warnings++;
						}
					if(!ctype_digit($index)) {
						$result['message'] = "<font color=green>WARNING: fixed DAT index (index number < 10)</font> “".$old_dat_index."” <font color=green> replaced with </font> “".$dat_index."” in song_id = <font color=green>".$id."</font><br />";
						$warnings++;
						}
					$dat_index = $prefix."-".$tape_nr."-".$index_nr;
					$result['dat_index'] = $dat_index;
					if($index_nr > 0 AND $index_nr < 99)
						return $result;
					else {
						$result['message'] = "<font color=red>Incorrect DAT index </font> “".$index."” <font color=red> (out of range)</font><br />";
						$errors++;
						}
					}
				else {
					$result['message'] = "<font color=red>Incorrect DAT index ‘".$old_dat_index."’, tape number</font> “".$tape."” <font color=red> out of range</font><br />";
					$errors++;
					}
				}
			else {
				$result['message'] = "<font color=red>Incorrect DAT index ‘".$old_dat_index."’, too long tape number</font> “".$tape."”<br />";
				$errors++;
				}
			}
		else {
			$result['message'] = "<font color=red>Wrong DAT index prefix in ‘".$old_dat_index."’, should be ‘UVS’</font><br />";
			$errors++;
			}
		}
	else {
		$result['message'] = "<font color=red>Missing tape number or index in this DAT index</font><br />";
		$errors++;
		}
//	echo "<font color=red>*** ERROR: DAT index =</font> “".$dat_index."” <font color=red>in</font> song_id = <font color=green>".$id."</font><br />";
	$result['dat_index'] = $dat_index;
	if($errors > $old_errors) $result['correct'] = FALSE;
	return $result;
	}

function flag_incorrect_DAT_index($id) {
	global $bdd;
	$query = "SELECT recording_DAT_index FROM ".BASE.".songs WHERE song_id = \"".$id."\"";
	$result = $bdd->query($query);
	$ligne = $result->fetch();
	$dat_index = $ligne['recording_DAT_index'];
	$result->closeCursor();
	$result_check_DAT_index = check_DAT_index($dat_index,$id);
	if(!$result_check_DAT_index['correct']) return "&nbsp;<small><font color=red>".$dat_index.": time-code?</font></small> ";
	else return '';
	}

function current_workset_id($user) {
	global $bdd;
	if(!is_translator($user)) {
		echo "User = ‘".$login."’<br />";
	//	die();
		return 0;
		}
	$query = "SELECT set_id FROM ".BASE.".workset WHERE status = \"current\" AND login = \"".$user."\" LIMIT 1";
//	echo $query."<br />";
	$result = $bdd->query($query);
	if($result) {
		$n = $result->rowCount();
		if($n > 0) {
			$ligne = $result->fetch();
			$result->closeCursor();
			$set_id = $ligne['set_id'];
	//		echo "Known set_id = ".$set_id."<br />";
			return $set_id;
			}
		}
	$query = "SELECT set_id FROM ".BASE.".workset ORDER BY set_id DESC LIMIT 1";
	$result = $bdd->query($query);
	if($result) {
		$n = $result->rowCount();
		if($n > 0) {
			$ligne = $result->fetch();
			$result->closeCursor();
			$set_id = $ligne['set_id'] + 1;
	//		echo "New set_id = ".$set_id."<br />";
			return $set_id;
			}
		}
	return 1;
	}
	
function other_work_set($song_id,$user,$status) {
	global $bdd;
	$query_there = "SELECT login, set_id FROM ".BASE.".workset WHERE song_id = \"".$song_id."\"";
	if($user <> '')
		$query_there .= " AND login = \"".$user."\"";
	if($status <> '')
		$query_there .= " AND status = \"".$status."\"";
	$query_there .= " ORDER BY set_id ASC LIMIT 1";
//	echo $query_there."<br />";
	$result_there = $bdd->query($query_there);
	if($result_there) {
		$n_there = $result_there->rowCount();
		$ligne = $result_there->fetch();
		$result_there->closeCursor();
		if(isset($ligne['set_id'])) $set_id = $ligne['set_id'];
		else return 0;
		if($n_there > 0) return $set_id;
		}
	return 0;
	}

function set_user($set_id) {
	global $bdd;
	$query_there = "SELECT login FROM ".BASE.".workset WHERE set_id = \"".$set_id."\" ORDER BY set_id ASC LIMIT 1";
	$result_there = $bdd->query($query_there);
	if($result_there) {
		$n_there = $result_there->rowCount();
		$ligne = $result_there->fetch();
		$login = $ligne['login'];
		$result_there->closeCursor();
		if($n_there > 0) return $login;
		}
	return '';
	}

function empty_current_set($user) {
	global $bdd;
	$query_there = "SELECT login FROM ".BASE.".workset WHERE login = \"".$user."\" AND status = \"current\"";
	$result_there = $bdd->query($query_there);
	if($result_there) {
		$n_there = $result_there->rowCount();
		$result_there->closeCursor();
		if($n_there > 0) return FALSE;
		}
	return TRUE;
	}

/* function translation_english($song_id) {
	global $bdd;
	$query = "SELECT translation_english FROM ".BASE.".songs WHERE song_id = \"".$song_id."\"";
	$result = $bdd->query($query);
	if(!$result) return '';
	$ligne = $result->fetch();
	$result->closeCursor();
	return $ligne['translation_english'];
	} */

function work_version($song_id,$user,$type) {
	global $bdd;
	$query = "SELECT ".$type." FROM ".BASE.".workset WHERE login = \"".$user."\" AND song_id = \"".$song_id."\" ORDER BY set_id DESC LIMIT 1";
	$result = $bdd->query($query);
	if(!$result) return '';
	$ligne = $result->fetch();
	$result->closeCursor();
	return $ligne[$type];
	}

/* function workset_id($song_id) {
	global $bdd;
	$query = "SELECT set_id FROM ".BASE.".workset WHERE song_id = \"".$song_id."\"";
	$result = $bdd->query($query);
	if(!$result) return '';
	$ligne = $result->fetch();
	$result->closeCursor();
	return $ligne['set_id'];
	} */
	
function transcription($song_id,$type) {
	global $bdd;
	switch($type) {
		case "devanagari":
			$field = $type;
			break;
		case "word_ids":
			$field = $type;
			break;
		case "remarks_marathi":
			$field = $type;
			break;
		case "remarks_english":
			$field = $type;
			break;
		case "roman":
			$field = "roman_devanagari";
			break;
		case "translation":
			$field = "translation_english";
			break;
		}
	$query = "SELECT ".$field." FROM ".BASE.".songs WHERE song_id = \"".$song_id."\"";
	$result = $bdd->query($query);
	if(!$result) return '';
	$ligne = $result->fetch();
	$result->closeCursor();
	return reshape_entry($ligne[$field]);
	}

function busy_in_work_set($song_id) {
	if(($set_id = other_work_set($song_id,'',"current")) > 0 OR ($set_id = other_work_set($song_id,'',"stored")) > 0 OR ($set_id = other_work_set($song_id,'',"submit")) > 0) return $set_id;
	else return 0;
	}

function remarks($song_id) {
	global $bdd;
	$remarks['marathi'] = $remarks['english'] = '';
	$query = "SELECT remarks_marathi, remarks_english FROM ".BASE.".songs WHERE song_id = \"".$song_id."\"";
	$result = $bdd->query($query);
	if(!$result) return $remarks;
	$ligne = $result->fetch();
	$result->closeCursor();
	$remarks['marathi'] = trim($ligne['remarks_marathi']);
	$remarks['english'] = trim($ligne['remarks_english']);
	return $remarks;
	}

function save_remarks($song_id,$user) {
	global $bdd;
	echo "<blockquote><font color=red>Saving changes to #".$song_id."</font></blockquote>";
	$remarks_marathi = fix_typo(reshape_entry($_POST['remarks_marathi']),0);
	$remarks_english = fix_typo(reshape_entry($_POST['remarks_english']),0);
	$query_update = "UPDATE ".BASE.".songs SET remarks_marathi = \"".$remarks_marathi."\", remarks_english = \"".$remarks_english."\" WHERE song_id = \"".$song_id."\"";
//	echo $query_update."<br />";
	$result_update = $bdd->query($query_update);
	if(!$result_update) {
		echo "<br /><font color=red>ERROR modifying table:</font> ".$query_update."<br />";
		die();
		}
	$result_update->closeCursor();
	
	// Enter this change in table ‘song_metadata’
	if($remarks_marathi == '') $remarks_marathi = '~';
	if($remarks_english == '') $remarks_english = '~';
	$query_there = "SELECT song_id FROM ".BASE.".song_metadata WHERE song_id = \"".$song_id."\" AND devanagari = \"\" AND roman_devanagari = \"\"";
	$result_there = $bdd->query($query_there);
	$n_there = $result_there->rowCount();
	if($n_there > 0) $already_there_metadata = TRUE;
	else $already_there_metadata = FALSE;
	if($already_there_metadata) {
		$query_update = "UPDATE ".BASE.".song_metadata SET remarks_marathi = \"".$remarks_marathi."\", remarks_english = \"".$remarks_english."\" WHERE song_id = \"".$song_id."\" AND devanagari = \"\" AND roman_devanagari = \"\"";
	//	echo $query_update."<br />";
		$result_update = $bdd->query($query_update);
		if(!$result_update) {
			echo "<br /><font color=red>ERROR modifying table:</font> ".$query_update."<br />";
			die();
			}
		$result_update->closeCursor();
		}
	else {
		$query_update = "INSERT INTO ".BASE.".song_metadata (song_id, remarks_marathi, remarks_english, login, devanagari, roman_devanagari) VALUES (\"".$song_id."\",\"".$remarks_marathi."\",\"".$remarks_english."\",\"".$user."\",\"\",\"\")";
	//	echo $query_update."<br />";
		$result_update = $bdd->query($query_update);
		if(!$result_update) {
			echo "<br /><font color=red>ERROR modifying table:</font> ".$query_update."<br />";
			die();
			}
		$result_update->closeCursor();
		}
	}

function semantic_class_id($song_id) {
	global $bdd;
	$query = "SELECT semantic_class_id FROM ".BASE.".songs WHERE song_id = \"".$song_id."\"";
	$result = $bdd->query($query);
	if(!$result) return '';
	$ligne = $result->fetch();
	$result->closeCursor();
	return $ligne['semantic_class_id'];
	}
	
function check_semantic_id($length,$semantic_class_id) {
	$good = TRUE;
//	echo $semantic_class_id."<br />";
	$alpha = substr($semantic_class_id,0,1);
	if(!ctype_alpha($alpha)) $good = FALSE;
	$num = substr($semantic_class_id,1,2);
	if(!ctype_digit($num)) $good = FALSE;
	if($length == "start" AND strlen($semantic_class_id) < 6) {
		if(!$good) echo "<span style=\"color:red;\">Incorrect semantic ID</span> ";
		return $good;
		}
	$hyphen = substr($semantic_class_id,3,1);
	if($hyphen <> '-') $good = FALSE;
	$num = substr($semantic_class_id,4,2);
	if(!ctype_digit($num)) $good = FALSE;
	if(strlen($semantic_class_id) == 6) {
		$num = substr($semantic_class_id,4,2);
		if(!ctype_digit($num)) $good = FALSE;
		}
	else {
		if(strlen($semantic_class_id) == 7) {
			$num = substr($semantic_class_id,4,3);
			if(!ctype_digit($num)) $good = FALSE;
			}
		else {
			$hyphen = substr($semantic_class_id,6,1);
			if($hyphen <> '-') $good = FALSE;
			if(strlen($semantic_class_id) > 6) {
				$num = substr($semantic_class_id,7,2);
				if(!ctype_digit($num)) $good = FALSE;
				}
			if(strlen($semantic_class_id) > 9) {
				$alpha = substr($semantic_class_id,9,1);
				if(!ctype_alpha($alpha)) $good = FALSE;
				}
			if(strlen($semantic_class_id) > 10) {
				$num = substr($semantic_class_id,10,2);
				if(!ctype_digit($num)) $good = FALSE;
				}
			}
		}
	if(!$good) echo "<span style=\"color:red;\">Incorrect semantic ID</span> ";
	return $good;
	}

function check_semantic_class_prefix($prefix) {
	global $bdd;
	$good = FALSE;
	$query = "SELECT semantic_class_id FROM ".BASE.".classification";
//	echo $query."<br />";
	$result = $bdd->query($query);
	while($ligne = $result->fetch()) {
		$class = $ligne['semantic_class_id'];
		if(is_integer($pos=strpos($class,$prefix)) AND $pos == 0) {
			$good = TRUE;
			break;
			}
		}
	$result->closeCursor();
	return $good;
	}

function semantic_class_id_given_class($semantic_class) {
	global $bdd;
	$semantic_class = trim($semantic_class);
	$semantic_class = str_replace('—','-',$semantic_class);
	$semantic_class = str_replace(' ','',$semantic_class);
	if($semantic_class == '') return '';
	$query = "SELECT semantic_class_id FROM ".BASE.".classification WHERE semantic_class = \"".$semantic_class."\"";
//	echo $query."<br />";
	$result = $bdd->query($query);
	if(!$result) return '';
	$ligne = $result->fetch();
	$result->closeCursor();
	return $ligne['semantic_class_id'];
	}

function semantic_class_given_id($semantic_class_id) {
	global $bdd;
	if($semantic_class_id == '') return '';
	$query = "SELECT semantic_class FROM ".BASE.".classification WHERE semantic_class_id = \"".$semantic_class_id."\"";
	$result = $bdd->query($query);
	if(!$result) return "???";
	$ligne = $result->fetch();
	$result->closeCursor();
	return $ligne['semantic_class'];
	}

function semantic_class_text_of_song($song_id) {
	global $bdd, $login;
	$semantic_class_id = semantic_class_id($song_id);
	$query_class = "SELECT semantic_class, semantic_class_title, semantic_class_title_prefix FROM ".BASE.".classification WHERE semantic_class_id = \"".$semantic_class_id."\"";
	$result_class = $bdd->query($query_class);
	if(!$result_class) return '';
	$ligne = $result_class->fetch();
	$result_class->closeCursor();
	$semantic_class = $ligne['semantic_class'];
	$semantic_class_title_prefix = $ligne['semantic_class_title_prefix'];
	$semantic_class_title = $ligne['semantic_class_title'];
	$semantic_class_text_of_song = "<b><span style=\"color:MediumTurquoise;\">".$semantic_class."</span> </b>(<a target=\"_blank\" href=\"songs.php?semantic_class_id=".$semantic_class_id."\">".$semantic_class_id."</a>) <b>— <span style=\"color:MediumTurquoise;\">".semantic_class_text($semantic_class_title_prefix,$semantic_class_title)."</span></b>";
	if(is_translator($login)) $semantic_class_text_of_song .= " <small><span style=\"color:MediumTurquoise;\">➡</span>&nbsp;<a target=\"_blank\" href=\"edit-classification.php?class=".$semantic_class_title_prefix."\">Edit&nbsp;or&nbsp;comment this class…</a></small>";
	return $semantic_class_text_of_song;
	}

function fix_lines($item) {
	set_time_limit(1000);
	$item = str_replace("\r",'',$item);
	$i = 0;
	do {
		$item = str_replace("<br /><br />","<br />",$item,$count);
		if($count > 0) $i++;
		}
	while($count > 0);
	$item = "@@@".$item."@@@";
	do {
		$item = str_replace("<br />@@@",'',$item,$count1);
		$item = str_replace("@@@<br />",'',$item,$count2);
		}
	while(($count1 + $count2) > 0);
	$item = str_replace("@@@",'',$item);
	$item = trim($item);
	return $item;
	}

function fix_uppercase($item,$return) {
	set_time_limit(1000);
	$table = explode($return,$item);
	$result = '';
	for($i = 0; $i < count($table); $i++) {
		$line = ucfirst(trim($table[$i]));
		if($result <> '') $result .= $return;
		$result .= $line;
		}
	return $result;
	}

function first_lowercase($item) {
	set_time_limit(1000);
	$table = explode("<br />",$item);
	$result = '';
	for($i = 0; $i < count($table); $i++) {
		$line = lcfirst($table[$i]);
		if($result <> '') $result .= "<br />";
		$result .= $line;
		}
	return $result;
	}

function short_list($list,$number) {
	$short_list = '';
	if(trim($list) == '' OR !is_integer(strpos($list,","))) return $list;
	if($number == 0) return $list; // keep entire list
	$table = explode(",",$list);
	$count = count($table);
	for($i = 0; $i < $count AND $i < $number; $i++) {
		$word = trim($table[$i]);
		if($i > 0) $short_list .= ", ";
		$short_list .= $word;
		}
	if($i < $count) $short_list .= " etc. (".$count." entries)";
	return $short_list;
	}

function spelling_marks($lang,$text,$colour) {
	global $bdd;
	$spelling_marks = '';
	$text = trim($text);
	if($text == '') return '';
	$text = str_replace("<br />"," [br] ",$text);
	$table = explode(" ",$text);
	$count = count($table);
	switch($lang) {
		case 'en': $the_table = "lexicon"; break;
		case 'fr': $the_table = "lexicon_fr"; break;
		case 'mr': $the_table = "lexicon_mr"; break;
		case 'ro': $the_table = "lexicon_ro"; break;
		default: $the_table = "lexicon";
		}
	for($i=0; $i < $count; $i++) {
		if($i > 0) $spelling_marks .= " ";
		$word = $table[$i];
		if($word == "[br]")
			$spelling_marks .= "<br />";
		else {
			$plain_word = str_replace("*"," * ",$word);
			$plain_word = str_replace("/"," / ",$plain_word);
			$plain_word = str_replace("("," ( ",$plain_word);
			$plain_word = str_replace(")"," ) ",$plain_word);
			$plain_word = str_replace("."," . ",$plain_word);
			$plain_word = str_replace(","," , ",$plain_word);
			$plain_word = str_replace(";"," ; ",$plain_word);
			$plain_word = str_replace("?"," ? ",$plain_word);
			$plain_word = str_replace("!"," ! ",$plain_word);
			$plain_word = str_replace("“"," “ ",$plain_word);
			$plain_word = str_replace("”"," ” ",$plain_word);
			$plain_word = str_replace("«"," « ",$plain_word);
			$plain_word = str_replace("»"," » ",$plain_word);
			$plain_word = str_replace('"',' " ',$plain_word);
			$plain_word = str_replace("'"," ' ",$plain_word);
			$plain_word = str_replace("’"," ’ ",$plain_word);
			$plain_word = str_replace(":"," : ",$plain_word);
			$plain_word = str_replace("-"," - ",$plain_word);
			$plain_word = str_replace("—"," — ",$plain_word);
		/*	do $plain_word = str_replace("  "," ",$plain_word,$count2);
			while($count2 > 0); */
			$plain_word = preg_replace('/\s+/',' ',$plain_word);
			$plain_word = trim($plain_word);
			$table2 = explode(" ",$plain_word);
			for($j=0; $j < count($table2); $j++) {
				$word_part = trim($table2[$j]);
				if($word_part <> '') {
					$query = "SELECT id FROM ".BASE.".".$the_table." WHERE word = \"".$word_part."\" LIMIT 1";
					$result = $bdd->query($query);
					$n = $result->rowCount();
					$result->closeCursor();
				//	if($lang == "mr") echo $query." ".$n."<br />";
					if($n < 1) $word_part = "<span style=\"color:".$colour.";\">".$word_part."</span>";
					}
				$spelling_marks .= $word_part;
				}
			}
		}
	$spelling_marks = str_replace(" <br /> ","<br />",$spelling_marks);
	return trim($spelling_marks);
	}

function StoreSpelling($delete,$lang,$text) {
	global $bdd;
	if($text == '') return;
	$text = str_replace("[???]",'',$text);
	$text = str_replace( "<br />"," [br] ",$text);
	$text = str_replace("("," ( ",$text);
	$text = str_replace(")"," ) ",$text);
	$text = str_replace("/"," / ",$text);
	$text = str_replace("."," . ",$text);
	$text = str_replace(","," , ",$text);
	$text = str_replace(";"," ; ",$text);
	$text = str_replace("?"," ? ",$text);
	$text = str_replace("!"," ! ",$text);
	$text = str_replace("“"," “ ",$text);
	$text = str_replace("”"," ” ",$text);
	$text = str_replace("«"," « ",$text);
	$text = str_replace("»"," » ",$text);
	$text = str_replace('"',' " ',$text);
	$text = str_replace("'"," ' ",$text);
	$text = str_replace("’"," ’ ",$text);
	$text = str_replace(":"," : ",$text);
	$text = str_replace("-"," - ",$text);
	$text = str_replace("—"," — ",$text);
	$text = str_replace("*"," * ",$text);
/*	do $text = str_replace("  "," ",$text,$count2);
	while($count2 > 0); */
	$text = preg_replace('/\s+/',' ',$text);
	$table = explode(" ",$text);
	$count = count($table);
	switch($lang) {
		case 'en': $the_table = "lexicon"; break;
		case 'fr': $the_table = "lexicon_fr"; break;
		case 'mr': $the_table = "lexicon_mr"; break;
		case 'ro': $the_table = "lexicon_ro"; break;
		default: $the_table = "lexicon";
		}
	for($i=0; $i < $count; $i++) {
		$word = trim($table[$i]);
		if($word <> '' AND $word <> "[br]") {
			$plain_word = trim($word);
			if($plain_word <> '') {
				if(!$delete) {
					$query = "SELECT id, word FROM ".BASE.".".$the_table." WHERE word = \"".$plain_word."\" LIMIT 1";
					$result = $bdd->query($query);
				//	if($lang == "mr") echo $query."<br />";
					$n = $result->rowCount();
					if($n < 1) {
						$query_update = "INSERT INTO ".BASE.".".$the_table." (word) VALUES (\"".$plain_word."\")";
					//	if($lang == "mr") echo $query_update."<br />";
						$result_update = $bdd->query($query_update);
						if(!$result_update) {
							echo "<br /><font color=red>ERROR modifying table:</font> ".$query_update."<br />";
							die();
							}
						$result_update->closeCursor();
						}
					else {
						$ligne = $result->fetch();
					//	if($lang == "mr") echo "@".$ligne['word']."<br />";
						}
					$result->closeCursor();
					}
				else {
					$query = "DELETE FROM ".BASE.".".$the_table." WHERE word = \"".$plain_word."\"";
					$result = $bdd->query($query);
					$n = $result->rowCount();
					$result->closeCursor();
					}
				}
			}
		}
	return;
	}

function ResetLexicon($lang,$song_id) {
	global $bdd;
	$query = "SELECT translation_english, translation_french, devanagari, roman_devanagari FROM ".BASE.".songs WHERE song_id = \"".$song_id."\"";
	$result = $bdd->query($query);
	if($result) {
		$ligne = $result->fetch();
		$result->closeCursor();
		$text = '';
		if($lang == 'en') $text = $ligne['translation_english'];
		if($lang == 'fr') $text = $ligne['translation_french'];
		if($lang == 'mr') $text = $ligne['devanagari'];
		if($lang == 'ro') $text = $ligne['roman_devanagari'];
	//	echo $text."<br />";
		StoreSpelling(TRUE,$lang,$text);
		}
	return;
	}

function fix_time_code($time) {
	$time = trim($time);
	$time = preg_replace('/\s+/',' ',$time);
	$time = str_replace(' ',':',$time);
	$time = str_replace("???",'',$time);
	if($time == '') return '';
	$time = str_replace('.',':',$time);
	$time = str_replace('-',':',$time);
	$time = str_replace('/',':',$time);
	$time = str_replace(',',':',$time);
	$time = str_replace(';',':',$time);
	$time = str_replace("'",':',$time);
	$time = str_replace('"',':',$time);
	$time = str_replace('”',':',$time);
	$time = str_replace('’',':',$time);
	$time = str_ireplace("hour",':',$time);
	$time = str_ireplace("h",':',$time);
	$time = str_ireplace("minute",':',$time);
	$time = str_ireplace("min",':',$time);
	$time = str_ireplace("mn",':',$time);
	$time = str_ireplace("seconde",'',$time);
	$time = str_ireplace("second",'',$time);
	$time = str_ireplace("sec",'',$time);
	$time = str_ireplace("s",'',$time);
	do $time = str_replace("::",':',$time,$count);
	while($count > 0);
	$table = explode(":",$time);
	$count = count($table);
	$seconds = intval($table[$count-1]);
	if($count > 1) $seconds += 60 * intval($table[$count-2]);
	if($count > 2) $seconds += 3600 * intval($table[$count-3]);
	$time_code = seconds_to_time_code($seconds);
//	echo $time." = ".$seconds." = ".$time_code."<br />";
	return $time_code;
	}
	
function time_code_to_seconds($time_code) {
	$table = explode(":",$time_code);
	$count = count($table);
	if($count < 2) return $time_code;
	if($count == 2) return (60 * $table[0] + $table[1]);
	return (3600 * $table[0] + 60 * $table[1] + $table[2]);
	}

function seconds_to_time_code($seconds) {
	$time = gmdate("H:i:s",$seconds);
	if(is_integer($pos=strpos($time,"00:")) AND $pos == 0)
		$time = substr($time,3,strlen($time)-3);
	return $time;
	}

function time_start_index($DAT_index) {
	global $bdd;
	$query = "SELECT time_code_start FROM ".BASE.".recordings WHERE recording_DAT_index = \"".$DAT_index."\"";
	$result = $bdd->query($query);
	$ligne = $result->fetch();
	$time_code_start = $ligne['time_code_start'];
	$result->closeCursor();
	return $time_code_start;
	}
	
function time_end_index($DAT_index) {
	global $bdd;
	$query = "SELECT time_code_end FROM ".BASE.".recordings WHERE recording_DAT_index = \"".$DAT_index."\"";
	$result = $bdd->query($query);
	$ligne = $result->fetch();
	$time_code_end = $ligne['time_code_end'];
	$result->closeCursor();
	return $time_code_end;
	}

function guess_DAT_index($forgood,$song_id) {
	global $bdd;
	$query = "SELECT time_code_start, recording_DAT_index FROM ".BASE.".songs WHERE song_id = \"".$song_id."\"";
	$result = $bdd->query($query);
	$ligne = $result->fetch();
	$result->closeCursor();
	$recording_DAT_index = trim($ligne['recording_DAT_index']);
	$time_code = trim($ligne['time_code_start']);
	if($time_code == '' OR $recording_DAT_index == '') return '';
	$seconds = time_code_to_seconds($time_code);
	// First reshape the format of recording_DAT_index
	$result_check_DAT_index = check_DAT_index($recording_DAT_index,$song_id);
	$recording_DAT_index = $result_check_DAT_index['dat_index'];
	if($forgood) {
		$query_update = "UPDATE ".BASE.".songs SET recording_DAT_index = \"".$recording_DAT_index."\" WHERE song_id = \"".$song_id."\"";
		$result_update = $bdd->query($query_update);
		if(!$result_update) {
			echo "<br /><span style=\"color:red;\">ERROR modifying table:</span> ".$query_update."<br />";
			die();
			}
		$result_update->closeCursor();
		}
	// Now try to find the correct segment
	$table = explode('-',$recording_DAT_index);
	$tape = intval($table[1]);
	if($tape < 10) $tape = "0".$tape;
	$tape_code = $table[0]."-".$tape;
	$query = "SELECT time_code_start, time_code_end, recording_DAT_index FROM ".BASE.".recordings WHERE recording_DAT_index LIKE \"".$tape_code."%\" ORDER BY time_code_start";
	$result = $bdd->query($query);
	$recording_DAT_index = '';
	while($ligne = $result->fetch()) {
		$DAT_index = $ligne['recording_DAT_index'];
		$time_code_start = $ligne['time_code_start'];
		$time_code_end = $ligne['time_code_end'];
		$seconds_start = time_code_to_seconds($time_code_start);
		$seconds_end = time_code_to_seconds($time_code_end);
		if($seconds >= $seconds_start AND $seconds < $seconds_end) {
			$recording_DAT_index = $DAT_index;
//	No break: if a song belongs to several overlapping segments, the last one should be taken
			}
		}
	$result->closeCursor();
	if($forgood) {
		if($recording_DAT_index <> '') {
			$query_update = "UPDATE ".BASE.".songs SET recording_DAT_index = \"".$recording_DAT_index."\" WHERE song_id = \"".$song_id."\"";
			$result_update = $bdd->query($query_update);
			if(!$result_update) {
				echo "<br /><span style=\"color:red;\">ERROR modifying table:</span> ".$query_update."<br />";
				die();
				}
			$result_update->closeCursor();
			$login = $_SESSION['login'];
			$date = date('Y-m-d H:i:s');
			$query_there = "SELECT song_id FROM ".BASE.".song_metadata WHERE song_id = \"".$song_id."\" AND devanagari = \"\" AND roman_devanagari = \"\"";
			$result_there = $bdd->query($query_there);
			$n_there = $result_there->rowCount();
			if($n_there > 0) $already_there_metadata = TRUE;
			else $already_there_metadata = FALSE;
			$result_there->closeCursor();
			if($already_there_metadata) {
				$query_update = "UPDATE ".BASE.".song_metadata SET recording_DAT_index = \"".$recording_DAT_index."\" WHERE song_id = \"".$song_id."\" AND devanagari = \"\" AND roman_devanagari = \"\"";
				$result_update = $bdd->query($query_update);
				if(!$result_update) {
					echo "<br /><span style=\"color:red;\">ERROR modifying table:</span> ".$query_update."<br />";
					die();
					}
				$result_update->closeCursor();
				}
			else {
				$query_update = "INSERT INTO ".BASE.".song_metadata (song_id, recording_DAT_index, login, date, devanagari, roman_devanagari, remarks_marathi, remarks_english) VALUES (\"".$song_id."\",\"".$recording_DAT_index."\",\"".$login."\",\"".$date."\",\"\",\"\",\"\",\"\")";
				$result_update = $bdd->query($query_update);
				if(!$result_update) {
					echo "<br /><span style=\"color:red;\">ERROR modifying table:</span> ".$query_update."<br />";
					die();
					}
				$result_update->closeCursor();
				}
			}
		}
	return $recording_DAT_index;
	}

function ReadRewriteRules($language) {
	// Read from database
	global $bdd;
	$rule = array();
	$i = 0;
	$query = "SELECT * FROM ".BASE.".rewrite_rules WHERE language = \"".$language."\" ORDER BY id";
	$result = $bdd->query($query);
	if($result) {
		while($ligne = $result->fetch()) {
			$rule[$i][0] = $ligne['left_arg'];
			$rule[$i][1] = $ligne['right_arg'];
			$rule[$i][2] = $ligne['context'];
			$rule[$i][3] = $ligne['position'];
			$i++;
			}
		}
	if(count($rule) == 0) echo "<font color=red>ERROR: no rule found in</font> ".$language."<br />";
	return $rule;
	}

function apply_rules($delete_tag,$fix_uppercase,$text,$rule) {
	global $song_id;
/*	if($fix_uppercase) */ $text = first_lowercase($text);
	if($delete_tag) $text = str_replace('*','',$text); // Added 27-09-2017
	$text = "§§§ ".$text." ";
	$text = str_replace("<br />"," <br />§§ ",$text);
	$text = str_replace("\n"," \n§§ ",$text);
	$text = str_replace(","," ,§",$text);
	$text = str_replace(")"," )§",$text);
	$text = str_replace("(","§( ",$text);
	$text = str_replace("/"," / ",$text);
	// $text = str_replace("“","§“ ",$text);
	$text = str_replace("!"," §!",$text);
	$text = str_replace("?"," §?",$text);
	$text = str_replace("” ","§”§ ",$text);
	$text = str_replace(" “"," §“§",$text);
//	$text = str_replace("“"," “§ ",$text); // Added 27/04/2018
	$text = str_replace("’ "," §’ ",$text);
//	echo $text." 1<br />";
	for($k = 0; $k < count($rule); $k++) {
	//	$search = str_replace("||","’",$rule[$k][0]);
		$search = $rule[$k][0];
		$replace = $rule[$k][1];
	//	$replace = str_replace("||","’",$replace);
		$context = $rule[$k][2];
		if($context == 'D' OR $search == '' OR $search == $replace) continue;
		$position = $rule[$k][3];
	/*	$text2 = str_replace(' ','+',$text);
		echo $k." - ".$text2."<br />";
		echo "‘".$search."’ --> ‘".$replace."’<br />"; */
		$count1 = $count2 = $count3 = $count4 = $count5 = 0;
		$text = str_ireplace("§§ ".$search,"§§ ".$replace,$text);
		if(is_integer($pos=strpos($text,$search))) {
		//	echo "<br />".$k." doing: (".$search.") => (".$replace.") [".$position."]<br />";
			if($position == 'S') { // Prefix only
				$text = str_replace(" ".$search," ".$replace,$text,$count1);
				$text = str_replace(">".$search,">".$replace,$text,$count2);
				$text = str_replace("§".$search,"§".$replace,$text,$count3);
				if($pos == 0)
					$text = substr_replace($text,$replace,0,strlen($search));
				}
			else {
			//	echo "<br />••".$text."<br />";
			//	echo "search = “".$search."” replace = “".$replace."”<br />";
				$text = str_replace($search,$replace,$text);
				// This worked when search contained several words separated with spaces
				// Now we deal wit the rare case of a repeated tagged word
			/*	$table = explode(' ',$text);
				$table = str_replace(trim($search),trim($replace),$table);
				$text = implode(' ',$table); */
			//	 echo "•••".$text."<br />";
				}
			}
		}
	/* do $text = str_replace("  "," ",$text,$count);
	while($count > 0); */
	$text = preg_replace('/\s+/',' ',$text);
	$text = str_replace("§",'',$text);
	$text = trim($text);
	$text = str_replace(" ,",",",$text);
	$text = str_replace(" )",")",$text);
	$text = str_replace(" !","!",$text);
	$text = str_replace(" ?","?",$text);
	$text = str_replace("( ","(",$text);
	$text = str_replace(" / ","/",$text);
	$text = str_replace("<br/>","<br />",$text);
/*	$text = str_replace("“ ","“",$text);
	$text = str_replace(" ”","”",$text); */
	$text = str_replace(" ’ ","’ ",$text);
	$text = str_replace(" <br />","<br />",$text);
	$text = str_replace("<br /> ","<br />",$text);
	$text = fix_lines($text);
	$text = str_replace(" *","*",$text);
	$text = str_replace(",*","*,",$text);
	$text = str_replace(")*","*)",$text);
	$text = str_replace("**","*",$text);
	$text = str_replace("*s*","*s",$text);
	$text = str_replace("* ’","*’",$text);
	$text = str_replace("* “ ","*“ ",$text);
	$text = str_replace("*’s*","*’s",$text);
	$text = str_replace("* s ","*s ",$text);
	if($fix_uppercase) $text = fix_uppercase($text,"<br />");
	return $text;
	}

function fix_translation($song_id,$translation_correction_english) {
	global $bdd;
	// Revised 19/09/2018
	$test = FALSE;
	if(!isset($_SESSION['login']) OR $_SESSION['login'] == '') return;
	$query = "SELECT translation_english FROM ".BASE.".songs WHERE song_id = \"".$song_id."\" LIMIT 1";
	$result = $bdd->query($query);
	$n = $result->rowCount();
	if($n > 0) {
		$ligne = $result->fetch();
		$old_translation = $ligne['translation_english'];
		$translation = $old_translation;
		if($test) echo "Before in SONGS = ".$translation."<br /><br/>";
		$translation = apply_rules(TRUE,TRUE,$translation,$translation_correction_english);
		$translation = str_replace('_',' ',$translation);
		$translation = str_replace("||","’",$translation);
		$translation = fix_typo($translation,0);
		if($test) echo "After in SONGS = ".$translation."<br /><br/>";
		if($translation <> $old_translation) {
		//	echo $old_translation."<br />§fixed => ".$translation."<br /><br />";
			$query_update = "UPDATE ".BASE.".songs SET translation_english = \"".$translation."\" WHERE song_id = \"".$song_id."\"";
		//	echo $query_update."<br />";
			$result_update = $bdd->query($query_update);
			if(!$result_update) {
				echo "<br /><font color=red>".$query_update."<br />";
				echo "ERROR: FAILED</font>";
				die();
				}
			$result_update->closeCursor();
			}
		}
	$result->closeCursor();
	$query2 = "SELECT version, text FROM ".BASE.".translations WHERE song_id = \"".$song_id."\" ORDER BY version DESC";
	$result2 = $bdd->query($query2);
	$n2 = $result2->rowCount();
	if($n2 > 0) {
		$ligne2 = $result2->fetch();
		$version = $ligne2['version'];
		$old_translation = $ligne2['text'];
		$translation = $old_translation;
		if($test) echo "Before in translations = ".$translation."<br /><br/>";
		$translation = apply_rules(TRUE,TRUE,$translation,$translation_correction_english);
		$translation = str_replace('_',' ',$translation);
		$translation = str_replace("||","’",$translation);
		$translation = fix_typo($translation,0);
		if($test) echo "After in translations = ".$translation."<br /><br/>";
		if($translation <> $old_translation) {
			$query_update = "UPDATE ".BASE.".translations SET text = \"".$translation."\" WHERE song_id = \"".$song_id."\" AND version = \"".$version."\"";
			$result_update = $bdd->query($query_update);
			if(!$result_update) {
				echo "<br /><font color=red>".$query_update."<br />";
				echo "ERROR: FAILED</font>";
				die();
				}
			$result_update->closeCursor();
			}
		}
	$result2->closeCursor();
	$query3 = "SELECT translation FROM ".BASE.".workset WHERE song_id = \"".$song_id."\" AND translation <> \"\"";
	$result3 = $bdd->query($query3);
	$n3 = $result3->rowCount();
	if($n3 > 0) {
		$ligne3 = $result3->fetch();
		$old_translation = $ligne3['translation'];
		$translation = $old_translation;
		if($test) echo "Before in workset = ".$translation."<br /><br/>";
		$translation = apply_rules(TRUE,TRUE,$translation,$translation_correction_english);
		$translation = str_replace('_',' ',$translation);
		$translation = str_replace("||","’",$translation);
		$translation = fix_typo($translation,0);
		if($test) echo "After in workset = ".$translation."<br /><br/>";
		if($translation <> $old_translation) {
			$query_update = "UPDATE ".BASE.".workset SET translation = \"".$translation."\" WHERE song_id = \"".$song_id."\" AND translation <> \"\"";
			$result_update = $bdd->query($query_update);
			if(!$result_update) {
				echo "<br /><font color=red>".$query_update."<br />";
				echo "ERROR: FAILED</font>";
				die();
				}
			$result_update->closeCursor();
			}
		}
	$result3->closeCursor();
	return;
	}

function map_link($gps,$show_code) {
	if(trim($gps) == '') return '';
	$table = explode(';',$gps);
	if(count($table) <> 2) {
		if($show_code) return $gps."?";
		else return "[map?]";
		}
	$east = trim(str_replace("east=",'',$table[0]));
	$north = trim(str_replace("north=",'',$table[1]));
	if($east == '' OR $north == '') {
		if($show_code) return $gps."?";
		else return "[map?]";
		}
	$link1 = "https://www.openstreetmap.org/?lat=".$north."&lon=".$east."&zoom=15&layers=M";
	$link2 = "https://maps.google.co.uk/maps?q=".$north.",".$east."&output=kml";
	if($show_code) $result = "<a target=\"_blank\" href=\"".$link2."\">".$gps."</a>";
	else {
		$result1 = "<a style=\"background-color:GreenYellow; padding:2px;\" target=\"_blank\" href=\"".$link1."\">OpenStreetMap</a>";
		$result2 = "<a style=\"background-color:PowderBlue; padding:2px;\" target=\"_blank\" href=\"".$link2."\">GoogleMap</a>";
		$result = " ".$result1." ".$result2;
		}
	return $result;
	}

function check_todays_backup() {
	global $bdd;
	$backup_path = "DB_DUMP";
	$date = date("Y-m-d");
	$olddir = getcwd();
	chdir($backup_path);
	if(!file_exists($date)) {
		echo "<font color=red>Creating ‘".$backup_path."/".$date."’ folder and saving all databases…</font><br />";
		$cmd = "mkdir ".$date;
		exec($cmd);
		chdir($date);
		$tables = array();
		backup_tables(FALSE,$tables,TRUE,"sql");
		$tables = array();
		backup_tables(FALSE,$tables,FALSE,"sql");
		$tables = array();
		backup_tables(FALSE,$tables,FALSE,"csv");
		chdir($olddir);
		backup_settings(FALSE);
		ClearBackups(TRUE);
		}
	chdir($olddir);
	return;
	}
	
function backup_settings($verbose) {
	if($verbose) echo "<font color=green>Dumping SETTINGS to DB_DUMP</font><br />";
	$date = date("Y-m-d");
	$olddir =  getcwd();
//	$backup_path = "DB_DUMP";
/*	chdir($backup_path);
	if(!file_exists($date)) {
		echo "<font color=red>Creating ‘".$backup_path."/".$date."’ folder and saving all databases…</font><br />";
		$cmd = "mkdir ".$date;
		exec($cmd);
		}
	chdir($olddir); */
	$backup_path = "DB_DUMP/".$date;
	chdir($backup_path);
	if($verbose) echo "<small>backup_path = ".$backup_path."</small><br /><br />";
	$cmd = "cp -R ../../SETTINGS .";
	exec($cmd);
	chdir($olddir);
	return;
	}

// https://stackoverflow.com/questions/18279066/pdo-mysql-backups-function
function backup_tables($verbose,$tables,$compression,$format) {
	global $bdd;
//	$verbose = TRUE;
	$bdd->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_NATURAL);
	$date = date("Y-m-d");
	$olddir =  getcwd();
	$backup_path = "DB_DUMP/".$date;
	if(!is_dir($backup_path)) mkdir($backup_path);
	chdir($backup_path); 
//	echo "olddir = ".$olddir."  backup_path = ".$backup_path."<br />";
	$stats = FALSE;
	if($format == "csv") $stats = fopen("stats.txt","w");
	// array of all database field types which just take numbers 
	$numtypes = array('tinyint','smallint','mediumint','int','bigint','float','double','decimal','real');
	// get all of the tables
	if(empty($tables)) {
		$pstm1 = $bdd->query('SHOW TABLES');
		while($row = $pstm1->fetch(PDO::FETCH_NUM)) {
			$tables[] = $row[0];
			}
		}
	else {
		$tables = is_array($tables) ? $tables : explode(',',$tables);
		}
	
	// cycle through the table(s)
	if($verbose) echo "<font color=green>Dumping tables to ".$backup_path.":</font>";
	if($verbose) echo "<ul>";
	foreach($tables as $table) {
		//create/open files
		$unlink = TRUE;
		if($format == "csv") {
			$filename = $table.".csv";
			if(file_exists($filename)) $unlink = unlink($filename);
			$handle = fopen($filename,"w");
			}
		else {
			if($compression) {
				$filename = $table.".sql.gz";
				if(file_exists($filename)) $unlink = unlink($filename);
				$zp = gzopen($filename,"wb9");
				}
			else {
				$filename = $table.".sql";
				if(file_exists($filename)) $unlink = unlink($filename);
				$handle = fopen($filename,"w");
				}
			}
		if($verbose) echo "<li><small><font color=blue>".$filename."</font></small>";
		if(!$unlink) {
			echo "<small></li><font color=red>ERROR: unable to delete the preceding version of ‘".$filename."’!</font><br /></small>";
			continue;
			}
		echo "<br /><small>SELECT * FROM `".$table."`</small><br />";
		// if($table == "groups") continue; // 2024-09-26 Because SELECT * FROM groups crashes!
		$result = $bdd->query("SELECT * FROM `".$table."`");
		$num_rows = $result->rowCount();
		echo "<small>num_rows = ".$num_rows."</small><br />";
		$num_fields = $result->columnCount();
		$return = "";
		$return .= "DROP TABLE IF EXISTS `".$table."`"; 

		//table structure
		$pstm2 = $bdd->query("SHOW CREATE TABLE `".$table."`");
		$row2 = $pstm2->fetch(PDO::FETCH_NUM);
		$ifnotexists = str_replace('CREATE TABLE', 'CREATE TABLE IF NOT EXISTS', $row2[1]);
		$return .= "\n\n".$ifnotexists.";\n\n";

		if($format <> "csv") {
			if($compression) gzwrite($zp, $return);
			else fwrite($handle,$return);
			}
		$return = "";
		
		//insert values
		if($num_rows) {
			$return = "INSERT INTO `".$table."` (";
			$pstm3 = $bdd->query("SHOW COLUMNS FROM `".$table."`");
			$count = 0;
			$type = array();
			
			while($rows = $pstm3->fetch(PDO::FETCH_NUM)) {
				if(stripos($rows[1], '(')) {
					$type[$table][] = stristr($rows[1], '(', true);
					}
				else $type[$table][] = $rows[1];
			
				$return .= "`".$rows[0]."`";
				$count++;
				if($count < ($pstm3->rowCount())) {
					$return .= ", ";
					}
				}
			$return .= ")".' VALUES';
			if($format <> "csv") {
				if($compression) gzwrite($zp, $return);
				else fwrite($handle,$return);
				}
			$return = "";
			}
		$count = 0;
		while($row = $result->fetch(PDO::FETCH_NUM)) {
			if($format <> "csv") $return = "\n\t(";
			for($j=0; $j < $num_fields; $j++) {
				//$row[$j] = preg_replace("\n","\\n",$row[$j]);
				if(isset($row[$j])) {
					if($format == "csv") $return .= '"'.str_replace('"','\\"',$row[$j]).'"'; // str_replace added 28 March 2018
					else {
						//if number, take away "". else leave as string
						if((in_array($type[$table][$j],$numtypes)) && (!empty($row[$j])))
							$return .= $row[$j];
						else
							$return .= $bdd->quote($row[$j]);
						}
					}
				else {
					if($format == "csv") $return .= '';
					else $return .= 'NULL';
					}
				if($j < ($num_fields-1)) $return .= ',';
				}
			$count++;
			if($format == "csv") $return .= "\n";
			else {
				if($count < ($result->rowCount()))
					$return .= "),";
				else $return .= ");";
				}
			if($format == "csv") fwrite($handle,$return);
			else {
				if($compression) gzwrite($zp, $return);
				else fwrite($handle,$return);
				}
			$return = "";
			}
		$return = "\n\n-- ------------------------------------------------ \n\n";
		if($verbose) echo "<small> (".$count." records)";
		
		if($format <> "csv") {
			if($compression) gzwrite($zp, $return);
			else fwrite($handle,$return);
			}
		$return = "";
	
		if($format == "csv") {
			fclose($handle);
			fwrite($stats,$filename." ".$count."\n");
			}
		else {
			if($compression) gzclose($zp);
			else fclose($handle);
			}
		$filesize = filesize($filename);
		if($verbose) echo " - ".$filesize." bytes - ".date("F d Y H:i:s",filemtime($filename))."</small></li>";
		}
	if($verbose) echo "</small></ul>";
	if($stats) fclose($stats);
	chdir($olddir);
	return;
	}

function ClearBackups($trace) {
	$test = FALSE;
	$dir = "DB_DUMP";
	$n = 0;
	$year = date("Y");
	if($handle = opendir($dir)) {
		if($trace) echo "<small><span style=\"color:red;\">Removing old backups in ".$dir." (only the recent ".MAX_BACKUPS." ones will be preserved)</span></small><br />";
  		while(FALSE !== ($file = readdir($handle))) {
			if(!is_dir($dir.'/'.$file)) continue;
			if(substr($file,0,1) == "." ) continue;
			if(strstr($file,"Icon") != FALSE) continue;
			
			if(!is_integer(strpos($file,($year - 1))) AND !is_integer(strpos($file,$year))) continue;
			
			$time = filemtime($dir.'/'.$file);
			$time_list[$file] = $file;
			$n++;
  			}
 		closedir($handle);
		}
	else {
		echo "<span style=\"color:red;\">ERROR: cannot open ‘".$dir."’</span><br />";
		return;
		}
	if($trace) echo "<small>Currently ".$n." backups…</small><br />";
	$i = 0;
	arsort($time_list);
	foreach($time_list as $file => $time) {
		if($i++ < MAX_BACKUPS) {
			if($trace) echo "<small>".$file." - preserved</small><br />";
			continue;
			}
		else { 
		/*	$command = "rm -r ".$dir."/".$file;
			if($trace) echo "<small>".$command."</small><br />";
			exec($command); */
			my_rmdir($dir."/".$file);
			if($trace) echo "<small>Deleted ".$dir."/".$file."</small><br />";
			}
		}
	return;
	}

function my_rmdir($src) {
    $dir = opendir($src);
    while(FALSE !== ($file = readdir($dir))) {
        if(($file <> '.' ) && ($file <> '..')) {
            $full = $src.'/'.$file;
            if(is_dir($full)) my_rmdir($full);
            else unlink($full);
            }
        }
    closedir($dir);
    rmdir($src);
    return;
	}

function owner_of_song($song_id) {
	global $bdd;
	$query = "SELECT login FROM ".BASE.".songs WHERE song_id=\"".$song_id."\"";
	$result = $bdd->query($query);
	$n = $result->rowCount();
	if($n > 0) {
		$ligne = $result->fetch();
		$result->closeCursor();
		return $ligne['login'];
		}
	return '';
	}

function delete_song($delete_song_id,$verbose) {
	global $bdd;
	$query_delete = "DELETE FROM ".BASE.".songs WHERE song_id = \"".$delete_song_id."\"";
	if($verbose) echo $query_delete."<br />";
	$result_delete = $bdd->query($query_delete);
	if(!$result_delete) {
		echo "<br /><span style=\"color:red;\">ERROR modifying table:</span> ".$query_delete."<br />";
		return FALSE;
		}
	$result_delete->closeCursor();
	$query_delete = "DELETE FROM ".BASE.".song_metadata WHERE song_id = \"".$delete_song_id."\"";
	if($verbose) echo $query_delete."<br />";
	$result_delete = $bdd->query($query_delete);
	if(!$result_delete) {
		echo "<br /><span style=\"color:red;\">ERROR modifying table:</span> ".$query_delete."<br />";
		return FALSE;
		}
	else $result_delete->closeCursor();
	$query_delete = "DELETE FROM ".BASE.".workset WHERE song_id = \"".$delete_song_id."\"";
	if($verbose) echo $query_delete."<br />";
	$result_delete = $bdd->query($query_delete);
	if(!$result_delete) {
		echo "<br /><span style=\"color:red;\">ERROR modifying table:</span> ".$query_delete."<br />";
		return FALSE;
		}
	else $result_delete->closeCursor();
	$query_delete = "DELETE FROM ".BASE.".translations WHERE song_id = \"".$delete_song_id."\"";
	if($verbose) echo $query_delete."<br />";
	$result_delete = $bdd->query($query_delete);
	if(!$result_delete) {
		echo "<br /><span style=\"color:red;\">ERROR modifying table:</span> ".$query_delete."<br />";
		return FALSE;
		}
	else $result_delete->closeCursor();
	$query_delete = "DELETE FROM ".BASE.".group_index WHERE song_id = \"".$delete_song_id."\"";
	if($verbose) echo $query_delete."<br />";
	$result_delete = $bdd->query($query_delete);
	if(!$result_delete) {
		echo "<br /><span style=\"color:red;\">ERROR modifying table:</span> ".$query_delete."<br />";
		return FALSE;
		}
	else $result_delete->closeCursor();
	return TRUE;
	}

function get_browser_name() {
	if(!isset($_SERVER['HTTP_USER_AGENT'])) return '';
	$user_agent = $_SERVER['HTTP_USER_AGENT'];
    if (strpos($user_agent, 'Opera') || strpos($user_agent, 'OPR/')) return 'Opera';
    elseif (strpos($user_agent, 'Edge')) return 'Edge';
    elseif (strpos($user_agent, 'Chrome')) return 'Chrome';
    elseif (strpos($user_agent, 'Safari')) return 'Safari';
    elseif (strpos($user_agent, 'Firefox')) return 'Firefox';
    elseif (strpos($user_agent, 'MSIE') || strpos($user_agent, 'Trident/7')) return 'Explorer';
	return 'Other';
	}

function howmany($table) {
	global $bdd;
	$query = "SELECT count(*) from ".BASE.".".$table;
	$result = $bdd->query($query);
	$count = $result->fetchColumn();
	$result->closeCursor();
	return $count;
	}

function all_words($return,$text) {
	$words = array();
	$text = trim($text);
	$text = str_replace("[???]",'',$text);
	if($text == '') return $words;
	if($return == '') {
		$text = str_ireplace("<br />",' ',$text);
		$text = str_ireplace("<br/>",' ',$text);
		$text = str_ireplace("<br>",' ',$text);
		$text = str_replace("\n",' ',$text);
		}
	else $text = str_replace($return," [BR] ",$text);
	$text = str_replace('('," ( ",$text);
	$text = str_replace(')'," ) ",$text);
	$text = str_replace('?'," ? ",$text);
	$text = str_replace('!'," ! ",$text);
	$text = str_replace('.'," . ",$text);
	$text = str_replace(','," , ",$text);
	$text = str_replace('।'," । ",$text);
	$text = str_replace('/'," / ",$text);
	$text = str_replace('…'," … ",$text);
	$text = str_replace(';'," ; ",$text);
//	$text = str_replace(':'," : ",$text);
	do $text = str_replace("  ",' ',$text,$count);
	while($count > 0);
	$words = explode(' ',$text);
//	for($i = 0; $i < count($words); $i++) echo $words[$i]."<br />";
	if($return <> '') $words = str_replace("[BR]",$return,$words);
	return $words;
	}

function LearnTransliteration($song_id,$devanagari_words,$roman_words,$done_by_google) {
	global $bdd, $login;
	$done = FALSE;
	if(count($devanagari_words) <> count($roman_words)) return $done;
	for($i = 0; $i < count($devanagari_words); $i++) {
		$devanagari = trim($devanagari_words[$i]);
		$roman = trim($roman_words[$i]);
		if($roman == "[???]" OR $roman == '' OR $roman == "<br />" OR $devanagari == '') continue;
		$query = "SELECT roman, song_id FROM ".BASE.".dev_roman WHERE devanagari = \"".$devanagari."\" LIMIT 1";
	//	echo $query."<br />";
		$result = $bdd->query($query);
		$n = $result->rowCount();
		if($n == 0) {
			$query_update = "INSERT INTO ".BASE.".dev_roman (devanagari, roman, song_id) VALUES (\"".$devanagari."\",\"".$roman."\",\"".$song_id."\")";
			$result_update = $bdd->query($query_update);
			$result_update->closeCursor();
			$done = TRUE;
			$query_rule = "SELECT id FROM ".BASE.".dev_roman WHERE roman = \"".$roman."\" AND devanagari = \"".$devanagari."\" AND song_id = \"".$song_id."\"";
			$result_rule = $bdd->query($query_rule);
			$n_rule = $result_rule->rowCount();
		//	if($login == "Bernard") echo "n_rule = ".$n_rule."<br />";
			$ligne_rule = $result_rule->fetch();
			$rule_id = $ligne_rule['id'];
			$result_rule->closeCursor();
			echo "<br /><small><span style=\"color:red;\">➡ Created rule:</span> ".$devanagari." -> ".$roman."</small>";
			add_to_rule_history($devanagari,$roman,'','',$song_id,$rule_id);
			$query_duplicate = "SELECT devanagari, song_id FROM ".BASE.".dev_roman WHERE roman = \"".$roman."\" AND devanagari <> \"".$devanagari."\"";
			$result_duplicate = $bdd->query($query_duplicate);
			$n_duplicate = $result_duplicate->rowCount();
			if($n_duplicate > 0) {
				echo "<br /><br /><span style=\"color:red;\">WARNING:";
				while($ligne_duplicate = $result_duplicate->fetch()) {
					$conflicting_song_id = $ligne_duplicate['song_id'];
					$devanagari_duplicate = $ligne_duplicate['devanagari'];
					$song_url = "edit-songs.php?start=".$conflicting_song_id."&end=".$conflicting_song_id;
					$song_link = "<a href=\"".$song_url."\" target=\"_blank\">".$conflicting_song_id."</a>";
					echo "<br /><small>➡➡➡</span> Conflicting rule: ".$devanagari_duplicate." -> ".$roman." used in song #".$song_link."</small>";
					}
				echo "<br />";
				}
			if($done_by_google) ApplyThisTransliterationRuleToAllSongs($devanagari,$roman);
			$result_duplicate->closeCursor();
			}
		else {
			$ligne = $result->fetch();
			$oldroman = $ligne['roman'];
			if($roman <> $oldroman) {
				$query_update = "UPDATE ".BASE.".dev_roman SET roman = \"".$roman."\", song_id = \"".$song_id."\" WHERE devanagari = \"".$devanagari."\"";
		//		echo $query_update."<br />";
				$result_update = $bdd->query($query_update);
				if(!$result_update) {
					echo "<br /><span style=\"color:red;\">ERROR modifying table:</span> ".$query_update."<br />";
					die();
					}
				$result_update->closeCursor();
				$done = TRUE;
				echo "<small>";
				echo "<br /><span style=\"color:red;\">➡➡➡ CHANGED RULE:</span> ".$devanagari." -> ".$roman." <span style=\"color:red;\">(instead of</span> ".$oldroman;
				echo "<span style=\"color:red;\">)</span></small>";
				$query_rule = "SELECT id FROM ".BASE.".dev_roman WHERE roman = \"".$roman."\" AND devanagari = \"".$devanagari."\" AND song_id = \"".$song_id."\"";
				$result_rule = $bdd->query($query_rule);
				$n_rule = $result_rule->rowCount();
			//	if($login == "Bernard") echo "n_rule = ".$n_rule."<br />";
				$ligne_rule = $result_rule->fetch();
				$rule_id = $ligne_rule['id'];
				$result_rule->closeCursor();
				add_to_rule_history($devanagari,$roman,$devanagari,$oldroman,$song_id,$rule_id);
				}
			if($done_by_google) ApplyThisTransliterationRuleToAllSongs($devanagari,$roman);
			}
		$result->closeCursor();
		}
	return $done;
	}

function ApplyThisTransliterationRuleToAllSongs($devanagari_word,$roman_word) {
	global $bdd, $login;
	if(is_admin($login)) $verbose = TRUE;
	else $verbose = FALSE;
	$test = FALSE;
	if(strlen($devanagari_word) < 1) return;
	if($devanagari_word == '(' OR $devanagari_word == ')' OR $devanagari_word == '.' OR $devanagari_word == ',' OR $devanagari_word == ';' OR $devanagari_word == ':' OR $devanagari_word == '-'  OR $devanagari_word == '+'  OR $devanagari_word == '!') return;
//	$query_devanagari = QueryWordInTranscription("devanagari",$devanagari_word,"song_id, devanagari, roman_devanagari",'');
	$where = QueryOneWord("devanagari",$devanagari_word);
	$query_devanagari = "SELECT song_id, devanagari, roman_devanagari FROM ".BASE.".songs WHERE ".$where;
	echo "<br /><small><span style=\"color:red;\">Applying rule:</span> ".$devanagari_word." -> ".$roman_word." <span style=\"color:red;\">to all songs</span></small>";
	if($test) echo "<br />".$query_devanagari."<br />";
	$result_devanagari = $bdd->query($query_devanagari);
	$n_devanagari = $result_devanagari->rowCount();
	$firstchange = TRUE;
	while($ligne_song = $result_devanagari->fetch()) {
		$song_id = $ligne_song['song_id'];
		$devanagari = $ligne_song['devanagari'];
		$devanagari_words = all_words('<br />',$devanagari);
		$roman_devanagari = $ligne_song['roman_devanagari'];
		$roman_words = all_words('<br />',$roman_devanagari);
		$change = FALSE;
		for($index = 0; $index < count($devanagari_words); $index++) {
			if($devanagari_words[$index] == $devanagari_word) {
				if(isset($roman_words[$index]))
					$roman_bad = $roman_words[$index];
				else $roman_bad = '';
				if($roman_bad <> $roman_word) {
					if($test) echo "<br />song_id = ".$song_id." index = ".$index."<br />";
					ReplaceWordInTranscription($song_id,"roman_devanagari",$index,$roman_word);
					$change = TRUE;
					}
				}
			}
		$song_url = "edit-songs.php?start=".$song_id."&end=".$song_id;
		$song_link = "<a href=\"".$song_url."\" target=\"_blank\">".$song_id."</a>";
		if($verbose AND $change AND $firstchange) echo "<br />";
		$firstchange = FALSE;
		if($verbose AND $change) echo "<small> #".$song_link."</small>";
		if($test AND $change) die();
		}
	$result_devanagari->closeCursor();
	return;
	}

function ReplaceWordInTranscription($song_id,$field,$index,$good) {
	// This works for both devanagari and roman_devanagari
	global $bdd, $login;
	$test = FALSE;
	$query_rule = "SELECT ".$field." FROM ".BASE.".songs WHERE song_id = \"".$song_id."\"";
	if($test) echo "<br />".$query_rule."<br />";
	$result_rule = $bdd->query($query_rule);
	$n = $result_rule->rowCount();
	$change = FALSE;
	if($n == 0) return $change;
	$ligne = $result_rule->fetch();
	$text = $ligne[$field];
	if($test) echo "<br />".$text."<br />";
	$output = '';
	$return = "<br />";
	$all_words = all_words("<br />",$text);
	for($i = 0; $i < count($all_words); $i++) {
		$word = trim($all_words[$i]);
		if($i == $index) {
			$word = $good;
			$change = TRUE;
			}
		if($output <> '') $output .= " ";
		$output .= $word;
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
	if($test) echo "<br />".$output;
	
	if($change) {
		$query_update = "UPDATE ".BASE.".songs SET ".$field." = \"".$output."\" WHERE song_id = \"".$song_id."\"";
		if($test) echo "<br />".$query_update."<br />";
		$result_update = $bdd->query($query_update);
		if(!$result_update) {
			echo "<br /><font color=red>".$query_update."<br />";
			echo "ERROR: FAILED</font>";
			die();
			}
		$result_update->closeCursor();
		UpdateFieldInWorkset($field,$output,$song_id);
		}
	return $change;
	}

function UpdateFieldInWorkset($field,$output,$song_id) {
	global $bdd;
	if($field == "roman_devanagari") $field = "roman";
	$query = "UPDATE ".BASE.".workset SET ".$field." = \"".$output."\" WHERE song_id = \"".$song_id."\" AND ".$field." <> \"\"";
//	echo $query."<br />";
	$result = $bdd->query($query);
	$result->closeCursor();
	return;
	}

function LearnTransliterationFromSong($song_id) {
	global $bdd;
	$query_there = "SELECT devanagari, roman_devanagari FROM ".BASE.".songs WHERE song_id = \"".$song_id."\" AND devanagari <> \"\" AND roman_devanagari <> \"\"";
	$result_there = $bdd->query($query_there);
	$n_there = $result_there->rowCount();
	if($n_there > 0) {
		$ligne = $result_there->fetch();
		$devanagari = $ligne['devanagari'];
		$roman = $ligne['roman_devanagari'];
		$devanagari_words = all_words("<br />",$devanagari);
		$roman_words = all_words("<br />",$roman);
		LearnTransliteration($song_id,$devanagari_words,$roman_words,FALSE);
		}
	$result_there->closeCursor();
	return;
	}

function QueryWordInTranscription($field,$words,$what,$orderby) {
	$query = "SELECT ".$what." FROM ".BASE.".songs WHERE ".QueryWordInTranscriptionWhere($field,$words,$orderby);
	return $query;
	}

function FormatLogicalExpression($expression) {
	/* do $expression = str_replace("  "," ",$expression,$count);
	while($count > 0); */
	$expression = preg_replace('/\s+/',' ',$expression);
	$expression = str_ireplace(" OR ","+_",trim($expression));
	$expression = str_ireplace(" || ","+_",$expression);
	$expression = str_ireplace(" AND ","._",$expression);
	$expression = str_ireplace("_NOT ","!",$expression);
	$expression = str_ireplace(" !","!",$expression);
	$expression = str_replace('_','',$expression);
	$expression = str_replace("( ",'(',$expression);
	$expression = str_replace(" )",')',$expression);
	$expression = str_replace(" (",'(',$expression);
	$expression = str_replace(") ",')',$expression);
	$expression = str_ireplace(' ',"+",$expression);
	$expression = str_replace(",","+",$expression);
	$expression = str_replace(";","+",$expression);
	// Word boundaries
	$expression = str_replace('“','',$expression);
	$expression = str_replace('”','',$expression);
	$expression = str_replace("‘",'',$expression);
	$expression = str_replace("’",'',$expression);
	$expression = str_replace("'",'',$expression);
	$expression = str_replace('"','',$expression);
	// Now fix possible errors to avoid ill-formed SQL query
	do $expression = str_replace("++",'+',$expression,$count);
	while($count > 0);
	do $expression = str_replace("..",'.',$expression,$count);
	while($count > 0);
	do $expression = str_replace("!!",'!',$expression,$count);
	while($count > 0);
	$expression = str_replace("+.",'.',$expression);
	$expression = str_replace(".+",'.',$expression);
	$expression = str_replace("!+",'!',$expression);
	$expression = str_replace("!.",'!',$expression);
	$expression = str_replace("(.",'(',$expression);
	$expression = str_replace("(+",'(',$expression);
	$expression = str_replace("+)",')',$expression);
	$expression = str_replace(".)",')',$expression);
	$expression = str_replace(")!",')',$expression);
	$expression = str_replace(")(",'',$expression);
	return $expression;
	}
	
function QueryWordInTranscriptionWhere($field,$words,$orderby) {
	global $login;
//	if($login == "Bernard") echo $words."<br />";
	$expression = FormatLogicalExpression($words);
	$length = strlen($expression);
//	if($login == "Bernard") echo $expression." ".$length."<br />";
	$query_all = '(';
	$word = '';
	for($i = 0; $i < $length; $i++) {
		$c = $expression[$i];
		if($c == '(') {
			$level = 0;
			$sub = '';
			for($j = ($i + 1); $j < $length; $j++) {
				$c = $expression[$j];
		//		if($login == "Bernard") echo $c." ";
				if($c == '(') $level++;
				else if($c == ')') {
					$level--;
					if($level < 0) {
					//	if($login == "Bernard") echo "<br />sub = ".$sub."<br />";
						$query_all .= QueryWordInTranscriptionWhere($field,$sub,'');
						break;
						}
					}
				$sub .= $c;
				}
			$word = '';
			$i = $j;
			}
		else if($c == ".") {
			if($word <> '') $query_all .= QueryOneWord($field,$word);
			$query_all .= " AND ";
			$word = '';
			}
		else if($c == "+") {
			if($word <> '') $query_all .= QueryOneWord($field,$word);
			$query_all .= " OR ";
			$word = '';
			}
		else if($c == "!") {
			$query_all .= " NOT ";
			$word = '';
			}
		else $word .= $c;
		}
	if($word <> '') $query_all .= QueryOneWord($field,$word);
	$query_all .= ")";
	if($orderby <> '') $query_all .= " ORDER BY ".$orderby."";
	return $query_all;
	}

function QueryOneWord($field,$word) {
	$query_all = '';
	$length = mb_strlen($word);
	if($length < 4)
		$query_all .= "(".$field." LIKE \"% ".$word." %\" OR ".$field." LIKE \"%>".$word." %\" OR ".$field." LIKE \"% ".$word."<%\" OR ".$field." LIKE \"".$word." %\" OR ".$field." LIKE \"% ".$word."\")";
	else 
		$query_all .= "(MATCH ".$field." AGAINST (\"".$word."\" IN NATURAL LANGUAGE MODE))";
	return $query_all;
	}

function Mapping($devanagari,$word_ids,$i_word,$spell) {
	$word_show = '';
	$i_word_stress = -1;
	$start_line = TRUE;
	$devanagari_words = all_words("<br />",$devanagari);
	$nb_words = count($devanagari_words);
	for($i = 0, $j = 0; $i < $nb_words; $i++) {
		$devanagari_word = $devanagari_words[$i];
		if($devanagari_word == '') continue;
		if($devanagari_word == "<br />") {
			$word_show .= $devanagari_word;
			$start_line = TRUE;
			}
		else {
			$left_context = '';
			if($i > 0) $left_context = $devanagari_words[$i-1];
			if($left_context == "<br />") $left_context = '';
			$right_context = '';
			if($i < ($nb_words - 1)) $right_context = $devanagari_words[$i+1];
			if($right_context == "<br />") $right_context = '';
			$get_word = GuessEnglishWord($word_ids,$j,$devanagari_word,$left_context,$right_context);
			$english_word = $get_word['english'];
			if($english_word <> '') {
				if(!$spell) $english_word = str_replace(' ','_',$english_word);
				if($start_line) $english_word = ucfirst($english_word);
				if($j == $i_word)
					$word_show .= " <span style=\"color:red;\">".$english_word."</span> ";
				else $word_show .= " ".$english_word." ";
				}
			else {
				if($spell) $word_show .= " (§) ";
				else {
					if($j == $i_word)
						$word_show .= " (<span style=\"color:red;\">".$devanagari_word."</span>) ";
					else $word_show .= "(".$devanagari_word.")";
					}
				}
			$start_line = FALSE;
			$j++;
			}
		}
	return $word_show;
	}

function GuessEnglishWord($word_ids,$j,$devanagari_word,$left_context,$right_context) {
	global $bdd;
	$trace = FALSE;
	$n = 0; $roman_word = '';
	$word_choice = array();
	if($word_ids <> '') $word_choice = explode('-',$word_ids);
	$result = FALSE;
	if(isset($word_choice[$j]) AND $word_choice[$j] > 0) {
		$query = "SELECT id, english FROM ".BASE.".meaning WHERE english <> \"\" AND devanagari LIKE \"".$devanagari_word."\" AND id = \"".$word_choice[$j]."\"";
		$result = $bdd->query($query);
		if($result) $n = $result->rowCount();
		else $n = 0; // Rule no longer exists
		}
	if($n == 0 AND $left_context <> "" AND $right_context <> "") {
		$ntest = 0;
		if($trace) {
			if($result) $result->closeCursor(); // TEST FOR EXACT MATCHING
			$query = "SELECT id, english FROM ".BASE.".meaning WHERE english <> \"\" AND devanagari = \"".$devanagari_word."\" AND left_context = \"".$left_context."\" AND right_context = \"".$right_context."\"";
			$query .= " ORDER BY default_choice DESC";
			$result = $bdd->query($query);
			if($result) $n = $ntest = $result->rowCount();
			else $n = 0;
			}
		
		if($result) $result->closeCursor();
		$query = "SELECT id, english FROM ".BASE.".meaning WHERE english <> \"\" AND devanagari LIKE \"".$devanagari_word."\" AND left_context LIKE \"".$left_context."\" AND right_context LIKE \"".$right_context."\"";
		$query .= " ORDER BY default_choice DESC";
		$result = $bdd->query($query);
		if($result) $n = $result->rowCount();
		else $n = 0; // No full context
		
		if($trace AND $n > 0 AND $ntest == 0) {
			echo "<font color=red><br />APPROX MATCHING ONLY:</font><br />";
			echo "(".$left_context.") ".$devanagari_word." (".$right_context.")";
			die();
			}
			
		if($n == 0) {
			if($result) $result->closeCursor();
			if($roman_word == '') $roman_word = Transliterate(0,"<br />",$devanagari_word);
			$query = "SELECT id, english FROM ".BASE.".meaning WHERE english <> \"\" AND simple_form LIKE \"".$roman_word."\" AND left_context LIKE \"".$left_context."\" AND right_context LIKE \"".$right_context."\"";
			$query .= " ORDER BY default_choice DESC";
			$result = $bdd->query($query);
			if($result) $n = $result->rowCount();
			else $n = 0; // No full context even simple form
			}
		}
	if($n == 0 AND $left_context <> "") {
		if($result) $result->closeCursor();
		$query = "SELECT id, english FROM ".BASE.".meaning WHERE english <> \"\" AND devanagari LIKE \"".$devanagari_word."\" AND left_context LIKE \"".$left_context."\"";
		$query .= " ORDER BY default_choice DESC";
		$result = $bdd->query($query);
		if($result) $n = $result->rowCount();
		else $n = 0; // No left context
		if($n == 0) {
			if($result) $result->closeCursor();
			if($roman_word == '') $roman_word = Transliterate(0,"<br />",$devanagari_word);
			$query = "SELECT id, english FROM ".BASE.".meaning WHERE english <> \"\" AND simple_form LIKE \"".$roman_word."\" AND left_context LIKE \"".$left_context."\"";
			$query .= " ORDER BY default_choice DESC";
			$result = $bdd->query($query);
			if($result) $n = $result->rowCount();
			else $n = 0; // No left context even simple form
			}
		}
	if($n == 0 AND $right_context <> "") {
		if($result) $result->closeCursor();
		$query = "SELECT id, english FROM ".BASE.".meaning WHERE english <> \"\" AND devanagari LIKE \"".$devanagari_word."\" AND right_context LIKE \"".$right_context."\"";
		$query .= " ORDER BY default_choice DESC";
		$result = $bdd->query($query);
		if($result) $n = $result->rowCount();
		else $n = 0; // No right context
		if($n == 0) {
			if($result) $result->closeCursor();
			if($roman_word == '') $roman_word = Transliterate(0,"<br />",$devanagari_word);
			$query = "SELECT id, english FROM ".BASE.".meaning WHERE english <> \"\" AND simple_form LIKE \"".$roman_word."\" AND right_context LIKE \"".$right_context."\"";
			$query .= " ORDER BY default_choice DESC";
			$result = $bdd->query($query);
			if($result) $n = $result->rowCount();
			else $n = 0; // No right context even simple form
			}
		}
	if($n == 0) {
		if($result) $result->closeCursor();
		$query = "SELECT id, english FROM ".BASE.".meaning WHERE english <> \"\" AND devanagari LIKE \"".$devanagari_word."\"";
		$query .= " AND default_choice = \"1\"";
		$result = $bdd->query($query);
		if($result) $n = $result->rowCount();
		else $n = 0; // No devanagari value with default choice
		}
	if($n == 0) {
		if($result) $result->closeCursor();
		if($roman_word == '') $roman_word = Transliterate(0,"<br />",$devanagari_word);
		$query = "SELECT id, english FROM ".BASE.".meaning WHERE english <> \"\" AND simple_form LIKE \"".$roman_word."\"";
		$query .= " AND default_choice = \"1\"";
		$result = $bdd->query($query);
		if($result) $n = $result->rowCount();
		else $n = 0; // No simple_form value with default choice
		}
	if($n == 0) { // Take first entry in the table
		if($result) $result->closeCursor();
		$query = "SELECT id, english FROM ".BASE.".meaning WHERE english <> \"\" AND devanagari LIKE \"".$devanagari_word."\" ORDER BY id";
		$result = $bdd->query($query);
		if($result) $n = $result->rowCount();
		else $n = 0;
		}
	if($n == 0) { // Take first entry of simple form in the table
		if($result) $result->closeCursor();
		if($roman_word == '') $roman_word = Transliterate(0,"<br />",$devanagari_word);
		$query = "SELECT id, english FROM ".BASE.".meaning WHERE english <> \"\" AND simple_form LIKE \"".$roman_word."\" ORDER BY id";
		$result = $bdd->query($query);
		if($result) $n = $result->rowCount();
		else $n = 0;
		}
	if($n > 0) { // Found it!
		$ligne = $result->fetch();
		$get_word['english'] = $ligne['english'];
		$get_word['id'] = $ligne['id'];
		}
	else {
		// No entry, bad luck
		$get_word['english'] = '';
		$get_word['id'] = 0;
		}
	if($result) $result->closeCursor();
	return $get_word;
	}

function char_simple($c) {
	switch($c) {
	//	case 'a': $c = ''; break;
		case 'ā': $c = 'a'; break;
		case 'Ā': $c = 'A'; break;
		case 'ī': $c = 'i'; break;
		case 'ṇ': $c = 'n'; break;
		case 'ṅ': $c = 'n'; break;
		case 'ñ': $c = 'n'; break;
		case 'ḷ': $c = 'l'; break;
		case 'ṇ': $c = 'n'; break;
		case 'ṁ': $c = 'n'; break;
		case 'ṃ': $c = 'n'; break;
		case 'ō': $c = 'o'; break;
		case 'Ō': $c = 'O'; break;
		case 'ḍ': $c = 'd'; break;
		case 'ś': $c = 's'; break;
		case 'ṣ': $c = 's'; break;
		case 'Ś': $c = 'S'; break;
		case 'ṭ': $c = 't'; break;
		case 'ē': $c = 'e'; break;
		case 'ū': $c = 'u'; break;
		}
	return $c;
	}

function simple_form($text) {
	$result = '';
	for($i = 0; $i < strlen($text); $i++) {
		$c = mb_substr($text,$i,1,'UTF-8');
		$result .= char_simple($c);
		}
	return $result;
	}

function in_work_set($work_set,$user) {
	return "<span style=\"color:Orange;\">← in work set #".$work_set." by ‘".$user."’</span>";
	}

function group_list($recursive,$type,$with_link,$song_id,$no_repeat) {
	global $bdd;
	$group_list = '';
	$group_comment = array();
	$done = array();
	$group_comment_table_mr = $group_comment_table_en = array();
	$query_group_index = "SELECT * FROM ".BASE.".group_index WHERE song_id = \"".$song_id."\"";
	$result_group_index = $bdd->query($query_group_index);
	$n_group_index = $result_group_index->rowCount();
	while($ligne_index = $result_group_index->fetch()) {
		$group_id = $ligne_index['group_id'];
		$query_group = "SELECT * FROM ".BASE.".groups WHERE id = \"".$group_id."\"";
		$result_group = $bdd->query($query_group);
		$ligne_group = $result_group->fetch();
		$result_group->closeCursor();
		if($type == "list" OR $type == "label") {
			$group_link = group_link($type,$group_id,$with_link,$recursive,$done,$no_repeat);
			$done[$group_id] = TRUE;
		//	echo "group_link = ".$group_link."<br />";
			if($group_link <> '') {
				if($group_list <> '') $group_list .= ", ";
				$group_list .= $group_link;
				}
			}
		else if($type == "comment") {
			$all_group_comments = all_group_comments($group_id);
			for($i = 0; $i < count($all_group_comments); $i++) {
				$comment_mr = $all_group_comments[$i]['mr'];
				$comment_en = $all_group_comments[$i]['en'];
				if($comment_mr <> '' AND !in_array($comment_mr,$group_comment_table_mr))
					$group_comment_table_mr[] = $comment_mr;
				if($comment_en <> '' AND !in_array($comment_en,$group_comment_table_en))
					$group_comment_table_en[] = $comment_en;
				}
			}
		}
	$result_group_index->closeCursor();
	if($type == "label" OR $type == "list") return $group_list;
	else if($type == "comment") {
		$group_comment['mr'] = implode("<br />",$group_comment_table_mr);
		$group_comment['en'] = implode("<br />",$group_comment_table_en);
		return $group_comment;
		}
	}

function group_link($type,$group_id,$with_link,$recursive,$done,$no_repeat) {
	global $bdd;
	$group_list = '';
	if($type == "list" AND $no_repeat AND isset($done[$group_id])) return '';
	$query_group = "SELECT * FROM ".BASE.".groups WHERE id = \"".$group_id."\"";
	$result_group = $bdd->query($query_group);
	$ligne_group = $result_group->fetch();
	$result_group->closeCursor();
	if(isset($ligne_group['parent'])) $parent = $ligne_group['parent'];
	else $parent = 0;
	if($recursive AND $parent > 0) {
		$parent_list = group_link($type,$parent,$with_link,$recursive,$done,$no_repeat);
		if($type == "list") {
			if($parent_list <> '') $group_list = $parent_list.", ";
			}
		else $group_list = $parent_list." -> ";
		}
	if(isset($ligne_group['label'])) $label = $ligne_group['label'];
	else $label = '';
	if($type == "list") $link = $group_id;
	else if($with_link)
		$link = "<a target=\"_blank\" title=\"Display songs in this group\" href=\"songs.php?group_label=".$label."\">".$label."</a>";
	else $link = $label;
	$group_list .= $link;
	return $group_list;
	}

function first_group_in_list($song_id) {
	$group_list = trim(group_list(FALSE,'list',FALSE,$song_id,TRUE));
//	echo $song_id." group_list = ".$group_list."<br />";
	if($group_list == '') return '';
	$table = explode(",",$group_list);
	return trim($table[0]);
	}

function firstline($texte) {
	$table = explode("<br />",$texte);
	return trim($table[0]);
	}

function all_group_comments($group_id) {
	global $bdd;
	$comments = array(); $i = 0;
	$query_group = "SELECT * FROM ".BASE.".groups WHERE id = \"".$group_id."\"";
//	echo $query_group."<br />";
	$result_group = $bdd->query($query_group);
	if($result_group) $n = $result_group->rowCount();
	else $n = 0;
	$comment_mr = $comment_en = '';
	if($n > 0) {
		$ligne_group = $result_group->fetch();
		$parent_group_id = $ligne_group['parent'];
		if($parent_group_id > 0) {
			$comments = all_group_comments($parent_group_id);
			$i = count($comments);
			}
		$comment_mr = $ligne_group['comment_mr'];
		$comment_en = $ligne_group['comment_en'];
		}
	if($result_group) $result_group->closeCursor();
//	if($comment_mr <> '') {
		$comments[$i]['mr'] = $comment_mr;
//		}
//	if($comment_en <> '') {
		$comments[$i]['en'] = $comment_en;
//		}
	return $comments;
	}

function generic_class_id($condition,$specific_class_id,$test) {
	global $bdd;
	if($specific_class_id == '') return '';
	$query_class = "SELECT id, specific_class_id FROM ".BASE.".glossary WHERE (".$condition.") AND definition <> \"\" AND definition <> \"(new class definition)\" AND specific_song_id = \"0\" AND specific_group_id = \"0\" AND specific_class_id  <> \"\" AND specific_class_id  <> \"".$specific_class_id."\" ORDER BY specific_class_id DESC";
	$query_class = "SELECT id, specific_class_id FROM ".BASE.".glossary WHERE (".$condition.") AND definition <> \"\" AND definition <> \"(new class definition)\" AND specific_song_id = \"0\" AND specific_group_id = \"0\" AND specific_class_id  <> \"\" ORDER BY specific_class_id DESC";
	$result_class = $bdd->query($query_class);
	if($test) echo "<br />query_generic_class = ".$query_class."<br />";
	if($result_class) {
		$n_class = $result_class->rowCount();
		if($test) echo "n_class = ".$n_class."<br />";
		while($ligne_class = $result_class->fetch()) {
			$class_id = $ligne_class['specific_class_id'];
			$id = $ligne_class['id'];
			if($test) echo "• ".$id." ".$class_id;
			if(is_integer($pos=strpos($specific_class_id,$class_id)) AND $pos == 0) {
				if($test) echo " ok<br />";
				return $class_id;
				}
			else if($test) echo "<br />";
			}
		}
	return $specific_class_id;
	}

function choix_definition($word,$song_id,$all_groups_this_song,$first_in_line,$test) {
	global $bdd;
//	$test = TRUE;
	$date = date('Y-m-d H:i:s');
	$choix_definition = array();
	$choix_definition['mode'] = "default";
	$choix_definition['plural'] = '';
	$choix_definition['definition_default'] = $choix_definition['definition'] = '';
	$choix_definition['word_id_class'] = 0;
	$choix_definition['word_id_group'] = 0;
	$choix_definition['specific_class_id'] = '';
	$choix_definition['specific_group_id'] = '';
	$choix_definition['generic_class_id'] = 0;
	$choix_definition['word'] = $word;
	$choix_definition['plural'] = '';
	$choix_definition['definition'] = '';
	$choix_definition['id'] = $choix_definition['word_id_default'] = 0;
	$choix_definition['group_select'] = -1;
	$new_word = FALSE;
	$low_word = lcfirst($word);
	$condition = "word = \"".$word."\" OR plural = \"".$word."\"";
	if($first_in_line)
		$condition .= " OR word = \"".$low_word."\" OR plural = \"".$low_word."\"";
	$query_default = "SELECT id, word, definition, plural FROM ".BASE.".glossary WHERE (".$condition.") AND specific_song_id = \"0\" AND specific_group_id = \"0\" AND specific_class_id = \"\"";
	if($test) {
		echo "<hr style=\"border-color:Gold; border-style:solid; color:Gold; border-width:3px;\">";
		echo "<span style=\"color:red\"><b>".$word."</b></span><br />";
		echo $query_default."<br />";
		}
//	$n_default = -1;
	$result_default = $bdd->query($query_default);
	if($result_default) {
		$n = $result_default->rowCount();
		$ligne_default = $result_default->fetch();
		if($n > 0) {
			$choix_definition['id'] = $choix_definition['word_id_default'] = $ligne_default['id'];
			$choix_definition['definition'] = $choix_definition['definition_default'] = $ligne_default['definition'];
			$choix_definition['plural'] = $ligne_default['plural'];
			$choix_definition['word'] = $ligne_default['word'];
			}
		if($n == 1 AND $ligne_default['definition'] == '') $new_word = TRUE;
		$result_default->closeCursor();
		}
	else $n = 0;
	if($n == 0) { // This should never happen
		$definition = "(missing definition)";
		$sort = ucfirst($word);
		$letter_range_this_word = $sort[0];
		$query_insert = "INSERT INTO ".BASE.".glossary (word, sort, plural, song_id, letter_range, definition, date) VALUES (\"".$word."\", \"".$word."\", \"\", \"".$song_id."\", \"".$letter_range_this_word."\", \"".$definition."\", \"".$date."\")";
		if($test) echo "Should never happen? ".$query_insert."<br />";
		$result_insert = $bdd->query($query_insert);
		if($result_insert) $result_insert->closeCursor();
		$query_default = "SELECT id, definition FROM ".BASE.".glossary WHERE word = \"".$word."\" AND specific_song_id = \"0\" AND specific_group_id = \"0\" AND specific_class_id = \"\"";
		$result_default = $bdd->query($query_default);
		$ligne_default = $result_default->fetch();
		$choix_definition['id'] = $choix_definition['word_id_default'] = $ligne_default['id'];
		$choix_definition['definition'] = $choix_definition['definition_default'] = $ligne_default['definition'];
		$result_default->closeCursor();
//		return $choix_definition;
		}
	
	$nclass = 0;
	$query_class = "SELECT id, specific_class_id FROM ".BASE.".glossary WHERE (".$condition.") AND definition = \"\" AND specific_song_id = \"".$song_id."\" AND specific_group_id = \"0\" AND specific_class_id  <> \"\"";
	$result_class = $bdd->query($query_class);
	if($test) echo "<br />First query_class = ".$query_class."<br />";
	if($result_class) {
		$n_class = $result_class->rowCount();
		$ligne_class = $result_class->fetch();
		if($n_class > 0) {
			$choix_definition['specific_class_id'] = $ligne_class['specific_class_id'];
			if($test) echo "ligne_class['id'] = ".$ligne_class['id']."<br />";
			$query_class2 = "SELECT id, definition FROM ".BASE.".glossary WHERE (".$condition.") AND definition <> \"\" AND specific_song_id = \"0\" AND specific_group_id = \"0\" AND specific_class_id = \"".$ligne_class['specific_class_id']."\"";
			if($test) echo "Query_class2 = ".$query_class2."<br />";
			$result_class2 = $bdd->query($query_class2);
			if($result_class2) {
				$n_class = $result_class2->rowCount();
				$ligne_class2 = $result_class2->fetch();
				}
			$result_class2->closeCursor();
			}
		$result_class->closeCursor();
		}
	else $n_class = 0;
	if($test) echo "Class attributed, n_class = ".$n_class."<br />";
	
	if($choix_definition['specific_class_id'] == '')
		$spec = semantic_class_id($song_id);
	else $spec = $choix_definition['specific_class_id'];
	if(!$new_word) $choix_definition['generic_class_id'] = generic_class_id($condition,$spec,$test);
	else $choix_definition['generic_class_id'] = '';
	if($test) echo "choix_definition['generic_class_id'] = ".$choix_definition['generic_class_id']."<br />";
	
	$query_specific = "SELECT id, definition FROM ".BASE.".glossary WHERE (".$condition.") AND definition <> \"\" AND specific_song_id = \"".$song_id."\" AND specific_group_id = \"0\" AND specific_class_id = \"\"";
	$result_specific = $bdd->query($query_specific);
	if($test) echo "<br />query_specific = ".$query_class."<br />";
	if($result_specific) {
		$n = $result_specific->rowCount();
		$ligne_specific = $result_specific->fetch();
		if($n > 0) {
			$choix_definition['id'] = $ligne_specific['id'];
			$choix_definition['definition'] = $ligne_specific['definition'];
			$choix_definition['mode'] = "specific";
			}
		$result_specific->closeCursor();
		}
	else $n = 0;
	if($n > 0) return $choix_definition;
	$query_generic = "SELECT id FROM ".BASE.".glossary WHERE (".$condition.") AND definition = \"\" AND specific_song_id = \"".$song_id."\" AND specific_group_id = \"0\" AND specific_class_id = \"\"";
	$result_generic = $bdd->query($query_generic);
	if($test) echo "<br />query_generic = ".$query_class."<br />";
	if($result_generic) {
		$n = $result_generic->rowCount();
		$ligne_generic = $result_generic->fetch();
		if($n > 0) {
			$choix_definition['id'] = $ligne_generic['id'];
			$choix_definition['mode'] = "generic";
			if($test) echo "Mode = generic, defined by #".$choix_definition['id']."<br />";
			}
		$result_generic->closeCursor();
		}
	else $n = 0;
	if($n > 0) return $choix_definition;
	
	if($test) echo "<br />all_groups_this_song = ".$all_groups_this_song."<br />";
	
	$table = explode(",",$all_groups_this_song);
	$use_this_group = $done_group = FALSE;
	// for($i = 0; $i < count($table); $i++) {
	for($i = count($table) - 1; $i >= 0; $i--) {
		$group = trim($table[$i]);
		if($group == '') continue;
		if($use_this_group) break; // By default the first group is selected
		$query_group = "SELECT id, definition FROM ".BASE.".glossary WHERE (".$condition.") AND definition <> \"\" AND specific_group_id = \"".$group."\"";
		$result_group = $bdd->query($query_group);
		if($test) echo "<br />query_group = ".$query_group."<br />";
		if($result_group) {
			$n_group = $result_group->rowCount();
			$ligne_group = $result_group->fetch();
			if($n_group > 0) {
				if(!$done_group) {
					$choix_definition['id'] = $ligne_group['id'];
					$choix_definition['specific_group_id'] = $group;
					$choix_definition['word_id_group'] = $ligne_group['id'];
					$choix_definition['definition'] = $ligne_group['definition'];
					$choix_definition['group_select'] = $i;
					$done_group = TRUE;
					$choix_definition['mode'] = "group";
					}
				$query_group2 = "SELECT id FROM ".BASE.".glossary WHERE (".$condition.") AND specific_song_id = \"".$song_id."\" AND specific_group_id = \"".$group."\"";
				if($test) echo "query_group2 = ".$query_group2."<br />";
				$result_group2 = $bdd->query($query_group2);
				if($result_group2) {
					$n_group = $result_group2->rowCount();
					if($test) echo "n_group = ".$n_group."<br />";
					$ligne_group2 = $result_group2->fetch();
					$result_group2->closeCursor();
					}
				}
			if($n_group > 0) {
				$use_this_group = TRUE;
				$choix_definition['id'] = $ligne_group2['id'];
				$choix_definition['specific_group_id'] = $group;
				$choix_definition['word_id_group'] = $ligne_group['id'];
				$choix_definition['definition'] = $ligne_group['definition'];
				$choix_definition['group_select'] = $i;
				if($test) echo "use_this_group = TRUE, id = ".$choix_definition['id']." definition = ".$choix_definition['definition']."<br />";
				}
			$result_group->closeCursor();
			}
		else $n_group = 0;
		}
	
	if($test AND !$use_this_group) echo "use_this_group = FALSE<br />";
	
	if($test) echo "definition = ".$choix_definition['definition']."<br />";
	
	if($use_this_group) return $choix_definition;
	if($n_class > 0) {
		$choix_definition['mode'] = "class";
		$choix_definition['id'] = $ligne_class2['id'];
		$choix_definition['definition'] = $ligne_class2['definition'];
		$choix_definition['word_id_class'] = $ligne_class2['id'];
		if($test) echo "<br />➡ song_id = ".$song_id.", n_class = ".$n_class.", mode = ".$choix_definition['mode'].", definition = ".$choix_definition['definition'].", word_id_class = ".$choix_definition['word_id_class'].", word_id_group = ".$choix_definition['word_id_group']."<br />";
		}
	else {
	//	if($use_this_group) return $choix_definition;
		if($choix_definition['mode'] == "group") return $choix_definition;
		if($choix_definition['generic_class_id'] <> '') {
			$class = $choix_definition['generic_class_id'];
	//		$query_class = "SELECT id, definition FROM ".BASE.".glossary WHERE (".$condition.") AND definition <> \"\" AND specific_song_id = \"0\" AND specific_group_id = \"0\" AND specific_class_id  = \"".$class."\"";
			$query_class = "SELECT id, definition FROM ".BASE.".glossary WHERE (".$condition.") AND definition <> \"\" AND specific_class_id  = \"".$class."\"";
			$result_class = $bdd->query($query_class);
			if($test) echo "<br />query_class = ".$query_class."<br />";
			$n = $result_class->rowCount();
			if($n > 0) {
				$ligne_class = $result_class->fetch();
				$choix_definition['mode'] = "class";
				$choix_definition['definition'] = $ligne_class['definition'];
				$choix_definition['word_id_class'] = $ligne_class['id'];
				}
			}
		if($test) echo "<br />➡➡ song_id = ".$song_id.", n_class = ".$n_class.", mode = ".$choix_definition['mode'].", definition = ".$choix_definition['definition'].", word_id_class =".$choix_definition['word_id_class'].", word_id_group =".$choix_definition['word_id_group']."<br />";
		}
	return $choix_definition;
	}
	
function glossary_form($song_id,$text,$edit,$size) {
	global $bdd;
	$test = FALSE;
//	$test = TRUE;
	if($text == '') return '';
	$form = ''; $tag = '*'; $n_words = 0; $done = array();
	$first_in_line = TRUE;
	$table_words = all_english_words($text);
	$semantic_class_this_song = semantic_class_id($song_id);
	// $group_this_song = first_group_in_list($song_id);
	$all_groups_this_song = group_list(TRUE,'list',FALSE,$song_id,TRUE);
	$group = $done = array();
	$table = explode(",",$all_groups_this_song);
	$number_groups = count($table);
	for($j = 0; $j < $number_groups; $j++) {
		$group[$j] = trim($table[$j]);
		$group_name[$j] = group_link("label",$group[$j],FALSE,FALSE,$done,FALSE);
		}
	
	for($i_word = 0; $i_word < count($table_words); $i_word++) {
		$word = trim($table_words[$i_word]);
		if($test) echo $word."<br />";
		if($word == "[BR]") {
			$first_in_line = TRUE;
			continue;
			}
		if($word == '') continue;
		if(is_integer(strpos($word,$tag))) {
			$word = simple_form(str_replace($tag,'',$word));
			$word = str_replace("||","’",$word);
		/*	if(isset($done[$word])) continue;
			else $done[$word] = TRUE; */
			$n_words++;
			$low_word = lcfirst($word);
			$choix_definition = choix_definition($word,$song_id,$all_groups_this_song,$first_in_line,$test);
			$old_definition = $choix_definition['definition'];
			$word_id = $choix_definition['id'];
			$mode = $choix_definition['mode'];
			if($mode == "default") $mode = "generic";
			if($test) echo "Mode = ".$mode."<br />";
			$plural = $choix_definition['plural'];
			$definition_default = $choix_definition['definition_default'];
			$word_id_default = $choix_definition['word_id_default'];
			$word_id_class = $choix_definition['word_id_class'];
			$word_id_group = $choix_definition['word_id_group'];
			$specific_class_id = $choix_definition['specific_class_id'];
			$generic_class_id = $choix_definition['generic_class_id'];
			$group_select = $choix_definition['group_select'];
			$word = $choix_definition['word']; // May have changed from plural to singular
			if(isset($done[$word_id])) continue; 
			else $done[$word_id] = TRUE; // Avoid repetition of words
			if($n_words == 1) {
				$form = '';
				if($edit) {
					$group_label_list = group_list(TRUE,"label",TRUE,$song_id,FALSE);	
					if($group_label_list <> '') {
						if(is_integer(strpos($group_label_list,"->")))
							$group_label_list = "</p><ul><li style=\"padding-bottom:4px;\">".str_replace(", ","</li><li style=\"padding-bottom:4px;\">",$group_label_list)."</li></ul>";
						else $group_label_list .= "</p>";
						$form .= "<p>Group(s) = ".$group_label_list;
						}
					}
				$form .= "<table style=\"border-collapse:separate; border-spacing:4px;\">";
				}
			$form .= "<tr><td style=\"vertical-align:middle; border-radius:15%; background-color:cyan; padding:0px; padding-left:6px; padding-right:6px;\">".str_replace('_',"&nbsp;",$word);
			if($plural <> '') $form .= "&nbsp;➡&nbsp;".str_replace('_',"&nbsp;",$plural);
			if($test) $form .= "<br />".$word_id;
			$form .= "</td>";
			$form .= "<td style=\"vertical-align:middle; padding:0px; padding-left:6px;\">";
			if($old_definition == '') $definition = $definition_default;
			else $definition = $old_definition;
			if($test) echo "specific_class_id = ".$specific_class_id.", generic_class_id = ".$generic_class_id."<br />";
			if($specific_class_id == '') $specific_class_id = $generic_class_id;
			if($mode <> "class" AND $mode <> "group") $specific_class_id = $generic_class_id;
			if($specific_class_id == '') $specific_class_id = $semantic_class_this_song;
			if(!$edit) {
				$form .= $definition;
				if(identified()) {
					if($mode == "specific") $form .= " ➡&nbsp;<i>in this song</i>";
					if($mode == "class") $form .= " ➡&nbsp;<i>in class</i> ‘".$specific_class_id."’";
					if($mode == "group" AND $number_groups > 1) {
						$form .= " ➡&nbsp;<i>in group </i>";
						$form .= "‘".$group_name[$group_select]."’ ";
						}
					}
				}
			else {
				$form .= "<textarea name=\"definition_".$word_id."\" rows=\"2\" style=\"width:".$size."px;\">".str_replace("<br />","\n",$definition)."</textarea>";
				$more_meanings_link = more_meanings("link",$word_id,$word,$plural);
				if($more_meanings_link <> '')
					$form .= "<br />➡ ".$more_meanings_link;
				$form .= "</td>";
				$form .= "<td style=\"vertical-align:middle; text-align:left;\">";
				if($definition <> $definition_default) $form .= "<i>".$definition_default."</i>";
		//		$form .= "<br />".$mode;
				$form .= "</td>";
				$form .= "<td style=\"white-space:nowrap;\">";
				$form .= "<input type=\"radio\" name=\"new_mode_".$word_id."\" value=\"generic\" ";
				if($mode == "generic") $form .= "checked";
				$form .= ">Generic&nbsp;definition<br />";
				$form .= "<input type=\"radio\" name=\"new_mode_".$word_id."\" value=\"specific\" ";
				if($mode == "specific") $form .= "checked";
				$form .= ">This&nbsp;song<br />";
				
				$form .= "<input type=\"hidden\" name=\"all_groups_this_song_".$word_id."\" value=\"".$all_groups_this_song."\">";
				$old_group_id = 0;
				if($group_select >= 0) $old_group_id  = $group[$group_select];
				$form .= "<input type=\"hidden\" name=\"old_group_id_".$word_id."\" value=\"".$old_group_id."\">";
				for($j = 0; $j < $number_groups; $j++) {
					if($group_name[$j] <> '') {
						$form .= "<input type=\"radio\" name=\"new_mode_".$word_id."\" value=\"group_".$j."\"";
						if($mode == "group" AND $group_select == $j) $form .= "checked";
						$form .= ">Group ‘".$group_name[$j]."’<br />";
						}
					}
				$form .= "<input type=\"radio\" name=\"new_mode_".$word_id."\" value=\"class\" ";
				if($mode == "class") $form .= "checked";
				$form .= ">Semantic&nbsp;class";
				if($mode == "class") {
					$form .= "&nbsp;<input type=\"text\" name=\"specific_class_id_".$word_id."\" size=\"15\" value=\"";
				//	if($specific_class_id == '') $specific_class_id = $semantic_class_this_song;
					$form .= $specific_class_id;
					$form .= "\">";
					}
				else {
					$form .= "&nbsp;(".$specific_class_id.")";
					$form .= "<input type=\"hidden\" name=\"specific_class_id_".$word_id."\" value=\"".$specific_class_id."\">";
					}
				$form .= "<input type=\"hidden\" name=\"old_class_id_".$word_id."\" value=\"".$specific_class_id."\">";
				$form .= "</td>";
				$form .= "<input type=\"hidden\" name=\"word_".$word_id."\" value=\"".$word."\">";
				$form .= "<input type=\"hidden\" name=\"plural_".$word_id."\" value=\"".$plural."\">";
				$form .= "<input type=\"hidden\" name=\"mode_".$word_id."\" value=\"".$mode."\">";
				$form .= "<input type=\"hidden\" name=\"old_def_".$word_id."\" value=\"".$old_definition."\">";
				$form .= "<input type=\"hidden\" name=\"word_id_default_".$word_id."\" value=\"".$word_id_default."\">";
				$form .= "<input type=\"hidden\" name=\"word_id_class_".$word_id."\" value=\"".$word_id_class."\">";
				$form .= "<input type=\"hidden\" name=\"word_id_group_".$word_id."\" value=\"".$word_id_group."\">";
				$form .= "<input type=\"hidden\" name=\"def_default_".$word_id."\" value=\"".$definition_default."\">";
				}
			$form .= "</td></tr>";
			}
		$first_in_line = FALSE;
		}
	if($edit) {
		if($n_words == 0) $form = "<table style=\"border-collapse:separate; border-spacing:4px;\">";
		$form .= "<tr>";
		$form .= "<td style=\"vertical-align:middle; text-align:right; border-radius:15%; background-color:yellow; padding:6px;\">";
		$form .= "New entry&nbsp;=&nbsp;";
		$form .= "</td>";
		$form .= "<td colspan=\"2\" style=\"vertical-align:middle; background-color:yellow; padding:6px; white-space:nowrap;\">";
		$form .= "<input type=\"text\" name=\"new_word\" size=\"15\" value=\"\" />";
		$form .= " Plural&nbsp;=&nbsp;";
		$form .= "<input type=\"text\" name=\"new_plural\" size=\"15\" value=\"\" />";
		$form .= "&nbsp;<input type=\"checkbox\" name=\"new_force_case\" value=\"ok\" /><•••&nbsp;Force case";
		$form .= "</td></tr>";
		$form .= "<input type=\"hidden\" name=\"song_id\" value=\"".$song_id."\">";
		}
	if($n_words > 0 OR $edit) $form .= "</table>";
	return $form;
	}

function all_english_words($text) {
	$text = ' '.str_replace("<br />"," [BR] ",$text).' ';
//	echo $text."<br />";
	$text = str_replace('(',' ',$text);
	$text = str_replace(')',' ',$text);
	$text = str_replace('?',' ',$text);
	$text = str_replace('!',' ',$text);
	$text = str_replace('.',' ',$text);
	$text = str_replace(',',' ',$text);
	$text = str_replace('।',' ',$text);
	$text = str_replace('/',' ',$text);
	$text = str_replace('…',' ',$text);
	$text = str_replace(';',' ',$text);
	$text = str_replace(':',' ',$text);
	$text = str_replace('“',' ',$text);
	$text = str_replace('”',' ',$text);
	$text = str_replace('"',' ',$text);
	$text = str_replace("'","’",$text);
	$text = str_replace("’s ",' ',$text);
	$text = str_replace("’s_",'_',$text);
	$text = str_replace("’ ",' ',$text);
	$text = str_replace("’_",'_',$text);
//	$text = str_replace("'",' ',$text);
//	$text = str_replace("’",' ',$text);
//	$text = str_replace("-",' ',$text);
	do $text = str_replace("  ",' ',$text,$count);
	while($count > 0);
	$table = explode(' ',$text);
	return $table;
	}
	
function more_meanings($type,$word_id,$word,$plural) {
	global $bdd;
	$list = array();
	if($plural == '')
		$query = "SELECT * FROM ".BASE.".glossary WHERE id <> \"".$word_id."\" AND word = \"".$word."\" AND definition <> \"\"";
	else
		$query = "SELECT * FROM ".BASE.".glossary WHERE id <> \"".$word_id."\" AND (word = \"".$word."\" OR plural = \"".$plural."\") AND definition <> \"\"";
	// echo $query."<br />";
	$result = $bdd->query($query);
	$n = $result->rowCount();
	if($n < 1) return '';
	if($type == "link") {
		$url = "glossary-detail.php?word=".$word;
		$link = "<a target=\"_blank\" title=\"".$n." more meanings\" href=\"".$url."\">More meanings…</a>";
		$result->closeCursor();
		return $link;
		}
	while($ligne = $result->fetch()) {
		$id = $ligne['id'];
		$list[] = $id;
		}
	$result->closeCursor();
	}

function bugs($song_id,$bugsign) {
	global $bdd;
	$bugs = '';
	$query = "SELECT * FROM ".BASE.".songs WHERE song_id = \"".$song_id."\"";
	$result = $bdd->query($query);
	$n = $result->rowCount();
	$ligne = $result->fetch();
	$devanagari = $ligne['devanagari'];
	$roman_devanagari = $ligne['roman_devanagari'];
//	if(!is_integer(strpos($devanagari,"<br />"))) $bugs .= "☂";
	if(is_integer(strpos($roman_devanagari,"[???]"))) $bugs .= $bugsign; 
	if($bugs <> '') return $bugs;
	if(is_integer(strpos($roman_devanagari,"r̥"))) $bugs .= "r̥";
	if(is_integer(strpos($roman_devanagari,"'"))) $bugs .= " '";
	$devanagari = str_replace("<br />","\n",$devanagari);
	$devanagari_words = all_words('',$devanagari);
	$devanagari_count = count($devanagari_words);
	$roman_transliteration = Transliterate(0,"<br />",trim($ligne['devanagari']));
	$roman_devanagari_source = str_replace(" <br />","<br />",trim($roman_devanagari));
	$roman_devanagari = str_replace("<br />","\n",$roman_devanagari_source);		
	$roman_words = all_words('',$roman_devanagari);
	$roman_count = count($roman_words);
	if($devanagari_count <> $roman_count) $bugs .= $bugsign;
	else if($roman_transliteration <> $roman_devanagari_source) {
//		echo "<p>".$roman_transliteration."<br />".$roman_devanagari_source."</p>";
		$bugs .= $bugsign;
		}
	return $bugs;
	}

function send_mail($mailfrom,$user,$mailto,$subject,$texte) {
	$name = str_replace('_',' ',$user);
	$to = "\"".$name."\" <".$mailto.">";
	$from = $mailfrom;
//	ini_set("SMTP","smtp.ccrss.org");
	ini_set("SMTP","mail-fr.securemail.pro");
	$JOUR  = date("Y-m-d");
	$HEURE = date("H:i");
	$headers = "From: ".$from." \n";
	$headers .= "Reply-to: ".$from." \n";
	$headers .= "MIME-Version: 1.0 \n";
	$headers .= "X-Priority: 1  \n";
	$headers .= "X-MSMail-Priority: High \n";
	$headers .= "Content-Type: text/html; charset=utf-8 \n";
	$mail_Data = "<html> \n";
	$mail_Data .= "<head> \n";
	$mail_Data .= "<title>".$subject."</title> \n";
	$mail_Data .= "</head> \n";
	$mail_Data .= "<body> \n";
	$mail_Data .= $texte."<br><br> \n";
	$mail_Data .= "</body> \n";
	$mail_Data .= "</html> \n";
	$CR_Mail = mail($to,$subject,$mail_Data,$headers);
	if($CR_Mail === FALSE)
   		echo "<p style=\"width:100%; text-align:center; color:red;\">Error sending email</p>";
	return $CR_Mail;
	}

function add_to_rule_history($new_leftarg,$new_rightarg,$old_leftarg,$old_rightarg,$song_id,$rule_id) {
	global $bdd, $login;
	if($new_leftarg == '' AND $new_rightarg == '') $action = "delete";
	else if($old_leftarg == '') $action = "create";
	else $action = "modify";
	$query_update = "INSERT INTO ".BASE.".rules_history (action, new_leftarg, new_rightarg, old_leftarg, old_rightarg, song_id, rule_id, login) VALUES (\"".$action."\",\"".$new_leftarg."\",\"".$new_rightarg."\",\"".$old_leftarg."\",\"".$old_rightarg."\",\"".$song_id."\",\"".$rule_id."\",\"".$login."\")";
//	if($login == "Bernard") echo $query_update."<br />";
	$result_update = $bdd->query($query_update);
	if(!$result_update) {
		echo "<br /><font color=red>ERROR modifying table:</font> ".$query_update."<br />";
		die();
		}
	$result_update->closeCursor();
	return;
	}

function delete_picture($type,$id) {
	global $allowedExts,$bdd;
	if($type == "performer") {
		$photo_path = PICT_PATH2."performers/";
		$link_to_picture = link_to_picture($photo_path,$allowedExts,$id);
		echo "<p style=\"width:100%; text-align:center; color:red;\">Deleted ".$link_to_picture."</p>";
		$command = "rm -f ".$link_to_picture;
		if(!is_integer(strpos($link_to_picture,"/0."))) exec($command);
		$query = "UPDATE ".BASE.".performers SET performer_picture = \"\", performer_photo_credit = \"\" WHERE performer_id = \"".$id."\"";
	//	echo $query."<br />"; 
		$result = $bdd->query($query);
		if($result) $result->closeCursor();
		else {
			echo "<br /><font color=red>".$query."<br />";
			echo "ERROR: FAILED</font>";
			}
		}
	return;
	}

function link_to_picture($photo_path,$allowedExts,$id) {
	for($i = 0; $i < count($allowedExts); $i++) {
		$ext = $allowedExts[$i];
		$file = $photo_path.$id.".".$ext;
		if(file_exists($file)) return $file;
		}
	return $photo_path."0.jpg";
	}

function resize_image($id,$newwidth,$type) {
	global $allowedExts;
	if($type == "performer") {
		$photo_path = PICT_PATH2."performers/";
		$file = link_to_picture($photo_path,$allowedExts,$id);
		if(is_integer(strpos($file,"/0."))) return;
	    list($width,$height) = getimagesize($file);
	    $filesize = filesize($file);
	    $ratio = $newwidth / $width;
		if($ratio >= 1 AND $filesize < MAX_STORED_FILESIZE) return;
	    $table = explode(".",$file);
		$extension = strtolower(end($table));
		$type = '';
		if($extension == "jpeg" OR $extension == "jpg") $type = "jpeg";
		if($extension == "png") $type = "png";
		if($type == '') return;
		$newheight = intval($height * $ratio);
	   	if($type == "jpeg") $src = imagecreatefromjpeg($file);
	    if($type == "png") $src = imagecreatefrompng($file);
	    if($src == FALSE) {
	    	echo "Error resizing image.<br />"; return FALSE;
	    	}
	    $dst = imagecreatetruecolor($newwidth,$newheight);
	    imagecopyresampled($dst,$src,0,0,0,0,$newwidth,$newheight,$width,$height);
		if($type == "jpeg") imagejpeg($dst,$photo_path.$id.".".$extension);
		if($type == "png") imagepng($dst,$photo_path.$id.".".$extension);
		}
    return;
	}

function extractWords($string,$n) {
	// Remove HTML tags and trim whitespace
	$cleanString = strip_tags(trim($string));

	// Explode the string into an array of words
	$words = explode(' ', $cleanString);

	// Take the first 'n' words from the array
	$extractedWords = array_slice($words,0,$n);

	// Join the extracted words back into a string
	$result = implode(' ',$extractedWords);
	if(trim($result) <> '') $result .= "…";
	return $result;
	}
// Do not put any space nor line feed after closing php tag below!
?>